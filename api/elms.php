<?php
/**
 * api/elms.php — Centralized Electronic Learning Management System (ELMS) API
 * 
 * Interconnects Students, Faculty, and Administrators:
 *  - GET  ?action=get_courses        : Returns courses with modules, assignments & personalized submission status
 *  - POST ?action=add_module         : (Faculty/Admin) Upload/add a learning handout or slide deck to a course
 *  - POST ?action=create_assignment  : (Faculty/Admin) Post a new coursework assignment or task
 *  - POST ?action=submit_assignment  : (Student) Submit assignment links and student notes
 *  - GET  ?action=get_submissions    : (Faculty/Admin) Retrieve all student submissions across courses
 *  - POST ?action=grade_submission   : (Faculty/Admin) Grade a submission with score and instructor remarks
 */

// If included by another script as a library, expose functions without executing API dispatcher
$currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$isDirectApiCall = ($currentScript === 'elms.php' || $currentScript === 'lms.php' || basename($_SERVER['PHP_SELF'] ?? '') === 'elms.php' || basename($_SERVER['PHP_SELF'] ?? '') === 'lms.php' || defined('ELMS_API_EXECUTE') || defined('LMS_API_EXECUTE'));

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/supabase_helper.php';

if ($isDirectApiCall) {
    $preAction = $_GET['action'] ?? $_POST['action'] ?? '';
    if ($preAction !== 'download_material' && $preAction !== 'download_submission') {
        header('Content-Type: application/json; charset=utf-8');
    }
    require_login();
}

$elmsFile = __DIR__ . '/../backend/elms_courses.json';

function loadElmsData($filePath) {
    if (!file_exists($filePath)) {
        return ['courses' => [], 'submissions' => []];
    }
    $raw = @file_get_contents($filePath);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = ['courses' => [], 'submissions' => []];
    }
    if (!isset($data['courses'])) $data['courses'] = [];
    if (!isset($data['submissions'])) $data['submissions'] = [];
    return $data;
}

function saveElmsData($filePath, array $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($filePath, $json, LOCK_EX) !== false;
}

$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';
$userEmail = strtolower(trim($_SESSION['email'] ?? ''));
$userName = $_SESSION['name'] ?? 'User';
$studentNumber = $_SESSION['student_number'] ?? '2024-00192';
session_write_close();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Fallback to parse JSON body
$input = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $parsed = json_decode($rawInput, true);
    if (is_array($parsed)) {
        $input = $parsed;
        if (empty($action) && isset($input['action'])) {
            $action = $input['action'];
        }
    }
}

function matchesStudentSection($courseSection, $courseProgram, $userSec, $userProg): bool {
    $cSec = strtoupper(trim($courseSection ?? ''));
    $cProg = strtoupper(trim($courseProgram ?? ''));
    $sSec = strtoupper(trim($userSec ?? ''));
    $sProg = strtoupper(trim($userProg ?? ''));

    if (empty($cSec) || empty($sSec)) return true;
    if ($cSec === $sSec) return true;
    if (strpos($cSec, $sSec) !== false || strpos($sSec, $cSec) !== false) return true;
    if (!empty($cProg) && !empty($sProg) && $cProg === $sProg) {
        if (preg_replace('/[^0-9A-Z]/', '', $cSec) === preg_replace('/[^0-9A-Z]/', '', $sSec)) return true;
    }
    return false;
}

function isClassAssignedToTeacher(array $c, string $tEmail, string $tName): bool {
    $tEmail = strtolower(trim($tEmail));
    $tName = strtolower(trim($tName));
    
    // 1. Direct instructor_email match
    $cInstEmail = strtolower(trim($c['instructor_email'] ?? ''));
    if (!empty($tEmail) && !empty($cInstEmail) && $cInstEmail === $tEmail) {
        return true;
    }
    
    // 2. Direct created_by_email match
    $cCreatedEmail = strtolower(trim($c['created_by_email'] ?? ''));
    if (!empty($tEmail) && !empty($cCreatedEmail) && $cCreatedEmail === $tEmail) {
        return true;
    }
    
    // 3. Name token overlap (e.g. "MORENO, EDSAN" vs "Prof. Edsan Moreno")
    $cInst = strtolower(trim($c['instructor'] ?? ''));
    if (!empty($cInst) && !in_array($cInst, ['tba', 'arranged', 'unassigned', 'none'])) {
        $cWords = preg_split('/[\s,\.\-]+/', $cInst, -1, PREG_SPLIT_NO_EMPTY);
        $tWords = preg_split('/[\s,\.\-]+/', $tName, -1, PREG_SPLIT_NO_EMPTY);
        
        $filterTitles = function($w) {
            return strlen($w) >= 3 && !in_array($w, ['prof', 'professor', 'dr', 'engr', 'instructor', 'sir', 'maam', 'dean']);
        };
        $cFiltered = array_values(array_filter($cWords, $filterTitles));
        $tFiltered = array_values(array_filter($tWords, $filterTitles));
        
        if (!empty($cFiltered) && !empty($tFiltered)) {
            $matched = array_intersect($cFiltered, $tFiltered);
            if (count($matched) >= 1) {
                return true;
            }
        }
    }
    
    return false;
}

function normalizeMeetingLink(?string $link, string $courseCode, string $platform = 'plugnmeet', ?string $defaultLink = null): string {
    $link = trim($link ?? '');
    if (str_contains($link, 'meet.google.com/live_room.php') || str_contains($link, 'meet.google.com//live_room.php')) {
        $link = preg_replace('#^https?://meet\.google\.com/+#' , '/', $link);
    }
    if (str_contains($link, 'live_room.php')) {
        return preg_replace('#^https?://[^/]+/+#i', '/', $link);
    }
    $subjClean = preg_replace('/[^A-Za-z0-9]/', '', $courseCode);
    return "/live_room.php?course_code=" . rawurlencode($courseCode) . "&room_id=NPC-ELMS-" . $subjClean;
}

function normalizeGoogleMeetLink(?string $link, string $courseCode, ?string $defaultLink = null): string {
    return normalizeMeetingLink($link, $courseCode, 'plugnmeet', $defaultLink);
}

function registerStudentLivePresenceJoin(string $sessionCode, string $courseCode, string $stNum, string $stName, string $stEmail, string $presenceFile, string $elmsFile): array {
    $now = time();
    $computedStatus = 'present';
    $elms = loadElmsData($elmsFile);
    foreach ($elms['courses'] as $c) {
        if (($c['live_session']['session_code'] ?? '') === $sessionCode || $c['code'] === $courseCode) {
            $presUntil = !empty($c['live_session']['present_until']) ? strtotime($c['live_session']['present_until']) : 0;
            $lateUntil = !empty($c['live_session']['late_until']) ? strtotime($c['live_session']['late_until']) : 0;
            if ($presUntil && $now > $presUntil) {
                $computedStatus = ($lateUntil && $now <= $lateUntil) ? 'late' : 'late';
            }
            break;
        }
    }

    $presence = loadPresenceData($presenceFile);
    if (!isset($presence[$sessionCode])) {
        $presence[$sessionCode] = [];
    }

    $isRejoin = false;
    $prevLeaves = 0;
    $accumSecs = 0;

    if (isset($presence[$sessionCode][$stNum])) {
        $existing = &$presence[$sessionCode][$stNum];
        $prevLeaves = $existing['leave_count'] ?? 0;
        $accumSecs = $existing['accumulated_seconds'] ?? 0;

        if (empty($existing['is_online'])) {
            $isRejoin = true;
        }

        $existing['is_online'] = true;
        $existing['current_join_ts'] = $now;
        $existing['last_heartbeat'] = $now;
        $existing['last_joined_at'] = date('c', $now);
        $existing['student_name'] = $stName;
        $existing['student_email'] = $stEmail;
        $existing['verified_via'] = 'plugnmeet_verified';
        $existingEntry = $existing;
        unset($existing);
    } else {
        $presence[$sessionCode][$stNum] = [
            'student_number'      => $stNum,
            'student_name'        => $stName,
            'student_email'       => $stEmail,
            'session_code'        => $sessionCode,
            'status'              => $computedStatus,
            'is_online'           => true,
            'accumulated_seconds' => 0,
            'current_join_ts'     => $now,
            'last_joined_at'      => date('c', $now),
            'last_heartbeat'      => $now,
            'last_left_at'        => null,
            'leave_count'         => 0,
            'check_in_at'         => date('c', $now),
            'verified_via'        => 'plugnmeet_verified'
        ];
        $existingEntry = $presence[$sessionCode][$stNum];
    }

    savePresenceData($presenceFile, $presence);

    // Sync official attendance record to Supabase
    $refId = 'REF-PNM-' . date('Ymd') . '-' . strtoupper(substr(md5($sessionCode . $stNum), 0, 6));
    require_once __DIR__ . '/../includes/supabase_helper.php';
    
    // Check if record exists in attendance_records
    $recCheck = supabaseServiceQuery(
        "/rest/v1/attendance_records?session_code=eq." . rawurlencode($sessionCode) . 
        "&student_number=eq." . rawurlencode($stNum) . "&limit=1"
    );

    if ($recCheck['status'] === 200 && empty($recCheck['data'])) {
        supabaseServiceQuery("/rest/v1/attendance_records", 'POST', [[
            'student_id'     => $stNum,
            'student_name'   => $stName,
            'student_number' => $stNum,
            'session_code'   => $sessionCode,
            'check_in_at'    => date('c', $now),
            'method'         => 'live_portal',
            'status'         => $computedStatus,
            'reference_id'   => $refId,
            'verified_via'   => 'plugnmeet_verified'
        ]], ["Prefer: resolution=merge-duplicates"]);
    }

    return [
        'success'             => true,
        'is_rejoin'           => $isRejoin,
        'is_online'           => true,
        'duration_seconds'    => $accumSecs,
        'leave_count'         => $prevLeaves,
        'status'              => $computedStatus,
        'check_in_at'         => $existingEntry['check_in_at'],
        'student_name'        => $stName,
        'student_number'      => $stNum,
        'server_timestamp'    => $now
    ];
}

// If included as a library, stop here and do not execute the API router
if (!$isDirectApiCall) {
    return;
}

// ─── 0. GET: Secure File Downloads (Materials & Submissions) ──────────────────
if ($action === 'download_material') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        exit('Invalid material ID.');
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lms_materials WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $mat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mat) {
        http_response_code(404);
        exit('Learning material not found.');
    }

    $relPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $mat['file_path']);
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relPath;

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        exit('File not found on server disk.');
    }

    $origName = $mat['file_name'] ?: basename($fullPath);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $mimes = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc'  => 'application/msword',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls'  => 'application/vnd.ms-excel',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'zip'  => 'application/zip',
        'txt'  => 'text/plain'
    ];
    $contentType = $mimes[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . addslashes($origName) . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($fullPath);
    exit;
}

if ($action === 'download_submission') {
    $id = trim($_GET['id'] ?? '');
    if (empty($id)) {
        http_response_code(400);
        exit('Invalid submission ID.');
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lms_submissions WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sub || empty($sub['file_path'])) {
        http_response_code(404);
        exit('Submission file not found.');
    }

    $relPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sub['file_path']);
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relPath;

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        exit('File not found on server disk.');
    }

    $origName = $sub['file_name'] ?: basename($fullPath);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $mimes = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc'  => 'application/msword',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls'  => 'application/vnd.ms-excel',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'zip'  => 'application/zip',
        'txt'  => 'text/plain'
    ];
    $contentType = $mimes[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . addslashes($origName) . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($fullPath);
    exit;
}

// ─── 1. GET: Fetch Courses, Modules & Personalized Submissions (Real-Time MySQL)
if ($action === 'get_courses' || ($method === 'GET' && empty($action))) {
    $db = getDB();
    $studentSection = $_SESSION['section'] ?? '2A';
    $studentProgram = $_SESSION['program'] ?? 'AIS';

    // Retrieve courses from MySQL classes table
    $stmtClasses = $db->query("SELECT * FROM classes ORDER BY code ASC");
    $rawClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

    // If classes table is empty, seed defaults
    if (empty($rawClasses)) {
        require_once __DIR__ . '/../migrations/013_lms_realtime_tables.php';
        $stmtClasses = $db->query("SELECT * FROM classes ORDER BY code ASC");
        $rawClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
    }

    // Retrieve all active materials from MySQL
    $stmtMat = $db->query("SELECT * FROM lms_materials ORDER BY created_at DESC");
    $allMaterials = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
    $materialsByCourse = [];
    foreach ($allMaterials as $m) {
        $cCode = strtoupper(trim($m['course_code']));
        $materialsByCourse[$cCode][] = [
            'id'          => $m['id'],
            'course_code' => $m['course_code'],
            'title'       => $m['title'],
            'description' => $m['description'] ?? '',
            'file_name'   => $m['file_name'],
            'file_path'   => $m['file_path'],
            'type'        => $m['file_type'],
            'size'        => $m['file_size'],
            'date'        => substr($m['created_at'], 0, 10),
            'uploaded_by' => $m['uploaded_by']
        ];
    }

    // Retrieve assignments from MySQL
    $stmtAsgs = $db->query("SELECT * FROM lms_assignments ORDER BY created_at DESC");
    $allAsgs = $stmtAsgs->fetchAll(PDO::FETCH_ASSOC);

    // Retrieve submissions
    if ($userRole === 'student') {
        $stmtSubs = $db->prepare("SELECT * FROM lms_submissions WHERE student_number = ? OR LOWER(student_email) = ?");
        $stmtSubs->execute([$studentNumber, strtolower($userEmail)]);
    } else {
        $stmtSubs = $db->query("SELECT * FROM lms_submissions ORDER BY submitted_at DESC");
    }
    $allSubs = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);
    $subsByAsg = [];
    foreach ($allSubs as $s) {
        $subsByAsg[$s['assignment_id']] = $s;
    }

    $asgsByCourse = [];
    foreach ($allAsgs as $a) {
        $sub = $subsByAsg[$a['id']] ?? null;
        $cCode = strtoupper(trim($a['course_code']));
        $asgsByCourse[$cCode][] = [
            'id'                  => $a['id'],
            'course_code'         => $a['course_code'],
            'title'               => $a['title'],
            'instructions'        => $a['instructions'] ?? '',
            'due_date'            => $a['due_date'],
            'points'              => (int)$a['points'],
            'status'              => $sub ? $sub['status'] : 'Pending',
            'score'               => $sub['score'] ?? null,
            'remarks'             => $sub['remarks'] ?? null,
            'submitted_link'      => $sub['submitted_link'] ?? null,
            'submitted_file_name' => $sub['file_name'] ?? null,
            'submitted_file_path' => $sub['file_path'] ?? null,
            'submitted_file_size' => $sub['file_size'] ?? null,
            'submission_id'       => $sub['id'] ?? null,
            'submitted_at'        => $sub['submitted_at'] ?? null,
            'created_at'          => substr($a['created_at'], 0, 10)
        ];
    }

    // Active live sessions check
    $elmsData = loadElmsData($elmsFile);
    $liveSessionsByCourse = [];
    foreach ($elmsData['courses'] as $ec) {
        if (!empty($ec['live_session'])) {
            $liveSessionsByCourse[strtoupper(trim($ec['code']))] = $ec['live_session'];
        }
    }

    $courses = [];
    foreach ($rawClasses as $c) {
        // Faculty isolation: Instructors only see courses assigned to them based on schedule
        if (in_array($userRole, ['teacher', 'faculty'])) {
            if (!isClassAssignedToTeacher($c, $userEmail, $userName)) {
                continue;
            }
        } elseif ($userRole === 'student') {
            // Students only see courses matching their enrolled section (e.g. AIS 2A)
            if (!matchesStudentSection($c['section'] ?? '', $c['program'] ?? '', $studentSection, $studentProgram)) {
                continue;
            }
        }

        $code = strtoupper(trim($c['code']));
        $live = $liveSessionsByCourse[$code] ?? [
            'is_active'            => (bool)($c['is_live'] ?? false),
            'topic'                => $c['title'] . ' Live Lecture',
            'meeting_link'         => $c['meeting_link'] ?? '',
            'allowed_section'      => $c['section'] ?? '2A'
        ];
        $isLive = !empty($live['is_active']);
        $isLiveForMe = false;
        if ($isLive) {
            if ($userRole !== 'student') {
                $isLiveForMe = true;
            } else {
                $isLiveForMe = matchesStudentSection($c['section'] ?? '', $c['program'] ?? '', $studentSection, $studentProgram);
            }
        }

        $courses[] = [
            'id'               => $c['id'],
            'code'             => $c['code'],
            'title'            => $c['title'] ?: $c['name'],
            'name'             => $c['name'] ?: $c['title'],
            'program'          => $c['program'] ?? 'AIS',
            'section'          => $c['section'] ?? '2A',
            'instructor'       => $c['instructor'] ?: 'Faculty Instructor',
            'instructor_email' => $c['instructor_email'] ?? '',
            'schedule'         => trim(($c['schedule_day'] ?? '') . ' · ' . ($c['start_time'] ?? '') . ' – ' . ($c['end_time'] ?? '')),
            'room'             => $c['room'] ?: 'IT Lab 3',
            'progress'         => 65,
            'modules'          => $materialsByCourse[$code] ?? [],
            'assignments'      => $asgsByCourse[$code] ?? [],
            'live_session'     => $live,
            'meeting_link'     => $live['meeting_link'] ?? '',
            'is_live'          => $isLive,
            'is_live_for_me'   => $isLiveForMe
        ];
    }

    echo json_encode([
        'success'         => true,
        'courses'         => $courses,
        'role'            => $userRole,
        'student_section' => $studentSection,
        'student_program' => $studentProgram,
        'term'            => '1st Semester · AY 2026–2027'
    ]);
    exit;
}

// ─── 2. POST: Add / Upload Module to Course (Faculty / Admin -> MySQL) ────────
if ($action === 'add_module' || $action === 'upload_material') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only instructors and administrators can add course modules.']);
        exit;
    }

    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $title = trim($_POST['title'] ?? $input['title'] ?? '');
    $description = trim($_POST['description'] ?? $input['description'] ?? '');

    if (empty($courseCode) || empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Course code and module title are required.']);
        exit;
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'materials';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = '';
    $fileName = '';
    $fileType = 'pdf';
    $fileSize = '0 KB';

    // Handle real file upload
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['material_file'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowedExts = ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'txt', 'png', 'jpg', 'jpeg', 'webp', 'zip'];
        if (!in_array($ext, $allowedExts)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File type .' . $ext . ' not allowed. Allowed types: ' . implode(', ', $allowedExts)]);
            exit;
        }

        $safeFileName = 'mat_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
            exit;
        }

        $filePath = 'uploads/materials/' . $safeFileName;
        $fileName = $origName;
        $fileType = in_array($ext, ['png', 'jpg', 'jpeg', 'webp']) ? 'image' : (in_array($ext, ['pptx', 'ppt']) ? 'slides' : $ext);
        $bytes = filesize($destPath);
        $fileSize = ($bytes >= 1048576) ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024, 0) . ' KB';
    } else {
        // Fallback for metadata-only creation
        $fileName = trim($_POST['file_name'] ?? ($title . '.pdf'));
        $fileType = trim($_POST['type'] ?? 'pdf');
        $fileSize = trim($_POST['size'] ?? '1.5 MB');
        $safeFileName = 'mat_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.pdf';
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
        
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>/Contents 4 0 R>>endobj\n4 0 obj<</Length 90>>stream\nBT\n/F1 16 Tf\n50 720 Td\n(NPC LMS - " . $title . ") Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000219 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n360\n%%EOF";
        file_put_contents($destPath, $pdfContent);
        $filePath = 'uploads/materials/' . $safeFileName;
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO lms_materials (course_code, title, description, file_path, file_name, file_type, file_size, uploaded_by, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $courseCode, $title, $description, $filePath, $fileName, $fileType, $fileSize,
        $_SESSION['name'] ?? $userEmail
    ]);
    $newId = (int)$db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => "Module successfully published to {$courseCode}.",
        'module'  => [
            'id'          => $newId,
            'course_code' => $courseCode,
            'title'       => $title,
            'file_name'   => $fileName,
            'file_path'   => $filePath,
            'type'        => $fileType,
            'size'        => $fileSize,
            'date'        => date('Y-m-d')
        ]
    ]);
    exit;
}

// ─── POST: Delete Module / Material (Faculty / Admin) ─────────────────────────
if ($action === 'delete_material' || $action === 'delete_module') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $id = intval($_POST['id'] ?? $input['id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT file_path FROM lms_materials WHERE id = ?");
    $stmt->execute([$id]);
    $mat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($mat) {
        if (!empty($mat['file_path'])) {
            $f = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $mat['file_path']);
            if (file_exists($f)) @unlink($f);
        }
        $db->prepare("DELETE FROM lms_materials WHERE id = ?")->execute([$id]);
    }
    echo json_encode(['success' => true, 'message' => 'Material successfully removed.']);
    exit;
}

// ─── 3. POST: Create New Assignment (Faculty / Admin -> MySQL) ────────────────
if ($action === 'create_assignment') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only instructors and administrators can create assignments.']);
        exit;
    }

    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $title = trim($_POST['title'] ?? $input['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? $input['instructions'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? $input['due_date'] ?? date('Y-m-d 23:59', strtotime('+7 days')));
    $points = intval($_POST['points'] ?? $input['points'] ?? 100);

    if (empty($courseCode) || empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Course code and assignment title are required.']);
        exit;
    }

    $attachmentPath = null;
    $attachmentName = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $safeName = 'asg_att_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $destPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'materials' . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $attachmentPath = 'uploads/materials/' . $safeName;
            $attachmentName = $origName;
        }
    }

    $newAsgId = 'asg-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO lms_assignments (id, course_code, title, instructions, due_date, points, attachment_path, attachment_name, created_by, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $newAsgId, $courseCode, $title, $instructions, $dueDate,
        $points > 0 ? $points : 100, $attachmentPath, $attachmentName,
        $_SESSION['name'] ?? $userEmail
    ]);

    echo json_encode([
        'success'    => true,
        'message'    => "Assignment '{$title}' created successfully for {$courseCode}.",
        'assignment' => [
            'id'           => $newAsgId,
            'course_code'  => $courseCode,
            'title'        => $title,
            'instructions' => $instructions,
            'due_date'     => $dueDate,
            'points'       => $points > 0 ? $points : 100,
            'created_at'   => date('Y-m-d')
        ]
    ]);
    exit;
}

// ─── POST: Delete Assignment (Faculty / Admin) ────────────────────────────────
if ($action === 'delete_assignment') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $id = trim($_POST['id'] ?? $input['id'] ?? '');
    $db = getDB();
    $db->prepare("DELETE FROM lms_submissions WHERE assignment_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM lms_assignments WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Assignment deleted.']);
    exit;
}

// ─── 4. POST: Submit Assignment (Student -> MySQL File Upload & Links) ─────────
if ($action === 'submit_assignment') {
    $asgId = trim($_POST['assignment_id'] ?? $input['assignment_id'] ?? '');
    $link  = trim($_POST['link'] ?? $input['link'] ?? '');
    $notes = trim($_POST['notes'] ?? $input['notes'] ?? '');

    if (empty($asgId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Assignment ID is required.']);
        exit;
    }

    $db = getDB();
    $stmtAsg = $db->prepare("SELECT course_code, title FROM lms_assignments WHERE id = ? LIMIT 1");
    $stmtAsg->execute([$asgId]);
    $asg = $stmtAsg->fetch(PDO::FETCH_ASSOC);
    $courseCode = $asg['course_code'] ?? 'GENERAL';

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'submissions';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = null;
    $fileName = null;
    $fileType = null;
    $fileSize = null;

    // Check for student file upload (supports all image types, PDF, Word DOCX, ZIP, etc.)
    $uploadFile = $_FILES['submission_file'] ?? $_FILES['file'] ?? null;
    if ($uploadFile && $uploadFile['error'] === UPLOAD_ERR_OK) {
        $origName = basename($uploadFile['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowedExts = ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'zip', 'txt', 'sql'];
        if (!in_array($ext, $allowedExts)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File extension .' . $ext . ' not allowed. Allowed formats: Images (PNG, JPG, WebP), PDF, Word DOCX, PPTX, Excel, or ZIP.']);
            exit;
        }

        $safeFileName = 'sub_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($uploadFile['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save student submission file on server.']);
            exit;
        }

        $filePath = 'uploads/submissions/' . $safeFileName;
        $fileName = $origName;
        $fileType = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) ? 'image' : $ext;
        $bytes = filesize($destPath);
        $fileSize = ($bytes >= 1048576) ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024, 0) . ' KB';
    }

    if (empty($filePath) && empty($link)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please attach a file (Image, PDF, Word DOCX, ZIP) or provide a submission link.']);
        exit;
    }

    // Check if student already has a submission record
    $stmtCheck = $db->prepare("SELECT id, file_path, file_name, file_type, file_size FROM lms_submissions WHERE assignment_id = ? AND (student_number = ? OR LOWER(student_email) = ?)");
    $stmtCheck->execute([$asgId, $studentNumber, strtolower($userEmail)]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $subId = $existing['id'];
        $finalPath = $filePath ?: $existing['file_path'];
        $finalName = $fileName ?: $existing['file_name'];
        $finalType = $fileType ?: $existing['file_type'];
        $finalSize = $fileSize ?: $existing['file_size'];

        $upd = $db->prepare("UPDATE lms_submissions 
                              SET file_path = ?, file_name = ?, file_type = ?, file_size = ?, submitted_link = ?, notes = ?, submitted_at = NOW(), status = 'Submitted' 
                              WHERE id = ?");
        $upd->execute([$finalPath, $finalName, $finalType, $finalSize, $link, $notes, $subId]);
    } else {
        $subId = 'sub-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $ins = $db->prepare("INSERT INTO lms_submissions (id, assignment_id, course_code, student_number, student_name, student_email, file_path, file_name, file_type, file_size, submitted_link, notes, submitted_at, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Submitted')");
        $ins->execute([
            $subId, $asgId, $courseCode, $studentNumber, $userName,
            $userEmail ?: ($studentNumber . '@navotaspolytechniccollege.edu.ph'),
            $filePath, $fileName, $fileType, $fileSize, $link, $notes
        ]);
    }

    echo json_encode([
        'success'    => true,
        'message'    => 'Coursework successfully submitted to your instructor!',
        'submission' => [
            'id'             => $subId,
            'assignment_id'  => $asgId,
            'course_code'    => $courseCode,
            'file_name'      => $fileName,
            'file_size'      => $fileSize,
            'submitted_link' => $link,
            'submitted_at'   => date('Y-m-d H:i:s'),
            'status'         => 'Submitted'
        ]
    ]);
    exit;
}

// ─── 5. GET: Retrieve Submissions List (Faculty / Admin -> MySQL) ──────────────
if ($action === 'get_submissions') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access to student submissions.']);
        exit;
    }

    $db = getDB();

    if (in_array($userRole, ['teacher', 'faculty'])) {
        // Find course codes assigned to this faculty member based on class schedule
        $stmtClasses = $db->query("SELECT * FROM classes");
        $allClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
        $myCourseCodes = [];
        foreach ($allClasses as $c) {
            if (isClassAssignedToTeacher($c, $userEmail, $userName)) {
                $myCourseCodes[] = strtoupper(trim($c['code']));
            }
        }

        if (empty($myCourseCodes)) {
            echo json_encode([
                'success'     => true,
                'submissions' => [],
                'total'       => 0
            ]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($myCourseCodes), '?'));
        $stmt = $db->prepare("SELECT s.*, a.title AS assignment_title, a.points AS total_points, a.due_date
                              FROM lms_submissions s
                              LEFT JOIN lms_assignments a ON a.id = s.assignment_id
                              WHERE UPPER(TRIM(s.course_code)) IN ($placeholders)
                              ORDER BY s.submitted_at DESC");
        $stmt->execute($myCourseCodes);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("SELECT s.*, a.title AS assignment_title, a.points AS total_points, a.due_date
                            FROM lms_submissions s
                            LEFT JOIN lms_assignments a ON a.id = s.assignment_id
                            ORDER BY s.submitted_at DESC");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success'     => true,
        'submissions' => $subs,
        'total'       => count($subs)
    ]);
    exit;
}

// ─── 6. POST: Grade Student Submission (Faculty / Admin -> MySQL) ─────────────
if ($action === 'grade_submission') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only instructors and administrators can grade student coursework.']);
        exit;
    }

    $subId = trim($_POST['submission_id'] ?? $input['submission_id'] ?? '');
    $score = trim($_POST['score'] ?? $input['score'] ?? '');
    $remarks = trim($_POST['remarks'] ?? $input['remarks'] ?? '');

    if (empty($subId) || empty($score)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Submission ID and score are required.']);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE lms_submissions 
                          SET status = 'Graded', score = ?, remarks = ?, graded_by = ?, graded_at = NOW() 
                          WHERE id = ?");
    $stmt->execute([$score, $remarks, $userName . ' (' . ($userEmail ?: 'Faculty') . ')', $subId]);

    echo json_encode([
        'success' => true,
        'message' => "Student submission graded ({$score}). Feedback recorded in MySQL.",
    ]);
    exit;
}

// ─── 7. POST: Toggle Live Virtual Classroom ON/OFF (Teacher / Admin) ─────────
if ($action === 'toggle_live_class') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only instructors and administrators can start or end live classes.']);
        exit;
    }

    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $state = strtolower(trim($_POST['state'] ?? $input['state'] ?? 'start')); // 'start' or 'end'
    $topic = trim($_POST['topic'] ?? $input['topic'] ?? '');
    $agenda = trim($_POST['agenda'] ?? $input['agenda'] ?? '');
    $platform = 'plugnmeet';
    $meetingLink = trim($_POST['meeting_link'] ?? $input['meeting_link'] ?? '');
    $gracePeriod = max(5, min(120, intval($_POST['grace_period'] ?? $input['grace_period'] ?? 15)));

    if (empty($courseCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Course code is required.']);
        exit;
    }

    $data = loadElmsData($elmsFile);
    $foundCourse = null;

    foreach ($data['courses'] as &$c) {
        if ($c['code'] === $courseCode || ($c['id'] ?? '') === $courseCode) {
            $secCode = preg_replace('/[^A-Za-z0-9]/', '', $c['section'] ?? '2A');
            $subjCode = preg_replace('/[^A-Za-z0-9]/', '', $c['code']);
            $sessionCode = strtoupper("NPC-{$subjCode}-" . date('Y-m-d'));

            if ($state === 'start') {
                $roomId = 'NPC-ELMS-' . $subjCode . '-SEC' . $secCode . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

                $platform = 'plugnmeet';
                $timerMode = trim($_POST['timer_mode'] ?? $input['timer_mode'] ?? 'unlimited');
                $durationMinutes = intval($_POST['duration_minutes'] ?? $input['duration_minutes'] ?? 0);
                $meetingLink = "/live_room.php?session_code=" . rawurlencode($sessionCode) . "&course_code=" . rawurlencode($c['code']) . "&room_id=" . rawurlencode($roomId);
                $c['meeting_link'] = $meetingLink;

                $nowTs = time();
                $presUntil = ($timerMode === 'unlimited') ? date('c', $nowTs + 86400) : date('c', $nowTs + ($gracePeriod * 60));
                $lateUntil = ($timerMode === 'unlimited') ? date('c', $nowTs + 86400) : date('c', $nowTs + (($gracePeriod + 10) * 60));

                $c['live_session'] = [
                    'is_active'            => true,
                    'topic'                => $topic ?: ($c['title'] . ' Live Virtual Lecture'),
                    'agenda'               => $agenda,
                    'started_at'           => date('Y-m-d H:i:s'),
                    'ended_at'             => null,
                    'started_by'           => $userName . ' (' . ($userEmail ?: 'Faculty') . ')',
                    'room_id'              => $roomId,
                    'session_code'         => $sessionCode,
                    'allowed_section'      => $c['section'] ?? '2A',
                    'platform'             => $platform,
                    'timer_mode'           => $timerMode,
                    'duration_minutes'     => $durationMinutes,
                    'meeting_link'         => $meetingLink,
                    'grace_period_minutes' => $gracePeriod,
                    'present_until'        => $presUntil,
                    'late_until'           => $lateUntil,
                    'is_attendance_locked' => false
                ];

                // Synchronize with Supabase attendance_sessions
                require_once __DIR__ . '/../includes/supabase_helper.php';
                supabaseServiceQuery("/rest/v1/attendance_sessions", 'POST', [[
                    'session_code'         => $sessionCode,
                    'class_code'           => $c['code'],
                    'section'              => $c['section'] ?? '2A',
                    'instructor'           => $c['instructor'] ?? $userName,
                    'is_active'            => true,
                    'present_until'        => $presUntil,
                    'late_until'           => $lateUntil,
                    'meeting_provider'     => $platform,
                    'meeting_link'         => $meetingLink,
                    'topic'                => $topic ?: ($c['title'] . ' Live Virtual Lecture'),
                    'agenda'               => $agenda,
                    'session_status'       => 'active',
                    'grace_period_minutes' => $gracePeriod,
                    'is_attendance_locked' => false,
                    'created_at'           => date('c')
                ]], ["Prefer: resolution=merge-duplicates"]);

            } else { // end
                if (!isset($c['live_session'])) {
                    $c['live_session'] = [];
                }
                $c['live_session']['is_active'] = false;
                $c['live_session']['ended_at'] = date('Y-m-d H:i:s');

                // Synchronize with Supabase
                require_once __DIR__ . '/../includes/supabase_helper.php';
                supabaseServiceQuery(
                    "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($c['live_session']['session_code'] ?? $sessionCode),
                    'PATCH',
                    ['is_active' => false, 'session_status' => 'ended']
                );
            }
            $foundCourse = $c;
            break;
        }
    }
    unset($c);

    if (!$foundCourse) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Course {$courseCode} not found."]);
        exit;
    }

    saveElmsData($elmsFile, $data);

    echo json_encode([
        'success'      => true,
        'message'      => ($state === 'start') ? "Live class started for {$foundCourse['code']} (Section {$foundCourse['section']})." : "Live class ended for {$foundCourse['code']}.",
        'live_session' => $foundCourse['live_session'],
        'course'       => $foundCourse
    ]);
    exit;
}

// ─── POST: Update Live Meeting Link (Teacher / Faculty) ──────────────────────
if ($action === 'update_live_meeting_link') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $courseCode  = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $meetingLink = trim($_POST['meeting_link'] ?? $input['meeting_link'] ?? '');

    if (empty($courseCode) || empty($meetingLink)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Course code and meeting link are required.']);
        exit;
    }

    $meetingLink = normalizeMeetingLink($meetingLink, $courseCode);

    $data = loadElmsData($elmsFile);
    $found = false;

    foreach ($data['courses'] as &$c) {
        if ($c['code'] === $courseCode || ($c['id'] ?? '') === $courseCode) {
            $c['meeting_link'] = $meetingLink;
            if (isset($c['live_session']) && !empty($c['live_session']['is_active'])) {
                $c['live_session']['meeting_link'] = $meetingLink;
                require_once __DIR__ . '/../includes/supabase_helper.php';
                if (!empty($c['live_session']['session_code'])) {
                    supabaseServiceQuery(
                        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($c['live_session']['session_code']),
                        'PATCH',
                        ['meeting_link' => $meetingLink]
                    );
                }
            }
            $found = true;
            break;
        }
    }
    unset($c);

    if ($found) {
        saveElmsData($elmsFile, $data);
        echo json_encode([
            'success'      => true,
            'meeting_link' => $meetingLink,
            'message'      => 'Meeting link updated successfully for all students.'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Course {$courseCode} not found."]);
    }
    exit;
}

// ─── 8. GET: Retrieve Active Live Sessions (Section-Gated for Students) ──────
if ($action === 'get_live_sessions') {
    $data = loadElmsData($elmsFile);
    $studentSection = $_SESSION['section'] ?? '2A';
    $studentProgram = $_SESSION['program'] ?? 'AIS';
    $activeSessions = [];

    foreach ($data['courses'] as $c) {
        $live = $c['live_session'] ?? null;
        if ($live && !empty($live['is_active'])) {
            $isMine = true;
            if ($userRole === 'student') {
                $isMine = matchesStudentSection($c['section'] ?? '', $c['program'] ?? '', $studentSection, $studentProgram);
            }
            if ($isMine) {
                $activeSessions[] = [
                    'course_id'            => $c['id'],
                    'course_code'          => $c['code'],
                    'course_title'         => $c['title'],
                    'section'              => $c['section'],
                    'program'              => $c['program'] ?? '',
                    'instructor'           => $c['instructor'],
                    'topic'                => $live['topic'],
                    'agenda'               => $live['agenda'] ?? '',
                    'started_at'           => $live['started_at'],
                    'room_id'              => $live['room_id'],
                    'session_code'         => $live['session_code'] ?? '',
                    'platform'             => $live['platform'] ?? 'plugnmeet',
                    'timer_mode'           => $live['timer_mode'] ?? 'unlimited',
                    'duration_minutes'     => $live['duration_minutes'] ?? 0,
                    'meeting_link'         => normalizeMeetingLink($live['meeting_link'] ?? '', $c['code'], $live['platform'] ?? 'plugnmeet', $c['meeting_link'] ?? null),
                    'present_until'        => $live['present_until'] ?? null,
                    'late_until'           => $live['late_until'] ?? null,
                    'is_attendance_locked' => !empty($live['is_attendance_locked'])
                ];
            }
        }
    }

    echo json_encode([
        'success'         => true,
        'live_sessions'   => $activeSessions,
        'total'           => count($activeSessions),
        'student_section' => $studentSection,
        'role'            => $userRole
    ]);
    exit;
}

// ─── 9. POST: Join Live Classroom (Verifies Section Access & Returns Room/Meet) 
if ($action === 'join_live_class') {
    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $roomId = trim($_POST['room_id'] ?? $input['room_id'] ?? '');

    $data = loadElmsData($elmsFile);
    $foundCourse = null;

    foreach ($data['courses'] as $c) {
        if (($courseCode && $c['code'] === $courseCode) || ($roomId && ($c['live_session']['room_id'] ?? '') === $roomId)) {
            $foundCourse = $c;
            break;
        }
    }

    if (!$foundCourse) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Virtual classroom not found.']);
        exit;
    }

    $live = $foundCourse['live_session'] ?? null;
    if (!$live || empty($live['is_active'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This live class has ended or is not currently active.']);
        exit;
    }

    $studentSection = $_SESSION['section'] ?? '2A';
    $studentProgram = $_SESSION['program'] ?? 'AIS';

    // Strict section check for students
    if ($userRole === 'student') {
        $allowed = matchesStudentSection($foundCourse['section'] ?? '', $foundCourse['program'] ?? '', $studentSection, $studentProgram);
        if (!$allowed) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error'   => "Access Denied: This live class is restricted to Section {$foundCourse['section']} only. Your enrolled section is {$studentProgram} {$studentSection}."
            ]);
            exit;
        }
    }

    // Return room and Meet launch parameters
    echo json_encode([
        'success'              => true,
        'room_id'              => $live['room_id'],
        'session_code'         => $live['session_code'] ?? '',
        'platform'             => $live['platform'] ?? 'plugnmeet',
        'timer_mode'           => $live['timer_mode'] ?? 'unlimited',
        'duration_minutes'     => $live['duration_minutes'] ?? 0,
        'meeting_link'         => normalizeMeetingLink($live['meeting_link'] ?? '', $foundCourse['code'], $live['platform'] ?? 'plugnmeet'),
        'topic'                => $live['topic'],
        'agenda'               => $live['agenda'] ?? '',
        'course_code'          => $foundCourse['code'],
        'course_title'         => $foundCourse['title'],
        'section'              => $foundCourse['section'],
        'instructor'           => $foundCourse['instructor'],
        'display_name'         => $userName . ($studentNumber ? " ({$studentNumber})" : ""),
        'role'                 => $userRole,
        'is_attendance_locked' => !empty($live['is_attendance_locked']),
        'present_until'        => $live['present_until'] ?? null,
        'late_until'           => $live['late_until'] ?? null
    ]);
    exit;
}

// ─── 10. POST: Live Classroom Attendance Controls (Lock/Unlock) ───────────────
if ($action === 'toggle_live_attendance_lock') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $locked = !empty($_POST['locked'] ?? $input['locked'] ?? false);

    $data = loadElmsData($elmsFile);
    foreach ($data['courses'] as &$c) {
        if ($c['code'] === $courseCode || ($c['id'] ?? '') === $courseCode) {
            if (isset($c['live_session'])) {
                $c['live_session']['is_attendance_locked'] = $locked;
                if (!empty($c['live_session']['session_code'])) {
                    require_once __DIR__ . '/../includes/supabase_helper.php';
                    supabaseServiceQuery(
                        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($c['live_session']['session_code']),
                        'PATCH',
                        ['is_attendance_locked' => $locked]
                    );
                }
            }
            break;
        }
    }
    unset($c);
    saveElmsData($elmsFile, $data);

    echo json_encode(['success' => true, 'is_attendance_locked' => $locked]);
    exit;
}

// ─── 11. POST: Live Classroom Extend Grace Period ─────────────────────────────
if ($action === 'extend_live_grace_period') {
    if (!in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');
    $minutes = max(1, min(60, intval($_POST['minutes'] ?? $input['minutes'] ?? 10)));

    $data = loadElmsData($elmsFile);
    $resTimes = [];
    foreach ($data['courses'] as &$c) {
        if ($c['code'] === $courseCode || ($c['id'] ?? '') === $courseCode) {
            if (isset($c['live_session']) && !empty($c['live_session']['is_active'])) {
                $now = time();
                $curPres = !empty($c['live_session']['present_until']) ? strtotime($c['live_session']['present_until']) : $now;
                $curLate = !empty($c['live_session']['late_until']) ? strtotime($c['live_session']['late_until']) : $now;
                
                $newPres = date('c', max($now, $curPres) + ($minutes * 60));
                $newLate = date('c', max($now, $curLate) + ($minutes * 60));

                $c['live_session']['present_until'] = $newPres;
                $c['live_session']['late_until'] = $newLate;
                $c['live_session']['is_attendance_locked'] = false;

                $resTimes = ['present_until' => $newPres, 'late_until' => $newLate];

                if (!empty($c['live_session']['session_code'])) {
                    require_once __DIR__ . '/../includes/supabase_helper.php';
                    supabaseServiceQuery(
                        "/rest/v1/attendance_sessions?session_code=eq." . rawurlencode($c['live_session']['session_code']),
                        'PATCH',
                        ['present_until' => $newPres, 'late_until' => $newLate, 'is_attendance_locked' => false]
                    );
                }
            }
            break;
        }
    }
    unset($c);
    saveElmsData($elmsFile, $data);

    echo json_encode(['success' => true, 'times' => $resTimes, 'message' => "Grace period extended by {$minutes} minutes."]);
    exit;
}

// ─── 12. LIVE ONLINE CLASS PRESENCE & REAL-TIME ATTENDANCE TRACKER ───────────
$presenceFile = __DIR__ . '/../backend/elms_presence.json';

function loadPresenceData($filePath): array {
    if (!file_exists($filePath)) return [];
    $raw = @file_get_contents($filePath);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function savePresenceData($filePath, array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($filePath, $json, LOCK_EX) !== false;
}

function parseCollegiateName(string $fullName): array {
    $clean = trim(preg_replace('/\s+/', ' ', $fullName));
    if (empty($clean)) return ['last_name' => '', 'first_name' => '', 'formatted' => ''];
    $tokens = explode(' ', $clean);
    if (count($tokens) === 1) {
        return ['last_name' => $tokens[0], 'first_name' => '', 'formatted' => strtoupper($tokens[0])];
    }
    $suffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v'];
    $suffix = '';
    $lastToken = strtolower(end($tokens));
    if (in_array($lastToken, $suffixes) && count($tokens) > 2) {
        $suffix = ' ' . array_pop($tokens);
    }
    $lower = array_map('strtolower', $tokens);
    $count = count($tokens);
    if ($count >= 3 && in_array($lower[$count - 2], ['dela', 'del', 'san'])) {
        $lastName = implode(' ', array_slice($tokens, -2)) . $suffix;
        $firstName = implode(' ', array_slice($tokens, 0, -2));
    } elseif ($count >= 4 && $lower[$count - 3] === 'de' && in_array($lower[$count - 2], ['la', 'los'])) {
        $lastName = implode(' ', array_slice($tokens, -3)) . $suffix;
        $firstName = implode(' ', array_slice($tokens, 0, -3));
    } else {
        $lastName = array_pop($tokens) . $suffix;
        $firstName = implode(' ', $tokens);
    }
    return [
        'last_name' => $lastName,
        'first_name' => $firstName,
        'formatted' => strtoupper($lastName) . ', ' . $firstName
    ];
}

function generateLiveKitRoomToken(string $room, string $identity, string $name, bool $isAdmin = false, int $durationSeconds = 86400): string {
    $apiKey = 'npc_elms_key';
    $apiSecret = 'npc_elms_secret_2026';
    
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'exp' => $now + $durationSeconds,
        'iss' => $apiKey,
        'sub' => $identity,
        'nbf' => $now - 5,
        'video' => [
            'room' => $room,
            'roomJoin' => true,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'roomAdmin' => $isAdmin
        ],
        'name' => $name
    ], JSON_UNESCAPED_SLASHES);

    $b64Url = function($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    };

    $encodedHeader = $b64Url($header);
    $encodedPayload = $b64Url($payload);
    $sig = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $apiSecret, true);
    return "$encodedHeader.$encodedPayload." . $b64Url($sig);
}

// ─── 11B. GET/POST: Generate LiveKit WebRTC Token (Drive D Media Server) ─────
if ($action === 'get_livekit_token') {
    $roomName = trim($_GET['room'] ?? $_POST['room'] ?? $input['room'] ?? 'NPC-CLASS');
    $stNum = $_SESSION['student_number'] ?? $studentNumber ?? '2024-00192';
    $stName = $_SESSION['name'] ?? $userName ?? 'Student';
    $role = $_SESSION['base_role'] ?? $_SESSION['role'] ?? $userRole;
    $isAdmin = in_array($role, ['teacher', 'faculty', 'admin', 'registrar']);

    $identity = ($role === 'student') 
        ? ('stu_' . preg_replace('/[^a-zA-Z0-9]/', '', $stNum ?: $userEmail))
        : ('fac_' . preg_replace('/[^a-zA-Z0-9]/', '', $userEmail));

    $displayName = ($role === 'student') 
        ? ($stName . ' (' . ($stNum ?: 'Student') . ')')
        : ('Prof. ' . $stName . ' [Instructor]');

    $cleanRoom = preg_replace('/[^A-Za-z0-9_-]/', '', $roomName) ?: 'NPC-CLASS';
    $token = generateLiveKitRoomToken($cleanRoom, $identity, $displayName, $isAdmin, 86400);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'server_url' => 'ws://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ':7880',
        'room' => $cleanRoom,
        'identity' => $identity,
        'name' => $displayName,
        'is_admin' => $isAdmin,
        'duration_seconds' => 86400
    ]);
    exit;
}

// ─── 12A. POST: Student Enters / Joins PlugNmeet Live Session ───────────────
if ($action === 'meeting_presence_join') {
    $sessionCode = trim($_POST['session_code'] ?? $input['session_code'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? $input['course_code'] ?? '');

    if (empty($sessionCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session code is required.']);
        exit;
    }

    $stNum = $_SESSION['student_number'] ?? $studentNumber ?? '2024-00192';
    $stName = $_SESSION['name'] ?? $userName ?? 'Student';
    $stEmail = $_SESSION['email'] ?? $userEmail ?? '';

    $res = registerStudentLivePresenceJoin($sessionCode, $courseCode, $stNum, $stName, $stEmail, $presenceFile, $elmsFile);
    echo json_encode($res);
    exit;
}

// ─── 12B. POST: Live Presence Heartbeat (every 5 seconds) ────────────────────
if ($action === 'meeting_presence_heartbeat') {
    $sessionCode = trim($_POST['session_code'] ?? $input['session_code'] ?? '');
    if (empty($sessionCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session code required.']);
        exit;
    }

    $stNum = $_SESSION['student_number'] ?? $studentNumber ?? '2024-00192';
    $now = time();

    $presence = loadPresenceData($presenceFile);
    if (!isset($presence[$sessionCode][$stNum])) {
        // Not joined yet, auto-join
        $presence[$sessionCode][$stNum] = [
            'student_number'      => $stNum,
            'student_name'        => $_SESSION['name'] ?? $userName ?? 'Student',
            'student_email'       => $_SESSION['email'] ?? $userEmail ?? '',
            'session_code'        => $sessionCode,
            'status'              => 'present',
            'is_online'           => true,
            'accumulated_seconds' => 0,
            'current_join_ts'     => $now,
            'last_joined_at'      => date('c', $now),
            'last_heartbeat'      => $now,
            'last_left_at'        => null,
            'leave_count'         => 0,
            'check_in_at'         => date('c', $now),
            'verified_via'        => 'plugnmeet_verified'
        ];
    }

    $entry = &$presence[$sessionCode][$stNum];

    // If was marked offline, reconnecting now
    if (empty($entry['is_online'])) {
        $entry['leave_count'] = ($entry['leave_count'] ?? 0) + 1;
        $entry['is_online'] = true;
        $entry['current_join_ts'] = $now;
    }

    $entry['last_heartbeat'] = $now;
    $accum = $entry['accumulated_seconds'] ?? 0;
    $currentJoin = $entry['current_join_ts'] ?? $now;
    $activeDuration = $accum + max(0, $now - $currentJoin);

    savePresenceData($presenceFile, $presence);

    echo json_encode([
        'success'          => true,
        'is_online'        => true,
        'duration_seconds' => $activeDuration,
        'leave_count'      => $entry['leave_count'] ?? 0,
        'status'           => $entry['status'] ?? 'present',
        'server_timestamp' => $now
    ]);
    exit;
}

// ─── 12C. POST: Student Leaves Online Class / Closes Window ──────────────────
if ($action === 'meeting_presence_leave') {
    $sessionCode = trim($_POST['session_code'] ?? $input['session_code'] ?? '');
    $stNum = trim($_POST['student_number'] ?? $input['student_number'] ?? ($_SESSION['student_number'] ?? $studentNumber ?? ''));

    if (empty($sessionCode) || empty($stNum)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session code and student number required.']);
        exit;
    }

    $now = time();
    $presence = loadPresenceData($presenceFile);

    if (isset($presence[$sessionCode][$stNum])) {
        $entry = &$presence[$sessionCode][$stNum];
        if (!empty($entry['is_online'])) {
            $currentJoin = $entry['current_join_ts'] ?? $now;
            $elapsed = max(0, $now - $currentJoin);
            $entry['accumulated_seconds'] = ($entry['accumulated_seconds'] ?? 0) + $elapsed;
            $entry['is_online'] = false;
            $entry['last_left_at'] = date('c', $now);
            $entry['leave_count'] = ($entry['leave_count'] ?? 0) + 1;
            savePresenceData($presenceFile, $presence);
        }
    }

    $finalLeaveCount = isset($presence[$sessionCode][$stNum]) ? ($presence[$sessionCode][$stNum]['leave_count'] ?? 1) : 1;
    echo json_encode(['success' => true, 'leave_count' => $finalLeaveCount, 'message' => 'Left online class session recorded.']);
    exit;
}

// ─── 12D. GET: Real-Time Presence Roster (Sorted by Last Name A-Z) ────────────
if ($action === 'get_live_presence_roster') {
    $sessionCode = trim($_GET['session_code'] ?? '');
    $section = trim($_GET['section'] ?? '');

    if (empty($sessionCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session code is required.']);
        exit;
    }

    $now = time();
    $presence = loadPresenceData($presenceFile);
    $sessionPresence = $presence[$sessionCode] ?? [];

    // Run server watchdog: auto-pause students with missing heartbeats (> 20s)
    $dirty = false;
    foreach ($sessionPresence as $sn => &$p) {
        if (!empty($p['is_online'])) {
            $lastHb = $p['last_heartbeat'] ?? 0;
            if ($now - $lastHb > 20) {
                // Heartbeat dropped — mark offline, pause duration, increment leave count
                $currentJoin = $p['current_join_ts'] ?? $lastHb;
                $elapsed = max(0, $lastHb - $currentJoin);
                $p['accumulated_seconds'] = ($p['accumulated_seconds'] ?? 0) + $elapsed;
                $p['is_online'] = false;
                $p['last_left_at'] = date('c', $lastHb);
                $p['leave_count'] = ($p['leave_count'] ?? 0) + 1;
                $dirty = true;
            }
        }
    }
    unset($p);

    if ($dirty) {
        $presence[$sessionCode] = $sessionPresence;
        savePresenceData($presenceFile, $presence);
    }

    // Resolve enrolled students from users table where role = 'student' (cached 60s for speed)
    require_once __DIR__ . '/../includes/supabase_helper.php';
    $cacheFile = sys_get_temp_dir() . '/npc_students_cache.json';
    $allStudents = null;
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        $allStudents = @json_decode(file_get_contents($cacheFile), true);
    }
    if (!is_array($allStudents)) {
        $uQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=full_name,student_number,program,section");
        $allStudents = ($uQuery['status'] === 200 && is_array($uQuery['data'])) ? $uQuery['data'] : [];
        if (!empty($allStudents)) {
            @file_put_contents($cacheFile, json_encode($allStudents));
        }
    }

    // Filter students by section if specified
    $targetSec = strtoupper(trim($section));
    $enrolled = [];
    if (!empty($targetSec)) {
        foreach ($allStudents as $s) {
            $sSec = strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? '')));
            if ($sSec === $targetSec || strpos($sSec, $targetSec) !== false || (strpos($targetSec, strtoupper(trim($s['section'] ?? ''))) !== false && trim($s['section'] ?? '') !== '')) {
                $enrolled[] = $s;
            }
        }
    }
    if (empty($enrolled)) {
        $enrolled = $allStudents;
    }

    // Also fetch official Supabase records for this session (cached 6s to prevent repeated network delays during rapid poll)
    $recCacheFile = sys_get_temp_dir() . '/npc_att_' . md5($sessionCode) . '.json';
    $officialRecords = null;
    if (file_exists($recCacheFile) && (time() - filemtime($recCacheFile)) < 6) {
        $officialRecords = @json_decode(file_get_contents($recCacheFile), true);
    }
    if (!is_array($officialRecords)) {
        $rQuery = supabaseServiceQuery("/rest/v1/attendance_records?session_code=eq." . rawurlencode($sessionCode) . "&select=*");
        $officialRecords = ($rQuery['status'] === 200 && is_array($rQuery['data'])) ? $rQuery['data'] : [];
        if (!empty($officialRecords)) {
            @file_put_contents($recCacheFile, json_encode($officialRecords));
        }
    }
    $recordsByStudent = [];
    foreach ($officialRecords as $rec) {
        $recordsByStudent[$rec['student_number']] = $rec;
    }

    // Build unified roster
    $roster = [];
    $processedNumbers = [];

    // 1. Process enrolled students
    foreach ($enrolled as $st) {
        $sNum = $st['student_number'] ?? '';
        if (empty($sNum)) continue;
        $processedNumbers[$sNum] = true;

        $p = $sessionPresence[$sNum] ?? null;
        $rec = $recordsByStudent[$sNum] ?? null;
        $nameParsed = parseCollegiateName($st['full_name'] ?? '');

        $isOnline = false;
        $duration = 0;
        $leaveCount = 0;
        $status = 'absent';
        $checkInAt = null;
        $verifiedVia = 'portal';

        if ($p) {
            $isOnline = !empty($p['is_online']);
            $leaveCount = intval($p['leave_count'] ?? 0);
            $accum = intval($p['accumulated_seconds'] ?? 0);
            $joinTs = intval($p['current_join_ts'] ?? $now);
            $duration = $isOnline ? ($accum + max(0, $now - $joinTs)) : $accum;
            $checkInAt = $p['check_in_at'] ?? null;
            $rawVia = $p['verified_via'] ?? 'plugnmeet_verified';
            $verifiedVia = ($rawVia === 'google_meet_verified') ? 'plugnmeet_verified' : $rawVia;
        } elseif ($rec) {
            $status = $rec['status'] ?? 'present';
            $checkInAt = $rec['check_in_at'] ?? null;
            $rawVia = $rec['verified_via'] ?? $rec['method'] ?? 'plugnmeet_verified';
            $verifiedVia = ($rawVia === 'google_meet_verified') ? 'plugnmeet_verified' : $rawVia;
        }

        $roster[] = [
            'student_number'   => $sNum,
            'full_name'        => $st['full_name'] ?? 'Student',
            'last_name'        => $nameParsed['last_name'],
            'first_name'       => $nameParsed['first_name'],
            'formatted_name'   => $nameParsed['formatted'],
            'section'          => ($st['program'] ?? '') . ' ' . ($st['section'] ?? ''),
            'is_online'        => $isOnline,
            'duration_seconds' => $duration,
            'leave_count'      => $leaveCount,
            'status'           => $status,
            'check_in_at'      => $checkInAt,
            'verified_via'     => $verifiedVia,
            'last_left_at'     => $p['last_left_at'] ?? null
        ];
    }

    // 2. Include any students present in session who might not be in enrolled list
    foreach ($sessionPresence as $sNum => $p) {
        if (!isset($processedNumbers[$sNum])) {
            $processedNumbers[$sNum] = true;
            $nameParsed = parseCollegiateName($p['student_name'] ?? 'Student');
            $isOnline = !empty($p['is_online']);
            $leaveCount = intval($p['leave_count'] ?? 0);
            $accum = intval($p['accumulated_seconds'] ?? 0);
            $joinTs = intval($p['current_join_ts'] ?? $now);
            $duration = $isOnline ? ($accum + max(0, $now - $joinTs)) : $accum;

            $roster[] = [
                'student_number'   => $sNum,
                'full_name'        => $p['student_name'] ?? 'Student',
                'last_name'        => $nameParsed['last_name'],
                'first_name'       => $nameParsed['first_name'],
                'formatted_name'   => $nameParsed['formatted'],
                'section'          => $targetSec ?: '2A',
                'is_online'        => $isOnline,
                'duration_seconds' => $duration,
                'leave_count'      => $leaveCount,
                'status'           => $p['status'] ?? 'present',
                'check_in_at'      => $p['check_in_at'] ?? null,
                'verified_via'     => (($p['verified_via'] ?? '') === 'google_meet_verified') ? 'plugnmeet_verified' : ($p['verified_via'] ?? 'plugnmeet_verified'),
                'last_left_at'     => $p['last_left_at'] ?? null
            ];
        }
    }

    // ─── STRICT COLLEGIATE LAST NAME SORTING (A to Z) ───────────────────────
    usort($roster, function ($a, $b) {
        $lastCmp = strcasecmp($a['last_name'] ?? '', $b['last_name'] ?? '');
        if ($lastCmp !== 0) return $lastCmp;
        return strcasecmp($a['first_name'] ?? '', $b['first_name'] ?? '');
    });

    // Compute metrics
    $totalEnrolled = count($roster);
    $onlineCount = 0;
    $leftCount = 0;
    $presentCount = 0;
    $lateCount = 0;

    foreach ($roster as $r) {
        if (!empty($r['is_online'])) {
            $onlineCount++;
        } elseif ($r['leave_count'] > 0 || ($r['status'] !== 'absent' && $r['check_in_at'])) {
            $leftCount++;
        }
        if ($r['status'] === 'present') $presentCount++;
        elseif ($r['status'] === 'late') $lateCount++;
    }

    echo json_encode([
        'success'          => true,
        'session_code'     => $sessionCode,
        'total_enrolled'   => $totalEnrolled,
        'online_count'     => $onlineCount,
        'left_count'       => $leftCount,
        'present_count'    => $presentCount,
        'late_count'       => $lateCount,
        'server_timestamp' => $now,
        'roster'           => $roster
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid or unknown ELMS action requested.']);

