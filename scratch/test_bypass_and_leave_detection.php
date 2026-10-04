<?php
$baseUrl = 'http://localhost:8000';

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

echo "=======================================================\n";
echo "NPC ELMS: TEST AUTO GMEET ROOM AND REAL-TIME LEAVE\n";
echo "=======================================================\n\n";

// 1. Login Teacher
echo "[1] Logging in Teacher...\n";
$tLogin = makeRequest("$baseUrl/dev_login.php?role=teacher&redirect=/teacher/courses.php", 'GET', null, $teacherCookie);
assert($tLogin['code'] === 200, "Teacher login failed");
echo "    Teacher logged in: HTTP {$tLogin['code']}\n";

// 2. Login Student
echo "[2] Logging in Student...\n";
$sLogin = makeRequest("$baseUrl/dev_login.php?role=student&redirect=/student/courses.php", 'GET', null, $studentCookie);
assert($sLogin['code'] === 200, "Student login failed");
echo "    Student logged in: HTTP {$sLogin['code']}\n";

// 3. Teacher starts live class with NO meeting link (100% automatic)
echo "[3] Teacher starts live class with BLANK meeting link (Auto-Generate Test)...\n";
$tStart = makeRequest("$baseUrl/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code'  => 'AIS 201',
    'state'        => 'start',
    'topic'        => 'Automated Room and Anti-Bypass Testing Lecture',
    'agenda'       => 'Verifying dedicated auto-room and real-time leave detection.',
    'platform'     => 'google_meet',
    'meeting_link' => '', // BLANK -> must auto-generate!
    'grace_period' => 15
], $teacherCookie);

assert(!empty($tStart['data']['success']), "Failed to start live class: " . json_encode($tStart['data']));
$liveSession = $tStart['data']['live_session'];
$autoLink = $liveSession['meeting_link'];
$sessionCode = $liveSession['session_code'];
echo "    Auto-generated Meet URL: $autoLink\n";
echo "    Session Code: $sessionCode\n";
assert(preg_match('#^https://meet\.google\.com/[a-z]{3}-[a-z]{4}-[a-z]{3}$#', $autoLink), "Meeting link does not follow official Google Meet 3-4-3 format: $autoLink");
echo "    PASS: Room automatically provided with authentic 3-4-3 format ($autoLink)!\n";

// 4. Student joins presence
echo "[4] Student joins online presence in ELMS...\n";
$sJoin = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code'  => 'AIS 201'
], $studentCookie);
assert(!empty($sJoin['data']['success']), "Student presence join failed");
echo "    Student presence active. is_online: " . ($sJoin['data']['is_online'] ? 'TRUE' : 'FALSE') . "\n";

// 5. Student sends 1 heartbeat
sleep(1);
echo "[5] Student sends Keepalive Heartbeat...\n";
$sHb = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $studentCookie);
assert(!empty($sHb['data']['success']), "Student heartbeat failed");
echo "    Heartbeat OK. Active duration: {$sHb['data']['duration_seconds']}s\n";

// 6. Teacher inspects roster -> student must be 'In GMeet'
echo "[6] Teacher checks roster...\n";
$tRoster1 = makeRequest("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$records1 = $tRoster1['data']['roster'] ?? $tRoster1['data']['records'] ?? [];
$studentRec1 = null;
foreach ($records1 as $r) {
    if (($r['student_number'] ?? '') === '2024-00192') $studentRec1 = $r;
}
assert($studentRec1 !== null, "Student not found on teacher roster");
assert(!empty($studentRec1['is_online']), "Student should be marked online");
echo "    Roster check: Student " . ($studentRec1['formatted_name'] ?? $studentRec1['full_name']) . " is ONLINE (In GMeet)\n";

// 7. Student leaves online class (simulating tab close / Meet close)
echo "[7] Student closes Google Meet / leaves class (Anti-Bypass Realtime Leave Test)...\n";
$sLeave = makeRequest("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code'   => $sessionCode,
    'student_number' => '2024-00192'
], $studentCookie);
assert(!empty($sLeave['data']['success']), "Leave call failed");
echo "    Leave registered. Server response: {$sLeave['data']['message']}, leave_count: {$sLeave['data']['leave_count']}\n";

// 8. Teacher immediately polls roster -> student must now be 'Left / Offline' and leave_count = 1
echo "[8] Teacher checks roster immediately after student leave...\n";
$tRoster2 = makeRequest("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', null, $teacherCookie);
$records2 = $tRoster2['data']['roster'] ?? $tRoster2['data']['records'] ?? [];
$studentRec2 = null;
foreach ($records2 as $r) {
    if (($r['student_number'] ?? '') === '2024-00192') $studentRec2 = $r;
}
assert($studentRec2 !== null, "Student not found on roster after leave");
assert(empty($studentRec2['is_online']), "Student should now be OFFLINE / Left");
assert(($studentRec2['leave_count'] ?? 0) >= 1, "Leave count should be >= 1");
echo "    Roster check: Student status: " . ($studentRec2['is_online'] ? 'Online' : 'Left / Offline') . "\n";
echo "    Leave Count: {$studentRec2['leave_count']}x Left\n";
echo "    Frozen Duration: {$studentRec2['duration_seconds']}s\n";
echo "    PASS: Real-time leave detection confirmed!\n";

// 9. Clean up: Teacher ends live class
echo "[9] Teacher ends live class session...\n";
$tEnd = makeRequest("$baseUrl/api/elms.php?action=toggle_live_class", 'POST', [
    'course_code' => 'AIS 201',
    'state'       => 'end'
], $teacherCookie);
assert(!empty($tEnd['data']['success']), "Failed to end live class");
echo "    Live session ended cleanly.\n";

// 10. Student polls courses -> session must be marked ended (is_live_for_me = false)
echo "[10] Student polls courses after teacher ended class...\n";
$sCourses = makeRequest("$baseUrl/api/elms.php?action=get_courses", 'GET', null, $studentCookie);
assert(!empty($sCourses['data']['success']), "Student get_courses failed");
$foundAis = null;
foreach ($sCourses['data']['courses'] as $sc) {
    if ($sc['code'] === 'AIS 201') $foundAis = $sc;
}
assert($foundAis !== null, "AIS 201 not found");
assert(empty($foundAis['is_live_for_me']), "AIS 201 should no longer be live for student");
assert(empty($foundAis['live_session']['is_active']), "live_session should be inactive");
echo "    Student courses polling confirms class is ended! (is_live_for_me: " . ($foundAis['is_live_for_me'] ? 'true' : 'false') . ", is_active: " . ($foundAis['live_session']['is_active'] ? 'true' : 'false') . ")\n";
echo "    PASS: Student auto-termination trigger confirmed!\n";

@unlink($teacherCookie);
@unlink($studentCookie);

echo "\n=======================================================\n";
echo "ALL TESTS PASSED: AUTO ROOM AND REALTIME LEAVE WORKING!\n";
echo "=======================================================\n";
