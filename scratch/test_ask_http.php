<?php
// Login student first
$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['identifier' => '2024-00192']));
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
curl_close($ch);

// Read student courses to extract CSRF token from page meta
$ch = curl_init('http://127.0.0.1:8000/student/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
$html = curl_exec($ch);
curl_close($ch);

preg_match('/window\.__CSRF__\s*=\s*"([^"]+)"/', $html, $m);
$csrf = $m[1] ?? '';
if (!$csrf) {
    preg_match('/meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $html, $m);
    $csrf = $m[1] ?? '';
}

echo "Captured CSRF Token: $csrf\n";

// Call ask.php
$ch = curl_init('http://127.0.0.1:8000/api/ask.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-CSRF-Token: ' . $csrf
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['question' => 'ano ang mga grades ko?']));
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
$aiRes = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "AI Endpoint Response:\n$aiRes\n";
