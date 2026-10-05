<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <title>Live Attendance Verification - NPC Connect Admin</title>
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
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
        <!-- SideNavBar Desktop -->
        <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
                <!-- Top Header -->
                <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
                        <div class="flex items-center gap-4">
                                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                                <h2 class="text-xl font-bold text-primary hidden lg:block">Live Attendance Session</h2>
                        </div>
                        <div class="flex items-center gap-4">
                                <button onclick="refreshNewCode(true)" class="bg-surface-container hover:bg-surface-container-high text-primary px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">autorenew</span>
                                        Rotate QR Now
                                </button>
                                <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card">
                                                <?php echo htmlspecialchars($admin_initial); ?>
                                        </div>
                                </div>
                        </div>
                </header>

                <!-- Canvas -->
                <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 flex-1">
                        <!-- Header Banner -->
                        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-outline-variant/60 pb-4">
                                <div>
                                        <h1 class="text-2xl font-bold text-primary tracking-tight">Active Classroom Check-in</h1>
                                        <p class="text-sm text-on-surface-variant mt-1">QR code rotates automatically on every student scan to prevent sharing. Verified list is sorted alphabetically (A-Z).</p>
                                </div>

                                <!-- Session Countdown Timer Widget -->
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-sm flex items-center gap-4">
                                        <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center npc-navy-card">
                                                        <span class="material-symbols-outlined text-[22px]">timer</span>
                                                </div>
                                                <div>
                                                        <span class="text-[10px] font-mono uppercase tracking-wider text-on-surface-variant block font-bold">Session Timer</span>
                                                        <span id="session-timer-display" class="font-mono text-xl font-bold text-primary">15:00</span>
                                                </div>
                                        </div>
                                        <div class="h-8 w-px bg-outline-variant/40"></div>
                                        <div class="flex items-center gap-1.5">
                                                <button id="timer-toggle-btn" onclick="toggleTimer()" class="p-2 bg-surface-container hover:bg-surface-container-high text-primary rounded-lg transition-colors" title="Start/Pause">
                                                        <span id="timer-icon" class="material-symbols-outlined text-[18px]">play_arrow</span>
                                                </button>
                                                <button onclick="addTimer(5)" class="px-2.5 py-1.5 bg-surface-container hover:bg-surface-container-high text-primary rounded-lg text-xs font-mono font-bold transition-colors" title="Add 5 Minutes">
                                                        +5m
                                                </button>
                                                <button onclick="resetTimer()" class="p-2 bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-error rounded-lg transition-colors" title="Reset">
                                                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                                                </button>
                                        </div>
                                </div>
                        </div>

                        <!-- Stats Bar -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center justify-between">
                                        <div>
                                                <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Total Scanned</span>
                                                <p class="text-2xl font-bold text-primary mt-1" id="stat-total">0</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-xl bg-surface-container text-primary flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[22px]">group</span>
                                        </div>
                                </div>

                                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center justify-between">
                                        <div>
                                                <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">On-Time (Present)</span>
                                                <p class="text-2xl font-bold text-status-success mt-1" id="stat-present">0</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-xl bg-status-success/15 text-status-success flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[22px]">check_circle</span>
                                        </div>
                                </div>

                                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center justify-between">
                                        <div>
                                                <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Late Arrivals</span>
                                                <p class="text-2xl font-bold text-status-warning mt-1" id="stat-late">0</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-xl bg-status-warning/15 text-status-warning flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[22px]">schedule</span>
                                        </div>
                                </div>
                        </div>

                        <!-- Main Interactive Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                                <!-- Left: Dynamic Rotating QR Code Card -->
                                <div class="lg:col-span-5 flex flex-col gap-6">
                                        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant p-8 flex flex-col items-center justify-center shadow-sm relative overflow-hidden text-center">
                                                <div class="flex items-center justify-between w-full mb-4">
                                                        <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                                                                <span class="w-2 h-2 rounded-full bg-status-success animate-pulse"></span>
                                                                Live Dynamic Token
                                                        </span>
                                                        <span id="rotation-status" class="text-[11px] font-mono text-status-info">Auto-rotates per scan</span>
                                                </div>

                                                <div class="w-full max-w-[280px] aspect-square rounded-2xl overflow-hidden p-3 border-2 border-outline-variant/60 bg-white flex items-center justify-center shadow-inner relative group">
                                                        <div id="qrcode"></div>
                                                </div>

                                                <div class="mt-6 flex items-center gap-4 w-full px-4">
                                                        <div class="flex-1 h-[1px] bg-outline-variant/30"></div>
                                                        <span class="text-xs font-mono uppercase tracking-wider text-on-surface-variant font-bold">Session Code</span>
                                                        <div class="flex-1 h-[1px] bg-outline-variant/30"></div>
                                                </div>

                                                <div class="mt-3 bg-surface-container py-2.5 px-6 rounded-xl border border-outline-variant/50 flex items-center gap-2">
                                                        <span id="session-code" class="font-mono text-2xl text-primary font-bold tracking-widest">NPC-2026</span>
                                                </div>

                                                <p class="text-xs text-on-surface-variant mt-4">Students can scan via webcam or enter this code in the <a href="/student/qrcode.php" target="_blank" class="text-primary underline font-semibold">Student Portal</a>.</p>
                                        </div>
                                </div>

                                <!-- Right: Alphabetical Real-Time Attendance Log -->
                                <div class="lg:col-span-7 bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-sm flex flex-col min-h-[520px] overflow-hidden">
                                        <div class="p-6 border-b border-outline-variant flex items-center justify-between bg-surface-subtle">
                                                <div>
                                                        <h3 class="font-bold text-primary text-lg flex items-center gap-2">
                                                                <span class="material-symbols-outlined text-primary text-[20px]">sort_by_alpha</span>
                                                                Verified Attendance (Alphabetical A-Z)
                                                        </h3>
                                                        <p class="text-xs text-on-surface-variant mt-0.5">Deduplicated in real-time — single entry per student</p>
                                                </div>
                                                <button onclick="clearSessionAttendance()" class="text-xs font-semibold text-error hover:bg-error-container/20 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Clear Session
                                                </button>
                                        </div>

                                        <div class="flex-1 overflow-y-auto">
                                                <div class="overflow-x-auto -mx-1 px-1"><table class="w-full text-left border-collapse">
                                                        <thead>
                                                                <tr class="bg-surface-container-low font-mono text-xs text-on-surface uppercase tracking-wider">
                                                                        <th class="py-3 px-5 font-semibold border-b border-outline-variant">Student Name</th>
                                                                        <th class="py-3 px-5 font-semibold border-b border-outline-variant">Student ID</th>
                                                                        <th class="py-3 px-5 font-semibold border-b border-outline-variant">Time Scanned</th>
                                                                        <th class="py-3 px-5 font-semibold border-b border-outline-variant text-right">Status</th>
                                                                </tr>
                                                        </thead>
                                                        <tbody id="attendance-tbody" class="divide-y divide-outline-variant/30 text-sm">
                                                                <tr>
                                                                        <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                                                                <span class="material-symbols-outlined text-[36px] text-outline-variant mb-2 block">qr_code_scanner</span>
                                                                                <p class="font-semibold">No students verified yet</p>
                                                                                <p class="text-xs text-on-surface-variant mt-1">Display the QR code to your class. As students scan, their names appear alphabetically here.</p>
                                                                        </td>
                                                                </tr>
                                                        </tbody>
                                                </table></div>
                                        </div>
                                </div>
                        </div>
                </div>
        </main>

        <script>
                const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
                const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
                const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
                const csrfToken = <?= json_encode($csrf_token) ?>;

                let qrCodeInstance = null;
                let currentToken = '';

                // Timer State
                let timerSeconds = 15 * 60; // 15 minutes default
                let timerRunning = false;
                let timerInterval = null;
                let sessionStartTime = Date.now();
                const GRACE_PERIOD_SECONDS = 5 * 60; // 5 minutes grace period for "Present"

                function updateTimerDisplay() {
                        const mins = Math.floor(timerSeconds / 60);
                        const secs = timerSeconds % 60;
                        document.getElementById('session-timer-display').innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }

                function toggleTimer() {
                        const icon = document.getElementById('timer-icon');
                        if (timerRunning) {
                                clearInterval(timerInterval);
                                timerRunning = false;
                                icon.innerText = 'play_arrow';
                        } else {
                                timerRunning = true;
                                icon.innerText = 'pause';
                                timerInterval = setInterval(() => {
                                        if (timerSeconds > 0) {
                                                timerSeconds--;
                                                updateTimerDisplay();
                                        } else {
                                                clearInterval(timerInterval);
                                                timerRunning = false;
                                                icon.innerText = 'replay';
                                                alert('Attendance session timer ended!');
                                        }
                                }, 1000);
                        }
                }

                function addTimer(mins) {
                        timerSeconds += mins * 60;
                        updateTimerDisplay();
                }

                function resetTimer() {
                        clearInterval(timerInterval);
                        timerRunning = false;
                        timerSeconds = 15 * 60;
                        sessionStartTime = Date.now();
                        document.getElementById('timer-icon').innerText = 'play_arrow';
                        updateTimerDisplay();
                }

                function refreshNewCode(manual = false) {
                        currentToken = 'NPC-' + Math.random().toString(36).substring(2, 8).toUpperCase();
                        document.getElementById('session-code').textContent = currentToken;

                        const qrContainer = document.getElementById('qrcode');
                        qrContainer.innerHTML = '';
                        qrCodeInstance = new QRCode(qrContainer, {
                                text: JSON.stringify({
                                        session: currentToken,
                                        timestamp: Date.now()
                                }),
                                width: 240,
                                height: 240,
                                colorDark: "#001736",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                        });

                        if (!manual) {
                                const statusEl = document.getElementById('rotation-status');
                                statusEl.innerText = '✨ Rotated on scan!';
                                statusEl.classList.add('text-status-success', 'font-bold');
                                setTimeout(() => {
                                        statusEl.innerText = 'Auto-rotates per scan';
                                        statusEl.classList.remove('text-status-success', 'font-bold');
                                }, 2500);
                        }
                }

                async function loadAttendanceLogs() {
                        try {
                                const {
                                        data,
                                        error
                                } = await supabaseClient
                                        .from('attendance_records')
                                        .select('*')
                                        .order('check_in_at', {
                                                ascending: true
                                        });

                                if (error) throw error;

                                // Deduplicate by student_number / student_name (keep earliest)
                                const uniqueMap = new Map();
                                const duplicateIdsToDelete = [];

                                (data || []).forEach(record => {
                                        const key = (record.student_number || record.student_name || record.id).trim().toLowerCase();
                                        if (!uniqueMap.has(key)) {
                                                uniqueMap.set(key, record);
                                        } else {
                                                duplicateIdsToDelete.push(record.id);
                                        }
                                });

                                // Auto-delete duplicates in background if any found
                                if (duplicateIdsToDelete.length > 0) {
                                        fetch('/api/admin.php?action=delete_attendance_records', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                                                body: JSON.stringify({ ids: duplicateIdsToDelete, csrf_token: csrfToken })
                                        }).catch(() => {});
                                }

                                // Sort alphabetically by student_name (A to Z)
                                const uniqueStudents = Array.from(uniqueMap.values()).sort((a, b) => {
                                        const nameA = (a.student_name || '').toUpperCase();
                                        const nameB = (b.student_name || '').toUpperCase();
                                        return nameA.localeCompare(nameB);
                                });

                                // Compute stats & late status
                                let presentCount = 0;
                                let lateCount = 0;

                                const processed = uniqueStudents.map(s => {
                                        const checkInTime = new Date(s.check_in_at).getTime();
                                        // If check-in happened after grace period from session start, mark Late
                                        const isLate = (checkInTime - sessionStartTime) > (GRACE_PERIOD_SECONDS * 1000);
                                        if (isLate) lateCount++;
                                        else presentCount++;
                                        return {
                                                ...s,
                                                isLate
                                        };
                                });

                                document.getElementById('stat-total').innerText = processed.length;
                                document.getElementById('stat-present').innerText = presentCount;
                                document.getElementById('stat-late').innerText = lateCount;

                                const tbody = document.getElementById('attendance-tbody');
                                if (processed.length === 0) {
                                        tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[36px] text-outline-variant mb-2 block">qr_code_scanner</span>
                                <p class="font-semibold">No students verified yet</p>
                                <p class="text-xs text-on-surface-variant mt-1">Display the QR code to your class. As students scan, their names appear alphabetically here.</p>
                            </td>
                        </tr>
                    `;
                                        return;
                                }

                                tbody.innerHTML = processed.map(r => {
                                        const initial = (r.student_name || 'S').charAt(0).toUpperCase();
                                        const timeStr = new Date(r.check_in_at).toLocaleTimeString([], {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit'
                                        });
                                        const statusBadge = r.isLate ?
                                                `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-status-warning/15 text-status-warning"><span class="w-1.5 h-1.5 rounded-full bg-status-warning"></span> Late</span>` :
                                                `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-status-success/15 text-status-success"><span class="w-1.5 h-1.5 rounded-full bg-status-success"></span> Present</span>`;

                                        return `
                        <tr class="hover:bg-surface-subtle transition-colors">
                            <td class="py-3.5 px-5 font-semibold text-primary flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs shadow-sm npc-navy-card">
                                    ${initial}
                                </div>
                                <span>${r.student_name || 'Student'}</span>
                            </td>
                            <td class="py-3.5 px-5 font-mono text-xs text-on-surface-variant font-medium">${r.student_number || r.student_id || 'N/A'}</td>
                            <td class="py-3.5 px-5 text-xs text-on-surface-variant font-mono">${timeStr}</td>
                            <td class="py-3.5 px-5 text-right font-medium">${statusBadge}</td>
                        </tr>
                    `;
                                }).join('');

                        } catch (err) {
                                console.error('Error loading attendance:', err);
                        }
                }

                async function clearSessionAttendance() {
                        if (!await npcConfirm({
                                title: 'Clear Session Attendance',
                                message: 'Are you sure you want to clear all attendance records for this session?',
                                type: 'danger',
                                confirmText: 'Clear Records'
                        })) return;
                        let ok = false;
                        try {
                                const res = await fetch('/api/admin.php?action=delete_attendance_records', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                                        body: JSON.stringify({ scope: 'all', csrf_token: csrfToken })
                                });
                                const data = await res.json();
                                ok = !!data.success;
                                if (!ok) throw new Error(data.message || 'Clear failed');
                        } catch (err) {
                                alert('Error: ' + err.message);
                        }
                        refreshNewCode(true);
                        loadAttendanceLogs();
                }

                // Initialize
                refreshNewCode(true);
                loadAttendanceLogs();
                updateTimerDisplay();

                // Realtime Subscription: Rotate QR and reload list on scan!
                supabaseClient
                        .channel('live-attendance-feed')
                        .on('postgres_changes', {
                                event: 'INSERT',
                                schema: 'public',
                                table: 'attendance_records'
                        }, payload => {
                                refreshNewCode(false); // Rotate QR immediately!
                                loadAttendanceLogs(); // Reload and sort alphabetically
                        })
                        .subscribe();
        </script>
</body>

</html>