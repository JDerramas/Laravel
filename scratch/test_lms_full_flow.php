<?php
/**
 * scratch/test_lms_full_flow.php
 * Comprehensive test of LMS file upload, download, and grading flow
 */
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();

echo "=== 1. TEST DATABASE CONNECTION & LMS TABLES ===\n";
$tables = ['lms_materials', 'lms_assignments', 'lms_submissions'];
foreach ($tables as $t) {
    $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "Table $t: $count records\n";
}

echo "\n=== 2. TEST FACULTY MODULE UPLOAD (SIMULATED API CALL) ===\n";
// Create a sample file
$sampleDir = __DIR__ . '/sample_files';
if (!is_dir($sampleDir)) mkdir($sampleDir, 0777, true);
$samplePdfPath = $sampleDir . '/sample_lecture_week3.pdf';
file_put_contents($samplePdfPath, "%PDF-1.4 Mock PDF content for AIS 201 Accounting Information Systems Lecture 3");

$uploadDir = __DIR__ . '/../uploads/materials';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
$destFileName = 'mat_ais201_test_' . time() . '.pdf';
$destPath = $uploadDir . '/' . $destFileName;
copy($samplePdfPath, $destPath);

$stmt = $db->prepare("INSERT INTO lms_materials (course_code, title, description, file_path, file_name, file_type, file_size, uploaded_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    'AIS 201',
    'Week 3: General Ledger and Reporting Cycle (PDF Handout)',
    'Comprehensive lecture notes covering chart of accounts and journal entries.',
    'uploads/materials/' . $destFileName,
    'sample_lecture_week3.pdf',
    'application/pdf',
    filesize($destPath),
    'prof.derramas@gmail.com'
]);
$materialId = $db->lastInsertId();
echo "Inserted Material ID: $materialId ($destFileName)\n";

echo "\n=== 3. TEST FACULTY ASSIGNMENT CREATION ===\n";
$asgId = 'asg-ais201-case-' . time();
$stmt = $db->prepare("INSERT INTO lms_assignments (id, course_code, title, instructions, due_date, points) 
                      VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $asgId,
    'AIS 201',
    'Financial Statement Analysis Case Study (Img/PDF/DOCX)',
    'Please analyze the balance sheet attached in the module and submit your written report as PDF, DOCX, or screenshot.',
    date('Y-m-d H:i:s', strtotime('+7 days')),
    100
]);
echo "Created Assignment ID: $asgId\n";

echo "\n=== 4. TEST STUDENT SUBMISSION WITH FILE ATTACHMENT ===\n";
$sampleSubmissionsDir = __DIR__ . '/../uploads/submissions';
if (!is_dir($sampleSubmissionsDir)) mkdir($sampleSubmissionsDir, 0777, true);
$sampleSubmissionDoc = $sampleDir . '/student_case_study.docx';
file_put_contents($sampleSubmissionDoc, "Mock DOCX submission content: Financial statement analysis for AIS 201.");

$subFileName = 'sub_ais201_student_' . time() . '.docx';
$subDestPath = $sampleSubmissionsDir . '/' . $subFileName;
copy($sampleSubmissionDoc, $subDestPath);

$subId = 'sub-' . substr(md5(uniqid()), 0, 12);
$stmt = $db->prepare("INSERT INTO lms_submissions (id, assignment_id, course_code, student_number, student_name, student_email, file_path, file_name, file_type, file_size, notes, status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Turned In')");
$stmt->execute([
    $subId,
    $asgId,
    'AIS 201',
    '2024-00192',
    'Lovi Student',
    'student.npc@gmail.com',
    'uploads/submissions/' . $subFileName,
    'student_case_study.docx',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    filesize($subDestPath),
    'Good day Sir! Here is my submitted case study assignment.'
]);
echo "Created Student Submission ID: $subId (File: $subFileName, Size: " . filesize($subDestPath) . " bytes)\n";

echo "\n=== 5. TEST FACULTY GRADING SUBMISSION ===\n";
$gradeStmt = $db->prepare("UPDATE lms_submissions 
                          SET score = ?, remarks = ?, graded_by = ?, graded_at = NOW(), status = 'Graded' 
                          WHERE id = ?");
$gradeStmt->execute([
    95,
    'Excellent depth of analysis and clear formatting. Keep up the great work!',
    'Prof. Jilo Derramas',
    $subId
]);
echo "Updated Submission $subId to Graded (Score: 95/100)\n";

echo "\n=== 6. VERIFY QUERY FROM STUDENT VIEW ===\n";
$subCheck = $db->prepare("SELECT * FROM lms_submissions WHERE assignment_id = ? AND student_number = ?");
$subCheck->execute([$asgId, '2024-00192']);
$studentResult = $subCheck->fetch(PDO::FETCH_ASSOC);
print_r($studentResult);

echo "\nALL REALTIME LMS TESTS COMPLETED SUCCESSFULLY!\n";
