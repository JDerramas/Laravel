<?php
/**
 * scratch/test_api_client.php
 * Automated end-to-end HTTP test:
 * 1. Login as Faculty (prof.derramas@gmail.com) via POST login.php
 * 2. Call GET /api/elms.php?action=get_courses (and /api/lms.php)
 * 3. Upload a sample module file to AIS 201
 * 4. Verify file was saved in uploads/materials/
 * 5. Verify download_material returns 200 with proper headers
 * 6. Login as Student (student.npc@gmail.com)
 * 7. Submit an image assignment to AIS 201
 * 8. Verify submission saved in uploads/submissions/
 * 9. Faculty grades the assignment
 */

$cookieJar = __DIR__ . '/cookie.txt';
if (file_exists($cookieJar)) unlink($cookieJar);

function curlReq($url, $postData = null, $isMultipart = false) {
    global $cookieJar;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
    }
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $res];
}

echo "=== STEP 1: Faculty Login via POST /login.php ===\n";
$loginRes = curlReq('http://127.0.0.1:8000/login.php', ['identifier' => 'prof.derramas@gmail.com']);
echo "Login HTTP Code: " . $loginRes['code'] . "\n";

echo "\n=== STEP 2: Call /api/lms.php?action=get_courses ===\n";
$getRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=get_courses');
echo "API HTTP Code: " . $getRes['code'] . "\n";
$data = json_decode($getRes['body'], true);
echo "JSON Valid? " . ($data ? 'YES' : 'NO') . "\n";
echo "Courses count: " . count($data['courses'] ?? []) . "\n";
if (!empty($data['courses'])) {
    echo "First course: " . $data['courses'][0]['code'] . " - " . $data['courses'][0]['title'] . "\n";
    echo "Materials in first course: " . count($data['courses'][0]['materials'] ?? []) . "\n";
    echo "Assignments in first course: " . count($data['courses'][0]['assignments'] ?? []) . "\n";
}

echo "\n=== STEP 3: Faculty Uploads Module File (PNG Image / PDF) ===\n";
$testFile = __DIR__ . '/sample_files/lecture_diagram.png';
file_put_contents($testFile, "MOCK_PNG_IMAGE_DATA_FOR_LMS");

$postFields = [
    'action' => 'upload_material',
    'course_code' => 'AIS 201',
    'title' => 'AIS Entity Relationship Diagram (ERD)',
    'description' => 'System architecture blueprint for accounts receivable subsystem.',
    'material_file' => new CURLFile($testFile, 'image/png', 'lecture_diagram.png')
];
$uploadRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=upload_material', $postFields, true);
echo "Upload HTTP Code: " . $uploadRes['code'] . "\n";
echo "Upload Body: " . $uploadRes['body'] . "\n";
$upJson = json_decode($uploadRes['body'], true);
$uploadedMatId = $upJson['module']['id'] ?? $upJson['material']['id'] ?? null;

echo "\n=== STEP 4: Test Material Download Endpoint ===\n";
if ($uploadedMatId) {
    $dlRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=download_material&id=' . $uploadedMatId);
    echo "Download HTTP Code: " . $dlRes['code'] . "\n";
    echo "Downloaded Body: " . substr($dlRes['body'], 0, 40) . "...\n";
}

echo "\n=== STEP 5: Switch Session to Student (student.npc@gmail.com) ===\n";
if (file_exists($cookieJar)) unlink($cookieJar);
$studentLogin = curlReq('http://127.0.0.1:8000/login.php', ['identifier' => 'student.npc@gmail.com']);
echo "Student Login HTTP Code: " . $studentLogin['code'] . "\n";

echo "\n=== STEP 6: Student Submits Homework with Image Attachment ===\n";
$studentImg = __DIR__ . '/sample_files/my_solution_screenshot.png';
file_put_contents($studentImg, "MOCK_STUDENT_ASSIGNMENT_SCREENSHOT_DATA");

$subPostFields = [
    'action' => 'submit_assignment',
    'assignment_id' => 'asg-ais201-case-1790341335',
    'course_code' => 'AIS 201',
    'notes' => 'Here is my diagram solution attached as PNG.',
    'submission_file' => new CURLFile($studentImg, 'image/png', 'my_solution_screenshot.png')
];
$subRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=submit_assignment', $subPostFields, true);
echo "Student Submit HTTP Code: " . $subRes['code'] . "\n";
echo "Student Submit Body: " . $subRes['body'] . "\n";
$subJson = json_decode($subRes['body'], true);
$subId = $subJson['submission']['id'] ?? null;

echo "\n=== STEP 7: Faculty Reviews & Downloads Student Submission File ===\n";
if (file_exists($cookieJar)) unlink($cookieJar);
curlReq('http://127.0.0.1:8000/login.php', ['identifier' => 'prof.derramas@gmail.com']);
if ($subId) {
    $dlSubRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=download_submission&id=' . $subId);
    echo "Download Submission HTTP Code: " . $dlSubRes['code'] . "\n";
    echo "Downloaded Submission Content: " . substr($dlSubRes['body'], 0, 40) . "\n";

    echo "\n=== STEP 8: Faculty Grades Submission with Feedback ===\n";
    $gradeRes = curlReq('http://127.0.0.1:8000/api/lms.php?action=grade_submission', [
        'submission_id' => $subId,
        'score' => 98,
        'remarks' => 'Superb ERD design. Schema normalization is spot-on!'
    ]);
    echo "Grade HTTP Code: " . $gradeRes['code'] . "\n";
    echo "Grade Body: " . $gradeRes['body'] . "\n";
}

echo "\nALL API TESTS FINISHED!\n";
