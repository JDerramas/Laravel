<?php
require_once __DIR__ . '/../includes/auth.php';
require_teacher();
$teacher_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Faculty Professor';
$teacher_initial = strtoupper(substr($teacher_name, 0, 1));
$teacher_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar');
$csrf_token = getCsrfToken();
$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Faculty Portal · NPC LMS</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js"></script>
    <script>
        if (typeof THREE === 'undefined') {
            const s = document.createElement('script');
            s.src = "https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js";
            document.head.appendChild(s);
        }
    </script>
    <script src="/assets/js/npc-three.js"></script>
    <script src="/assets/js/npc.js"></script>
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
    <main class="flex-1 lg:pl-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-4 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer" title="Open navigation menu" aria-label="Open Navigation">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="text-xl font-bold text-primary lg:hidden">NPC Faculty</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Faculty Dashboard & Consultation Hub</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all">
                    <span class="material-symbols-outlined text-[16px]">map</span>
                    <span>NPC Map</span>
                </a>
                <?php 
                $teacherAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                ?>
                <a href="/teacher/profile.php" class="flex items-center gap-2 p-1 rounded-xl hover:bg-surface-container transition-all group" title="View Faculty Profile">
                    <?php if (!empty($teacherAvatar)): ?>
                        <img src="<?= htmlspecialchars($teacherAvatar) ?>" alt="<?= htmlspecialchars($teacher_name) ?>" class="w-9 h-9 rounded-full object-cover border border-amber-300 shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all" referrerpolicy="no-referrer" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary font-bold text-sm items-center justify-center shadow-sm npc-navy-card shrink-0 hidden group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                            <?= htmlspecialchars($teacher_initial) ?>
                        </div>
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                            <?= htmlspecialchars($teacher_initial) ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-primary hidden sm:inline group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors"><?= htmlspecialchars($teacher_name) ?></span>
                </a>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 flex-1">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-outline-variant/60 pb-6">
                <div>
                    <!-- Date & Status Meta Pill Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-2.5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container border border-outline-variant font-mono text-xs font-semibold text-primary shadow-sm">
                            <span class="material-symbols-outlined text-[15px] text-primary">calendar_today</span>
                            <span id="current-date"><?= date('l, F j, Y') ?></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container border border-outline-variant font-mono text-xs font-semibold text-on-surface-variant shadow-sm">
                            <span class="material-symbols-outlined text-[15px] text-status-success animate-pulse">schedule</span>
                            <span id="npc-live-clock"><?= date('g:i:s A') ?></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase tracking-widest bg-secondary-container text-on-secondary-container border border-secondary-container">
                            <span class="material-symbols-outlined text-[13px]">cast_for_education</span>
                            Faculty Portal
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight">
                        <span class="text-shimmer text-primary">Welcome, Prof. <?= htmlspecialchars(explode(' ', trim($teacher_name))[0]) ?></span>
                        <span class="inline-block align-middle ml-1.5 animate-float material-symbols-outlined text-[24px] text-secondary" aria-hidden="true">waving_hand</span>
                    </h1>
                    <p class="text-sm text-on-surface-variant mt-1">Manage class attendance, encode semester grades, publish class materials, and review student appointments.</p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                    <a href="/teacher/campus_map.php" class="ripple press bg-surface-container border border-outline-variant text-primary px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-surface-container-high flex items-center gap-2 shadow-sm transition-all hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        NPC Map
                    </a>
                    <a href="/teacher/courses.php" class="ripple press bg-primary text-on-primary px-4 py-2.5 rounded-xl text-xs font-semibold hover:opacity-90 flex items-center gap-2 shadow-sm transition-all hover:-translate-y-0.5 npc-navy-card">
                        <span class="material-symbols-outlined text-[16px]">menu_book</span>
                        Courses & Modules Hub
                    </a>
                    <a href="/teacher/attendance.php" class="ripple press bg-surface-container border border-outline-variant text-primary px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-surface-container-high flex items-center gap-2 shadow-sm transition-all hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span>
                        Start QR Attendance
                    </a>
                    <a href="/teacher/grades.php" class="bg-[#107c41] text-white px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-[#0c5d31] flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">grade</span>
                        Open Gradebook
                    </a>
                </div>
            </div>

            <!-- Teaching Brief (live counters) -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Teaching brief">
                <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-error text-[22px]">report</span>
                    <div>
                        <p class="text-[10px] font-mono uppercase text-on-surface-variant font-bold">Classes Today</p>
                        <h3 class="text-xl font-bold text-on-surface tabular-nums" id="brief-today-count">—</h3>
                    </div>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-status-warning text-[22px]">pending_actions</span>
                    <div>
                        <p class="text-[10px] font-mono uppercase text-on-surface-variant font-bold">Pending Consults</p>
                        <h3 class="text-xl font-bold text-on-surface tabular-nums" id="teacher-consult-count">0</h3>
                    </div>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary text-[22px]">campaign</span>
                    <div>
                        <p class="text-[10px] font-mono uppercase text-on-surface-variant font-bold">My Class Posts</p>
                        <h3 class="text-xl font-bold text-on-surface tabular-nums" id="brief-ann-count">—</h3>
                    </div>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-[22px]">folder_open</span>
                    <div>
                        <p class="text-[10px] font-mono uppercase text-on-surface-variant font-bold">Materials Shared</p>
                        <h3 class="text-xl font-bold text-on-surface tabular-nums" id="brief-mat-count">—</h3>
                    </div>
                </div>
                <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-status-info text-[22px]">assignment_turned_in</span>
                    <div>
                        <p class="text-[10px] font-mono uppercase text-on-surface-variant font-bold">Grades Submitted</p>
                        <h3 class="text-xl font-bold text-on-surface tabular-nums" id="brief-submitted-count">—</h3>
                    </div>
                </div>
            </section>

            <!-- Recent Absences Widget -->
            <section id="recent-absences-card" class="hidden bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant flex items-center justify-between bg-surface-subtle">
                    <h3 class="font-bold text-primary text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-error text-[18px]">event_busy</span> Recent Absences (my sessions)
                    </h3>
                    <a href="/teacher/attendance.php" class="text-xs font-bold text-primary hover:underline">Open Attendance →</a>
                </div>
                <div id="recent-absences-list" class="divide-y divide-outline-variant/40 max-h-52 overflow-y-auto custom-scroll"></div>
            </section>

            <!-- Bento Stats -->
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="npc-card tilt bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-xs font-mono font-semibold uppercase text-on-surface-variant">Active Class Load</span>
                    <h3 class="text-3xl font-bold text-primary mt-2 tabular-nums" id="teacher-class-count" data-countup="0">0</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Assigned lecture &amp; lab sections</p>
                </div>

                <div class="npc-card tilt bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-xs font-mono font-semibold uppercase text-on-surface-variant">Pending Consultations</span>
                    <h3 class="text-3xl font-bold text-amber-600 mt-2 tabular-nums" id="teacher-consult-count" data-countup="0">0</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Student appointment requests</p>
                </div>

                <div class="npc-navy-card text-white p-6 rounded-2xl shadow-md">
                    <span class="text-xs font-mono font-semibold uppercase text-blue-200">Academic Term</span>
                    <h3 class="text-2xl font-bold text-white mt-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary-container">school</span> AY 2026-2027
                    </h3>
                    <p class="text-xs text-blue-100/90 mt-1">1st Semester • In Progress</p>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" aria-label="Quick actions">
                <a href="/teacher/courses.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-primary/40 p-4 shadow-sm flex flex-col items-start gap-3 hover:border-primary transition-all hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center relative overflow-hidden" data-npc-3d-icon="book">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px]">menu_book</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-primary">Courses & Modules</p>
                        <p class="text-[11px] font-mono text-on-surface-variant">Syllabi & tasks</p>
                    </div>
                </a>
                <a href="/teacher/attendance.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex flex-col items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center npc-navy-card relative overflow-hidden" data-npc-3d-icon="qr">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px]">qr_code_scanner</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-on-surface">Take Attendance</p>
                        <p class="text-[11px] font-mono text-on-surface-variant">Live QR session</p>
                    </div>
                </a>
                <a href="/teacher/grades.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex flex-col items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#107c41]/10 text-[#107c41] flex items-center justify-center">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px]">grade</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-on-surface">Encode Grades</p>
                        <p class="text-[11px] font-mono text-on-surface-variant">Gradebook</p>
                    </div>
                </a>
                <a href="/teacher/classes.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex flex-col items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px]">group</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-on-surface">Class Rosters</p>
                        <p class="text-[11px] font-mono text-on-surface-variant">Student lists</p>
                    </div>
                </a>
                <a href="#material-upload-form" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex flex-col items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-status-info/15 text-status-info flex items-center justify-center">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px]">upload_file</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-on-surface">Share Materials</p>
                        <p class="text-[11px] font-mono text-on-surface-variant">Upload handouts</p>
                    </div>
                </a>
                <a href="/teacher/ai_assistant.php" class="npc-card npc-tile ripple press npc-navy-card text-white rounded-2xl p-4 shadow-sm flex flex-col items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center relative overflow-hidden" data-npc-3d-icon="ai">
                        <span class="material-symbols-outlined npc-tile-icon text-[20px] text-secondary-container">auto_awesome</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Teaching AI</p>
                        <p class="text-[11px] font-mono text-blue-200">Lesson prep help</p>
                    </div>
                </a>
            </section>

            <!-- Today's Classes (live widget) -->
            <section class="npc-navy-card text-white rounded-2xl p-6 shadow-md relative overflow-hidden" aria-label="Today's classes">
                <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full blur-3xl opacity-25 pointer-events-none" style="background:radial-gradient(circle, rgba(254,212,136,.8), transparent 70%);"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-base flex items-center gap-2 text-white">
                            <span class="material-symbols-outlined text-secondary-container text-[20px]">today</span>
                            Today's Classes
                        </h3>
                        <span id="npc-today-date" class="font-mono text-[11px] uppercase tracking-wider text-blue-200"></span>
                    </div>
                    <div id="npc-today-classes" class="flex flex-col gap-2.5">
                        <p class="text-xs text-blue-100/90 animate-pulse">Checking today's schedule…</p>
                    </div>
                </div>
            </section>

            <!-- Assigned Classes Grid -->
            <div class="space-y-4">
                <div class="flex justify-between items-center flex-wrap gap-2">
                    <h2 class="text-lg font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">menu_book</span>
                        Your Assigned Classes & Sections
                    </h2>
                    <div class="flex items-center gap-3">
                        <a href="/teacher/courses.php" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">folder_open</span> Open Courses & Modules Hub →
                        </a>
                        <a href="/teacher/classes.php" class="text-xs text-on-surface-variant font-semibold hover:text-primary hover:underline">View Student Rosters →</a>
                    </div>
                </div>

                <div id="teacher-classes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div class="col-span-full bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant">
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
                    </div>
                </div>
            </div>

            <!-- Faculty Academic Calendar & Milestones (Full Width Mode) -->
            <section class="space-y-4" id="faculty-calendar-section">
                <?php $CALENDAR_PORTAL = 'faculty';
                $CALENDAR_MODE = 'full';
                include __DIR__ . '/../includes/_calendar.php'; ?>
            </section>

            <!-- Campus Bulletins (announcements feed) -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
                <div class="pb-4 border-b border-outline-variant/60 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[22px]" style="font-variation-settings: 'FILL' 1;">campaign</span>
                        <span>Campus Bulletins</span>
                    </h3>
                    <span class="font-mono text-xs text-on-surface-variant font-semibold bg-surface-container px-2 py-0.5 rounded">Live</span>
                </div>
                <div class="pt-4 flex flex-col gap-4" id="faculty-announcements-container">
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
                </div>
            </div>

            <!-- Class Announcement Composer & Recent Posts -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-outline-variant/60 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[20px]">campaign</span>
                        <h3 class="font-bold text-primary text-base">Post to My Class</h3>
                    </div>
                    <span class="text-xs font-mono text-gray-500">Section-scoped only</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Class / Section:</label>
                        <select id="ann-class" required class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold"></select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Category:</label>
                        <select id="ann-category" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                            <option value="general">General</option>
                            <option value="assignment">Assignment</option>
                            <option value="exam">Exam</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Title:</label>
                        <input type="text" id="ann-title" maxlength="160" required placeholder="e.g. Quiz 2 moved to Friday" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Message:</label>
                    <textarea id="ann-body" rows="3" placeholder="What would you like to announce to the class? They will receive a notification." class="w-full bg-white border border-gray-300 rounded-xl p-3 text-xs"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Attachment Link (optional):</label>
                        <input type="url" id="ann-attachment-url" placeholder="https://drive.google.com/…" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Schedule Post (optional):</label>
                        <input type="datetime-local" id="ann-scheduled-at" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2">
                        <p class="text-[10px] text-on-surface-variant mt-1">If scheduled, students will not see this until the designated time.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-[11px] text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">info</span> Enrolled students of the selected section will immediately receive a bell notification.</p>
                    <button onclick="postClassAnnouncement()" id="btn-post-ann" class="px-5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary-container shadow-sm flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-[15px]">send</span> Post Announcement
                    </button>
                </div>

                <div class="border-t border-outline-variant/40 pt-3">
                    <h4 class="font-bold text-xs text-gray-700 mb-2">Recent Class Posts:</h4>
                    <div id="my-class-announcements" class="space-y-2 max-h-56 overflow-y-auto custom-scroll text-xs">
                        <div class="npc-skeleton-group" aria-busy="true">
                            <div class="skeleton sk-line w-3/4"></div>
                            <div class="skeleton sk-line w-1/2"></div>
                            <div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Consultation Appointments & Class Materials Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Consultation Appointments (7 cols) -->
                <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-outline-variant/60 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">calendar_clock</span>
                            <h3 class="font-bold text-primary text-base">Student Consultation Schedule</h3>
                        </div>
                        <span class="text-xs font-mono text-gray-500">Live Requests</span>
                    </div>

                    <div id="consultations-list" class="space-y-3">
                        <div class="npc-skeleton-group p-1" aria-busy="true">
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
                </div>

                <!-- Right: Secure Class Materials Upload (5 cols) -->
                <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-outline-variant/60 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">upload_file</span>
                            <h3 class="font-bold text-primary text-base">Class Materials & Syllabi</h3>
                        </div>
                    </div>

                    <form id="material-upload-form" onsubmit="uploadClassMaterial(event)" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Document Title:</label>
                            <input type="text" id="mat-title" required placeholder="e.g. DM103 Course Syllabus & Guidelines" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Category:</label>
                                <select id="mat-category" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                                    <option value="Syllabus">Syllabus</option>
                                    <option value="Lecture Notes">Lecture Notes</option>
                                    <option value="Assignment">Assignment</option>
                                    <option value="Exam Guide">Exam Guide</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">File (PDF, DOC, PPT, XLS):</label>
                                <input type="file" id="mat-file" required class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-container">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Module / Week Label (optional):</label>
                            <input type="text" id="mat-module-week" maxlength="32" placeholder="e.g. Module 1 · Week 3" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                            <p class="text-[10px] text-on-surface-variant mt-1">Students will see this in the Materials tab for quick reference.</p>
                        </div>

                        <button type="submit" id="btn-upload-mat" class="w-full py-2.5 bg-primary text-white rounded-xl font-bold text-xs hover:bg-primary-container shadow-sm flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">cloud_upload</span> Upload to Portal
                        </button>
                    </form>

                    <div class="border-t border-outline-variant/40 pt-3">
                        <h4 class="font-bold text-xs text-gray-700 mb-2">Uploaded Materials:</h4>
                        <div id="materials-list" class="space-y-2 max-h-48 overflow-y-auto text-xs">
                            <div class="npc-skeleton-group" aria-busy="true">
                                <div class="sk-row">
                                    <div class="flex-1 min-w-0">
                                        <div class="skeleton sk-line w-3/4" style="margin-bottom:.3rem"></div>
                                        <div class="skeleton sk-line w-1/3" style="height:.6rem;margin-bottom:0"></div>
                                    </div>
                                    <div class="skeleton sk-chip"></div>
                                </div>
                                <div class="sk-row" style="padding-bottom:0">
                                    <div class="flex-1 min-w-0">
                                        <div class="skeleton sk-line w-2/3" style="margin-bottom:.3rem"></div>
                                        <div class="skeleton sk-line w-1/4" style="height:.6rem;margin-bottom:0"></div>
                                    </div>
                                    <div class="skeleton sk-chip"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 bg-gray-900 text-white text-xs px-4 py-3 rounded-xl shadow-2xl z-50 flex items-center gap-2.5 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-2">
        <span class="material-symbols-outlined text-[18px] text-emerald-400" id="toast-icon">check_circle</span>
        <span id="toast-message">Notification</span>
    </div>

    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);

        const currentTeacherName = <?= json_encode($teacher_name) ?>;
        const currentTeacherEmail = <?= json_encode($teacher_email) ?>;
        const isAdmin = <?= json_encode($is_admin) ?>;
        const csrfToken = <?= json_encode($csrf_token) ?>;

        async function loadTeacherDashboard() {
            const grid = document.getElementById('teacher-classes-grid');
            try {
                let classes = null;

                // 1. Primary: Server-side secure faculty endpoint
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

                // 2. Fallback: Local REST endpoint
                if (!classes) {
                    const res = await fetch('/api/rest.php?table=classes&select=*&order=code.asc');
                    const all = await res.json();
                    if (Array.isArray(all)) {
                        const myEmail = (currentTeacherEmail || '').toLowerCase().trim();
                        const myName = (currentTeacherName || '').toLowerCase().trim();
                        classes = all.filter(c => isClassAssignedToTeacher(c, myEmail, myName));
                    }
                }

                // STRICT FACULTY SCOPING: Only classes assigned to this faculty member appear
                const myClasses = classes || [];

                const classCountEl = document.getElementById('teacher-class-count');
                const total = myClasses.length;
                if (classCountEl) {
                    classCountEl.setAttribute('data-countup', String(total));
                    if (window.npcCountUp) window.npcCountUp(classCountEl);
                    else classCountEl.innerText = total;
                }

                // Populate announcement composer class selector
                const annSel = document.getElementById('ann-class');
                if (annSel && !annSel.options.length) {
                    annSel.innerHTML = myClasses.map(c =>
                        `<option value="${c.id}">${(c.code || '')} — ${(c.section || 'N/A')}</option>`
                    ).join('');
                }
                // Brief counter handled by renderTodayClasses below

                renderTodayClasses(myClasses);

                if (myClasses.length === 0) {
                    grid.innerHTML = `
                        <div class="col-span-full bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2 block">school</span>
                            <p class="font-semibold text-lg">No assigned classes found</p>
                        </div>
                    `;
                } else {
                    grid.innerHTML = myClasses.map(c => `
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 hover:shadow-md transition-shadow flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <span class="px-2.5 py-0.5 bg-primary/10 text-primary font-mono text-xs font-bold rounded-lg">${c.code}</span>
                                    <span class="text-xs font-mono font-bold bg-surface-container-low text-on-surface px-2 py-0.5 rounded">${c.section || 'AIS 2A'}</span>
                                </div>
                                <h3 class="font-bold text-sm text-primary mb-2">${c.title}</h3>
                                <div class="space-y-1 text-xs text-on-surface-variant font-medium">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[15px] text-status-info">schedule</span>
                                        <span>${c.schedule_day || 'TBA'} (${c.start_time || 'TBA'}${c.end_time ? ' - ' + c.end_time : ''})</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[15px] text-secondary">location_on</span>
                                        <span>${c.room || 'Room TBA'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 pt-3 border-t border-outline-variant/30 grid grid-cols-3 gap-2">
                                <a href="/teacher/courses.php?launch_class=${c.id}" class="bg-red-600/90 text-white py-2 rounded-xl text-center text-xs font-bold hover:bg-red-600 flex items-center justify-center gap-1 shadow-xs" title="Start PlugNmeet Live Classroom & Control Panel">
                                    <span class="material-symbols-outlined text-[14px]">co_present</span> Live Class
                                </a>
                                <a href="/teacher/attendance.php?class_id=${c.id}" class="bg-primary text-on-primary py-2 rounded-xl text-center text-xs font-bold hover:opacity-90 flex items-center justify-center gap-1 shadow-xs npc-navy-card">
                                    <span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> QR
                                </a>
                                <a href="/teacher/grades.php?class_id=${c.id}" class="bg-[#107c41] text-white py-2 rounded-xl text-center text-xs font-bold hover:bg-[#0c5d31] flex items-center justify-center gap-1 shadow-xs">
                                    <span class="material-symbols-outlined text-[14px]">grade</span> Grades
                                </a>
                                <a href="/teacher/courses.php?class_id=${c.id}" class="col-span-3 py-1.5 px-3 rounded-lg border border-primary/30 bg-primary/5 text-primary text-[11px] font-bold hover:bg-primary/10 flex items-center justify-center gap-1.5 transition-colors" title="Manage Modules, Tasks & Submissions">
                                    <span class="material-symbols-outlined text-[14px]">menu_book</span> Courses & Modules Hub
                                </a>
                            </div>
                        </div>
                    `).join('');
                }

                await loadConsultations();
                await loadMaterials();

            } catch (err) {
                console.error('Failed to load teacher dashboard:', err);
                if (grid) {
                    grid.innerHTML = `
                        <div class="col-span-full bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[48px] text-error mb-2 block">error</span>
                            <p class="font-semibold text-lg text-primary">Could Not Load Assigned Classes</p>
                            <p class="text-xs text-on-surface-variant mt-1 mb-4">Please check your network connection or try again.</p>
                            <button onclick="loadTeacherDashboard()" class="px-4 py-2 bg-primary text-on-primary rounded-xl text-xs font-bold npc-navy-card">Retry</button>
                        </div>
                    `;
                }
            }
        }

        async function loadConsultations() {
            try {
                const res = await fetch('/api/faculty.php?action=get_consultations');
                const data = await res.json();
                const list = document.getElementById('consultations-list');

                if (data.success && data.appointments && data.appointments.length > 0) {
                    const pendingCount = data.appointments.filter(a => a.status === 'Pending').length;
                    document.getElementById('teacher-consult-count').innerText = pendingCount;

                    list.innerHTML = data.appointments.map(a => `
                        <div class="p-3 bg-surface rounded-xl border border-outline-variant/60 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-primary">${a.student_name}</span>
                                    <span class="font-mono text-[11px] text-gray-500">(${a.student_number})</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${a.status === 'Confirmed' ? 'bg-emerald-100 text-emerald-800' : (a.status === 'Declined' ? 'bg-red-100 text-error' : (a.status === 'Completed' ? 'bg-blue-100 text-blue-800' : (a.status === 'Rescheduled' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800')))}">${a.status}</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-0.5"><strong>Topic:</strong> ${a.topic} • <strong>Date:</strong> ${a.requested_date} @ ${a.requested_time}</p>
                            </div>
                            ${a.status === 'Pending' ? `
                                <div class="flex items-center gap-1">
                                    <button onclick="updateConsultationStatus('${a.id}', 'Confirmed')" class="px-2.5 py-1 bg-emerald-600 text-white rounded-lg font-bold text-[11px]">Confirm</button>
                                    <button onclick="updateConsultationStatus('${a.id}', 'Declined')" class="px-2.5 py-1 bg-red-600 text-white rounded-lg font-bold text-[11px]">Decline</button>
                                    <button onclick="updateConsultationStatus('${a.id}', 'Rescheduled')" class="px-2.5 py-1 border border-outline-variant text-primary rounded-lg font-bold text-[11px] hover:bg-surface-container" title="Offer a new schedule">Reschedule</button>
                                </div>
                            ` : (a.status === 'Confirmed' ? `
                                <div class="flex items-center gap-1">
                                    <button onclick="updateConsultationStatus('${a.id}', 'Completed')" class="px-2.5 py-1 bg-blue-600 text-white rounded-lg font-bold text-[11px]" title="Mark as done">Complete</button>
                                    <button onclick="updateConsultationStatus('${a.id}', 'Rescheduled')" class="px-2.5 py-1 border border-outline-variant text-primary rounded-lg font-bold text-[11px] hover:bg-surface-container">Reschedule</button>
                                </div>
                            ` : '')}
                        </div>
                    `).join('');
                } else {
                    document.getElementById('teacher-consult-count').innerText = '0';
                    list.innerHTML = '<p class="text-xs text-gray-400 p-4 text-center">No student consultation requests pending.</p>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function updateConsultationStatus(id, status) {
            let payload = {
                appointment_id: id,
                status: status,
                notes: '',
                csrf_token: csrfToken
            };

            if (status === 'Rescheduled') {
                const nd = prompt('New date (YYYY-MM-DD):', '');
                if (!nd || nd.trim() === '') return;
                const nt = prompt('New time (e.g. 14:00 or 2:00 PM):', '');
                if (!nt || nt.trim() === '') return;
                payload.new_date = nd.trim();
                payload.new_time = nt.trim();
            } else {
                const note = prompt(`Optional notes for "${status}":`, '') ?? '';
                payload.notes = note.trim();
            }

            try {
                const res = await fetch('/api/faculty.php?action=update_consultation_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || ('Marked as ' + status), 'success');
                    await loadConsultations();
                } else {
                    showToast(data.message || 'Update failed.', 'error');
                }
            } catch (err) {
                showToast('Network error: ' + err.message, 'error');
            }
        }

        /* ── Class announcement composer ── */
        async function postClassAnnouncement() {
            const classId = document.getElementById('ann-class').value;
            const title = document.getElementById('ann-title').value.trim();
            const body = document.getElementById('ann-body').value.trim();
            const category = document.getElementById('ann-category').value;

            if (!classId || !title) {
                showToast('Please select a class and enter an announcement title.', 'error');
                return;
            }

            const btn = document.getElementById('btn-post-ann');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">sync</span> Posting…';

            try {
                const res = await fetch('/api/faculty.php?action=post_class_announcement', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        class_id: classId,
                        title: title,
                        body: body,
                        category: category,
                        attachment_url: document.getElementById('ann-attachment-url')?.value.trim() || '',
                        attachment_name: (document.getElementById('ann-attachment-url')?.value.trim() || '').split('/').pop() || '',
                        scheduled_at: document.getElementById('ann-scheduled-at')?.value || '',
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`${data.message} (${data.notified || 0} students notified)`, 'success');
                    document.getElementById('ann-title').value = '';
                    document.getElementById('ann-body').value = '';
                    await loadMyAnnouncements();
                } else {
                    showToast(data.message || 'Failed to post.', 'error');
                }
            } catch (err) {
                showToast('Network error: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">send</span> Post Announcement';
            }
        }

        async function loadMyAnnouncements() {
            try {
                const res = await fetch('/api/faculty.php?action=get_class_announcements');
                const data = await res.json();
                const list = document.getElementById('my-class-announcements');
                const countEl = document.getElementById('brief-ann-count');

                if (data.success && data.announcements && data.announcements.length > 0) {
                    if (countEl) countEl.innerText = data.announcements.length;
                    list.innerHTML = data.announcements.slice(0, 20).map(a => `
                        <div class="p-2.5 bg-surface rounded-xl border border-outline-variant/50">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <span class="font-mono text-[10px] font-bold text-primary">${a.class_code || ''} · ${a.section || ''}</span>
                                <span class="text-[10px] font-mono text-gray-400">${new Date(a.created_at).toLocaleDateString()}</span>
                            </div>
                            <p class="font-bold text-on-surface break-words">${facultyEscapeHtml(a.title)}</p>
                            ${a.body ? `<p class="text-[11px] text-gray-600 break-words mt-0.5" style="overflow-wrap:anywhere;">${facultyEscapeHtml(a.body)}</p>` : ''}
                        </div>
                    `).join('');
                } else {
                    if (countEl) countEl.innerText = '0';
                    list.innerHTML = '<p class="text-xs text-gray-400">No posts yet — your announcements will appear here.</p>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function uploadClassMaterial(e) {
            e.preventDefault();
            const title = document.getElementById('mat-title').value.trim();
            const category = document.getElementById('mat-category').value;
            const fileInput = document.getElementById('mat-file');

            if (!fileInput.files[0]) return alert('Please choose a file.');

            const formData = new FormData();
            formData.append('title', title);
            formData.append('category', category);
            formData.append('file', fileInput.files[0]);
            formData.append('module_week', document.getElementById('mat-module-week')?.value.trim() || '');
            formData.append('csrf_token', csrfToken);

            document.getElementById('btn-upload-mat').disabled = true;
            document.getElementById('btn-upload-mat').innerText = 'Uploading...';

            try {
                const res = await fetch('/api/faculty.php?action=upload_material', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ Class material uploaded successfully!');
                    document.getElementById('material-upload-form').reset();
                    await loadMaterials();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('Upload failed: ' + err.message);
            } finally {
                document.getElementById('btn-upload-mat').disabled = false;
                document.getElementById('btn-upload-mat').innerHTML = '<span class="material-symbols-outlined text-[16px]">cloud_upload</span> Upload to Portal';
            }
        }

        async function loadMaterials() {
            try {
                const res = await fetch('/api/faculty.php?action=get_materials');
                const data = await res.json();
                const list = document.getElementById('materials-list');

                if (data.success && data.materials && data.materials.length > 0) {
                    const matCountEl = document.getElementById('brief-mat-count');
                    if (matCountEl) matCountEl.innerText = data.materials.length;
                    list.innerHTML = data.materials.map(m => `
                        <div class="p-2.5 bg-surface rounded-xl border flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-primary truncate">${m.title}</p>
                                <span class="text-[10px] text-gray-500">${m.category} • ${m.file_size || ''}</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <a href="/api/download_material.php?file=${encodeURIComponent(m.file_name || '')}" class="text-status-info material-symbols-outlined text-[18px] hover:opacity-70" title="Download">download</a>
                                <button onclick="deleteMaterial('${m.id}', '${facultyEscapeHtml(m.title).replace(/'/g, '&#39;')}')" class="text-error material-symbols-outlined text-[18px] hover:opacity-70" title="Delete material">delete</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p class="text-xs text-gray-400">No materials uploaded yet.</p>';
                }
            } catch (err) {
                console.error(err);
            }
        }


        async function deleteMaterial(id, title) {
            if (!await npcConfirm({
                    title: 'Delete Material',
                    message: `Delete "${title}"? Students will no longer see this material.`,
                    type: 'danger',
                    confirmText: 'Yes, Delete'
                })) return;
            try {
                const res = await fetch('/api/faculty.php?action=delete_material', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        id: id,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Material deleted.', 'success');
                    await loadMaterials();
                } else {
                    showToast(data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                showToast('Network error: ' + err.message, 'error');
            }
        }

        /* Styled toast — replaces alert() across the faculty dashboard */
        function showToast(msg, kind) {
            var t = document.getElementById('toast');
            if (!t) return;
            var icon = document.getElementById('toast-icon');
            var msgEl = document.getElementById('toast-message');
            icon.textContent = kind === 'error' ? 'error' : 'check_circle';
            icon.className = 'material-symbols-outlined text-[18px] ' + (kind === 'error' ? 'text-red-400' : 'text-emerald-400');
            msgEl.textContent = String(msg);
            t.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            clearTimeout(window.__npcToastTimer);
            window.__npcToastTimer = setTimeout(function() {
                t.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            }, 4000);
        }

        /* ── Faculty announcements feed (full-text cards) ── */
        function facultyEscapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function facultyRenderBody(raw) {
            if (!raw) return '<p class="text-on-surface-variant/70 italic text-xs">No announcement content.</p>';
            let text = String(raw).trim()
                .replace(/\s*bis_skin_checked="[^"]*"/gi, '')
                .replace(/\s*contenteditable="[^"]*"/gi, '')
                .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
                .replace(/on\w+="[^"]*"/gi, '');
            if (/<(div|p|span|ul|ol|li|h[1-6]|strong|b|em|i|blockquote|table|br)/i.test(text)) {
                return `<div class="announcement-content text-xs leading-relaxed break-words" style="overflow-wrap:anywhere;word-break:break-word;">${text}</div>`;
            }
            const formatted = facultyEscapeHtml(text)
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-primary">$1</strong>')
                .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
                .replace(/• (.*?)(\n|$)/g, '<li class="ml-4 list-disc">$1</li>')
                .replace(/\n/g, '<br>');
            return `<div class="announcement-content text-xs leading-relaxed break-words" style="overflow-wrap:anywhere;word-break:break-word;">${formatted}</div>`;
        }
        async function loadFacultyAnnouncements() {
            const container = document.getElementById('faculty-announcements-container');
            if (!container || typeof supabase === 'undefined') return;
            try {
                const {
                    data,
                    error
                } = await supabaseClient
                    .from('announcements')
                    .select('*')
                    .eq('status', 'published')
                    .order('created_at', {
                        ascending: false
                    })
                    .limit(6);
                if (error) throw error;
                if (!data || !data.length) {
                    container.innerHTML = '<div class="text-center text-on-surface-variant text-sm py-4">No recent announcements.</div>';
                    return;
                }
                container.innerHTML = data.map(a => {
                    const isEmergency = a.category === 'emergency';
                    const isAcademic = a.category === 'academic';
                    const badgeClass = isEmergency ? 'bg-error-container text-error' :
                        (isAcademic ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-primary');
                    const icon = isEmergency ? 'warning' : (isAcademic ? 'school' : 'campaign');
                    const dateStr = new Date(a.created_at).toLocaleDateString();
                    return `
                    <div class="p-3.5 bg-surface-container-low/40 rounded-xl border border-outline-variant/40 hover:bg-surface-container-low transition-colors space-y-2 overflow-hidden">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full ${badgeClass} font-mono text-[10px] uppercase font-bold tracking-wide shrink-0">
                                <span class="material-symbols-outlined text-[12px]">${icon}</span> ${facultyEscapeHtml(a.category)}
                            </span>
                            <span class="font-mono text-[10px] text-on-surface-variant shrink-0">${dateStr}</span>
                        </div>
                        <h4 class="text-sm font-bold text-primary break-words leading-snug" style="overflow-wrap:anywhere;word-break:break-word;">${facultyEscapeHtml(a.title)}</h4>
                        <div class="announcement-body text-xs leading-relaxed">${facultyRenderBody(a.body)}</div>
                    </div>`;
                }).join('');
            } catch (err) {
                console.error('Faculty announcements failed:', err);
                container.innerHTML = '<div class="text-center text-on-surface-variant text-sm py-4">Announcements unavailable right now.</div>';
            }
        }
        if (typeof supabaseClient !== 'undefined') loadFacultyAnnouncements();

        loadTeacherDashboard();
        loadMyAnnouncements();
        loadRecentAbsences();

        // Real-time background refresh for teacher dashboard
        setInterval(function() {
            loadTeacherDashboard();
            loadRecentAbsences();
        }, 6000);

        /* ── Recent absences across my last sessions ── */
        async function loadRecentAbsences() {
            try {
                const res = await fetch('/api/faculty.php?action=get_my_sessions&limit=5');
                const data = await res.json();
                const card = document.getElementById('recent-absences-card');
                const list = document.getElementById('recent-absences-list');
                if (!data.success || !data.absences || !data.absences.length) return;
                card.classList.remove('hidden');
                list.innerHTML = data.absences.slice(0, 12).map(a => `
                    <div class="px-5 py-2.5 flex items-center justify-between gap-3 text-xs">
                        <div class="min-w-0">
                            <span class="font-bold text-on-surface">${a.student_name}</span>
                            <span class="font-mono text-[10px] text-on-surface-variant ml-1">(${a.student_number})</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="font-mono text-[10px] text-primary font-bold">${a.class_code}</span>
                            <span class="text-[10px] font-mono text-on-surface-variant">${new Date(a.check_in_at).toLocaleDateString()}</span>
                            ${a.method === 'auto_absent' ? '<span class="px-1.5 py-0.5 rounded bg-error/10 text-error text-[9px] font-bold uppercase">auto</span>' : ''}
                        </div>
                    </div>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        /* ── Live clock + Today's Classes widget ─────────────── */
        (function() {
            var clockEl = document.getElementById('npc-live-clock');
            if (clockEl) {
                function tick() {
                    clockEl.textContent = new Date().toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
                tick();
                setInterval(tick, 1000);
            }

            var dateEl = document.getElementById('npc-today-date');
            if (dateEl) {
                dateEl.textContent = new Date().toLocaleDateString('en-US', {
                    weekday: 'long',
                    month: 'long',
                    day: 'numeric'
                }).toUpperCase();
            }
        })();

        function renderTodayClasses(classes) {
            var wrap = document.getElementById('npc-today-classes');
            if (!wrap) return;
            var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var today = dayNames[new Date().getDay()];

            var todays = (classes || []).filter(function(c) {
                var d = String(c.schedule_day || '').toLowerCase();
                return d.indexOf(today.toLowerCase()) !== -1;
            });

            if (!todays.length) {
                var todayZeroEl = document.getElementById('brief-today-count');
                if (todayZeroEl) todayZeroEl.innerText = '0';
                wrap.innerHTML = '<div class="flex items-center gap-2.5 text-sm text-blue-100/90 py-1">' +
                    '<span class="material-symbols-outlined text-[18px] text-secondary-container">event_available</span>' +
                    '<span>No classes scheduled for today — enjoy the break, Prof.!</span>' +
                    '</div>';
                return;
            }

            todays.sort(function(a, b) {
                return String(a.start_time || '').localeCompare(String(b.start_time || ''));
            });

            var todayCountEl = document.getElementById('brief-today-count');
            if (todayCountEl) todayCountEl.innerText = String(todays.length);

            wrap.innerHTML = todays.map(function(c) {
                return '<div class="flex items-center justify-between gap-3 rounded-xl px-4 py-2.5 bg-white/10 border border-white/10 hover:bg-white/15 transition-colors">' +
                    '<div class="flex items-center gap-3 min-w-0">' +
                    '<span class="font-mono text-xs font-bold text-secondary-container shrink-0">' + (c.start_time || '') + '</span>' +
                    '<div class="min-w-0"><p class="text-sm font-semibold truncate">' + (c.code || '') + ' — ' + (c.title || '') + '</p>' +
                    '<p class="text-[11px] text-blue-200 truncate">' + (c.room || 'Room TBA') + ' • Section ' + (c.section || '—') + '</p></div>' +
                    '</div>' +
                    '<div class="flex items-center gap-1.5 shrink-0">' +
                    '<a href="/teacher/courses.php?launch_class=' + c.id + '" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-600/90 text-white text-[11px] font-bold hover:bg-red-600 transition-colors shadow-xs" title="Start PlugNmeet Live Classroom & Control Panel">' +
                    '<span class="material-symbols-outlined text-[14px]">co_present</span> Live Class</a>' +
                    '<a href="/teacher/attendance.php?class_id=' + c.id + '" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-secondary-container text-on-secondary-container text-[11px] font-bold hover:opacity-90 press">' +
                    '<span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> QR</a>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }
    </script>
</body>

</html>