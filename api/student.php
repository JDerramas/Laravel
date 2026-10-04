<?php
/**
 * api_student.php — Server-Side API for Student Portal Services
 * 
 * Handles:
 *  - My Grades (only approved & published)
 *  - Enrolled subjects & schedule
 *  - Attendance history & attendance rate % per subject
 *  - Notifications & mark read
 *  - Document requests (COR, COE, Good Moral, Transcript)
 *  - Profile update request workflow
 *  - Faculty consultation booking
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/supabase_helper.php';

require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$currentUserEmail = strtolower($_SESSION['email'] ?? '');
$currentUserName = $_SESSION['name'] ?? 'Student';
$currentStudentNumber = $_SESSION['student_number'] ?? '';
$currentUserRole = $_SESSION['role'] ?? 'student';
$baseRole = $_SESSION['base_role'] ?? $currentUserRole;

if (empty($currentStudentNumber) || in_array($currentStudentNumber, ['ADMIN-001', 'FAC-001', 'N/A', 'GUEST'])) {
    if (in_array($currentUserRole, ['admin', 'teacher']) || in_array($baseRole, ['admin', 'teacher'])) {
        $currentStudentNumber = '2024-00192';
    }
}
session_write_close();

// ─── 1. GET: Fetch Published Grades & Breakdown ────────────────────────────────
if ($method === 'GET' && $action === 'get_my_grades') {
    if (empty($currentStudentNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student number not found in session.']);
        exit;
    }

    // Query official `grades` table (published records)
    $gQuery = supabaseServiceQuery("/rest/v1/grades?student_number=eq." . urlencode($currentStudentNumber) . "&order=subject_code.asc");
    $grades = ($gQuery['status'] === 200 && is_array($gQuery['data'])) ? $gQuery['data'] : [];

    // Also fetch detailed period breakdown from student_grades where is_published = true
    $sgQuery = supabaseServiceQuery("/rest/v1/student_grades?student_number=eq." . urlencode($currentStudentNumber) . "&is_published=eq.true");
    $detailedGrades = ($sgQuery['status'] === 200 && is_array($sgQuery['data'])) ? $sgQuery['data'] : [];

    // Index detailed by class_id
    $detailsMap = [];
    foreach ($detailedGrades as $dg) {
        $detailsMap[$dg['class_id']] = $dg;
    }

    // Compute cumulative GPA
    $totalEnrolledUnits = 0;
    $gradedUnits = 0;
    $weightedSum = 0;
    $passedCount = 0;
    $failedCount = 0;
    $incCount = 0;

    foreach ($grades as &$g) {
        $units = floatval($g['units'] ?? 3.0);
        $totalEnrolledUnits += $units;
        $gradeVal = floatval($g['grade'] ?? 0);
        $status = $g['status'] ?? 'Ongoing';

        if ($gradeVal > 0) {
            $gradedUnits += $units;
            $weightedSum += ($units * $gradeVal);
            if ($gradeVal <= 3.00 && $gradeVal >= 1.00) $passedCount++;
            else if ($gradeVal > 3.00) $failedCount++;
        }
        if (strtoupper($status) === 'INC') $incCount++;
    }

    $gpa = $gradedUnits > 0 ? round($weightedSum / $gradedUnits, 2) : 0;

    echo json_encode([
        'success' => true,
        'grades' => $grades,
        'detailed' => $detailsMap,
        'summary' => [
            'gpa' => $gpa > 0 ? number_format($gpa, 2) : '—',
            'total_units' => $totalEnrolledUnits,
            'passed' => $passedCount,
            'failed' => $failedCount,
            'inc' => $incCount,
            'term' => '1st Semester, 2026-2027'
        ]
    ]);
    exit;
}

// ─── 2. GET: Fetch Enrolled Classes & Schedules ────────────────────────────────
if ($method === 'GET' && $action === 'get_enrolled_schedule') {
    // Resolve student program and section with fallback to session/students table
    $prog = $_SESSION['program'] ?? '';
    $sec = $_SESSION['section'] ?? '';

    if (empty($prog) || empty($sec)) {
        $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . urlencode($currentUserEmail) . "&limit=1");
        $user = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
        if ($user) {
            if (empty($prog)) $prog = $user['program'] ?? '';
            if (empty($sec)) $sec = $user['section'] ?? '';
        }
    }

    if (empty($prog)) $prog = 'AIS';
    if (empty($sec)) $sec = '2A';

    $progTrim = trim((string)$prog);
    $secTrim = trim((string)$sec);
    if (!empty($progTrim) && stripos($secTrim, $progTrim) === 0) {
        $section = $secTrim;
    } else {
        $section = trim("$progTrim $secTrim");
    }

    // Fetch classes for this section
    $cQuery = supabaseServiceQuery("/rest/v1/classes?order=code.asc");
    $allClasses = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];

    $secUpper = strtoupper($section);
    $myClasses = array_filter($allClasses, function($c) use ($secUpper, $progTrim, $secTrim) {
        $cSec = strtoupper(trim($c['section'] ?? ''));
        if ($cSec === $secUpper) return true;
        if (!empty($progTrim) && !empty($secTrim)) {
            if (str_contains($cSec, strtoupper($progTrim)) && str_contains($cSec, strtoupper($secTrim))) {
                return true;
            }
        }
        return false;
    });

    echo json_encode([
        'success' => true,
        'section' => $section,
        'classes' => array_values($myClasses)
    ]);
    exit;
}

// ─── 3. GET: Attendance History & Subject Percentage ───────────────────────────
if ($method === 'GET' && $action === 'get_attendance_metrics') {
    $attQuery = supabaseServiceQuery("/rest/v1/attendance_records?student_number=eq." . urlencode($currentStudentNumber) . "&order=check_in_at.desc");
    $records = ($attQuery['status'] === 200 && is_array($attQuery['data'])) ? $attQuery['data'] : [];

    $presentCount = 0;
    $lateCount = 0;
    $absentCount = 0;

    foreach ($records as $r) {
        $st = strtolower($r['status'] ?? 'present');
        if ($st === 'present') $presentCount++;
        elseif ($st === 'late') $lateCount++;
        elseif ($st === 'absent') $absentCount++;
    }

    $totalScans = count($records);
    $rate = $totalScans > 0 ? round((($presentCount + ($lateCount * 0.8)) / $totalScans) * 100, 1) : 100.0;

    echo json_encode([
        'success' => true,
        'records' => $records,
        'stats' => [
            'rate' => $rate . '%',
            'total_checkins' => $totalScans,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount
        ]
    ]);
    exit;
}

// ─── 4. POST: Request Official Document ─────────────────────────────────────────
if ($method === 'POST' && $action === 'request_document') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $docType = trim($input['document_type'] ?? '');
    $purpose = trim($input['purpose'] ?? '');

    $allowedDocs = [
        'Certificate of Registration (COR)',
        'Certificate of Enrollment (COE)',
        'Certificate of Good Moral Character',
        'Official Transcript of Records (OTR)',
        'Certified True Copy of Grades / Grade Slip'
    ];

    if (empty($docType) || empty($purpose)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Document type and purpose are required.']);
        exit;
    }

    // Generate unique reference number: NPC-DOC-[YEAR]-[RANDOM6]
    $refNo = 'NPC-DOC-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $docReq = [
        'student_number' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'student_email' => $currentUserEmail,
        'document_type' => $docType,
        'purpose' => $purpose,
        'reference_no' => $refNo,
        'status' => 'Pending'
    ];

    $res = supabaseServiceQuery("/rest/v1/document_requests", 'POST', [$docReq]);

    // Send confirmation notification
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $currentUserEmail,
        'title' => 'Document Request Submitted',
        'message' => "Your request for $docType (Ref: $refNo) has been received by the Registrar. Status: Pending.",
        'type' => 'document',
        'link_url' => 'academic.php'
    ]]);

    logSecurityEvent("DOC_REQUESTED: $docType (Ref: $refNo) by $currentUserEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'reference_no' => $refNo,
        'message' => "Document request successfully submitted! Tracking Ref: $refNo"
    ]);
    exit;
}

// ─── 5. GET: Fetch Document Requests History ───────────────────────────────────
if ($method === 'GET' && $action === 'get_document_requests') {
    $dQuery = supabaseServiceQuery("/rest/v1/document_requests?student_number=eq." . urlencode($currentStudentNumber) . "&order=requested_at.desc");
    $requests = ($dQuery['status'] === 200 && is_array($dQuery['data'])) ? $dQuery['data'] : [];

    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

// ─── 6. POST: Request Profile Update (Sensitive Record Security) ────────────────
if ($method === 'POST' && $action === 'request_profile_update') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $changes = $input['requested_changes'] ?? [];
    $reason = trim($input['reason'] ?? '');

    if (empty($changes) || empty($reason)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide the requested changes and reason.']);
        exit;
    }

    $reqData = [
        'student_number' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'student_email' => $currentUserEmail,
        'requested_changes' => $changes,
        'reason' => $reason,
        'status' => 'Pending'
    ];

    supabaseServiceQuery("/rest/v1/profile_update_requests", 'POST', [$reqData]);

    // Notify admin
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => 'admin@navotaspolytechniccollege.edu.ph',
        'title' => 'Profile Update Request',
        'message' => "Student $currentUserName ($currentStudentNumber) requested a profile change: $reason",
        'type' => 'system',
        'link_url' => 'admin_students.php'
    ]]);

    logSecurityEvent("PROFILE_UPDATE_REQUESTED: $currentStudentNumber by $currentUserEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'message' => 'Profile update request submitted to Registrar for verification.'
    ]);
    exit;
}

// ─── 7. GET: Notifications List ────────────────────────────────────────────────
if ($method === 'GET' && $action === 'get_notifications') {
    $nQuery = supabaseServiceQuery("/rest/v1/notifications?user_email=eq." . urlencode($currentUserEmail) . "&order=created_at.desc&limit=20");
    $notifs = ($nQuery['status'] === 200 && is_array($nQuery['data'])) ? $nQuery['data'] : [];

    $unreadCount = count(array_filter($notifs, fn($n) => !($n['is_read'] ?? false)));

    echo json_encode([
        'success' => true,
        'notifications' => $notifs,
        'unread_count' => $unreadCount
    ]);
    exit;
}

// ─── 8. POST: Mark Notifications as Read ───────────────────────────────────────
if ($method === 'POST' && $action === 'mark_notifs_read') {
    requireCsrf();
    supabaseServiceQuery(
        "/rest/v1/notifications?user_email=eq." . urlencode($currentUserEmail),
        'PATCH',
        ['is_read' => true]
    );

    echo json_encode(['success' => true]);
    exit;
}

// ─── 9. POST: Book Faculty Consultation ────────────────────────────────────────
if ($method === 'POST' && $action === 'book_consultation') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $facultyEmail = strtolower(trim($input['faculty_email'] ?? ''));
    $facultyName = trim($input['faculty_name'] ?? 'Faculty');
    $subjectCode = trim($input['subject_code'] ?? '');
    $reqDate = trim($input['date'] ?? '');
    $reqTime = trim($input['time'] ?? '');
    $topic = trim($input['topic'] ?? '');

    if (empty($facultyEmail) || empty($reqDate) || empty($reqTime) || empty($topic)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All consultation booking fields are required.']);
        exit;
    }

    $apptData = [
        'faculty_email' => $facultyEmail,
        'faculty_name' => $facultyName,
        'student_number' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'student_email' => $currentUserEmail,
        'subject_code' => $subjectCode,
        'requested_date' => $reqDate,
        'requested_time' => $reqTime,
        'topic' => $topic,
        'status' => 'Pending'
    ];

    supabaseServiceQuery("/rest/v1/consultation_appointments", 'POST', [$apptData]);

    // Notify faculty
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $facultyEmail,
        'title' => 'New Consultation Request',
        'message' => "$currentUserName ($currentStudentNumber) requested a consultation for $subjectCode on $reqDate at $reqTime: $topic",
        'type' => 'system',
        'link_url' => 'teacher.php'
    ]]);

    logSecurityEvent("CONSULTATION_BOOKED: Student $currentStudentNumber with $facultyEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'message' => 'Consultation appointment request submitted to your professor!'
    ]);
    exit;
}

// ─── 10. POST: Secure QR / Manual Attendance Check-in ──────────────────────────
// Identity is taken from the SERVER session — never from the request body.
if ($method === 'POST' && $action === 'checkin_attendance') {
    requireCsrf();

    // Rate limit: max 10 verification attempts per minute per session
    $now = time();
    $attempts = array_values(array_filter($_SESSION['checkin_attempts'] ?? [], fn($t) => $t > $now - 60));
    if (count($attempts) >= 10) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many attempts. Please wait a minute and try again.']);
        exit;
    }
    $attempts[] = $now;
    $_SESSION['checkin_attempts'] = $attempts;

    if (empty($currentStudentNumber) || $currentStudentNumber === 'GUEST') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No verified student identity in session.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $rawToken = trim($input['code'] ?? '');
    $checkMethod = ($input['method'] ?? 'manual') === 'qr_code' ? 'qr_code' : 'manual';

    // Normalize the code (mirrors legacy client logic)
    $code = strtoupper($rawToken);
    $parsed = json_decode($rawToken, true);
    if (is_array($parsed) && !empty($parsed['session'])) {
        $code = strtoupper(trim($parsed['session']));
    }
    $code = trim($code);

    if ($code === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session code cannot be empty.']);
        exit;
    }

    // Strip rotating salt suffix (e.g. NPC-DM103-2026-08-21-3F8A -> NPC-DM103-2026-08-21)
    $segments = explode('-', $code);
    $baseCode = count($segments) > 5 ? implode('-', array_slice($segments, 0, 5)) : $code;

    foreach ([$code, $baseCode] as $candidate) {
        if (!preg_match('/^[A-Z0-9\-]{3,64}$/', $candidate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Malformed session code.']);
            exit;
        }
    }

    // Look up the active faculty-created session
    $c1 = rawurlencode($code);
    $c2 = rawurlencode($baseCode);
    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?or=(session_code.eq.$c1,session_code.eq.$c2)&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && is_array($sQuery['data']) && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;

    if (!$session) {
        logSecurityEvent("CHECKIN_REJECTED: Invalid code '$code' by $currentUserEmail", $currentUserEmail, 'Medium');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Invalid session code: \"$code\" does not match any active attendance session."]);
        exit;
    }

    if (($session['is_active'] ?? true) === false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "The attendance session for {$session['class_code']} has been closed."]);
        exit;
    }

    if (!empty($session['is_attendance_locked'])) {
        http_response_code(423);
        echo json_encode(['success' => false, 'message' => "Attendance check-in for {$session['class_code']} is currently locked by the instructor."]);
        exit;
    }

    // Time windows are evaluated with SERVER time — client clock irrelevant
    $nowTs = time();
    $computedStatus = 'present';
    if (!empty($session['present_until']) && $nowTs > strtotime($session['present_until'])) {
        $computedStatus = 'late';
    }
    if (!empty($session['late_until']) && $nowTs > strtotime($session['late_until'])) {
        http_response_code(410);
        echo json_encode(['success' => false, 'message' => "The check-in window for {$session['class_code']} has ended."]);
        exit;
    }

    // Duplicate check for this session
    $sn = rawurlencode($currentStudentNumber);
    $sc = rawurlencode($session['session_code']);
    $dupQuery = supabaseServiceQuery("/rest/v1/attendance_records?student_number=eq.$sn&session_code=eq.$sc&select=id,check_in_at,status,reference_id,verified_via&limit=1");
    if ($dupQuery['status'] === 200 && is_array($dupQuery['data']) && !empty($dupQuery['data'])) {
        $prev = $dupQuery['data'][0];
        $existingReceipt = [
            'reference_id' => $prev['reference_id'] ?? ('REF-' . date('Ymd', strtotime($prev['check_in_at'])) . '-' . substr(md5($prev['id']), 0, 6)),
            'timestamp' => $prev['check_in_at'],
            'formatted_time' => date('M d, Y g:i A', strtotime($prev['check_in_at'])),
            'status' => $prev['status'] ?? 'present',
            'student_number' => $currentStudentNumber,
            'student_name' => $currentUserName,
            'class_code' => $session['class_code'] ?? '',
            'section' => $session['section'] ?? '',
            'instructor' => $session['instructor'] ?? 'Assigned Faculty',
            'verified_via' => $prev['verified_via'] ?? ($checkMethod === 'live_portal' ? 'Live Classroom' : 'QR Attendance')
        ];
        echo json_encode([
            'success' => false,
            'duplicate' => true,
            'receipt' => $existingReceipt,
            'message' => 'You have already checked in for ' . ($session['class_code'] ?? 'this class') .
                         ' at ' . date('g:i:s A', strtotime($prev['check_in_at'])) . '.'
        ]);
        exit;
    }

    // Generate verified reference ID
    $refId = 'REF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $verifiedMethod = ($checkMethod === 'live_portal') ? 'live_portal_checkin' : (($checkMethod === 'qr_scan') ? 'qr_scan' : 'manual_code');

    $insertRes = supabaseServiceQuery("/rest/v1/attendance_records", 'POST', [[
        'student_id' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'student_number' => $currentStudentNumber,
        'session_code' => $session['session_code'],
        'check_in_at' => date('c'),
        'method' => $checkMethod,
        'status' => $computedStatus,
        'reference_id' => $refId,
        'verified_via' => $verifiedMethod
    ]]);

    if ($insertRes['status'] < 200 || $insertRes['status'] >= 300) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Failed to record attendance. Please try again.']);
        exit;
    }

    logSecurityEvent("ATTENDANCE_CHECKIN: $currentStudentNumber -> {$session['session_code']} ($computedStatus) [$refId]", $currentUserEmail, 'Low');

    $receipt = [
        'reference_id' => $refId,
        'timestamp' => date('c'),
        'formatted_time' => date('M d, Y g:i A'),
        'status' => $computedStatus,
        'student_number' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'class_code' => $session['class_code'] ?? '',
        'section' => $session['section'] ?? '',
        'instructor' => $session['instructor'] ?? 'Assigned Faculty',
        'verified_via' => ($checkMethod === 'live_portal' ? 'Live Classroom Check-in' : 'QR Attendance Scanner')
    ];

    echo json_encode([
        'success' => true,
        'status' => $computedStatus,
        'server_time' => date('c'),
        'receipt' => $receipt,
        'session' => [
            'class_code' => $session['class_code'] ?? '',
            'section' => $session['section'] ?? '',
            'instructor' => $session['instructor'] ?? ''
        ]
    ]);
    exit;
}

// ─── 10B. GET: Fetch Attendance Receipt ──────────────────────────────────────
if ($method === 'GET' && $action === 'get_attendance_receipt') {
    $ref = trim($_GET['reference_id'] ?? '');
    $sc = trim($_GET['session_code'] ?? '');
    $sn = rawurlencode($currentStudentNumber);

    $url = "/rest/v1/attendance_records?student_number=eq.$sn";
    if (!empty($ref)) {
        $url .= "&reference_id=eq." . rawurlencode($ref);
    } elseif (!empty($sc)) {
        $url .= "&session_code=eq." . rawurlencode($sc);
    }
    $url .= "&order=check_in_at.desc&limit=1";

    $rQuery = supabaseServiceQuery($url);
    if ($rQuery['status'] === 200 && is_array($rQuery['data']) && !empty($rQuery['data'])) {
        $rec = $rQuery['data'][0];
        $sessCode = $rec['session_code'] ?? '';
        $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessCode) . "&limit=1");
        $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : [];

        echo json_encode([
            'success' => true,
            'receipt' => [
                'reference_id' => $rec['reference_id'] ?? ('REF-' . date('Ymd', strtotime($rec['check_in_at'])) . '-' . substr(md5($rec['id']), 0, 6)),
                'timestamp' => $rec['check_in_at'],
                'formatted_time' => date('M d, Y g:i A', strtotime($rec['check_in_at'])),
                'status' => $rec['status'] ?? 'present',
                'student_number' => $currentStudentNumber,
                'student_name' => $currentUserName,
                'class_code' => $session['class_code'] ?? $sessCode,
                'section' => $session['section'] ?? '',
                'instructor' => $session['instructor'] ?? 'Assigned Faculty',
                'verified_via' => $rec['verified_via'] ?? ($rec['method'] === 'live_portal' ? 'Live Classroom' : 'QR Attendance')
            ]
        ]);
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Attendance receipt not found.']);
    exit;
}

// ─── 11. GET: Class materials available to MY section (from my enrolled classes) ─
if ($method === 'GET' && $action === 'get_class_materials') {
    // Student's section from session-backed user record
    $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($currentUserEmail) . "&limit=1");
    $user = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
    $mySection = strtoupper(trim(($user['program'] ?? '') . ' ' . ($user['section'] ?? '')));

    $mQuery = supabaseServiceQuery("/rest/v1/faculty_materials?order=created_at.desc&limit=100");
    $materials = ($mQuery['status'] === 200 && is_array($mQuery['data'])) ? $mQuery['data'] : [];

    // Match materials whose class section matches the student's (or class_id empty = all sections)
    $out = [];
    foreach ($materials as $m) {
        $cid = $m['class_id'] ?? '';
        if (empty($cid)) { $out[] = $m; continue; }
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        if ($cQuery['status'] === 200 && !empty($cQuery['data'])) {
            $cSec = strtoupper(trim($cQuery['data'][0]['section'] ?? ''));
            if ($cSec === '' || strpos($mySection, $cSec) !== false || strpos($cSec, $mySection) !== false) {
                $out[] = $m;
            }
        }
    }

    echo json_encode(['success' => true, 'materials' => array_values($out), 'section' => $mySection]);
    exit;
}

// ─── 12. GET: Class announcements for MY section (feed for Academic page) ──────
if ($method === 'GET' && $action === 'get_section_announcements') {
    $uQuery2 = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($currentUserEmail) . "&limit=1");
    $me = ($uQuery2['status'] === 200 && !empty($uQuery2['data'])) ? $uQuery2['data'][0] : null;
    $mySection = strtoupper(trim(($me['program'] ?? '') . ' ' . ($me['section'] ?? '')));

    $aQuery = supabaseServiceQuery("/rest/v1/class_announcements?status=eq.published&order=created_at.desc&limit=50");
    $items = ($aQuery['status'] === 200 && is_array($aQuery['data'])) ? $aQuery['data'] : [];

    $visible = array_values(array_filter($items, function ($a) use ($mySection) {
        // Hide scheduled announcements not yet due
        if (!empty($a['scheduled_at']) && strtotime($a['scheduled_at']) > time()) return false;
        $aSec = strtoupper(trim($a['section'] ?? ''));
        return $aSec === '' || strpos($mySection, $aSec) !== false || strpos($aSec, $mySection) !== false;
    }));

    echo json_encode(['success' => true, 'announcements' => $visible]);
    exit;
}

// ─── 13. POST: Submit an attendance excuse letter ──────────────────────────────
if ($method === 'POST' && $action === 'submit_excuse') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $facultyEmail = strtolower(trim($input['faculty_email'] ?? ''));
    $classCode = strtoupper(substr(trim($input['class_code'] ?? ''), 0, 32));
    $absenceDate = trim($input['absence_date'] ?? '');
    $reason = trim($input['reason'] ?? '');

    if (empty($facultyEmail) || empty($absenceDate) || mb_strlen($reason) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Professor, date, and a reason of at least 10 characters are required.']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $absenceDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid absence date.']);
        exit;
    }

    // Resolve faculty name from users table
    $fQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($facultyEmail) . "&select=full_name&limit=1");
    $facultyName = ($fQuery['status'] === 200 && !empty($fQuery['data'])) ? $fQuery['data'][0]['full_name'] : 'Faculty';

    supabaseServiceQuery("/rest/v1/attendance_excuses", 'POST', [[
        'student_number' => $currentStudentNumber,
        'student_name' => $currentUserName,
        'student_email' => $currentUserEmail,
        'faculty_email' => $facultyEmail,
        'class_code' => $classCode,
        'absence_date' => $absenceDate,
        'reason' => mb_substr($reason, 0, 1000),
        'status' => 'Pending'
    ]]);

    // Notify the professor
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $facultyEmail,
        'title' => "Excuse Letter Submitted — {$currentUserName}",
        'message' => "$currentUserName ($currentStudentNumber) submitted an excuse for $classCode on $absenceDate. Reason: " . mb_substr($reason, 0, 120),
        'type' => 'academic',
        'link_url' => 'teacher_attendance.php'
    ]]);

    logSecurityEvent("EXCUSE_SUBMITTED: $currentStudentNumber for $classCode on $absenceDate", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => 'Excuse letter submitted. You will receive a notification once reviewed by your professor.']);
    exit;
}

// ─── 14. GET: My submitted excuses + their statuses ────────────────────────────
if ($method === 'GET' && $action === 'get_my_excuses') {
    $r = supabaseServiceQuery("/rest/v1/attendance_excuses?student_number=eq." . rawurlencode((string)$currentStudentNumber) . "&order=created_at.desc&limit=30");
    $excuses = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
    echo json_encode(['success' => true, 'excuses' => $excuses]);
    exit;
}

// ─── 15. POST: Request grade clarification → notifies the professor ────────────
if ($method === 'POST' && $action === 'request_grade_clarification') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $subjectCode = strtoupper(substr(trim($input['subject_code'] ?? ''), 0, 32));
    $message = trim($input['message'] ?? '');
    if (empty($subjectCode) || mb_strlen($message) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Subject and a message (min 10 chars) are required.']);
        exit;
    }

    // Find the professor from classes table by subject code + section match
    $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($currentUserEmail) . "&select=program,section&limit=1");
    $me = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
    $mySection = strtoupper(trim(($me['program'] ?? '') . ' ' . ($me['section'] ?? '')));

    $cQuery = supabaseServiceQuery("/rest/v1/classes?code=eq." . rawurlencode($subjectCode) . "&limit=5");
    $classes = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];
    $facultyEmail = '';
    foreach ($classes as $c) {
        $cSec = strtoupper(trim($c['section'] ?? ''));
        if ($cSec === '' || strpos($mySection, $cSec) !== false) {
            $facultyEmail = strtolower(trim($c['instructor_email'] ?? $c['created_by_email'] ?? ''));
            break;
        }
    }
    if (empty($facultyEmail)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Could not find the professor for this subject. Please verify the subject code.']);
        exit;
    }

    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $facultyEmail,
        'title' => "Grade Clarification Request — $subjectCode",
        'message' => "$currentUserName ($currentStudentNumber) is asking for clarification on their $subjectCode grade: " . mb_substr($message, 0, 150),
        'type' => 'academic',
        'link_url' => 'teacher_grades.php'
    ]]);

    logSecurityEvent("GRADE_CLARIFICATION: $currentStudentNumber for $subjectCode -> $facultyEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => 'Clarification request sent to your professor.']);
    exit;
}

// ─── 16. GET: My professors (for consultation booking dropdown) ────────────────
if ($method === 'GET' && $action === 'get_teachers') {
    // Professors from my enrolled classes first
    $sQuery = supabaseServiceQuery("/rest/v1/classes?select=instructor,instructor_email,code,section&order=instructor.asc&limit=500");
    $classes = ($sQuery['status'] === 200 && is_array($sQuery['data'])) ? $sQuery['data'] : [];

    $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($currentUserEmail) . "&select=program,section&limit=1");
    $me = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
    $mySection = strtoupper(trim(($me['program'] ?? '') . ' ' . ($me['section'] ?? '')));

    $teachers = [];
    $seen = [];
    foreach ($classes as $c) {
        $email = strtolower(trim($c['instructor_email'] ?? ''));
        $name = trim($c['instructor'] ?? '');
        if (empty($email) || empty($name) || isset($seen[$email])) continue;
        $sec = strtoupper(trim($c['section'] ?? ''));
        $isMine = ($sec !== '' && strpos($mySection, $sec) !== false);
        $seen[$email] = true;
        $teachers[] = [
            'email' => $email,
            'name' => $name,
            'subject_code' => $c['code'] ?? '',
            'my_class' => $isMine
        ];
    }
    // My professors first, then others alphabetically
    usort($teachers, function ($a, $b) {
        if ($a['my_class'] !== $b['my_class']) return $a['my_class'] ? -1 : 1;
        return strcmp($a['name'], $b['name']);
    });

    echo json_encode(['success' => true, 'teachers' => $teachers]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
