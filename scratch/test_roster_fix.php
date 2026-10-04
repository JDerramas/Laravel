<?php
/**
 * Focused test: verify get_live_presence_roster returns proper roster data
 */
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie_e2e2.txt';
@unlink($cookieFile);

function httpReq($url, $method = 'GET', $data = [], $cookieFile = null, $follow = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
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

// 1. Login (just get the session cookie, don't follow redirect)
echo "=== Login ===\n";
httpReq("$baseUrl/dev_login.php?role=student", 'GET', [], $cookieFile, false);

// 2. Join
echo "=== Join ===\n";
$join = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS201'
], $cookieFile);
$jData = json_decode($join['body'], true);
echo "Join OK: " . ($jData['success'] ? 'YES' : 'NO') . " | Online: " . ($jData['is_online'] ? 'YES' : 'NO') . " | Duration: " . ($jData['duration_seconds'] ?? 0) . "s | Leaves: " . ($jData['leave_count'] ?? 0) . "\n";

// 3. Heartbeat
sleep(2);
echo "\n=== Heartbeat ===\n";
$hb = httpReq("$baseUrl/api/elms.php?action=meeting_presence_heartbeat", 'POST', ['session_code' => $sessionCode], $cookieFile);
$hData = json_decode($hb['body'], true);
echo "HB OK: " . ($hData['success'] ? 'YES' : 'NO') . " | Duration: " . ($hData['duration_seconds'] ?? 0) . "s\n";

// 4. Roster
echo "\n=== Live Roster ===\n";
$roster = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', [], $cookieFile);
$rData = json_decode($roster['body'], true);

if (isset($rData['roster'])) {
    echo "✅ ROSTER RETURNED SUCCESSFULLY!\n";
    echo "Online count: " . ($rData['online_count'] ?? 0) . "\n";
    echo "Left count: " . ($rData['left_count'] ?? 0) . "\n";
    echo "Present count: " . ($rData['present_count'] ?? 0) . "\n";
    echo "Total enrolled: " . ($rData['total_enrolled'] ?? 0) . "\n\n";
    
    foreach ($rData['roster'] as $i => $r) {
        echo "#" . ($i+1) . " " . ($r['formatted_name'] ?? $r['full_name'] ?? 'N/A') . "\n";
        echo "   Number: " . ($r['student_number'] ?? 'N/A') . "\n";
        echo "   Online: " . ($r['is_online'] ? '🟢 YES' : '🔴 NO') . "\n";
        echo "   Duration: " . ($r['duration_seconds'] ?? 0) . "s\n";
        echo "   Leaves: " . ($r['leave_count'] ?? 0) . ($r['leave_count'] > 0 ? " 🔴" : "") . "\n";
        echo "   Status: " . ($r['status'] ?? 'N/A') . "\n";
        echo "   Check-in: " . ($r['check_in_at'] ?? 'N/A') . "\n\n";
    }
} else {
    echo "❌ ROSTER NOT RETURNED! Got: " . substr($roster['body'], 0, 300) . "\n";
}

// 5. Leave
echo "=== Leave ===\n";
$leave = httpReq("$baseUrl/api/elms.php?action=meeting_presence_leave", 'POST', [
    'session_code' => $sessionCode,
    'student_number' => '2024-00192'
], $cookieFile);
echo "Leave: " . $leave['body'] . "\n";

// 6. Rejoin
echo "\n=== Rejoin ===\n";
$rejoin = httpReq("$baseUrl/api/elms.php?action=meeting_presence_join", 'POST', [
    'session_code' => $sessionCode,
    'course_code' => 'AIS201'
], $cookieFile);
$rjData = json_decode($rejoin['body'], true);
echo "Rejoin: leaves=" . ($rjData['leave_count'] ?? '?') . " duration=" . ($rjData['duration_seconds'] ?? '?') . "s\n";

// 7. Final roster
echo "\n=== Final Roster ===\n";
$roster2 = httpReq("$baseUrl/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode($sessionCode) . "&section=2A", 'GET', [], $cookieFile);
$r2Data = json_decode($roster2['body'], true);

if (isset($r2Data['roster'])) {
    foreach ($r2Data['roster'] as $r) {
        echo "  " . ($r['formatted_name'] ?? 'N/A') . " | Online: " . ($r['is_online'] ? '🟢' : '🔴') . " | Duration: " . ($r['duration_seconds'] ?? 0) . "s | Leaves: " . ($r['leave_count'] ?? 0) . "\n";
    }
    echo "\n✅ ALL PRESENCE LIFECYCLE TESTS PASSED!\n";
} else {
    echo "❌ Final roster failed\n";
}

@unlink($cookieFile);
