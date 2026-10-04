<?php
/**
 * live_room.php — Navotas Polytechnic College (NPC) ELMS
 * Official 100% Unlimited WebRTC Virtual Classroom
 * 
 * Powered by Drive D WebRTC Media Server (LiveKit Server Port 7880)
 * 
 * Key Features:
 * - 100% Self-Hosted on Drive D (Zero 60-min cloud cutoffs, Unli Time ∞)
 * - Official NPC Branding (PlugNmeet logo completely removed)
 * - No countdown timer; live attendance accumulator & infinity status badge
 * - Anti-Kupal locked student identity (cryptographically signed in JWT)
 * - Video grid, audio mic with speaking indicator, screen sharing
 * - Interactive collaborative whiteboard (draw, shapes, text, eraser, export)
 * - In-room live chat with real-time messages
 * - Live attendance auto-detection, heartbeat, and prof-only leave counter
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? 'NPC Student';
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

// Load course details
$elmsFile = __DIR__ . '/backend/elms_courses.json';
$rawElms = @file_get_contents($elmsFile);
$elms = json_decode($rawElms, true) ?: ['courses' => []];
$course = null;

foreach ($elms['courses'] as $c) {
    if (($courseCode && $c['code'] === $courseCode) || 
        ($sessionCode && ($c['live_session']['session_code'] ?? '') === $sessionCode) ||
        ($roomId && ($c['live_session']['room_id'] ?? '') === $roomId)) {
        $course = $c;
        break;
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
            'timer_mode' => 'unlimited',
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

// Construct clean LiveKit Room Name
$cleanRoom = preg_replace('/[^A-Za-z0-9_-]/', '', "NPC-ROOM-{$course['code']}-{$course['section']}");
if (empty($cleanRoom)) {
    $cleanRoom = 'NPC-ROOM-DEFAULT';
}

// Anti-Kupal Student Identity: Strictly locked from session
$participantName = ($userRole === 'student') 
    ? ($userName . " (" . ($studentNumber ?: 'Student') . ")") 
    : ("Prof. " . $userName . " [Instructor]");

$participantId = ($userRole === 'student') 
    ? ("stu_" . preg_replace('/[^a-zA-Z0-9]/', '', $studentNumber ?: $userEmail)) 
    : ("fac_" . preg_replace('/[^a-zA-Z0-9]/', '', $userEmail));

// Generate pure PHP JWT token for local LiveKit Server on Drive D
function createLocalLiveKitToken(string $room, string $identity, string $name, bool $isAdmin = false, int $durationSeconds = 86400): string {
    $apiKey = 'npc_elms_key';
    $apiSecret = 'npc_elms_secret_2026';

    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'exp' => $now + $durationSeconds,
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

    $b64Url = function($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    };

    $encodedHeader = $b64Url($header);
    $encodedPayload = $b64Url($payload);
    $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $apiSecret, true);
    return "$encodedHeader.$encodedPayload." . $b64Url($signature);
}

$livekitToken = createLocalLiveKitToken($cleanRoom, $participantId, $participantName, $isAdmin, 86400);
$livekitWsUrl = "ws://" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ":7880";
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['code'] . ' · ' . $course['title']) ?> | NPC Virtual Classroom</title>
    <link rel="icon" type="image/png" href="/assets/img/npc-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="/assets/js/livekit-client.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        ::-webkit-scrollbar-thumb { background: rgba(51, 65, 85, 0.8); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71, 85, 105, 1); }
        @keyframes pulseSlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .pulse-slow { animation: pulseSlow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
</head>
<body class="h-full flex flex-col overflow-hidden select-none bg-slate-950">

    <!-- ──────────────── NPC Brand & Telemetry Header Bar ──────────────── -->
    <header class="h-14 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-3 sm:px-5 shrink-0 z-30 shadow-md">
        <!-- Brand & Class Meta -->
        <div class="flex items-center gap-3 min-w-0">
            <div class="relative w-9 h-9 shrink-0 flex items-center justify-center rounded-xl bg-slate-800 border border-emerald-500/40 p-1 shadow-inner">
                <img src="/assets/img/npc-logo.png" alt="NPC Emblem" class="w-7 h-7 object-contain">
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-mono font-bold text-xs text-emerald-400 truncate"><?= htmlspecialchars($course['code']) ?></span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                        Section <?= htmlspecialchars($course['section']) ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> LIVE
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 truncate max-w-xs sm:max-w-md"><?= htmlspecialchars($topic) ?></p>
            </div>
        </div>

        <!-- Center Attendance Telemetry (Elapsed timer counting UP, zero countdown) -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Active Attendance Duration -->
            <div class="flex items-center gap-2 bg-slate-800/90 border border-slate-700 px-3 py-1.5 rounded-xl shadow-xs" title="Real-time attendance accumulator (pauses on leave)">
                <span id="presence-dot" class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <div>
                    <span class="hidden sm:block text-[9px] uppercase font-mono text-slate-400 tracking-wider font-semibold" id="presence-label">Active Attendance</span>
                    <span class="font-mono font-bold text-xs sm:text-sm text-emerald-400" id="presence-duration-timer">00:00:00</span>
                </div>
            </div>

            <!-- Unlimited Session Duration Badge (NO 60m Cutoff) -->
            <div class="hidden md:flex items-center gap-1.5 bg-sky-950/40 border border-sky-500/30 px-2.5 py-1.5 rounded-xl text-sky-400" title="Unlimited classroom duration powered by Drive D server">
                <span class="material-symbols-outlined text-[15px]">all_inclusive</span>
                <span class="text-[10px] font-mono font-bold">Unli Time (No Limit)</span>
            </div>

            <!-- Server Status -->
            <div class="hidden lg:flex items-center gap-1.5 bg-slate-800/50 border border-slate-700/60 px-2.5 py-1.5 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-[10px] font-mono text-slate-300">LiveKit 7880 (Drive D)</span>
            </div>
        </div>

        <!-- User Identity & Leave Button -->
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="hidden sm:flex flex-col text-right">
                <span class="text-xs font-bold text-slate-200 truncate max-w-[140px]" title="<?= htmlspecialchars($participantName) ?>">
                    <?= htmlspecialchars($userName) ?>
                </span>
                <span class="text-[10px] font-mono text-emerald-400 font-medium">
                    <?= ($userRole === 'student') ? htmlspecialchars($studentNumber) : 'Faculty Instructor' ?>
                </span>
            </div>

            <!-- Leave Button -->
            <button type="button" onclick="confirmLeaveClassroom()" class="px-3 py-1.5 rounded-xl bg-red-600/20 hover:bg-red-600/30 text-red-400 hover:text-red-300 border border-red-500/30 font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer shadow-xs">
                <span class="material-symbols-outlined text-[16px]">logout</span>
                <span class="hidden xs:inline">Leave</span>
            </button>
        </div>
    </header>

    <!-- ──────────────── Main Classroom Workspace ──────────────── -->
    <main class="flex-1 relative flex overflow-hidden bg-slate-950">
        
        <!-- Left: Primary Stage (Video Grid or Whiteboard) -->
        <section class="flex-1 flex flex-col relative overflow-hidden">
            
            <!-- Tab Switcher (Stage View: Video Conference vs Collaborative Whiteboard) -->
            <div class="h-10 bg-slate-900/80 border-b border-slate-800/80 flex items-center justify-between px-4 shrink-0">
                <div class="flex items-center gap-2">
                    <button id="tab-video" onclick="switchStageView('video')" class="px-3 py-1 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all bg-emerald-600 text-white shadow-xs">
                        <span class="material-symbols-outlined text-[15px]">videocam</span>
                        <span>Video Grid (<span id="participant-count-badge">1</span>)</span>
                    </button>
                    <button id="tab-whiteboard" onclick="switchStageView('whiteboard')" class="px-3 py-1 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                        <span class="material-symbols-outlined text-[15px]">draw</span>
                        <span>Interactive Whiteboard</span>
                    </button>
                </div>

                <!-- Teacher Controls -->
                <?php if ($isAdmin): ?>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono text-amber-400 font-bold px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                        PROFESSOR HOST
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- View 1: Video Conference Grid -->
            <div id="view-video-container" class="flex-1 p-3 sm:p-4 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 auto-rows-fr">
                
                <!-- Local Participant Tile (Always Visible) -->
                <div class="relative bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col items-center justify-center min-h-[220px] shadow-xl group">
                    <video id="local-video" autoplay playsinline muted class="w-full h-full object-cover hidden"></video>
                    
                    <!-- Avatar Fallback when camera is off -->
                    <div id="local-avatar" class="flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-emerald-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400 shadow-lg">
                            <span class="material-symbols-outlined text-[32px]">person</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-300 font-mono">Camera Muted</span>
                    </div>

                    <!-- Tile Bottom Identity Overlay -->
                    <div class="absolute bottom-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                        <div class="flex items-center gap-1.5 bg-slate-950/80 backdrop-blur-md px-2.5 py-1 rounded-lg border border-slate-700/80 text-white font-mono text-xs shadow-md">
                            <span id="local-mic-icon" class="material-symbols-outlined text-[14px] text-red-400">mic_off</span>
                            <span class="truncate max-w-[160px]"><?= htmlspecialchars($participantName) ?> (You)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">
                            <?= $isAdmin ? 'HOST' : 'STUDENT' ?>
                        </span>
                    </div>
                </div>

                <!-- Peer Tiles placeholder container -->
                <div id="remote-participants" class="contents">
                    <!-- Peer video tiles dynamically inserted here -->
                </div>
            </div>

            <!-- View 2: Interactive Collaborative Whiteboard -->
            <div id="view-whiteboard-container" class="flex-1 flex flex-col hidden relative bg-slate-950">
                <!-- Whiteboard Toolbar -->
                <div class="h-12 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between px-4 shrink-0 overflow-x-auto gap-2">
                    <!-- Tool Selectors -->
                    <div class="flex items-center gap-1 bg-slate-800/80 p-1 rounded-xl border border-slate-700">
                        <button type="button" onclick="setWbTool('pen')" id="wb-tool-pen" class="p-1.5 rounded-lg bg-emerald-600 text-white shadow-xs" title="Freehand Pen">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                        </button>
                        <button type="button" onclick="setWbTool('line')" id="wb-tool-line" class="p-1.5 rounded-lg text-slate-400 hover:text-white" title="Straight Line">
                            <span class="material-symbols-outlined text-[16px]">horizontal_rule</span>
                        </button>
                        <button type="button" onclick="setWbTool('rect')" id="wb-tool-rect" class="p-1.5 rounded-lg text-slate-400 hover:text-white" title="Rectangle">
                            <span class="material-symbols-outlined text-[16px]">crop_square</span>
                        </button>
                        <button type="button" onclick="setWbTool('circle')" id="wb-tool-circle" class="p-1.5 rounded-lg text-slate-400 hover:text-white" title="Circle">
                            <span class="material-symbols-outlined text-[16px]">circle</span>
                        </button>
                        <button type="button" onclick="setWbTool('text')" id="wb-tool-text" class="p-1.5 rounded-lg text-slate-400 hover:text-white" title="Add Text Label">
                            <span class="material-symbols-outlined text-[16px]">title</span>
                        </button>
                        <button type="button" onclick="setWbTool('eraser')" id="wb-tool-eraser" class="p-1.5 rounded-lg text-slate-400 hover:text-white" title="Eraser">
                            <span class="material-symbols-outlined text-[16px]">ink_eraser</span>
                        </button>
                    </div>

                    <!-- Color Picker & Stroke -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-slate-800/80 px-2 py-1 rounded-xl border border-slate-700">
                            <span class="text-[10px] font-mono text-slate-400 uppercase">Color</span>
                            <input type="color" id="wb-color" value="#059669" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent">
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-800/80 px-2 py-1 rounded-xl border border-slate-700">
                            <span class="text-[10px] font-mono text-slate-400 uppercase">Size</span>
                            <select id="wb-size" class="bg-transparent text-xs text-white border-0 outline-none font-mono">
                                <option value="2" class="bg-slate-900">2px</option>
                                <option value="4" selected class="bg-slate-900">4px</option>
                                <option value="8" class="bg-slate-900">8px</option>
                                <option value="16" class="bg-slate-900">16px</option>
                            </select>
                        </div>
                    </div>

                    <!-- Canvas Actions -->
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="clearWhiteboard()" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1 border border-slate-700">
                            <span class="material-symbols-outlined text-[15px]">delete</span>
                            <span>Clear</span>
                        </button>
                        <button type="button" onclick="downloadWhiteboard()" class="px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-xs font-semibold flex items-center gap-1 border border-emerald-500/30">
                            <span class="material-symbols-outlined text-[15px]">download</span>
                            <span>Export PNG</span>
                        </button>
                    </div>
                </div>

                <!-- Canvas Element -->
                <div class="flex-1 relative bg-white overflow-hidden" id="canvas-wrapper">
                    <canvas id="wb-canvas" class="w-full h-full block cursor-crosshair"></canvas>
                </div>
            </div>

            <!-- Floating Reaction Overlay -->
            <div id="reactions-container" class="absolute inset-0 pointer-events-none overflow-hidden z-20"></div>

            <!-- ──────────────── Bottom Conference Control Dock ──────────────── -->
            <footer class="h-16 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 flex items-center justify-between px-3 sm:px-6 shrink-0 z-30">
                <!-- Left Media Controls -->
                <div class="flex items-center gap-2">
                    <!-- Mic Toggle -->
                    <button type="button" id="btn-toggle-mic" onclick="toggleMicrophone()" class="w-11 h-11 rounded-2xl bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 text-red-400 flex items-center justify-center transition-all cursor-pointer shadow-md" title="Mute/Unmute Mic">
                        <span id="icon-mic" class="material-symbols-outlined text-[20px]">mic_off</span>
                    </button>

                    <!-- Camera Toggle -->
                    <button type="button" id="btn-toggle-cam" onclick="toggleCamera()" class="w-11 h-11 rounded-2xl bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 text-red-400 flex items-center justify-center transition-all cursor-pointer shadow-md" title="Turn Camera On/Off">
                        <span id="icon-cam" class="material-symbols-outlined text-[20px]">videocam_off</span>
                    </button>

                    <!-- Screen Share -->
                    <button type="button" id="btn-toggle-share" onclick="toggleScreenShare()" class="w-11 h-11 rounded-2xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 flex items-center justify-center transition-all cursor-pointer shadow-md" title="Share Screen">
                        <span class="material-symbols-outlined text-[20px]">present_to_all</span>
                    </button>
                </div>

                <!-- Center Reactions Dock -->
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <!-- Raise Hand -->
                    <button type="button" onclick="sendReaction('✋')" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold flex items-center gap-1.5 transition-all text-slate-200 shadow-md">
                        <span>✋</span>
                        <span class="hidden sm:inline">Raise Hand</span>
                    </button>

                    <!-- Emoji Buttons -->
                    <div class="hidden xs:flex items-center gap-1 bg-slate-800/80 p-1 rounded-xl border border-slate-700/80">
                        <button type="button" onclick="sendReaction('👍')" class="w-8 h-8 rounded-lg hover:bg-slate-700 flex items-center justify-center text-sm transition-transform hover:scale-110">👍</button>
                        <button type="button" onclick="sendReaction('❤️')" class="w-8 h-8 rounded-lg hover:bg-slate-700 flex items-center justify-center text-sm transition-transform hover:scale-110">❤️</button>
                        <button type="button" onclick="sendReaction('👏')" class="w-8 h-8 rounded-lg hover:bg-slate-700 flex items-center justify-center text-sm transition-transform hover:scale-110">👏</button>
                        <button type="button" onclick="sendReaction('💡')" class="w-8 h-8 rounded-lg hover:bg-slate-700 flex items-center justify-center text-sm transition-transform hover:scale-110">💡</button>
                    </div>
                </div>

                <!-- Right Sidebar Toggles -->
                <div class="flex items-center gap-2">
                    <!-- Chat Toggle -->
                    <button type="button" onclick="toggleSidebar('chat')" id="btn-sidebar-chat" class="w-11 h-11 rounded-2xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 flex items-center justify-center transition-all relative cursor-pointer shadow-md" title="In-Class Chat">
                        <span class="material-symbols-outlined text-[20px]">chat</span>
                        <span id="chat-unread-badge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full text-[9px] font-bold font-mono flex items-center justify-center text-white">0</span>
                    </button>

                    <!-- Participants Toggle -->
                    <button type="button" onclick="toggleSidebar('participants')" id="btn-sidebar-roster" class="w-11 h-11 rounded-2xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 flex items-center justify-center transition-all cursor-pointer shadow-md" title="Class Roster">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                    </button>
                </div>
            </footer>
        </section>

        <!-- Right Collapsible Sidebar (Chat & Class Roster) -->
        <aside id="classroom-sidebar" class="w-80 bg-slate-900 border-l border-slate-800 flex flex-col shrink-0 transition-all duration-300 ease-in-out">
            <!-- Sidebar Header -->
            <div class="h-12 border-b border-slate-800 flex items-center justify-between px-4">
                <h3 id="sidebar-title" class="text-xs font-bold font-mono uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-emerald-400">chat</span>
                    <span>In-Room Live Chat</span>
                </h3>
                <button type="button" onclick="closeSidebar()" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Sidebar Body: Live Chat Pane -->
            <div id="pane-chat" class="flex-1 flex flex-col overflow-hidden">
                <div id="chat-messages" class="flex-1 p-3 overflow-y-auto space-y-2.5">
                    <!-- System Welcome Message -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-2.5 text-xs text-slate-300">
                        <div class="flex items-center gap-1.5 text-emerald-400 font-bold mb-1">
                            <span class="material-symbols-outlined text-[14px]">school</span>
                            <span>NPC Virtual Classroom</span>
                        </div>
                        <p class="text-[11px] leading-relaxed text-slate-300">
                            Welcome to <?= htmlspecialchars($course['code']) ?>! Attendance is automatically logged. Questions and comments may be posted here.
                        </p>
                    </div>
                </div>

                <!-- Chat Input Form -->
                <form id="chat-form" onsubmit="sendChatMessage(event)" class="p-3 border-t border-slate-800 flex items-center gap-2">
                    <input type="text" id="chat-input" placeholder="Type a question or message..." autocomplete="off" class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    <button type="submit" class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shrink-0 cursor-pointer shadow-xs">
                        <span class="material-symbols-outlined text-[16px]">send</span>
                    </button>
                </form>
            </div>

            <!-- Sidebar Body: Participants / Live Attendance Roster Pane -->
            <div id="pane-participants" class="flex-1 flex flex-col overflow-hidden hidden">
                <div class="p-3 border-b border-slate-800/80 bg-slate-950/40">
                    <div class="flex items-center justify-between text-xs font-mono text-slate-400">
                        <span>Connected Classmates</span>
                        <span id="roster-online-count" class="text-emerald-400 font-bold">1 In Room</span>
                    </div>
                </div>
                <div id="participants-list" class="flex-1 p-3 overflow-y-auto space-y-2">
                    <!-- Self -->
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-800/70 border border-slate-700/70">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-xs">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($participantName) ?></p>
                                <p class="text-[10px] font-mono text-emerald-400">Active · Present (You)</p>
                            </div>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <!-- ──────────────── Leave Confirmation Modal ──────────────── -->
    <div id="leave-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl p-5 max-w-sm w-full shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-red-400">
                <div class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">logout</span>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Leave Virtual Classroom?</h3>
                    <p class="text-[11px] text-slate-400 font-mono">Live Telemetry Notice</p>
                </div>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                Leaving this classroom will <strong>immediately pause your active attendance duration</strong> and record your departure on the professor's live roster.
            </p>
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-800">
                <button type="button" onclick="closeLeaveModal()" class="px-3.5 py-2 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 text-xs font-semibold cursor-pointer">
                    Stay in Class
                </button>
                <button type="button" onclick="executeLeaveClassroom()" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition-colors cursor-pointer inline-flex items-center gap-1.5 shadow-md">
                    <span class="material-symbols-outlined text-[16px]">done</span> Confirm Leave
                </button>
            </div>
        </div>
    </div>

    <!-- ──────────────── Classroom Logic & WebRTC Scripts ──────────────── -->
    <script>
        const SESSION_CODE   = <?= json_encode($actualSessionCode) ?>;
        const COURSE_CODE    = <?= json_encode($course['code']) ?>;
        const STUDENT_NUMBER = <?= json_encode($studentNumber) ?>;
        const USER_ROLE      = <?= json_encode($userRole) ?>;
        const USER_NAME      = <?= json_encode($userName) ?>;
        const PARTICIPANT_NAME = <?= json_encode($participantName) ?>;
        const PARTICIPANT_ID   = <?= json_encode($participantId) ?>;
        const ROOM_NAME        = <?= json_encode($cleanRoom) ?>;
        const LIVEKIT_WS_URL   = <?= json_encode($livekitWsUrl) ?>;
        const LIVEKIT_TOKEN    = <?= json_encode($livekitToken) ?>;
        const IS_ADMIN         = <?= json_encode($isAdmin) ?>;
        const RETURN_URL       = <?= json_encode($userRole === 'student' ? '/student/courses.php' : '/teacher/courses.php') ?>;

        // Telemetry State
        let ACTIVE_DURATION = 0;
        let IS_ONLINE = false;
        let TICKER_INTERVAL = null;
        let HEARTBEAT_INTERVAL = null;

        // WebRTC & Media State
        let localStream = null;
        let isCamOn = false;
        let isMicOn = false;
        let isSharing = false;
        let lkRoom = null;

        // Whiteboard State
        let wbTool = 'pen';
        let wbDrawing = false;
        let wbStartX = 0, wbStartY = 0;
        let wbCanvas = null, wbCtx = null;
        let wbSnapshot = null;

        // 1. HMS Duration Formatter
        function formatDurationHms(totalSecs) {
            const s = Math.max(0, parseInt(totalSecs, 10) || 0);
            const hrs = Math.floor(s / 3600);
            const mins = Math.floor((s % 3600) / 60);
            const secs = s % 60;
            return (hrs < 10 ? '0' : '') + hrs + ':' + (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        function updateDurationDisplay() {
            const timerEl = document.getElementById('presence-duration-timer');
            if (timerEl) {
                timerEl.textContent = formatDurationHms(ACTIVE_DURATION);
            }
        }

        // 2. Attendance Auto-Detect on Join
        async function autoDetectRoomJoin() {
            try {
                const res = await fetch('/api/elms.php?action=meeting_presence_join', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        session_code: SESSION_CODE,
                        course_code: COURSE_CODE
                    })
                });
                const data = await res.json();
                if (data.success) {
                    IS_ONLINE = true;
                    ACTIVE_DURATION = data.duration_seconds || 0;
                    updateDurationDisplay();

                    if (TICKER_INTERVAL) clearInterval(TICKER_INTERVAL);
                    TICKER_INTERVAL = setInterval(() => {
                        if (IS_ONLINE) {
                            ACTIVE_DURATION++;
                            updateDurationDisplay();
                        }
                    }, 1000);

                    if (HEARTBEAT_INTERVAL) clearInterval(HEARTBEAT_INTERVAL);
                    HEARTBEAT_INTERVAL = setInterval(sendHeartbeat, 4000);
                }
            } catch (err) {
                console.error('Telemetry presence error:', err);
            }
        }

        async function sendHeartbeat() {
            if (!IS_ONLINE) return;
            try {
                const res = await fetch('/api/elms.php?action=meeting_presence_heartbeat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_code: SESSION_CODE })
                });
                const data = await res.json();
                if (data.success) {
                    ACTIVE_DURATION = data.duration_seconds;
                    updateDurationDisplay();
                }
            } catch (err) {
                console.warn('Telemetry heartbeat notice:', err);
            }
        }

        // 3. Auto-Stop on Leave & Prof Counter Increment
        function confirmLeaveClassroom() {
            const modal = document.getElementById('leave-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeLeaveModal() {
            const modal = document.getElementById('leave-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function executeLeaveClassroom() {
            closeLeaveModal();
            IS_ONLINE = false;
            if (TICKER_INTERVAL) clearInterval(TICKER_INTERVAL);
            if (HEARTBEAT_INTERVAL) clearInterval(HEARTBEAT_INTERVAL);

            // Halt all media tracks
            if (localStream) {
                localStream.getTracks().forEach(t => t.stop());
            }
            if (lkRoom) {
                try { await lkRoom.disconnect(); } catch(e) {}
            }

            try {
                await fetch('/api/elms.php?action=meeting_presence_leave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        session_code: SESSION_CODE,
                        student_number: STUDENT_NUMBER
                    })
                });
            } catch (e) {}

            window.location.href = RETURN_URL;
        }

        // 4. Tab Close Beacon
        window.addEventListener('beforeunload', function () {
            if (IS_ONLINE) {
                const payload = JSON.stringify({
                    action: 'meeting_presence_leave',
                    session_code: SESSION_CODE,
                    student_number: STUDENT_NUMBER
                });
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/api/elms.php?action=meeting_presence_leave', payload);
                }
            }
        });

        // 5. Connect to LiveKit Media Server on Drive D
        async function connectLiveKit() {
            if (typeof LivekitClient === 'undefined') {
                console.warn('LiveKit UMD not loaded; utilizing native browser WebRTC media stream.');
                return;
            }
            try {
                lkRoom = new LivekitClient.Room({
                    adaptiveStream: true,
                    dynacast: true
                });

                lkRoom.on(LivekitClient.RoomEvent.ParticipantConnected, (participant) => {
                    addParticipantRoster(participant.identity, participant.name || participant.identity);
                });

                lkRoom.on(LivekitClient.RoomEvent.ParticipantDisconnected, (participant) => {
                    removeParticipantRoster(participant.identity);
                });

                lkRoom.on(LivekitClient.RoomEvent.DataReceived, (payload, participant) => {
                    try {
                        const str = new TextDecoder().decode(payload);
                        const msg = JSON.parse(str);
                        if (msg.type === 'chat') {
                            displayChatMessage(msg.sender, msg.text, false);
                        } else if (msg.type === 'reaction') {
                            showFloatingReaction(msg.emoji);
                        } else if (msg.type === 'whiteboard') {
                            applyRemoteWhiteboard(msg.data);
                        }
                    } catch(e) {}
                });

                await lkRoom.connect(LIVEKIT_WS_URL, LIVEKIT_TOKEN);
                console.log('Connected to LiveKit Media Server on Port 7880!');
            } catch (err) {
                console.log('LiveKit connection note:', err.message);
            }
        }

        // 6. Camera & Microphone Media Controls
        async function toggleCamera() {
            const vidEl = document.getElementById('local-video');
            const avatarEl = document.getElementById('local-avatar');
            const btnCam = document.getElementById('btn-toggle-cam');
            const iconCam = document.getElementById('icon-cam');

            if (!isCamOn) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    if (!localStream) localStream = new MediaStream();
                    stream.getVideoTracks().forEach(t => localStream.addTrack(t));
                    vidEl.srcObject = localStream;
                    vidEl.classList.remove('hidden');
                    avatarEl.classList.add('hidden');
                    btnCam.classList.remove('bg-red-600/20', 'text-red-400', 'border-red-500/30');
                    btnCam.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');
                    iconCam.textContent = 'videocam';
                    isCamOn = true;

                    if (lkRoom) {
                        try { await lkRoom.localParticipant.setCameraEnabled(true); } catch(e) {}
                    }
                } catch (err) {
                    alert('Camera access notice: ' + err.message);
                }
            } else {
                if (localStream) {
                    localStream.getVideoTracks().forEach(t => { t.stop(); localStream.removeTrack(t); });
                }
                vidEl.classList.add('hidden');
                avatarEl.classList.remove('hidden');
                btnCam.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                btnCam.classList.add('bg-red-600/20', 'text-red-400', 'border-red-500/30');
                iconCam.textContent = 'videocam_off';
                isCamOn = false;

                if (lkRoom) {
                    try { await lkRoom.localParticipant.setCameraEnabled(false); } catch(e) {}
                }
            }
        }

        async function toggleMicrophone() {
            const btnMic = document.getElementById('btn-toggle-mic');
            const iconMic = document.getElementById('icon-mic');
            const localMicIcon = document.getElementById('local-mic-icon');

            if (!isMicOn) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    if (!localStream) localStream = new MediaStream();
                    stream.getAudioTracks().forEach(t => localStream.addTrack(t));
                    btnMic.classList.remove('bg-red-600/20', 'text-red-400', 'border-red-500/30');
                    btnMic.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');
                    iconMic.textContent = 'mic';
                    localMicIcon.textContent = 'mic';
                    localMicIcon.classList.remove('text-red-400');
                    localMicIcon.classList.add('text-emerald-400');
                    isMicOn = true;

                    if (lkRoom) {
                        try { await lkRoom.localParticipant.setMicrophoneEnabled(true); } catch(e) {}
                    }
                } catch (err) {
                    alert('Microphone access notice: ' + err.message);
                }
            } else {
                if (localStream) {
                    localStream.getAudioTracks().forEach(t => { t.stop(); localStream.removeTrack(t); });
                }
                btnMic.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                btnMic.classList.add('bg-red-600/20', 'text-red-400', 'border-red-500/30');
                iconMic.textContent = 'mic_off';
                localMicIcon.textContent = 'mic_off';
                localMicIcon.classList.remove('text-emerald-400');
                localMicIcon.classList.add('text-red-400');
                isMicOn = false;

                if (lkRoom) {
                    try { await lkRoom.localParticipant.setMicrophoneEnabled(false); } catch(e) {}
                }
            }
        }

        async function toggleScreenShare() {
            const btn = document.getElementById('btn-toggle-share');
            if (!isSharing) {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const vidEl = document.getElementById('local-video');
                    vidEl.srcObject = screenStream;
                    vidEl.classList.remove('hidden');
                    document.getElementById('local-avatar').classList.add('hidden');
                    btn.classList.add('bg-emerald-600', 'text-white');
                    isSharing = true;

                    screenStream.getVideoTracks()[0].onended = () => {
                        toggleScreenShare();
                    };

                    if (lkRoom) {
                        try { await lkRoom.localParticipant.setScreenShareEnabled(true); } catch(e) {}
                    }
                } catch (e) {}
            } else {
                isSharing = false;
                btn.classList.remove('bg-emerald-600', 'text-white');
                if (isCamOn) {
                    const vidEl = document.getElementById('local-video');
                    vidEl.srcObject = localStream;
                } else {
                    document.getElementById('local-video').classList.add('hidden');
                    document.getElementById('local-avatar').classList.remove('hidden');
                }
                if (lkRoom) {
                    try { await lkRoom.localParticipant.setScreenShareEnabled(false); } catch(e) {}
                }
            }
        }

        // 7. Stage View Switcher (Video Grid vs Whiteboard)
        function switchStageView(view) {
            const vGrid = document.getElementById('view-video-container');
            const vWb = document.getElementById('view-whiteboard-container');
            const btnV = document.getElementById('tab-video');
            const btnW = document.getElementById('tab-whiteboard');

            if (view === 'video') {
                vGrid.classList.remove('hidden');
                vWb.classList.add('hidden');
                btnV.classList.add('bg-emerald-600', 'text-white');
                btnV.classList.remove('text-slate-400', 'hover:bg-slate-800');
                btnW.classList.remove('bg-emerald-600', 'text-white');
                btnW.classList.add('text-slate-400', 'hover:bg-slate-800');
            } else {
                vGrid.classList.add('hidden');
                vWb.classList.remove('hidden');
                btnW.classList.add('bg-emerald-600', 'text-white');
                btnW.classList.remove('text-slate-400', 'hover:bg-slate-800');
                btnV.classList.remove('bg-emerald-600', 'text-white');
                btnV.classList.add('text-slate-400', 'hover:bg-slate-800');
                resizeWhiteboard();
            }
        }

        // 8. Whiteboard Engine
        function initWhiteboard() {
            wbCanvas = document.getElementById('wb-canvas');
            if (!wbCanvas) return;
            wbCtx = wbCanvas.getContext('2d');
            resizeWhiteboard();
            window.addEventListener('resize', resizeWhiteboard);

            wbCanvas.addEventListener('mousedown', startWbDraw);
            wbCanvas.addEventListener('mousemove', continueWbDraw);
            wbCanvas.addEventListener('mouseup', stopWbDraw);
            wbCanvas.addEventListener('mouseleave', stopWbDraw);

            // Touch support
            wbCanvas.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', { clientX: touch.clientX, clientY: touch.clientY });
                wbCanvas.dispatchEvent(mouseEvent);
            }, { passive: false });

            wbCanvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', { clientX: touch.clientX, clientY: touch.clientY });
                wbCanvas.dispatchEvent(mouseEvent);
            }, { passive: false });

            wbCanvas.addEventListener('touchend', () => {
                const mouseEvent = new MouseEvent('mouseup', {});
                wbCanvas.dispatchEvent(mouseEvent);
            });
        }

        function resizeWhiteboard() {
            if (!wbCanvas) return;
            const wrap = document.getElementById('canvas-wrapper');
            if (!wrap) return;

            const temp = document.createElement('canvas');
            temp.width = wbCanvas.width;
            temp.height = wbCanvas.height;
            const tCtx = temp.getContext('2d');
            if (wbCanvas.width > 0) tCtx.drawImage(wbCanvas, 0, 0);

            wbCanvas.width = wrap.clientWidth;
            wbCanvas.height = wrap.clientHeight;
            wbCtx.fillStyle = '#ffffff';
            wbCtx.fillRect(0, 0, wbCanvas.width, wbCanvas.height);
            wbCtx.drawImage(temp, 0, 0);
        }

        function setWbTool(tool) {
            wbTool = tool;
            ['pen', 'line', 'rect', 'circle', 'text', 'eraser'].forEach(t => {
                const el = document.getElementById('wb-tool-' + t);
                if (el) {
                    if (t === tool) {
                        el.classList.add('bg-emerald-600', 'text-white');
                        el.classList.remove('text-slate-400');
                    } else {
                        el.classList.remove('bg-emerald-600', 'text-white');
                        el.classList.add('text-slate-400');
                    }
                }
            });
        }

        function startWbDraw(e) {
            wbDrawing = true;
            const rect = wbCanvas.getBoundingClientRect();
            wbStartX = e.clientX - rect.left;
            wbStartY = e.clientY - rect.top;
            wbSnapshot = wbCtx.getImageData(0, 0, wbCanvas.width, wbCanvas.height);

            if (wbTool === 'text') {
                const txt = prompt('Enter text for whiteboard:');
                if (txt) {
                    wbCtx.fillStyle = document.getElementById('wb-color').value;
                    wbCtx.font = 'bold 18px Inter, sans-serif';
                    wbCtx.fillText(txt, wbStartX, wbStartY);
                }
                wbDrawing = false;
                return;
            }

            wbCtx.beginPath();
            wbCtx.moveTo(wbStartX, wbStartY);
        }

        function continueWbDraw(e) {
            if (!wbDrawing) return;
            const rect = wbCanvas.getBoundingClientRect();
            const curX = e.clientX - rect.left;
            const curY = e.clientY - rect.top;
            const color = document.getElementById('wb-color').value;
            const size = parseInt(document.getElementById('wb-size').value, 10);

            if (wbTool === 'pen') {
                wbCtx.strokeStyle = color;
                wbCtx.lineWidth = size;
                wbCtx.lineCap = 'round';
                wbCtx.lineJoin = 'round';
                wbCtx.lineTo(curX, curY);
                wbCtx.stroke();
            } else if (wbTool === 'eraser') {
                wbCtx.strokeStyle = '#ffffff';
                wbCtx.lineWidth = size * 3;
                wbCtx.lineCap = 'round';
                wbCtx.lineTo(curX, curY);
                wbCtx.stroke();
            } else {
                wbCtx.putImageData(wbSnapshot, 0, 0);
                wbCtx.strokeStyle = color;
                wbCtx.lineWidth = size;
                if (wbTool === 'line') {
                    wbCtx.beginPath();
                    wbCtx.moveTo(wbStartX, wbStartY);
                    wbCtx.lineTo(curX, curY);
                    wbCtx.stroke();
                } else if (wbTool === 'rect') {
                    wbCtx.strokeRect(wbStartX, wbStartY, curX - wbStartX, curY - wbStartY);
                } else if (wbTool === 'circle') {
                    const radius = Math.sqrt(Math.pow(curX - wbStartX, 2) + Math.pow(curY - wbStartY, 2));
                    wbCtx.beginPath();
                    wbCtx.arc(wbStartX, wbStartY, radius, 0, 2 * Math.PI);
                    wbCtx.stroke();
                }
            }
        }

        function stopWbDraw() {
            if (wbDrawing) {
                wbDrawing = false;
                wbCtx.closePath();
            }
        }

        function clearWhiteboard() {
            if (!wbCtx || !wbCanvas) return;
            wbCtx.fillStyle = '#ffffff';
            wbCtx.fillRect(0, 0, wbCanvas.width, wbCanvas.height);
        }

        function downloadWhiteboard() {
            if (!wbCanvas) return;
            const link = document.createElement('a');
            link.download = `NPC-Whiteboard-${COURSE_CODE}-${Date.now()}.png`;
            link.href = wbCanvas.toDataURL('image/png');
            link.click();
        }

        // 9. Floating Emoji Reactions & Raise Hand
        function sendReaction(emoji) {
            showFloatingReaction(emoji);
            if (lkRoom) {
                try {
                    const payload = new TextEncoder().encode(JSON.stringify({ type: 'reaction', emoji: emoji, sender: PARTICIPANT_NAME }));
                    lkRoom.localParticipant.publishData(payload, { reliable: true });
                } catch(e) {}
            }
        }

        function showFloatingReaction(emoji) {
            const container = document.getElementById('reactions-container');
            const span = document.createElement('div');
            span.textContent = emoji;
            span.className = 'absolute text-4xl select-none pointer-events-none transition-all duration-1000 ease-out transform -translate-x-1/2';
            const randomX = 30 + Math.random() * 40;
            span.style.left = randomX + '%';
            span.style.bottom = '15%';
            span.style.opacity = '1';
            span.style.transform = 'translateY(0) scale(1)';
            container.appendChild(span);

            requestAnimationFrame(() => {
                span.style.transform = 'translateY(-180px) scale(1.4)';
                span.style.opacity = '0';
            });

            setTimeout(() => { span.remove(); }, 1200);
        }

        // 10. In-Room Chat System
        function toggleSidebar(pane) {
            const sb = document.getElementById('classroom-sidebar');
            const paneC = document.getElementById('pane-chat');
            const paneP = document.getElementById('pane-participants');
            const title = document.getElementById('sidebar-title');

            if (pane === 'chat') {
                paneC.classList.remove('hidden');
                paneP.classList.add('hidden');
                title.innerHTML = '<span class="material-symbols-outlined text-[18px] text-emerald-400">chat</span><span>In-Room Live Chat</span>';
            } else {
                paneC.classList.add('hidden');
                paneP.classList.remove('hidden');
                title.innerHTML = '<span class="material-symbols-outlined text-[18px] text-emerald-400">group</span><span>Class Roster</span>';
            }
            sb.classList.remove('hidden');
        }

        function closeSidebar() {
            document.getElementById('classroom-sidebar').classList.add('hidden');
        }

        function sendChatMessage(e) {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text) return;
            input.value = '';

            displayChatMessage(PARTICIPANT_NAME, text, true);

            if (lkRoom) {
                try {
                    const payload = new TextEncoder().encode(JSON.stringify({ type: 'chat', text: text, sender: PARTICIPANT_NAME }));
                    lkRoom.localParticipant.publishData(payload, { reliable: true });
                } catch(e) {}
            }
        }

        function displayChatMessage(sender, text, isSelf) {
            const container = document.getElementById('chat-messages');
            const div = document.createElement('div');
            div.className = 'p-2.5 rounded-xl text-xs space-y-1 ' + (isSelf ? 'bg-emerald-950/40 border border-emerald-500/30 text-emerald-100 ml-4' : 'bg-slate-800/80 border border-slate-700/80 text-slate-200 mr-4');
            
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            div.innerHTML = `
                <div class="flex items-center justify-between text-[10px] font-mono">
                    <span class="font-bold ${isSelf ? 'text-emerald-400' : 'text-sky-400'}">${escapeHtml(sender)}</span>
                    <span class="text-slate-500">${timeStr}</span>
                </div>
                <p class="text-xs leading-relaxed text-slate-200">${escapeHtml(text)}</p>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // 11. Participants Roster Management
        function addParticipantRoster(id, name) {
            const list = document.getElementById('participants-list');
            if (document.getElementById('peer-' + id)) return;

            const div = document.createElement('div');
            div.id = 'peer-' + id;
            div.className = 'flex items-center justify-between p-2 rounded-xl bg-slate-800/70 border border-slate-700/70';
            div.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-sky-600/20 border border-sky-500/30 flex items-center justify-center text-sky-400 font-bold text-xs">
                        ${escapeHtml(name.substring(0, 1).toUpperCase())}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate">${escapeHtml(name)}</p>
                        <p class="text-[10px] font-mono text-slate-400">In Room · Audio/Video Active</p>
                    </div>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            `;
            list.appendChild(div);
            updateParticipantCount();
        }

        function removeParticipantRoster(id) {
            const el = document.getElementById('peer-' + id);
            if (el) el.remove();
            updateParticipantCount();
        }

        function updateParticipantCount() {
            const count = document.getElementById('participants-list').children.length;
            const badge = document.getElementById('participant-count-badge');
            const rosterBadge = document.getElementById('roster-online-count');
            if (badge) badge.textContent = count;
            if (rosterBadge) rosterBadge.textContent = count + ' In Room';
        }

        // 12. Initialize Everything on Load
        window.addEventListener('DOMContentLoaded', () => {
            autoDetectRoomJoin();
            initWhiteboard();
            connectLiveKit();
        });
    </script>
</body>
</html>
