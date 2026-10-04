<?php
$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['identifier' => '2024001', 'password' => 'Password123!']));
$res = curl_exec($ch);
echo substr($res, 0, 800);
