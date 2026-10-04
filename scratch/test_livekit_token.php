<?php
function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function createLiveKitToken($apiKey, $apiSecret, $room, $identity, $name, $isAdmin = false, $duration = 86400) {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'exp' => $now + $duration,
        'iss' => $apiKey,
        'sub' => $identity,
        'nbf' => $now - 5,
        'video' => [
            'room' => $room,
            'roomJoin' => true,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'roomAdmin' => $isAdmin
        ],
        'name' => $name
    ], JSON_UNESCAPED_SLASHES);

    $encodedHeader = base64UrlEncode($header);
    $encodedPayload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $apiSecret, true);
    $encodedSignature = base64UrlEncode($signature);

    return "$encodedHeader.$encodedPayload.$encodedSignature";
}

$token = createLiveKitToken("npc_elms_key", "npc_elms_secret_2026", "NPC-AIS201", "stu_202400192", "Lovi Student (2024-00192)");
echo "GENERATED TOKEN:\n" . $token . "\n";
