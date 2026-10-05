<?php
/**
 * api_faculty.php — Server-Side API for Faculty/Professor Operations
 * 
 * Handles:
 *  - Manual attendance correction with reason & audit logging
 *  - Exportable / printable attendance summary
 *  - Consultation appointment status management
 *  - Secure class materials upload
 *  - Section-specific announcements
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helper.php';

require_teacher();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$currentUserEmail = strtolower($_SESSION['email'] ?? '');
$currentUserName = $_SESSION['name'] ?? 'Faculty';
$currentUserRole = $_SESSION['role'] ?? 'teacher';
$isAdmin = ($currentUserRole === 'admin' || $currentUserRole === 'registrar');
session_write_close();

// ─── 1. POST: Manual Attendance Correction with Required Reason ────────────────
if ($method === 'POST' && $action === 'correct_attendance') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $recordId = trim($input['record_id'] ?? '');
    $studentNumber = trim($input['student_number'] ?? '');
    $studentName = trim($input['student_name'] ?? '');
    $sessionCode = trim($input['session_code'] ?? '');
    $newStatus = trim($input['status'] ?? 'present'); // 'present', 'late', 'absent', 'excused'
    $reason = trim($input['reason'] ?? '');

    if (empty($reason) || empty($studentNumber) || empty($sessionCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student number, session code, and correction reason are required.']);
        exit;
    }

    if (!empty($recordId)) {
        // Update existing attendance record
        supabaseServiceQuery(
            "/rest/v1/attendance_records?id=eq.$recordId",
            'PATCH',
            [
                'status' => $newStatus,
                'corrected_by' => $currentUserEmail,
                'corrected_reason' => $reason,
                'remarks' => "Status manually updated to $newStatus by Prof. $currentUserName"
            ]
        );
    } else {
        // Insert corrected record if student had no prior scan
        supabaseServiceQuery(
            "/rest/v1/attendance_records",
            'POST',
            [[
                'student_id' => $studentNumber,
                'student_name' => $studentName,
                'student_number' => $studentNumber,
                'session_code' => $sessionCode,
                'check_in_at' => date('c'),
                'method' => 'manual',
                'status' => $newStatus,
                'corrected_by' => $currentUserEmail,
                'corrected_reason' => $reason,
                'remarks' => "Manual entry by Prof. $currentUserName"
            ]]
        );
    }

    logSecurityEvent("ATTENDANCE_CORRECTED: Student $studentNumber in $sessionCode -> $newStatus by $currentUserEmail. Reason: $reason", $currentUserEmail, 'Medium');

    echo json_encode([
        'success' => true,
        'message' => "Attendance status updated to $newStatus!"
    ]);
    exit;
}

// ─── 2. GET: Faculty Consultation Appointments List ────────────────────────────
if ($method === 'GET' && $action === 'get_consultations') {
    $cQuery = supabaseServiceQuery("/rest/v1/consultation_appointments?faculty_email=eq." . urlencode($currentUserEmail) . "&order=requested_date.desc");
    $appts = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];

    echo json_encode(['success' => true, 'appointments' => $appts]);
    exit;
}

// ─── 3. POST: Update Consultation Appointment Status ───────────────────────────
if ($method === 'POST' && $action === 'update_consultation_status') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['appointment_id'] ?? '');
    $status = trim($input['status'] ?? ''); // 'Confirmed', 'Declined', 'Completed', 'Rescheduled'
    $notes = trim($input['notes'] ?? '');
    // Reschedule payload (optional)
    $newDate = trim($input['new_date'] ?? '');
    $newTime = trim($input['new_time'] ?? '');

    if (empty($id) || !in_array($status, ['Confirmed', 'Declined', 'Completed', 'Rescheduled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid appointment ID and status required.']);
        exit;
    }
    if ($status === 'Rescheduled' && (empty($newDate) || empty($newTime))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New date and time are required when rescheduling.']);
        exit;
    }

    // Fetch appointment
    $aQuery = supabaseServiceQuery("/rest/v1/consultation_appointments?id=eq.$id&limit=1");
    if ($aQuery['status'] === 200 && !empty($aQuery['data'])) {
        $appt = $aQuery['data'][0];

        // Ownership check: only the consulted faculty or an admin may act
        if (!$isAdmin && strtolower(trim($appt['faculty_email'] ?? '')) !== strtolower(trim($currentUserEmail))) {
            logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried to act on consultation $id owned by {$appt['faculty_email']}", $currentUserEmail, 'High');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You can only manage consultations addressed to you.']);
            exit;
        }

        $patch = [
            'status' => $status,
            'notes' => $notes
        ];
        if ($status === 'Rescheduled') {
            $patch['requested_date'] = $newDate;
            $patch['requested_time'] = $newTime;
            $patch['status'] = 'Pending'; // back to student's court to accept new slot
        }

        supabaseServiceQuery(
            "/rest/v1/consultation_appointments?id=eq.$id",
            'PATCH',
            $patch
        );

        // Student-facing message per outcome
        if ($status === 'Rescheduled') {
            $msg = "Prof. $currentUserName rescheduled your consultation for {$appt['subject_code']} to $newDate at $newTime." . (!empty($notes) ? " Note: $notes" : '') . " Please confirm the new schedule.";
            $title = "Consultation Rescheduled";
        } elseif ($status === 'Completed') {
            $msg = "Your consultation with Prof. $currentUserName for {$appt['subject_code']} on {$appt['requested_date']} has been marked completed." . (!empty($notes) ? " Notes: $notes" : '');
            $title = "Consultation Completed";
        } else {
            $msg = "Prof. $currentUserName has marked your consultation request for {$appt['subject_code']} on {$appt['requested_date']} as $status." . (!empty($notes) ? " Notes: $notes" : '');
            $title = "Consultation Request $status";
        }

        // Notify student
        supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
            'user_email' => $appt['student_email'],
            'title' => $title,
            'message' => $msg,
            'type' => 'system',
            'link_url' => 'academic.php'
        ]]);
    }

    logSecurityEvent("CONSULTATION_$status: appt $id by $currentUserEmail" . ($status === 'Rescheduled' ? " -> $newDate $newTime" : ''), $currentUserEmail, 'Low');

    echo json_encode(['success' => true, 'message' => "Consultation request marked as $status."]);
    exit;
}

// ─── 4. POST: Secure Upload Class Material / Syllabus ──────────────────────────
if ($method === 'POST' && $action === 'upload_material') {
    requireCsrf();

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error occurred.']);
        exit;
    }

    $file = $_FILES['file'];
    $classId = trim($_POST['class_id'] ?? '');
    $title = trim($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
    $category = trim($_POST['category'] ?? 'Lecture Notes');

    // Validate size (15MB max)
    if ($file['size'] > 15 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'File exceeds 15MB maximum size.']);
        exit;
    }

    // Validate MIME types
    $allowedMimeTypes = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimeTypes)) {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'File type not permitted. Allowed: PDF, Word, PowerPoint, Excel, Images.']);
        exit;
    }

    // Storage destination
    $destDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeFilename = 'mat_' . substr(hash('sha256', uniqid('', true) . $file['name']), 0, 16) . '.' . $ext;
    $targetPath = $destDir . DIRECTORY_SEPARATOR . $safeFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Record in faculty_materials table
        $matData = [
            'faculty_email' => $currentUserEmail,
            'class_id' => !empty($classId) ? $classId : null,
            'title' => $title,
            'file_name' => $safeFilename,
            'file_size' => number_format($file['size'] / 1048576, 1) . ' MB',
            'category' => $category,
            'module_week' => substr(trim($input['module_week'] ?? ''), 0, 32)
        ];

        supabaseServiceQuery("/rest/v1/faculty_materials", 'POST', [$matData]);

        logSecurityEvent("MATERIAL_UPLOADED: $title ($safeFilename) by $currentUserEmail", $currentUserEmail, 'Low');

        echo json_encode([
            'success' => true,
            'filename' => $safeFilename,
            'title' => $title,
            'message' => 'Class material uploaded securely!'
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save file to server storage.']);
        exit;
    }
}

// ─── 5. GET: List Class Materials ──────────────────────────────────────────────
if ($method === 'GET' && $action === 'get_materials') {
    $classId = trim($_GET['class_id'] ?? '');
    $endpoint = "/rest/v1/faculty_materials?order=created_at.desc";
    if (!empty($classId)) {
        $endpoint = "/rest/v1/faculty_materials?class_id=eq.$classId&order=created_at.desc";
    }

    $mQuery = supabaseServiceQuery($endpoint);
    $materials = ($mQuery['status'] === 200 && is_array($mQuery['data'])) ? $mQuery['data'] : [];

    echo json_encode(['success' => true, 'materials' => $materials]);
    exit;
}

// ─── Shared: verify a class belongs to the requesting faculty ──────────────────
function facultyOwnsClass(array $class, string $email, bool $isAdmin): bool {
    if ($isAdmin) return true;
    $cEmail = strtolower(trim($class['created_by_email'] ?? ''));
    $myEmail = strtolower(trim($email));
    return $cEmail !== '' && $myEmail !== '' && $cEmail === $myEmail;
}

function jsonFail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ─── 6. POST: Start Live Attendance Session (server-authorized) ────────────────
if ($method === 'POST' && $action === 'start_session') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $classId = trim($input['class_id'] ?? '');
    if (empty($classId)) jsonFail(400, 'Class ID is required.');

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$class) jsonFail(404, 'Class not found.');
    if (!facultyOwnsClass($class, $currentUserEmail, $isAdmin)) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried to start session for class $classId", $currentUserEmail, 'High');
        jsonFail(403, 'You do not own this class.');
    }

    $presentMins = max(1, min(180, intval($input['present_mins'] ?? (getSetting('attendance_present_window_minutes', '10')))));
    $lateMins = max(0, min(180, intval($input['late_mins'] ?? (getSetting('attendance_late_window_minutes', '5')))));

    $meetingProvider = trim($input['meeting_provider'] ?? 'plugnmeet');
    $meetingLink = trim($input['meeting_link'] ?? '');
    $topic = trim($input['topic'] ?? ($class['name'] ?? $class['code'] ?? 'Live Class'));
    $agenda = trim($input['agenda'] ?? '');

    // Session code built server-side from verified class data
    $safeCodePart = preg_replace('/[^A-Za-z0-9]/', '', $class['code'] ?? 'CLASS');
    $sessionCode = strtoupper("NPC-$safeCodePart-" . date('Y-m-d'));
    $nowTs = time();
    $presentUntil = date('c', $nowTs + $presentMins * 60);
    $lateUntil = date('c', $nowTs + ($presentMins + $lateMins) * 60);

    supabaseServiceQuery("/rest/v1/attendance_sessions", 'POST',
        [[
            'session_code' => $sessionCode,
            'class_id' => $classId,
            'class_code' => $class['code'] ?? '',
            'section' => $class['section'] ?? '',
            'instructor' => $class['instructor'] ?? $currentUserName,
            'is_active' => true,
            'present_until' => $presentUntil,
            'late_until' => $lateUntil,
            'meeting_provider' => $meetingProvider,
            'meeting_link' => $meetingLink,
            'topic' => $topic,
            'agenda' => $agenda,
            'session_status' => 'active',
            'grace_period_minutes' => $presentMins,
            'is_attendance_locked' => false,
            'created_at' => date('c')
        ]],
        ["Prefer: resolution=merge-duplicates"]
    );

    logSecurityEvent("SESSION_STARTED: $sessionCode by $currentUserEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'session_code' => $sessionCode,
        'present_until' => $presentUntil,
        'late_until' => $lateUntil,
        'meeting_provider' => $meetingProvider,
        'meeting_link' => $meetingLink,
        'topic' => $topic,
        'is_attendance_locked' => false,
        'message' => 'Live attendance session started.'
    ]);
    exit;
}

// ─── 7. POST: End Live Attendance Session ──────────────────────────────────────
if ($method === 'POST' && $action === 'end_session') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionCode = trim($input['session_code'] ?? '');
    if (empty($sessionCode)) jsonFail(400, 'Session code required.');

    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode) . "&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;
    if (!$session) jsonFail(404, 'Session not found.');

    if (!$isAdmin) {
        $cid = $session['class_id'] ?? '';
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
        if (!$class || !facultyOwnsClass($class, $currentUserEmail, false)) {
            logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried to end foreign session $sessionCode", $currentUserEmail, 'High');
            jsonFail(403, 'You do not own this session.');
        }
    }

    supabaseServiceQuery(
        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode),
        'PATCH',
        [
            'is_active' => false,
            'session_status' => 'ended'
        ]
    );

    echo json_encode(['success' => true, 'message' => 'Session closed.']);
    exit;
}

// ─── 7B. POST: Lock/Unlock Live Attendance Session ────────────────────────────
if ($method === 'POST' && $action === 'toggle_attendance_lock') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionCode = trim($input['session_code'] ?? '');
    $locked = !empty($input['locked']);
    if (empty($sessionCode)) jsonFail(400, 'Session code required.');

    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode) . "&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;
    if (!$session) jsonFail(404, 'Session not found.');

    if (!$isAdmin) {
        $cid = $session['class_id'] ?? '';
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
        if (!$class || !facultyOwnsClass($class, $currentUserEmail, false)) {
            jsonFail(403, 'You do not own this session.');
        }
    }

    supabaseServiceQuery(
        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode),
        'PATCH',
        ['is_attendance_locked' => $locked]
    );

    logSecurityEvent("ATTENDANCE_LOCK_TOGGLED: $sessionCode locked=" . ($locked ? '1' : '0') . " by $currentUserEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'is_attendance_locked' => $locked,
        'message' => $locked ? 'Attendance check-in has been locked.' : 'Attendance check-in unlocked.'
    ]);
    exit;
}

// ─── 7C. POST: Extend Attendance Grace Period ─────────────────────────────────
if ($method === 'POST' && $action === 'extend_grace_period') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionCode = trim($input['session_code'] ?? '');
    $additionalMins = max(1, min(60, intval($input['minutes'] ?? 10)));
    if (empty($sessionCode)) jsonFail(400, 'Session code required.');

    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode) . "&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;
    if (!$session) jsonFail(404, 'Session not found.');

    if (!$isAdmin) {
        $cid = $session['class_id'] ?? '';
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
        if (!$class || !facultyOwnsClass($class, $currentUserEmail, false)) {
            jsonFail(403, 'You do not own this session.');
        }
    }

    $currentLateTs = !empty($session['late_until']) ? strtotime($session['late_until']) : time();
    $newLateUntil = date('c', max(time(), $currentLateTs) + ($additionalMins * 60));
    $currentPresTs = !empty($session['present_until']) ? strtotime($session['present_until']) : time();
    $newPresentUntil = date('c', max(time(), $currentPresTs) + ($additionalMins * 60));

    supabaseServiceQuery(
        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode),
        'PATCH',
        [
            'present_until' => $newPresentUntil,
            'late_until' => $newLateUntil,
            'is_attendance_locked' => false
        ]
    );

    echo json_encode([
        'success' => true,
        'present_until' => $newPresentUntil,
        'late_until' => $newLateUntil,
        'message' => "Grace period extended by {$additionalMins} minutes."
    ]);
    exit;
}

// ─── 8. GET: Live roster for one of your sessions ──────────────────────────────
if ($method === 'GET' && $action === 'get_live_roster') {
    $sessionCode = trim($_GET['session_code'] ?? '');
    if (empty($sessionCode)) jsonFail(400, 'Session code required.');

    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode) . "&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;

    // If no session row exists yet (started before first upsert), allow staff read
    if ($session && !$isAdmin) {
        $cid = $session['class_id'] ?? '';
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
        if (!$class || !facultyOwnsClass($class, $currentUserEmail, false)) {
            jsonFail(403, 'You do not own this session.');
        }
    }

    $sc = rawurlencode($sessionCode);
    $rQuery = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&order=check_in_at.asc&select=id,student_id,student_name,student_number,session_code,check_in_at,status,method,verified_via");
    $records = ($rQuery['status'] === 200 && is_array($rQuery['data'])) ? $rQuery['data'] : [];

    // Attach real-time presence data (is_online, duration_seconds, leave_count)
    $presenceFile = __DIR__ . '/../backend/elms_presence.json';
    $presence = [];
    if (file_exists($presenceFile)) {
        $pData = json_decode(@file_get_contents($presenceFile), true);
        if (is_array($pData) && isset($pData[$sessionCode])) {
            $presence = $pData[$sessionCode];
        }
    }

    $now = time();
    $existingNumbers = [];
    foreach ($records as &$rec) {
        $sn = $rec['student_number'] ?? '';
        if ($sn) $existingNumbers[$sn] = true;
        if ($sn && isset($presence[$sn])) {
            $p = $presence[$sn];
            $isOnline = !empty($p['is_online']) && ($now - ($p['last_heartbeat'] ?? 0) <= 20);
            $accum = intval($p['accumulated_seconds'] ?? 0);
            $joinTs = intval($p['current_join_ts'] ?? $now);
            $rec['is_online'] = $isOnline;
            $rec['leave_count'] = intval($p['leave_count'] ?? 0);
            $rec['duration_seconds'] = $isOnline ? ($accum + max(0, $now - $joinTs)) : $accum;
        } else {
            $rec['is_online'] = false;
            $rec['leave_count'] = 0;
            $rec['duration_seconds'] = 0;
        }
    }
    unset($rec);

    // Include presence attendees who checked in via Google Meet
    foreach ($presence as $sn => $p) {
        if (!isset($existingNumbers[$sn])) {
            $isOnline = !empty($p['is_online']) && ($now - ($p['last_heartbeat'] ?? 0) <= 20);
            $accum = intval($p['accumulated_seconds'] ?? 0);
            $joinTs = intval($p['current_join_ts'] ?? $now);
            $records[] = [
                'id'               => 'presence-' . md5($sessionCode . $sn),
                'student_id'       => $sn,
                'student_number'   => $sn,
                'student_name'     => $p['student_name'] ?? 'Student',
                'session_code'     => $sessionCode,
                'check_in_at'      => $p['check_in_at'] ?? date('c'),
                'status'           => $p['status'] ?? 'present',
                'method'           => 'live_portal',
                'verified_via'     => $p['verified_via'] ?? 'plugnmeet_verified',
                'is_online'        => $isOnline,
                'leave_count'      => intval($p['leave_count'] ?? 0),
                'duration_seconds' => $isOnline ? ($accum + max(0, $now - $joinTs)) : $accum
            ];
        }
    }

    echo json_encode(['success' => true, 'records' => $records]);
    exit;
}

// ─── 9. GET: Students enrolled in one of your sections ─────────────────────────
if ($method === 'GET' && $action === 'get_section_students') {
    $section = trim($_GET['section'] ?? '');
    if (empty($section)) jsonFail(400, 'Section required.');

    $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,student_number,program,section");
    $students = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];

    $targetSec = strtoupper(trim($section));
    $matched = array_values(array_filter($students, function ($s) use ($targetSec) {
        $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
        return $sSec === $targetSec
            || (strpos($sSec, $targetSec) !== false)
            || (strpos($targetSec, strtoupper(trim($s['section'] ?? ''))) !== false && trim($s['section'] ?? '') !== '');
    }));

    usort($matched, fn($a, $b) => strcmp($a['full_name'] ?? '', $b['full_name'] ?? ''));
    echo json_encode(['success' => true, 'students' => $matched]);
    exit;
}

// ─── 10. POST: Mark all enrolled students present (bulk override) ──────────────
if ($method === 'POST' && $action === 'mark_all_present') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $classId = trim($input['class_id'] ?? '');
    $sessionCode = trim($input['session_code'] ?? '');
    if (empty($classId) || empty($sessionCode)) jsonFail(400, 'Class ID and session code are required.');

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$class) jsonFail(404, 'Class not found.');
    if (!facultyOwnsClass($class, $currentUserEmail, $isAdmin)) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail attempted bulk override on $classId", $currentUserEmail, 'High');
        jsonFail(403, 'You do not own this class.');
    }

    // Enrolled students resolved server-side from section data
    $targetSec = strtoupper(trim($class['section'] ?? ''));
    $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,student_number,program,section");
    $allStudents = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];
    $enrolled = array_filter($allStudents, function ($s) use ($targetSec) {
        $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
        return $sSec === $targetSec || strpos($sSec, $targetSec) !== false;
    });

    $sc = rawurlencode($sessionCode);
    $existingQuery = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&select=student_number");
    $existingNums = [];
    if ($existingQuery['status'] === 200 && is_array($existingQuery['data'])) {
        foreach ($existingQuery['data'] as $row) $existingNums[$row['student_number']] = true;
    }

    $toInsert = [];
    foreach ($enrolled as $s) {
        $num = $s['student_number'] ?? '';
        if ($num === '' || isset($existingNums[$num])) continue;
        $toInsert[] = [
            'student_id' => $num,
            'student_name' => $s['full_name'],
            'student_number' => $num,
            'session_code' => $sessionCode,
            'check_in_at' => date('c'),
            'method' => 'bulk_override',
            'status' => 'present',
            'corrected_by' => $currentUserEmail,
            'remarks' => "Bulk marked present by Prof. $currentUserName"
        ];
    }

    $inserted = 0;
    if (!empty($toInsert)) {
        $res = supabaseServiceQuery("/rest/v1/attendance_records", 'POST', $toInsert, ["Prefer: return=minimal"]);
        if ($res['status'] >= 200 && $res['status'] < 300) $inserted = count($toInsert);
    }

    logSecurityEvent("ATTENDANCE_BULK: $inserted students marked present in $sessionCode by $currentUserEmail", $currentUserEmail, 'Medium');

    echo json_encode(['success' => true, 'inserted' => $inserted, 'message' => "$inserted students marked present."]);
    exit;
}

// ─── 11. POST: Create a class (faculty-owned) ──────────────────────────────────
if ($method === 'POST' && $action === 'create_class') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $record = [
        'code' => substr(trim($input['code'] ?? ''), 0, 32),
        'title' => substr(trim($input['title'] ?? ''), 0, 160),
        'section' => substr(trim($input['section'] ?? ''), 0, 64),
        'schedule_day' => substr(trim($input['schedule_day'] ?? 'TBA'), 0, 24),
        'start_time' => substr(trim($input['start_time'] ?? 'TBA'), 0, 16),
        'end_time' => substr(trim($input['end_time'] ?? 'TBA'), 0, 16),
        'instructor' => substr(trim($input['instructor'] ?? $currentUserName), 0, 120),
        'room' => substr(trim($input['room'] ?? 'Room TBA'), 0, 64),
        'units' => max(0, min(12, floatval($input['units'] ?? 3.0))),
        'created_by_name' => $currentUserName,
        'created_by_email' => $currentUserEmail
    ];

    if ($record['code'] === '' || $record['title'] === '') jsonFail(400, 'Subject code and title are required.');

    $res = supabaseServiceQuery("/rest/v1/classes", 'POST', [$record]);
    if ($res['status'] < 200 || $res['status'] >= 300) jsonFail(502, 'Database rejected the new class.');

    logSecurityEvent("CLASS_CREATED: {$record['code']} {$record['section']} by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => 'Class created.']);
    exit;
}

// ─── 12. POST: Delete a class you own ──────────────────────────────────────────
if ($method === 'POST' && $action === 'delete_class') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if (empty($id)) jsonFail(400, 'Class ID required.');

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($id) . "&limit=1");
    $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$class) jsonFail(404, 'Class not found.');
    if (!facultyOwnsClass($class, $currentUserEmail, $isAdmin)) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried deleting class $id", $currentUserEmail, 'High');
        jsonFail(403, 'You do not own this class.');
    }

    supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($id), 'DELETE');
    logSecurityEvent("CLASS_DELETED: {$class['code']} {$class['section']} by $currentUserEmail", $currentUserEmail, 'High');

    echo json_encode(['success' => true, 'message' => 'Class deleted.']);
    exit;
}

// ─── 13. POST: Post a class-scoped announcement (fans out to enrolled students) ─
if ($method === 'POST' && $action === 'post_class_announcement') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $classId = trim($input['class_id'] ?? '');
    $title = trim($input['title'] ?? '');
    $body = trim($input['body'] ?? '');
    $category = trim($input['category'] ?? 'general');
    if (!in_array($category, ['general', 'assignment', 'exam', 'reminder'], true)) $category = 'general';

    if (empty($classId) || empty($title)) jsonFail(400, 'Class and title are required.');
    if (mb_strlen($title) > 160) jsonFail(400, 'Title too long (max 160 chars).');

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$class) jsonFail(404, 'Class not found.');
    if (!facultyOwnsClass($class, $currentUserEmail, $isAdmin)) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried posting announcement to class $classId", $currentUserEmail, 'High');
        jsonFail(403, 'You can only post announcements to your own classes.');
    }

    $ins = supabaseServiceQuery("/rest/v1/class_announcements", 'POST', [[
        'class_id' => $classId,
        'class_code' => $class['code'] ?? '',
        'section' => $class['section'] ?? '',
        'faculty_email' => $currentUserEmail,
        'faculty_name' => $currentUserName,
        'title' => mb_substr($title, 0, 160),
        'body' => mb_substr($body, 0, 4000),
        'category' => $category,
        'status' => 'published',
        'attachment_url' => substr(trim($input['attachment_url'] ?? ''), 0, 500),
        'attachment_name' => substr(trim($input['attachment_name'] ?? ''), 0, 200),
        'scheduled_at' => !empty($input['scheduled_at']) ? $input['scheduled_at'] : null
    ]]);
    if ($ins['status'] < 200 || $ins['status'] >= 300) jsonFail(502, 'Database rejected the announcement.');

    // Scheduled posts are NOT fanned out now — the display filter hides them until scheduled_at
    $scheduledAt = !empty($input['scheduled_at']) ? strtotime($input['scheduled_at']) : null;
    if ($scheduledAt !== null && $scheduledAt > time()) {
        logSecurityEvent("CLASS_ANNOUNCEMENT_SCHEDULED: [{$class['code']} {$class['section']}] \"$title\" for " . date('c', $scheduledAt) . " by $currentUserEmail", $currentUserEmail, 'Low');
        echo json_encode(['success' => true, 'message' => 'Announcement scheduled for ' . date('M j, Y g:i A', $scheduledAt) . '.', 'notified' => 0, 'scheduled' => true]);
        exit;
    }

    // Fan-out: notify every enrolled student of this section
    $targetSec = strtoupper(trim($class['section'] ?? ''));
    $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,email,program,section");
    $students = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];
    $notifRows = [];
    foreach ($students as $s) {
        $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
        if ($targetSec !== '' && strpos($sSec, $targetSec) === false) continue;
        $notifRows[] = [
            'user_email' => strtolower($s['email'] ?? ''),
            'title' => "[" . ($class['code'] ?? 'Class') . "] $title",
            'message' => "Prof. $currentUserName announced for " . ($class['code'] ?? '') . " (" . ($class['section'] ?? '') . "): " . mb_substr($body, 0, 220),
            'type' => 'academic',
            'link_url' => 'academic.php'
        ];
    }
    // Chunk inserts (Supabase prefers smaller batches)
    $notified = 0;
    foreach (array_chunk($notifRows, 100) as $batch) {
        if (empty($batch)) continue;
        $nres = supabaseServiceQuery("/rest/v1/notifications", 'POST', $batch, ["Prefer: return=minimal"]);
        if ($nres['status'] >= 200 && $nres['status'] < 300) $notified += count($batch);
    }

    logSecurityEvent("CLASS_ANNOUNCEMENT: [{$class['code']} {$class['section']}] \"$title\" by $currentUserEmail (~$notified notified)", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'message' => "Announcement posted to {$class['code']} ({$class['section']}).",
        'notified' => $notified
    ]);
    exit;
}

// ─── 14. GET: List class announcements for MY classes ─────────────────────────
if ($method === 'GET' && $action === 'get_class_announcements') {
    if ($isAdmin) {
        $endpoint = "/rest/v1/class_announcements?order=created_at.desc&limit=50";
    } else {
        $endpoint = "/rest/v1/class_announcements?faculty_email=eq." . rawurlencode($currentUserEmail) . "&order=created_at.desc&limit=50";
    }
    $aQuery = supabaseServiceQuery($endpoint);
    $items = ($aQuery['status'] === 200 && is_array($aQuery['data'])) ? $aQuery['data'] : [];
    echo json_encode(['success' => true, 'announcements' => $items]);
    exit;
}

// ─── 15. POST: Delete an uploaded material (owner or admin only) ──────────────
if ($method === 'POST' && $action === 'delete_material') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if (empty($id)) jsonFail(400, 'Material ID required.');

    $mQuery = supabaseServiceQuery("/rest/v1/faculty_materials?id=eq." . rawurlencode($id) . "&limit=1");
    $mat = ($mQuery['status'] === 200 && !empty($mQuery['data'])) ? $mQuery['data'][0] : null;
    if (!$mat) jsonFail(404, 'Material not found.');

    $ownerEmail = strtolower(trim($mat['faculty_email'] ?? ''));
    if (!$isAdmin && $ownerEmail !== strtolower(trim($currentUserEmail))) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried deleting material $id owned by $ownerEmail", $currentUserEmail, 'High');
        jsonFail(403, 'You can only delete your own materials.');
    }

    supabaseServiceQuery("/rest/v1/faculty_materials?id=eq." . rawurlencode($id), 'DELETE');

    // Best-effort file removal from local storage
    $safeName = basename($mat['file_name'] ?? '');
    if ($safeName !== '') {
        $fpath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($fpath)) @unlink($fpath);
    }

    logSecurityEvent("MATERIAL_DELETED: {$mat['title']} by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => 'Material deleted.']);
    exit;
}

// ─── 16. GET: Excuse letters submitted to ME ───────────────────────────────────
if ($method === 'GET' && $action === 'get_excuses') {
    if ($isAdmin) {
        $endpoint = "/rest/v1/attendance_excuses?order=created_at.desc&limit=50";
    } else {
        $endpoint = "/rest/v1/attendance_excuses?faculty_email=eq." . rawurlencode($currentUserEmail) . "&order=created_at.desc&limit=50";
    }
    $r = supabaseServiceQuery($endpoint);
    $excuses = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
    echo json_encode(['success' => true, 'excuses' => $excuses]);
    exit;
}

// ─── 17. POST: Approve / Reject an excuse letter ───────────────────────────────
if ($method === 'POST' && $action === 'review_excuse') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $decision = trim($input['decision'] ?? ''); // 'Approved' | 'Rejected'
    $remarks = trim($input['remarks'] ?? '');

    if (empty($id) || !in_array($decision, ['Approved', 'Rejected'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Excuse ID and a valid decision are required.']);
        exit;
    }

    $r = supabaseServiceQuery("/rest/v1/attendance_excuses?id=eq." . rawurlencode($id) . "&limit=1");
    $excuse = ($r['status'] === 200 && !empty($r['data'])) ? $r['data'][0] : null;
    if (!$excuse) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Excuse letter not found.']);
        exit;
    }
    // Ownership: only the addressed professor or an admin may review
    if (!$isAdmin && strtolower(trim($excuse['faculty_email'] ?? '')) !== strtolower(trim($currentUserEmail))) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried reviewing excuse owned by {$excuse['faculty_email']}", $currentUserEmail, 'High');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only review excuses addressed to you.']);
        exit;
    }

    supabaseServiceQuery("/rest/v1/attendance_excuses?id=eq." . rawurlencode($id), 'PATCH', [
        'status' => $decision,
        'reviewed_by' => $currentUserEmail,
        'reviewed_at' => date('c'),
        'remarks' => mb_substr($remarks, 0, 500)
    ]);

    // If approved AND tied to a specific QR session, flip that record to 'excused'
    if ($decision === 'Approved' && !empty($excuse['session_code'])) {
        $sc = rawurlencode($excuse['session_code']);
        $num = rawurlencode((string)$excuse['student_number']);
        supabaseServiceQuery(
            "/rest/v1/attendance_records?session_code=eq.$sc&student_number=eq.$num",
            'PATCH',
            ['status' => 'excused', 'corrected_by' => $currentUserEmail, 'corrected_reason' => 'Excuse approved: ' . mb_substr($remarks, 0, 200)]
        );
    }

    // Notify the student
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $excuse['student_email'],
        'title' => "Excuse Letter $decision — {$excuse['class_code']}",
        'message' => "Prof. $currentUserName has marked your excuse for {$excuse['class_code']} ({$excuse['absence_date']}) as <b>$decision</b>." . (!empty($remarks) ? " Remarks: $remarks" : ''),
        'type' => 'academic',
        'link_url' => 'academic.php'
    ]]);

    logSecurityEvent("EXCUSE_REVIEWED: {$excuse['id']} -> $decision by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => "Excuse letter $decision.", 'auto_corrected' => ($decision === 'Approved' && !empty($excuse['session_code']))]);
    exit;
}

// ─── 18. POST: Finalize session — mark every unmarked enrollee ABSENT ──────────
if ($method === 'POST' && $action === 'finalize_session_absents') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionCode = trim($input['session_code'] ?? '');
    if (empty($sessionCode)) jsonFail(400, 'Session code required.');

    // Ownership check via session -> class
    $sQuery = supabaseServiceQuery("/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($sessionCode) . "&order=created_at.desc&limit=1");
    $session = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;
    if (!$session) jsonFail(404, 'Session not found.');
    if (!$isAdmin) {
        $cid = $session['class_id'] ?? '';
        $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($cid) . "&limit=1");
        $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
        if (!$class || !facultyOwnsClass($class, $currentUserEmail, false)) {
            logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried finalizing foreign session $sessionCode", $currentUserEmail, 'High');
            jsonFail(403, 'You do not own this session.');
        }
    }

    // Everyone enrolled in this section
    $section = strtoupper(trim($session['section'] ?? ''));
    $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,student_number,program,section");
    $students = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];
    $enrolled = array_values(array_filter($students, function ($s) use ($section) {
        if ($section === '') return true;
        $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
        return strpos($sSec, $section) !== false;
    }));

    // Who already checked in?
    $sc = rawurlencode($sessionCode);
    $rQuery = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&select=student_number");
    $records = ($rQuery['status'] === 200 && is_array($rQuery['data'])) ? $rQuery['data'] : [];
    $present = [];
    foreach ($records as $rec) $present[strtolower((string)$rec['student_number'])] = true;

    // Insert absent rows for everyone missing
    $rows = [];
    foreach ($enrolled as $s) {
        $num = (string)($s['student_number'] ?? '');
        if ($num === '' || isset($present[strtolower($num)])) continue;
        $rows[] = [
            'student_id' => $num,
            'student_name' => $s['full_name'] ?? '',
            'student_number' => $num,
            'session_code' => $sessionCode,
            'check_in_at' => date('c'),
            'method' => 'auto_absent',
            'status' => 'absent',
            'remarks' => 'Marked absent automatically at session close'
        ];
    }
    $inserted = 0;
    foreach (array_chunk($rows, 100) as $batch) {
        $res = supabaseServiceQuery("/rest/v1/attendance_records", 'POST', $batch, ["Prefer: return=minimal"]);
        if ($res['status'] >= 200 && $res['status'] < 300) $inserted += count($batch);
    }

    logSecurityEvent("SESSION_FINALIZED: $sessionCode — $inserted absents recorded by " . ($_SESSION['email'] ?? 'faculty'), $_SESSION['email'] ?? '', 'Medium');
    echo json_encode(['success' => true, 'message' => "$inserted student(s) marked absent.", 'absent_count' => $inserted]);
    exit;
}

// ─── 19. GET: Export attendance CSV for one session ───────────────────────────
if ($method === 'GET' && $action === 'export_attendance_csv') {
    $sessionCode = trim($_GET['session_code'] ?? '');
    if (empty($sessionCode)) { http_response_code(400); exit('Session code required.'); }

    $sc = rawurlencode($sessionCode);
    $rQuery = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&order=check_in_at.asc&select=student_number,student_name,status,method,check_in_at");
    $records = ($rQuery['status'] === 200 && is_array($rQuery['data'])) ? $rQuery['data'] : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="npc-attendance-' . preg_replace('/[^A-Za-z0-9_-]/', '', $sessionCode) . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Number', 'Student Name', 'Status', 'Method', 'Check-in At']);
    foreach ($records as $rec) {
        fputcsv($out, [$rec['student_number'], $rec['student_name'], $rec['status'], $rec['method'], $rec['check_in_at']]);
    }
    fclose($out);
    exit;
}

// ─── 20. GET: Class roster w/ attendance summary + grade status per student ───
if ($method === 'GET' && $action === 'get_class_roster') {
    $classId = trim($_GET['class_id'] ?? '');
    if (empty($classId)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Class ID required.']); exit; }

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $class = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$class) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Class not found.']); exit; }
    if (!facultyOwnsClass($class, $currentUserEmail, $isAdmin)) {
        logSecurityEvent("ACCESS_DENIED: $currentUserEmail tried roster of foreign class $classId", $currentUserEmail, 'High');
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'You do not own this class.']); exit;
    }

    // Section students
    $section = strtoupper(trim($class['section'] ?? ''));
    $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,student_number,program,section,email&order=full_name.asc&limit=1000");
    $students = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];
    $roster = array_values(array_filter($students, function ($s) use ($section) {
        if ($section === '') return true;
        $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
        return strpos($sSec, $section) !== false;
    }));

    // Attendance aggregates for this class's sessions
    $code = $class['code'] ?? '';
    $attByStudent = [];
    $sessQ = supabaseServiceQuery("/rest/v1/attendance_sessions?select=session_code&class_id=eq." . rawurlencode($classId) . "&limit=500");
    $sessions = ($sessQ['status'] === 200 && is_array($sessQ['data'])) ? $sessQ['data'] : [];
    foreach ($sessions as $sess) {
        $sc = rawurlencode($sess['session_code']);
        $recQ = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&select=student_number,status");
        $recs = ($recQ['status'] === 200 && is_array($recQ['data'])) ? $recQ['data'] : [];
        foreach ($recs as $r) {
            $k = strtolower((string)$r['student_number']);
            $attByStudent[$k] = $attByStudent[$k] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
            $st = strtolower($r['status'] ?? '');
            if (isset($attByStudent[$k][$st])) $attByStudent[$k][$st]++;
        }
    }

    // Grade status from grades table by subject code
    $gradeByStudent = [];
    if ($code !== '') {
        $gQ = supabaseServiceQuery("/rest/v1/grades?subject_code=eq." . rawurlencode($code) . "&select=student_number,grade,status&limit=2000");
        $gRecs = ($gQ['status'] === 200 && is_array($gQ['data'])) ? $gQ['data'] : [];
        foreach ($gRecs as $gr) {
            $gradeByStudent[strtolower((string)$gr['student_number'])] = [
                'grade' => $gr['grade'] ?? null,
                'status' => $gr['status'] ?? 'Not Submitted'
            ];
        }
    }

    $rows = array_map(function ($s) use ($attByStudent, $gradeByStudent) {
        $k = strtolower((string)$s['student_number']);
        $att = $attByStudent[$k] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
        $total = $att['present'] + $att['late'] + $att['absent'];
        $rate = $total > 0 ? round((($att['present'] + 0.8 * $att['late']) / $total) * 100, 1) . '%' : '—';
        return [
            'student_number' => $s['student_number'],
            'full_name' => $s['full_name'],
            'email' => $s['email'] ?? '',
            'att_present' => $att['present'], 'att_late' => $att['late'],
            'att_absent' => $att['absent'], 'att_excused' => $att['excused'],
            'att_rate' => $rate,
            'grade' => $gradeByStudent[$k]['grade'] ?? null,
            'grade_status' => $gradeByStudent[$k]['status'] ?? 'Not Submitted'
        ];
    }, $roster);

    echo json_encode(['success' => true, 'class' => ['id' => $classId, 'code' => $code, 'section' => $section], 'roster' => $rows]);
    exit;
}

// ─── 21. GET: Recent absent records across MY sessions (dashboard widget) ─────
if ($method === 'GET' && $action === 'get_my_sessions') {
    $limit = min(10, max(1, intval($_GET['limit'] ?? 5)));

    // My sessions (or all if admin)
    $sessEndpoint = $isAdmin
        ? "/rest/v1/attendance_sessions?select=session_code,class_code,created_at&order=created_at.desc&limit=$limit"
        : "/rest/v1/attendance_sessions?select=session_code,class_code,created_at&instructor=eq." . rawurlencode($currentUserName) . "&order=created_at.desc&limit=$limit";
    // Fallback: instructor field may store name; also try matching via owned classes
    $sessQ = supabaseServiceQuery($sessEndpoint);
    $sessions = ($sessQ['status'] === 200 && is_array($sessQ['data'])) ? $sessQ['data'] : [];

    if (!$isAdmin && empty($sessions)) {
        // Match sessions by my classes' session-code prefix convention NPC-<CODE>-<date>
        $cQ = supabaseServiceQuery("/rest/v1/classes?select=code&limit=100");
        $myClasses = ($cQ['status'] === 200 && is_array($cQ['data'])) ? array_filter($cQ['data'], fn($c) => facultyOwnsClass($c, $currentUserEmail, false)) : [];
        $codes = array_values(array_filter(array_map(fn($c) => preg_replace('/[^A-Za-z0-9]/', '', $c['code'] ?? ''), $myClasses)));
        if (!empty($codes)) {
            $or = implode(',', array_map(fn($cd) => "session_code.ilike.NPC-$cd-*", $codes));
            $sessQ = supabaseServiceQuery("/rest/v1/attendance_sessions?select=session_code,class_code,created_at&or=($or)&order=created_at.desc&limit=$limit");
            $sessions = ($sessQ['status'] === 200 && is_array($sessQ['data'])) ? $sessQ['data'] : [];
        }
    }

    // Collect recent absent records from those sessions
    $absences = [];
    foreach (array_slice($sessions, 0, $limit) as $sess) {
        $sc = rawurlencode($sess['session_code']);
        $rQ = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq.$sc&status=eq.absent&select=student_number,student_name,check_in_at,method&order=check_in_at.desc&limit=20");
        $recs = ($rQ['status'] === 200 && is_array($rQ['data'])) ? $rQ['data'] : [];
        foreach ($recs as $rec) {
            $rec['class_code'] = $sess['class_code'] ?? '';
            $rec['session_created'] = $sess['created_at'] ?? null;
            $absences[] = $rec;
        }
    }
    // Newest first
    usort($absences, fn($a, $b) => strcmp((string)($b['check_in_at'] ?? ''), (string)($a['check_in_at'] ?? '')));

    echo json_encode(['success' => true, 'absences' => array_slice($absences, 0, 30)]);
    exit;
}

// ─── 22. GET: Assigned classes for current faculty ───────────────────────────
if ($method === 'GET' && ($action === 'get_assigned_classes' || $action === 'my_classes')) {
    $cQuery = supabaseServiceQuery("/rest/v1/classes?order=code.asc&limit=500");
    $classes = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? $cQuery['data'] : [];

    $myEmail = strtolower(trim($currentUserEmail));
    $myName = strtolower(trim($currentUserName));
    $cleanMyName = preg_replace('/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i', '', $myName);

    $assigned = [];
    foreach ($classes as $c) {
        if ($isAdmin) {
            $assigned[] = $c;
            continue;
        }

        $cInst = strtolower(trim($c['instructor'] ?? ''));
        $cleanCInst = preg_replace('/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i', '', $cInst);
        $cInstEmail = strtolower(trim($c['instructor_email'] ?? ''));
        $cCreatedEmail = strtolower(trim($c['created_by_email'] ?? ''));

        $isTba = empty($cInst) || in_array($cInst, ['tba', 'to be announced', 'unassigned', 'none']);

        // 1. Direct Instructor Email match
        if ($cInstEmail !== '' && $myEmail !== '' && $cInstEmail === $myEmail) {
            $assigned[] = $c;
            continue;
        }

        // 2. Instructor Name match
        if (!$isTba && $cleanMyName !== '' && (str_contains($cleanCInst, $cleanMyName) || str_contains($cleanMyName, $cleanCInst))) {
            $assigned[] = $c;
            continue;
        }

        // 3. Class created by the faculty member
        if ($cCreatedEmail !== '' && $myEmail !== '' && $cCreatedEmail === $myEmail) {
            $assigned[] = $c;
            continue;
        }
    }

    echo json_encode([
        'success' => true,
        'classes' => array_values($assigned),
        'total'   => count($assigned)
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);

