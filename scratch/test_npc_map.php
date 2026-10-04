<?php
function testPage($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'html' => $html];
}

$sCookie = tempnam(sys_get_temp_dir(), 's_');
$tCookie = tempnam(sys_get_temp_dir(), 't_');
$aCookie = tempnam(sys_get_temp_dir(), 'a_');

// 1. Student
testPage('http://127.0.0.1:8000/dev_login.php?role=student', $sCookie);
$sDash = testPage('http://127.0.0.1:8000/student/index.php', $sCookie);
echo 'Student Dash Code: ' . $sDash['code'] . ', Has NPC Map: ' . (str_contains($sDash['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";
echo 'Student Dash Has Settings: ' . (str_contains($sDash['html'], 'Settings') ? 'YES' : 'NO') . "\n";
$sMap = testPage('http://127.0.0.1:8000/student/campus_map.php', $sCookie);
echo 'Student Map Code: ' . $sMap['code'] . ', Has NPC Map: ' . (str_contains($sMap['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";
echo 'Student Map Has Mobile Find Prof: ' . (str_contains($sMap['html'], 'btn-open-faculty-mobile') ? 'YES' : 'NO') . "\n";

// 2. Teacher
testPage('http://127.0.0.1:8000/dev_login.php?role=teacher', $tCookie);
$tDash = testPage('http://127.0.0.1:8000/teacher/index.php', $tCookie);
echo 'Teacher Dash Code: ' . $tDash['code'] . ', Has NPC Map: ' . (str_contains($tDash['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";
$tMap = testPage('http://127.0.0.1:8000/teacher/campus_map.php', $tCookie);
echo 'Teacher Map Code: ' . $tMap['code'] . ', Has NPC Map: ' . (str_contains($tMap['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";
$tBlock = testPage('http://127.0.0.1:8000/student/campus_map.php', $tCookie);
echo 'Teacher -> Student Map blocked (Code 302): ' . $tBlock['code'] . "\n";

// 3. Admin
testPage('http://127.0.0.1:8000/dev_login.php?role=admin', $aCookie);
$aDash = testPage('http://127.0.0.1:8000/admin/index.php', $aCookie);
echo 'Admin Dash Code: ' . $aDash['code'] . ', Has NPC Map: ' . (str_contains($aDash['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";
$aMap = testPage('http://127.0.0.1:8000/admin/campus_map.php', $aCookie);
echo 'Admin Map Code: ' . $aMap['code'] . ', Has NPC Map: ' . (str_contains($aMap['html'], 'NPC Map') ? 'YES' : 'NO') . "\n";

@unlink($sCookie); @unlink($tCookie); @unlink($aCookie);
