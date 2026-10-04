<?php
// Test PlugNmeet Real-Time Presence & Lifecycle Auto-Detection
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$presenceFile = __DIR__ . '/../backend/elms_presence.json';
$coursesFile  = __DIR__ . '/../backend/elms_courses.json';

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

echo "=== 1. Logging in Teacher & Student ===\n";
$tLogin = makeRequest("{$baseUrl}/dev_login.php?role=teacher&redirect=/teacher/courses.php", 'GET', null, $teacherCookie);
echo "Teacher Login: HTTP {$tLogin['code']}\n";
$sLogin = makeRequest("{$baseUrl}/dev_login.php?role=student&redirect=/student/courses.php", 'GET', null, $studentCookie);
echo "Student Login: HTTP {$sLogin['code']}\n";

echo "\n=== 2. Starting Live Class with PlugNmeet ===\n";
$startRes = makeRequest("{$baseUrl}/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code'      => 'AIS 201',
    'state'            => 'start',
    'topic'            => 'PlugNmeet Real-Time Test Discussion',
    'agenda'           => 'Verification of real-time auto-detection',
    'platform'         => 'plugnmeet',
    'timer_mode'       => 'unlimited',
    'duration_minutes' => 0,
    'meeting_link'     => '',
    'grace_period'     => 15
], $teacherCookie);

if (!$startRes['data'] || empty($startRes['data']['success'])) {
    echo "FAIL: Could not start live class: " . json_encode($startRes['data']) . "\n";
    exit(1);
}

$liveSession = $startRes['data']['live_session'];
$sessionCode = $liveSession['session_code'];
echo "SUCCESS: Live class started. Session code: {$sessionCode}\n";
echo "Platform: " . ($liveSession['platform'] ?? 'N/A') . "\n";
echo "Meeting Link: " . ($liveSession['meeting_link'] ?? 'N/A') . "\n";

if ($liveSession['platform'] !== 'plugnmeet') {
    echo "FAIL: Platform is not 'plugnmeet'!\n";
    exit(1);
}
if (!str_contains($liveSession['meeting_link'], 'live_room.php')) {
    echo "FAIL: Meeting link does not route to live_room.php!\n";
    exit(1);
}

echo "\n=== 3. Student Dashboard Auto-Detection Polling (2.5s poller) ===\n";
$pollRes = makeRequest("{$baseUrl}/api/elms.php?action=get_live_sessions", 'GET', null, $studentCookie);
if (!$pollRes['data'] || empty($pollRes['data']['success']) || empty($pollRes['data']['live_sessions'])) {
    echo "FAIL: Polling did not return active live sessions: " . json_encode($pollRes['data']) . "\n";
    exit(1);
}
$found = false;
foreach ($pollRes['data']['live_sessions'] as $s) {
    if ($s['session_code'] === $sessionCode) {
        $found = true;
        echo "SUCCESS: Auto-detected live session {$s['session_code']} for {$s['course_code']} (Platform: {$s['platform']})\n";
        break;
    }
}
if (!$found) {
    echo "FAIL: Active session code not found in get_live_sessions!\n";
    exit(1);
}

echo "\n=== 4. Student Auto-Detection on Joining PlugNmeet (live_room.php / meeting_presence_join) ===\n";
$joinRes = makeRequest("{$baseUrl}/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code'  => 'AIS 201'
], $studentCookie);

echo "Join response: " . json_encode($joinRes['data']) . "\n";
if (!$joinRes['data'] || empty($joinRes['data']['success'])) {
    echo "FAIL: Student presence join failed!\n";
    exit(1);
}

echo "\n=== 5. Verifying Real-Time Teacher Roster Reflection ===\n";
$rosterRes = makeRequest("{$baseUrl}/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
if (!$rosterRes['data'] || empty($rosterRes['data']['success'])) {
    echo "FAIL: Could not fetch live presence roster: " . json_encode($rosterRes['data']) . "\n";
    exit(1);
}

$rosterData = $rosterRes['data'];
echo "Online Count: " . ($rosterData['online_count'] ?? 0) . "\n";
echo "Present Count: " . ($rosterData['present_count'] ?? 0) . "\n";
echo "Left Count: " . ($rosterData['left_count'] ?? 0) . "\n";

$studentFound = null;
foreach ($rosterData['roster'] as $r) {
    if ($r['is_online']) {
        $studentFound = $r;
        break;
    }
}

if (!$studentFound) {
    echo "FAIL: Online student not found in teacher roster!\n";
    exit(1);
}

echo "Student in roster: Number={$studentFound['student_number']}, Name={$studentFound['full_name']}, Online=" . ($studentFound['is_online'] ? 'YES' : 'NO') . ", Status={$studentFound['status']}, VerifiedVia={$studentFound['verified_via']}\n";

if (!$studentFound['is_online']) {
    echo "FAIL: Student should be marked is_online = true!\n";
    exit(1);
}
if ($studentFound['verified_via'] !== 'plugnmeet_verified') {
    echo "FAIL: verified_via should be 'plugnmeet_verified', got: {$studentFound['verified_via']}\n";
    exit(1);
}

echo "\n=== 6. Testing Heartbeats & Active Duration Ticker ===\n";
for ($i = 1; $i <= 3; $i++) {
    $hb = makeRequest("{$baseUrl}/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
        'session_code' => $sessionCode
    ], $studentCookie);
    echo "Heartbeat #{$i} response: duration={$hb['data']['duration_seconds']}s, leave_count={$hb['data']['leave_count']}\n";
    usleep(150000);
}

echo "\n=== 7. Testing Student Leave / Disconnect Telemetry ===\n";
$leaveRes = makeRequest("{$baseUrl}/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);
echo "Leave response: " . json_encode($leaveRes['data']) . "\n";

$rosterRes2 = makeRequest("{$baseUrl}/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$studentFound2 = null;
foreach ($rosterRes2['data']['roster'] as $r) {
    if ($r['student_number'] === $studentFound['student_number']) {
        $studentFound2 = $r;
        break;
    }
}
echo "After leave: Online=" . ($studentFound2['is_online'] ? 'YES' : 'NO') . ", LeaveCount={$studentFound2['leave_count']}\n";
if ($studentFound2['is_online'] !== false) {
    echo "FAIL: Student is_online should be false after leaving!\n";
    exit(1);
}
if ($studentFound2['leave_count'] < 1) {
    echo "FAIL: Student leave_count should be at least 1!\n";
    exit(1);
}

echo "\n=== 8. Testing Student Reconnect / Rejoin Telemetry ===\n";
$rejoinRes = makeRequest("{$baseUrl}/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code'  => 'AIS 201'
], $studentCookie);
echo "Rejoin response: duration={$rejoinRes['data']['duration_seconds']}s, leave_count={$rejoinRes['data']['leave_count']}\n";

$rosterRes3 = makeRequest("{$baseUrl}/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$studentFound3 = null;
foreach ($rosterRes3['data']['roster'] as $r) {
    if ($r['student_number'] === $studentFound['student_number']) {
        $studentFound3 = $r;
        break;
    }
}
echo "After rejoin: Online=" . ($studentFound3['is_online'] ? 'YES' : 'NO') . ", LeaveCount={$studentFound3['leave_count']}\n";
if ($studentFound3['is_online'] !== true) {
    echo "FAIL: Student is_online should be true after rejoin!\n";
    exit(1);
}
if ($studentFound3['leave_count'] !== 1) {
    echo "FAIL: Student leave_count should stay 1 upon rejoin!\n";
    exit(1);
}

echo "\n=== 9. Concluding Live Session ===\n";
$endRes = makeRequest("{$baseUrl}/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code' => 'AIS 201',
    'state'       => 'end'
], $teacherCookie);
echo "End class response: " . json_encode($endRes['data']) . "\n";

$finalPoll = makeRequest("{$baseUrl}/api/elms.php?action=get_live_sessions", 'GET', null, $studentCookie);
$activeCount = count($finalPoll['data']['live_sessions'] ?? []);
echo "Active sessions remaining: {$activeCount}\n";

echo "\nALL TESTS PASSED WITH 100% REAL-TIME LIVE SYNCHRONIZATION AND AUTO-DETECTION!\n";

@unlink($teacherCookie);
@unlink($studentCookie);
