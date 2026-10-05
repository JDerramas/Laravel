<?php
/**
 * api_admin.php — Server-Side API for Admin & Registrar Operations
 * 
 * Handles:
 *  - Dashboard metric aggregations
 *  - Academic calendar (School Years, Semesters, Programs, Sections, Subjects)
 *  - Document requests approval & status updates
 *  - Profile update request verification
 *  - Role management
 *  - Audit log queries & filters
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/supabase_helper.php';
require_once __DIR__ . '/../includes/ai_moderation.php';

require_admin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$currentUserEmail = strtolower($_SESSION['email'] ?? '');
$currentUserName = $_SESSION['name'] ?? 'Administrator';

// ─── 1. GET: Admin Dashboard Metrics ───────────────────────────────────────────
if ($method === 'GET' && $action === 'get_dashboard_metrics') {
    // 1. Total Students
    $sQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=id");
    $totalStudents = ($sQuery['status'] === 200 && is_array($sQuery['data'])) ? count($sQuery['data']) : 0;

    // 2. Total Faculty
    $fQuery = supabaseServiceQuery("/rest/v1/users?or=(role.eq.teacher,role.eq.faculty)&select=id");
    $totalFaculty = ($fQuery['status'] === 200 && is_array($fQuery['data'])) ? count($fQuery['data']) : 0;

    // 3. Active Classes
    $cQuery = supabaseServiceQuery("/rest/v1/classes?select=id");
    $totalClasses = ($cQuery['status'] === 200 && is_array($cQuery['data'])) ? count($cQuery['data']) : 0;

    // 4. Pending Document Requests
    $dQuery = supabaseServiceQuery("/rest/v1/document_requests?status=eq.Pending&select=id");
    $pendingDocs = ($dQuery['status'] === 200 && is_array($dQuery['data'])) ? count($dQuery['data']) : 0;

    // 5. Pending Grade Approvals
    $gQuery = supabaseServiceQuery("/rest/v1/grade_submissions?status=eq.Submitted&select=id");
    $pendingGrades = ($gQuery['status'] === 200 && is_array($gQuery['data'])) ? count($gQuery['data']) : 0;

    // 6. Pending Grade Change Requests
    $gcrQuery = supabaseServiceQuery("/rest/v1/grade_change_requests?status=eq.Pending&select=id");
    $pendingGradeChanges = ($gcrQuery['status'] === 200 && is_array($gcrQuery['data'])) ? count($gcrQuery['data']) : 0;

    // 7. Recent Audit Logs
    $aQuery = supabaseServiceQuery("/rest/v1/security_logs?order=created_at.desc&limit=8");
    $recentLogs = ($aQuery['status'] === 200 && is_array($aQuery['data'])) ? $aQuery['data'] : [];

    // 8. Attendance Today (records checked in since local midnight)
    $todayStart = date('Y-m-d') . 'T00:00:00';
    $attToday = supabaseServiceQuery("/rest/v1/attendance_records?check_in_at=gte." . rawurlencode($todayStart) . "&select=id,status");
    $attRows = ($attToday['status'] === 200 && is_array($attToday['data'])) ? $attToday['data'] : [];
    $attSummary = ['total' => count($attRows), 'present' => 0, 'late' => 0, 'absent' => 0];
    foreach ($attRows as $ar) {
        $st = strtolower($ar['status'] ?? '');
        if (isset($attSummary[$st])) $attSummary[$st]++;
    }

    echo json_encode([
        'success' => true,
        'metrics' => [
            'total_students' => $totalStudents,
            'total_faculty' => $totalFaculty,
            'active_classes' => $totalClasses,
            'pending_docs' => $pendingDocs,
            'pending_grades' => $pendingGrades,
            'pending_grade_changes' => $pendingGradeChanges,
            'attendance_today' => $attSummary
        ],
        'recent_logs' => $recentLogs
    ]);
    exit;
}

// ─── 2. GET: List All Document Requests (with filter) ───────────────────────────
if ($method === 'GET' && $action === 'get_all_document_requests') {
    $statusFilter = trim($_GET['status'] ?? '');
    $endpoint = "/rest/v1/document_requests?order=requested_at.desc";
    if (!empty($statusFilter)) {
        $endpoint = "/rest/v1/document_requests?status=eq.$statusFilter&order=requested_at.desc";
    }

    $dQuery = supabaseServiceQuery($endpoint);
    $requests = ($dQuery['status'] === 200 && is_array($dQuery['data'])) ? $dQuery['data'] : [];

    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

// ─── 3. POST: Process Document Request (Status Update) ─────────────────────────
if ($method === 'POST' && $action === 'process_document_request') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $status = trim($input['status'] ?? ''); // 'Processing', 'Ready for Pickup', 'Released', 'Rejected'
    $remarks = trim($input['remarks'] ?? '');

    if (empty($id) || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID and new status are required.']);
        exit;
    }

    // Fetch request to get student details
    $rQuery = supabaseServiceQuery("/rest/v1/document_requests?id=eq.$id&limit=1");
    if ($rQuery['status'] === 200 && !empty($rQuery['data'])) {
        $req = $rQuery['data'][0];

        supabaseServiceQuery(
            "/rest/v1/document_requests?id=eq.$id",
            'PATCH',
            [
                'status' => $status,
                'remarks' => $remarks,
                'processed_by' => $currentUserEmail,
                'updated_at' => date('c')
            ]
        );

        // Notify student
        supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
            'user_email' => $req['student_email'],
            'title' => "Document Request Status: $status",
            'message' => "Your request for {$req['document_type']} (Ref: {$req['reference_no']}) has been updated to '$status'." . (!empty($remarks) ? " Note: $remarks" : ''),
            'type' => 'document',
            'link_url' => 'academic.php'
        ]]);

        logSecurityEvent("DOC_PROCESSED: {$req['reference_no']} marked as $status by $currentUserEmail", $currentUserEmail, 'Low');
    }

    echo json_encode(['success' => true, 'message' => "Document request updated to $status."]);
    exit;
}

// ─── 4. GET: List All Grade Submissions & Approvals Queue ──────────────────────
if ($method === 'GET' && $action === 'get_grade_approval_queue') {
    $sQuery = supabaseServiceQuery("/rest/v1/grade_submissions?order=submitted_at.desc");
    $submissions = ($sQuery['status'] === 200 && is_array($sQuery['data'])) ? $sQuery['data'] : [];

    // Enrich submissions with subject title, section, year level, instructor name and email from classes table
    $cQuery = supabaseServiceQuery("/rest/v1/classes?select=id,code,title,section,instructor,instructor_email,year_level");
    $classesById = [];
    if ($cQuery['status'] === 200 && is_array($cQuery['data'])) {
        foreach ($cQuery['data'] as $c) {
            $classesById[$c['id']] = $c;
        }
    }

    foreach ($submissions as &$sub) {
        $c = $classesById[$sub['class_id']] ?? null;
        if ($c) {
            $sub['subject_code'] = !empty($c['code']) ? $c['code'] : ($sub['class_code'] ?? '');
            $sub['subject_title'] = !empty($c['title']) ? $c['title'] : ($c['name'] ?? '');
            $sub['section'] = !empty($c['section']) ? $c['section'] : ($sub['section'] ?? '');
            $sub['year_level'] = $c['year_level'] ?? '';
            $sub['instructor_name'] = !empty($c['instructor']) ? $c['instructor'] : ($sub['faculty_name'] ?? '');
            $sub['instructor_email'] = !empty($c['instructor_email']) ? $c['instructor_email'] : ($sub['faculty_email'] ?? '');
        } else {
            $sub['subject_code'] = $sub['class_code'] ?? '';
            $sub['subject_title'] = $sub['subject_title'] ?? '';
            $sub['instructor_name'] = $sub['faculty_name'] ?? '';
            $sub['instructor_email'] = $sub['faculty_email'] ?? '';
        }
    }
    unset($sub);

    // Also fetch pending grade change requests
    $gcrQuery = supabaseServiceQuery("/rest/v1/grade_change_requests?order=requested_at.desc");
    $changeRequests = ($gcrQuery['status'] === 200 && is_array($gcrQuery['data'])) ? $gcrQuery['data'] : [];

    echo json_encode([
        'success' => true,
        'submissions' => $submissions,
        'change_requests' => $changeRequests
    ]);
    exit;
}

// ─── 5. POST: Update User Role (Role Management) ───────────────────────────────
if ($method === 'POST' && $action === 'update_user_role') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $targetEmail = strtolower(trim($input['email'] ?? ''));
    $newRole = trim($input['role'] ?? '');

    $allowedRoles = ['student', 'teacher', 'admin', 'registrar'];

    if (empty($targetEmail) || !in_array($newRole, $allowedRoles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid email and role required.']);
        exit;
    }

    // Update in users table
    $upRes = supabaseServiceQuery(
        "/rest/v1/users?email=eq." . urlencode($targetEmail),
        'PATCH',
        ['role' => $newRole]
    );

    logSecurityEvent("ROLE_CHANGED: $targetEmail updated to $newRole by $currentUserEmail", $currentUserEmail, 'High');

    echo json_encode(['success' => true, 'message' => "User role for $targetEmail updated to $newRole."]);
    exit;
}

// ─── 6. GET: Security & System Audit Logs ──────────────────────────────────────
if ($method === 'GET' && $action === 'get_audit_logs') {
    $limit = intval($_GET['limit'] ?? 50);
    $severity = trim($_GET['severity'] ?? '');

    $endpoint = "/rest/v1/security_logs?order=created_at.desc&limit=$limit";
    if (!empty($severity)) {
        $endpoint = "/rest/v1/security_logs?severity=eq.$severity&order=created_at.desc&limit=$limit";
    }

    $lQuery = supabaseServiceQuery($endpoint);
    $logs = ($lQuery['status'] === 200 && is_array($lQuery['data'])) ? $lQuery['data'] : [];

    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

// ─── 7. POST: Create or update a class/schedule offering ───────────────────────
if ($method === 'POST' && $action === 'save_class') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $record = [
        'code' => substr(trim($input['code'] ?? ''), 0, 32),
        'title' => substr(trim($input['title'] ?? ''), 0, 160),
        'section' => substr(trim($input['section'] ?? ''), 0, 64),
        'schedule_day' => substr(trim($input['schedule_day'] ?? 'TBA'), 0, 24),
        'start_time' => substr(trim($input['start_time'] ?? 'TBA'), 0, 16),
        'end_time' => substr(trim($input['end_time'] ?? 'TBA'), 0, 16),
        'instructor' => substr(trim($input['instructor'] ?? 'TBA'), 0, 120),
        'room' => substr(trim($input['room'] ?? 'Room TBA'), 0, 64),
        'units' => max(0, min(12, floatval($input['units'] ?? 3.0)))
    ];

    if ($record['code'] === '' || $record['title'] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Subject code and title are required.']);
        exit;
    }

    $id = trim($input['id'] ?? '');
    if ($id !== '') {
        // Preserve original creator metadata on updates
        $result = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($id), 'PATCH', $record);
    } else {
        $email = trim($input['created_by_email'] ?? '') ?: 'admin@navotaspolytechniccollege.edu.ph';
        $name = trim($input['created_by_name'] ?? '') ?: $currentUserName;
        $record['created_by_email'] = filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : 'admin@navotaspolytechniccollege.edu.ph';
        $record['created_by_name'] = substr($name, 0, 120);
        $result = supabaseServiceQuery("/rest/v1/classes", 'POST', [$record]);
    }

    if ($result['status'] < 200 || $result['status'] >= 300) {
        echo json_encode(['success' => false, 'message' => 'Database rejected the change.', 'detail' => $result['data']]);
        exit;
    }

    logSecurityEvent("CLASS_SAVED: {$record['code']} {$record['section']} by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
    exit;
}

// ─── 8. POST: Delete a class/schedule offering ─────────────────────────────────
if ($method === 'POST' && $action === 'delete_class') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Class ID required.']);
        exit;
    }
    supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($id), 'DELETE');
    logSecurityEvent("CLASS_DELETED: $id by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode(['success' => true, 'message' => 'Deleted.']);
    exit;
}

// ─── 8.1. Program / Academic Degree Management Endpoints ──────────────────────
if ($method === 'GET' && $action === 'list_programs') {
    $db = getDB();
    try {
        $stmt = $db->query("
            SELECT p.*, 
                   COUNT(DISTINCT s.id) as student_count,
                   COUNT(DISTINCT c.id) as class_count
            FROM programs p
            LEFT JOIN students s ON s.program = p.code
            LEFT JOIN classes c ON c.section LIKE CONCAT(p.code, '%')
            GROUP BY p.id
            ORDER BY p.code ASC
        ");
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'programs' => $programs]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'create_program') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $code = strtoupper(substr(trim(strip_tags($input['code'] ?? '')), 0, 32));
    $name = substr(trim(strip_tags($input['name'] ?? '')), 0, 191);
    $dept = substr(trim(strip_tags($input['department'] ?? 'College of Computer Studies')), 0, 191);

    if (empty($code) || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Program Code and Program Name are required.']);
        exit;
    }

    $db = getDB();
    $chk = $db->prepare("SELECT id FROM programs WHERE code = ? LIMIT 1");
    $chk->execute([$code]);
    if ($chk->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "A program with code '$code' already exists."]);
        exit;
    }

    $id = 'prog-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code)) . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
    $ins = $db->prepare("INSERT INTO programs (id, code, name, department) VALUES (?, ?, ?, ?)");
    $ins->execute([$id, $code, $name, $dept]);

    logSecurityEvent("PROGRAM_CREATED: $code ($name) by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => "Program '$code - $name' added successfully."]);
    exit;
}

if ($method === 'POST' && $action === 'update_program') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $code = strtoupper(substr(trim(strip_tags($input['code'] ?? '')), 0, 32));
    $name = substr(trim(strip_tags($input['name'] ?? '')), 0, 191);
    $dept = substr(trim(strip_tags($input['department'] ?? '')), 0, 191);
    $oldCode = strtoupper(trim($input['old_code'] ?? ''));

    if (empty($id) || empty($code) || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Program ID, code, and name are required.']);
        exit;
    }

    $db = getDB();
    // Check if new code conflicts with another program
    $chk = $db->prepare("SELECT id FROM programs WHERE code = ? AND id != ? LIMIT 1");
    $chk->execute([$code, $id]);
    if ($chk->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Another program with code '$code' already exists."]);
        exit;
    }

    $up = $db->prepare("UPDATE programs SET code = ?, name = ?, department = ? WHERE id = ?");
    $up->execute([$code, $name, $dept, $id]);

    // If code changed, cascade update to students and users tables
    if (!empty($oldCode) && $oldCode !== $code) {
        $db->prepare("UPDATE students SET program = ? WHERE program = ?")->execute([$code, $oldCode]);
        $db->prepare("UPDATE users SET program = ? WHERE program = ?")->execute([$code, $oldCode]);
    }

    logSecurityEvent("PROGRAM_UPDATED: $code ($name) by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => "Program '$code' updated successfully."]);
    exit;
}

if ($method === 'POST' && $action === 'delete_program') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $code = trim($input['code'] ?? '');

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Program ID is required.']);
        exit;
    }

    $db = getDB();
    // Check if students are currently enrolled in this program
    if (!empty($code)) {
        $stdChk = $db->prepare("SELECT COUNT(*) FROM students WHERE program = ?");
        $stdChk->execute([$code]);
        $count = (int)$stdChk->fetchColumn();
        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Cannot delete program '$code' because $count student(s) are currently enrolled in it."]);
            exit;
        }
    }

    $del = $db->prepare("DELETE FROM programs WHERE id = ?");
    $del->execute([$id]);

    logSecurityEvent("PROGRAM_DELETED: $code ($id) by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => "Program '$code' deleted successfully."]);
    exit;
}

// ─── 9. POST: Create a single student record ───────────────────────────────────
if ($method === 'POST' && $action === 'create_student') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $name = substr(trim($input['full_name'] ?? ''), 0, 120);
    $email = strtolower(trim($input['email'] ?? ''));
    $number = substr(trim($input['student_number'] ?? 'N/A'), 0, 24);
    $program = substr(trim($input['program'] ?? 'BSIS'), 0, 32);
    $section = substr(trim($input['section'] ?? '1A'), 0, 16);

    $scholar = trim($input['scholar_status'] ?? 'Non-Scholar');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid email address is required.']);
        exit;
    }

    if ($name === '' || str_starts_with($name, 'Pending')) {
        $name = resolveSmartFullName($email, '', 'student');
    }

    if ($number === '' || $number === 'N/A' || $number === 'AUTO') {
        $prefix = explode('@', $email)[0];
        if (preg_match('/\d{4,}/', $prefix, $m)) {
            $number = $m[0];
        } else {
            $number = date('Y') . '-' . str_pad((string)mt_rand(100, 99999), 5, '0', STR_PAD_LEFT);
        }
    }

    $res = supabaseServiceQuery("/rest/v1/users", 'POST', [[
        'full_name' => $name,
        'email' => $email,
        'student_number' => $number,
        'program' => $program,
        'section' => $section,
        'role' => 'student',
        'status' => 'Active',
        'is_active' => 1,
        'password_hash' => 'oauth'
    ]]);

    if ($res['status'] < 200 || $res['status'] >= 300) {
        echo json_encode(['success' => false, 'message' => 'Insert failed (email may already exist).']);
        exit;
    }

    // Also register in students table with scholarship status
    try {
        $db = getDB();
        $uQuery = supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($email) . "&select=id&limit=1");
        $newUid = (!empty($uQuery['data'][0]['id'])) ? $uQuery['data'][0]['id'] : null;
        $st = $db->prepare("INSERT INTO students (user_id, email, full_name, student_number, program, section, scholar_status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), student_number=VALUES(student_number), program=VALUES(program), section=VALUES(section), scholar_status=VALUES(scholar_status)");
        $st->execute([$newUid, $email, $name, $number, $program, $section, $scholar]);
    } catch (\Throwable $e) {
        error_log("Failed to insert into students table: " . $e->getMessage());
    }

    logSecurityEvent("STUDENT_CREATED: $email ($program-$section, $scholar) by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => "Student $name added."]);
    exit;
}

// ─── 9b. POST: Update an existing student record ──────────────────────────────
if ($method === 'POST' && $action === 'update_student') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $name = substr(trim($input['full_name'] ?? ''), 0, 120);
    $number = substr(trim($input['student_number'] ?? 'N/A'), 0, 32);
    $program = substr(trim($input['program'] ?? 'BSIS'), 0, 32);
    $section = substr(trim($input['section'] ?? '1A'), 0, 16);
    $scholar = trim($input['scholar_status'] ?? 'Non-Scholar');

    if (empty($id) || $name === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID and full name are required.']);
        exit;
    }

    $uQuery = supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($id) . "&select=id,email,role&limit=1");
    $user = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    // 1. Update users table
    $patchData = [
        'full_name' => $name,
        'student_number' => $number,
        'program' => $program,
        'section' => $section
    ];
    supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($id), 'PATCH', $patchData);

    // 2. Update students table (including scholar_status)
    try {
        $db = getDB();
        $userEmail = strtolower($user['email']);
        $chk = $db->prepare("SELECT id FROM students WHERE user_id = ? OR email = ? LIMIT 1");
        $chk->execute([$id, $userEmail]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $upSt = $db->prepare("UPDATE students SET user_id = ?, email = ?, full_name = ?, student_number = ?, program = ?, section = ?, scholar_status = ? WHERE id = ?");
            $upSt->execute([$id, $userEmail, $name, $number, $program, $section, $scholar, $existing['id']]);
        } else {
            $insSt = $db->prepare("INSERT INTO students (user_id, email, full_name, student_number, program, section, scholar_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insSt->execute([$id, $userEmail, $name, $number, $program, $section, $scholar]);
        }
    } catch (\Throwable $e) {
        error_log("Failed to update student in students table: " . $e->getMessage());
    }

    logSecurityEvent("STUDENT_UPDATED: {$user['email']} ($name, $program-$section, $scholar) by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => "Student record for $name updated successfully."]);
    exit;
}

// ─── 10. POST: Delete a student record ─────────────────────────────────────────
if ($method === 'POST' && $action === 'delete_student') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID required.']);
        exit;
    }

    // Guard: only student-role rows may be removed here
    $uQuery = supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($id) . "&select=id,email,role&limit=1");
    $user = ($uQuery['status'] === 200 && !empty($uQuery['data'])) ? $uQuery['data'][0] : null;
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    if (($user['role'] ?? '') !== 'student') {
        logSecurityEvent("ACCESS_DENIED: Attempt to delete non-student account {$user['email']} by $currentUserEmail", $currentUserEmail, 'High');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only student accounts can be deleted here. Faculty/admin accounts require Supabase Auth removal.']);
        exit;
    }

    supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($id), 'DELETE');
    logSecurityEvent("STUDENT_DELETED: {$user['email']} by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode(['success' => true, 'message' => 'Student removed.']);
    exit;
}

// ─── 11. POST: Publish/save an announcement ────────────────────────────────────
if ($method === 'POST' && $action === 'save_announcement') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $title = substr(trim(strip_tags($input['title'] ?? '')), 0, 200);
    $body = trim($input['body'] ?? ''); // Rich HTML body — sanitized on output client-side
    $category = in_array($input['category'] ?? '', ['news', 'academic', 'emergency']) ? $input['category'] : 'news';
    $status = in_array($input['status'] ?? '', ['published', 'draft']) ? $input['status'] : 'draft';
    $department = substr(trim($input['department'] ?? 'all'), 0, 64);
    $priority = in_array($input['priority'] ?? '', ['Normal', 'Important', 'Urgent']) ? $input['priority'] : 'Normal';
    $isPinned = !empty($input['is_pinned']);
    $targetAudience = in_array($input['target_audience'] ?? '', ['all', 'students', 'faculty', 'program', 'section']) ? $input['target_audience'] : 'all';
    $targetProgram = substr(trim($input['target_program'] ?? ''), 0, 32);
    $targetSection = substr(trim($input['target_section'] ?? ''), 0, 32);
    $scheduledAt = !empty($input['scheduled_at']) ? date('c', strtotime($input['scheduled_at'])) : null;
    $expiresAt = !empty($input['expires_at']) ? date('c', strtotime($input['expires_at'])) : null;

    if ($title === '' || $body === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title and body content are required.']);
        exit;
    }

    $payload = [
        'title'           => $title,
        'body'            => $body,
        'category'        => $category,
        'status'          => $status,
        'department'      => $department,
        'priority'        => $priority,
        'is_pinned'       => $isPinned,
        'target_audience' => $targetAudience,
        'target_program'  => $targetProgram ?: null,
        'target_section'  => $targetSection ?: null,
        'scheduled_at'    => $scheduledAt,
        'expires_at'      => $expiresAt,
        'audience'        => ($targetAudience === 'students') ? ['students'] : (($targetAudience === 'faculty') ? ['faculty'] : ['students', 'faculty'])
    ];

    $res = supabaseServiceQuery("/rest/v1/announcements", 'POST', [$payload]);

    if ($res['status'] < 200 || $res['status'] >= 300) {
        echo json_encode(['success' => false, 'message' => 'Database rejected the announcement.']);
        exit;
    }

    logSecurityEvent("ANNOUNCEMENT_SAVED: '$title' ($status, $priority) by $currentUserEmail", $currentUserEmail, 'Low');
    echo json_encode(['success' => true, 'message' => $status === 'published' ? 'Announcement published!' : 'Draft saved!']);
    exit;
}

// ─── 12. POST: Delete an announcement ──────────────────────────────────────────
if ($method === 'POST' && $action === 'delete_announcement') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Announcement ID required.']);
        exit;
    }
    supabaseServiceQuery("/rest/v1/announcements?id=eq." . rawurlencode($id), 'DELETE');
    logSecurityEvent("ANNOUNCEMENT_DELETED: $id by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => 'Announcement deleted.']);
    exit;
}

// ─── 13. POST: Delete attendance records (dedupe / clear session) ──────────────
// Accepts either explicit ids[] (max 500) or {scope:"all"} to wipe every record.
if ($method === 'POST' && $action === 'delete_attendance_records') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $ids = array_slice(array_filter(array_map('trim', (array)($input['ids'] ?? []))), 0, 500);
    $wipeAll = ($input['scope'] ?? '') === 'all';

    if (!$wipeAll && empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Provide record ids[] or scope=all.']);
        exit;
    }

    foreach (array_chunk($ids, 100) as $chunk) {
        $in = implode(',', array_map('rawurlencode', $chunk));
        supabaseServiceQuery("/rest/v1/attendance_records?id=in.($in)", 'DELETE');
    }
    if ($wipeAll) {
        supabaseServiceQuery("/rest/v1/attendance_records?id=neq.00000000-0000-0000-0000-000000000000", 'DELETE');
    }

    logSecurityEvent(
        $wipeAll ? "ATTENDANCE_WIPED: all records cleared by $currentUserEmail" : "ATTENDANCE_DEDUPE: " . count($ids) . " duplicate records removed by $currentUserEmail",
        $currentUserEmail,
        'High'
    );
    echo json_encode(['success' => true, 'deleted' => $wipeAll ? 'all' : count($ids)]);
    exit;
}

// ─── 13c. GET: Paginated audit logs with SERVER-SIDE filters ───────────────────
if ($method === 'GET' && $action === 'get_audit_logs') {
    $limit  = min(500, max(1, (int)($_GET['limit'] ?? 500)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $severity = trim($_GET['severity'] ?? '');
    $userF    = trim($_GET['user'] ?? '');
    $actionF  = trim($_GET['action'] ?? '');
    $dateFrom = trim($_GET['date_from'] ?? '');
    $dateTo   = trim($_GET['date_to'] ?? '');

    // Validate date format (YYYY-MM-DD) before touching the query
    foreach ([$dateFrom, $dateTo] as $d) {
        if ($d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid date filter format.']);
            exit;
        }
    }

    $params = [];
    if ($severity !== '')   $params[] = 'severity=eq.' . rawurlencode($severity);
    if ($userF !== '')      $params[] = 'user_email=ilike.' . rawurlencode('*' . $userF . '*');
    if ($actionF !== '')    $params[] = 'event=ilike.' . rawurlencode('*' . $actionF . '*');
    if ($dateFrom !== '')   $params[] = 'created_at=gte.' . rawurlencode($dateFrom . 'T00:00:00Z');
    if ($dateTo !== '')     $params[] = 'created_at=lte.' . rawurlencode($dateTo . 'T23:59:59Z');

    $endpoint = "/rest/v1/security_logs?select=id,event,user_email,ip_address,severity,created_at"
              . "&order=created_at.desc&limit={$limit}&offset={$offset}";
    if (!empty($params)) {
        $endpoint .= '&' . implode('&', $params);
    }
    $res = supabaseServiceQuery($endpoint);
    $logs = ($res['status'] === 200 && is_array($res['data'])) ? $res['data'] : [];
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

// ─── 14. GET: List all user accounts (students, faculty, admins) ───────────────
if ($method === 'GET' && $action === 'list_users') {
    $roleFilter = trim($_GET['role'] ?? '');
    $endpoint = "/rest/v1/users?select=id,full_name,email,student_number,program,section,role,is_active,status,created_at&order=created_at.desc&limit=500";
    if (in_array($roleFilter, ['student', 'teacher', 'admin'])) {
        $endpoint = "/rest/v1/users?role=eq.$roleFilter&select=id,full_name,email,student_number,program,section,role,is_active,status,created_at&order=created_at.desc&limit=500";
    }
    $res = supabaseServiceQuery($endpoint);
    $users = ($res['status'] === 200 && is_array($res['data'])) ? $res['data'] : [];

    // Enrich student accounts with scholar status from students table
    if (!empty($users)) {
        try {
            $db = getDB();
            $sStmt = $db->query("SELECT LOWER(email) as email, scholar_status FROM students WHERE scholar_status IS NOT NULL AND scholar_status != ''");
            $scholarMap = [];
            while ($sRow = $sStmt->fetch(PDO::FETCH_ASSOC)) {
                $scholarMap[$sRow['email']] = $sRow['scholar_status'];
            }
            foreach ($users as &$u) {
                $em = strtolower($u['email'] ?? '');
                $u['scholar_status'] = $scholarMap[$em] ?? 'Non-Scholar';
            }
            unset($u);
        } catch (\Throwable $e) {}
    }

    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

// ─── 15. POST: Create a new admin / teacher / student account ──────────────────
// Admin & teacher accounts are provisioned in Supabase Auth by IT via invite;
// the users-table row created here gives them portal access the moment they
// sign in with that NPC Gmail (set_session.php trusts this table's role).
if ($method === 'POST' && $action === 'create_user') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $name  = substr(trim(strip_tags($input['full_name'] ?? '')), 0, 120);
    $email = strtolower(substr(trim($input['email'] ?? ''), 0, 160));
    $role  = trim($input['role'] ?? 'student');
    $number = strtoupper(substr(trim(strip_tags($input['number'] ?? '')), 0, 32));
    $program = substr(trim(strip_tags($input['program'] ?? '')), 0, 64);
    $section = substr(trim(strip_tags($input['section'] ?? '')), 0, 32);
    $scholarStatus = trim($input['scholar_status'] ?? 'Non-Scholar');
    $sendInvite = !empty($input['send_invite']);

    // ── Validation ──
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
        exit;
    }

    // Check if an existing student record already exists in the students table for this email
    $db = getDB();
    $existingStudent = null;
    try {
        $stChk = $db->prepare("SELECT * FROM students WHERE LOWER(email) = ? LIMIT 1");
        $stChk->execute([$email]);
        $existingStudent = $stChk->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    if (!empty($existingStudent['full_name']) && stripos($existingStudent['full_name'], 'Pending') === false) {
        // Inherit verified official name from Student Management Directory
        $name = trim($existingStudent['full_name']);
        if (empty($number) && !empty($existingStudent['student_number'])) {
            $number = $existingStudent['student_number'];
        }
        if (empty($program) && !empty($existingStudent['program'])) {
            $program = $existingStudent['program'];
        }
        if (empty($section) && !empty($existingStudent['section'])) {
            $section = $existingStudent['section'];
        }
        if (!empty($existingStudent['scholar_status'])) {
            $scholarStatus = $existingStudent['scholar_status'];
        }
    } elseif ($name === '' || str_starts_with($name, 'Pending')) {
        $name = resolveSmartFullName($email, '', $role);
    }

    // Auto-extract student number from email digits or generate formatted sequence
    if ($number === '' || $number === 'N/A') {
        $prefix = explode('@', $email)[0];
        if (preg_match('/\d{4,}/', $prefix, $m)) {
            $number = $m[0];
        } else {
            $number = ($role === 'student') ? date('Y') . '-' . str_pad((string)mt_rand(100, 99999), 5, '0', STR_PAD_LEFT) : 'STAFF';
        }
    }

    if (!in_array($role, ['student', 'teacher', 'admin'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
        exit;
    }

    // Duplicate check
    $safeEmail = rawurlencode($email);
    $dupe = supabaseServiceQuery("/rest/v1/users?email=eq.$safeEmail&select=id,email&limit=1");
    if ($dupe['status'] === 200 && !empty($dupe['data'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'An account with this email address already exists.']);
        exit;
    }

    $row = [
        'full_name'      => $name,
        'email'          => $email,
        'student_number' => $number,
        'role'           => $role,
        'password_hash'  => 'oauth',
        'status'         => 'Active',
        'is_active'      => 1
    ];
    if ($role === 'student') {
        $row['program'] = $program !== '' ? $program : 'BSIS';
        $row['section'] = $section !== '' ? $section : '1A';
    } else {
        // Department field doubles as program for employees
        $row['program'] = $program !== '' ? $program : 'Faculty';
    }

    // Optional: send Supabase Auth invite so the account exists for SSO
    $inviteNote = '';
    if ($sendInvite) {
        $inv = supabaseServiceQuery('/auth/v1/admin/generate_link', 'POST', [
            'type'          => 'signup',
            'email'         => $email,
            'options'       => ['redirect_to' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth_callback.php']
        ]);
        if ($inv['status'] >= 200 && $inv['status'] < 300) {
            $inviteNote = ' Invite email sent.';
        }
    }

    $res = supabaseServiceQuery("/rest/v1/users", 'POST', [ $row ]);
    if ($res['status'] < 200 || $res['status'] >= 300) {
        echo json_encode(['success' => false, 'message' => 'Database rejected the new account (email may already exist).']);
        exit;
    }

    // If this is a student, sync to students table with scholar_status
    if ($role === 'student') {
        try {
            $uRow = supabaseServiceQuery("/rest/v1/users?email=eq.$safeEmail&select=id&limit=1");
            $newUid = (!empty($uRow['data'][0]['id'])) ? $uRow['data'][0]['id'] : null;

            // Retain official name in students table if already present
            $stdName = (!empty($existingStudent['full_name']) && stripos($existingStudent['full_name'], 'Pending') === false)
                ? $existingStudent['full_name']
                : $name;

            $st = $db->prepare("INSERT INTO students (user_id, email, full_name, student_number, program, section, scholar_status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = IF(full_name != '' AND full_name NOT LIKE 'Pending%', full_name, VALUES(full_name)), student_number = VALUES(student_number), program = VALUES(program), section = VALUES(section), scholar_status = VALUES(scholar_status)");
            $st->execute([$newUid, $email, $stdName, $number, $row['program'], $row['section'], $scholarStatus]);
        } catch (\Throwable $e) {}
    }

    logSecurityEvent("USER_CREATED: $role account '$email' created by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => ucfirst($role) . " account for $name added." . $inviteNote]);
    exit;
}

// ─── 16. POST: Change a user's role (promote/demote) ───────────────────────────
if ($method === 'POST' && $action === 'set_user_role') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = trim($input['id'] ?? '');
    $newRole = trim($input['role'] ?? '');

    if (empty($id) || !in_array($newRole, ['student', 'teacher', 'admin'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID and a valid role are required.']);
        exit;
    }

    $db = getDB();
    $uStmt = $db->prepare("SELECT id, email, role FROM users WHERE id = ? OR LOWER(email) = ? LIMIT 1");
    $uStmt->execute([$id, strtolower($id)]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    try {
        $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $user['id']]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error updating role.']);
        exit;
    }

    try {
        supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($user['id']), 'PATCH', ['role' => $newRole]);
    } catch (\Throwable $e) {}

    // Check if the current logged-in user changed their own role
    $isSelf = (strcasecmp($user['email'] ?? '', $currentUserEmail) === 0 || ($_SESSION['user_id'] ?? '') === $user['id']);
    $redirectUrl = null;
    if ($isSelf) {
        $_SESSION['role'] = $newRole;
        $_SESSION['base_role'] = $newRole;
        $_SESSION['active_portal'] = ($newRole === 'teacher' ? 'faculty' : ($newRole === 'student' ? 'student' : 'admin'));
        $redirectUrl = ($newRole === 'teacher') ? '/teacher/index.php' : (($newRole === 'student') ? '/student/index.php' : '/admin/index.php');
    }

    logSecurityEvent("ROLE_CHANGED: {$user['email']} → $newRole by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode([
        'success' => true,
        'message' => "{$user['email']} role updated to $newRole.",
        'redirect' => $redirectUrl
    ]);
    exit;
}

// ─── 17. POST: Set User Account Status (Active, Restricted, Snoozed, Banned) ─────
if ($method === 'POST' && $action === 'set_user_status') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    $status = trim($input['status'] ?? 'Active');

    if (empty($id) || !in_array($status, ['Active', 'Restricted', 'Snoozed', 'Banned'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid User ID and status required.']);
        exit;
    }

    $db = getDB();
    $uStmt = $db->prepare("SELECT id, email, status, is_active FROM users WHERE id = ? OR LOWER(email) = ? LIMIT 1");
    $uStmt->execute([$id, strtolower($id)]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $isActive = ($status === 'Active') ? 1 : 0;
    try {
        $db->prepare("UPDATE users SET status = ?, is_active = ? WHERE id = ?")->execute([$status, $isActive, $user['id']]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error updating status.']);
        exit;
    }

    try {
        supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($user['id']), 'PATCH', [
            'status' => $status,
            'is_active' => $isActive
        ]);
    } catch (\Throwable $e) {}

    logSecurityEvent("USER_STATUS_CHANGED: {$user['email']} set to '$status' by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode(['success' => true, 'message' => "Status for {$user['email']} set to $status."]);
    exit;
}

// ─── 18. POST: Permanently Delete User Account ────────────────────────────────
if ($method === 'POST' && $action === 'delete_user') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required.']);
        exit;
    }

    $db = getDB();
    $user = null;

    // 1. Search MySQL users table by id or email
    try {
        $uStmt = $db->prepare("SELECT id, email, full_name, role FROM users WHERE id = ? OR LOWER(email) = ? LIMIT 1");
        $uStmt->execute([$id, strtolower($id)]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // 2. If not found in users, check students table by id, user_id, or email
    if (!$user) {
        try {
            $sStmt = $db->prepare("SELECT id, user_id, email, full_name FROM students WHERE id = ? OR user_id = ? OR LOWER(email) = ? LIMIT 1");
            $sStmt->execute([$id, $id, strtolower($id)]);
            $sRow = $sStmt->fetch(PDO::FETCH_ASSOC);
            if ($sRow) {
                if (!empty($sRow['user_id'])) {
                    $uStmt = $db->prepare("SELECT id, email, full_name, role FROM users WHERE id = ? LIMIT 1");
                    $uStmt->execute([$sRow['user_id']]);
                    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
                }
                if (!$user) {
                    $user = [
                        'id' => $sRow['user_id'] ?: ('student-' . $sRow['id']),
                        'email' => $sRow['email'],
                        'full_name' => $sRow['full_name'],
                        'role' => 'student'
                    ];
                }
            }
        } catch (\Throwable $e) {}
    }

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User account not found.']);
        exit;
    }

    if (strcasecmp($user['email'] ?? '', $currentUserEmail) === 0 || ($_SESSION['user_id'] ?? '') === $user['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own active administrator account.']);
        exit;
    }

    $userEmail = strtolower($user['email']);
    $userId = $user['id'];

    // Delete from users table
    try {
        $db->prepare("DELETE FROM users WHERE id = ? OR LOWER(email) = ?")->execute([$userId, $userEmail]);
    } catch (\Throwable $e) {
        error_log("[delete_user Error] Failed to delete from users: " . $e->getMessage());
    }

    // Delete from students table
    try {
        $db->prepare("DELETE FROM students WHERE user_id = ? OR id = ? OR LOWER(email) = ?")->execute([$userId, $id, $userEmail]);
    } catch (\Throwable $e) {}

    // Cascade delete attendance, grades, and enrollments
    try {
        $db->prepare("DELETE FROM attendance WHERE student_id = ? OR LOWER(student_email) = ?")->execute([$userId, $userEmail]);
    } catch (\Throwable $e) {}
    try {
        $db->prepare("DELETE FROM grades WHERE student_id = ? OR LOWER(student_email) = ?")->execute([$userId, $userEmail]);
    } catch (\Throwable $e) {}
    try {
        $db->prepare("DELETE FROM enrollments WHERE student_id = ? OR LOWER(student_email) = ?")->execute([$userId, $userEmail]);
    } catch (\Throwable $e) {}

    // Best-effort deletion in Supabase driver if configured
    try {
        supabaseServiceQuery("/rest/v1/users?id=eq." . rawurlencode($userId), 'DELETE');
        supabaseServiceQuery("/rest/v1/students?user_id=eq." . rawurlencode($userId), 'DELETE');
    } catch (\Throwable $e) {}

    logSecurityEvent("USER_DELETED: $userEmail permanently deleted by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode(['success' => true, 'message' => "Account for $userEmail was permanently deleted."]);
    exit;
}

// ─── 19. POST: Activate / Deactivate a user account ────────────────────────────
if ($method === 'POST' && $action === 'set_user_active') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $email = strtolower(trim($input['email'] ?? ''));
    $active = !empty($input['is_active']);

    if ($email === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User email is required.']);
        exit;
    }
    if (strtolower($email) === strtolower($currentUserEmail ?? '')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
        exit;
    }

    $statusVal = $active ? 'Active' : 'Restricted';
    $db = getDB();
    try {
        $db->prepare("UPDATE users SET is_active = ?, status = ? WHERE LOWER(email) = ?")->execute([$active ? 1 : 0, $statusVal, $email]);
    } catch (\Throwable $e) {
        error_log("[set_user_active Error] " . $e->getMessage());
    }

    try {
        supabaseServiceQuery("/rest/v1/users?email=eq." . rawurlencode($email), 'PATCH', ['is_active' => $active ? 1 : 0, 'status' => $statusVal]);
    } catch (\Throwable $e) {}

    logSecurityEvent("USER_" . ($active ? 'ACTIVATED' : 'DEACTIVATED') . ": $email by " . ($_SESSION['email'] ?? 'admin'), $_SESSION['email'] ?? '', 'High');
    echo json_encode(['success' => true, 'message' => $active ? 'Account reactivated.' : 'Account deactivated. Login is now blocked.']);
    exit;
}

// ─── 19. GET: Schedule conflict checker ────────────────────────────────────────
// Checks teacher / room / section / time-overlap conflicts.
// Pass candidate params (section,instructor,room,schedule_day,start_time,end_time)
// to test an UNSAVED offering; response then lists conflicts INVOLVING it only.
if ($method === 'GET' && $action === 'check_schedule_conflicts') {
    $excludeId = trim($_GET['exclude_id'] ?? '');
    $all = supabaseServiceQuery("/rest/v1/classes?select=id,code,title,section,schedule_day,start_time,end_time,instructor,instructor_email,room");
    $classes = ($all['status'] === 200 && is_array($all['data'])) ? $all['data'] : [];

    $candidate = null;
    $candSection = trim($_GET['section'] ?? '');
    $candStart = trim($_GET['start_time'] ?? '');
    $candEnd = trim($_GET['end_time'] ?? '');
    if ($candStart !== '' && $candEnd !== '') {
        $candidate = [
            'id' => '__candidate__',
            'code' => trim($_GET['code'] ?? 'NEW'),
            'title' => '',
            'section' => $candSection,
            'schedule_day' => trim($_GET['schedule_day'] ?? ''),
            'start_time' => $candStart,
            'end_time' => $candEnd,
            'instructor' => trim($_GET['instructor'] ?? ''),
            'room' => trim($_GET['room'] ?? '')
        ];
        $classes[] = $candidate;
    }

    $conflicts = findScheduleConflicts($classes, $excludeId);
    if ($candidate !== null) {
        // Only keep conflicts that involve the candidate
        $conflicts = array_values(array_filter($conflicts, fn($cf) =>
            str_contains($cf['a'], '__candidate__') || str_contains($cf['b'], '__candidate__')));
        // Replace placeholder label with readable name
        foreach ($conflicts as &$cf) {
            $cf['a'] = str_replace('__candidate__', $candidate['code'], $cf['a']);
            $cf['b'] = str_replace('__candidate__', $candidate['code'], $cf['b']);
        }
        unset($cf);
    }
    echo json_encode(['success' => true, 'conflicts' => array_values($conflicts)]);
    exit;
}

function findScheduleConflicts(array $classes, string $excludeId = ''): array {
    $dayMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 0];
    $expand = function ($daysStr) use ($dayMap): array {
        $out = [];
        foreach (preg_split('/[,&\/]+/', strtolower(trim((string)$daysStr))) as $part) {
            $part = trim($part);
            if ($part === '' || $part === 'tba') continue;
            $key = substr($part, 0, 3);
            if (isset($dayMap[$key])) $out[] = $dayMap[$key];
        }
        return $out;
    };
    $toMin = function ($t): int {
        $t = trim((string)$t);
        if ($t === '' || strtoupper($t) === 'TBA') return -1;
        if (!preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)?/i', $t, $m)) return -1;
        $h = (int)$m[1]; $min = (int)$m[2]; $ap = strtoupper($m[3] ?? '');
        if ($ap === 'PM' && $h < 12) $h += 12;
        if ($ap === 'AM' && $h === 12) $h = 0;
        return $h * 60 + $min;
    };

    // Normalize into comparable slots
    $slots = [];
    foreach ($classes as $c) {
        if (!empty($excludeId) && ($c['id'] ?? '') === $excludeId) continue;
        $start = $toMin($c['start_time'] ?? '');
        $end = $toMin($c['end_time'] ?? '');
        if ($start < 0 || $end <= $start) continue; // skip TBA/invalid
        $days = $expand($c['schedule_day'] ?? '');
        if (!$days) continue;
        $slots[] = [
            'id' => $c['id'], 'code' => $c['code'] ?? '?', 'section' => $c['section'] ?? '',
            'instructor' => $c['instructor'] ?? '', 'room' => strtolower(trim($c['room'] ?? '')),
            'days' => $days, 'start' => $start, 'end' => $end
        ];
    }

    $conflicts = [];
    $n = count($slots);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $a = $slots[$i]; $b = $slots[$j];
            if (!array_intersect($a['days'], $b['days'])) continue;
            if ($a['start'] < $b['end'] && $b['start'] < $a['end']) {
                // Time overlap confirmed — classify the kind of conflict
                $types = [];
                $aInst = strtolower(trim($a['instructor'])); $bInst = strtolower(trim($b['instructor']));
                if ($aInst !== '' && $aInst === $bInst) $types[] = 'teacher';
                if ($a['room'] !== '' && $a['room'] === $b['room']) $types[] = 'room';
                if (strcasecmp(trim($a['section']), trim($b['section'])) === 0) $types[] = 'section';
                if (empty($types)) continue; // overlap but unrelated offerings
                $fmt = fn($m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                $conflicts[] = [
                    'type' => implode('+', $types),
                    'a' => "{$a['code']} ({$a['section']})",
                    'b' => "{$b['code']} ({$b['section']})",
                    'day' => $a['days'][0],
                    'window' => $fmt(max($a['start'], $b['start'])) . '–' . $fmt(min($a['end'], $b['end']))
                ];
            }
        }
    }
    return $conflicts;
}

// ─── 20. GET: Consolidated reports hub data ────────────────────────────────────
if ($method === 'GET' && $action === 'get_report') {
    $type = trim($_GET['type'] ?? '');
    $allowed = ['master_list','class_roster','attendance_summary','grade_report','failing_students','pending_documents','teacher_load'];
    if (!in_array($type, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown report type.']);
        exit;
    }

    $rows = [];
    $columns = [];
    $title = '';

    switch ($type) {
        case 'master_list':
            $title = 'Student Master List';
            $r = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=student_number,full_name,program,section,email&order=program.asc,section.asc,full_name.asc&limit=2000");
            $rows = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
            $columns = ['student_number','full_name','program','section','email'];
            break;

        case 'class_roster':
            $title = 'Class Roster';
            $classId = trim($_GET['class_id'] ?? '');
            $sec = '';
            if ($classId !== '') {
                $c = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
                if ($c['status'] === 200 && !empty($c['data'])) $sec = strtoupper(trim($c['data'][0]['section'] ?? ''));
            } else {
                $sec = strtoupper(trim($_GET['section'] ?? ''));
            }
            if ($sec !== '') {
                $u = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=student_number,full_name,program,section&order=full_name.asc&limit=1000");
                $students = ($u['status'] === 200 && is_array($u['data'])) ? $u['data'] : [];
                $rows = array_values(array_filter($students, fn($s) =>
                    strpos(strtoupper(trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? ''))), $sec) !== false));
            }
            $columns = ['student_number','full_name','program','section'];
            break;

        case 'attendance_summary':
            $title = 'Attendance Summary per Student';
            $r = supabaseServiceQuery("/rest/v1/attendance_records?select=student_number,student_name,status&limit=5000");
            $recs = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
            $agg = [];
            foreach ($recs as $rec) {
                $k = $rec['student_number'] ?? '?';
                $agg[$k] = $agg[$k] ?? ['student_number' => $k, 'student_name' => $rec['student_name'] ?? '', 'present' => 0, 'late' => 0, 'absent' => 0];
                $st = strtolower($rec['status'] ?? 'present');
                if (isset($agg[$k][$st])) $agg[$k][$st]++;
            }
            $total = count($recs) ?: 1;
            $rows = array_values(array_map(function ($a) use ($recs) {
                $t = $a['present'] + $a['late'] + $a['absent'];
                $a['rate'] = $t > 0 ? round((($a['present'] + 0.8 * $a['late']) / $t) * 100, 1) . '%' : '—';
                return $a;
            }, $agg));
            usort($rows, fn($x, $y) => strcmp($x['student_name'], $y['student_name']));
            $columns = ['student_number','student_name','present','late','absent','rate'];
            break;

        case 'grade_report':
        case 'failing_students':
            $failingOnly = ($type === 'failing_students');
            $title = $failingOnly ? 'Failing Students (grade > 3.00 or INC)' : 'Official Grade Report';
            $r = supabaseServiceQuery("/rest/v1/grades?select=student_number,subject_code,description,grade,units,status&order=student_number.asc&limit=5000" . ($failingOnly ? '&grade=gte.3.00' : ''));
            $rows = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
            if ($failingOnly) {
                $rows = array_values(array_filter($rows, fn($g) => (floatval($g['grade'] ?? 0) > 3.00) || (strtoupper($g['status'] ?? '') === 'INC')));
            }
            $columns = ['student_number','subject_code','description','grade','units','status'];
            break;

        case 'pending_documents':
            $title = 'Pending Document Requests';
            $r = supabaseServiceQuery("/rest/v1/document_requests?select=reference_no,student_name,document_type,purpose,status,requester=student_email,requested_at&order=requested_at.desc&limit=1000");
            if ($r['status'] !== 200) {
                $r = supabaseServiceQuery("/rest/v1/document_requests?select=*&order=requested_at.desc&limit=1000");
            }
            $rows = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
            $rows = array_values(array_filter($rows, fn($d) => stripos($d['status'] ?? '', 'released') === false));
            $columns = array_keys($rows[0] ?? ['reference_no'=>'','student_name'=>'','document_type'=>'','status'=>'','requested_at'=>'']);
            break;

        case 'teacher_load':
            $title = 'Teacher Load Summary';
            $r = supabaseServiceQuery("/rest/v1/classes?select=instructor,instructor_email,code,title,section,units&order=instructor.asc&limit=1000");
            $cls = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
            $load = [];
            foreach ($cls as $c) {
                $k = $c['instructor'] ?? 'Unassigned';
                $load[$k] = $load[$k] ?? ['instructor' => $k, 'subjects' => 0, 'sections' => [], 'units' => 0.0];
                $load[$k]['subjects']++;
                $load[$k]['sections'][$c['section'] ?? ''] = true;
                $load[$k]['units'] += floatval($c['units'] ?? 0);
            }
            $rows = array_map(function ($l) {
                $l['sections'] = implode(', ', array_keys(array_filter($l['sections'])));
                $l['units'] = number_format($l['units'], 1);
                return $l;
            }, array_values($load));
            $columns = ['instructor','subjects','sections','units'];
            break;
    }

    logSecurityEvent("REPORT_GENERATED: $title (" . count($rows) . " rows) by " . ($_SESSION['email'] ?? 'admin'), $_SESSION['email'] ?? '', 'Low');
    echo json_encode(['success' => true, 'title' => $title, 'columns' => $columns, 'rows' => $rows]);
    exit;
}

// ─── 21. GET/POST: Academic term settings (active school year & semester) ──────
if ($method === 'GET' && $action === 'get_academic_settings') {
    // Merge school_years with their semesters (schema: migration 003)
    $r = supabaseServiceQuery("/rest/v1/school_years?select=id,year_label,is_active&order=created_at.desc&limit=20");
    $years = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];

    $s = supabaseServiceQuery("/rest/v1/semesters?select=id,school_year_id,name,is_current&order=created_at.asc&limit=100");
    $sems = ($s['status'] === 200 && is_array($s['data'])) ? $s['data'] : [];

    $semByYear = [];
    foreach ($sems as $sem) {
        $semByYear[$sem['school_year_id']][] = $sem;
    }

    $terms = [];
    foreach ($years as $y) {
        $yearSems = $semByYear[$y['id']] ?? [];
        if (empty($yearSems)) {
            // Year without semester rows — still offerable
            $terms[] = ['id' => $y['id'], 'year_label' => $y['year_label'], 'semester_id' => null, 'semester' => '', 'is_active' => $y['is_active']];
        } else {
            foreach ($yearSems as $sem) {
                $terms[] = [
                    'id' => $sem['id'],
                    'year_label' => $y['year_label'],
                    'semester_id' => $sem['id'],
                    'semester' => $sem['name'],
                    'is_active' => ($y['is_active'] && $sem['is_current'])
                ];
            }
        }
    }

    echo json_encode(['success' => true, 'terms' => $terms]);
    exit;
}
if ($method === 'POST' && $action === 'set_active_term') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $termId = trim($input['term_id'] ?? '');
    if ($termId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Term ID required.']);
        exit;
    }
    // term_id may be a semesters.id OR a school_years.id — detect which
    $isSemester = false;
    $chk = supabaseServiceQuery("/rest/v1/semesters?id=eq." . rawurlencode($termId) . "&select=id&limit=1");
    if ($chk['status'] === 200 && !empty($chk['data'])) $isSemester = true;

    if ($isSemester) {
        // Find parent year, deactivate everything, then activate this pair
        $semQ = supabaseServiceQuery("/rest/v1/semesters?id=eq." . rawurlencode($termId) . "&select=school_year_id&limit=1");
        $parentId = ($semQ['status'] === 200 && !empty($semQ['data'])) ? $semQ['data'][0]['school_year_id'] : null;
        supabaseServiceQuery("/rest/v1/semesters?id=neq." . rawurlencode($termId), 'PATCH', ['is_current' => false]);
        supabaseServiceQuery("/rest/v1/school_years?id=neq." . rawurlencode((string)$parentId), 'PATCH', ['is_active' => false]);
        if ($parentId) supabaseServiceQuery("/rest/v1/school_years?id=eq." . rawurlencode((string)$parentId), 'PATCH', ['is_active' => true]);
        $res = supabaseServiceQuery("/rest/v1/semesters?id=eq." . rawurlencode($termId), 'PATCH', ['is_current' => true]);
    } else {
        // Fallback: treat as school_years.id (activate whole year, first/current semester)
        supabaseServiceQuery("/rest/v1/school_years?id=neq." . rawurlencode($termId), 'PATCH', ['is_active' => false]);
        supabaseServiceQuery("/rest/v1/semesters?school_year_id=neq." . rawurlencode($termId), 'PATCH', ['is_current' => false]);
        supabaseServiceQuery("/rest/v1/school_years?id=eq." . rawurlencode($termId), 'PATCH', ['is_active' => true]);
        $res = supabaseServiceQuery("/rest/v1/semesters?school_year_id=eq." . rawurlencode($termId) . "&order=created_at.asc&limit=1", 'PATCH', ['is_current' => true]);
    }

    if ($res['status'] < 200 || $res['status'] >= 300) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Database rejected the update.']);
        exit;
    }
    logSecurityEvent("TERM_ACTIVATED: $termId by " . ($_SESSION['email'] ?? 'admin'), $_SESSION['email'] ?? '', 'High');
    echo json_encode(['success' => true, 'message' => 'Active academic term updated.']);
    exit;
}

// ─── 22. GET/POST: Global app settings (attendance grace period, etc.) ─────────
if ($method === 'GET' && $action === 'get_app_settings') {
    $r = supabaseServiceQuery("/rest/v1/app_settings?select=setting_key,setting_value,data_type,description");
    $settings = ($r['status'] === 200 && is_array($r['data'])) ? $r['data'] : [];
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}
if ($method === 'POST' && $action === 'set_academic_setting') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $key = trim($input['key'] ?? '');
    $value = trim((string)($input['value'] ?? ''));

    $allowedKeys = [
        'attendance_grace_minutes',
        'attendance_present_window_minutes',
        'attendance_late_window_minutes',
        'upload_max_size_mb',
        'allow_self_enrollment'
    ];
    if (!in_array($key, $allowedKeys, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown setting key.']);
        exit;
    }
    // Range validation per key
    $ranges = [
        'attendance_grace_minutes'          => [0, 60],
        'attendance_present_window_minutes' => [1, 180],
        'attendance_late_window_minutes'    => [0, 180],
        'upload_max_size_mb'                => [1, 100]
    ];
    if (isset($ranges[$key])) {
        $iv = intval($value);
        [$min, $max] = $ranges[$key];
        if ($iv < $min || $iv > $max) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $key)) . " must be between $min and $max."]);
            exit;
        }
        $value = (string)$iv;
    }

    // Upsert by setting_key
    $res = supabaseServiceQuery(
        "/rest/v1/app_settings?setting_key=eq." . rawurlencode($key),
        'PATCH',
        ['setting_value' => $value, 'updated_at' => date('c')]
    );
    if ($res['status'] < 200 || $res['status'] >= 300) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Database rejected the update.']);
        exit;
    }

    logSecurityEvent("SETTING_UPDATED: $key=$value by " . ($_SESSION['email'] ?? 'admin'), $_SESSION['email'] ?? '', 'Medium');
    echo json_encode(['success' => true, 'message' => 'Setting saved.']);
    exit;
}

// ─── 24. POST: Delete Security Audit Log Entry ─────────────────────────────────
if ($method === 'POST' && $action === 'delete_audit_log') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $logId = trim($input['id'] ?? '');
    $wipeAll = ($input['scope'] ?? '') === 'all';

    if (!$wipeAll && empty($logId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Audit log ID is required.']);
        exit;
    }

    if ($wipeAll) {
        $delRes = supabaseServiceQuery("/rest/v1/security_logs?id=neq.00000000-0000-0000-0000-000000000000", 'DELETE');
        logSecurityEvent("AUDIT_LOG_WIPED: Entire security audit log cleared by $currentUserEmail", $currentUserEmail, 'High');
        echo json_encode(['success' => true, 'message' => 'All audit logs wiped.']);
        exit;
    }

    $delRes = supabaseServiceQuery("/rest/v1/security_logs?id=eq." . rawurlencode($logId), 'DELETE');
    if ($delRes['status'] >= 200 && $delRes['status'] < 300) {
        logSecurityEvent("AUDIT_LOG_DELETED: Log entry #$logId deleted by $currentUserEmail", $currentUserEmail, 'Medium');
        echo json_encode(['success' => true, 'message' => 'Audit log entry removed.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete audit log entry from database.']);
    }
    exit;
}

// ─── 25. GET: AI Safety Violations & Lockout List ──────────────────────────────
if ($method === 'GET' && $action === 'get_ai_violations') {
    $violations = getAiViolationsList();
    echo json_encode([
        'success' => true,
        'violations' => $violations,
        'count' => count($violations)
    ]);
    exit;
}

// ─── 26. POST: Reset AI Strikes & Lift Lockout ─────────────────────────────────
if ($method === 'POST' && $action === 'reset_ai_strikes') {
    requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $targetEmail = strtolower(trim($input['email'] ?? ''));

    if (empty($targetEmail)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Target user email is required.']);
        exit;
    }

    $ok = resetUserAiViolations($targetEmail, $currentUserEmail);
    if ($ok) {
        echo json_encode(['success' => true, 'message' => "AI strikes cleared and suspension lifted for $targetEmail."]);
    } else {
        echo json_encode(['success' => true, 'message' => "No active violations found for $targetEmail (cleared)."]);
    }
    exit;
}

// ─── 27. GET: Failing & At-Risk Students ───────────────────────────────────────
if ($method === 'GET' && $action === 'get_failing_students') {
    $search = strtolower(trim($_GET['q'] ?? ''));
    $program = trim($_GET['program'] ?? '');
    
    // Fetch grades with failing or INC status
    $res = supabaseServiceQuery("/rest/v1/grades?select=id,student_number,subject_code,description,grade,units,status,created_at&order=student_number.asc&limit=5000");
    $allGrades = ($res['status'] === 200 && is_array($res['data'])) ? $res['data'] : [];

    // Filter failing: grade > 3.00, grade == 5.00, status == Failed / INC / Incomplete
    $failingGrades = array_values(array_filter($allGrades, function ($g) {
        $gradeVal = floatval($g['grade'] ?? 0);
        $st = strtoupper(trim($g['status'] ?? ''));
        return ($gradeVal > 3.00) || ($gradeVal == 5.00) || in_array($st, ['FAILED', 'INC', 'INCOMPLETE', 'DROP', 'DROPPED']);
    }));

    // Fetch student profile info for name and section mapping
    $userRes = supabaseServiceQuery("/rest/v1/users?role=eq.student&select=student_number,full_name,program,section,email&limit=1000");
    $studentsMap = [];
    if ($userRes['status'] === 200 && is_array($userRes['data'])) {
        foreach ($userRes['data'] as $u) {
            $num = strtoupper(trim($u['student_number'] ?? ''));
            if ($num !== '') {
                $studentsMap[$num] = $u;
            }
        }
    }

    $rows = [];
    foreach ($failingGrades as $fg) {
        $num = strtoupper(trim($fg['student_number'] ?? ''));
        $stu = $studentsMap[$num] ?? null;
        
        $row = [
            'id' => $fg['id'] ?? '',
            'student_number' => $num ?: '—',
            'student_name' => $stu['full_name'] ?? 'Student ' . $num,
            'email' => $stu['email'] ?? '—',
            'program' => $stu['program'] ?? '—',
            'section' => $stu['section'] ?? '—',
            'subject_code' => $fg['subject_code'] ?? '—',
            'description' => $fg['description'] ?? '—',
            'grade' => $fg['grade'] ?? '5.00',
            'units' => $fg['units'] ?? 3,
            'status' => $fg['status'] ?? 'Failed',
            'created_at' => $fg['created_at'] ?? ''
        ];

        // Filters
        if ($program && $program !== 'all' && stripos($row['program'], $program) === false) {
            continue;
        }
        if ($search) {
            $combined = strtolower($row['student_number'] . ' ' . $row['student_name'] . ' ' . $row['subject_code'] . ' ' . $row['description']);
            if (strpos($combined, $search) === false) {
                continue;
            }
        }

        $rows[] = $row;
    }

    echo json_encode([
        'success' => true,
        'failing_students' => $rows,
        'count' => count($rows)
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
