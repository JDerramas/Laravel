<?php
declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

function makeRequest($url, $method = 'GET', $data = null, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ]);
        }
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response, 'data' => json_decode($response, true)];
}

$teacherCookie = tempnam(sys_get_temp_dir(), 'tchr_');
$studentCookie = tempnam(sys_get_temp_dir(), 'std_');

$tLogin = makeRequest("{$baseUrl}/dev_login.php?role=teacher&redirect=/campus_map.php", 'GET', null, $teacherCookie);
echo "Teacher Login: HTTP {$tLogin['code']}\n";

$sLogin = makeRequest("{$baseUrl}/dev_login.php?role=student&redirect=/campus_map.php", 'GET', null, $studentCookie);
echo "Student Login: HTTP {$sLogin['code']}\n";

echo "\n=== 1. Claiming Room 2F-03 (RM-201) as Faculty with Announcement ===\n";
$claimRes = makeRequest("{$baseUrl}/api/campus_map.php?action=claim_room", 'POST', [
    'room_id' => '2F-03',
    'room_code' => 'RM-201',
    'room_name' => 'Classroom 1 (Room 201)',
    'floor' => '2F',
    'activity_type' => 'Department Review & Consultation',
    'announcement' => 'BSIT 2-A Midterm Consultation & Hands-on Coding Review with Prof. Jilo',
    'enable_virtual' => true
], $teacherCookie);

echo "Claim HTTP: {$claimRes['code']}\n";
print_r($claimRes['data']);

echo "\n=== 2. Real-time Occupancy Check (Student Perspective) ===\n";
$occRes = makeRequest("{$baseUrl}/api/campus_map.php?action=get_occupancy", 'GET', null, $studentCookie);
echo "Occupancy HTTP: {$occRes['code']}\n";
print_r($occRes['data']);

echo "\n=== 3. Student Attempting to Claim Room (Should be Forbidden 403) ===\n";
$studentClaim = makeRequest("{$baseUrl}/api/campus_map.php?action=claim_room", 'POST', [
    'room_id' => '2F-04',
    'room_code' => 'RM-202'
], $studentCookie);
echo "Student Claim HTTP: {$studentClaim['code']} (Expected: 403)\n";
print_r($studentClaim['data']);

echo "\n=== 4. Releasing Room 2F-03 ===\n";
$relRes = makeRequest("{$baseUrl}/api/campus_map.php?action=release_room", 'POST', [
    'room_id' => '2F-03'
], $teacherCookie);
echo "Release HTTP: {$relRes['code']}\n";
print_r($relRes['data']);

@unlink($teacherCookie);
@unlink($studentCookie);
