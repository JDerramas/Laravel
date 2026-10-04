<?php
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie.txt';
@unlink($cookieFile);

function httpReq($url, $method = 'GET', $data = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// 1. Dev login as student
$loginRes = httpReq("$baseUrl/dev_login.php?as=student", 'GET', [], $cookieFile);
echo "Logged in as student.\n";

$sessionCode = 'TEST-GMEET-SESSION-' . time();

// 2. Test Join
echo "--- 2. Testing meeting_presence_join ---\n";
$joinRes = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'DM103'
], $cookieFile);
echo "Join: $joinRes\n";
$jData = json_decode($joinRes, true);
assert($jData['success'] === true);
assert($jData['is_online'] === true);
assert($jData['leave_count'] === 0);

// 3. Test Heartbeat after 2s
sleep(2);
echo "--- 3. Testing meeting_presence_heartbeat ---\n";
$hbRes = httpReq("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $cookieFile);
echo "Heartbeat: $hbRes\n";
$hData = json_decode($hbRes, true);
assert($hData['success'] === true);
assert($hData['duration_seconds'] >= 1);

// 4. Test Leave
echo "--- 4. Testing meeting_presence_leave ---\n";
$leaveRes = httpReq("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode,
    'student_number' => $jData['student_number']
], $cookieFile);
echo "Leave: $leaveRes\n";
$lData = json_decode($leaveRes, true);
assert($lData['success'] === true);

// 5. Test Rejoin
echo "--- 5. Testing Rejoin ---\n";
$rejoinRes = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'DM103'
], $cookieFile);
echo "Rejoin: $rejoinRes\n";
$rjData = json_decode($rejoinRes, true);
assert($rjData['success'] === true);
assert($rjData['leave_count'] >= 1);

// 6. Test Roster (Surname Sorted)
echo "--- 6. Testing get_live_presence_roster ---\n";
$rosterRes = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=$sessionCode", 'GET', [], $cookieFile);
echo "Roster: $rosterRes\n";
$rData = json_decode($rosterRes, true);
assert($rData['success'] === true);
echo "\n=== ALL BACKEND PRESENCE LIFECYCLE TESTS PASSED! ===\n";
@unlink($cookieFile);
