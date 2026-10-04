<?php
$url = 'https://burst-digital-reasons-smithsonian.trycloudflare.com/api/campus_map.php?action=get_all';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Tunnel HTTP: {$code}\n";
$json = json_decode($res, true);
if ($json && $json['success']) {
    echo "Tunnel working perfectly! Total rooms: " . count($json['rooms']) . "\n";
} else {
    echo "Tunnel response: " . substr($res, 0, 150) . "\n";
}
