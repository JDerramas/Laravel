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
    return ['status' => $code, 'body' => $res];
}

$endpoints = [
    '/auth/room/isRoomActive' => ['room_id' => 'test-room-123'],
    '/auth/room/fetchParticipants' => ['room_id' => 'test-room-123'],
    '/auth/room/getActiveRoomInfo' => ['room_id' => 'test-room-123'],
    '/auth/room/getActiveRoomsInfo' => new stdClass(),
    '/auth/room/getPastRooms' => new stdClass(),
];

foreach ($endpoints as $ep => $d) {
    $r = callPnm($ep, $d);
    echo "$ep => [{$r['status']}] {$r['body']}\n";
}
