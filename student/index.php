<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar');
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $PAGE_TITLE = 'Student Dashboard · NPC LMS';
    include __DIR__ . '/../includes/_head.php'; ?>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar (Sticky Desktop navigation) -->
        <?php $NPC_PORTAL = 'student';
        include __DIR__ . '/../includes/_sidebar.php'; ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-3">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-base sm:text-lg font-bold text-primary lg:hidden truncate">NPC LMS</span>
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Dashboard</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <a href="/student/profile.php" class="flex items-center gap-2 p-1 rounded-xl hover:bg-surface-container transition-all group" title="View My Profile">
                        <span class="font-mono text-[11px] sm:text-xs font-semibold bg-surface-container px-2 sm:px-3 py-1.5 rounded-md border border-outline-variant text-primary hidden sm:inline shrink-0" id="user-id-chip">ID: <?= htmlspecialchars($user_id_display) ?></span>
                        <?php 
                        $userAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                        if (!empty($userAvatar)): ?>
                            <img alt="<?= htmlspecialchars($raw_name) ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-amber-300 shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all"
                                src="<?= htmlspecialchars($userAvatar) ?>" referrerpolicy="no-referrer"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($user_name, 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm flex items-center justify-center shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($user_name, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-sm font-semibold text-primary hidden md:inline truncate max-w-[120px] group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors" id="user-name-display"><?= htmlspecialchars($raw_name) ?></span>
                    </a>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto overflow-x-hidden" id="canvas-container">

                <!-- 1. LOGIN VIEW REMOVED - NOW IN LOGIN.PHP -->

                <!-- 2. STUDENT DASHBOARD VIEW -->
                <div id="dashboard-view" class="view active">
                    <!-- Dashboard Greeting Header -->
                    <div class="mb-6 sm:mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-outline-variant/60 pb-5 sm:pb-6">
                        <div>
                            <?php
                            // ── Role-aware greeting ──────────────────────────────
                            $npcRole = $_SESSION['role'] ?? 'student';
                            if ($npcRole === 'admin') {
                                $npcHello  = 'Administrator view — seeing the portal exactly as students do.';
                                $npcBadge  = ['ADMIN VIEW', 'shield_person', 'bg-error/10 text-error border border-error/30'];
                                $npcWave   = 'shield_person';
                            } elseif ($npcRole === 'teacher') {
                                $npcHello  = 'Faculty view — checking what your students see.';
                                $npcBadge  = ['FACULTY VIEW', 'cast_for_education', 'bg-secondary-container text-on-secondary-container border border-secondary-container'];
                                $npcWave   = 'cast_for_education';
                            } else {
                                $npcHello  = 'Here is your real-time academic overview and schedule.';
                                $npcBadge  = ['STUDENT', 'school', 'bg-surface-container text-primary border border-outline-variant'];
                                $npcWave   = 'waving_hand';
                            }
                            ?>
                            <!-- Date & Status Meta Pill Row -->
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-2.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg bg-surface-container border border-outline-variant font-mono text-[11px] sm:text-xs font-semibold text-primary shadow-sm">
                                    <span class="material-symbols-outlined text-[14px] sm:text-[15px] text-primary">calendar_today</span>
                                    <span id="current-date"><?= date('l, F j, Y') ?></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg bg-surface-container border border-outline-variant font-mono text-[11px] sm:text-xs font-semibold text-on-surface-variant shadow-sm">
                                    <span class="material-symbols-outlined text-[14px] sm:text-[15px] text-status-success animate-pulse">schedule</span>
                                    <span id="npc-live-clock"><?= date('g:i:s A') ?></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase tracking-widest <?= $npcBadge[2] ?>">
                                    <span class="material-symbols-outlined text-[13px]"><?= $npcBadge[1] ?></span>
                                    <?= $npcBadge[0] ?>
                                </span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight">
                                <span class="text-shimmer text-primary">Welcome, <?= htmlspecialchars($user_name) ?>.</span>
                                <span class="inline-block align-middle ml-1.5 animate-float material-symbols-outlined text-[22px] sm:text-[28px] text-secondary" aria-hidden="true"><?= $npcWave ?></span>
                            </h2>
                            <p class="text-sm sm:text-base text-on-surface-variant mt-1">
                                <span><?= htmlspecialchars($npcHello) ?></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container border border-outline-variant text-primary font-mono text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full <?= $is_logged_in ? 'bg-status-success' : 'bg-status-warning' ?>"></span>
                                <?= $is_logged_in ? 'Student ID: ' . htmlspecialchars($user_id_display) : 'Guest Mode' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Live Class Alert Banner (Active sessions in student's section) -->
                    <div id="student-dash-live-banner" class="hidden mb-6"></div>

                    <!-- Quick Actions Bento Grid -->
                    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-8" aria-label="Quick actions">
                        <a href="/student/campus_map.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-3.5 sm:p-5 shadow-sm flex items-center gap-3 sm:gap-4 group">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined npc-tile-icon text-[20px] sm:text-[22px]">map</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-on-surface truncate">NPC Map</p>
                                <p class="text-[10px] sm:text-[11px] text-on-surface-variant font-mono truncate">Campus layout</p>
                            </div>
                        </a>
                        <a href="/student/qrcode.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-3.5 sm:p-5 shadow-sm flex items-center gap-3 sm:gap-4 group">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shrink-0 npc-navy-card relative overflow-hidden" data-npc-3d-icon="qr">
                                <span class="material-symbols-outlined npc-tile-icon text-[20px] sm:text-[22px]">qr_code_scanner</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-on-surface truncate">Scan Attendance</p>
                                <p class="text-[10px] sm:text-[11px] text-on-surface-variant font-mono truncate">QR check-in</p>
                            </div>
                        </a>
                        <a href="/student/schedule.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-3.5 sm:p-5 shadow-sm flex items-center gap-3 sm:gap-4 group">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined npc-tile-icon text-[20px] sm:text-[22px]">calendar_month</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-on-surface truncate">My Schedule</p>
                                <p class="text-[10px] sm:text-[11px] text-on-surface-variant font-mono truncate">Weekly view</p>
                            </div>
                        </a>
                        <a href="/student/academic.php" class="npc-card npc-tile ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-3.5 sm:p-5 shadow-sm flex items-center gap-3 sm:gap-4 group">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-surface-container-high text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined npc-tile-icon text-[20px] sm:text-[22px]">insights</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-on-surface truncate">My Grades</p>
                                <p class="text-[10px] sm:text-[11px] text-on-surface-variant font-mono truncate">Performance</p>
                            </div>
                        </a>
                        <a href="/student/ai_assistant.php" class="col-span-2 sm:col-span-1 npc-card npc-tile ripple press npc-navy-card text-white rounded-2xl p-3.5 sm:p-5 shadow-sm flex items-center gap-3 sm:gap-4 group">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white/10 flex items-center justify-center shrink-0 relative overflow-hidden" data-npc-3d-icon="ai">
                                <span class="material-symbols-outlined npc-tile-icon text-[20px] sm:text-[22px] text-secondary-container">smart_toy</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-white truncate">Ask Campus AI</p>
                                <p class="text-[10px] sm:text-[11px] font-mono text-blue-200 truncate">Instant answers</p>
                            </div>
                        </a>
                    </section>

                    <!-- Main Content 2-Column Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                        <!-- Left 2-Column Area: Classes & Attendance -->
                        <div class="lg:col-span-2 flex flex-col gap-6">

                            <!-- Smart Alerts Strip — populated by loadSmartAlerts() -->
                            <div id="smart-alerts-strip" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3" aria-label="Your alerts"></div>

                            <!-- Upcoming Classes Card -->
                            <!-- Live Next-Class Ticker — populated by loadClassesFeed() -->
                            <div id="next-class-ticker" class="hidden mb-4 rounded-2xl border border-secondary-container/30 bg-secondary-container/10 px-4 sm:px-5 py-4 shadow-sm animate-fade-in">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-secondary text-[18px] animate-pulse">schedule</span>
                                    <span class="font-mono text-[10px] font-bold uppercase tracking-wider text-secondary">Next Class Today</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-on-surface">subject</span>
                                        <span class="font-bold text-secondary" id="next-subject">—</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">access_time</span>
                                        <span class="font-mono text-sm text-on-surface" id="next-time">—</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">meeting_room</span>
                                        <span class="text-sm text-on-surface" id="next-room">—</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">person</span>
                                        <span class="text-sm text-on-surface" id="next-teacher">—</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto sm:ml-auto pt-2 sm:pt-0 no-print">
                                        <a href="/student/schedule.php" class="px-3 py-1.5 rounded-lg bg-primary text-white text-[11px] font-bold hover:bg-primary-container shadow-sm inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">calendar_month</span> View Schedule</a>
                                        <a href="/student/qrcode.php" class="px-3 py-1.5 rounded-lg border border-outline-variant text-primary text-[11px] font-bold hover:bg-surface-container shadow-sm inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> Check Attendance</a>
                                    </div>
                                </div>
                            </div>

                            <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant overflow-hidden shadow-sm">
                                <div class="p-5 border-b border-outline-variant/60 flex justify-between items-center bg-surface-subtle">
                                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-[20px]">school</span>
                                        <span>Upcoming Classes</span>
                                    </h3>
                                    <span class="font-mono text-xs text-on-surface-variant font-medium bg-surface-container px-2.5 py-1 rounded">Today</span>
                                </div>

                                <div class="divide-y divide-outline-variant/40">
                                    <?php if (!$is_logged_in): ?>
                                        <div class="p-8 text-center flex flex-col items-center justify-center">
                                            <span class="material-symbols-outlined text-[48px] text-outline-variant mb-3">lock</span>
                                            <h4 class="text-lg font-semibold text-on-surface mb-1">Login Required</h4>
                                            <p class="text-sm text-on-surface-variant max-w-md mx-auto mb-4">You must be logged in as an official NPC student to view your upcoming classes and schedule.</p>
                                            <a href="/login.php" class="px-5 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-primary-container transition-colors inline-flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px]">login</span> Sign In
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div id="classes-container" class="divide-y divide-outline-variant/40">
                                            <div class="npc-skeleton-group p-5" aria-busy="true">
                                                <div class="sk-row">
                                                    <div class="skeleton sk-avatar"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="skeleton sk-line w-2/3"></div>
                                                        <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                                                    </div>
                                                </div>
                                                <div class="sk-row">
                                                    <div class="skeleton sk-avatar"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="skeleton sk-line w-3/4"></div>
                                                        <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                                                    </div>
                                                </div>
                                                <div class="sk-row">
                                                    <div class="skeleton sk-avatar"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="skeleton sk-line w-1/2"></div>
                                                        <div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div>
                                                    </div>
                                                </div>
                                                <div class="sk-row" style="padding-bottom:0">
                                                    <div class="skeleton sk-avatar"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="skeleton sk-line w-2/3"></div>
                                                        <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <!-- Attendance Summary Widget -->
                            <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant p-6 shadow-sm">
                                <h3 class="text-lg font-bold text-primary mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">fact_check</span>
                                    <span>Attendance Overview</span>
                                </h3>

                                <?php if (!$is_logged_in): ?>
                                    <div class="text-center py-6">
                                        <span class="material-symbols-outlined text-[36px] text-outline-variant mb-2">lock</span>
                                        <p class="text-sm text-on-surface-variant font-medium">Login to view attendance</p>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col sm:flex-row items-center justify-around gap-6 sm:gap-8">
                                        <!-- Donut Chart -->
                                        <div class="relative w-32 h-32 shrink-0">
                                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                                <!-- Background Circle -->
                                                <path class="text-surface-container-high" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3.5"></path>
                                                <!-- Present (Green/Primary) -->
                                                <path id="donut-present-path" class="text-primary transition-all duration-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="100, 100" stroke-width="3.5"></path>
                                                <!-- Late (Yellow) -->
                                                <path id="donut-late-path" class="text-secondary-container transition-all duration-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="0, 100" stroke-dashoffset="0" stroke-width="3.5"></path>
                                                <!-- Absent (Red) -->
                                                <path id="donut-absent-path" class="text-error transition-all duration-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="0, 100" stroke-dashoffset="0" stroke-width="3.5"></path>
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                <span id="attendance-rate-display" class="text-2xl font-bold text-primary">100%</span>
                                                <span class="text-[10px] font-mono text-on-surface-variant uppercase">Rate</span>
                                            </div>
                                        </div>

                                        <!-- Legend Cards -->
                                        <div class="flex-1 w-full grid grid-cols-3 gap-2.5 sm:gap-3">
                                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/60 flex flex-col items-center text-center">
                                                <div class="w-2.5 h-2.5 rounded-full bg-primary mb-1.5"></div>
                                                <span class="font-mono text-[10px] text-on-surface-variant uppercase mb-0.5">Present</span>
                                                <span id="attendance-present-stat" class="text-base sm:text-lg font-bold text-on-surface">100%</span>
                                                <span id="attendance-present-count" class="text-[10px] font-mono text-on-surface-variant/80">0 scans</span>
                                            </div>
                                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/60 flex flex-col items-center text-center">
                                                <div class="w-2.5 h-2.5 rounded-full bg-secondary-container mb-1.5"></div>
                                                <span class="font-mono text-[10px] text-on-surface-variant uppercase mb-0.5">Late</span>
                                                <span id="attendance-late-stat" class="text-base sm:text-lg font-bold text-on-surface">0%</span>
                                                <span id="attendance-late-count" class="text-[10px] font-mono text-on-surface-variant/80">0 scans</span>
                                            </div>
                                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/60 flex flex-col items-center text-center">
                                                <div class="w-2.5 h-2.5 rounded-full bg-error mb-1.5"></div>
                                                <span class="font-mono text-[10px] text-on-surface-variant uppercase mb-0.5">Absent</span>
                                                <span id="attendance-absent-stat" class="text-base sm:text-lg font-bold text-error">0%</span>
                                                <span id="attendance-absent-count" class="text-[10px] font-mono text-on-surface-variant/80">0 scans</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>

                        <!-- Right Column: Academic Calendar & Announcements Feed -->
                        <div class="lg:col-span-1 flex flex-col gap-6">
                            <!-- Academic Calendar Widget (Compact Mode for sidebar column) -->
                            <?php $CALENDAR_PORTAL = 'student';
                            $CALENDAR_MODE = 'compact';
                            include __DIR__ . '/../includes/_calendar.php'; ?>

                            <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col">
                                <div class="pb-4 border-b border-outline-variant/60 flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                                        <span class="material-symbols-outlined text-secondary text-[22px]" style="font-variation-settings: 'FILL' 1;">campaign</span>
                                        <span>Campus Bulletins</span>
                                    </h3>
                                    <span class="font-mono text-xs text-on-surface-variant font-semibold bg-surface-container px-2 py-0.5 rounded">Live</span>
                                </div>

                                <div class="py-2 flex flex-col gap-3.5" id="announcements-container">
                                    <div class="npc-skeleton-group" aria-busy="true">
                                        <div class="sk-row">
                                            <div class="skeleton sk-avatar"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="skeleton sk-line w-3/4"></div>
                                                <div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div>
                                            </div>
                                        </div>
                                        <div class="sk-row">
                                            <div class="skeleton sk-avatar"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="skeleton sk-line w-2/3"></div>
                                                <div class="skeleton sk-line w-1/3" style="margin-bottom:0"></div>
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
                    </div>
                </div>

                <script>
                    const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
                    const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
                    let supabaseClient = null;
                    try {
                        supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
                    } catch (e) {
                        console.warn('Supabase init failed:', e);
                    }

                    function escapeHtml(text) {
                        if (!text) return '';
                        var d = document.createElement('div');
                        d.textContent = String(text);
                        return d.innerHTML;
                    }

                    // Fetch dynamic data for dashboard
                    document.addEventListener('DOMContentLoaded', async () => {
                        loadClassesFeed();
                        try {
                            loadAnnouncementsFeed();
                        } catch (e) {
                            console.warn('announcements:', e);
                        }
                        try {
                            loadAttendanceOverview();
                        } catch (e) {
                            console.warn('attendance:', e);
                        }
                        try {
                            loadSmartAlerts();
                        } catch (e) {
                            console.warn('alerts:', e);
                        }
                    });

                    /* ── Smart Alerts Strip: attendance warning, pending docs, unread notifs, latest grade ── */
                    async function loadSmartAlerts() {
                        const strip = document.getElementById('smart-alerts-strip');
                        if (!strip) return;
                        const esc = t => String(t == null ? '' : t).replace(/[&<>"']/g, c => ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#39;'
                        } [c]));
                        strip.innerHTML = '<div class="npc-skeleton-group p-3 sm:col-span-2 xl:col-span-4" aria-busy="true"><div class="sk-row"><div class="skeleton sk-avatar"></div><div class="flex-1 min-w-0"><div class="skeleton sk-line w-2/3"></div><div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div></div></div></div>';

                        const alerts = [];

                        // 1. Attendance warning + 2. Latest grade + pending docs (parallel)
                        const [attRes, gradeRes, docRes] = await Promise.allSettled([
                            fetch('/api/student.php?action=get_attendance_metrics').then(r => r.json()),
                            fetch('/api/student.php?action=get_my_grades').then(r => r.json()),
                            fetch('/api/student.php?action=get_document_requests').then(r => r.json())
                        ]);

                        if (attRes.status === 'fulfilled' && attRes.value?.success && attRes.value.metrics) {
                            const m = attRes.value.metrics;
                            const rate = parseFloat(m.rate) || null;
                            if (rate !== null && rate < 75) {
                                alerts.push({
                                    kind: 'danger',
                                    icon: 'warning',
                                    title: 'Attendance Warning!',
                                    body: `Your attendance is at ${rate}% — you may be disqualified from exams if it drops further.`,
                                    href: 'academic.php?tab=attendance'
                                });
                            } else if (m.absent > 0 || m.late > 0) {
                                alerts.push({
                                    kind: 'warn',
                                    icon: 'event_busy',
                                    title: 'Attendance Record',
                                    body: `${m.absent || 0} absent · ${m.late || 0} late this semester.`,
                                    href: 'academic.php?tab=attendance'
                                });
                            }
                        }

                        if (gradeRes.status === 'fulfilled' && gradeRes.value?.success) {
                            const grades = (gradeRes.value.grades || []).filter(g => g.grade && parseFloat(g.grade) > 0);
                            const latest = grades[grades.length - 1];
                            if (latest) {
                                alerts.push({
                                    kind: 'info',
                                    icon: 'military_tech',
                                    title: `New Grade: ${latest.subject_code}`,
                                    body: `Grade ${latest.grade} (${latest.units || 3} units) — published.`,
                                    href: 'academic.php'
                                });
                            }
                        }

                        if (docRes.status === 'fulfilled' && docRes.value?.success) {
                            const pending = (docRes.value.requests || []).filter(d => !['Released', 'Rejected'].includes(d.status));
                            if (pending.length) {
                                alerts.push({
                                    kind: 'info',
                                    icon: 'receipt_long',
                                    title: 'Document Request',
                                    body: `${pending.length} pending — latest: ${pending[0].status}.`,
                                    href: 'academic.php?tab=docs'
                                });
                            }
                        }

                        // Render
                        if (!alerts.length) {
                            strip.innerHTML = '';
                            return;
                        }
                        const tone = {
                            danger: ['border-error/40 bg-error/10 text-error', 'text-error'],
                            warn: ['border-status-warning/40 bg-status-warning/10 text-status-warning', 'text-status-warning'],
                            info: ['border-primary/30 bg-primary/5 text-primary', 'text-primary']
                        };
                        strip.innerHTML = alerts.slice(0, 4).map(a => `
                        <a href="${a.href}" class="rounded-2xl border ${tone[a.kind][0]} p-4 flex items-start gap-3 hover:shadow-md transition-shadow">
                            <span class="material-symbols-outlined text-[20px] shrink-0 ${tone[a.kind][1]}">${a.icon}</span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold leading-snug">${esc(a.title)}</p>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 break-words">${esc(a.body)}</p>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-outline ml-auto shrink-0">chevron_right</span>
                        </a>
                    `).join('');
                    }

                    /* Donut safety: recompute on resize/rotation so the chart
                       never renders broken on phones. */
                    (function() {
                        var t = null;
                        window.addEventListener('resize', function() {
                            clearTimeout(t);
                            t = setTimeout(function() {
                                var p = document.getElementById('donut-present-path');
                                if (p && typeof loadAttendanceOverview === 'function') {
                                    try {
                                        loadAttendanceOverview();
                                    } catch (e) {
                                        /* noop */ }
                                }
                            }, 250);
                        });
                    })();

                    async function loadClassesFeed() {
                        const container = document.getElementById('classes-container');
                        if (!container) return;
                        try {
                            // Use server-side enrolled schedule API so the section
                            // context is handled correctly (not just the raw classes table).
                            const res = await fetch('/api/student.php?action=get_enrolled_schedule', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const data = await res.json();

                            if (data.success && data.classes && data.classes.length > 0) {
                                // Day-name map (PH academic schedule format)
                                const dayMap = {
                                    'sunday': 0,
                                    'monday': 1,
                                    'tuesday': 2,
                                    'wednesday': 3,
                                    'thursday': 4,
                                    'friday': 5,
                                    'saturday': 6
                                };
                                const today = new Date();
                                const todayIdx = today.getDay();
                                // Build full set of day keys the student has classes on today
                                const todayClasses = data.classes.filter(c => {
                                    const sd = String(c.schedule_day || '').toLowerCase().trim();
                                    if (!sd || sd === 'tba') return false;
                                    return sd.split(/[,\/]/).some(part => dayMap[part.trim()] === todayIdx);
                                });

                                // Sort by start_time
                                todayClasses.sort((a, b) => {
                                    const ta = parseTimeToMinutes(a.start_time);
                                    const tb = parseTimeToMinutes(b.start_time);
                                    return ta - tb;
                                });

                                // Time-aware state per class
                                const nowMin = today.getHours() * 60 + today.getMinutes();
                                const computed = todayClasses.map(c => {
                                    const startMin = parseTimeToMinutes(c.start_time);
                                    const endMin = parseTimeToMinutes(c.end_time);
                                    let tag = 'upcoming';
                                    let tagLabel = '⏰ Upcoming';
                                    if (nowMin >= startMin && nowMin < endMin) {
                                        tag = 'live';
                                        tagLabel = '🔴 LIVE NOW';
                                    } else if (nowMin >= endMin) {
                                        tag = 'passed';
                                        tagLabel = '✓ Passed';
                                    }
                                    return {
                                        ...c,
                                        tag,
                                        tagLabel,
                                        startMin
                                    };
                                }).sort((a, b) => a.startMin - b.startMin);

                                // The "next" class = first one that hasn't ended yet
                                const nextIdx = computed.findIndex(c => c.tag === 'live' || c.tag === 'upcoming');
                                const nextClass = nextIdx >= 0 ? computed[nextIdx] : null;

                                // Inject a live "Next Class" ticker above the list if there is one
                                const nextTicker = document.getElementById('next-class-ticker');
                                if (nextTicker) {
                                    if (nextClass) {
                                        nextTicker.classList.remove('hidden');
                                        document.getElementById('next-subject').textContent = nextClass.title || nextClass.code;
                                        document.getElementById('next-time').textContent = `${nextClass.start_time || 'TBA'}${nextClass.end_time ? ' - ' + nextClass.end_time : ''}`;
                                        document.getElementById('next-room').textContent = nextClass.room || 'TBA';
                                        document.getElementById('next-teacher').textContent = nextClass.instructor || 'Faculty';
                                    } else {
                                        nextTicker.classList.add('hidden');
                                    }
                                }

                                if (!computed.length) {
                                    /* No classes today — friendly empty state */
                                    if (nextTicker) nextTicker.classList.add('hidden');
                                    container.innerHTML = '<div class="p-8 text-center flex flex-col items-center gap-2">' +
                                        '<span class="material-symbols-outlined text-[40px] text-outline-variant">event_available</span>' +
                                        '<p class="text-sm font-semibold text-on-surface">No classes today 🎉</p>' +
                                        '<p class="text-xs text-on-surface-variant">' + escapeHtml(data.section || 'Your section') + ' &bull; ' + data.classes.length + ' subjects enrolled this term</p></div>';
                                    return;
                                }

                                container.innerHTML = computed.map((c, i) => `
                            <div class="${i === nextIdx ? 'ring-2 ring-secondary animate-subtle-pulse bg-secondary/5' : ''} p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-container-low/50 transition-colors">
                                <div class="flex items-start gap-3.5 sm:gap-4">
                                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-surface-container flex flex-col items-center justify-center text-primary border border-outline-variant shrink-0 font-bold">
                                        <span class="font-mono text-xs leading-tight">${escapeHtml(c.code)}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h4 class="text-sm sm:text-base font-semibold text-on-surface break-words">${escapeHtml(c.title)}</h4>
                                            <span class="px-2 py-0.5 rounded-full bg-status-info/10 text-status-info font-mono text-[10px] uppercase font-bold border border-status-info/20">${escapeHtml(c.section || '01')}</span>
                                            ${i === nextIdx ? `<span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-mono text-[10px] uppercase font-bold border border-secondary-container/30 flex items-center gap-1">${c.tag === 'live' ? '🔴 LIVE' : '⏰ NEXT'}</span>` : ''}
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-on-surface-variant">
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> ${escapeHtml(c.instructor || 'Faculty')}</span>
                                            <span class="hidden sm:inline w-1 h-1 rounded-full bg-outline-variant"></span>
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">meeting_room</span> ${escapeHtml(c.room || 'TBA')}</span>
                                            <span class="hidden sm:inline w-1 h-1 rounded-full bg-outline-variant"></span>
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> ${escapeHtml(c.schedule_day || 'TBA')}: ${c.start_time || 'TBA'} - ${c.end_time || 'TBA'}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-full ${c.tag === 'live' ? 'bg-error/10 text-error animate-pulse' : c.tag === 'upcoming' ? 'bg-status-info/10 text-status-info' : 'bg-outline-variant/20 text-on-surface-variant'}">
                                    ${c.tagLabel}
                                </span>
                            </div>
                            `).join('');
                            } else {
                                container.innerHTML = '<div class="p-5 text-center text-on-surface-variant text-sm">No scheduled classes found.</div>';
                            }
                        } catch (err) {
                            console.error('Failed to load classes', err);
                            container.innerHTML = '<div class="p-5 text-center text-on-surface-variant text-sm">Failed to load schedule.</div>';
                        }
                    }

                    function parseTimeToMinutes(t) {
                        if (!t) return 9999;
                        const m = String(t).trim().match(/(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)?/i);
                        if (!m) return String(t).toLowerCase().includes('tba') ? 9999 : 0;
                        let h = parseInt(m[1], 10);
                        const min = parseInt(m[2], 10);
                        const ampm = (m[3] || '').toUpperCase();
                        if (ampm === 'PM' && h < 12) h += 12;
                        if (ampm === 'AM' && h === 12) h = 0;
                        return h * 60 + min;
                    }

                    async function loadAttendanceOverview() {
                        try {
                            const res = await fetch('/api/student.php?action=get_attendance_history', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const d = await res.json();

                            let present = 0,
                                late = 0,
                                absent = 0,
                                total = 0,
                                rate = 100;
                            if (d && d.success && d.stats) {
                                present = parseInt(d.stats.present || 0, 10);
                                late = parseInt(d.stats.late || 0, 10);
                                absent = parseInt(d.stats.absent || 0, 10);
                                total = parseInt(d.stats.total_checkins || 0, 10);
                                if (!total) total = present + late + absent;
                                rate = total > 0 ? parseFloat(d.stats.rate) || 0 : 100;
                            }

                            const presentPct = total > 0 ? Math.round((present / total) * 100) : 100;
                            const latePct = total > 0 ? Math.round((late / total) * 100) : 0;
                            const absentPct = total > 0 ? Math.round((absent / total) * 100) : 0;

                            const rateDisplay = document.getElementById('attendance-rate-display');
                            if (rateDisplay) rateDisplay.textContent = (total > 0 ? Math.round(rate) : 100) + '%';

                            const pStat = document.getElementById('attendance-present-stat');
                            if (pStat) pStat.textContent = presentPct + '%';
                            const pCount = document.getElementById('attendance-present-count');
                            if (pCount) pCount.textContent = present + ' scans';

                            const lStat = document.getElementById('attendance-late-stat');
                            if (lStat) lStat.textContent = latePct + '%';
                            const lCount = document.getElementById('attendance-late-count');
                            if (lCount) lCount.textContent = late + ' scans';

                            const aStat = document.getElementById('attendance-absent-stat');
                            if (aStat) aStat.textContent = absentPct + '%';
                            const aCount = document.getElementById('attendance-absent-count');
                            if (aCount) aCount.textContent = absent + ' scans';

                            // Animate SVG Donut Paths
                            const pPath = document.getElementById('donut-present-path');
                            if (pPath) pPath.setAttribute('stroke-dasharray', `${presentPct}, 100`);

                            const lPath = document.getElementById('donut-late-path');
                            if (lPath) {
                                lPath.setAttribute('stroke-dasharray', `${latePct}, 100`);
                                lPath.setAttribute('stroke-dashoffset', `-${presentPct}`);
                            }

                            const aPath = document.getElementById('donut-absent-path');
                            if (aPath) {
                                aPath.setAttribute('stroke-dasharray', `${absentPct}, 100`);
                                aPath.setAttribute('stroke-dashoffset', `-${presentPct + latePct}`);
                            }
                        } catch (e) {
                            console.warn('Attendance stats using default fallback:', e);
                        }
                    }

                    function renderAnnouncementBody(raw) {
                        if (!raw) return '<p class="text-on-surface-variant/70 italic text-xs">No announcement content.</p>';
                        let text = String(raw).trim();

                        // Strip browser extension artifacts
                        text = text.replace(/\s*bis_skin_checked="[^"]*"/gi, '');
                        text = text.replace(/\s*contenteditable="[^"]*"/gi, '');
                        text = text.replace(/\s*spellcheck="[^"]*"/gi, '');

                        // Check if content has HTML markup
                        if (/<(div|p|span|ul|ol|li|h[1-6]|strong|b|em|i|blockquote|table|br)/i.test(text)) {
                            // Strip risky tags
                            text = text.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                            text = text.replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '');
                            text = text.replace(/on\w+="[^"]*"/gi, '');

                            // Normalize alert boxes from editor to theme-aware classes
                            text = text.replace(/style="[^"]*background-color:\s*#fee2e2[^"]*"/gi, 'class="p-3 mb-2 rounded-xl bg-error/15 border-l-4 border-error text-on-surface text-xs"');
                            text = text.replace(/style="[^"]*background-color:\s*#eff4ff[^"]*"/gi, 'class="p-3 mb-2 rounded-xl bg-primary/15 border-l-4 border-primary text-on-surface text-xs"');
                            text = text.replace(/style="[^"]*background-color:\s*#fef3c7[^"]*"/gi, 'class="p-3 mb-2 rounded-xl bg-secondary-container/30 border-l-4 border-secondary text-on-surface text-xs"');

                            // Style lists cleanly
                            text = text.replace(/<ul\b([^>]*)>/gi, '<ul class="list-disc ml-5 my-1 space-y-1 text-xs" $1>');
                            text = text.replace(/<ol\b([^>]*)>/gi, '<ol class="list-decimal ml-5 my-1 space-y-1 text-xs" $1>');

                            return `<div class="announcement-content text-xs leading-relaxed break-words" style="overflow-wrap:anywhere; word-break:break-word;">${text}</div>`;
                        }

                        // Plain text or markdown formatting
                        const escaped = escapeHtml(text);
                        const formatted = escaped
                            .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-primary">$1</strong>')
                            .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
                            .replace(/&lt;u&gt;(.*?)&lt;\/u&gt;/gi, '<u class="underline">$1</u>')
                            .replace(/• (.*?)(\n|$)/g, '<li class="ml-4 list-disc">$1</li>')
                            .replace(/\n/g, '<br>');

                        return `<div class="announcement-content text-xs leading-relaxed break-words" style="overflow-wrap:anywhere; word-break:break-word;">${formatted}</div>`;
                    }

                    /* Announcement cards grow with their text. Very long posts
                       start collapsed (~12 lines) with a smooth Read-more. */
                    function buildAnnouncementCard(a) {
                        /* Box height always matches the text exactly — no clamping */
                        return `<div class="announcement-body text-xs leading-relaxed">${renderAnnouncementBody(a.body)}</div>`;
                    }

                    async function loadAnnouncementsFeed() {
                        const container = document.getElementById('announcements-container');
                        if (!container || !supabaseClient) return;
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
                                .limit(8);

                            if (error) throw error;

                            if (data && data.length > 0) {
                                container.innerHTML = data.map((a, idx) => {
                                    const isEmergency = a.category === 'emergency';
                                    const isAcademic = a.category === 'academic';
                                    const badgeClass = isEmergency ?
                                        'bg-error-container text-error' :
                                        (isAcademic ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-primary');
                                    const icon = isEmergency ? 'warning' : (isAcademic ? 'school' : 'campaign');
                                    const dateStr = new Date(a.created_at).toLocaleDateString();

                                    return `
                                <div class="p-3.5 bg-surface-container-low/40 rounded-xl border border-outline-variant/40 hover:bg-surface-container-low transition-colors space-y-2 overflow-hidden">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full ${badgeClass} font-mono text-[10px] uppercase font-bold tracking-wide shrink-0">
                                            <span class="material-symbols-outlined text-[12px]">${icon}</span> ${escapeHtml(a.category)}
                                        </span>
                                        <span class="font-mono text-[10px] text-on-surface-variant shrink-0">${dateStr}</span>
                                    </div>
                                    <h4 class="text-sm font-bold text-primary break-words leading-snug" style="overflow-wrap:anywhere; word-break:break-word;">${escapeHtml(a.title)}</h4>
                                    ${buildAnnouncementCard(a)}
                                </div>
                                `;
                                }).join('');
                            } else {
                                container.innerHTML = '<div class="text-center text-on-surface-variant text-sm py-4">No recent announcements.</div>';
                            }
                        } catch (err) {
                            console.error('Failed to load announcements', err);
                            container.innerHTML = '<div class="text-center text-error text-sm py-4">Failed to load announcements.</div>';
                        }
                    }
                </script>

                <!-- 3. NPC AI ASSISTANT CHAT VIEW -->
                <div id="chatbot-view" class="view">
                    <div class="h-[calc(100vh-8rem)] flex overflow-hidden bg-surface border border-outline-variant rounded-2xl shadow-sm">
                        <!-- Conversation History Sidebar -->
                        <aside class="hidden md:flex flex-col w-72 bg-surface-subtle border-r border-outline-variant">
                            <div class="p-4 border-b border-outline-variant">
                                <button class="w-full flex items-center justify-center gap-2 bg-primary text-on-primary py-2.5 px-4 rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-sm">add</span>
                                    <span class="font-mono text-sm font-semibold">New Conversation</span>
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                <div class="px-3 py-2 text-on-surface-variant font-mono text-xs font-semibold opacity-70 mt-2">Today</div>
                                <button class="w-full text-left px-3 py-2.5 rounded-lg bg-surface-container text-on-surface flex items-center gap-3 border border-outline-variant/50">
                                    <span class="material-symbols-outlined text-outline">chat_bubble</span>
                                    <span class="text-base truncate">Enrollment Deadlines</span>
                                </button>
                                <button class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-surface-container-low text-on-surface-variant flex items-center gap-3 transition-colors">
                                    <span class="material-symbols-outlined text-outline-variant">chat_bubble</span>
                                    <span class="text-base truncate">Degree Audit Help</span>
                                </button>
                                <div class="px-3 py-2 text-on-surface-variant font-mono text-xs font-semibold opacity-70 mt-4">Previous 7 Days</div>
                                <button class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-surface-container-low text-on-surface-variant flex items-center gap-3 transition-colors">
                                    <span class="material-symbols-outlined text-outline-variant">chat_bubble</span>
                                    <span class="text-base truncate">Library Access Hours</span>
                                </button>
                                <button class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-surface-container-low text-on-surface-variant flex items-center gap-3 transition-colors">
                                    <span class="material-symbols-outlined text-outline-variant">chat_bubble</span>
                                    <span class="text-base truncate">Campus Wi-Fi Setup</span>
                                </button>
                            </div>
                        </aside>

                        <!-- Active Chat Canvas -->
                        <section class="flex-1 flex flex-col bg-surface relative">
                            <!-- Chat Header -->
                            <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-lowest flex justify-between items-center shadow-sm z-10">
                                <div>
                                    <h2 class="text-2xl font-bold text-on-surface">NPC AI Assistant</h2>
                                    <p class="font-mono text-sm font-semibold text-on-surface-variant">Your official student information assistant</p>
                                </div>
                                <!-- Safety UI Indicator -->
                                <div class="flex items-center gap-2 bg-surface-container-high px-3 py-1.5 rounded-full border border-outline-variant">
                                    <span class="material-symbols-outlined text-status-info" style="font-size: 18px;">shield</span>
                                    <span class="font-mono text-xs font-semibold text-on-surface-variant">Warning Count: <span class="font-bold">0/3</span></span>
                                </div>
                            </div>

                            <!-- Chat Messages Area -->
                            <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-background">
                                <!-- Intro Message -->
                                <div class="flex justify-center my-4">
                                    <div class="bg-surface-container-low px-4 py-2 rounded-full border border-outline-variant text-on-surface-variant font-mono text-xs font-semibold">
                                        Conversation started automatically
                                    </div>
                                </div>

                                <!-- Initial AI Message -->
                                <div class="flex flex-col items-start w-full">
                                    <div class="flex gap-3 max-w-[90%] md:max-w-[80%]">
                                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-sm">
                                            <span class="material-symbols-outlined text-on-primary" style="font-size: 18px;">smart_toy</span>
                                        </div>
                                        <div class="bg-surface-container-lowest border border-outline-variant p-4 rounded-xl rounded-tl-none shadow-sm flex flex-col gap-3">
                                            <div class="text-base text-on-surface space-y-3">
                                                <p>Hello! I am your NPC academic assistant. You can ask me any question regarding campus policies, course requirements, or administrative documents in the knowledge base.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="font-mono text-xs font-semibold text-on-surface-variant mt-1 ml-11">Now</span>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <div class="p-6 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <div class="max-w-4xl mx-auto flex items-end gap-2 bg-surface-subtle border border-outline-variant rounded-2xl focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden pr-2 pl-4 py-2 shadow-inner transition-shadow">
                                    <textarea id="chat-input" class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 resize-none text-base text-on-surface py-2 max-h-32 overflow-y-auto" placeholder="Ask about academic policies, campus services, or your records..." rows="1" style="min-height: 40px;"></textarea>
                                    <div class="flex items-center gap-1 pb-1">
                                        <button aria-label="Attach file" class="text-on-surface-variant p-2 hover:bg-surface-container-high hover:text-primary rounded-full transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined">attach_file</span>
                                        </button>
                                        <button id="chat-send-btn" aria-label="Send message" onclick="sendMessage()" class="text-white bg-primary p-2 hover:bg-primary/90 rounded-full transition-colors flex items-center justify-center shadow-md cursor-pointer">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <p class="font-mono text-xs font-semibold text-on-surface-variant/70">AI Assistant may produce inaccurate information. Always verify against official documents.</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- 5. ATTENDANCE KIOSK VIEW -->
                <div id="kiosk-view" class="view">
                    <div class="flex flex-col items-center justify-center min-h-[70vh]">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-8 md:p-10 text-center max-w-lg w-full shadow-sm">
                            <div class="w-16 h-16 rounded-2xl bg-surface-container-low flex items-center justify-center mx-auto mb-6 text-primary border border-outline-variant">
                                <span class="material-symbols-outlined text-[36px]">fact_check</span>
                            </div>
                            <h1 class="text-2xl font-bold text-primary mb-2">Attendance Check-In</h1>
                            <p class="text-sm text-on-surface-variant mb-6">Enter your Student ID number or scan your badge below.</p>

                            <div class="mb-6">
                                <label class="block mb-2 font-mono text-xs text-on-surface uppercase font-semibold">Student ID Number</label>
                                <input type="text" id="kiosk-id" class="w-full text-center text-2xl font-mono font-bold p-4 bg-surface border border-outline-variant rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 tracking-wider"
                                    placeholder="e.g. 251505">
                            </div>

                            <button class="w-full bg-primary hover:bg-primary-container text-white font-semibold py-4 px-6 rounded-xl transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer active:scale-98" onclick="checkIn()">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                <span>Confirm Attendance</span>
                            </button>

                            <div id="kiosk-status" class="mt-6 font-mono text-sm"></div>
                        </div>
                    </div>
                </div>

                <!-- 6. ACADEMIC VIEW -->
                <div id="academic-view" class="view">
                    <div class="flex flex-col gap-6 lg:gap-8">
                        <!-- Page Header & Student Summary -->
                        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-outline-variant pb-6">
                            <div>
                                <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-2">Academic Performance</h1>
                                <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">Track your grades, view academic standing, and monitor your progress towards graduation.</p>
                            </div>
                            <div class="flex items-center gap-4 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shrink-0">
                                <div class="w-12 h-12 rounded-full bg-primary-container overflow-hidden flex items-center justify-center">
                                    <span class="text-on-primary text-lg font-bold"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                                </div>
                                <div>
                                    <h2 class="font-headline-md text-headline-md text-on-surface"><?= htmlspecialchars($_SESSION['name']) ?></h2>
                                    <div class="flex items-center gap-3 font-label-sm text-label-sm text-on-surface-variant mt-1">
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">badge</span> <?= htmlspecialchars($user_id_display) ?></span>
                                        <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">computer</span> —</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Overview (Bento Grid) -->
                        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Sem GPA -->
                            <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant hover:shadow-md transition-shadow duration-300 flex flex-col justify-between relative overflow-hidden group">
                                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <span class="material-symbols-outlined text-[64px]">trending_up</span>
                                </div>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-1">Semester GPA</p>
                                    <h3 class="font-display-lg text-display-lg text-primary">1.25</h3>
                                </div>
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-surface-container text-on-primary-container font-label-sm text-label-sm gap-1">
                                        <span class="material-symbols-outlined text-[14px]">arrow_upward</span> Excellent
                                    </span>
                                </div>
                            </div>
                            <!-- Cumul GPA -->
                            <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant hover:shadow-md transition-shadow duration-300 flex flex-col justify-between relative overflow-hidden group">
                                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <span class="material-symbols-outlined text-[64px]">account_balance</span>
                                </div>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-1">Cumulative GPA</p>
                                    <h3 class="font-display-lg text-display-lg text-primary">1.38</h3>
                                </div>
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">Top 5% of Cohort</span>
                                </div>
                            </div>
                            <!-- Standing -->
                            <div class="npc-navy-card text-white rounded-xl p-6 shadow-md flex flex-col justify-between relative overflow-hidden">
                                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-on-primary-fixed-variant/20 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                    <p class="font-label-sm text-label-sm text-blue-200 uppercase tracking-widest mb-2">Academic Standing</p>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="material-symbols-outlined text-[32px] text-secondary-container fill">workspace_premium</span>
                                        <h3 class="font-headline-lg text-headline-lg text-white">Dean's Lister</h3>
                                    </div>
                                </div>
                                <div class="relative z-10 mt-4">
                                    <p class="font-label-sm text-label-sm text-blue-100/90">Eligible for merit scholarship renewal next semester.</p>
                                </div>
                            </div>
                        </section>

                        <!-- Main Content Area: Table & Trends Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start">
                            <!-- Grade Report Section (Span 2 cols on lg) -->
                            <div class="lg:col-span-2 flex flex-col gap-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <h3 class="font-headline-md text-headline-md text-primary">Grade Report</h3>
                                    <!-- Semester Selector -->
                                    <div class="relative min-w-[240px]">
                                        <select class="appearance-none w-full bg-surface-container-lowest border border-outline-variant text-on-surface font-label-md text-label-md py-2.5 pl-4 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary cursor-pointer hover:bg-surface-container-low transition-colors shadow-sm">
                                            <option>1st Semester, 2026-2027</option>
                                            <option>2nd Semester, 2025-2026</option>
                                            <option>1st Semester, 2025-2026</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                                    </div>
                                </div>
                                <!-- Detailed Table -->
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse min-w-[600px]">
                                            <thead>
                                                <tr class="bg-surface-container text-primary font-label-sm text-label-sm uppercase tracking-wider font-bold">
                                                    <th class="p-4 py-3 font-semibold border-b border-outline-variant/60">Subject Code</th>
                                                    <th class="p-4 py-3 font-semibold border-b border-outline-variant/60">Description</th>
                                                    <th class="p-4 py-3 font-semibold border-b border-outline-variant/60 text-center">Units</th>
                                                    <th class="p-4 py-3 font-semibold border-b border-outline-variant/60 text-center">Grade</th>
                                                    <th class="p-4 py-3 font-semibold border-b border-outline-variant/60 text-right">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="font-body-md text-body-md">
                                                <tr class="border-b border-surface-container-high hover:bg-surface-container-low/50 transition-colors">
                                                    <td class="p-4 font-label-md text-label-md text-on-surface-variant">CS311</td>
                                                    <td class="p-4 text-on-surface font-medium">Data Structures &amp; Algorithms</td>
                                                    <td class="p-4 text-center text-on-surface-variant">3.0</td>
                                                    <td class="p-4 text-center font-bold text-primary">1.25</td>
                                                    <td class="p-4 text-right">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-surface-container text-primary font-label-sm text-label-sm">Passed</span>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-surface-container-high hover:bg-surface-container-low/50 transition-colors bg-surface-subtle">
                                                    <td class="p-4 font-label-md text-label-md text-on-surface-variant">IT302</td>
                                                    <td class="p-4 text-on-surface font-medium">Web Systems and Technologies</td>
                                                    <td class="p-4 text-center text-on-surface-variant">3.0</td>
                                                    <td class="p-4 text-center font-bold text-primary">1.50</td>
                                                    <td class="p-4 text-right">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-surface-container text-primary font-label-sm text-label-sm">Passed</span>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-surface-container-high hover:bg-surface-container-low/50 transition-colors">
                                                    <td class="p-4 font-label-md text-label-md text-on-surface-variant">MATH205</td>
                                                    <td class="p-4 text-on-surface font-medium">Discrete Mathematics</td>
                                                    <td class="p-4 text-center text-on-surface-variant">3.0</td>
                                                    <td class="p-4 text-center font-bold text-primary">1.25</td>
                                                    <td class="p-4 text-right">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-surface-container text-primary font-label-sm text-label-sm">Passed</span>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-surface-container-high hover:bg-surface-container-low/50 transition-colors bg-surface-subtle">
                                                    <td class="p-4 font-label-md text-label-md text-on-surface-variant">HUM102</td>
                                                    <td class="p-4 text-on-surface font-medium">Professional Ethics</td>
                                                    <td class="p-4 text-center text-on-surface-variant">3.0</td>
                                                    <td class="p-4 text-center font-bold text-secondary-container bg-secondary/10 rounded">1.00</td>
                                                    <td class="p-4 text-right">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-secondary-container/20 text-on-secondary-container border border-secondary-container font-label-sm text-label-sm">Exceptional</span>
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                                    <td class="p-4 font-label-md text-label-md text-on-surface-variant">CS312</td>
                                                    <td class="p-4 text-on-surface font-medium">Software Engineering I</td>
                                                    <td class="p-4 text-center text-on-surface-variant">3.0</td>
                                                    <td class="p-4 text-center font-bold text-outline-variant">-</td>
                                                    <td class="p-4 text-right">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-surface-variant text-on-surface-variant font-label-sm text-label-sm">Ongoing</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="bg-surface-container-low p-4 border-t border-outline-variant flex justify-between items-center">
                                        <span class="font-label-md text-label-md text-on-surface-variant">Total Units Enrolled: <strong class="text-on-surface">15.0</strong></span>
                                        <button class="text-primary font-label-md text-label-md hover:underline flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[18px]">download</span> Download PDF
                                        </button>
                                    </div>

                                </div>
                            </div>
                            <!-- Performance Trend Sidebar -->
                            <div class="flex flex-col gap-4">
                                <h3 class="font-headline-md text-headline-md text-primary">Academic History</h3>
                                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm flex flex-col gap-6">
                                    <!-- Simulated Chart/Trend -->
                                    <div class="flex flex-col gap-1">
                                        <div class="flex justify-between items-end mb-2 border-b border-outline-variant pb-2">
                                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase">Semester</span>
                                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase">GPA</span>
                                        </div>
                                        <!-- Trend Items -->
                                        <div class="flex items-center gap-4 group">
                                            <div class="w-16 font-label-md text-label-md text-on-surface-variant">Y3 S1</div>
                                            <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden flex items-center relative">
                                                <div class="absolute h-full bg-primary rounded-full" style="width: 85%;"></div>
                                            </div>
                                            <div class="w-10 text-right font-headline-md text-headline-md text-primary">1.25</div>
                                        </div>
                                        <div class="flex items-center gap-4 group mt-2">
                                            <div class="w-16 font-label-md text-label-md text-on-surface-variant">Y2 S2</div>
                                            <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden flex items-center relative">
                                                <div class="absolute h-full bg-primary-container rounded-full" style="width: 75%;"></div>
                                            </div>
                                            <div class="w-10 text-right font-headline-md text-headline-md text-on-surface">1.40</div>
                                        </div>
                                        <div class="flex items-center gap-4 group mt-2">
                                            <div class="w-16 font-label-md text-label-md text-on-surface-variant">Y2 S1</div>
                                            <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden flex items-center relative">
                                                <div class="absolute h-full bg-primary-container rounded-full" style="width: 70%;"></div>
                                            </div>
                                            <div class="w-10 text-right font-headline-md text-headline-md text-on-surface">1.55</div>
                                        </div>
                                        <div class="flex items-center gap-4 group mt-2">
                                            <div class="w-16 font-label-md text-label-md text-on-surface-variant">Y1 S2</div>
                                            <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden flex items-center relative">
                                                <div class="absolute h-full bg-primary-container rounded-full" style="width: 80%;"></div>
                                            </div>
                                            <div class="w-10 text-right font-headline-md text-headline-md text-on-surface">1.35</div>
                                        </div>
                                    </div>
                                    <!-- Info Alert -->
                                    <div class="bg-surface-container-low border border-surface-variant rounded-lg p-4 flex gap-3 mt-2">
                                        <span class="material-symbols-outlined text-status-info">info</span>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant leading-relaxed">
                                            Your current trajectory indicates a high probability of graduating with <strong class="text-on-surface">Magna Cum Laude</strong> honors. Keep up the excellent work.
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating GWA Calculator -->
                <button id="npc-gwa-fab" data-tip="GWA Calculator"
                    class="no-print fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full npc-navy-card text-white shadow-lg flex items-center justify-center ripple press hover:scale-105 transition-transform cursor-pointer"
                    aria-label="Open GWA calculator">
                    <span class="material-symbols-outlined text-[24px] text-white">calculate</span>
                </button>

            </main>
        </div>
    </div>

    <script>
        /* ── NPC dashboard enhancements v2 ───────────────────── */
        (function() {
            // Live clock
            var clockEl = document.getElementById('npc-live-clock');
            if (clockEl) {
                setInterval(function() {
                    clockEl.textContent = new Date().toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }, 1000);
            }

            // Focus mode — dims sidebar/topbar chrome for distraction-free reading
            var focusBtn = document.getElementById('npc-focus-toggle');
            if (focusBtn) {
                var focused = false;
                focusBtn.addEventListener('click', function() {
                    focused = !focused;
                    document.documentElement.classList.toggle('npc-focus-mode', focused);
                    focusBtn.querySelector('.material-symbols-outlined').textContent = focused ? 'center_focus_weak' : 'center_focus_strong';
                    if (window.notify) window.notify(focused ? 'Focus mode ON — chrome dimmed.' : 'Focus mode OFF.', 'info', 2200);
                });
            }

            /* Notifications: handled by the universal npc.js center */
        })();
    </script>

    <script>
        /* ═══ GWA CALCULATOR — student feature ═══ */
        (function() {
            var fab = document.getElementById('npc-gwa-fab');
            if (!fab) return;

            var open = false;

            function escClose(e) {
                if (e.key === 'Escape') closeAll();
            }

            function closeAll() {
                var m = document.getElementById('npc-gwa-modal');
                if (m) m.remove();
                document.removeEventListener('keydown', escClose);
                open = false;
            }

            fab.addEventListener('click', function() {
                if (!open) buildModal();
            });

            function buildModal() {
                open = true;
                var m = document.createElement('div');
                m.id = 'npc-gwa-modal';
                m.className = 'npc-modal-backdrop';
                m.innerHTML =
                    '<div class="npc-modal-card max-w-lg" role="dialog" aria-label="GWA Calculator">' +
                    '  <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-subtle">' +
                    '    <h3 class="font-bold text-primary flex items-center gap-2"><span class="material-symbols-outlined">calculate</span> GWA Calculator</h3>' +
                    '    <button id="npc-gwa-close" class="p-1.5 rounded-full hover:bg-surface-container transition-colors cursor-pointer"><span class="material-symbols-outlined text-[20px]">close</span></button>' +
                    '  </div>' +
                    '  <div class="p-6 overflow-y-auto flex flex-col gap-4 text-sm">' +
                    '    <p class="text-xs text-on-surface-variant">Enter final grades per subject on the Philippine 5-point scale (lower is better). Untick a row to exclude it.</p>' +
                    '    <div class="overflow-x-auto -mx-1 px-1"><table class="w-full text-left"><thead><tr class="font-mono text-[10px] uppercase text-on-surface-variant tracking-wider">' +
                    '      <th class="pb-2">Subject</th><th class="pb-2 text-center">Final Grade</th><th class="pb-2 text-center">Units</th><th class="pb-2 text-center">Include</th><th></th>' +
                    '    </tr></thead><tbody id="npc-gwa-body"></tbody></table></div>' +
                    '    <button id="npc-gwa-addrow" class="self-start inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline cursor-pointer"><span class="material-symbols-outlined text-[16px]">add_circle</span> Add subject</button>' +
                    '    <div class="rounded-2xl p-5 text-center" style="background:linear-gradient(135deg, rgb(var(--primary-rgb)/.08), rgb(var(--secondary-rgb)/.10));border:1px solid rgb(var(--outline-variant-rgb));">' +
                    '      <p class="font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Your General Weighted Average</p>' +
                    '      <p id="npc-gwa-value" class="text-5xl font-extrabold text-primary tabular-nums">—</p>' +
                    '      <p id="npc-gwa-honors" class="text-xs font-semibold mt-2 text-on-surface-variant">Enter grades to compute</p>' +
                    '    </div>' +
                    '  </div>' +
                    '  <div class="px-6 py-4 border-t border-outline-variant bg-surface-subtle flex justify-between items-center">' +
                    '    <button id="npc-gwa-reset" class="text-xs font-semibold text-error hover:underline cursor-pointer">Reset rows</button>' +
                    '    <button id="npc-gwa-done" class="npc-navy-card text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 press cursor-pointer ripple">Done</button>' +
                    '  </div>' +
                    '</div>';
                document.body.appendChild(m);

                var tbody = m.querySelector('#npc-gwa-body');
                var valEl = m.querySelector('#npc-gwa-value');
                var honorsEl = m.querySelector('#npc-gwa-honors');

                function addRow() {
                    var tr = document.createElement('tr');
                    tr.className = 'npc-gwa-row border-b border-outline-variant/40';
                    tr.innerHTML =
                        '<td class="px-2 py-1.5"><input class="npc-gwa-code w-full bg-surface-container-low border border-outline-variant rounded-lg px-2 py-1.5 text-xs font-mono" placeholder="e.g. CS311"></td>' +
                        '<td class="px-2 py-1.5"><input type="number" step="0.01" min="1" max="5" class="npc-gwa-grade w-full bg-surface-container-low border border-outline-variant rounded-lg px-2 py-1.5 text-xs font-mono text-center" placeholder="1.25"></td>' +
                        '<td class="px-2 py-1.5"><input type="number" step="0.5" min="0.5" max="12" class="npc-gwa-units w-full bg-surface-container-low border border-outline-variant rounded-lg px-2 py-1.5 text-xs font-mono text-center" placeholder="3.0"></td>' +
                        '<td class="px-2 py-1.5 text-center"><input type="checkbox" class="npc-gwa-inc w-4 h-4 accent-blue-600 cursor-pointer" checked></td>' +
                        '<td class="px-2 py-1.5 text-right"><button type="button" class="npc-gwa-del p-1 rounded hover:bg-error/10 text-error cursor-pointer opacity-60 hover:opacity-100 transition-opacity" title="Remove"><span class="material-symbols-outlined text-[16px]">delete</span></button></td>';
                    tbody.appendChild(tr);
                    tr.querySelector('.npc-gwa-del').addEventListener('click', function() {
                        tr.remove();
                        compute();
                    });
                }

                function compute() {
                    var sum = 0,
                        units = 0;
                    tbody.querySelectorAll('.npc-gwa-row').forEach(function(tr) {
                        if (!tr.querySelector('.npc-gwa-inc').checked) return;
                        var g = parseFloat(tr.querySelector('.npc-gwa-grade').value);
                        var u = parseFloat(tr.querySelector('.npc-gwa-units').value);
                        if (isNaN(g) || isNaN(u)) return;
                        sum += g * u;
                        units += u;
                    });
                    if (units <= 0) {
                        valEl.textContent = '—';
                        honorsEl.textContent = 'Enter grades to compute';
                        honorsEl.className = 'text-xs font-semibold mt-2 text-on-surface-variant';
                        return;
                    }
                    var gwa = sum / units;
                    valEl.textContent = gwa.toFixed(2);
                    var msg, cls;
                    if (gwa <= 1.20) {
                        msg = 'Summa cum laude trajectory! Outstanding.';
                        cls = 'text-status-success';
                    } else if (gwa <= 1.45) {
                        msg = 'Magna cum laude trajectory — excellent!';
                        cls = 'text-status-success';
                    } else if (gwa <= 1.75) {
                        msg = 'Cum laude territory — keep it up!';
                        cls = 'text-primary';
                    } else if (gwa <= 3.00) {
                        msg = 'Passing — steady progress.';
                        cls = 'text-on-surface-variant';
                    } else {
                        msg = 'Below passing line — consult your adviser.';
                        cls = 'text-error';
                    }
                    honorsEl.textContent = msg;
                    honorsEl.className = 'text-xs font-semibold mt-2 ' + cls;
                    if (gwa <= 1.45 && !compute.__celebrated) {
                        compute.__celebrated = true;
                        if (window.npcConfetti) window.npcConfetti.burst(90);
                    }
                }

                m.addEventListener('input', compute);
                m.addEventListener('change', compute);
                m.querySelector('#npc-gwa-addrow').addEventListener('click', addRow);
                m.querySelector('#npc-gwa-close').addEventListener('click', closeAll);
                m.querySelector('#npc-gwa-done').addEventListener('click', closeAll);
                m.querySelector('#npc-gwa-reset').addEventListener('click', function() {
                    tbody.innerHTML = '';
                    for (var i = 0; i < 5; i++) addRow();
                    compute();
                });
                m.addEventListener('click', function(e) {
                    if (e.target === m) closeAll();
                });
                document.addEventListener('keydown', escClose);

                for (var i = 0; i < 5; i++) addRow();
            }
        })();

        // Real-time Live Class Alert on Student Dashboard
        var LAST_DASHBOARD_LIVE_KEY = '';
        async function checkStudentDashboardLiveClass() {
            var banner = document.getElementById('student-dash-live-banner');
            if (!banner) return;
            try {
                var res = await fetch('/api/elms.php?action=get_live_sessions');
                var data = await res.json();
                if (data.success && data.live_sessions && data.live_sessions.length > 0) {
                    var s = data.live_sessions[0];
                    var currentLiveKey = s.course_code + '_' + (s.session_code || '') + '_' + (s.is_attendance_locked ? '1' : '0');
                    if (currentLiveKey !== LAST_DASHBOARD_LIVE_KEY) {
                        var isFirstAlert = (LAST_DASHBOARD_LIVE_KEY === '');
                        LAST_DASHBOARD_LIVE_KEY = currentLiveKey;
                        banner.className = 'relative overflow-hidden rounded-2xl border-2 border-red-500/80 bg-gradient-to-r from-red-500/15 via-surface-container-low to-red-500/5 p-4 md:p-5 shadow-lg ring-4 ring-red-500/10 animate-fade-in flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6';
                        banner.innerHTML = '<div class="flex items-start gap-3.5">' +
                            '<div class="w-10 h-10 rounded-2xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-md relative overflow-hidden" data-npc-3d-icon="live">' +
                            '<span class="material-symbols-outlined text-[22px] animate-radar-3d">sensors</span>' +
                            '</div>' +
                            '<div>' +
                            '<div class="flex items-center gap-2">' +
                            '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-red-600 text-white animate-pulse">🔴 LIVE CLASS IN PROGRESS</span>' +
                            '<span class="text-xs font-mono font-bold text-red-600 dark:text-red-400">Section ' + (s.section || '2A') + '</span>' +
                            '<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-primary/10 text-primary">PlugNmeet WebRTC</span>' +
                            '</div>' +
                            '<h4 class="text-base font-bold text-primary mt-0.5">' + s.course_code + ' — ' + s.course_title + '</h4>' +
                            '<p class="text-xs text-on-surface-variant">Instructor: <strong class="text-on-surface">' + s.instructor + '</strong> · ' + (s.topic || 'Synchronous Virtual Class') + '</p>' +
                            '</div>' +
                            '</div>' +
                            '<a href="/student/courses.php?join_course=' + encodeURIComponent(s.course_code) + '" class="btn-3d px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition-all inline-flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">' +
                            '<span class="material-symbols-outlined text-[17px]">co_present</span> Join Live Class (PlugNmeet)' +
                            '</a>';
                        banner.classList.remove('hidden');
                        if (!isFirstAlert && window.notify) {
                            window.notify('🔴 LIVE ONLINE CLASS: ' + s.course_code + ' has started in PlugNmeet! Click "Join Live Class" to enter.', 'success');
                        }
                        if (window.npcThree && typeof window.npcThree.initAuto3DIcons === 'function') {
                            window.npcThree.initAuto3DIcons();
                        }
                    }
                } else {
                    if (LAST_DASHBOARD_LIVE_KEY !== '') {
                        LAST_DASHBOARD_LIVE_KEY = '';
                        banner.classList.add('hidden');
                        banner.innerHTML = '';
                    }
                }
            } catch (err) {
                console.warn('Live session check notice:', err);
            }
        }
        checkStudentDashboardLiveClass();
        setInterval(checkStudentDashboardLiveClass, 2500);
    </script>

    <script src="/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../../assets/js/app.js') ? filemtime(__DIR__ . '/../../assets/js/app.js') : (file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : 1) ?>"></script>
</body>

</html>