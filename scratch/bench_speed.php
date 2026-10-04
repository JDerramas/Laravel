<?php
$cookie = __DIR__ . '/bench_cookie.txt';
@unlink($cookie);

function fetchUrl($url, $cookie) {
    $t0 = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $dt = microtime(true) - $t0;
    return ['code' => $code, 'time' => round($dt, 4), 'len' => strlen($body)];
}

echo "1. Teacher Login: ";
$r = fetchUrl('http://localhost:8000/dev_login.php?role=teacher', $cookie);
echo "{$r['code']} in {$r['time']}s\n";

echo "2. Teacher Courses: ";
$r = fetchUrl('http://localhost:8000/teacher/courses.php', $cookie);
echo "{$r['code']} in {$r['time']}s (length: {$r['len']})\n";

echo "3. Switch to Student: ";
$r = fetchUrl('http://localhost:8000/switch_portal.php?to=student', $cookie);
echo "{$r['code']} in {$r['time']}s\n";

echo "4. Student Dashboard: ";
$r = fetchUrl('http://localhost:8000/student/index.php', $cookie);
echo "{$r['code']} in {$r['time']}s (length: {$r['len']})\n";

echo "5. Student Courses: ";
$r = fetchUrl('http://localhost:8000/student/courses.php', $cookie);
echo "{$r['code']} in {$r['time']}s (length: {$r['len']})\n";

@unlink($cookie);
