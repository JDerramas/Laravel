<?php
/**
 * live_room_livekit_direct.php — Navotas Polytechnic College (NPC) ELMS
 * Direct LiveKit Cloud Integration
 * Ultra-low latency WebRTC, no local docker required for video
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? 'NPC User';
$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';
$studentNumber = $_SESSION['student_number'] ?? '2024001';
$studentSection = $_SESSION['section'] ?? '2A';

if (empty($userEmail)) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/includes/supabase_helper.php';
require_once __DIR__ . '/includes/livekit_helper.php';

$env = loadEnv();
$liveKitUrl = $env['LIVEKIT_URL'] ?? 'wss://plugnmeet-7nzjcv92.livekit.cloud';
$apiKey = $env['LIVEKIT_API_KEY'] ?? 'APISWZfE37YJjg9';
$apiSecret = $env['LIVEKIT_API_SECRET'] ?? 'alANxZ42c5YPR05jobgnbYWBmaE94PW5D2pRLf7L85d';

$sessionCode = trim($_GET['session_code'] ?? '');
$courseCode  = trim($_GET['course_code'] ?? '');
$roomId      = trim($_GET['room_id'] ?? '');

// Load course details
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
        'title' => 'Virtual Classroom Lecture',
        'section' => $studentSection,
        'instructor' => 'Assigned Faculty'
    ];
}

$isAdmin = in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar']);
$participantName = ($userRole === 'student')
    ? ($userName . " (" . ($studentNumber ?: 'Student') . ")")
    : ("Prof. " . $userName . " [Instructor]");

$participantId = ($userRole === 'student')
    ? ("stu_" . preg_replace('/[^a-zA-Z0-9]/', '', $studentNumber ?: $userEmail))
    : ("fac_" . preg_replace('/[^a-zA-Z0-9]/', '', $userEmail));

// Generate clean room ID
$liveKitRoomId = preg_replace('/[^A-Za-z0-9_-]/', '', "NPC-{$course['code']}-{$course['section']}");

// Automatic student presence tracking
$presenceFile = __DIR__ . '/backend/elms_presence.json';
if ($userRole === 'student' && !empty($sessionCode)) {
    require_once __DIR__ . '/api/elms.php';
    if (function_exists('registerStudentLivePresenceJoin')) {
        @registerStudentLivePresenceJoin($sessionCode, $course['code'], $studentNumber, $userName, $userEmail, $presenceFile, $elmsFile);
    }
}

// Generate LiveKit token using Pure PHP helper
$accessToken = generateLiveKitAccessToken(
    $apiKey,
    $apiSecret,
    $participantId,
    $participantName,
    $liveKitRoomId,
    $isAdmin,
    [
        'email' => $userEmail,
        'role' => $userRole,
        'section' => $studentSection
    ]
);

$returnPath = ($userRole === 'student') ? '/student/courses.php' : '/teacher/courses.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['code']) ?> - LiveKit Virtual Classroom</title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        :root {
            --npc-green: #006837;
            --npc-gold: #f59e0b;
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.85);
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-dark); color: #f8fafc; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        
        /* Top Navigation Header */
        .top-bar {
            height: 60px;
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 50;
        }
        .class-meta { display: flex; align-items: center; gap: 12px; }
        .badge {
            background: var(--npc-green);
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .class-title { font-size: 15px; font-weight: 600; color: #fff; }
        .live-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .live-pulse {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            box-shadow: 0 0 8px #ef4444;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* Stage / Video Grid */
        .stage-container {
            flex: 1;
            padding: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            overflow-y: auto;
            align-content: center;
            justify-items: center;
        }
        .video-tile {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            height: 100%;
            min-height: 240px;
            max-height: calc(100vh - 170px);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .video-tile.active-speaker { border-color: var(--npc-gold); }
        .video-tile video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--npc-green);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .participant-overlay {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(6px);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Bottom Controls Bar */
        .bottom-bar {
            height: 80px;
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 50;
        }
        .btn-ctrl {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #334155;
            color: #fff;
            border: none;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-ctrl:hover { background: #475569; transform: translateY(-2px); }
        .btn-ctrl.off { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
        .btn-ctrl.active { background: var(--npc-green); }
        .btn-leave {
            background: var(--danger);
            color: #fff;
            border: none;
            padding: 0 24px;
            height: 48px;
            border-radius: 24px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-leave:hover { background: #dc2626; transform: translateY(-2px); }
        
        #conn-status {
            position: fixed;
            top: 70px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            display: none;
            z-index: 99;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="class-meta">
            <span class="badge"><?= htmlspecialchars($course['code']) ?></span>
            <span class="class-title"><?= htmlspecialchars($course['title']) ?></span>
            <span class="live-tag"><span class="live-pulse"></span> LIVEKIT CLOUD</span>
        </div>
        <div style="font-size: 13px; color: #94a3b8;">
            Room: <strong style="color: #fff;"><?= htmlspecialchars($liveKitRoomId) ?></strong> &nbsp;|&nbsp; 
            User: <strong style="color: #fff;"><?= htmlspecialchars($participantName) ?></strong>
        </div>
    </div>

    <div id="conn-status">Connecting to LiveKit Cloud...</div>

    <div class="stage-container" id="stage">
        <!-- Local participant tile -->
        <div class="video-tile" id="tile-local">
            <div class="avatar-placeholder" id="avatar-local">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <video id="video-local" autoplay playsinline muted style="display:none;"></video>
            <div class="participant-overlay">
                <span class="material-symbols-outlined" style="font-size:16px;">person</span>
                <span><?= htmlspecialchars($participantName) ?> (You)</span>
            </div>
        </div>
    </div>

    <div class="bottom-bar">
        <button class="btn-ctrl" id="btn-mic" onclick="toggleMic()" title="Microphone">
            <span class="material-symbols-outlined" id="icon-mic">mic</span>
        </button>
        <button class="btn-ctrl" id="btn-cam" onclick="toggleCam()" title="Camera">
            <span class="material-symbols-outlined" id="icon-cam">videocam</span>
        </button>
        <button class="btn-ctrl" id="btn-share" onclick="toggleShare()" title="Share Screen">
            <span class="material-symbols-outlined">present_to_all</span>
        </button>
        <button class="btn-leave" onclick="leaveMeeting()">
            <span class="material-symbols-outlined">call_end</span> Leave Class
        </button>
    </div>

    <script>
        const LIVEKIT_URL = <?= json_encode($liveKitUrl) ?>;
        const ACCESS_TOKEN = <?= json_encode($accessToken) ?>;
        const ROOM_ID = <?= json_encode($liveKitRoomId) ?>;
        const RETURN_PATH = <?= json_encode($returnPath) ?>;

        let room = null;
        let isMicOn = true;
        let isCamOn = true;
        let isSharing = false;

        const statusEl = document.getElementById('conn-status');

        function setStatus(text, show = true) {
            statusEl.innerText = text;
            statusEl.style.display = show ? 'block' : 'none';
        }

        async function initLiveKit() {
            setStatus("Connecting to LiveKit Cloud WebRTC...", true);
            try {
                room = new LivekitClient.Room({
                    adaptiveStream: true,
                    dynacast: true,
                    videoCaptureDefaults: {
                        resolution: LivekitClient.VideoPresets.h720.resolution
                    }
                });

                // Remote participant connected
                room.on(LivekitClient.RoomEvent.ParticipantConnected, participant => {
                    console.log('Participant joined:', participant.identity);
                });

                // Track subscribed
                room.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => {
                    attachRemoteTrack(track, participant);
                });

                // Track unsubscribed
                room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
                    track.detach();
                    const el = document.getElementById('tile-' + participant.identity);
                    if (el && !participant.tracks.size) {
                        el.remove();
                    }
                });

                // Participant disconnected
                room.on(LivekitClient.RoomEvent.ParticipantDisconnected, participant => {
                    const el = document.getElementById('tile-' + participant.identity);
                    if (el) el.remove();
                });

                // Active speakers
                room.on(LivekitClient.RoomEvent.ActiveSpeakersChanged, speakers => {
                    document.querySelectorAll('.video-tile').forEach(t => t.classList.remove('active-speaker'));
                    speakers.forEach(s => {
                        const id = s.isLocal ? 'tile-local' : 'tile-' + s.identity;
                        const tile = document.getElementById(id);
                        if (tile) tile.classList.add('active-speaker');
                    });
                });

                await room.connect(LIVEKIT_URL, ACCESS_TOKEN);
                setStatus("Connected to LiveKit Cloud", false);

                // Publish local audio & video
                await room.localParticipant.enableCameraAndMicrophone();
                
                // Attach local video track
                const videoTrack = room.localParticipant.getTrackPublication(LivekitClient.Track.Source.Camera)?.videoTrack;
                if (videoTrack) {
                    const videoEl = document.getElementById('video-local');
                    videoTrack.attach(videoEl);
                    videoEl.style.display = 'block';
                    document.getElementById('avatar-local').style.display = 'none';
                }

            } catch (err) {
                console.error("LiveKit connection error:", err);
                setStatus("Connection error: " + err.message, true);
            }
        }

        function attachRemoteTrack(track, participant) {
            let tile = document.getElementById('tile-' + participant.identity);
            if (!tile) {
                tile = document.createElement('div');
                tile.className = 'video-tile';
                tile.id = 'tile-' + participant.identity;

                const avatar = document.createElement('div');
                avatar.className = 'avatar-placeholder';
                avatar.innerText = (participant.name || participant.identity).substring(0, 1).toUpperCase();
                avatar.id = 'avatar-' + participant.identity;
                tile.appendChild(avatar);

                const overlay = document.createElement('div');
                overlay.className = 'participant-overlay';
                overlay.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;">person</span><span>${participant.name || participant.identity}</span>`;
                tile.appendChild(overlay);

                document.getElementById('stage').appendChild(tile);
            }

            if (track.kind === 'video') {
                const videoEl = track.attach();
                videoEl.style.width = '100%';
                videoEl.style.height = '100%';
                videoEl.style.objectFit = 'cover';
                tile.appendChild(videoEl);
                const av = document.getElementById('avatar-' + participant.identity);
                if (av) av.style.display = 'none';
            } else if (track.kind === 'audio') {
                const audioEl = track.attach();
                document.body.appendChild(audioEl);
            }
        }

        async function toggleMic() {
            if (!room) return;
            isMicOn = !isMicOn;
            await room.localParticipant.setMicrophoneEnabled(isMicOn);
            const btn = document.getElementById('btn-mic');
            const icon = document.getElementById('icon-mic');
            btn.classList.toggle('off', !isMicOn);
            icon.innerText = isMicOn ? 'mic' : 'mic_off';
        }

        async function toggleCam() {
            if (!room) return;
            isCamOn = !isCamOn;
            await room.localParticipant.setCameraEnabled(isCamOn);
            const btn = document.getElementById('btn-cam');
            const icon = document.getElementById('icon-cam');
            const videoEl = document.getElementById('video-local');
            const avatarEl = document.getElementById('avatar-local');

            btn.classList.toggle('off', !isCamOn);
            icon.innerText = isCamOn ? 'videocam' : 'videocam_off';
            videoEl.style.display = isCamOn ? 'block' : 'none';
            avatarEl.style.display = isCamOn ? 'none' : 'flex';
        }

        async function toggleShare() {
            if (!room) return;
            isSharing = !isSharing;
            try {
                await room.localParticipant.setScreenShareEnabled(isSharing);
                document.getElementById('btn-share').classList.toggle('active', isSharing);
            } catch (err) {
                console.error("Screen share error:", err);
                isSharing = false;
            }
        }

        function leaveMeeting() {
            if (room) {
                room.disconnect();
            }
            window.location.href = RETURN_PATH;
        }

        window.addEventListener('DOMContentLoaded', initLiveKit);
    </script>
</body>
</html>
