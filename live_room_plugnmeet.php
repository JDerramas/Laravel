<?php
/**
 * live_room.php — Navotas Polytechnic College (NPC) ELMS
 * Synchronous Virtual Classroom (PlugNmeet Integration)
 *
 * Uses the proven, feature-rich PlugNmeet video conference platform:
 * - Direct WebRTC audio/video, screensharing, interactive whiteboard, live chat
 * - Clean branding: PlugNmeet logo and countdown timer completely hidden
 * - Anti-Kupal locked student identity (cryptographically signed in join token)
 * - Unlimited room duration (room_duration: 0, no timer/cutoffs)
 * - Automatic return to NPC ELMS on leave/logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? 'NPC User';
$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';
$studentNumber = $_SESSION['student_number'] ?? '2024-00192';
$studentSection = $_SESSION['section'] ?? '2A';
$studentProgram = $_SESSION['program'] ?? 'AIS';

if (empty($userEmail)) {
    header('Location: /login.php');
    exit;
}

$sessionCode = trim($_GET['session_code'] ?? '');
$courseCode  = trim($_GET['course_code'] ?? '');
$roomId      = trim($_GET['room_id'] ?? '');

// Load course details from elms_courses.json
$elmsFile = __DIR__ . '/backend/elms_courses.json';
$rawElms = @file_get_contents($elmsFile);
$elms = json_decode($rawElms, true) ?: ['courses' => []];
$course = null;

if (!empty($elms['courses'])) {
    foreach ($elms['courses'] as $c) {
        if (($courseCode && $c['code'] === $courseCode) || 
            ($sessionCode && ($c['live_session']['session_code'] ?? '') === $sessionCode) ||
            ($roomId && ($c['live_session']['room_id'] ?? '') === $roomId)) {
            $course = $c;
            break;
        }
    }
}

if (!$course) {
    $course = [
        'code' => $courseCode ?: 'NPC-CLASS',
        'title' => 'Synchronous Virtual Lecture',
        'section' => $studentSection,
        'instructor' => 'Assigned Faculty',
        'live_session' => [
            'topic' => 'Synchronous Virtual Classroom',
            'session_code' => $sessionCode ?: ('NPC-' . ($courseCode ?: 'GEN') . '-' . date('Y-m-d')),
            'room_id' => $roomId ?: ('NPC-ROOM-' . substr(md5($userEmail . time()), 0, 8))
        ]
    ];
}

$live = $course['live_session'] ?? [];
$actualSessionCode = $live['session_code'] ?? $sessionCode;
$actualRoomId = $live['room_id'] ?? $roomId;
$topic = $live['topic'] ?? ($course['title'] . ' Live Class');
$isAdmin = in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar']);

// Locked student/teacher identity (Anti-Kupal)
$participantName = ($userRole === 'student') 
    ? ($userName . " (" . ($studentNumber ?: 'Student') . ")") 
    : ("Prof. " . $userName . " [Instructor]");

$participantId = ($userRole === 'student') 
    ? ("stu_" . preg_replace('/[^a-zA-Z0-9]/', '', $studentNumber ?: $userEmail)) 
    : ("fac_" . preg_replace('/[^a-zA-Z0-9]/', '', $userEmail));

// Build deterministic PlugNmeet Room ID
$plugRoomId = preg_replace('/[^A-Za-z0-9_-]/', '', "NPC-ELMS-{$course['code']}-{$course['section']}-" . substr(md5($actualSessionCode ?: $course['code']), 0, 8));

// PlugNmeet Server API Credentials — Local Docker Server
$apiKey    = 'plugnmeet';
$apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
$serverUrl = 'http://localhost:8085';  // Local PlugNMeet Docker server - DISABLED, using LiveKit direct instead

// Construct return URL for when user leaves the meeting
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$returnPath = ($userRole === 'student') ? '/student/courses.php' : '/teacher/courses.php';
$logoutUrl = $protocol . $host . $returnPath;

// 1. Create room on PlugNmeet server (room_duration = 0 ensures no timer is displayed)
$roomInfo = [
    'room_id' => $plugRoomId,
    'empty_timeout' => 7200,
    'metadata' => [
        'room_title' => $course['code'] . ' · ' . $course['title'] . ' (NPC ELMS)',
        'welcome_message' => 'Welcome to Navotas Polytechnic College Virtual Classroom! Unlimited Session.',
        'logout_url' => $logoutUrl,
        'room_features' => [
            'allow_webcams' => true,
            'mute_on_start' => false,
            'allow_screen_share' => true,
            'admin_only_webcams' => false,
            'allow_view_other_webcams' => true,
            'allow_view_other_users_list' => true,
            'room_duration' => 0, // ZERO = Completely disables the countdown timer component
            'allow_virtual_bg' => true,
            'allow_raise_hand' => true,
            'allow_reactions' => true,
            'chat_features' => ['is_allow' => true, 'is_allow_file_upload' => true],
            'shared_note_pad_features' => ['is_allow' => true],
            'whiteboard_features' => ['is_allow' => true],
            'recording_features' => ['is_allow' => true, 'is_allow_cloud' => true]
        ]
    ]
];

$createPayload = json_encode($roomInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$createSig = hash_hmac('sha256', $createPayload, $apiSecret);

$ch = curl_init("$serverUrl/auth/room/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $createPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "API-KEY: $apiKey",
    "HASH-SIGNATURE: $createSig"
]);
$createResult = curl_exec($ch);
$createError  = curl_error($ch);
curl_close($ch);

// 2. Get Join Token with the verified identity
$joinPayload = json_encode([
    'room_id' => $plugRoomId,
    'user_info' => [
        'is_admin' => $isAdmin,
        'name' => $participantName,
        'user_id' => $participantId
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$joinSig = hash_hmac('sha256', $joinPayload, $apiSecret);

$ch2 = curl_init("$serverUrl/auth/room/getJoinToken");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $joinPayload);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "API-KEY: $apiKey",
    "HASH-SIGNATURE: $joinSig"
]);
$joinResRaw = curl_exec($ch2);
$joinError  = curl_error($ch2);
curl_close($ch2);

$joinRes = json_decode($joinResRaw, true);
$accessToken = $joinRes['token'] ?? '';

if (!empty($accessToken)) {
    // CSS to completely suppress logo and timer
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

    // Redirect to PlugNmeet Demo Virtual Classroom with customized design
    $plugnmeetUrl = "$serverUrl/?access_token=" . rawurlencode($accessToken) . "&custom_design=" . rawurlencode($customDesign);
    header("Location: $plugnmeetUrl");
    exit;
}

// Fallback: If token generation failed, show friendly retry page
$debugInfo = '';
if (!empty($joinError)) {
    $debugInfo = 'cURL Error: ' . htmlspecialchars($joinError);
} elseif (!empty($joinResRaw)) {
    $decoded = json_decode($joinResRaw, true);
    $debugInfo = 'Server response: ' . htmlspecialchars($decoded['msg'] ?? $joinResRaw);
} else {
    $debugInfo = 'No response from server. Is the PlugNMeet Docker container running? (docker compose up -d in plugnmeet-server folder)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NPC Virtual Classroom — Connection Issue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="h-screen bg-slate-950 text-slate-100 flex items-center justify-center font-[Inter] p-4">
    <div class="max-w-lg w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl text-center space-y-5">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <span class="material-symbols-outlined text-[32px]">wifi_off</span>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Could Not Connect to Classroom</h2>
            <p class="text-sm text-slate-400 leading-relaxed mt-2">
                The virtual lecture room server is preparing your secure session. Please click below to retry joining.
            </p>
            <div class="mt-3 p-3 bg-slate-800 rounded-xl text-left">
                <p class="text-xs text-amber-400 font-mono break-all"><?= $debugInfo ?></p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
            <button onclick="location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-all shadow-lg shadow-emerald-900/30 inline-flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                Retry Connection
            </button>
            <a href="<?= htmlspecialchars($returnPath) ?>" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-sm font-semibold transition-all inline-flex items-center justify-center">
                Back to Courses
            </a>
        </div>
    </div>
</body>
</html>
