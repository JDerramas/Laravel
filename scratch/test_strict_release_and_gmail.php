<?php
/**
 * Test script for:
 * 1. Strict single-host release enforcement in api/campus_map.php
 * 2. Gmail auto-login without password in login.php
 */

$baseUrl = 'http://127.0.0.1:8000';

function callApi($url, $method = 'GET', $data = null, $cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
    }
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    // Extract cookies
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
    $newCookies = [];
    foreach ($matches[1] as $item) {
        parse_str($item, $cookie);
        $newCookies = array_merge($newCookies, $cookie);
    }
    $cookieStr = '';
    foreach ($newCookies as $k => $v) {
        $cookieStr .= "$k=$v; ";
    }

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'cookies' => trim($cookieStr)
    ];
}

echo "========================================\n";
echo "TEST 1: Gmail Auto-Login (No Password Needed)\n";
echo "========================================\n";

// 1. Submit Gmail via POST to login.php WITHOUT password
$gmail = 'juan.delacruz.test@gmail.com';
$loginRes = callApi("{$baseUrl}/login.php", 'POST', ['identifier' => $gmail, 'password' => '']);
echo "HTTP Code: " . $loginRes['code'] . "\n";
if (strpos($loginRes['headers'], 'Location:') !== false) {
    preg_match('/Location:\s*([^\r\n]+)/', $loginRes['headers'], $m);
    echo "SUCCESS: Auto-login redirected to: " . ($m[1] ?? 'unknown') . "\n";
    echo "Cookies received: " . $loginRes['cookies'] . "\n";
} else {
    echo "FAILED to redirect. Body:\n" . substr($loginRes['body'], 0, 300) . "\n";
}

echo "\n========================================\n";
echo "TEST 2: Strict Single-Host Room Release\n";
echo "========================================\n";

// A. Login as Prof Alice
$profAliceEmail = 'prof.alice.npc@gmail.com';
$aliceLogin = callApi("{$baseUrl}/login.php", 'POST', ['identifier' => $profAliceEmail, 'password' => '']);
$aliceCookies = $aliceLogin['cookies'];
echo "Prof Alice logged in. Cookie: {$aliceCookies}\n";

// Claim Room 201 as Alice
$claimData = json_encode([
    'action' => 'use_room',
    'room_id' => 'rm-201',
    'activity_type' => 'Lecture Class',
    'announcement' => 'CS 101 Lecture by Prof Alice'
]);
$claimRes = callApi("{$baseUrl}/api/campus_map.php", 'POST', $claimData, $aliceCookies);
echo "Alice claimed Room 201. HTTP Code: {$claimRes['code']}\n";
echo "Claim response: {$claimRes['body']}\n";

// B. Login as Prof Bob
$profBobEmail = 'prof.bob.npc@gmail.com';
$bobLogin = callApi("{$baseUrl}/login.php", 'POST', ['identifier' => $profBobEmail, 'password' => '']);
$bobCookies = $bobLogin['cookies'];
echo "Prof Bob logged in. Cookie: {$bobCookies}\n";

// Bob attempts to release Alice's Room 201
$bobReleaseData = json_encode([
    'action' => 'release_room',
    'room_id' => 'rm-201'
]);
$bobReleaseRes = callApi("{$baseUrl}/api/campus_map.php", 'POST', $bobReleaseData, $bobCookies);
echo "Bob trying to release Alice's room. HTTP Code: {$bobReleaseRes['code']}\n";
echo "Bob response (Should be 403 Forbidden): {$bobReleaseRes['body']}\n";

// C. Login as Admin
$adminEmail = 'admin.system@navotaspolytechniccollege.edu.ph';
$adminLogin = callApi("{$baseUrl}/login.php", 'POST', ['identifier' => $adminEmail, 'password' => '']);
$adminCookies = $adminLogin['cookies'];
echo "Admin logged in. Cookie: {$adminCookies}\n";

// Admin attempts to release Alice's room (Should ALSO be rejected because only the claiming host can release!)
$adminReleaseRes = callApi("{$baseUrl}/api/campus_map.php", 'POST', $bobReleaseData, $adminCookies);
echo "Admin trying to release Alice's room. HTTP Code: {$adminReleaseRes['code']}\n";
echo "Admin response (Should be 403 Forbidden): {$adminReleaseRes['body']}\n";

// D. Now Alice herself releases Room 201
$aliceReleaseRes = callApi("{$baseUrl}/api/campus_map.php", 'POST', $bobReleaseData, $aliceCookies);
echo "Alice releasing her own room. HTTP Code: {$aliceReleaseRes['code']}\n";
echo "Alice release response (Should be 200 OK): {$aliceReleaseRes['body']}\n";

echo "\n========================================\n";
echo "ALL TESTS COMPLETED!\n";
echo "========================================\n";
