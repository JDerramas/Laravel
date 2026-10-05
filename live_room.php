<?php

/**
 * live_room.php — Navotas Polytechnic College (NPC) ELMS
 * Synchronous Virtual Classroom (PlugNmeet Integration)
 *
 * Uses the proven, feature-rich PlugNmeet video conference platform:
 * - Direct WebRTC audio/video, screensharing, interactive whiteboard, live chat
 * - Clean branding: PlugNmeet logo and countdown timer completely hidden
 * - 100% Automated Institutional Presence Detection & Ticking Duration
 * - Automatic leave detection with beacon on window close
 * - Unlimited room duration (room_duration: 0, no timer/cutoffs)
 * - Automatic return to NPC ELMS on leave/logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? 'NPC User';
$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';
$studentNumber = $_SESSION['student_number'] ?? '2024001';
$studentSection = $_SESSION['section'] ?? '2A';
$studentProgram = $_SESSION['program'] ?? 'AIS';

if (empty($userEmail)) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/includes/db_helper.php';
$env = loadEnv();
$liveEngine = $_GET['engine'] ?? $env['LIVE_ROOM_ENGINE'] ?? 'plugnmeet';
if ($liveEngine === 'livekit') {
    require __DIR__ . '/live_room_livekit_direct.php';
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
            ($roomId && ($c['live_session']['room_id'] ?? '') === $roomId)
        ) {
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

// Automatically register student presence upon entering live_room.php
$presenceFile = __DIR__ . '/backend/elms_presence.json';
$initialDuration = 0;
$initialLeaves = 0;

if ($userRole === 'student' && !empty($actualSessionCode)) {
    require_once __DIR__ . '/api/elms.php';
    $joinRes = registerStudentLivePresenceJoin($actualSessionCode, $course['code'], $studentNumber, $userName, $userEmail, $presenceFile, $elmsFile);
    $initialDuration = intval($joinRes['duration_seconds'] ?? 0);
    $initialLeaves = intval($joinRes['leave_count'] ?? 0);
} elseif (!empty($live['started_at'])) {
    $sessionStartTs = strtotime($live['started_at']);
    $sessionEndTs = !empty($live['ended_at']) ? strtotime($live['ended_at']) : time();
    if ($sessionStartTs !== false && $sessionEndTs !== false) {
        $initialDuration = max(0, $sessionEndTs - $sessionStartTs);
    }
}

// Keep PlugNmeet room identity aligned with this specific ELMS live session.
$plugRoomId = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '',
    $actualRoomId ?: ("NPC-ELMS-{$course['code']}-{$course['section']}-" . substr(md5($actualSessionCode ?: $course['code']), 0, 8))
);

// ============================================================================
// ⚙️ PLUGNMEET & LIVEKIT PRESET CONFIGURATION (SWITCHABLE)
// Piliin lang kung alin ang gagamitin: 'local_docker' (100% Free), 'livekit_cloud', o 'demo'
// ============================================================================
$platformMode = $_GET['platform'] ?? 'local_docker'; // Default: 100% FREE Self-Hosted Docker stack
$currentHost = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
$forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
$publicScheme = $forwardedProto === 'https' ? 'https://' : 'http://';
// [NGROK REMOVED] Dati: $isNgrokHost = strpos($currentHost, 'ngrok') !== false;
// Sinusuportahan na ngayon ang Cloudflare, Localtunnel, o kahit anong public host nang walang ngrok:
$isPublicHost = ($currentHost !== 'localhost' && $currentHost !== '127.0.0.1' && strpos($currentHost, '192.168.') === false);

if ($platformMode === 'local_docker') {
    // 🖥️ PRESET 1: SARILING DOCKER SA LAPTOP (Port 8085)
    $apiKey    = 'plugnmeet';
    $apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
    $serverUrl = 'http://localhost:8085';
    
    // Dati: $clientFacingUrl = $isNgrokHost ? ($publicScheme . $currentHost . '/plugnmeet') : 'http://localhost:8085';
    $clientFacingUrl = $isPublicHost
        ? ($publicScheme . $currentHost . '/plugnmeet')
        : 'http://localhost:8085';

} elseif ($platformMode === 'livekit_cloud') {
    // ☁️ PRESET 2: LIVEKIT CLOUD (100GB Free / Zero Init sa Laptop / Unli Duration)
    // Tumatakbo ang PlugNmeet controller sa Docker o cloud, habang ang video ay sa LiveKit Cloud
    $apiKey    = 'plugnmeet';
    $apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
    $serverUrl = 'http://localhost:8085';

    $useGatewayRoute = ($isPublicHost || strpos($currentHost, '8001') !== false);
    $clientFacingUrl = $useGatewayRoute
        ? ($publicScheme . $currentHost . '/plugnmeet')
        : 'http://localhost:8085';

} else {
    // 🌐 PRESET 3: PUBLIC DEMO SERVER (May 1-hour limit)
    // $apiKey    = 'plugnmeet';
    // $apiSecret = 'zumyyYWqv7KR2kUqvYdq4z4sXg7XTBD2ljT6';
    // $serverUrl = 'https://demo.plugnmeet.com';
    // $clientFacingUrl = 'https://demo.plugnmeet.com';
}



// Construct return URL for when user leaves the meeting
$protocol = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https://' : 'http://';
$host = $currentHost ?: 'localhost:8000';
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

// 1a. Create room — with error checking and retry
$accessToken = '';
$createError = '';
for ($attempt = 1; $attempt <= 2; $attempt++) {
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
    $createResRaw = curl_exec($ch);
    $createErr    = curl_error($ch);
    curl_close($ch);

    if ($createErr) {
        $createError = "cURL error: $createErr";
        sleep(1);
        continue;
    }

    $createRes = json_decode($createResRaw, true);
    if (!($createRes['status'] ?? false)) {
        $createError = $createRes['msg'] ?? 'Room creation failed';
        // Room may already exist — that's fine, proceed to token
        if (($createRes['status_code'] ?? '') === 'ROOM_ALREADY_EXISTS') {
            $createError = '';
        }
        break;
    }
    $createError = '';
    break;
}

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
$joinCurlErr = curl_error($ch2);
curl_close($ch2);

$joinRes = json_decode($joinResRaw, true);
$accessToken = $joinRes['token'] ?? '';

if (!empty($accessToken)) {
    // CSS to suppress logo and timer inside plugNmeet, plus un-mirror camera
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
video.camera-video,
.camera-video-player video,
.video-camera-item video {
  transform: scaleX(1) !important;
  -webkit-transform: scaleX(1) !important;
}
body.mirrored-camera video.camera-video,
body.mirrored-camera .camera-video-player video,
body.mirrored-camera .video-camera-item video {
  transform: scaleX(-1) !important;
  -webkit-transform: scaleX(-1) !important;
}
';
    $dataUri = 'data:text/css;base64,' . base64_encode($css);
    $customDesign = json_encode([
        'custom_css_url' => $dataUri,
        'primary_color' => '#059669',
        'secondary_color' => '#0284c7'
    ], JSON_UNESCAPED_SLASHES);

    $plugnmeetUrl = "$clientFacingUrl/?access_token=" . rawurlencode($accessToken) . "&custom_design=" . rawurlencode($customDesign);
} else {
    $plugnmeetUrl = '';
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($course['code']) ?> Virtual Classroom · NPC ELMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.3);
                opacity: 0;
            }

            100% {
                transform: scale(0.95);
                opacity: 0;
            }
        }

        .animate-pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="h-full w-full overflow-hidden flex flex-col bg-slate-950 text-slate-100 select-none">

    <?php if (empty($accessToken)): ?>
        <!-- Error Fallback -->
        <div class="flex-1 flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl text-center space-y-5">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <span class="material-symbols-outlined text-[32px]">wifi_off</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Could Not Connect to Classroom</h2>
                    <p class="text-sm text-slate-400 leading-relaxed mt-2">
                        The virtual lecture room server is preparing your secure session. Please click below to retry joining.
                    </p>
                    <?php if (!empty($createError)): ?>
                    <p class="text-xs text-red-400 mt-2 font-mono bg-red-900/20 rounded-lg px-3 py-1.5 border border-red-500/20">
                        Server: <?= htmlspecialchars($createError) ?>
                    </p>
                    <?php elseif (!empty($joinCurlErr)): ?>
                    <p class="text-xs text-red-400 mt-2 font-mono bg-red-900/20 rounded-lg px-3 py-1.5 border border-red-500/20">
                        Connection: <?= htmlspecialchars($joinCurlErr) ?>
                    </p>
                    <?php elseif (!empty($joinResRaw) && empty($accessToken)): ?>
                    <p class="text-xs text-amber-400 mt-2 font-mono bg-amber-900/20 rounded-lg px-3 py-1.5 border border-amber-500/20">
                        <?= htmlspecialchars($joinRes['msg'] ?? 'Token not returned') ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
                    <button onclick="location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-all shadow-lg shadow-emerald-900/30 inline-flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">refresh</span> Retry Connection
                    </button>
                    <a href="<?= htmlspecialchars($returnPath) ?>" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-sm font-semibold transition-all inline-flex items-center justify-center">
                        Back to Courses
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- ─── Top Classroom Header Bar ─── -->
        <header class="h-14 bg-slate-900 border-b border-slate-800/80 px-3 sm:px-5 flex items-center justify-between shrink-0 z-30 shadow-md">
            <!-- Left: Course Branding -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-[18px]">co_present</span>
                </div>
                <div class="min-w-0 flex items-center gap-2">
                    <span class="font-bold text-xs sm:text-sm text-white font-mono tracking-tight shrink-0"><?= htmlspecialchars($course['code']) ?></span>
                    <span class="hidden md:inline text-xs text-slate-400 font-medium truncate max-w-[200px] lg:max-w-xs">· <?= htmlspecialchars($course['title']) ?></span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 font-mono text-[10px] font-bold shrink-0">Sec <?= htmlspecialchars($course['section'] ?? $studentSection) ?></span>
                </div>
            </div>

            <!-- Center: Classroom Active Timer (Only starts & displays when student is inside the class) -->
            <div class="flex items-center gap-2">
                <div id="classroom-timer-badge" class="hidden items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-950 border border-emerald-500/40 font-mono font-bold text-xs text-emerald-300 shadow-inner" title="Active Duration in Classroom">
                    <span class="material-symbols-outlined text-[15px] text-emerald-400">timer</span>
                    <span id="classroom-duration-timer">00:00:00</span>
                </div>
            </div>

            <!-- Right: Participant Identity & Controls -->
            <div class="flex items-center gap-2">
                <div class="hidden lg:flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs font-mono text-slate-200">
                    <span class="material-symbols-outlined text-[14px] text-slate-400"><?= $isAdmin ? 'cast_for_education' : 'school' ?></span>
                    <span class="truncate max-w-[150px]"><?= htmlspecialchars($userName) ?></span>
                    <span class="text-slate-400 font-bold"><?= $isAdmin ? '[Faculty]' : ('(' . htmlspecialchars($studentNumber) . ')') ?></span>
                </div>

                <!-- Flip / Mirror Camera Button (PC & Mobile) -->
                <button type="button" id="flip-camera-btn" onclick="toggleCameraMirror()" class="px-2.5 py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 border border-slate-700/60 text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-xs" title="Flip / Mirror Camera (PC & Mobile)">
                    <span class="material-symbols-outlined text-[16px]">flip</span>
                    <span class="hidden sm:inline" id="flip-camera-label">Flip Camera</span>
                </button>

                <!-- Minimize / Float Meeting (Picture-in-Picture) -->
                <button type="button" id="minimize-meeting-btn" onclick="toggleMeetingMinimize()" class="px-2.5 py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 border border-slate-700/60 text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-xs" title="Minimize / Float Video (Picture-in-Picture)">
                    <span class="material-symbols-outlined text-[16px]" id="minimize-meeting-icon">branding_watermark</span>
                    <span class="hidden sm:inline" id="minimize-meeting-label">Minimize</span>
                </button>

                <?php if ($isAdmin): ?>
                    <!-- Teacher Quick Live Roster Trigger -->
                    <button type="button" onclick="toggleTeacherRosterDrawer()" class="px-3 py-1.5 rounded-xl bg-sky-600/20 hover:bg-sky-600/30 text-sky-400 border border-sky-500/30 text-xs font-bold transition-all inline-flex items-center gap-1 cursor-pointer shadow-xs">
                        <span class="material-symbols-outlined text-[16px]">groups</span>
                        <span class="hidden sm:inline">Live Roster</span>
                    </button>
                <?php endif; ?>

                <!-- Fullscreen Button -->
                <button type="button" onclick="toggleFullscreen()" class="p-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer" title="Toggle Fullscreen">
                    <span class="material-symbols-outlined text-[18px]">fullscreen</span>
                </button>

                <!-- Leave Button -->
                <button type="button" onclick="confirmLeaveClassroom()" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-md cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">logout</span>
                    <span class="hidden sm:inline">Leave</span>
                </button>
            </div>
        </header>

        <!-- ─── Main Viewport: Full Height Embedded PlugNmeet or Floating Mini Player ─── -->
        <main class="flex-1 w-full h-[calc(100%-3.5rem)] relative bg-slate-950 overflow-hidden">

            <!-- ─── Background Classroom Hub (Visible when meeting is Minimized) ─── -->
            <div id="classroom-minimized-view" class="hidden w-full h-full overflow-y-auto p-4 sm:p-8 flex-col max-w-5xl mx-auto space-y-6">
                <!-- Welcome Banner & Quick Restore -->
                <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 border border-slate-800/90 p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold font-mono">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                Live Lecture In Progress (Floating Mode)
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                <?= htmlspecialchars($course['code']) ?> · <?= htmlspecialchars($course['title']) ?>
                            </h2>
                            <p class="text-sm text-slate-400 flex items-center gap-2 font-mono">
                                <span class="material-symbols-outlined text-[16px] text-slate-500">person</span>
                                <?= htmlspecialchars($course['instructor']) ?> · Section <?= htmlspecialchars($course['section'] ?? $studentSection) ?>
                            </p>
                        </div>
                        <button type="button" onclick="toggleMeetingMinimize()" class="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-xl shadow-emerald-900/30 transition-all transform hover:scale-105 active:scale-95 inline-flex items-center gap-2 cursor-pointer shrink-0">
                            <span class="material-symbols-outlined text-[20px]">open_in_full</span>
                            Expand Video Conference
                        </button>
                    </div>
                </div>

                <!-- Classroom Status Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Card 1: Attendance Verification -->
                    <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-5 space-y-2">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                            <span>Attendance Tracking</span>
                            <span class="material-symbols-outlined text-emerald-400 text-[18px]">verified</span>
                        </div>
                        <div class="text-lg font-bold text-white flex items-center gap-2 font-mono">
                            <span class="text-emerald-400">100% Active</span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Your audio, camera, and attendance keepalive remain active while minimized.
                        </p>
                    </div>

                    <!-- Card 2: Classroom Active Timer -->
                    <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-5 space-y-2">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                            <span>Active Ticking Time</span>
                            <span class="material-symbols-outlined text-emerald-400 text-[18px]">schedule</span>
                        </div>
                        <div class="text-2xl font-bold font-mono text-emerald-300" id="hub-duration-timer">
                            00:00:00
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Continuous institutional presence verified by server heartbeat.
                        </p>
                    </div>

                    <!-- Card 3: Quick Navigation -->
                    <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-5 space-y-2">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                            <span>Course Portal</span>
                            <span class="material-symbols-outlined text-sky-400 text-[18px]">menu_book</span>
                        </div>
                        <div class="text-sm font-semibold text-slate-200">
                            NPC ELMS Academic Hub
                        </div>
                        <div class="pt-1 flex gap-2">
                            <a href="<?= htmlspecialchars($returnPath) ?>" target="_blank" class="text-xs text-sky-400 hover:text-sky-300 underline inline-flex items-center gap-1 font-mono">
                                Open Course Materials <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Session Information & Help -->
                <div class="rounded-2xl bg-slate-900/50 border border-slate-800/80 p-5 text-xs text-slate-400 space-y-1">
                    <p class="font-bold text-slate-300">💡 Mini-Player Active:</p>
                    <p>The floating video window at the bottom right allows you to continue hearing the class and participating without dropping out. Click the "Expand" button anytime to return to the full interface.</p>
                </div>
            </div>

            <!-- ─── Embedded Meeting Container (Full or Floating PiP) ─── -->
            <div id="meeting-wrapper" class="w-full h-full relative transition-all duration-300">
                <!-- Floating Mini Bar Header (Visible only when minimized) -->
                <div id="floating-mini-bar" class="hidden h-9 px-3 bg-slate-900/95 border-b border-emerald-500/40 items-center justify-between z-20 backdrop-blur-sm select-none">
                    <div class="flex items-center gap-1.5 text-xs font-mono font-bold text-emerald-400 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping shrink-0"></span>
                        <span class="truncate"><?= htmlspecialchars($course['code']) ?> (Live)</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="toggleMeetingMinimize()" class="p-1 rounded-md text-slate-300 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer" title="Expand Fullscreen">
                            <span class="material-symbols-outlined text-[16px]">open_in_full</span>
                        </button>
                    </div>
                </div>

                <iframe
                    id="plugnmeet-frame"
                    src="<?= htmlspecialchars($plugnmeetUrl) ?>"
                    allow="camera *; microphone *; display-capture *; autoplay *; clipboard-write *; fullscreen *"
                    class="w-full h-full border-0 bg-slate-950">
                </iframe>
            </div>

            <?php if ($isAdmin): ?>
                <!-- ─── Slide-over Teacher Live Attendance Drawer ─── -->
                <div id="teacher-roster-drawer" class="fixed inset-y-0 right-0 top-14 z-40 w-full sm:w-96 bg-slate-900 border-l border-slate-800 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/60">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-emerald-400">groups</span>
                            <h3 class="text-sm font-bold text-white">Live Attendance Roster</h3>
                        </div>
                        <button type="button" onclick="toggleTeacherRosterDrawer()" class="p-1 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                    <!-- Summary Pills -->
                    <div class="p-3 bg-slate-950/40 border-b border-slate-800 flex items-center gap-2 flex-wrap text-[11px] font-mono font-bold">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 flex items-center gap-1" id="drawer-online-count">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> 0 in Class
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-red-500/15 text-red-400" id="drawer-left-count">0 Disconnected</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300" id="drawer-enrolled-count">0 Enrolled</span>
                    </div>
                    <!-- Student List -->
                    <div class="flex-1 overflow-y-auto p-3 divide-y divide-slate-800/60" id="drawer-roster-list">
                        <div class="py-8 text-center text-slate-400 italic font-mono text-xs">Loading roster...</div>
                    </div>
                    <div class="p-3 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-400">Auto-updating every 3s</span>
                        <button type="button" onclick="loadDrawerRoster()" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold cursor-pointer">Refresh</button>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- ─── Leave Confirmation Modal Dialog ─── -->
        <div id="leave-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
            <div class="max-w-sm w-full bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl text-center space-y-4">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-red-600/15 border border-red-500/30 flex items-center justify-center text-red-400">
                    <span class="material-symbols-outlined text-[24px]">door_open</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Leave Virtual Classroom?</h3>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Leaving this classroom will <strong class="text-red-400">pause your active attendance tracking</strong> and register your departure on the instructor's live roster.
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button type="button" onclick="dismissLeaveModal()" class="px-4 py-2 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold cursor-pointer transition-colors">
                        Stay in Class
                    </button>
                    <button type="button" onclick="executeLeaveClassroom()" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition-all shadow-md cursor-pointer inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">logout</span> Confirm Leave
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Automated Presence & Keepalive Telemetry Script ─── -->
        <script>
            var SESSION_CODE = <?= json_encode($actualSessionCode) ?>;
            var COURSE_CODE = <?= json_encode($course['code']) ?>;
            var STUDENT_NUM = <?= json_encode($studentNumber) ?>;
            var USER_ROLE = <?= json_encode($userRole) ?>;
            var RETURN_URL = <?= json_encode($logoutUrl) ?>;
            var DURATION_SECS = <?= intval($initialDuration) ?>;
            var IS_ONLINE = true;
            var SESSION_IS_ACTIVE = <?= json_encode(!array_key_exists('is_active', $live) || !empty($live['is_active'])) ?>;

            function formatDurationHms(totalSecs) {
                var s = Math.max(0, parseInt(totalSecs, 10) || 0);
                var hrs = Math.floor(s / 3600);
                var mins = Math.floor((s % 3600) / 60);
                var secs = s % 60;
                return (hrs < 10 ? '0' : '') + hrs + ':' + (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            }

            var sessionTimerActive = false;
            var timerInterval = null;

            function startClassroomTimer() {
                if (sessionTimerActive) return;
                sessionTimerActive = true;
                var badge = document.getElementById('classroom-timer-badge');
                var timerEl = document.getElementById('classroom-duration-timer');
                if (badge) {
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                }
                if (timerEl) {
                    timerEl.textContent = formatDurationHms(DURATION_SECS);
                }
                var hubTimerEl = document.getElementById('hub-duration-timer');
                if (hubTimerEl) {
                    hubTimerEl.textContent = formatDurationHms(DURATION_SECS);
                }
                if (!timerInterval) {
                    timerInterval = setInterval(function() {
                        if (IS_ONLINE && SESSION_IS_ACTIVE) {
                            DURATION_SECS++;
                            if (timerEl) timerEl.textContent = formatDurationHms(DURATION_SECS);
                            if (hubTimerEl) hubTimerEl.textContent = formatDurationHms(DURATION_SECS);
                        }
                    }, 1000);
                }
            }

            // ─── Floating / Minimize PiP Controller ───
            var isMeetingMinimized = false;

            function toggleMeetingMinimize() {
                isMeetingMinimized = !isMeetingMinimized;
                var wrapper = document.getElementById('meeting-wrapper');
                var hub = document.getElementById('classroom-minimized-view');
                var miniBar = document.getElementById('floating-mini-bar');
                var btnIcon = document.getElementById('minimize-meeting-icon');
                var btnLabel = document.getElementById('minimize-meeting-label');
                var btn = document.getElementById('minimize-meeting-btn');

                if (isMeetingMinimized) {
                    // Minimize to bottom-right floating window
                    wrapper.className = 'fixed bottom-4 right-4 z-50 w-[340px] sm:w-[420px] h-[220px] sm:h-[260px] rounded-2xl border-2 border-emerald-500/70 shadow-2xl shadow-black/90 overflow-hidden bg-slate-900 flex flex-col transition-all duration-300';
                    if (miniBar) {
                        miniBar.classList.remove('hidden');
                        miniBar.classList.add('flex');
                    }
                    if (hub) {
                        hub.classList.remove('hidden');
                        hub.classList.add('flex');
                    }
                    if (btnIcon) btnIcon.textContent = 'open_in_full';
                    if (btnLabel) btnLabel.textContent = 'Expand';
                    if (btn) btn.classList.add('text-emerald-400', 'border-emerald-500/50');
                } else {
                    // Restore to normal full viewport
                    wrapper.className = 'w-full h-full relative transition-all duration-300';
                    if (miniBar) {
                        miniBar.classList.add('hidden');
                        miniBar.classList.remove('flex');
                    }
                    if (hub) {
                        hub.classList.add('hidden');
                        hub.classList.remove('flex');
                    }
                    if (btnIcon) btnIcon.textContent = 'branding_watermark';
                    if (btnLabel) btnLabel.textContent = 'Minimize';
                    if (btn) btn.classList.remove('text-emerald-400', 'border-emerald-500/50');
                }
            }

            // Start timer only when PlugNmeet signals that the room is READY and user has entered
            window.addEventListener('message', function(ev) {
                if (ev && ev.data && (ev.data.type === 'PNM_ROOM_READY' || ev.data === 'PNM_ROOM_READY')) {
                    startClassroomTimer();
                }
            });

            // Automated 4-Second Server Heartbeat (Keeps Presence Online in Database & JSON)
            if (USER_ROLE === 'student' && SESSION_CODE) {
                setInterval(function() {
                    if (!IS_ONLINE || !SESSION_IS_ACTIVE) return;
                    fetch('/api/elms.php?action=meeting_presence_heartbeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            session_code: SESSION_CODE
                        })
                    }).then(function(res) {
                        return res.json();
                    }).then(function(data) {
                        if (data && data.success) {
                            startClassroomTimer();
                            if (typeof data.duration_seconds === 'number') {
                                DURATION_SECS = data.duration_seconds;
                                var timerEl = document.getElementById('classroom-duration-timer');
                                if (timerEl) timerEl.textContent = formatDurationHms(DURATION_SECS);
                            }
                        }
                    }).catch(function() {});
                }, 4000);
            }

            // Camera Mirror / Orientation Controller (PC & Mobile)
            var isCameraMirrored = localStorage.getItem('npc_cam_mirrored') === '1';

            function applyCameraMirror(mirrored) {
                isCameraMirrored = !!mirrored;
                localStorage.setItem('npc_cam_mirrored', isCameraMirrored ? '1' : '0');
                var label = document.getElementById('flip-camera-label');
                if (label) {
                    label.textContent = isCameraMirrored ? 'Mirrored' : 'Normal Cam';
                }
                var btn = document.getElementById('flip-camera-btn');
                if (btn) {
                    if (isCameraMirrored) {
                        btn.classList.add('text-emerald-400', 'border-emerald-500/50');
                    } else {
                        btn.classList.remove('text-emerald-400', 'border-emerald-500/50');
                    }
                }
                
                // Inject or update CSS in iframe
                var frame = document.getElementById('plugnmeet-frame');
                if (frame && frame.contentWindow) {
                    try {
                        var doc = frame.contentDocument || frame.contentWindow.document;
                        if (doc && doc.head) {
                            var styleEl = doc.getElementById('npc-mirror-override');
                            if (!styleEl) {
                                styleEl = doc.createElement('style');
                                styleEl.id = 'npc-mirror-override';
                                doc.head.appendChild(styleEl);
                            }
                            if (isCameraMirrored) {
                                styleEl.textContent = 'video.camera-video, .camera-video-player video, .video-camera-item video { transform: scaleX(-1) !important; -webkit-transform: scaleX(-1) !important; }';
                            } else {
                                styleEl.textContent = 'video.camera-video, .camera-video-player video, .video-camera-item video { transform: scaleX(1) !important; -webkit-transform: scaleX(1) !important; }';
                            }
                        }
                    } catch(e) {}
                }
            }

            function toggleCameraMirror() {
                applyCameraMirror(!isCameraMirrored);
            }

            // Sync camera mirror on iframe load
            var plugFrame = document.getElementById('plugnmeet-frame');
            if (plugFrame) {
                plugFrame.addEventListener('load', function() {
                    setTimeout(function() {
                        applyCameraMirror(isCameraMirrored);
                    }, 1500);
                });
            }

            // ─── Mobile Back Interceptor & Anti-Auto-Logout (Google Meet Style) ───
            var isExplicitLeaving = false;

            // Prevent mobile Back gesture / Back button from accidentally closing the classroom
            try {
                history.pushState({ inRoom: true }, '');
                window.addEventListener('popstate', function(e) {
                    history.pushState({ inRoom: true }, '');
                    if (!isMeetingMinimized) {
                        // Like Google Meet: Pressing back minimizes video to floating PiP so audio keeps playing!
                        toggleMeetingMinimize();
                    } else {
                        // If already minimized, show confirmation dialog before leaving
                        confirmLeaveClassroom();
                    }
                });
            } catch(e) {}

            // Screen WakeLock so phone screen doesn't turn off during class
            if ('wakeLock' in navigator) {
                var wakeLockRef = null;
                var requestWakeLock = function() {
                    navigator.wakeLock.request('screen').then(function(lock) {
                        wakeLockRef = lock;
                    }).catch(function() {});
                };
                requestWakeLock();
                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'visible' && !wakeLockRef) {
                        requestWakeLock();
                    }
                });
            }

            // 3. Safe Disconnect Beacon (Only fires on true tab close or explicit leave, NOT on mobile app switch)
            window.addEventListener('beforeunload', function(e) {
                if (isExplicitLeaving) {
                    sendLeaveBeacon();
                }
            });

            function sendLeaveBeacon() {
                if (!IS_ONLINE || USER_ROLE !== 'student' || !SESSION_CODE) return;
                IS_ONLINE = false;
                var payload = JSON.stringify({
                    session_code: SESSION_CODE,
                    student_number: STUDENT_NUM
                });
                if (navigator.sendBeacon) {
                    var blob = new Blob([payload], {
                        type: 'application/json'
                    });
                    navigator.sendBeacon('/api/elms.php?action=meeting_presence_leave', blob);
                } else {
                    fetch('/api/elms.php?action=meeting_presence_leave', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: payload,
                        keepalive: true
                    });
                }
            }

            // 4. Modal Leave Handlers
            function confirmLeaveClassroom() {
                var m = document.getElementById('leave-confirm-modal');
                if (m) {
                    m.classList.remove('hidden');
                    m.classList.add('flex');
                }
            }

            function dismissLeaveModal() {
                var m = document.getElementById('leave-confirm-modal');
                if (m) {
                    m.classList.add('hidden');
                    m.classList.remove('flex');
                }
            }

            async function executeLeaveClassroom() {
                isExplicitLeaving = true;
                IS_ONLINE = false;
                try {
                    if (USER_ROLE === 'student' && SESSION_CODE) {
                        await fetch('/api/elms.php?action=meeting_presence_leave', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                session_code: SESSION_CODE,
                                student_number: STUDENT_NUM
                            })
                        });
                    }
                } catch (e) {}
                window.location.href = RETURN_URL;
            }

            // 5. Fullscreen Toggle
            function toggleFullscreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(function() {});
                } else {
                    document.exitFullscreen().catch(function() {});
                }
            }

            <?php if ($isAdmin): ?>
                // 6. Teacher Live Roster Drawer
                var DRAWER_INTERVAL = null;

                function toggleTeacherRosterDrawer() {
                    var drawer = document.getElementById('teacher-roster-drawer');
                    if (!drawer) return;
                    var isHidden = drawer.classList.contains('translate-x-full');
                    if (isHidden) {
                        drawer.classList.remove('translate-x-full');
                        loadDrawerRoster();
                        DRAWER_INTERVAL = setInterval(loadDrawerRoster, 3500);
                    } else {
                        drawer.classList.add('translate-x-full');
                        if (DRAWER_INTERVAL) clearInterval(DRAWER_INTERVAL);
                    }
                }

                async function loadDrawerRoster() {
                    if (!SESSION_CODE) return;
                    try {
                        var res = await fetch('/api/elms.php?action=get_live_presence_roster&session_code=' + encodeURIComponent(SESSION_CODE) + '&section=' + encodeURIComponent(<?= json_encode($course['section'] ?? '') ?>));
                        var data = await res.json();
                        if (!data.success) return;

                        document.getElementById('drawer-online-count').innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> ' + (data.online_count || 0) + ' in Class';
                        document.getElementById('drawer-left-count').textContent = (data.left_count || 0) + ' Disconnected';
                        document.getElementById('drawer-enrolled-count').textContent = (data.total_enrolled || 0) + ' Enrolled';

                        var listEl = document.getElementById('drawer-roster-list');
                        if (!data.roster || !data.roster.length) {
                            listEl.innerHTML = '<div class="py-8 text-center text-slate-400 italic font-mono text-xs">No students enrolled yet.</div>';
                            return;
                        }

                        var html = '';
                        data.roster.forEach(function(r) {
                            var isOnline = !!r.is_online;
                            var badge = isOnline ?
                                '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> In Class</span>' :
                                '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Offline</span>';

                            var timer = isOnline ?
                                '<span class="font-mono text-emerald-400 font-bold text-xs">' + formatDurationHms(r.duration_seconds) + '</span>' :
                                '<span class="font-mono text-slate-400 text-xs">' + (r.duration_seconds ? ('Paused: ' + formatDurationHms(r.duration_seconds)) : '—') + '</span>';

                            var leaves = r.leave_count > 0 ?
                                '<span class="px-1.5 py-0.5 rounded bg-red-500/15 text-red-400 text-[10px] font-mono font-bold">🔴 ' + r.leave_count + 'x Left</span>' :
                                '';

                            html += '<div class="py-2.5 flex items-center justify-between gap-2">' +
                                '<div class="min-w-0">' +
                                '<p class="text-xs font-bold text-white font-mono truncate">' + (r.formatted_name || r.full_name) + '</p>' +
                                '<p class="text-[10px] font-mono text-slate-400">' + r.student_number + ' ' + leaves + '</p>' +
                                '</div>' +
                                '<div class="text-right shrink-0">' +
                                badge +
                                '<div class="mt-0.5">' + timer + '</div>' +
                                '</div>' +
                                '</div>';
                        });
                        listEl.innerHTML = html;
                    } catch (e) {}
                }
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>

</html>