<?php
/**
 * End-to-end presence lifecycle test:
 * 1. Login as student
 * 2. Join the active ELMS session (NPC-AIS201-2026-09-11)
 * 3. Send heartbeats to go online
 * 4. Fetch teacher roster and verify student appears with timer + leave count
 */
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie_e2e.txt';
@unlink($cookieFile);

function httpReq($url, $method = 'GET', $data = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $res];
}

$sessionCode = 'NPC-AIS201-2026-09-11';

echo "=== STEP 1: Login as student ===\n";
$login = httpReq("$baseUrl/dev_login.php?role=student", 'GET', [], $cookieFile);
echo "Login HTTP: {$login['code']}\n";

echo "\n=== STEP 2: Join presence session ===\n";
$join = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS201'
], $cookieFile);
echo "Join: {$join['body']}\n";
$jData = json_decode($join['body'], true);

echo "\n=== STEP 3: Send heartbeat (student still online) ===\n";
sleep(2);
$hb = httpReq("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', [
    'session_code' => $sessionCode
], $cookieFile);
echo "Heartbeat: {$hb['body']}\n";

echo "\n=== STEP 4: Fetch live roster (teacher perspective) ===\n";
$roster = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', [], $cookieFile);
echo "Roster: {$roster['body']}\n";
$rData = json_decode($roster['body'], true);

if (!empty($rData['roster'])) {
    echo "\n=== ROSTER RESULTS ===\n";
    echo "Online count: " . ($rData['online_count'] ?? 0) . "\n";
    echo "Left/disconnected count: " . ($rData['left_count'] ?? 0) . "\n";
    echo "Present count: " . ($rData['present_count'] ?? 0) . "\n";
    echo "Total enrolled: " . ($rData['total_enrolled'] ?? 0) . "\n\n";
    
    foreach ($rData['roster'] as $i => $r) {
        echo "Student #" . ($i+1) . ":\n";
        echo "  Name: " . ($r['formatted_name'] ?? $r['full_name'] ?? 'N/A') . "\n";
        echo "  Student Number: " . ($r['student_number'] ?? 'N/A') . "\n";
        echo "  Is Online: " . ($r['is_online'] ? 'YES 🟢' : 'NO 🔴') . "\n";
        echo "  Duration (seconds): " . ($r['duration_seconds'] ?? 0) . "\n";
        echo "  Leave Count: " . ($r['leave_count'] ?? 0) . "\n";
        echo "  Status: " . ($r['status'] ?? 'N/A') . "\n";
        echo "  Check-in: " . ($r['check_in_at'] ?? 'N/A') . "\n";
        echo "  Verified Via: " . ($r['verified_via'] ?? 'N/A') . "\n\n";
    }
}

echo "\n=== STEP 5: Simulate leave ===\n";
$leave = httpReq("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode,
    'student_number' => $jData['student_number'] ?? '2024-00192'
], $cookieFile);
echo "Leave: {$leave['body']}\n";

echo "\n=== STEP 6: Rejoin (simulating reconnect) ===\n";
$rejoin = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS201'
], $cookieFile);
echo "Rejoin: {$rejoin['body']}\n";
$rjData = json_decode($rejoin['body'], true);

echo "\n=== STEP 7: Final roster check ===\n";
$roster2 = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', [], $cookieFile);
$r2Data = json_decode($roster2['body'], true);

if (!empty($r2Data['roster'])) {
    echo "Online count: " . ($r2Data['online_count'] ?? 0) . "\n";
    echo "Left count: " . ($r2Data['left_count'] ?? 0) . "\n";
    foreach ($r2Data['roster'] as $r) {
        echo "  " . ($r['formatted_name'] ?? 'N/A') . " | Online: " . ($r['is_online'] ? 'YES' : 'NO') . " | Duration: " . ($r['duration_seconds'] ?? 0) . "s | Leaves: " . ($r['leave_count'] ?? 0) . "\n";
    }
}

echo "\n=== ALL E2E PRESENCE TESTS COMPLETE ===\n";
@unlink($cookieFile);
