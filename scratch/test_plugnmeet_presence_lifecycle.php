<?php
/**
 * Automated Verification Script:
 * PlugNmeet / WebRTC Virtual Classroom & Prof-Only Realtime Leave Counter
 */

$baseUrl = 'http://127.0.0.1:8000';
$teacherCookie = tempnam(sys_get_temp_dir(), 'tc_');
$studentCookie = tempnam(sys_get_temp_dir(), 'sc_');

function loginUser($baseUrl, $identifier, $password) {
    $ch = curl_init("$baseUrl/login.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'identifier' => $identifier,
        'password'   => $password
    ]));
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $res, $matches);
    $sessId = $matches[1] ?? '';
    return ['status' => $status, 'cookie' => $sessId ? "PHPSESSID=$sessId" : ''];
}

function httpReq($url, $method = 'GET', $body = null, $cookie = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            $payload = is_array($body) ? json_encode($body) : $body;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($res, true), 'raw' => $res];
}

echo "========================================================\n";
echo "1. AUTHENTICATING TEACHER & STUDENT\n";
echo "========================================================\n";

$tAuth = loginUser($baseUrl, 'jderramas251505@navotaspolytechniccollege.edu.ph', 'Password123!');
$teacherCookie = $tAuth['cookie'];
echo "Teacher Login: Status {$tAuth['status']} - Cookie: $teacherCookie\n";

$sAuth = loginUser($baseUrl, '2024001', 'Password123!');
$studentCookie = $sAuth['cookie'];
echo "Student Login: Status {$sAuth['status']} - Cookie: $studentCookie\n";

echo "\n========================================================\n";
echo "2. TEACHER STARTS PLUGNMEET LIVE CLASS (UNLI TIME)\n";
echo "========================================================\n";

$startClass = httpReq("$baseUrl/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code' => 'AIS 201',
    'state' => 'start',
    'topic' => 'PlugNmeet Real-time WebRTC Lecture',
    'agenda' => 'Demonstrating unli-time and automatic presence tracking.',
    'platform' => 'plugnmeet',
    'timer_mode' => 'unlimited',
    'duration_minutes' => 0
], $teacherCookie);

echo "Start Class Status: " . $startClass['status'] . "\n";
$session = $startClass['data']['live_session'] ?? [];
$sessionCode = $session['session_code'] ?? '';
echo "Platform: " . ($session['platform'] ?? 'N/A') . "\n";
echo "Timer Mode: " . ($session['timer_mode'] ?? 'N/A') . "\n";
echo "Session Code: $sessionCode\n";
echo "Meeting Link: " . ($session['meeting_link'] ?? 'N/A') . "\n";

assert($session['platform'] === 'plugnmeet', "Platform should be plugnmeet");
assert($session['timer_mode'] === 'unlimited', "Timer mode should be unlimited");

echo "\n========================================================\n";
// Reset presence record for this specific test run
$presenceFile = 'D:/xampp/htdocs/llama-b10483-bin-win-cpu-x64/LocalAI/app/backend/elms_presence.json';
$pData = json_decode(@file_get_contents($presenceFile), true) ?: [];
if (isset($pData[$sessionCode]['2024001'])) {
    unset($pData[$sessionCode]['2024001']);
    file_put_contents($presenceFile, json_encode($pData, JSON_PRETTY_PRINT));
}

$join1 = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS 201'
], $studentCookie);

echo "Student Join Response: " . json_encode($join1['data']) . "\n";
assert($join1['data']['success'] === true, "Student join should succeed");
assert($join1['data']['is_online'] === true, "Student should be online");
assert($join1['data']['duration_seconds'] === 0, "Initial duration should be 0");
assert($join1['data']['leave_count'] === 0, "Initial leave count should be 0");

echo "\n========================================================\n";
echo "4. SIMULATING ACTIVE PRESENCE (HEARTBEAT 1)\n";
echo "========================================================\n";
sleep(2);

$hb1 = httpReq("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);

echo "Heartbeat 1 Response: " . json_encode($hb1['data']) . "\n";
$duration1 = $hb1['data']['duration_seconds'] ?? 0;
echo "Active Duration after 2s: $duration1 seconds\n";
assert($duration1 >= 2, "Active duration should advance");

echo "\n========================================================\n";
echo "5. STUDENT LEAVES ROOM (AUTO-STOP TIMER & INCREMENT LEAVE COUNT)\n";
echo "========================================================\n";

$leave1 = httpReq("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode,
    'student_number' => '2024001'
], $studentCookie);

echo "Leave 1 Response: " . json_encode($leave1['data']) . "\n";
assert($leave1['data']['success'] === true, "Leave request should succeed");
assert($leave1['data']['leave_count'] === 1, "Leave count should now be 1");

echo "\n========================================================\n";
echo "6. TEACHER LIVE ROSTER CHECK (VERIFY PROF SEES LEAVE COUNT=1 AND FROZEN TIMER)\n";
echo "========================================================\n";

$roster1 = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$rosterData1 = $roster1['data']['roster'] ?? [];
$studentRec1 = null;
foreach ($rosterData1 as $r) {
    if (($r['student_number'] ?? '') === '2024001') {
        $studentRec1 = $r;
        break;
    }
}

echo "Teacher Roster Record: " . json_encode($studentRec1) . "\n";
assert($studentRec1 !== null, "Student should be found in roster");
assert($studentRec1['is_online'] === false, "Student should be offline / left");
assert($studentRec1['leave_count'] === 1, "Professor should see leave_count = 1");
$frozenDuration = $studentRec1['duration_seconds'];
echo "Frozen Duration: $frozenDuration seconds (Timer successfully stopped!)\n";

echo "\n========================================================\n";
echo "7. STUDENT REJOINS CLASSROOM (TIMER RESUMES FROM FROZEN DURATION)\n";
echo "========================================================\n";

$rejoin = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS 201'
], $studentCookie);

echo "Rejoin Response: " . json_encode($rejoin['data']) . "\n";
assert($rejoin['data']['is_rejoin'] === true, "Should be marked as rejoin");
assert($rejoin['data']['is_online'] === true, "Student should be online again");
assert($rejoin['data']['duration_seconds'] === $frozenDuration, "Duration should resume from frozen state ($frozenDuration)");
assert($rejoin['data']['leave_count'] === 1, "Leave count remains 1 while in room");

echo "\n========================================================\n";
echo "8. STUDENT ADVANCES ACTIVE TIME & LEAVES SECOND TIME\n";
echo "========================================================\n";
sleep(2);

$hb2 = httpReq("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);

$duration2 = $hb2['data']['duration_seconds'] ?? 0;
echo "Active Duration after second session: $duration2 seconds\n";
assert($duration2 > $frozenDuration, "Duration should advance beyond prior frozen duration");

$leave2 = httpReq("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode,
    'student_number' => '2024001'
], $studentCookie);

echo "Leave 2 Response: " . json_encode($leave2['data']) . "\n";
assert($leave2['data']['leave_count'] === 2, "Leave count should now be 2");

echo "\n========================================================\n";
echo "9. TEACHER ROSTER FINAL VERIFICATION (PROF SEES LEAVE COUNT=2)\n";
echo "========================================================\n";

$roster2 = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$rosterData2 = $roster2['data']['roster'] ?? [];
$studentRec2 = null;
foreach ($rosterData2 as $r) {
    if (($r['student_number'] ?? '') === '2024001') {
        $studentRec2 = $r;
        break;
    }
}

echo "Teacher Final Roster Record: " . json_encode($studentRec2) . "\n";
assert($studentRec2['is_online'] === false, "Student should be offline");
assert($studentRec2['leave_count'] === 2, "Professor MUST see leave_count = 2 in real time");
echo "Teacher successfully sees: is_online=false, duration={$studentRec2['duration_seconds']}s, leave_count={$studentRec2['leave_count']}\n";

echo "\n========================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! 100% VERIFIED!\n";
echo "========================================================\n";
