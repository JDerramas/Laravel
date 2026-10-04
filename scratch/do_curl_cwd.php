<?php
$ch = curl_init('http://localhost:8000/test_cwd.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP $code\nBody: $body\n";
