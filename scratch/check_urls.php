<?php
$ch = curl_init('http://localhost:8000/api/elms.php?action=get_courses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "api/elms.php HTTP $code, len: " . strlen($body) . "\n";

$ch2 = curl_init('http://localhost:8000/login.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$body2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
echo "login.php HTTP $code2, len: " . strlen($body2) . "\n";
