<?php
$cookieFile = __DIR__ . '/cookie_verify.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

function req($url, $method = 'GET', $data = [], $cookie = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Login as student
$login = req('http://localhost:8000/login.php', 'POST', ['identifier' => '2024001', 'password' => 'password123'], $cookieFile);

// Fetch live_room.php
$html = req('http://localhost:8000/live_room.php?course_code=AIS%20201', 'GET', [], $cookieFile);

echo "HTML Length: " . strlen($html) . "\n";
echo "Has NPC Logo: " . (str_contains($html, '/assets/img/npc-logo.png') ? "YES" : "NO") . "\n";
echo "Has PlugNmeet Logo: " . (str_contains($html, 'main-logo-light') || str_contains($html, 'main-logo-dark') ? "YES (Warning)" : "NO (Clean!)") . "\n";
echo "Has 58:57 or 60min countdown: " . (str_contains($html, '58:57') || str_contains($html, 'countdown') ? "YES (Warning)" : "NO (Clean!)") . "\n";
echo "Has Unli Time (No Limit): " . (str_contains($html, 'Unli Time (No Limit)') ? "YES" : "NO") . "\n";
echo "Has Active Attendance: " . (str_contains($html, 'Active Attendance') ? "YES" : "NO") . "\n";
echo "Has Locked Student Name: " . (str_contains($html, 'Lovi Student') ? "YES" : "NO") . "\n";
echo "Has LiveKit Port 7880: " . (str_contains($html, '7880') ? "YES" : "NO") . "\n";

if (file_exists($cookieFile)) @unlink($cookieFile);
