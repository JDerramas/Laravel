<?php
/**
 * includes/livekit_helper.php
 * Official LiveKit JWT AccessToken generator for PHP (Pure PHP, zero dependency)
 */

if (!function_exists('generateLiveKitAccessToken')) {
    function generateLiveKitAccessToken($apiKey, $apiSecret, $identity, $name, $roomName, $isAdmin = false, $metadata = []) {
        $now = time();
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $apiKey,
            'sub' => (string)$identity,
            'name' => (string)$name,
            'nbf' => $now - 10,
            'exp' => $now + (24 * 3600), // 24 hours validity
            'video' => [
                'room' => (string)$roomName,
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
                'roomAdmin' => (bool)$isAdmin
            ],
            'metadata' => is_string($metadata) ? $metadata : json_encode($metadata)
        ];

        $b64Url = function ($data) {
            return rtrim(strtr(base64_encode(is_string($data) ? $data : json_encode($data)), '+/', '-_'), '=');
        };

        $encodedHeader = $b64Url($header);
        $encodedPayload = $b64Url($payload);
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $apiSecret, true);
        $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
    }
}
