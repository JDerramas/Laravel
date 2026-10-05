<?php
require_once __DIR__ . '/../includes/db_helper.php';

// Create a local Google Auth handshake token for Lovi Student
$tokenPayload = json_encode([
    'provider' => 'google',
    'id' => 'usr-student-01',
    'email' => 'student2024001@navotaspolytechniccollege.edu.ph',
    'name' => 'Lovi Student',
    'picture' => 'https://lh3.googleusercontent.com/aida-public/default-avatar',
    'time' => time()
]);
$testToken = base64_encode($tokenPayload);

echo "Test Token: " . $testToken . "\n";

// Test verifySupabaseToken
$verified = verifySupabaseToken($testToken);
echo "\nverifySupabaseToken Result:\n";
print_r($verified);

// Test calling set_session.php via curl
$ch = curl_init('http://127.0.0.1:8000/set_session.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $testToken]));
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nHTTP Response ($httpCode):\n$res\n";
