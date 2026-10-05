<?php
require_once __DIR__ . '/../includes/auth.php';
require_teacher();
$teacher_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Faculty Professor';
$teacher_initial = strtoupper(substr($teacher_name, 0, 1));
$teacher_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$selected_class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Live Class Attendance QR - NPC Faculty Portal</title>
    <!-- Tailwind CSS CDN -->
    <script>
        /* Pre-paint theme: apply saved night-mode before first paint (no flash) */
        (function() {
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
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
    <!-- QRCode.js library for 100% reliable local client-side QR generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <script id="npc-role-meta" type="application/json">
        <?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?>
    </script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'faculty';
    include __DIR__ . '/../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col overflow-x-hidden">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-2 sm:gap-4">
                <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC Faculty</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[24px]">qr_code_scanner</span>
                    Live Class Attendance
                </h2>
            </div>
            <!-- Class Selector for Teacher -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px]">map</span>
                    <span class="hidden sm:inline">NPC Map</span>
                </a>
                <label class="text-xs font-bold text-gray-500 font-mono hidden md:inline">SUBJECT:</label>
                <select id="active-class-select" onchange="onClassDropdownChange()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-bold rounded-xl px-2.5 sm:px-3 py-1.5 sm:py-2 focus:outline-none focus:border-primary shadow-sm cursor-pointer max-w-[170px] sm:max-w-[280px] truncate">
                    <option value="">Select a subject...</option>
                </select>
            </div>
        </header>

        <!-- Canvas Container -->
        <div class="p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1 flex flex-col">

            <!-- 📌 1. INITIAL STATE: NO SUBJECT SELECTED VIEW -->
            <div id="no-subject-selected-view" class="hidden flex-col items-center justify-center p-12 bg-surface-container-lowest border border-outline-variant rounded-3xl shadow-sm text-center">
                <div class="w-20 h-20 rounded-3xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-[42px]">school</span>
                </div>
                <h3 class="text-xl font-bold text-primary">Select a Course Subject to Start Attendance</h3>
                <p class="text-xs text-on-surface-variant max-w-md mt-1.5 mb-8">
                    Choose one of your assigned course sections below to configure the grace period and launch the live QR code for your students.
                </p>

                <!-- Subject Cards Grid -->
                <div id="subjects-cards-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 w-full max-w-4xl text-left">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- 📌 2. SUBJECT SELECTED VIEW (Header Overview Card) -->
            <div id="subject-active-view" class="space-y-6">

                <!-- Class Header Banner -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="px-3 py-1 bg-primary text-white font-mono text-xs font-bold rounded-lg" id="banner-class-code">DM103</span>
                            <span class="px-3 py-1 bg-surface-container-low text-primary font-mono text-xs font-bold rounded-lg border border-outline-variant" id="banner-class-section">AIS 2A</span>
                            <span class="px-3 py-1 bg-emerald-50 text-status-success font-mono text-xs font-bold rounded-lg border border-emerald-200" id="banner-class-students-count">0 Enrolled</span>
                        </div>
                        <h1 class="text-2xl font-bold text-primary tracking-tight" id="banner-class-title">BUSINESS PROCESS MANAGEMENT</h1>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-on-surface-variant font-medium mt-2">
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-status-info">schedule</span> <span id="banner-class-schedule">Saturday (07:00 AM - 10:00 AM)</span></span>
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-secondary">location_on</span> <span id="banner-class-room">Room 302</span></span>
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-primary">person</span> <span id="banner-class-prof">Prof. Roderick Castillo</span></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <a id="link-excel-grades" href="/teacher/grades.php" class="px-3.5 py-2.5 bg-surface border border-outline-variant/60 hover:bg-surface-container text-xs font-bold rounded-xl text-primary flex items-center gap-1.5 shadow-sm transition-all">
                            <span class="material-symbols-outlined text-[16px] text-[#107c41]">table_chart</span> Open Grade Sheet
                        </a>
                    </div>
                </div>

                <!-- 📊 ATTENDANCE SUMMARY STATS -->
                <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                        <span class="text-[11px] font-mono font-bold uppercase text-on-surface-variant">Enrolled Total</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-primary mt-1" id="stat-total-enrolled">0</h3>
                        <p class="text-[11px] text-on-surface-variant mt-0.5">Students in section</p>
                    </div>

                    <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                        <span class="text-[11px] font-mono font-bold uppercase text-status-success">Present (On-Time)</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-status-success mt-1" id="stat-present">0</h3>
                        <p class="text-[11px] text-status-success mt-0.5">Checked-in on-time</p>
                    </div>

                    <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                        <span class="text-[11px] font-mono font-bold uppercase text-status-warning">Late Students</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-status-warning mt-1" id="stat-late">0</h3>
                        <p class="text-[11px] text-status-warning mt-0.5">Past on-time grace period</p>
                    </div>

                    <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                        <span class="text-[11px] font-mono font-bold uppercase text-error">Absent</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-error mt-1" id="stat-absent">0</h3>
                        <p class="text-[11px] text-error mt-0.5">No scan record logged</p>
                    </div>
                </section>

                <!-- 📌 3. PRE-SESSION LAUNCH CARD (QR IS HIDDEN HERE) -->
                <div id="pre-session-card" class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-8 shadow-sm flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-secondary-container/50 text-secondary flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-[36px]">qr_code</span>
                    </div>
                    <h2 class="text-xl font-bold text-primary">Live Attendance QR is Inactive</h2>
                    <p class="text-xs text-on-surface-variant max-w-lg mt-1 mb-6">
                        The QR code is currently hidden to prevent early scans. Configure the countdown timer durations below and click <strong class="text-primary">"Start Attendance Session"</strong> to reveal the live QR code.
                    </p>

                    <!-- Timer Configuration Settings -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full max-w-lg mb-6 text-left">
                        <div class="bg-surface p-4 rounded-xl border border-outline-variant/60">
                            <label class="block text-xs font-bold text-status-success mb-1 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-status-success"></span> Present (On-Time) Duration
                            </label>
                            <select id="config-present-mins" class="w-full bg-white border border-gray-300 text-xs font-bold rounded-lg px-3 py-2 focus:ring-1 focus:ring-primary focus:outline-none">
                                <option value="5">5 Minutes</option>
                                <option value="10" selected>10 Minutes (Standard)</option>
                                <option value="15">15 Minutes</option>
                                <option value="20">20 Minutes</option>
                            </select>
                            <span class="text-[10px] text-gray-500 mt-1 block">Students scanning within this time get marked Present.</span>
                        </div>

                        <div class="bg-surface p-4 rounded-xl border border-outline-variant/60">
                            <label class="block text-xs font-bold text-status-warning mb-1 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-status-warning"></span> Late Grace Period
                            </label>
                            <select id="config-late-mins" class="w-full bg-white border border-gray-300 text-xs font-bold rounded-lg px-3 py-2 focus:ring-1 focus:ring-primary focus:outline-none">
                                <option value="5" selected>5 Minutes</option>
                                <option value="10">10 Minutes</option>
                                <option value="15">15 Minutes</option>
                                <option value="0">No Late buffer (Direct Absent)</option>
                            </select>
                            <span class="text-[10px] text-gray-500 mt-1 block">Students scanning after Present duration get marked Late.</span>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <button onclick="startLiveAttendanceSession()" class="px-8 py-3.5 bg-[#107c41] hover:bg-[#0c5d31] text-white text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2 transform hover:-translate-y-0.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]">play_arrow</span>
                        <span>Start Attendance Session & Display QR Code</span>
                    </button>
                </div>

                <!-- 📌 4. ACTIVE LIVE ATTENDANCE SESSION CONTAINER (Revealed on Start) -->
                <div id="active-session-container" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Left: Interactive Live QR Code Panel (5 cols) -->
                    <div class="lg:col-span-5 flex flex-col gap-4">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col items-center text-center">

                            <!-- Session Phase Indicator Banner -->
                            <div class="flex items-center justify-between w-full mb-3">
                                <span id="session-phase-badge" class="px-3 py-1 bg-status-success/15 text-status-success text-xs font-bold rounded-full flex items-center gap-1.5 shadow-xs">
                                    <span class="w-2 h-2 rounded-full bg-status-success animate-ping"></span>
                                    <span id="session-phase-text">ON-TIME WINDOW (PRESENT)</span>
                                </span>
                                <span class="text-[11px] font-mono text-on-surface-variant font-bold" id="rotation-status-badge">Auto-Rotating</span>
                            </div>

                            <!-- Crisp Client-side QR Code Container using QRCode.js -->
                            <div class="bg-white p-4 rounded-2xl border-2 border-primary-container shadow-inner mb-4 relative flex items-center justify-center min-h-[250px] min-w-[250px]" id="qr-wrapper">
                                <div id="qrcode-canvas" class="flex items-center justify-center"></div>
                                <div class="absolute bottom-2 right-2 bg-primary text-white text-[9px] font-mono px-1.5 py-0.5 rounded font-bold shadow-xs" id="qr-token-indicator">TOKEN: LIVE</div>
                            </div>

                            <!-- Session Code Box -->
                            <div class="w-full bg-surface p-3 rounded-xl border border-outline-variant/60 mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] uppercase font-mono text-gray-500 font-bold">Manual Session Code</p>
                                    <p class="text-base font-mono font-bold text-primary tracking-wider" id="display-session-code">NPC-DM103-2026-08-21</p>
                                </div>
                                <button onclick="copySessionCode()" title="Copy Session Code" class="p-2 hover:bg-surface-container rounded-lg text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                </button>
                            </div>

                            <!-- Countdown Timer with Phase Feedback -->
                            <div class="w-full bg-surface-subtle p-4 rounded-xl border border-outline-variant/50 flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-mono uppercase font-bold text-on-surface-variant" id="timer-label">On-Time Countdown</span>
                                        <div class="text-3xl font-mono font-bold text-status-success transition-colors" id="countdown-timer">10:00</div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="addTime(5)" class="px-2.5 py-1.5 bg-surface border border-outline-variant/60 hover:bg-surface-container text-xs font-bold rounded-lg shadow-xs" title="Add 5 Minutes">+5m</button>
                                        <button onclick="toggleTimer()" id="timer-toggle-btn" class="px-3 py-1.5 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold rounded-lg shadow-xs flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]" id="timer-btn-icon">pause</span> Pause
                                        </button>
                                        <button onclick="rotateQRToken(true)" title="Refresh QR Token" class="px-2.5 py-1.5 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-lg hover:brightness-95 shadow-xs">
                                            <span class="material-symbols-outlined text-[15px]">refresh</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                    <div id="timer-progress-bar" class="bg-status-success h-full transition-all duration-1000" style="width: 100%;"></div>
                                </div>
                            </div>

                            <!-- Session Termination / Stop Button -->
                            <div class="w-full mt-4 flex items-center justify-between gap-3">
                                <button onclick="openQrModal()" class="flex-1 py-2.5 bg-surface border border-outline-variant/70 hover:bg-surface-container text-primary font-bold text-xs rounded-xl shadow-xs flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">fullscreen</span> Project Fullscreen
                                </button>
                                <button onclick="endAttendanceSession()" class="flex-1 py-2.5 bg-error/10 hover:bg-error/20 text-error font-bold text-xs rounded-xl border border-error/20 flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">stop_circle</span> End Session
                                </button>
                                <button onclick="exportSessionCsv()" title="Download this session's attendance as CSV" class="px-4 py-2.5 bg-surface hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-bold text-primary flex items-center justify-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">download</span> Export CSV
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Right: Real-time Alphabetical Roster Table (7 cols) -->
                    <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col">
                        <div class="p-4 px-6 border-b border-outline-variant bg-surface-subtle flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-primary text-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                                    Enrolled Roster Check-ins (A to Z)
                                </h3>
                                <p class="text-[11px] text-on-surface-variant">Live updates as students scan the QR code or enter code</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="markAllPresent()" class="text-xs px-3 py-1.5 bg-emerald-50 text-status-success font-bold rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                    Mark All Present
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto flex-1 max-h-[560px]">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="sticky top-0 bg-surface-container-low z-10">
                                    <tr class="font-mono uppercase text-on-surface border-b border-outline-variant">
                                        <th class="py-3 px-4 font-bold">Student Name</th>
                                        <th class="py-3 px-4 font-bold">Student No.</th>
                                        <th class="py-3 px-4 font-bold">Check-in Time</th>
                                        <th class="py-3 px-4 font-bold text-right">Status</th>
                                        <th class="py-3 px-4 font-bold text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="roster-tbody" class="divide-y divide-outline-variant/30 font-medium">
                                    <tr>
                                        <td colspan="8">
                                            <div class="npc-skeleton-group py-2" aria-busy="true">
                                                <div class="sk-table-row">
                                                    <div class="skeleton sk-line" style="margin-bottom:0"></div>
                                                    <div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div>
                                                    <div class="skeleton sk-chip"></div>
                                                    <div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div>
                                                </div>
                                                <div class="sk-table-row" style="padding-bottom:0">
                                                    <div class="skeleton sk-line" style="margin-bottom:0"></div>
                                                    <div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div>
                                                    <div class="skeleton sk-chip"></div>
                                                    <div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Excuse Letters Review Panel -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm mt-6">
                <div class="p-5 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                    <div>
                        <h3 class="font-bold text-primary text-base flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">mark_email_read</span>
                            Excuse Letters (submitted to you)
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Review student excuse letters. When approved with a session code, the attendance record automatically updates to 'Excused'.</p>
                    </div>
                    <button onclick="loadExcusesReview()" class="px-3 py-2 bg-surface hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-bold text-primary flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[15px]">refresh</span> Refresh
                    </button>
                </div>
                <div id="excuses-review-list" class="divide-y divide-outline-variant/40 max-h-80 overflow-y-auto custom-scroll">
                    <div class="npc-skeleton-group p-4" aria-busy="true">
                        <div class="sk-row">
                            <div class="skeleton sk-avatar"></div>
                            <div class="flex-1 min-w-0">
                                <div class="skeleton sk-line w-2/3"></div>
                                <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                            </div>
                        </div>
                        <div class="sk-row" style="padding-bottom:0">
                            <div class="skeleton sk-avatar"></div>
                            <div class="flex-1 min-w-0">
                                <div class="skeleton sk-line w-3/4"></div>
                                <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Fullscreen QR Code Modal for Classroom Projector -->
    <div id="fullscreen-qr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl flex flex-col items-center text-center relative border border-gray-200">
            <button onclick="closeQrModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">
                <span class="material-symbols-outlined text-[28px]">close</span>
            </button>
            <span class="px-3 py-1 bg-primary text-white text-xs font-mono font-bold rounded-lg mb-2" id="modal-class-title">DM103 - AIS 2A</span>
            <h2 class="text-xl font-bold text-primary mb-1">Scan for Live Attendance</h2>
            <p class="text-xs text-gray-500 mb-6">Point your phone camera or Student Portal scanner at the QR code.</p>

            <div class="bg-white p-4 rounded-2xl border-4 border-[#107c41] shadow-xl mb-4">
                <div id="modal-qrcode-canvas"></div>
            </div>

            <div class="bg-surface p-3 rounded-xl border w-full font-mono text-center">
                <span class="text-[10px] text-gray-500 font-bold block">SESSION CODE</span>
                <span class="text-lg font-bold text-primary tracking-widest" id="modal-session-code">NPC-DM103-2026-08-21</span>
            </div>

            <div class="mt-4 text-xs font-mono font-bold text-status-success" id="modal-timer-display">
                Present Window Remaining: 10:00
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 bg-gray-900 text-white text-xs px-4 py-3 rounded-xl shadow-2xl z-50 flex items-center gap-2.5 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-2">
        <span class="material-symbols-outlined text-[18px] text-emerald-400" id="toast-icon">check_circle</span>
        <span id="toast-message">Notification</span>
    </div>

    <script>
        // Supabase Client Config
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);

        const currentTeacherName = <?= json_encode($teacher_name) ?>;
        const currentTeacherEmail = <?= json_encode($teacher_email) ?>;
        const isAdmin = <?= json_encode($is_admin) ?>;
        const csrfToken = <?= json_encode($csrf_token) ?>;

        // State variables
        let activeClasses = [];
        let currentClass = null;
        let enrolledStudents = [];
        let attendanceLogs = [];
        let currentSessionCode = '';
        let currentQrToken = '';
        let qrCodeInstance = null;
        let modalQrCodeInstance = null;

        // Session & Timer State
        let isSessionActive = false;
        let sessionStartTime = 0;
        let presentDurationSeconds = 600; // 10 mins
        let lateDurationSeconds = 300; // 5 mins
        let totalSessionDuration = 900;
        let currentPhase = 'present'; // 'present' | 'late' | 'expired'
        let secondsRemaining = 600;
        let timerInterval = null;
        let isTimerPaused = false;
        let pollingInterval = null;

        // Helper: Last Name First Formatting & Sorting
        function parseName(fullName) {
            if (!fullName || typeof fullName !== 'string') return {
                lastName: '',
                firstName: '',
                formatted: ''
            };
            const clean = fullName.trim();
            if (!clean) return {
                lastName: '',
                firstName: '',
                formatted: ''
            };

            if (clean.includes(',')) {
                const parts = clean.split(',').map(s => s.trim());
                return {
                    lastName: parts[0],
                    firstName: parts.slice(1).join(', '),
                    formatted: clean
                };
            }

            const tokens = clean.split(/\s+/);
            if (tokens.length === 1) {
                return {
                    lastName: tokens[0],
                    firstName: '',
                    formatted: tokens[0]
                };
            }

            const suffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v'];
            let suffix = '';
            let workTokens = [...tokens];
            const lastTokenLower = workTokens[workTokens.length - 1].toLowerCase();
            if (suffixes.includes(lastTokenLower) && workTokens.length > 2) {
                suffix = ' ' + workTokens.pop();
            }

            const lowerTokens = workTokens.map(t => t.toLowerCase());
            let lastName = '';
            let firstName = '';

            if (workTokens.length >= 3 && (lowerTokens[workTokens.length - 2] === 'dela' || lowerTokens[workTokens.length - 2] === 'del' || lowerTokens[workTokens.length - 2] === 'san')) {
                lastName = workTokens.slice(-2).join(' ') + suffix;
                firstName = workTokens.slice(0, -2).join(' ');
            } else if (workTokens.length >= 4 && lowerTokens[workTokens.length - 3] === 'de' && (lowerTokens[workTokens.length - 2] === 'la' || lowerTokens[workTokens.length - 2] === 'los')) {
                lastName = workTokens.slice(-3).join(' ') + suffix;
                firstName = workTokens.slice(0, -3).join(' ');
            } else {
                lastName = workTokens[workTokens.length - 1] + suffix;
                firstName = workTokens.slice(0, -1).join(' ');
            }

            const formatted = `${lastName.toUpperCase()}, ${firstName.toUpperCase()}`;
            return {
                lastName,
                firstName,
                formatted
            };
        }

        function formatLastNameFirst(name) {
            return parseName(name).formatted;
        }

        function compareByLastName(aName, bName) {
            const a = parseName(aName);
            const b = parseName(bName);
            const cmp = a.lastName.localeCompare(b.lastName, undefined, {
                sensitivity: 'base'
            });
            if (cmp !== 0) return cmp;
            return a.firstName.localeCompare(b.firstName, undefined, {
                sensitivity: 'base'
            });
        }

        // Initialize Attendance Application
        async function initFacultyAttendance() {
            // 1. Fetch classes assigned to teacher
            let classes = null;
            try {
                const res = await fetch('/api/faculty.php?action=get_assigned_classes');
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.success && Array.isArray(json.classes)) {
                        classes = json.classes;
                    }
                }
            } catch (e) {
                console.warn('Faculty API error, falling back to local rest:', e);
            }

            if (!classes) {
                try {
                    const res = await fetch('/api/rest.php?table=classes&select=*&order=code.asc');
                    const all = await res.json();
                    if (Array.isArray(all)) {
                        const myEmail = (currentTeacherEmail || '').toLowerCase().trim();
                        const myName = (currentTeacherName || '').toLowerCase().trim();
                        classes = all.filter(c => isClassAssignedToTeacher(c, myEmail, myName));
                    }
                } catch (e) {
                    console.error('REST API error:', e);
                }
            }
            classes = classes || [];
            const myEmail = (currentTeacherEmail || '').toLowerCase().trim();
            const myName = (currentTeacherName || '').toLowerCase().trim();

            function isClassAssignedToTeacher(c, myEmail, myName) {
                if (!c) return false;
                const email = (myEmail || '').toLowerCase().trim();
                const name = (myName || '').toLowerCase().replace(/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i, '').trim();

                const cInst = (c.instructor || '').toLowerCase().replace(/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i, '').trim();
                const cInstEmail = (c.instructor_email || '').toLowerCase().trim();
                const cCreatedEmail = (c.created_by_email || '').toLowerCase().trim();
                const cCreatedName = (c.created_by_name || '').toLowerCase().replace(/^(prof\.|dr\.|engr\.|instructor|mr\.|ms\.|mrs\.)\s+/i, '').trim();

                const isTba = !cInst || ['tba', 'to be announced', 'unassigned', 'none'].includes(cInst);

                // 1. Direct Instructor Email match
                if (cInstEmail && email && cInstEmail === email) {
                    return true;
                }

                // 2. Instructor Name match (must not be TBA)
                if (!isTba && name && (cInst.includes(name) || name.includes(cInst))) {
                    return true;
                }

                // 3. Class created by the teacher themselves (and instructor is not TBA)
                if (cCreatedEmail && email && cCreatedEmail === email && !['admin@navotaspolytechniccollege.edu.ph'].includes(cCreatedEmail)) {
                    if (!$isTba && (cInst.includes(name) || name.includes(cInst))) {
                        return true;
                    }
                    if (!cInst || cInst === name || cCreatedName === name) {
                        return true;
                    }
                }

                return false;
            }

            // STRICT FACULTY SCOPING: Only show classes assigned to this faculty member
            activeClasses = (classes || []).filter(c => isClassAssignedToTeacher(c, myEmail, myName));

            // Populate Top Dropdown
            const select = document.getElementById('active-class-select');
            select.innerHTML = '<option value="">Select a subject...</option>' +
                activeClasses.map(c => `<option value="${c.id}">${c.code} - ${c.title} (${c.section || 'AIS 2A'})</option>`).join('');

            // Populate Subject Selection Cards Grid
            renderSubjectCardsGrid();

            // Check URL parameters for class_id
            const urlParams = new URLSearchParams(window.location.search);
            const initialClassId = urlParams.get('class_id');

            if (initialClassId && activeClasses.some(c => c.id === initialClassId)) {
                select.value = initialClassId;
                selectSubjectClass(initialClassId);
            } else if (activeClasses.length === 1) {
                select.value = activeClasses[0].id;
                selectSubjectClass(activeClasses[0].id);
            } else {
                showNoSubjectState();
            }

            listenToRealtimeScans();
        }

        // Render Subject Grid Cards for Initial State
        function renderSubjectCardsGrid() {
            const grid = document.getElementById('subjects-cards-grid');
            if (activeClasses.length === 0) {
                grid.innerHTML = '<div class="col-span-full p-8 text-center text-gray-500 font-mono">No assigned classes found for your faculty account.</div>';
                return;
            }

            grid.innerHTML = activeClasses.map(c => `
                <div onclick="selectSubjectClass('${c.id}')" class="bg-white p-5 rounded-2xl border border-outline-variant hover:border-primary hover:shadow-md transition-all cursor-pointer flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 bg-primary/10 text-primary font-mono text-xs font-bold rounded-lg">${c.code}</span>
                            <span class="text-xs font-mono font-bold bg-surface-container-low text-primary px-2 py-0.5 rounded border">${c.section || 'AIS 2A'}</span>
                        </div>
                        <h4 class="font-bold text-sm text-primary group-hover:text-[#107c41] transition-colors">${c.title}</h4>
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                            ${c.schedule_day || 'TBA'} (${c.start_time || 'TBA'}${c.end_time ? ' - ' + c.end_time : ''})
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs font-bold text-primary">
                        <span>Click to Start Attendance</span>
                        <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </div>
                </div>
            `).join('');
        }

        function showNoSubjectState() {
            document.getElementById('no-subject-selected-view').classList.remove('hidden');
            document.getElementById('no-subject-selected-view').classList.add('flex');
            document.getElementById('subject-active-view').classList.add('hidden');
        }

        function onClassDropdownChange() {
            const classId = document.getElementById('active-class-select').value;
            if (classId) {
                selectSubjectClass(classId);
            } else {
                showNoSubjectState();
            }
        }

        // When a Subject / Class is chosen
        async function selectSubjectClass(classId) {
            currentClass = activeClasses.find(c => c.id === classId);
            if (!currentClass) return;

            document.getElementById('active-class-select').value = classId;
            document.getElementById('no-subject-selected-view').classList.add('hidden');
            document.getElementById('no-subject-selected-view').classList.remove('flex');
            document.getElementById('subject-active-view').classList.remove('hidden');

            // Reset any previous active session
            clearInterval(timerInterval);
            clearInterval(pollingInterval);
            isSessionActive = false;

            // Show pre-session card (QR is HIDDEN initially)
            document.getElementById('pre-session-card').classList.remove('hidden');
            document.getElementById('active-session-container').classList.add('hidden');

            // Update Header Banner
            document.getElementById('banner-class-code').innerText = currentClass.code;
            document.getElementById('banner-class-section').innerText = currentClass.section || 'AIS 2A';
            document.getElementById('banner-class-title').innerText = currentClass.title;
            document.getElementById('banner-class-schedule').innerText = `${currentClass.schedule_day || 'TBA'} (${currentClass.start_time || 'TBA'}${currentClass.end_time ? ' - ' + currentClass.end_time : ''})`;
            document.getElementById('banner-class-room').innerText = currentClass.room || 'Room TBA';
            document.getElementById('banner-class-prof').innerText = currentClass.instructor || currentTeacherName;
            document.getElementById('link-excel-grades').href = `/teacher/grades.php?class_id=${encodeURIComponent(currentClass.id)}`;

            // Generate Session Code Format: NPC-[CODE]-[YYYY-MM-DD]
            const dateStr = new Date().toISOString().slice(0, 10);
            currentSessionCode = `NPC-${currentClass.code.replace(/[^a-zA-Z0-9]/g, '')}-${dateStr}`;
            document.getElementById('display-session-code').innerText = currentSessionCode;
            document.getElementById('modal-session-code').innerText = currentSessionCode;
            document.getElementById('modal-class-title').innerText = `${currentClass.code} - ${currentClass.section || 'AIS 2A'}`;

            // Fetch enrolled students in section via server API (privacy-scoped fields)
            try {
                const res = await fetch('/api/faculty.php?action=get_section_students&section=' + encodeURIComponent(currentClass.section || 'AIS 2A'), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                enrolledStudents = data.success ? (data.students || []) : [];
            } catch (err) {
                console.error('Student fetch failed:', err);
                enrolledStudents = [];
            }

            enrolledStudents.sort((a, b) => compareByLastName(a.full_name || '', b.full_name || ''));
            document.getElementById('banner-class-students-count').innerText = `${enrolledStudents.length} Enrolled`;
            document.getElementById('stat-total-enrolled').innerText = enrolledStudents.length;

            await fetchClassAttendance();
        }

        // 🚀 START LIVE ATTENDANCE SESSION (Reveals QR & Starts Countdown)
        async function startLiveAttendanceSession() {
            if (!currentClass) return;

            // 1. Read timer configurations
            const pMins = parseInt(document.getElementById('config-present-mins').value) || 10;
            const lMins = parseInt(document.getElementById('config-late-mins').value) || 5;

            isSessionActive = true;
            sessionStartTime = Date.now();

            // 2. Securely register the session SERVER-SIDE (ownership verified, times computed on server)
            let sessionInfo = null;
            try {
                const res = await fetch('/api/faculty.php?action=start_session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        present_mins: pMins,
                        late_mins: lMins,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Server rejected the session.');
                sessionInfo = data;
                currentSessionCode = data.session_code; // server-built code wins
            } catch (err) {
                isSessionActive = false;
                showToast('❌ Could not start session: ' + err.message, 'warning');
                return;
            }

            document.getElementById('display-session-code').innerText = currentSessionCode;
            document.getElementById('modal-session-code').innerText = currentSessionCode;

            // 3. Sync local countdown with the authoritative server deadlines
            const nowMs = Date.now();
            presentDurationSeconds = Math.max(5, Math.round((new Date(sessionInfo.present_until).getTime() - nowMs) / 1000));
            lateDurationSeconds = Math.max(0, Math.round((new Date(sessionInfo.late_until).getTime() - new Date(sessionInfo.present_until).getTime()) / 1000));
            totalSessionDuration = presentDurationSeconds + lateDurationSeconds;
            currentPhase = 'present';
            secondsRemaining = presentDurationSeconds;
            isTimerPaused = false;

            // 4. Hide pre-session card, show active live session container with QR Code
            document.getElementById('pre-session-card').classList.add('hidden');
            document.getElementById('active-session-container').classList.remove('hidden');

            // 5. Generate Crisp Local QR Code
            rotateQRToken(false);

            // 6. Start Countdown
            updateTimerDisplay();
            startTimerCountdown();

            // 7. Start Polling fallback (every 6 seconds, tab-aware)
            pollingInterval = setInterval(fetchClassAttendance, 6000);

            showToast('✅ Live Attendance QR Started! Session is now ACTIVE in database.', 'success');
        }

        // Generate / Rotate QR Code locally using QRCode.js
        function rotateQRToken(isManual = false) {
            const salt = Math.random().toString(36).substring(2, 8).toUpperCase();
            currentQrToken = `${currentSessionCode}-${salt}`;

            const qrPayload = JSON.stringify({
                session: currentSessionCode,
                token: currentQrToken,
                code: currentClass ? currentClass.code : '',
                timestamp: Date.now()
            });

            // Render in Left Panel Container
            const qrCanvas = document.getElementById('qrcode-canvas');
            qrCanvas.innerHTML = '';
            qrCodeInstance = new QRCode(qrCanvas, {
                text: qrPayload,
                width: 230,
                height: 230,
                colorDark: "#001736",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Render in Fullscreen Modal
            const modalCanvas = document.getElementById('modal-qrcode-canvas');
            modalCanvas.innerHTML = '';
            modalQrCodeInstance = new QRCode(modalCanvas, {
                text: qrPayload,
                width: 280,
                height: 280,
                colorDark: "#001736",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            document.getElementById('qr-token-indicator').innerText = `TOKEN: ${salt}`;

            if (isManual) {
                showToast('Rotated QR Code token!', 'info');
            }
        }

        // Live Countdown Timer Engine (Handles Present -> Late -> Expired phases)
        function startTimerCountdown() {
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                if (isTimerPaused) return;

                if (secondsRemaining > 0) {
                    secondsRemaining--;
                    updateTimerDisplay();
                } else {
                    // Transition to next phase
                    if (currentPhase === 'present' && lateDurationSeconds > 0) {
                        currentPhase = 'late';
                        secondsRemaining = lateDurationSeconds;
                        showToast('⚠️ On-time grace period ended! Next scans are marked LATE.', 'warning');
                        updateTimerDisplay();
                    } else {
                        // Session Expired
                        currentPhase = 'expired';
                        clearInterval(timerInterval);
                        onSessionTimerExpired();
                    }
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const m = Math.floor(secondsRemaining / 60);
            const s = secondsRemaining % 60;
            const timeFormatted = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

            document.getElementById('countdown-timer').innerText = timeFormatted;
            document.getElementById('modal-timer-display').innerText = `${currentPhase === 'present' ? 'Present' : 'Late'} Window Remaining: ${timeFormatted}`;

            const badge = document.getElementById('session-phase-badge');
            const phaseText = document.getElementById('session-phase-text');
            const timerLabel = document.getElementById('timer-label');
            const timerDisplay = document.getElementById('countdown-timer');
            const pBar = document.getElementById('timer-progress-bar');

            if (currentPhase === 'present') {
                badge.className = 'px-3 py-1 bg-status-success/15 text-status-success text-xs font-bold rounded-full flex items-center gap-1.5 shadow-xs';
                phaseText.innerHTML = '<span class="w-2 h-2 rounded-full bg-status-success animate-ping"></span> ON-TIME WINDOW (PRESENT)';
                timerLabel.innerText = 'On-Time Countdown (Present)';
                timerDisplay.className = 'text-3xl font-mono font-bold text-status-success';
                pBar.className = 'bg-status-success h-full transition-all duration-1000';
                pBar.style.width = `${(secondsRemaining / presentDurationSeconds) * 100}%`;
            } else if (currentPhase === 'late') {
                badge.className = 'px-3 py-1 bg-status-warning/20 text-status-warning text-xs font-bold rounded-full flex items-center gap-1.5 shadow-xs';
                phaseText.innerHTML = '<span class="w-2 h-2 rounded-full bg-status-warning animate-ping"></span> LATE GRACE WINDOW';
                timerLabel.innerText = 'Late Window Countdown';
                timerDisplay.className = 'text-3xl font-mono font-bold text-status-warning';
                pBar.className = 'bg-status-warning h-full transition-all duration-1000';
                pBar.style.width = `${(secondsRemaining / lateDurationSeconds) * 100}%`;
            } else {
                badge.className = 'px-3 py-1 bg-gray-200 text-gray-700 text-xs font-bold rounded-full flex items-center gap-1.5';
                phaseText.innerHTML = 'SESSION COMPLETED';
                timerLabel.innerText = 'Session Closed';
                timerDisplay.className = 'text-3xl font-mono font-bold text-gray-500';
                pBar.style.width = '0%';
            }
        }

        function toggleTimer() {
            isTimerPaused = !isTimerPaused;
            const btn = document.getElementById('timer-toggle-btn');
            const icon = document.getElementById('timer-btn-icon');
            if (isTimerPaused) {
                btn.innerHTML = '<span class="material-symbols-outlined text-[14px]">play_arrow</span> Resume';
                showToast('Timer paused', 'info');
            } else {
                btn.innerHTML = '<span class="material-symbols-outlined text-[14px]">pause</span> Pause';
                showToast('Timer resumed', 'info');
            }
        }

        function addTime(mins) {
            secondsRemaining += (mins * 60);
            updateTimerDisplay();
            showToast(`Added +${mins} minutes to session timer!`, 'success');
        }

        async function endSessionOnServer() {
            if (!currentSessionCode) return;
            try {
                await fetch('/api/faculty.php?action=end_session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        session_code: currentSessionCode,
                        csrf_token: csrfToken
                    })
                });
            } catch (err) {
                console.error('Failed to close session on server:', err);
            }
        }

        async function onSessionTimerExpired() {
            updateTimerDisplay();
            // Mark session as inactive server-side
            await endSessionOnServer();
            showToast('Session timer completed! Attendance closed.', 'info');
            fetchClassAttendance();
        }

        async function endAttendanceSession() {
            if (!await npcConfirm({
                    title: 'End Attendance Session',
                    message: 'End this live attendance session?\n\nConfirming will also mark all remaining unmarked students as ABSENT.\nCancel to keep the session open.',
                    type: 'warning',
                    confirmText: 'End & Mark Absents'
                })) return;
            clearInterval(timerInterval);
            clearInterval(pollingInterval);
            isSessionActive = false;
            currentPhase = 'expired';

            // Mark every unmarked enrollee absent BEFORE closing the session
            let absMsg = '';
            try {
                const finRes = await fetch('/api/faculty.php?action=finalize_session_absents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        session_code: currentSessionCode,
                        csrf_token: csrfToken
                    })
                });
                const finData = await finRes.json();
                if (finData.success && finData.absent_count > 0) absMsg = ` ${finData.absent_count} auto-marked absent.`;
            } catch (e) {
                console.warn('finalize failed:', e);
            }

            // Mark session as inactive in Supabase (server-side)
            await endSessionOnServer();

            updateTimerDisplay();
            fetchClassAttendance();
            showToast('Session closed.' + absMsg, 'success');
        }

        /* Export this session's attendance as CSV */
        function exportSessionCsv() {
            if (!currentSessionCode) {
                showToast('No active session to export.', 'error');
                return;
            }
            window.location.href = '/api/faculty.php?action=export_attendance_csv&session_code=' + encodeURIComponent(currentSessionCode);
        }

        // Fetch Logs and Update Live Roster (server-scoped to this session)
        async function fetchClassAttendance() {
            if (document.hidden) return;
            if (!currentClass || !currentSessionCode) return;

            try {
                const res = await fetch('/api/faculty.php?action=get_live_roster&session_code=' + encodeURIComponent(currentSessionCode), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                attendanceLogs = data.success ? (data.records || []) : [];
            } catch (err) {
                console.error('Roster fetch failed:', err);
            }
            updateMetricsAndRoster();
        }

        function updateMetricsAndRoster() {
            const total = enrolledStudents.length;
            let present = 0;
            let late = 0;

            attendanceLogs.forEach(log => {
                if (log.status === 'late') late++;
                else present++;
            });

            const absent = Math.max(0, total - (present + late));

            document.getElementById('stat-total-enrolled').innerText = total;
            document.getElementById('stat-present').innerText = present;
            document.getElementById('stat-late').innerText = late;
            document.getElementById('stat-absent').innerText = absent;

            // Render Roster
            const tbody = document.getElementById('roster-tbody');
            if (enrolledStudents.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-on-surface-variant font-mono">No enrolled students in this section.</td></tr>';
                return;
            }

            tbody.innerHTML = enrolledStudents.map(s => {
                const log = attendanceLogs.find(l =>
                    (l.student_number && s.student_number && l.student_number === s.student_number) ||
                    (l.student_name && s.full_name && l.student_name.toLowerCase() === s.full_name.toLowerCase())
                );

                let timeDisplay = '—';
                let statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-error/10 text-error"><span class="w-1.5 h-1.5 rounded-full bg-error"></span> Absent</span>';
                let currentStatus = 'absent';

                if (log) {
                    timeDisplay = new Date(log.check_in_at).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });

                    // Live Presence Indicator & Disconnect badge
                    if (log.is_online) {
                        timeDisplay += ' <span class="inline-flex items-center gap-1 font-bold text-status-success ml-1 text-[11px]"><span class="w-1.5 h-1.5 rounded-full bg-status-success animate-ping"></span> Online</span>';
                    } else if (log.leave_count > 0) {
                        timeDisplay += ' <span class="inline-flex items-center gap-0.5 text-error ml-1 text-[11px]"><span class="w-1.5 h-1.5 rounded-full bg-error"></span> Offline</span>';
                    }

                    if (log.duration_seconds && log.duration_seconds > 0) {
                        const m = Math.floor(log.duration_seconds / 60);
                        const s = log.duration_seconds % 60;
                        timeDisplay += ` <span class="text-on-surface-variant font-mono text-[11px]">(${m}m ${s}s)</span>`;
                    }

                    if (log.leave_count && log.leave_count > 0) {
                        timeDisplay += ` <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-error/15 text-error border border-error/20">🔴 ${log.leave_count}x Left</span>`;
                    }

                    if (log.status === 'late') {
                        currentStatus = 'late';
                        statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-status-warning/15 text-status-warning"><span class="w-1.5 h-1.5 rounded-full bg-status-warning"></span> Late</span>';
                    } else {
                        currentStatus = 'present';
                        statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-status-success/15 text-status-success"><span class="w-1.5 h-1.5 rounded-full bg-status-success"></span> Present</span>';
                    }
                }

                return `
                    <tr class="hover:bg-surface-subtle transition-colors">
                        <td class="py-3 px-4 font-bold text-primary">${escHtml(formatLastNameFirst(s.full_name || 'Unnamed Student'))}</td>
                        <td class="py-3 px-4 font-mono text-on-surface-variant">${s.student_number || 'N/A'}</td>
                        <td class="py-3 px-4 font-mono text-on-surface-variant text-xs">${timeDisplay}</td>
                        <td class="py-3 px-4 text-right">${statusBadge}</td>
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center gap-1">
                                <button onclick="toggleStudentAttendance('${s.student_number}', '${encodeURIComponent(s.full_name)}', 'present')" title="Mark Present" class="p-1 rounded hover:bg-emerald-100 text-status-success ${currentStatus === 'present' ? 'font-bold underline' : ''}">
                                    <span class="material-symbols-outlined text-[15px]">check</span>
                                </button>
                                <button onclick="toggleStudentAttendance('${s.student_number}', '${encodeURIComponent(s.full_name)}', 'late')" title="Mark Late" class="p-1 rounded hover:bg-amber-100 text-status-warning ${currentStatus === 'late' ? 'font-bold underline' : ''}">
                                    <span class="material-symbols-outlined text-[15px]">schedule</span>
                                </button>
                                <button onclick="toggleStudentAttendance('${s.student_number}', '${encodeURIComponent(s.full_name)}', 'absent')" title="Mark Absent" class="p-1 rounded hover:bg-red-100 text-error ${currentStatus === 'absent' ? 'font-bold underline' : ''}">
                                    <span class="material-symbols-outlined text-[15px]">close</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Manual Override of a Student's Attendance Status with Reason Logging
        async function toggleStudentAttendance(studentNum, studentNameEnc, newStatus) {
            const studentName = decodeURIComponent(studentNameEnc);

            const reason = prompt(`Enter reason for marking ${studentName} as ${newStatus.toUpperCase()}:`, 'Verified in classroom');
            if (reason === null || !reason.trim()) return;

            const existing = attendanceLogs.find(l => l.student_number === studentNum || l.student_name === studentName);

            try {
                const res = await fetch('/api/faculty.php?action=correct_attendance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        record_id: existing ? existing.id : '',
                        student_number: studentNum,
                        student_name: studentName,
                        session_code: currentSessionCode,
                        status: newStatus,
                        reason: reason.trim(),
                        csrf_token: csrfToken
                    })
                });

                const data = await res.json();
                if (data.success) {
                    await fetchClassAttendance();
                    showToast(`Updated attendance for ${studentName}`, 'success');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('Correction Error: ' + err.message);
            }
        }

        // Mark All Enrolled Students Present (server-side bulk override)
        async function markAllPresent() {
            if (!await npcConfirm({
                    title: 'Mark All Present',
                    message: 'Mark all enrolled students present for this session?',
                    type: 'info',
                    confirmText: 'Mark All Present'
                })) return;
            try {
                const res = await fetch('/api/faculty.php?action=mark_all_present', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        session_code: currentSessionCode,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Bulk override failed.');
            } catch (err) {
                alert('Error: ' + err.message);
            }
            fetchClassAttendance();
            showToast('All enrolled students marked present!', 'success');
        }

        // Copy Session Code
        function copySessionCode() {
            navigator.clipboard.writeText(currentSessionCode);
            showToast(`Copied session code: ${currentSessionCode}`, 'info');
        }

        // Fullscreen Projector Modal
        function openQrModal() {
            document.getElementById('fullscreen-qr-modal').classList.remove('hidden');
        }

        function closeQrModal() {
            document.getElementById('fullscreen-qr-modal').classList.add('hidden');
        }

        // Realtime Subscription
        function listenToRealtimeScans() {
            supabaseClient
                .channel('teacher-live-attendance')
                .on('postgres_changes', {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'attendance_records'
                }, payload => {
                    if (isSessionActive) {
                        rotateQRToken(false);
                    }
                    fetchClassAttendance();
                    showToast(`✨ Student checked in!`, 'success');
                })
                .subscribe();
        }

        // Toast Feedback
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            toastMsg.innerText = msg;
            if (type === 'warning') {
                toastIcon.innerText = 'warning';
                toastIcon.className = 'material-symbols-outlined text-[18px] text-amber-400';
            } else if (type === 'info') {
                toastIcon.innerText = 'info';
                toastIcon.className = 'material-symbols-outlined text-[18px] text-blue-400';
            } else {
                toastIcon.innerText = 'check_circle';
                toastIcon.className = 'material-symbols-outlined text-[18px] text-emerald-400';
            }

            toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            clearTimeout(window.toastTimer);
            window.toastTimer = setTimeout(() => {
                toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            }, 3500);
        }

        // Start Application
        initFacultyAttendance();
        loadExcusesReview();

        /* ── Excuse Letters review ── */
        async function loadExcusesReview() {
            const list = document.getElementById('excuses-review-list');
            if (!list) return;
            try {
                const res = await fetch('/api/faculty.php?action=get_excuses');
                const data = await res.json();
                if (!data.success || !data.excuses.length) {
                    list.innerHTML = '<p class="p-6 text-center text-sm text-on-surface-variant">No excuse letters submitted to you yet.</p>';
                    return;
                }
                const pill = st => st === 'Approved' ? 'bg-emerald-100 text-emerald-800' :
                    (st === 'Rejected' ? 'bg-red-100 text-error' : 'bg-amber-100 text-amber-800');
                list.innerHTML = data.excuses.map(x => {
                    const pending = x.status === 'Pending';
                    return `
                    <div class="p-4 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-bold text-primary text-sm">${escHtml(x.student_name)}</span>
                                <span class="font-mono text-[11px] text-on-surface-variant">(${escHtml(x.student_number)})</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${pill(x.status)}">${escHtml(x.status)}</span>
                            </div>
                            <p class="text-xs text-on-surface"><strong>${escHtml(x.class_code)}</strong> · absent on <strong>${escHtml(String(x.absence_date))}</strong></p>
                            <p class="text-xs text-on-surface-variant mt-1 break-words">${escHtml(x.reason)}</p>
                            ${x.remarks ? `<p class="text-[11px] mt-1"><strong>Remarks:</strong> ${escHtml(x.remarks)}</p>` : ''}
                        </div>
                        ${pending ? `
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="reviewExcuse('${escHtml(x.id)}', 'Approved')" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg font-bold text-[11px] hover:opacity-90">Approve</button>
                            <button onclick="reviewExcuse('${escHtml(x.id)}', 'Rejected')" class="px-3 py-1.5 bg-red-600 text-white rounded-lg font-bold text-[11px] hover:opacity-90">Reject</button>
                        </div>` : ''}
                    </div>`;
                }).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function reviewExcuse(id, decision) {
            const remarks = prompt(`Optional remarks for ${decision.toLowerCase()} excuse:`, '') ?? '';
            try {
                const res = await fetch('/api/faculty.php?action=review_excuse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        id,
                        decision,
                        remarks,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                showToast(data.message || (data.success ? 'Updated.' : 'Failed.'), data.success ? 'success' : 'error');
                if (data.success && data.auto_corrected) {
                    showToast('Attendance record auto-corrected to excused.', 'success');
                }
                loadExcusesReview();
            } catch (e) {
                showToast('Network error: ' + e.message, 'error');
            }
        }

        function escHtml(t) {
            return String(t == null ? '' : t).replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }
    </script>
</body>

</html>