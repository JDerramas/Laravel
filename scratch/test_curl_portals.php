<?php
function testUrl($url, $cookie) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

// 1. Login as student
$ch = curl_init('http://127.0.0.1:8000/dev_login.php?role=student');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $matches);
$studentCookie = implode('; ', $matches[1]);

echo 'Student HTTP code for /student/campus_map.php: ' . testUrl('http://127.0.0.1:8000/student/campus_map.php', $studentCookie) . "\n";

// 2. Login as teacher
$ch = curl_init('http://127.0.0.1:8000/dev_login.php?role=teacher');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $matches);
$teacherCookie = implode('; ', $matches[1]);

echo 'Teacher HTTP code for /teacher/campus_map.php: ' . testUrl('http://127.0.0.1:8000/teacher/campus_map.php', $teacherCookie) . "\n";
echo 'Teacher HTTP code for /student/campus_map.php (should be 302 redirect block): ' . testUrl('http://127.0.0.1:8000/student/campus_map.php', $teacherCookie) . "\n";
