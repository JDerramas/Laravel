<?php
// E2E Real-time Teacher-Student Lifecycle Simulation
header('Content-Type: text/plain');

$baseUrl = 'http://localhost:8000';

function makeRequest($url, $method = 'GET', $body = null, $cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    $res = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($res, 0, $headerSize);
    $responseBody = substr($res, $headerSize);
    curl_close($ch);

    // Extract cookie
    $cookie = '';
    if (preg_match('/Set-Cookie:\s*([^;]+)/mi', $headers, $m)) {
        $cookie = $m[1];
    }
    return [
        'headers' => $headers,
        'body' => $responseBody,
        'data' => json_decode($responseBody, true),
        'cookie' => $cookie
    ];
}

echo "=== STARTING FULL E2E REAL-TIME & MOBILE LINK TEST ===\n\n";

// Step 1: Login Teacher
$tLogin = makeRequest("$baseUrl/dev_login.php?role=teacher&redirect=/teacher/courses.php");
$teacherCookie = $tLogin['cookie'];
echo "[1] Teacher Login: " . ($teacherCookie ? "SUCCESS ($teacherCookie)" : "FAILED") . "\n";

// Step 2: Login Student
$sLogin = makeRequest("$baseUrl/dev_login.php?role=student&redirect=/student/courses.php");
$studentCookie = $sLogin['cookie'];
echo "[2] Student Login: " . ($studentCookie ? "SUCCESS ($studentCookie)" : "FAILED") . "\n";

// Step 3: Teacher Starts Live Class for AIS 201
$startPayload = [
    'course_code' => 'AIS 201',
    'state' => 'start',
    'topic' => 'Real-Time Sync Systems Audit',
    'agenda' => 'Live Attendance & Google Meet Integration',
    'platform' => 'google_meet',
    'meeting_link' => 'https://meet.google.com/npc-ais2-201',
    'grace_period' => 20
];
$tLiveRes = makeRequest("$baseUrl/api/elms.php?action=toggle_live_class", 'POST', $startPayload, $teacherCookie);
$sessionCode = $tLiveRes['data']['live_session']['session_code'] ?? '';
echo "[3] Teacher Starts Class: " . ($tLiveRes['data']['success'] ? "SUCCESS (Session: $sessionCode)" : "FAILED: " . json_encode($tLiveRes['data'])) . "\n";

// Step 4: Student checks live sessions in real-time
$sLiveCheck = makeRequest("$baseUrl/api/elms.php?action=get_live_sessions", 'GET', null, $studentCookie);
$isLiveForStudent = false;
$meetUrl = '';
if (!empty($sLiveCheck['data']['live_sessions'])) {
    foreach ($sLiveCheck['data']['live_sessions'] as $ls) {
        if ($ls['course_code'] === 'AIS 201') {
            $isLiveForStudent = true;
            $meetUrl = $ls['meeting_link'];
            break;
        }
    }
}
echo "[4] Student Polls Live Sessions: " . ($isLiveForStudent ? "SUCCESS (Found AIS 201 live, Meet URL: $meetUrl)" : "FAILED: " . json_encode($sLiveCheck['data'])) . "\n";

// Step 5: Student fetches courses in real-time
$sCoursesCheck = makeRequest("$baseUrl/api/elms.php?action=get_courses", 'GET', null, $studentCookie);
$courseLiveFlag = false;
if (!empty($sCoursesCheck['data']['courses'])) {
    foreach ($sCoursesCheck['data']['courses'] as $c) {
        if ($c['code'] === 'AIS 201' && !empty($c['is_live_for_me'])) {
            $courseLiveFlag = true;
            break;
        }
    }
}
echo "[5] Student Courses Broadcast: " . ($courseLiveFlag ? "SUCCESS (AIS 201 has is_live_for_me = true)" : "FAILED") . "\n";

// Step 6: Student joins live presence session
$sJoin = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS 201'
], $studentCookie);
$joinSuccess = !empty($sJoin['data']['success']);
echo "[6] Student Joins Presence: " . ($joinSuccess ? "SUCCESS (Online: " . ($sJoin['data']['is_online'] ? 'true' : 'false') . ", Dur: {$sJoin['data']['duration_seconds']}s)" : "FAILED: " . json_encode($sJoin['data'])) . "\n";

// Sleep 1 second then send heartbeat
sleep(1);
$sHb = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);
echo "[7] Student Heartbeat: " . (!empty($sHb['data']['success']) ? "SUCCESS (Dur: {$sHb['data']['duration_seconds']}s)" : "FAILED") . "\n";

// Step 8: Teacher checks Live Attendance Roster
$tRoster = makeRequest("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=$sessionCode&course_code=AIS%20201", 'GET', null, $teacherCookie);
$studentInRoster = false;
$studentStatus = '';
$rosterList = $tRoster['data']['roster'] ?? [];
foreach ($rosterList as $std) {
    if ($std['student_number'] === '2024-00192' || strpos($std['formatted_name'] ?? '', 'LOVI') !== false || strpos($std['full_name'] ?? '', 'Lovi') !== false) {
        $studentInRoster = true;
        $studentStatus = !empty($std['is_online']) ? 'Active in GMeet' : 'Left / Offline';
        $studentDuration = $std['duration_seconds'] ?? 0;
        break;
    }
}
echo "[8] Teacher Live Roster: " . ($studentInRoster ? "SUCCESS (Student visible: {$std['formatted_name']}, Status: $studentStatus, Dur: {$studentDuration}s)" : "FAILED (Total in roster: " . count($rosterList) . ")") . "\n";

// Step 9: Student Leaves Class
$sLeave = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);
echo "[9] Student Leaves: " . (!empty($sLeave['data']['success']) ? "SUCCESS (Leaves: " . ($sLeave['data']['leave_count'] ?? 1) . ")" : "FAILED") . "\n";

// Step 10: Teacher checks updated status after leave
$tRosterAfterLeave = makeRequest("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=$sessionCode&course_code=AIS%20201", 'GET', null, $teacherCookie);
$statusAfterLeave = '';
$rosterAfter = $tRosterAfterLeave['data']['roster'] ?? [];
foreach ($rosterAfter as $std) {
    if ($std['student_number'] === '2024-00192' || strpos($std['formatted_name'] ?? '', 'LOVI') !== false || strpos($std['full_name'] ?? '', 'Lovi') !== false) {
        $statusAfterLeave = !empty($std['is_online']) ? 'Active in GMeet' : 'Left / Offline';
        $leavesCount = $std['leave_count'] ?? 0;
        break;
    }
}
echo "[10] Teacher Sees Student Status After Leave: " . ($statusAfterLeave === 'Left / Offline' ? "SUCCESS (Status: $statusAfterLeave, Leaves logged: $leavesCount)" : "STATUS: $statusAfterLeave") . "\n";

// Step 11: Teacher Ends Class
$tEndRes = makeRequest("$baseUrl/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code' => 'AIS 201',
    'state' => 'end'
], $teacherCookie);
echo "[11] Teacher Ends Live Class: " . (!empty($tEndRes['data']['success']) ? "SUCCESS" : "FAILED") . "\n";

// Step 12: Student verifies class has ended in real-time
$sFinalCheck = makeRequest("$baseUrl/api/elms.php?action=get_live_sessions", 'GET', null, $studentCookie);
$isCleared = empty($sFinalCheck['data']['live_sessions']);
echo "[12] Student Live Session Cleared: " . ($isCleared ? "SUCCESS (0 active sessions)" : "REMAINING ACTIVE") . "\n";

echo "\n=== ALL E2E SIMULATION STEPS COMPLETED! ===\n";
