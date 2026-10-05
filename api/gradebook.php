<?php
/**
 * api_gradebook.php — Server-Side API for Faculty Grade Encoding & Admin Approval
 * 
 * Handles:
 *  - Fetching assigned classes & enrolled students
 *  - Saving draft grades per period / component
 *  - Computing raw, weighted, and transmuted grades (NPC 1.00 - 5.00 scale)
 *  - Submitting grade sheets for review
 *  - Admin reviewing, approving, locking, and publishing grades
 *  - Grade Change Requests (submission & review)
 *  - Audit logging of all grade activities
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helper.php';

require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$currentUserEmail = strtolower($_SESSION['email'] ?? '');
$currentUserName = $_SESSION['name'] ?? 'Staff';
$currentUserRole = $_SESSION['role'] ?? 'student';
$isAdmin = ($currentUserRole === 'admin' || $currentUserRole === 'registrar');
$isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'faculty' || $isAdmin);

// ─── HELPER: NPC Grade Transmutation Scale ──────────────────────────────────────
function transmuteToNpcGrade(float $percentage): array {
    if ($percentage >= 97.0) return ['grade' => 1.00, 'remark' => 'Passed', 'description' => 'Excellent'];
    if ($percentage >= 94.0) return ['grade' => 1.25, 'remark' => 'Passed', 'description' => 'Superior'];
    if ($percentage >= 91.0) return ['grade' => 1.50, 'remark' => 'Passed', 'description' => 'Very Good'];
    if ($percentage >= 88.0) return ['grade' => 1.75, 'remark' => 'Passed', 'description' => 'Good'];
    if ($percentage >= 85.0) return ['grade' => 2.00, 'remark' => 'Passed', 'description' => 'Meritorious'];
    if ($percentage >= 82.0) return ['grade' => 2.25, 'remark' => 'Passed', 'description' => 'Satisfactory'];
    if ($percentage >= 79.0) return ['grade' => 2.50, 'remark' => 'Passed', 'description' => 'Fair'];
    if ($percentage >= 76.0) return ['grade' => 2.75, 'remark' => 'Passed', 'description' => 'Passing'];
    if ($percentage >= 75.0) return ['grade' => 3.00, 'remark' => 'Passed', 'description' => 'Passing'];
    if ($percentage > 0)     return ['grade' => 5.00, 'remark' => 'Failed', 'description' => 'Failed'];
    return ['grade' => 0.00, 'remark' => 'INC', 'description' => 'Incomplete'];
}

// ─── HELPER: Check if a class is assigned to the current teacher ────────────────
function isTeacherAssignedToClass(array $class, string $userEmail, string $userName): bool {
    $email = strtolower(trim($userEmail));
    $name = strtolower(trim($userName));
    $cleanName = trim(preg_replace('/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i', '', $name));

    $cInst = strtolower(trim($class['instructor'] ?? ''));
    $cInstClean = trim(preg_replace('/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i', '', $cInst));
    $cInstEmail = strtolower(trim($class['instructor_email'] ?? ''));
    $cCreatedEmail = strtolower(trim($class['created_by_email'] ?? ''));
    $cCreatedName = strtolower(trim($class['created_by_name'] ?? ''));
    $cCreatedNameClean = trim(preg_replace('/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i', '', $cCreatedName));

    $isTba = empty($cInstClean) || in_array($cInstClean, ['tba', 'to be announced', 'unassigned', 'none']);

    // 1. Direct Instructor Email match
    if (!empty($cInstEmail) && !empty($email) && $cInstEmail === $email) {
        return true;
    }

    // 2. Instructor Name match (must not be TBA)
    if (!$isTba && !empty($cleanName) && (str_contains($cInstClean, $cleanName) || str_contains($cleanName, $cInstClean))) {
        return true;
    }

    // 3. Class created by the teacher themselves (and instructor is not TBA)
    if (!empty($cCreatedEmail) && !empty($email) && $cCreatedEmail === $email && !in_array($cCreatedEmail, ['admin@navotaspolytechniccollege.edu.ph'])) {
        if (!$isTba && (str_contains($cInstClean, $cleanName) || str_contains($cleanName, $cInstClean))) {
            return true;
        }
        if (empty($cInstClean) || $cInstClean === $cleanName || $cCreatedNameClean === $cleanName) {
            return true;
        }
    }

    return false;
}

// ─── 1. GET: Fetch Class Gradebook Details ──────────────────────────────────────
if ($method === 'GET' && $action === 'get_class_grades') {
    if (!$isTeacher) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']);
        exit;
    }

    $classId = trim($_GET['class_id'] ?? '');
    if (empty($classId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'class_id is required.']);
        exit;
    }

    // 1. Verify Class Access (Faculty can only access assigned classes unless admin)
    $classQuery = supabaseServiceQuery("/rest/v1/classes?id=eq.$classId&limit=1");
    if ($classQuery['status'] !== 200 || empty($classQuery['data'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found.']);
        exit;
    }

    $classInfo = $classQuery['data'][0];
    if (!$isAdmin && !isTeacherAssignedToClass($classInfo, $currentUserEmail, $currentUserName)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You are not authorized to access this class gradebook. Only the designated instructor can view or encode grades.']);
        exit;
    }

    // 2. Fetch Submission & Lock Status
    $subQuery = supabaseServiceQuery("/rest/v1/grade_submissions?class_id=eq.$classId&order=created_at.desc&limit=1");
    $submission = ($subQuery['status'] === 200 && !empty($subQuery['data'])) ? $subQuery['data'][0] : null;

    // 3. Fetch Enrolled Students & Stored Grades
    $gradesQuery = supabaseServiceQuery("/rest/v1/student_grades?class_id=eq.$classId&order=student_number.asc");
    $grades = ($gradesQuery['status'] === 200 && is_array($gradesQuery['data'])) ? $gradesQuery['data'] : [];

    // 4. Fetch Enrolled Students from Users table
    $section = trim($classInfo['section'] ?? 'AIS 2A');
    $studentsQuery = supabaseServiceQuery("/rest/v1/users?role=eq.student&order=full_name.asc");
    $allStudents = ($studentsQuery['status'] === 200 && is_array($studentsQuery['data'])) ? $studentsQuery['data'] : [];

    // Filter students by section
    $sectionStudents = array_filter($allStudents, function($s) use ($section) {
        $sSec = trim(($s['program'] ?? '') . ' ' . ($s['section'] ?? ''));
        return str_contains(strtoupper($sSec), strtoupper($section)) 
            || str_contains(strtoupper($section), strtoupper($sSec))
            || str_contains(strtoupper($s['section'] ?? ''), strtoupper($section));
    });

    // Roster assembly: combine section students, student_grades records, and general roster fallback
    $enrolledMap = [];
    foreach ($sectionStudents as $s) {
        $sNum = trim($s['student_number'] ?? $s['id'] ?? '');
        if ($sNum !== '') $enrolledMap[$sNum] = $s;
    }
    foreach ($grades as $g) {
        $sNum = trim($g['student_number'] ?? '');
        if ($sNum !== '' && !isset($enrolledMap[$sNum])) {
            $enrolledMap[$sNum] = [
                'id' => $g['student_id'] ?? $sNum,
                'student_number' => $sNum,
                'full_name' => $g['student_name'] ?? ('Student ' . $sNum),
                'program' => $classInfo['program'] ?? 'BSIS',
                'section' => $section
            ];
        }
    }
    if (empty($enrolledMap) && !empty($allStudents)) {
        foreach ($allStudents as $s) {
            $sNum = trim($s['student_number'] ?? $s['id'] ?? '');
            if ($sNum !== '') $enrolledMap[$sNum] = $s;
        }
    }
    $finalStudents = array_values($enrolledMap);

    // 5. Fetch Grade Components / Default Weights
    $compQuery = supabaseServiceQuery("/rest/v1/grade_components?class_id=eq.$classId&order=sort_order.asc,created_at.asc");
    $components = ($compQuery['status'] === 200 && is_array($compQuery['data'])) ? $compQuery['data'] : [];

    // Auto-seed balanced standard NPC components if none exist yet
    if (empty($components)) {
        $defaultComps = [
            // Prelim (100%)
            ['class_id' => $classId, 'component_name' => 'Quiz 1', 'percentage_weight' => 20, 'max_score' => 20, 'grading_period' => 'Prelim', 'description' => 'Written Quiz', 'sort_order' => 1],
            ['class_id' => $classId, 'component_name' => 'Hands-on Activity', 'percentage_weight' => 30, 'max_score' => 50, 'grading_period' => 'Prelim', 'description' => 'Laboratory Activity', 'sort_order' => 2],
            ['class_id' => $classId, 'component_name' => 'Prelim Exam', 'percentage_weight' => 50, 'max_score' => 100, 'grading_period' => 'Prelim', 'description' => 'Periodical Major Exam', 'sort_order' => 3],
            // Midterm (100%)
            ['class_id' => $classId, 'component_name' => 'Quiz 2', 'percentage_weight' => 20, 'max_score' => 20, 'grading_period' => 'Midterm', 'description' => 'Written Quiz', 'sort_order' => 1],
            ['class_id' => $classId, 'component_name' => 'Case Study Project', 'percentage_weight' => 30, 'max_score' => 100, 'grading_period' => 'Midterm', 'description' => 'Practical Project', 'sort_order' => 2],
            ['class_id' => $classId, 'component_name' => 'Midterm Exam', 'percentage_weight' => 50, 'max_score' => 100, 'grading_period' => 'Midterm', 'description' => 'Periodical Major Exam', 'sort_order' => 3],
            // Final (100%)
            ['class_id' => $classId, 'component_name' => 'Quizzes & Seatworks', 'percentage_weight' => 20, 'max_score' => 50, 'grading_period' => 'Final', 'description' => 'Cumulative Assessments', 'sort_order' => 1],
            ['class_id' => $classId, 'component_name' => 'Final Capstone Project', 'percentage_weight' => 30, 'max_score' => 100, 'grading_period' => 'Final', 'description' => 'Major Output', 'sort_order' => 2],
            ['class_id' => $classId, 'component_name' => 'Final Departmental Exam', 'percentage_weight' => 50, 'max_score' => 100, 'grading_period' => 'Final', 'description' => 'Comprehensive Final Exam', 'sort_order' => 3],
        ];
        $insRes = supabaseServiceQuery("/rest/v1/grade_components", 'POST', $defaultComps, ["Prefer: return=representation"]);
        if ($insRes['status'] >= 200 && $insRes['status'] < 300 && !empty($insRes['data'])) {
            $components = $insRes['data'];
        }
    }

    // 6. Fetch saved period weights (grading_schemes)
    $thisPeriodWeights = null;
    $gsQuery = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId) . "&limit=1");
    if ($gsQuery['status'] === 200 && !empty($gsQuery['data'])) {
        $thisPeriodWeights = json_decode($gsQuery['data'][0]['period_weights'] ?? 'null', true);
    }
    if (!$thisPeriodWeights) {
        $thisPeriodWeights = ['prelim' => 30, 'midterm' => 30, 'final' => 40];
    }

    // 7. Calculate Student Attendance Summary
    $attendanceStats = [];
    $sessQ = supabaseServiceQuery("/rest/v1/attendance_sessions?class_id=eq." . rawurlencode($classId) . "&select=session_code&limit=100");
    if ($sessQ['status'] === 200 && !empty($sessQ['data'])) {
        $sessionCodes = array_column($sessQ['data'], 'session_code');
        if (!empty($sessionCodes)) {
            $inList = implode(',', array_map('rawurlencode', $sessionCodes));
            $recQ = supabaseServiceQuery("/rest/v1/attendance_records?session_code=in.($inList)&select=student_number,status&limit=5000");
            if ($recQ['status'] === 200 && is_array($recQ['data'])) {
                $totalSessions = count($sessionCodes);
                foreach ($recQ['data'] as $rec) {
                    $sn = trim($rec['student_number'] ?? '');
                    if ($sn === '') continue;
                    if (!isset($attendanceStats[$sn])) {
                        $attendanceStats[$sn] = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'total' => $totalSessions];
                    }
                    $st = strtolower(trim($rec['status'] ?? 'present'));
                    if (isset($attendanceStats[$sn][$st])) {
                        $attendanceStats[$sn][$st]++;
                    }
                }
                foreach ($attendanceStats as $sn => &$stat) {
                    $effective = $stat['present'] + ($stat['late'] * 0.8) + $stat['excused'];
                    $stat['rate'] = $totalSessions > 0 ? round(($effective / $totalSessions) * 100, 1) : 100.0;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'class' => $classInfo,
        'submission' => $submission,
        'grades' => array_values($grades),
        'students' => $finalStudents,
        'components' => $components,
        'period_weights' => $thisPeriodWeights,
        'attendance_stats' => $attendanceStats,
        'is_locked' => ($submission['lock_state'] ?? false) || in_array($submission['status'] ?? '', ['Approved', 'Published'])
    ]);
    exit;
}

// ─── 2. POST: Save Draft Grades ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'save_draft') {
    requireCsrf();
    if (!$isTeacher) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');
    $gradesData = $input['grades'] ?? [];

    if (empty($classId) || !is_array($gradesData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
        exit;
    }

    // 1. Check class access authorization
    $classQuery = supabaseServiceQuery("/rest/v1/classes?id=eq.$classId&limit=1");
    if ($classQuery['status'] !== 200 || empty($classQuery['data'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found.']);
        exit;
    }
    $classInfo = $classQuery['data'][0];
    if (!$isAdmin && !isTeacherAssignedToClass($classInfo, $currentUserEmail, $currentUserName)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You are not authorized to save grades for this class.']);
        exit;
    }

    // 2. Check if class is locked
    $subQuery = supabaseServiceQuery("/rest/v1/grade_submissions?class_id=eq.$classId&limit=1");
    if ($subQuery['status'] === 200 && !empty($subQuery['data'])) {
        $sub = $subQuery['data'][0];
        if (($sub['lock_state'] ?? false) || in_array($sub['status'] ?? '', ['Approved', 'Published']) && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This grade sheet is locked after approval. Please submit a Grade Change Request to modify.']);
            exit;
        }
    }

    // Prepare rows for upsert in student_grades
    $rowsToUpsert = [];
    foreach ($gradesData as $g) {
        $sNum = trim($g['student_number'] ?? '');
        if (empty($sNum)) continue;

        $prelim = floatval($g['prelim'] ?? 0);
        $midterm = floatval($g['midterm'] ?? 0);
        $prefinal = floatval($g['prefinal'] ?? 0);
        $final = floatval($g['final'] ?? 0);

        // Compute weighted / equivalent
        $pWeight = floatval($input['weight_prelim'] ?? 30) / 100;
        $mWeight = floatval($input['weight_midterm'] ?? 30) / 100;
        $fWeight = floatval($input['weight_final'] ?? 40) / 100;

        $computedRaw = 0;
        if ($prelim > 0 && $midterm > 0 && $final > 0) {
            $computedRaw = ($prelim * $pWeight) + ($midterm * $mWeight) + ($final * $fWeight);
        } elseif ($final > 0) {
            $computedRaw = $final;
        } elseif ($midterm > 0) {
            $computedRaw = ($prelim > 0) ? ($prelim * 0.5 + $midterm * 0.5) : $midterm;
        } else {
            $computedRaw = $prelim;
        }

        $trans = transmuteToNpcGrade($computedRaw);
        $customRemark = trim($g['remarks'] ?? '');
        if ($prelim > 0 && $midterm > 0 && $final > 0) {
            $finalRemark = in_array(strtolower($customRemark), ['dropped', 'excused']) ? $customRemark : $trans['remark'];
        } elseif ($computedRaw > 0) {
            $finalRemark = 'Ongoing';
        } else {
            $finalRemark = 'INC';
        }

        $rowsToUpsert[] = [
            'class_id' => $classId,
            'student_number' => $sNum,
            'student_name' => trim($g['student_name'] ?? ''),
            'prelim' => $prelim,
            'midterm' => $midterm,
            'prefinal' => $prefinal,
            'final' => $final,
            'raw_grade' => round($computedRaw, 2),
            'weighted_grade' => round($computedRaw, 2),
            'equivalent_grade' => $trans['grade'],
            'final_rating' => $trans['grade'],
            'remarks' => $finalRemark,
            'component_scores' => json_encode(is_array($g['component_scores'] ?? null) ? $g['component_scores'] : new stdClass()),
            'is_locked' => false,
            'is_published' => false,
            'updated_at' => date('c')
        ];
    }

    if (empty($rowsToUpsert)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid student grade records to save.']);
        exit;
    }

    // Upsert into student_grades
    $upsertRes = supabaseServiceQuery(
        "/rest/v1/student_grades?on_conflict=class_id,student_number",
        'POST',
        $rowsToUpsert,
        ["Prefer: resolution=merge-duplicates"]
    );

    // Update or insert draft submission state
    supabaseServiceQuery(
        "/rest/v1/grade_submissions?on_conflict=class_id,grading_period",
        'POST',
        [[
            'class_id' => $classId,
            'class_code' => trim($input['class_code'] ?? 'DM103'),
            'section' => trim($input['section'] ?? 'AIS 2A'),
            'faculty_email' => $currentUserEmail,
            'faculty_name' => $currentUserName,
            'grading_period' => 'Final',
            'status' => 'Draft',
            'lock_state' => false,
            'remarks' => 'Draft saved by faculty'
        ]],
        ["Prefer: resolution=merge-duplicates"]
    );

    // Audit log
    logSecurityEvent("GRADE_DRAFT_SAVED: " . count($rowsToUpsert) . " grades saved for class $classId by $currentUserEmail", $currentUserEmail, 'Low');

    echo json_encode([
        'success' => true,
        'message' => 'Draft grades saved successfully!',
        'count' => count($rowsToUpsert)
    ]);
    exit;
}

// ─── 3. POST: Submit Grade Sheet for Approval ──────────────────────────────────
if ($method === 'POST' && $action === 'submit_grades') {
    requireCsrf();
    if (!$isTeacher) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');
    $gradesData = $input['grades'] ?? [];

    if (empty($classId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'class_id is required.']);
        exit;
    }

    // Check class access authorization
    $classQuery = supabaseServiceQuery("/rest/v1/classes?id=eq.$classId&limit=1");
    if ($classQuery['status'] !== 200 || empty($classQuery['data'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found.']);
        exit;
    }
    $classInfo = $classQuery['data'][0];
    if (!$isAdmin && !isTeacherAssignedToClass($classInfo, $currentUserEmail, $currentUserName)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You are not authorized to submit grades for this class.']);
        exit;
    }

    // If grades array is sent, upsert latest grades directly
    if (is_array($gradesData) && !empty($gradesData)) {
        $rowsToUpsert = [];
        $pWeight = floatval($input['weight_prelim'] ?? 30) / 100;
        $mWeight = floatval($input['weight_midterm'] ?? 30) / 100;
        $fWeight = floatval($input['weight_final'] ?? 40) / 100;

        foreach ($gradesData as $g) {
            $sNum = trim($g['student_number'] ?? '');
            if (empty($sNum)) continue;

            $prelim = floatval($g['prelim'] ?? 0);
            $midterm = floatval($g['midterm'] ?? 0);
            $final = floatval($g['final'] ?? 0);

            $computedRaw = 0;
            if ($prelim > 0 && $midterm > 0 && $final > 0) {
                $computedRaw = ($prelim * $pWeight) + ($midterm * $mWeight) + ($final * $fWeight);
            } elseif ($final > 0) {
                $computedRaw = $final;
            } elseif ($midterm > 0) {
                $computedRaw = ($prelim > 0) ? ($prelim * 0.5 + $midterm * 0.5) : $midterm;
            } else {
                $computedRaw = $prelim;
            }

            $trans = transmuteToNpcGrade($computedRaw);
            $customRemark = trim($g['remarks'] ?? '');
            if ($prelim > 0 && $midterm > 0 && $final > 0) {
                $finalRemark = in_array(strtolower($customRemark), ['dropped', 'excused']) ? $customRemark : $trans['remark'];
            } elseif ($computedRaw > 0) {
                $finalRemark = 'Ongoing';
            } else {
                $finalRemark = 'INC';
            }

            $rowsToUpsert[] = [
                'class_id' => $classId,
                'student_number' => $sNum,
                'student_name' => trim($g['student_name'] ?? ''),
                'prelim' => $prelim,
                'midterm' => $midterm,
                'final' => $final,
                'raw_grade' => round($computedRaw, 2),
                'weighted_grade' => round($computedRaw, 2),
                'equivalent_grade' => $trans['grade'],
                'final_rating' => $trans['grade'],
                'remarks' => $finalRemark,
                'component_scores' => json_encode(is_array($g['component_scores'] ?? null) ? $g['component_scores'] : new stdClass()),
                'is_locked' => true,
                'submitted_at' => date('c'),
                'updated_at' => date('c')
            ];
        }

        if (!empty($rowsToUpsert)) {
            supabaseServiceQuery(
                "/rest/v1/student_grades?on_conflict=class_id,student_number",
                'POST',
                $rowsToUpsert,
                ["Prefer: resolution=merge-duplicates"]
            );
        }
    }

    // Update submission status to 'Submitted'
    $subData = [
        'class_id' => $classId,
        'class_code' => trim($input['class_code'] ?? 'DM103'),
        'section' => trim($input['section'] ?? 'AIS 2A'),
        'faculty_email' => $currentUserEmail,
        'faculty_name' => $currentUserName,
        'grading_period' => 'Final',
        'status' => 'Submitted',
        'lock_state' => true, // Locked for faculty edits while under review
        'submitted_at' => date('c'),
        'remarks' => 'Submitted for Registrar/Admin review'
    ];

    $subRes = supabaseServiceQuery(
        "/rest/v1/grade_submissions?on_conflict=class_id,grading_period",
        'POST',
        [$subData],
        ["Prefer: resolution=merge-duplicates"]
    );

    // Notify admins
    $adminNotif = [
        'user_email' => 'admin@navotaspolytechniccollege.edu.ph',
        'title' => 'New Grade Sheet Submitted',
        'message' => "$currentUserName has submitted the official grade sheet for {$input['class_code']} ({$input['section']}) for review.",
        'type' => 'grade',
        'link_url' => "admin_grades.php?class_id=$classId"
    ];
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [$adminNotif]);

    logSecurityEvent("GRADE_SUBMITTED: Class $classId submitted for approval by $currentUserEmail", $currentUserEmail, 'Medium');

    echo json_encode([
        'success' => true,
        'message' => 'Grade sheet successfully submitted to the Registrar/Admin for approval!'
    ]);
    exit;
}

// ─── 4. POST: Admin Approve, Lock & Publish Grades ──────────────────────────────
if ($method === 'POST' && $action === 'approve_and_publish') {
    requireCsrf();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin or Registrar access required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');

    if (empty($classId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'class_id is required.']);
        exit;
    }

    // 1. Lock and set published in student_grades
    supabaseServiceQuery(
        "/rest/v1/student_grades?class_id=eq.$classId",
        'PATCH',
        [
            'is_locked' => true,
            'is_published' => true,
            'approved_at' => date('c'),
            'published_at' => date('c')
        ]
    );

    // 2. Also mirror into official `grades` table so student portal immediately displays them
    $sgQuery = supabaseServiceQuery("/rest/v1/student_grades?class_id=eq.$classId");
    $clQuery = supabaseServiceQuery("/rest/v1/classes?id=eq.$classId&limit=1");
    $classObj = ($clQuery['status'] === 200 && !empty($clQuery['data'])) ? $clQuery['data'][0] : null;

    if ($sgQuery['status'] === 200 && is_array($sgQuery['data']) && $classObj) {
        $gradesMirror = [];
        foreach ($sgQuery['data'] as $sg) {
            $gradesMirror[] = [
                'student_id' => $sg['student_number'],
                'student_number' => $sg['student_number'],
                'subject_code' => $classObj['code'],
                'description' => $classObj['title'],
                'units' => $classObj['units'] ?? 3.0,
                'grade' => $sg['equivalent_grade'],
                'prelim_grade' => $sg['prelim'] ?? 0,
                'midterm_grade' => $sg['midterm'] ?? 0,
                'final_grade' => $sg['final'] ?? 0,
                'status' => $sg['remarks'],
                'semester' => $classObj['semester'] ?? '1st Semester, 2026-2027'
            ];

            // Send notification to each enrolled student
            supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
                'user_email' => strtolower($sg['student_number']) . '@navotaspolytechniccollege.edu.ph',
                'title' => 'Official Grades Published',
                'message' => "Your final grade for {$classObj['code']} ({$classObj['title']}) has been approved and published: {$sg['equivalent_grade']} ({$sg['remarks']}).",
                'type' => 'grade',
                'link_url' => 'academic.php'
            ]], ["Prefer: return=minimal"]);
        }

        if (!empty($gradesMirror)) {
            supabaseServiceQuery(
                "/rest/v1/grades?on_conflict=student_number,subject_code",
                'POST',
                $gradesMirror,
                ["Prefer: resolution=merge-duplicates"]
            );
        }
    }

    // 3. Update Grade Submission status
    supabaseServiceQuery(
        "/rest/v1/grade_submissions?class_id=eq.$classId",
        'PATCH',
        [
            'status' => 'Published',
            'lock_state' => true,
            'approved_at' => date('c'),
            'published_at' => date('c'),
            'reviewed_by' => $currentUserEmail,
            'reviewed_at' => date('c')
        ]
    );

    logSecurityEvent("GRADE_PUBLISHED: Class $classId approved and published to students by $currentUserEmail", $currentUserEmail, 'High');

    echo json_encode([
        'success' => true,
        'message' => 'Grades officially approved, locked, and published to students!'
    ]);
    exit;
}

// ─── 5. POST: Faculty Request Grade Change ──────────────────────────────────────
if ($method === 'POST' && $action === 'request_grade_change') {
    requireCsrf();
    if (!$isTeacher) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');
    $studentNumber = trim($input['student_number'] ?? '');
    $studentName = trim($input['student_name'] ?? '');
    $originalGrade = floatval($input['original_grade'] ?? 0);
    $proposedGrade = floatval($input['proposed_grade'] ?? 0);
    $reason = trim($input['reason'] ?? '');

    if (empty($classId) || empty($studentNumber) || empty($reason) || $proposedGrade <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields including valid proposed grade and reason are required.']);
        exit;
    }

    $reqData = [
        'class_id' => $classId,
        'class_code' => trim($input['class_code'] ?? 'DM103'),
        'student_number' => $studentNumber,
        'student_name' => $studentName,
        'faculty_email' => $currentUserEmail,
        'faculty_name' => $currentUserName,
        'original_grade' => $originalGrade,
        'proposed_grade' => $proposedGrade,
        'reason' => $reason,
        'status' => 'Pending'
    ];

    $insertRes = supabaseServiceQuery("/rest/v1/grade_change_requests", 'POST', [$reqData]);

    // Notify admin
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => 'admin@navotaspolytechniccollege.edu.ph',
        'title' => 'Grade Change Request Submitted',
        'message' => "Prof. $currentUserName requested a grade change for $studentName ($studentNumber) in {$input['class_code']}: $originalGrade -> $proposedGrade.",
        'type' => 'grade',
        'link_url' => 'admin_grades.php'
    ]]);

    logSecurityEvent("GRADE_CHANGE_REQUESTED: $studentNumber ($originalGrade -> $proposedGrade) by $currentUserEmail. Reason: $reason", $currentUserEmail, 'Medium');

    echo json_encode([
        'success' => true,
        'message' => 'Grade change request submitted successfully to the Registrar for review.'
    ]);
    exit;
}

// ─── 6. POST: Admin Review Grade Change Request ─────────────────────────────────
if ($method === 'POST' && $action === 'review_grade_change') {
    requireCsrf();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin or Registrar access required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = trim($input['request_id'] ?? '');
    $decision = trim($input['decision'] ?? ''); // 'Approved' | 'Rejected'
    $adminRemarks = trim($input['remarks'] ?? '');

    if (empty($requestId) || !in_array($decision, ['Approved', 'Rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request ID or decision.']);
        exit;
    }

    // Fetch request
    $gcrQuery = supabaseServiceQuery("/rest/v1/grade_change_requests?id=eq.$requestId&limit=1");
    if ($gcrQuery['status'] !== 200 || empty($gcrQuery['data'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Grade change request not found.']);
        exit;
    }

    $req = $gcrQuery['data'][0];

    // If Approved, update student_grades & grades table
    if ($decision === 'Approved') {
        $trans = transmuteToNpcGrade($req['proposed_grade']);
        
        // Update student_grades
        supabaseServiceQuery(
            "/rest/v1/student_grades?class_id=eq.{$req['class_id']}&student_number=eq.{$req['student_number']}",
            'PATCH',
            [
                'equivalent_grade' => $req['proposed_grade'],
                'final_rating' => $req['proposed_grade'],
                'remarks' => $trans['remark'],
                'updated_at' => date('c')
            ]
        );

        // Update grades table
        supabaseServiceQuery(
            "/rest/v1/grades?student_number=eq.{$req['student_number']}&subject_code=eq.{$req['class_code']}",
            'PATCH',
            [
                'grade' => $req['proposed_grade'],
                'status' => $trans['remark']
            ]
        );
    }

    // Update request status
    supabaseServiceQuery(
        "/rest/v1/grade_change_requests?id=eq.$requestId",
        'PATCH',
        [
            'status' => $decision,
            'reviewed_by' => $currentUserEmail,
            'reviewed_at' => date('c'),
            'admin_remarks' => $adminRemarks
        ]
    );

    // Notify faculty
    supabaseServiceQuery("/rest/v1/notifications", 'POST', [[
        'user_email' => $req['faculty_email'],
        'title' => "Grade Change Request $decision",
        'message' => "Your grade change request for {$req['student_name']} in {$req['class_code']} was $decision by the Registrar." . (!empty($adminRemarks) ? " Remarks: $adminRemarks" : ''),
        'type' => 'grade',
        'link_url' => "/teacher/grades.php?class_id={$req['class_id']}"
    ]]);

    logSecurityEvent("GRADE_CHANGE_REVIEWED: Request $requestId $decision by $currentUserEmail", $currentUserEmail, 'High');

    echo json_encode([
        'success' => true,
        'message' => "Grade change request has been $decision."
    ]);
    exit;
}

// ─── POST: Add a grading component to a class/period ───────────────────────────
if ($method === 'POST' && $action === 'add_component') {
    requireCsrf();
    if (!$isTeacher) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);

    $classId = trim($input['class_id'] ?? '');
    $name = substr(trim($input['component_name'] ?? ''), 0, 80);
    $weight = floatval($input['percentage_weight'] ?? 0);
    $maxScore = floatval($input['max_score'] ?? 100);
    $period = ucfirst(strtolower(trim($input['grading_period'] ?? 'Prelim')));
    $description = substr(trim($input['description'] ?? ''), 0, 300);
    $sortOrder = intval($input['sort_order'] ?? 99);

    if ($classId === '' || $name === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Class and component name required.']); exit; }
    if (!in_array($period, ['Prelim', 'Midterm', 'Final'], true)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid grading period.']); exit; }
    if ($weight <= 0 || $weight > 100) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'Weight must be between 0 (exclusive) and 100.']); exit; }
    if ($maxScore <= 0 || $maxScore > 10000) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'Max score must be 1-10000.']); exit; }

    // Ownership
    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $cInfo = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$cInfo || (!$isAdmin && !isTeacherAssignedToClass($cInfo, $currentUserEmail, $currentUserName))) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You can only add components to your own assigned subjects.']); exit;
    }

    $res = supabaseServiceQuery("/rest/v1/grade_components", 'POST', [[
        'class_id' => $classId,
        'component_name' => $name,
        'percentage_weight' => $weight,
        'max_score' => $maxScore,
        'grading_period' => $period,
        'description' => $description,
        'sort_order' => $sortOrder
    ]]);
    if ($res['status'] < 200 || $res['status'] >= 300) { http_response_code(502); echo json_encode(['success' => false, 'message' => 'Database rejected the component.']); exit; }

    logSecurityEvent("GRADE_COMPONENT_ADDED: '$name' ($weight%) [$period] class $classId by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => "Component '$name' added.", 'components' => $res['data'] ?? null]);
    exit;
}

// ─── POST: Update a component (rename / weight / max_score / reorder) ──────────
if ($method === 'POST' && $action === 'update_component') {
    requireCsrf();
    if (!$isTeacher) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if ($id === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Component ID required.']); exit; }

    // Fetch + ownership via component's class
    $q = supabaseServiceQuery("/rest/v1/grade_components?id=eq." . rawurlencode($id) . "&limit=1");
    $comp = ($q['status'] === 200 && !empty($q['data'])) ? $q['data'][0] : null;
    if (!$comp) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Component not found.']); exit; }
    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($comp['class_id']) . "&limit=1");
    $cInfo = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$cInfo || (!$isAdmin && !isTeacherAssignedToClass($cInfo, $currentUserEmail, $currentUserName))) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You can only update components for your own assigned subjects.']); exit;
    }

    $patch = ['updated_at' => date('c')];
    if (isset($input['component_name'])) $patch['component_name'] = substr(trim($input['component_name']), 0, 80);
    if (isset($input['percentage_weight'])) {
        $w = floatval($input['percentage_weight']);
        if ($w < 0 || $w > 100) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'Weight must be 0-100.']); exit; }
        $patch['percentage_weight'] = $w;
    }
    if (isset($input['max_score'])) {
        $ms = floatval($input['max_score']);
        if ($ms <= 0 || $ms > 10000) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'Max score must be 1-10000.']); exit; }
        $patch['max_score'] = $ms;
    }
    if (isset($input['description'])) $patch['description'] = substr(trim($input['description']), 0, 300);
    if (isset($input['sort_order'])) $patch['sort_order'] = intval($input['sort_order']);

    $res = supabaseServiceQuery("/rest/v1/grade_components?id=eq." . rawurlencode($id), 'PATCH', $patch);
    if ($res['status'] < 200 || $res['status'] >= 300) { http_response_code(502); echo json_encode(['success' => false, 'message' => 'Database rejected the update.']); exit; }
    logSecurityEvent("GRADE_COMPONENT_UPDATED: {$comp['component_name']} in class {$comp['class_id']} by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => 'Component updated.']);
    exit;
}

// ─── POST: Delete a component ──────────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete_component') {
    requireCsrf();
    if (!$isTeacher) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    if ($id === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Component ID required.']); exit; }

    $q = supabaseServiceQuery("/rest/v1/grade_components?id=eq." . rawurlencode($id) . "&limit=1");
    $comp = ($q['status'] === 200 && !empty($q['data'])) ? $q['data'][0] : null;
    if (!$comp) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Component not found.']); exit; }
    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($comp['class_id']) . "&limit=1");
    $cInfo = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$cInfo || (!$isAdmin && !isTeacherAssignedToClass($cInfo, $currentUserEmail, $currentUserName))) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You can only delete components from your own assigned subjects.']); exit;
    }

    supabaseServiceQuery("/rest/v1/grade_components?id=eq." . rawurlencode($id), 'DELETE');
    logSecurityEvent("GRADE_COMPONENT_DELETED: '{$comp['component_name']}' from class {$comp['class_id']} by $currentUserEmail", $currentUserEmail, 'High');
    echo json_encode(['success' => true, 'message' => 'Component deleted.']);
    exit;
}

// ─── POST: Save period weights for a class ─────────────────────────────────────
if ($method === 'POST' && $action === 'save_period_weights') {
    requireCsrf();
    if (!$isTeacher) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');
    $pw = $input['period_weights'] ?? [];
    if ($classId === '' || !is_array($pw)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid payload.']); exit; }

    $clean = [];
    foreach (['prelim', 'midterm', 'final'] as $k) {
        $v = floatval($pw[$k] ?? 0);
        if ($v < 0 || $v > 100) { http_response_code(422); echo json_encode(['success' => false, 'message' => ucfirst($k) . " must be 0-100."]); exit; }
        $clean[$k] = $v;
    }
    $sum = array_sum($clean);
    if ($sum < 99.5 || $sum > 100.5) {
        http_response_code(422); echo json_encode(['success' => false, 'message' => "Period weights must total 100% (currently {$sum}%)."]); exit;
    }

    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $cInfo = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$cInfo || (!$isAdmin && !isTeacherAssignedToClass($cInfo, $currentUserEmail, $currentUserName))) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Privacy Restriction: You can only configure period weights for your own assigned subjects.']); exit;
    }

    $existing = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId) . "&select=id&limit=1");
    $payload = ['period_weights' => json_encode($clean), 'updated_at' => date('c')];
    if (!empty($existing['data'])) {
        $res = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId), 'PATCH', $payload);
    } else {
        $payload['class_id'] = $classId;
        $payload['faculty_email'] = $currentUserEmail;
        $res = supabaseServiceQuery("/rest/v1/grading_schemes", 'POST', [$payload]);
    }
    if ($res['status'] < 200 || $res['status'] >= 300) { http_response_code(502); echo json_encode(['success' => false, 'message' => 'Database rejected the weights.']); exit; }
    logSecurityEvent("PERIOD_WEIGHTS_SAVED: class $classId (" . json_encode($clean) . ") by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => 'Period weights saved!']);
    exit;
}

// ─── GET: Load per-class grading scheme ────────────────────────────────────────
if ($method === 'GET' && $action === 'get_grading_scheme') {
    $classId = trim($_GET['class_id'] ?? '');
    if ($classId === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Class ID required.']); exit; }

    // Ownership check
    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $classInfo0 = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$classInfo0 || (!$isAdmin && !isTeacherAssignedToClass($classInfo0, $currentUserEmail, $currentUserName))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Privacy Restriction: Not authorized for this class scheme.']);
        exit;
    }

    $sQuery = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId) . "&limit=1");
    $scheme = ($sQuery['status'] === 200 && !empty($sQuery['data'])) ? $sQuery['data'][0] : null;
    echo json_encode([
        'success' => true,
        'scheme' => $scheme ? [
            'period_weights' => json_decode($scheme['period_weights'] ?? '{}', true),
            'component_weights' => json_decode($scheme['component_weights'] ?? '{}', true),
            'is_locked' => (bool)($scheme['is_locked'] ?? false)
        ] : null
    ]);
    exit;
}

// ─── POST: Save per-class grading scheme ───────────────────────────────────────
if ($method === 'POST' && $action === 'save_grading_scheme') {
    requireCsrf();
    if (!$isTeacher) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Faculty or Admin access required.']); exit; }

    $input = json_decode(file_get_contents('php://input'), true);
    $classId = trim($input['class_id'] ?? '');
    $periodW = $input['period_weights'] ?? [];
    $compW = $input['component_weights'] ?? [];

    if ($classId === '') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Class ID required.']); exit; }
    if (!is_array($periodW) || !is_array($compW)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid scheme payload.']); exit; }

    // Ownership check
    $cQuery = supabaseServiceQuery("/rest/v1/classes?id=eq." . rawurlencode($classId) . "&limit=1");
    $classInfo0 = ($cQuery['status'] === 200 && !empty($cQuery['data'])) ? $cQuery['data'][0] : null;
    if (!$classInfo0 || (!$isAdmin && strtolower($classInfo0['instructor_email'] ?? ($classInfo0['created_by_email'] ?? '')) !== strtolower($currentUserEmail))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only save schemes for your own classes.']);
        exit;
    }

    // Validate period weights: numeric 0-100
    $cleanPeriod = [];
    foreach (['prelim', 'midterm', 'final'] as $k) {
        $v = floatval($periodW[$k] ?? 0);
        if ($v < 0 || $v > 100) { http_response_code(422); echo json_encode(['success' => false, 'message' => ucfirst($k) . " weight must be 0-100."]); exit; }
        $cleanPeriod[$k] = $v;
    }
    $sumP = array_sum($cleanPeriod);
    if ($sumP < 99 || $sumP > 101) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Period weights must total ~100% (currently {$sumP}%)."]);
        exit;
    }

    // Validate component weights per period: each period's parts must total ~100%
    $cleanComp = [];
    foreach (['prelim', 'midterm', 'final'] as $period) {
        if (empty($compW[$period]) || !is_array($compW[$period])) continue;
        $pClean = [];
        foreach ($compW[$period] as $compKey => $compVal) {
            $cv = floatval($compVal);
            if ($cv < 0 || $cv > 100) { http_response_code(422); echo json_encode(['success' => false, 'message' => "Component '$compKey' must be 0-100."]); exit; }
            $pClean[preg_replace('/[^a-z_]/', '', strtolower((string)$compKey))] = $cv;
        }
        $sumC = array_sum($pClean);
        if ($sumC < 99 || $sumC > 101) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => ucfirst($period) . " components must total ~100% (currently {$sumC}%)."]);
            exit;
        }
        $cleanComp[$period] = $pClean;
    }

    // Upsert by class_id (unique constraint handles duplicates)
    $existing = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId) . "&select=id&limit=1");
    $payload = [
        'period_weights' => json_encode($cleanPeriod),
        'component_weights' => json_encode($cleanComp),
        'updated_at' => date('c')
    ];
    if (!empty($existing['data'])) {
        $res = supabaseServiceQuery("/rest/v1/grading_schemes?class_id=eq." . rawurlencode($classId), 'PATCH', $payload);
    } else {
        $payload['class_id'] = $classId;
        $payload['faculty_email'] = $currentUserEmail;
        $res = supabaseServiceQuery("/rest/v1/grading_schemes", 'POST', [$payload]);
    }

    if ($res['status'] < 200 || $res['status'] >= 300) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Database rejected the scheme (does migration 009 exist?).']);
        exit;
    }

    logSecurityEvent("GRADING_SCHEME_SAVED: class $classId by $currentUserEmail", $currentUserEmail, 'Medium');
    echo json_encode(['success' => true, 'message' => 'Grading scheme saved for this class!']);
    exit;
}

// Default Fallback
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid or missing action.']);
exit;
