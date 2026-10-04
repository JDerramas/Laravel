<?php
$apiKey = 'plugnmeet';
$apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
$serverUrl = 'https://demo.plugnmeet.com';

$roomInfo = [
    'room_id' => 'npc-demo-test-room-' . time(),
    'empty_timeout' => 7200,
    'metadata' => [
        'room_title' => 'AIS 201 · Online Class (NPC ELMS)',
        'welcome_message' => 'Welcome to NPC Online Class!',
        'room_features' => ['room_duration' => 0]
    ]
];
$p = json_encode($roomInfo);
$sig = hash_hmac('sha256', $p, $apiSecret);

$ch = curl_init("$serverUrl/auth/room/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $p);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "API-KEY: $apiKey", "HASH-SIGNATURE: $sig"]);
curl_exec($ch);
curl_close($ch);

$join = json_encode(['room_id' => $roomInfo['room_id'], 'user_info' => ['is_admin' => true, 'name' => 'Prof. Santos [Faculty]', 'user_id' => 'fac_santos']]);
$sig2 = hash_hmac('sha256', $join, $apiSecret);
$ch2 = curl_init("$serverUrl/auth/room/getJoinToken");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $join);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "API-KEY: $apiKey", "HASH-SIGNATURE: $sig2"]);
$res = json_decode(curl_exec($ch2), true);
curl_close($ch2);

$css = '
#main-header .left > div:first-child,
#main-header .left img,
#main-header .left svg,
img[alt="logo"],
.logo,
.timer,
[class*="timer"],
.time-counter {
  display: none !important;
  visibility: hidden !important;
  width: 0 !important;
  height: 0 !important;
  opacity: 0 !important;
  pointer-events: none !important;
}
';
$dataUri = 'data:text/css;base64,' . base64_encode($css);
$customDesign = json_encode([
    'custom_css_url' => $dataUri,
    'primary_color' => '#059669',
    'secondary_color' => '#0284c7'
], JSON_UNESCAPED_SLASHES);

$finalUrl = "https://demo.plugnmeet.com/?access_token=" . $res['token'] . "&custom_design=" . rawurlencode($customDesign);
echo "TOKEN_URL: " . $finalUrl . PHP_EOL;
