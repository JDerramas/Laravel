<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_helper.php';

echo "=== 1. Testing getJsConfig() ===\n";
$config = getJsConfig();
print_r($config);

echo "\n=== 2. Testing verifySupabaseToken() with local token ===\n";
$mockPayload = [
    'email' => 'juan.delacruz@navotaspolytechniccollege.edu.ph',
    'name' => 'Juan Dela Cruz',
    'issued_at' => time()
];
$base64Token = base64_encode(json_encode($mockPayload));
$verified = verifySupabaseToken($base64Token);
echo "Verified User:\n";
print_r($verified);

echo "\n=== 3. Testing verifySupabaseToken() with JWT format ===\n";
$header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$jwtPayload = base64_encode(json_encode([
    'sub' => 'usr-jwt-test-123',
    'email' => 'prof.reyes@navotaspolytechniccollege.edu.ph',
    'name' => 'Prof. Maria Reyes',
    'user_metadata' => ['full_name' => 'Prof. Maria Reyes']
]));
$fakeSignature = base64_encode('sig');
$jwtToken = "$header.$jwtPayload.$fakeSignature";
$verifiedJwt = verifySupabaseToken($jwtToken);
echo "Verified JWT User:\n";
print_r($verifiedJwt);

echo "\n=== 4. Testing End-to-End set_session.php via cURL (Local Web Server) ===\n";
$ch = curl_init("http://127.0.0.1:8000/set_session.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $jwtToken]));
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP $httpCode Response: $res\n";

// Check if prof.reyes was auto-provisioned as teacher in users table
$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, email, role, full_name FROM users WHERE email = ?");
$stmt->execute(['prof.reyes@navotaspolytechniccollege.edu.ph']);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Auto-provisioned DB record for prof.reyes:\n";
print_r($userRow);

echo "\n=== 5. Testing Student Auto-Provisioning in set_session.php ===\n";
$stdJwtPayload = base64_encode(json_encode([
    'sub' => 'usr-jwt-std-456',
    'email' => '2024-00888@navotaspolytechniccollege.edu.ph',
    'name' => 'Pedro Penduko',
    'user_metadata' => ['full_name' => 'Pedro Penduko']
]));
$stdJwtToken = "$header.$stdJwtPayload.$fakeSignature";

$ch2 = curl_init("http://127.0.0.1:8000/set_session.php");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['access_token' => $stdJwtToken]));
$res2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
echo "HTTP $httpCode2 Response: $res2\n";

$stmtStd = $pdo->prepare("SELECT id, user_id, student_number, full_name, email, program, section FROM students WHERE email = ?");
$stmtStd->execute(['2024-00888@navotaspolytechniccollege.edu.ph']);
$stdRow = $stmtStd->fetch(PDO::FETCH_ASSOC);
echo "Auto-provisioned students table record for 2024-00888:\n";
print_r($stdRow);
