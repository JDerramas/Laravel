<?php
$baseUrl = 'http://localhost:8000';
$teacherCookie = tempnam(sys_get_temp_dir(), 'tchr_');
$ch = curl_init("{$baseUrl}/dev_login.php?role=teacher");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $teacherCookie);
curl_exec($ch);
curl_close($ch);

$ch = curl_init("{$baseUrl}/api/elms.php?action=get_live_presence_roster&session_code=" . urlencode("NPC-AIS201-2026-09-18") . "&section=2A");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $teacherCookie);
$res = curl_exec($ch);
curl_close($ch);

echo "RAW RES:\n" . substr($res, 0, 300) . "\n...\n";
$data = json_decode($res, true);
echo "JSON ERROR: " . json_last_error_msg() . "\n";
echo "DEBUG MARKER: " . ($data['debug_marker'] ?? 'NOT FOUND') . "\n";
@unlink($teacherCookie);
