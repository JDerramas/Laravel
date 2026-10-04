<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$full_name = $is_logged_in ? (string)$_SESSION['name'] : 'Guest User';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NPC Connect - Attendance Scanner</title>
    <script>
        /* Pre-paint theme: apply saved night-mode before first paint (no flash) */
        (function () {
            try {
                var t = localStorage.getItem('npc-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js"></script>
    <script>if (typeof THREE === 'undefined') { const s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js'; document.head.appendChild(s); }</script>
    <script src="/assets/js/npc-three.js"></script>
    <script src="/assets/js/npc.js"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "rgb(var(--primary-rgb) / <alpha-value>)",
                        "primary-container": "rgb(var(--primary-container-rgb) / <alpha-value>)",
                        "on-primary": "rgb(var(--on-primary-rgb) / <alpha-value>)",
                        "on-primary-container": "rgb(var(--on-primary-container-rgb) / <alpha-value>)",
                        "primary-fixed": "rgb(var(--primary-fixed-rgb) / <alpha-value>)",
                        "primary-fixed-dim": "rgb(var(--primary-fixed-dim-rgb) / <alpha-value>)",
                        "on-primary-fixed": "rgb(var(--on-primary-fixed-rgb) / <alpha-value>)",
                        "on-primary-fixed-variant": "rgb(var(--on-primary-fixed-variant-rgb) / <alpha-value>)",
                        "secondary": "rgb(var(--secondary-rgb) / <alpha-value>)",
                        "secondary-container": "rgb(var(--secondary-container-rgb) / <alpha-value>)",
                        "on-secondary": "rgb(var(--on-secondary-rgb) / <alpha-value>)",
                        "on-secondary-container": "rgb(var(--on-secondary-container-rgb) / <alpha-value>)",
                        "secondary-fixed": "rgb(var(--secondary-fixed-rgb) / <alpha-value>)",
                        "secondary-fixed-dim": "rgb(var(--secondary-fixed-dim-rgb) / <alpha-value>)",
                        "on-secondary-fixed": "rgb(var(--on-secondary-fixed-rgb) / <alpha-value>)",
                        "on-secondary-fixed-variant": "rgb(var(--on-secondary-fixed-variant-rgb) / <alpha-value>)",
                        "npc-gold-muted": "rgb(var(--npc-gold-muted-rgb) / <alpha-value>)",
                        "tertiary": "rgb(var(--tertiary-rgb) / <alpha-value>)",
                        "on-tertiary": "rgb(var(--on-tertiary-rgb) / <alpha-value>)",
                        "tertiary-container": "rgb(var(--tertiary-container-rgb) / <alpha-value>)",
                        "tertiary-fixed": "rgb(var(--tertiary-fixed-rgb) / <alpha-value>)",
                        "tertiary-fixed-dim": "rgb(var(--tertiary-fixed-dim-rgb) / <alpha-value>)",
                        "on-tertiary-fixed": "rgb(var(--on-tertiary-fixed-rgb) / <alpha-value>)",
                        "on-tertiary-fixed-variant": "rgb(var(--on-tertiary-fixed-variant-rgb) / <alpha-value>)",
                        "background": "rgb(var(--background-rgb) / <alpha-value>)",
                        "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
                        "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
                        "surface-bright": "rgb(var(--surface-bright-rgb) / <alpha-value>)",
                        "surface-dim": "rgb(var(--surface-dim-rgb) / <alpha-value>)",
                        "surface-tint": "rgb(var(--surface-tint-rgb) / <alpha-value>)",
                        "surface-variant": "rgb(var(--surface-variant-rgb) / <alpha-value>)",
                        "surface-container-lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
                        "surface-container-low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
                        "surface-container": "rgb(var(--surface-container-rgb) / <alpha-value>)",
                        "surface-container-high": "rgb(var(--surface-container-high-rgb) / <alpha-value>)",
                        "surface-container-highest": "rgb(var(--surface-container-highest-rgb) / <alpha-value>)",
                        "on-surface": "rgb(var(--on-surface-rgb) / <alpha-value>)",
                        "on-surface-variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
                        "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
                        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
                        "status-info": "rgb(var(--status-info-rgb) / <alpha-value>)",
                        "status-success": "rgb(var(--status-success-rgb) / <alpha-value>)",
                        "status-warning": "rgb(var(--status-warning-rgb) / <alpha-value>)",
                        "error": "rgb(var(--error-rgb) / <alpha-value>)",
                        "on-error": "rgb(var(--on-error-rgb) / <alpha-value>)",
                        "error-container": "rgb(var(--error-container-rgb) / <alpha-value>)",
                        "on-error-container": "rgb(var(--on-error-container-rgb) / <alpha-value>)",
                        "inverse-surface": "rgb(var(--inverse-surface-rgb) / <alpha-value>)",
                        "inverse-on-surface": "rgb(var(--inverse-on-surface-rgb) / <alpha-value>)",
                        "inverse-primary": "rgb(var(--inverse-primary-rgb) / <alpha-value>)",
                        "excel-green": "rgb(var(--excel-green-rgb) / <alpha-value>)",
                        "excel-dark": "rgb(var(--excel-dark-rgb) / <alpha-value>)",
                        "excel-light": "rgb(var(--excel-light-rgb) / <alpha-value>)",
                        "excel-border": "rgb(var(--excel-border-rgb) / <alpha-value>)"
                    },
                    fontFamily: {
                        "sans": ["Geist", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                }
            }
        }
    </script>
    <style>
        .scanner-frame {
            position: relative;
            overflow: hidden;
        }

        .scanner-frame::before,
        .scanner-frame::after {
            content: '';
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: #fed488;
            border-style: solid;
            z-index: 20;
            pointer-events: none;
        }

        .scanner-frame::before {
            top: 16px;
            left: 16px;
            border-width: 4px 0 0 4px;
            border-radius: 8px 0 0 0;
        }

        .scanner-frame::after {
            bottom: 16px;
            right: 16px;
            border-width: 0 4px 4px 0;
            border-radius: 0 0 8px 0;
        }

        .scanner-frame-tr {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            border-color: #fed488;
            border-style: solid;
            border-width: 4px 4px 0 0;
            border-radius: 0 8px 0 0;
            z-index: 20;
            pointer-events: none;
        }

        .scanner-frame-bl {
            position: absolute;
            bottom: 16px;
            left: 16px;
            width: 32px;
            height: 32px;
            border-color: #fed488;
            border-style: solid;
            border-width: 0 0 4px 4px;
            border-radius: 0 0 0 8px;
            z-index: 20;
            pointer-events: none;
        }

        .scan-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fed488, #ffffff, #fed488, transparent);
            box-shadow: 0 0 12px #fed488;
            top: 0;
            left: 0;
            animation: scanAnimation 2.2s ease-in-out infinite;
            z-index: 15;
            pointer-events: none;
        }

        @keyframes scanAnimation {
            0% {
                top: 10%;
                opacity: 0;
            }

            15% {
                opacity: 1;
            }

            85% {
                opacity: 1;
            }

            100% {
                top: 90%;
                opacity: 0;
            }
        }

        #qr-video-container video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 1rem;
        }
    </style>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar (Sticky Desktop navigation) -->
        <?php $NPC_PORTAL = 'student'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC Connect</span>
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Attendance Scanner</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <span class="font-mono text-[11px] sm:text-xs font-semibold bg-surface-container px-2 sm:px-3 py-1.5 rounded-md border border-outline-variant text-primary hidden sm:inline shrink-0" id="user-id-chip">ID: <?php echo htmlspecialchars($user_id_display); ?></span>
                    <?php 
                    $userAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                    if (!empty($userAvatar)): ?>
                        <img alt="<?php echo htmlspecialchars($full_name); ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-outline-variant shadow-sm shrink-0"
                            src="<?php echo htmlspecialchars($userAvatar); ?>" referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-primary hidden md:inline" id="user-name-display"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto" id="canvas-container">

                <!-- SCANNER VIEW -->
                <div class="flex-1 flex items-center justify-center bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 sm:p-8 min-h-[60vh] shadow-sm">
                    <div class="max-w-4xl w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

                        <!-- Left Column: Instructions & Manual Entry -->
                        <div class="lg:col-span-5 flex flex-col justify-center space-y-6">
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-status-success/15 text-status-success text-xs font-semibold mb-2">
                                    <span class="w-2 h-2 rounded-full bg-status-success animate-pulse"></span>
                                    Verified Live Attendance
                                </div>
                                <h3 class="text-2xl font-bold text-primary mb-2">Classroom Presence Check</h3>
                                <p class="text-on-surface-variant text-sm leading-relaxed">
                                    Scan your professor's live QR code or enter the active session code. All session codes are strictly validated against active classroom records.
                                </p>
                            </div>

                            <!-- Manual Session Code Form -->
                            <div class="bg-surface-container-low rounded-2xl border border-outline-variant/60 p-6 shadow-sm">
                                <h4 class="font-semibold text-primary mb-3 flex items-center gap-2 text-sm">
                                    <span class="material-symbols-outlined text-lg text-primary">keyboard</span>
                                    Manual Code Entry
                                </h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-mono font-semibold uppercase tracking-wider text-on-surface-variant mb-1" for="session-code-input">Session Code</label>
                                        <input class="w-full border border-outline-variant rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface font-mono text-sm uppercase placeholder:font-sans placeholder:normal-case" id="session-code-input" placeholder="e.g. NPC-DM103-2026-08-21" type="text" onkeydown="if(event.key==='Enter') handleManualSubmit()" />
                                    </div>
                                    <button id="submit-manual-btn" onclick="handleManualSubmit()" class="w-full bg-primary hover:bg-primary-container text-on-primary py-2.5 rounded-xl font-semibold transition-all shadow-sm text-sm flex items-center justify-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-[18px]">verified</span>
                                        <span>Verify & Submit Code</span>
                                    </button>
                                </div>
                            </div>

                            <div class="bg-surface-container rounded-xl p-4 flex items-center gap-3 border border-outline-variant/40">
                                <span class="material-symbols-outlined text-primary text-[20px]">verified_user</span>
                                <p class="text-xs text-on-surface-variant font-medium">Logged in: <strong><?php echo htmlspecialchars($full_name); ?></strong> (<?php echo htmlspecialchars($user_id_display); ?>)</p>
                            </div>
                        </div>

                        <!-- Right Column: Camera Viewfinder -->
                        <div class="lg:col-span-7 flex flex-col items-center justify-center">
                            <div class="w-full aspect-[4/3] max-w-lg bg-black rounded-2xl overflow-hidden relative shadow-lg border-2 border-outline-variant scanner-frame flex items-center justify-center">
                                <div class="scanner-frame-tr"></div>
                                <div class="scanner-frame-bl"></div>
                                <div class="scan-laser" id="laser-line"></div>

                                <!-- 3D Holographic Scanner HUD Overlay -->
                                <div id="scanner-3d-hud-container" class="absolute inset-0 pointer-events-none z-20"></div>

                                <!-- Pure Video Viewfinder Container -->
                                <div id="qr-video-container" class="w-full h-full"></div>

                                <!-- Camera Permission / Fallback State -->
                                <div id="camera-loading" class="absolute inset-0 bg-[#001736] flex flex-col items-center justify-center text-white p-6 text-center z-10">
                                    <span class="material-symbols-outlined text-[48px] text-secondary-container animate-pulse mb-3">videocam</span>
                                    <p class="font-bold text-sm">Starting Camera...</p>
                                    <p class="text-xs text-blue-100/90 mt-1">Please allow camera permissions in your browser or use Manual Code Entry.</p>
                                </div>
                            </div>

                            <div class="mt-5 flex items-center gap-2" id="scan-status-wrapper">
                                <span class="w-2.5 h-2.5 rounded-full bg-status-info animate-pulse" id="scan-pulse"></span>
                                <span class="font-semibold text-sm text-primary" id="scan-status">Camera active — scanning for rotating QR...</span>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- 🌟 Verification Result Modal -->
    <div id="result-modal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl flex flex-col items-center text-center border border-gray-100 transform transition-all scale-95" id="modal-card">
            
            <!-- Result Icon -->
            <div id="modal-icon-container" class="w-20 h-20 rounded-full bg-emerald-100 text-status-success flex items-center justify-center mb-4 shadow-sm">
                <span class="material-symbols-outlined text-[44px]" id="modal-icon">check_circle</span>
            </div>

            <h3 class="text-xl font-bold text-primary mb-1" id="modal-title">Attendance Verified!</h3>
            <p class="text-xs text-gray-500 mb-6" id="modal-subtitle">Your attendance record has been securely submitted to the professor's live roster.</p>

            <!-- Details Card -->
            <div class="bg-surface p-4 rounded-2xl border border-outline-variant/60 w-full text-left space-y-2 mb-6 font-mono text-xs">
                <div class="flex justify-between items-center py-1 border-b border-gray-200">
                    <span class="text-gray-500 font-sans">Course:</span>
                    <span class="font-bold text-primary" id="modal-detail-course">DM103 (AIS 2A)</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-gray-200">
                    <span class="text-gray-500 font-sans">Instructor:</span>
                    <span class="font-bold text-primary" id="modal-detail-prof">Prof. Roderick Castillo</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-gray-200">
                    <span class="text-gray-500 font-sans">Timestamp:</span>
                    <span class="font-bold text-primary" id="modal-detail-time">08:15 AM</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-gray-500 font-sans">Attendance Status:</span>
                    <span id="modal-detail-status" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-status-success/15 text-status-success">Present</span>
                </div>
            </div>

            <button onclick="closeResultModalAndRedirect()" class="w-full py-3 bg-primary text-white font-bold text-sm rounded-xl hover:bg-primary-container shadow-md transition-all">
                Done & Return to Dashboard
            </button>
        </div>
    </div>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;

        let html5QrCode = null;
        let isProcessing = false;

        // 🛡️ SECURE ATTENDANCE VERIFICATION via server-side API
        // Identity comes from the PHP session; all validation happens server-side.
        async function recordAttendance(rawToken, method = 'qr_code') {
            if (isProcessing) return;
            isProcessing = true;

            const statusEl = document.getElementById('scan-status');
            const pulseEl = document.getElementById('scan-pulse');
            const submitBtn = document.getElementById('submit-manual-btn');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">sync</span> Validating with Database...';
            }

            statusEl.innerText = "Verifying session code in database...";
            pulseEl.className = "w-2.5 h-2.5 rounded-full bg-status-warning animate-pulse";

            try {
                if (!String(rawToken || '').trim()) {
                    throw new Error("Session code cannot be empty. Please enter a valid code.");
                }

                const res = await fetch('/api/student.php?action=checkin_attendance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        code: String(rawToken),
                        method: method,
                        csrf_token: csrfToken
                    })
                });

                const data = await res.json().catch(() => ({ success: false, message: 'Invalid server response.' }));

                // Duplicate check-in — inline notice, no alert()
                if (data.duplicate) {
                    pulseEl.className = "w-2.5 h-2.5 rounded-full bg-status-warning";
                    statusEl.innerText = "Already checked in for this session";
                    showInlineNotice(data.message || "You have already completed attendance for this session.", "warning");
                    resetScannerState();
                    return;
                }

                // Expired/closed session — map backend HTTP codes to friendly messages
                if (res.status === 409 || res.status === 410 || res.status === 404) {
                    pulseEl.className = "w-2.5 h-2.5 rounded-full bg-status-warning";
                    statusEl.innerText = "Session unavailable";
                    if (res.status === 404) {
                        showInlineNotice(data.message || "Session code not found. Please verify the code and try again.", "error");
                    } else if (res.status === 410) {
                        showInlineNotice(data.message || "The check-in window for this class has closed. Please request manual check-in from your professor.", "warning");
                    } else {
                        showInlineNotice(data.message || "This session is currently closed. Please contact your professor if you missed the check-in.", "warning");
                    }
                    resetScannerState();
                    return;
                }

                if (!res.ok || !data.success) {
                    pulseEl.className = "w-2.5 h-2.5 rounded-full bg-error";
                    statusEl.innerText = data.message || "Verification failed.";
                    showInlineNotice(data.message || "Verification failed (" + res.status + ").", "error");
                    resetScannerState();
                    return;
                }

                // SUCCESS
                pulseEl.className = "w-2.5 h-2.5 rounded-full bg-status-success";
                statusEl.innerText = "Verified! Attendance recorded successfully.";
                showSuccessModal(data.session || {}, data.status);

            } catch (err) {
                console.error("Attendance verification error:", err);
                pulseEl.className = "w-2.5 h-2.5 rounded-full bg-error";
                statusEl.innerText = "Error: " + err.message;
                showInlineNotice("Attendance verification failed: " + err.message + " — please try again or use Manual Code Entry.", "error");
                resetScannerState();
            }
        }

        // Display Rich Verification Success Slip Modal
        function showSuccessModal(session, status) {
            document.getElementById('modal-detail-course').innerText = `${session.class_code} (${session.section || 'AIS 2A'})`;
            document.getElementById('modal-detail-prof').innerText = session.instructor || 'Faculty Professor';
            document.getElementById('modal-detail-time').innerText = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const statusEl = document.getElementById('modal-detail-status');
            const iconContainer = document.getElementById('modal-icon-container');
            const icon = document.getElementById('modal-icon');
            const title = document.getElementById('modal-title');

            if (status === 'late') {
                statusEl.className = "px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-status-warning/15 text-status-warning";
                statusEl.innerText = "Late Check-in";
                iconContainer.className = "w-20 h-20 rounded-full bg-amber-100 text-status-warning flex items-center justify-center mb-4 shadow-sm";
                icon.innerText = "schedule";
                title.innerText = "Late Attendance Recorded";
            } else {
                statusEl.className = "px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-status-success/15 text-status-success";
                statusEl.innerText = "Present (On-Time)";
                iconContainer.className = "w-20 h-20 rounded-full bg-emerald-100 text-status-success flex items-center justify-center mb-4 shadow-sm";
                icon.innerText = "check_circle";
                title.innerText = "Attendance Verified On-Time!";
            }

            // 🌟 Three.js 3D Attendance Effects
            try {
                if (window.scannerHud3D && typeof window.scannerHud3D.triggerVerificationPulse === 'function') {
                    window.scannerHud3D.triggerVerificationPulse();
                }
                if (window.npcThree && typeof window.npcThree.celebrate3D === 'function') {
                    window.npcThree.celebrate3D({ count: 160 });
                }
                if (window.npcThree && typeof window.npcThree.renderVerificationSeal === 'function') {
                    window.npcThree.renderVerificationSeal('modal-icon-container', { size: 80 });
                }
            } catch (e) {
                console.error("3D verification effect error:", e);
            }

            document.getElementById('result-modal').classList.remove('hidden');
        }

        function closeResultModalAndRedirect() {
            window.location.href = "/student/index.php";
        }

        function resetScannerState() {
            isProcessing = false;
            if (window.scannerHud3D && typeof window.scannerHud3D.setScanState === 'function') {
                window.scannerHud3D.setScanState('scanning');
            }
            const submitBtn = document.getElementById('submit-manual-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">verified</span><span>Verify & Submit Code</span>';
            }

            // Restart camera scan if active
            if (html5QrCode && !html5QrCode.isScanning) {
                startCameraScanner();
            }
        }

        async function handleManualSubmit() {
            const input = document.getElementById('session-code-input');
            const code = input.value.trim();
            if (!code) {
                showInlineNotice('Please enter the session code before submitting.', 'warning');
                return;
            }
            await recordAttendance(code, 'manual');
        }

        // Inline notice banner — replaces native alert() with styled UI
        function showInlineNotice(msg, kind) {
            var existing = document.getElementById('qr-inline-notice');
            if (existing) existing.remove();
            var el = document.createElement('div');
            el.id = 'qr-inline-notice';
            var bgClass = kind === 'error' ? 'bg-error/15 border-error/30 text-error' :
                          kind === 'warning' ? 'bg-status-warning/15 border-status-warning/30 text-status-warning' :
                          'bg-status-success/15 border-status-success/30 text-status-success';
            var icon = kind === 'error' ? 'error' : kind === 'warning' ? 'warning' : 'check_circle';
            el.className = 'fixed top-20 right-6 z-50 max-w-sm rounded-xl border p-4 shadow-2xl flex items-start gap-3 text-xs font-medium ' + bgClass;
            el.innerHTML = '<span class="material-symbols-outlined text-[18px] shrink-0">' + icon + '</span>' +
                '<span class="break-words leading-relaxed">' + String(msg).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>';
            document.body.appendChild(el);
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 6000);
        }

        // Camera Scanner Lifecycle
        async function startCameraScanner() {
            try {
                html5QrCode = new Html5Qrcode("qr-video-container");
                const config = {
                    fps: 15,
                    qrbox: {
                        width: 260,
                        height: 260
                    }
                };

                await html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        if (html5QrCode && html5QrCode.isScanning) {
                            html5QrCode.stop().catch(console.error);
                        }
                        recordAttendance(decodedText, 'qr_code');
                    },
                    (errorMessage) => {
                        // scanning in progress
                    }
                );

                document.getElementById('camera-loading').classList.add('hidden');

            } catch (err) {
                console.error("Camera start failed:", err);
                const loadingDiv = document.getElementById('camera-loading');
                loadingDiv.innerHTML = `
                    <span class="material-symbols-outlined text-[48px] text-error mb-2">videocam_off</span>
                    <p class="font-bold text-sm">Camera Unavailable</p>
                    <p class="text-xs text-blue-100/90 mt-1 max-w-xs">${err.message || 'Please enable camera permissions in your browser or use Manual Code Entry.'}</p>
                `;
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            // Initialize 3D Holographic Scanner HUD Overlay
            try {
                if (window.npcThree && typeof window.npcThree.initScannerHUD === 'function') {
                    window.scannerHud3D = window.npcThree.initScannerHUD('scanner-3d-hud-container');
                }
            } catch (e) {
                console.error("3D Scanner HUD init error:", e);
            }
            startCameraScanner();
        });
    </script>
</body>

</html>