<?php
$apiKey = 'plugnmeet';
$apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
$serverUrl = 'https://demo.plugnmeet.com';

function callPnm($endpoint, $data = []) {
    global $apiKey, $apiSecret, $serverUrl;
    $payload = json_encode($data);
    $sig = hash_hmac('sha256', $payload, $apiSecret);
    $ch = curl_init("$serverUrl$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "API-KEY: $apiKey",
        "HASH-SIGNATURE: $sig"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $code, 'body' => json_decode($res, true)];
}

$testRoomId = 'npc-test-active-' . time();
$create = callPnm('/auth/room/create', [
    'room_id' => $testRoomId,
    'empty_timeout' => 3600,
    'metadata' => [
        'room_title' => 'Test Room',
        'room_features' => ['room_duration' => 0]
    ]
]);

$info = callPnm('/auth/room/getActiveRoomInfo', ['room_id' => $testRoomId]);

echo "CREATE:\n" . json_encode($create, JSON_PRETTY_PRINT) . "\n";
echo "ACTIVE ROOM INFO:\n" . json_encode($info, JSON_PRETTY_PRINT) . "\n";
