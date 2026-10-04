<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$full_name = $is_logged_in ? (string)$_SESSION['name'] : 'Guest User';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
$user_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar');
$student_program = isset($_SESSION['program']) ? $_SESSION['program'] : 'AIS';
$student_section = isset($_SESSION['section']) ? $_SESSION['section'] : '2A';
$assigned_section = trim("$student_program $student_section");
$csrf_token = getCsrfToken();
$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records & Student Services - NPC Connect</title>
    <!-- Tailwind CSS CDN -->
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <style>
        @media print {
            @page { size: portrait; margin: 12mm 15mm; }
            aside, #topbar, nav, button, .no-print { display: none !important; }
            #app-root, main, #canvas-container { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            #print-report-header { display: block !important; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #333 !important; padding: 6px 8px !important; color: #000 !important; }
            th { background: #eee !important; font-weight: bold !important; }
        }
        #print-report-header { display: none; }
    </style>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>
    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar Desktop -->
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
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Academic Records & Student Services</h2>
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
                            <img alt="<?= htmlspecialchars($full_name) ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-amber-300 shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all"
                                src="<?= htmlspecialchars($userAvatar) ?>" referrerpolicy="no-referrer"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($full_name, 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($full_name, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-sm font-semibold text-primary hidden md:inline group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors" id="user-name-display"><?= htmlspecialchars($full_name) ?></span>
                    </a>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 sm:space-y-8" id="canvas-container">
                
                <!-- Print Header -->
                <div id="print-report-header">
                    <div class="flex items-center gap-4 mb-3">
                        <img src="/assets/img/npc-logo.png" class="w-12 h-12 object-contain" alt="NPC Emblem">
                        <div>
                            <h2 class="text-base font-bold uppercase text-black">Navotas Polytechnic College</h2>
                            <p class="text-xs text-gray-700">Office of the Registrar — Student Academic & Grade Report</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 text-xs border-t border-b border-gray-400 py-1.5 mb-3">
                        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
                        <p><strong>Student #:</strong> <?= htmlspecialchars($user_id_display) ?></p>
                        <p><strong>Program & Section:</strong> <?= htmlspecialchars($assigned_section) ?></p>
                        <p><strong>Term:</strong> 1st Semester, 2026-2027</p>
                    </div>
                </div>

                <!-- Page Header & Student Summary -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-outline-variant/60 pb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h1 class="text-2xl md:text-3xl font-bold text-primary tracking-tight">Academic Performance</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-status-success/15 text-status-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-status-success"></span> Official Student Records
                            </span>
                        </div>
                        <p class="text-sm text-on-surface-variant">Confidential student grades, cumulative GPA evaluation, attendance metrics, and official registrar requests.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 no-print">
                        <button onclick="openConsultModal()" class="px-3 sm:px-4 py-2 sm:py-2.5 bg-primary text-on-primary rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm hover:opacity-90 shrink-0">
                            <span class="material-symbols-outlined text-[16px]">event_available</span> Book Consultation
                        </button>
                        <button onclick="window.print()" class="px-3 sm:px-4 py-2 sm:py-2.5 bg-surface hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-bold text-primary flex items-center gap-1.5 shadow-sm shrink-0">
                            <span class="material-symbols-outlined text-[18px]">print</span> Print Grade Slip
                        </button>
                        <button onclick="openDocumentRequestModal()" class="px-3 sm:px-4 py-2 sm:py-2.5 bg-primary hover:bg-primary-container text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm shrink-0">
                            <span class="material-symbols-outlined text-[18px]">receipt_long</span> Request Documents
                        </button>
                    </div>
                </div>

                <!-- Bento Metrics -->
                <section class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-6">
                    <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-6 border border-outline-variant shadow-sm">
                        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-on-surface-variant">Semester GPA</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-primary mt-2" id="semester-gpa-display">—</h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-2 flex items-center gap-1" id="gpa-status-note">
                            <span class="material-symbols-outlined text-[16px] text-outline-variant">schedule</span> Evaluating Official Records
                        </p>
                    </div>

                    <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-6 border border-outline-variant shadow-sm">
                        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-on-surface-variant">Overall Attendance Rate</span>
                        <h3 class="text-2xl sm:text-3xl font-bold text-status-success mt-2" id="academic-attendance-rate">100%</h3>
                        <p class="text-xs text-on-surface-variant mt-2 font-medium" id="academic-checkins-count">Verified lecture check-ins</p>
                    </div>

                    <div class="npc-navy-card text-white rounded-2xl p-4 sm:p-6 shadow-md">
                        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-blue-200">Academic Standing</span>
                        <h3 class="text-xl sm:text-2xl font-bold text-white mt-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container">school</span> Regular Student
                        </h3>
                        <p class="text-xs text-blue-100/90 mt-2">Section <?= htmlspecialchars($assigned_section) ?> • AY 2026-2027</p>
                    </div>
                </section>

                <!-- Navigation Tabs: Grades vs Document Requests vs Attendance -->
                <div class="flex items-center gap-2 border-b border-outline-variant/60 no-print overflow-x-auto pb-1 -mb-px flex-nowrap whitespace-nowrap">
                    <button id="tab-btn-grades" onclick="switchStudentTab('grades')" class="px-4 sm:px-5 py-2.5 sm:py-3 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">grade</span>
                        <span>My Official Grades</span>
                    </button>
                    <button id="tab-btn-docs" onclick="switchStudentTab('docs')" class="px-4 sm:px-5 py-2.5 sm:py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        <span>Document Requests (<span id="doc-req-badge">0</span>)</span>
                    </button>
                    <button id="tab-btn-attendance" onclick="switchStudentTab('attendance')" class="px-4 sm:px-5 py-2.5 sm:py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">fact_check</span>
                        <span>Attendance History</span>
                    </button>
                    <button id="tab-btn-materials" onclick="switchStudentTab('materials'); loadClassMaterials();" class="px-4 sm:px-5 py-2.5 sm:py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">folder_shared</span>
                        <span>Class Materials (<span id="mat-badge">0</span>)</span>
                    </button>
                </div>

                <!-- 1. OFFICIAL GRADES TAB -->
                <div id="section-student-grades" class="space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                            <div>
                                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">military_tech</span>
                                    Official Published Grade Report
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">1st Semester, Academic Year 2026-2027 • Approved by Registrar</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-surface-container-low font-mono text-xs text-on-surface uppercase tracking-wider">
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Subject Code</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Description</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Units</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Prelim</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Midterm</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Finals</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Final Rating</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Published</th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant"></th>
                                        <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-right">Remark</th>
                                    </tr>
                                </thead>
                                <tbody id="student-grades-tbody" class="divide-y divide-outline-variant/30 text-sm font-medium">
                                            <tr>
                                                <td colspan="10" class="px-6 py-5">
                                                    <div class="npc-skeleton-group" aria-busy="true">
                                                        <div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div>
                                                        <div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div>
                                                        <div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. DOCUMENT REQUESTS TAB -->
                <div id="section-student-docs" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                            <div>
                                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                                    Document Requests Status Tracker
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">Track Certificate of Registration (COR), Certificate of Enrollment (COE), Good Moral & Transcript requests.</p>
                            </div>
                            <button onclick="openDocumentRequestModal()" class="px-3.5 py-2 bg-primary text-white rounded-xl text-xs font-bold shadow-sm">
                                + New Request
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low font-mono text-on-surface uppercase tracking-wider">
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Reference #</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Document Type</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Purpose</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Date Requested</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Registrar Remarks</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="document-requests-tbody" class="divide-y divide-outline-variant/30 text-xs">
                                            <tr><td colspan="5" class="px-6 py-4">
                                                <div class="npc-skeleton-group" aria-busy="true">
                                                    <div class="sk-table-row" style="grid-template-columns:1fr 2fr 1fr 1fr 1fr"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem"></div></div>
                                                    <div class="sk-table-row" style="grid-template-columns:1fr 2fr 1fr 1fr 1fr;padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem"></div></div>
                                                </div>
                                            </td></tr>
                                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. ATTENDANCE HISTORY TAB -->
                <div id="section-student-attendance" class="hidden space-y-6">
                    <!-- Attendance Summary Cards (counts + % + delinquency warning) -->
                    <div id="attendance-summary-wrap" class="hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
                            <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant shadow-sm">
                                <span class="text-[11px] font-mono font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-status-success">check_circle</span> On-Time</span>
                                <h3 class="text-3xl font-bold text-status-success mt-1.5" id="att-present-count">0</h3>
                                <p class="text-[11px] text-on-surface-variant mt-1">verified present check-ins</p>
                            </div>
                            <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant shadow-sm">
                                <span class="text-[11px] font-mono font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-status-warning">schedule</span> Late</span>
                                <h3 class="text-3xl font-bold text-status-warning mt-1.5" id="att-late-count">0</h3>
                                <p class="text-[11px] text-on-surface-variant mt-1">counted at 80% weight</p>
                            </div>
                            <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant shadow-sm">
                                <span class="text-[11px] font-mono font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-error">cancel</span> Absent</span>
                                <h3 class="text-3xl font-bold text-error mt-1.5" id="att-absent-count">0</h3>
                                <p class="text-[11px] text-on-surface-variant mt-1">recorded absences</p>
                            </div>
                            <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant shadow-sm flex items-center gap-4">
                                <div class="relative w-16 h-16 shrink-0">
                                    <svg viewBox="0 0 48 48" class="w-full h-full -rotate-90">
                                        <circle cx="24" cy="24" r="20" fill="none" stroke-width="5" class="stroke-outline-variant opacity-30"></circle>
                                        <circle id="att-rate-arc" cx="24" cy="24" r="20" fill="none" stroke-width="5" stroke-linecap="round" class="stroke-status-success transition-all duration-700 ease-out" stroke-dasharray="125.6" stroke-dashoffset="125.6"></circle>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center font-mono text-[11px] font-bold text-primary" id="att-rate-num">—</span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-mono font-semibold uppercase tracking-wider text-on-surface-variant">Attendance Rate</span>
                                    <h3 class="text-sm font-bold text-on-surface mt-1" id="att-rate-caption">Keep it up!</h3>
                                </div>
                            </div>
                        </div>
                        <div id="attendance-warning" class="hidden mt-4 rounded-2xl border border-error/30 bg-error/10 p-4 flex items-start gap-3 no-print">
                            <span class="material-symbols-outlined text-error text-[20px] shrink-0">warning</span>
                            <p class="text-xs font-semibold text-error leading-relaxed" id="attendance-warning-text"></p>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                            <div>
                                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">fact_check</span>
                                    Verified Attendance Log
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">Real-time attendance check-ins recorded via Faculty QR scanner.</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low font-mono text-on-surface uppercase tracking-wider">
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Session Code</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Date & Time</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant">Method</th>
                                        <th class="py-3 px-6 font-semibold border-b border-outline-variant text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="student-attendance-tbody" class="divide-y divide-outline-variant/30 text-xs">
                                    <tr><td colspan="8"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Excuse Letters (submit + track) -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm no-print">
                        <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                            <div>
                                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">mark_email_read</span>
                                    Attendance Excuse Letters
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">Were you absent or late for a valid reason? Submit an excuse letter for evaluation by your professor.</p>
                            </div>
                            <button onclick="toggleExcuseForm()" id="btn-toggle-excuse" class="px-4 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-bold shadow-sm hover:opacity-90 flex items-center gap-1.5 shrink-0">
                                <span class="material-symbols-outlined text-[16px]">add</span> Submit Excuse
                            </button>
                        </div>

                        <div id="excuse-form-wrap" class="hidden p-6 border-b border-outline-variant bg-surface-container/30 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Subject / Professor:</label>
                                    <select id="excuse-class" required class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold"></select>
                                    <p class="text-[10px] text-on-surface-variant mt-1">From your schedule — the excuse will be routed to this professor.</p>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Date of Absence/Tardiness:</label>
                                    <input type="date" id="excuse-date" max="<?= date('Y-m-d') ?>" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Session Code (optional):</label>
                                    <input type="text" id="excuse-session" placeholder="NPC-IT101-2026-08-25" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono">
                                    <p class="text-[10px] text-on-surface-variant mt-1">If known — this automatically updates your attendance record once approved.</p>
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Reason (min 10 characters):</label>
                                <textarea id="excuse-reason" rows="3" placeholder="e.g. I was hospitalized due to medical reasons and advised to rest..." class="w-full bg-white border border-gray-300 rounded-xl p-3 text-xs"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button onclick="toggleExcuseForm()" class="px-4 py-2 rounded-xl border border-outline-variant font-bold hover:bg-surface-container">Cancel</button>
                                <button onclick="submitExcuse()" id="btn-excuse-send" class="px-5 py-2 bg-primary text-on-primary rounded-xl font-bold shadow-sm hover:opacity-90 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[15px]">send</span> Submit
                                </button>
                            </div>
                        </div>

                        <div id="my-excuses-list" class="divide-y divide-outline-variant/40 max-h-64 overflow-y-auto custom-scroll">
                            <div class="npc-skeleton-group p-5" aria-busy="true">
                                <div class="sk-row"><div class="skeleton sk-avatar"></div><div class="flex-1 min-w-0"><div class="skeleton sk-line w-2/3"></div><div class="skeleton sk-line w-1/2" style="margin-bottom:0"></div></div></div>
                                <div class="sk-row" style="padding-bottom:0"><div class="skeleton sk-avatar"></div><div class="flex-1 min-w-0"><div class="skeleton sk-line w-3/4"></div><div class="skeleton sk-line w-1/3" style="margin-bottom:0"></div></div></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 4. CLASS MATERIALS TAB -->
                <div id="section-student-materials" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                            <div>
                                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">folder_shared</span>
                                    Class Materials &amp; Handouts
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">Files shared by your professors for your section — syllabus, lecture notes, modules, exam guides.</p>
                            </div>
                            <button onclick="loadClassMaterials(true)" class="px-3.5 py-2 bg-surface hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-bold text-primary flex items-center gap-1.5 shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">refresh</span> Refresh
                            </button>
                        </div>
                        <div id="materials-grid" class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></div>
                    </div>

                    <!-- Section announcements from my professors -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-outline-variant bg-surface-subtle">
                            <h3 class="font-bold text-primary text-base flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary text-[20px]">campaign</span>
                                Announcements from My Professors
                            </h3>
                            <p class="text-xs text-on-surface-variant mt-0.5">Section-scoped class posts — you will also receive bell notifications for updates.</p>
                        </div>
                        <div id="class-announcements-list" class="p-6 space-y-3"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- 📄 DOCUMENT REQUEST MODAL -->
    <div id="doc-request-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-4">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]">receipt_long</span>
                    <h3 class="text-base font-bold text-primary">Request Academic Document</h3>
                </div>
                <button onclick="closeDocumentRequestModal()" class="text-gray-400 hover:text-gray-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Document Type:</label>
                    <select id="req-doc-type" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-1 focus:ring-primary">
                        <option value="Certificate of Registration (COR)">Certificate of Registration (COR)</option>
                        <option value="Certificate of Enrollment (COE)">Certificate of Enrollment (COE)</option>
                        <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                        <option value="Official Transcript of Records (OTR)">Official Transcript of Records (OTR)</option>
                        <option value="Certified True Copy of Grades / Grade Slip">Certified True Copy of Grades / Grade Slip</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Purpose of Request:</label>
                    <textarea id="req-doc-purpose" rows="3" placeholder="e.g. Scholarship application, Employment requirement, Board Exam qualification..." class="w-full bg-white border border-gray-300 rounded-xl p-3 text-xs text-gray-800 focus:ring-1 focus:ring-primary"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t">
                <button onclick="closeDocumentRequestModal()" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button onclick="submitDocumentRequest()" class="px-5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary-container shadow-sm">
                    Submit Request
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 bg-gray-900 text-white text-xs px-4 py-3 rounded-xl shadow-2xl z-50 flex items-center gap-2.5 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-2">
        <span class="material-symbols-outlined text-[18px] text-emerald-400" id="toast-icon">check_circle</span>
        <span id="toast-message">Ready</span>
    </div>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;

        // Tiny escape helper for injected record fields
        function escA(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Expand/collapse per-subject component breakdown
        function toggleGradeDetail(classId, btn) {
            const row = document.getElementById('grade-detail-' + classId);
            if (!row || !btn) return;
            const nowHidden = row.classList.toggle('hidden');
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = nowHidden ? 'expand_more' : 'expand_less';
        }

        function switchStudentTab(tab) {
            document.getElementById('tab-btn-grades').className = tab === 'grades' 
                ? 'px-5 py-3 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2'
                : 'px-5 py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2';
            document.getElementById('tab-btn-docs').className = tab === 'docs' 
                ? 'px-5 py-3 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2'
                : 'px-5 py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2';
            document.getElementById('tab-btn-attendance').className = tab === 'attendance' 
                ? 'px-5 py-3 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2'
                : 'px-5 py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2';
            document.getElementById('tab-btn-materials').className = tab === 'materials' 
                ? 'px-5 py-3 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2'
                : 'px-5 py-3 text-xs font-bold text-gray-500 hover:text-primary border-b-2 border-transparent flex items-center gap-2';

            document.getElementById('section-student-grades').classList.toggle('hidden', tab !== 'grades');
            document.getElementById('section-student-docs').classList.toggle('hidden', tab !== 'docs');
            document.getElementById('section-student-attendance').classList.toggle('hidden', tab !== 'attendance');
            document.getElementById('section-student-materials').classList.toggle('hidden', tab !== 'materials');
        }

        // Class Materials + section announcements (lazy-load on first open)
        let materialsLoadedOnce = false;
        async function loadClassMaterials(force) {
            if (materialsLoadedOnce && !force) return;
            materialsLoadedOnce = true;

            // 1. Materials grid
            try {
                const res = await fetch('/api/student.php?action=get_class_materials');
                const data = await res.json();
                const grid = document.getElementById('materials-grid');
                const badge = document.getElementById('mat-badge');

                if (data.success && data.materials && data.materials.length > 0) {
                    badge.innerText = data.materials.length;
                    const iconMap = { pdf: 'picture_as_pdf', doc: 'description', docx: 'description', ppt: 'slideshow', pptx: 'slideshow', xls: 'table_chart', xlsx: 'table_chart', txt: 'article', jpg: 'image', jpeg: 'image', png: 'image' };
                    grid.innerHTML = data.materials.map(m => {
                        const ext = String(m.file_name || '').split('.').pop().toLowerCase();
                        const icon = iconMap[ext] || 'insert_drive_file';
                        return `
                        <div class="bg-surface-container-low/50 rounded-xl border border-outline-variant/60 p-4 flex flex-col gap-2 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between gap-2">
                                <span class="material-symbols-outlined text-[28px] text-error">${icon}</span>
                                <span class="text-[10px] font-mono uppercase text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">${escA(m.category || 'File')}</span>
                            </div>
                            <p class="text-sm font-bold text-on-surface break-words leading-snug">${escA(m.title)}</p>
                            <p class="text-[11px] text-on-surface-variant font-mono">${escA(m.file_size || '')}</p>
                            <a href="/api/download_material.php?file=${encodeURIComponent(m.file_name || '')}" class="mt-auto pt-2 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-primary-container shadow-sm">
                                <span class="material-symbols-outlined text-[15px]">download</span> Download
                            </a>
                        </div>
                        `;
                    }).join('');
                } else {
                    badge.innerText = '0';
                    grid.innerHTML = '<div class="col-span-full p-8 text-center flex flex-col items-center gap-2">' +
                        '<span class="material-symbols-outlined text-[40px] text-outline-variant">folder_off</span>' +
                        '<p class="text-sm font-semibold text-on-surface">No materials shared yet</p>' +
                        '<p class="text-xs text-on-surface-variant">Files shared by your professors will appear here.</p></div>';
                }
            } catch (err) {
                console.error(err);
            }

            // 2. Section announcements
            try {
                const res2 = await fetch('/api/student.php?action=get_section_announcements');
                const data2 = await res2.json();
                const list = document.getElementById('class-announcements-list');

                if (data2.success && data2.announcements && data2.announcements.length > 0) {
                    const catIcon = { assignment: 'assignment', exam: 'quiz', reminder: 'alarm', general: 'campaign' };
                    list.innerHTML = data2.announcements.map(a => `
                        <div class="p-4 bg-surface-container-low/40 rounded-xl border border-outline-variant/40 space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-mono text-[10px] uppercase font-bold">
                                    <span class="material-symbols-outlined text-[12px]">${catIcon[a.category] || 'campaign'}</span> ${escA(a.class_code || 'Class')} · ${escA(a.section || '')}
                                </span>
                                <span class="font-mono text-[10px] text-outline shrink-0">${new Date(a.created_at).toLocaleDateString()}</span>
                            </div>
                            <h4 class="text-sm font-bold text-primary break-words">${escA(a.title)}</h4>
                            ${a.body ? `<p class="text-xs text-on-surface-variant break-words leading-relaxed" style="overflow-wrap:anywhere;">${escA(a.body)}</p>` : ''}
                            <p class="text-[10px] font-mono text-outline">— Prof. ${escA(a.faculty_name || 'Faculty')}</p>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">No announcements from your professors yet.</p>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        /* ═══ Excuse Letters (student) ═══ */
        let myScheduleCache = [];
        function toggleExcuseForm() {
            document.getElementById('excuse-form-wrap').classList.toggle('hidden');
        }

        async function loadExcuses() {
            // Populate the class dropdown from enrolled schedule + render my excuses
            try {
                const sRes = await fetch('/api/student.php?action=get_enrolled_schedule');
                const sData = await sRes.json();
                if (sData.success) {
                    myScheduleCache = sData.classes || [];
                    const sel = document.getElementById('excuse-class');
                    if (sel && !sel.options.length) {
                        sel.innerHTML = '<option value="">Select subject…</option>' + myScheduleCache.map(c => {
                            const email = escA(String(c.instructor_email || ''));
                            return `<option value="${email}|${escA(String(c.code || ''))}">${escA(String(c.code || ''))} — ${escA(String(c.instructor || 'TBA'))}</option>`;
                        }).join('');
                    }
                }
            } catch (e) { console.error(e); }

            try {
                const res = await fetch('/api/student.php?action=get_my_excuses');
                const data = await res.json();
                const list = document.getElementById('my-excuses-list');
                if (!data.success || !data.excuses.length) {
                    list.innerHTML = '<p class="p-6 text-center text-sm text-on-surface-variant">No excuse letters submitted yet.</p>';
                    return;
                }
                const pill = st => st === 'Approved' ? 'bg-emerald-100 text-emerald-800'
                    : (st === 'Rejected' ? 'bg-red-100 text-error' : 'bg-amber-100 text-amber-800');
                list.innerHTML = data.excuses.map(x => `
                    <div class="p-4 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-mono font-bold text-primary text-xs">${escA(String(x.class_code || ''))}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${pill(x.status)}">${escA(String(x.status))}</span>
                            </div>
                            <p class="text-xs text-on-surface"><strong>${escA(String(x.absence_date))}</strong> — ${escA(String(x.reason)).slice(0, 140)}</p>
                            ${x.remarks ? `<p class="text-[11px] text-on-surface-variant mt-1"><strong>Prof remarks:</strong> ${escA(String(x.remarks))}</p>` : ''}
                        </div>
                        <span class="text-[10px] font-mono text-outline shrink-0">${new Date(x.created_at).toLocaleDateString()}</span>
                    </div>
                `).join('');
            } catch (e) { console.error(e); }
        }

        async function submitExcuse() {
            const sel = document.getElementById('excuse-class');
            const dateEl = document.getElementById('excuse-date');
            const sessionEl = document.getElementById('excuse-session');
            const reasonEl = document.getElementById('excuse-reason');
            const btn = document.getElementById('btn-excuse-send');

            const [facultyEmail, classCode] = (sel.value || '').split('|');
            if (!facultyEmail || !dateEl.value || reasonEl.value.trim().length < 10) {
                showToastMsg('Please complete the form — subject, date, and reason (min 10 characters) are required.', false);
                return;
            }
            btn.disabled = true;
            try {
                const res = await fetch('/api/student.php?action=submit_excuse', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        faculty_email: facultyEmail,
                        class_code: classCode,
                        absence_date: dateEl.value,
                        session_code: sessionEl.value.trim(),
                        reason: reasonEl.value.trim(),
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                showToastMsg(data.message || (data.success ? 'Submitted!' : 'Failed.'), data.success);
                if (data.success) {
                    reasonEl.value = ''; sessionEl.value = '';
                    document.getElementById('excuse-form-wrap').classList.add('hidden');
                    await loadExcuses();
                }
            } catch (e) {
                showToastMsg('Network error: ' + e.message, false);
            } finally {
                btn.disabled = false;
            }
        }

        /* ═══ Grade clarification (student → professor) ═══ */
        function requestClarification(subjectCode) {
            const msg = prompt(`Clarification request for ${subjectCode}:\n\nWhat would you like to ask or clarify with your professor?`, '');
            if (msg === null) return;
            if (msg.trim().length < 10) { showToastMsg('Message must be at least 10 characters.', false); return; }
            fetch('/api/student.php?action=request_grade_clarification', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ subject_code: subjectCode, message: msg.trim(), csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => showToastMsg(d.message || (d.success ? 'Sent!' : 'Failed.'), d.success))
            .catch(e => showToastMsg('Network error: ' + e.message, false));
        }

        /* ═══ Consultation booking modal (student) ═══ */
        async function openConsultModal(prefillSubject) {
            let m = document.getElementById('consult-modal');
            if (!m) {
                m = buildConsultModal();
                document.body.appendChild(m);
            }
            m.classList.remove('hidden');
            // Load teachers once
            const sel = document.getElementById('consult-teacher');
            if (sel && !sel.dataset.loaded) {
                try {
                    const res = await fetch('/api/student.php?action=get_teachers');
                    const data = await res.json();
                    if (data.success && data.teachers.length) {
                        sel.innerHTML = '<option value="">Select professor…</option>' +
                            data.teachers.map(t =>
                                `<option value="${escA(t.email)}|${escA(t.name)}|${escA(t.subject_code)}">${escA(t.name)}${t.my_class ? ' ★' : ''}${t.subject_code ? ' — ' + escA(t.subject_code) : ''}</option>`
                            ).join('');
                        sel.dataset.loaded = '1';
                    } else {
                        sel.innerHTML = '<option value="">No professors found</option>';
                    }
                } catch (e) { console.error(e); }
            }
            if (prefillSubject) {
                const opt = [...sel.options].find(o => o.value.includes('|' + prefillSubject));
                if (opt) sel.value = opt.value;
            }
        }

        function buildConsultModal() {
            const m = document.createElement('div');
            m.id = 'consult-modal';
            m.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4';
            m.innerHTML = `
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-xl max-w-md w-full">
                    <div class="p-5 border-b border-outline-variant flex items-center justify-between">
                        <h3 class="font-bold text-primary flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">event_available</span> Book Consultation</h3>
                        <button onclick="document.getElementById('consult-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error text-xl leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Professor:</label>
                            <select id="consult-teacher" onchange="consultTeacherChanged()" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 font-semibold"></select>
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Topic / Reason:</label>
                            <input type="text" id="consult-topic" placeholder="e.g. Clearance of attendance record / project consultation" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Preferred Date:</label>
                                <input type="date" id="consult-date" min="<?= date('Y-m-d') ?>" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Preferred Time:</label>
                                <input type="time" id="consult-time" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-1">
                            <button onclick="document.getElementById('consult-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-outline-variant font-bold hover:bg-surface-container">Cancel</button>
                            <button onclick="submitConsultation()" class="px-5 py-2 bg-primary text-on-primary rounded-xl font-bold shadow-sm hover:opacity-90">Book</button>
                        </div>
                    </div>
                </div>`;
            return m;
        }

        function consultTeacherChanged() {
            const v = (document.getElementById('consult-teacher').value || '').split('|');
            const subj = v[2] || '';
            const topicEl = document.getElementById('consult-topic');
            if (subj && !topicEl.value) topicEl.placeholder = `e.g. Consultation about ${subj}`;
        }

        async function submitConsultation() {
            const [fEmail, fName] = (document.getElementById('consult-teacher').value || '').split('|');
            const topic = document.getElementById('consult-topic').value.trim();
            const date = document.getElementById('consult-date').value;
            const time = document.getElementById('consult-time').value;
            if (!fEmail || !topic || !date || !time) {
                showToastMsg('Please complete all fields.', false);
                return;
            }
            const subjectCode = (document.getElementById('consult-teacher').value.split('|')[2]) || '';
            try {
                const res = await fetch('/api/student.php?action=book_consultation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ faculty_email: fEmail, faculty_name: fName, subject_code: subjectCode, date, time, topic, csrf_token: csrfToken })
                });
                const data = await res.json();
                showToastMsg(data.message || (data.success ? 'Booked!' : 'Failed.'), data.success);
                if (data.success) document.getElementById('consult-modal').classList.add('hidden');
            } catch (e) {
                showToastMsg('Network error: ' + e.message, false);
            }
        }

        /* Small toast helper (academic page has no global toast fn) */
        function showToastMsg(msg, ok) {
            if (window.notify) { window.notify(msg, ok ? 'success' : 'error'); return; }
            alert(msg);
        }

        async function loadStudentData() {
            // 1. Fetch Grades
            try {
                const res = await fetch('/api/student.php?action=get_my_grades');
                const data = await res.json();

                const tbody = document.getElementById('student-grades-tbody');
                const gpaDisplay = document.getElementById('semester-gpa-display');
                const gpaNote = document.getElementById('gpa-status-note');

                if (data.success && data.grades && data.grades.length > 0) {
                    // Per-period detail map from published student_grades records
                    const details = (data.detailed && typeof data.detailed === 'object') ? data.detailed : {};
                    tbody.innerHTML = data.grades.map(g => {
                        const units = parseFloat(g.units) || 3.0;
                        const grade = parseFloat(g.grade) || 0;
                        const isPass = grade <= 3.00 && grade >= 1.00;

                        const d = details[g.class_id] || null;
                        const p = d ? (parseFloat(d.prelim) || 0) : 0;
                        const m = d ? (parseFloat(d.midterm) || 0): 0;
                        const f = d ? (parseFloat(d.final) || 0) : 0;
                        const hasDetail = !!(p > 0 || m > 0 || f > 0);
                        const fmt = v => v > 0 ? Number(v).toFixed(2) : '—';

                        let pubDate = '—';
                        if (d && (d.published_at || d.updated_at)) {
                            try { pubDate = new Date(d.published_at || d.updated_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }); } catch (e) {}
                        }

                        // Component chips builder
                        const chipRow = (label, obj) => {
                            if (!obj || typeof obj !== 'object') return '';
                            const items = Object.entries(obj)
                                .filter(([k]) => String(k).toLowerCase() !== 'total')
                .map(([k, v]) => `<span class="inline-flex flex-col items-center px-2 py-1 rounded-lg bg-surface-container border border-outline-variant/60" title="${escA(String(k))}: ${escA(String(v))}"><span class="text-[10px] text-on-surface-variant font-semibold uppercase leading-tight">${escA(String(k))}</span><span class="font-mono text-xs font-bold text-primary">${escA(String(v))}</span></span>`)
                                .join('');
                            return items ? `<div class="flex items-center gap-1.5 flex-wrap mt-1.5"><span class="text-[10px] font-mono uppercase text-on-surface-variant w-16 shrink-0">${escA(label)}</span>${items}</div>` : '';
                        };

                        const detailHtml = hasDetail ? `
                            <tr id="grade-detail-${escA(String(g.class_id))}" class="hidden">
                                <td colspan="10">
                                    <div class="py-4 px-6 bg-surface-container/50 grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-2">
                                        ${chipRow('Prelim', d.prelim_components)}${chipRow('Midterm', d.midterm_components)}${chipRow('Finals', d.final_components)}
                                    </div>
                                    ${(d.remarks || d.notes) ? `<p class="text-[11px] text-on-surface-variant px-6 pb-4 -mt-1"><strong>Faculty remarks:</strong> <span class="font-medium text-on-surface">${escA(String(d.remarks || d.notes))}</span></p>` : ''}
                                    <div class="px-6 pb-4 -mt-1 no-print">
                                        <button onclick="requestClarification('${escA(String(g.subject_code || ''))}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-outline-variant text-[11px] font-bold text-primary hover:bg-surface-container transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">help</span> Request Clarification
                                        </button>
                                    </div>
                                </td>
                            </tr>` : '';

                        const isOngoing = (!grade || grade <= 0 || (g.status && g.status.toLowerCase() === 'ongoing'));
                        let badgeClass = 'bg-status-info/15 text-status-info border border-status-info/20';
                        let dotClass = 'bg-status-info animate-pulse';
                        let statusText = 'Ongoing';

                        if (!isOngoing) {
                            if (isPass) {
                                badgeClass = 'bg-status-success/15 text-status-success';
                                dotClass = 'bg-status-success';
                                statusText = g.status || 'Passed';
                            } else {
                                badgeClass = 'bg-red-100 text-error dark:bg-red-950/40 dark:text-red-400';
                                dotClass = 'bg-error';
                                statusText = g.status || 'Failed';
                            }
                        }

                        return `
                            <tr class="hover:bg-surface-subtle transition-colors">
                                <td class="py-3.5 px-6 font-mono font-bold text-primary">${g.subject_code}</td>
                                <td class="py-3.5 px-6 text-on-surface">${g.description || 'Subject Course'}</td>
                                ${detailHtml ? `<td class="py-3.5 px-6 font-mono text-center"><button onclick="toggleGradeDetail('${escA(String(g.class_id))}', this)" class="inline-flex p-1.5 rounded-lg hover:bg-surface-container transition-colors" title="View component breakdown" aria-label="Toggle component breakdown"><span class="material-symbols-outlined text-[18px] text-primary">expand_more</span></button></td>` : `<td class="py-3.5 px-6 text-center text-outline-variant text-xs">—</td>`}
                                <td class="py-3.5 px-6 font-mono text-center">${units.toFixed(1)}</td>
                                <td class="py-3.5 px-6 font-mono text-center ${p > 0 ? 'text-on-surface' : 'text-outline-variant'}">${fmt(p)}</td>
                                <td class="py-3.5 px-6 font-mono text-center ${m > 0 ? 'text-on-surface ' : 'text-outline-variant'}">${fmt(m)}</td>
                                <td class="py-3.5 px-6 font-mono text-center ${f > 0 ? 'text-on-surface' : 'text-outline-variant'}">${fmt(f)}</td>
                                <td class="py-3.5 px-6 font-mono font-bold text-primary text-center">${grade > 0 ? grade.toFixed(2) : '—'}</td>
                                <td class="py-3.5 px-6 font-mono text-[11px] text-on-surface-variant text-center whitespace-nowrap">${pubDate}</td>
                                <td class="py-3.5 px-6 text-right">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${badgeClass}">
                                        <span class="w-1.5 h-1.5 rounded-full ${dotClass}"></span> ${statusText}
                                    </span>
                                </td>
                            </tr>
                            ${detailHtml}
                        `;
                    }).join('');

                    if (data.summary.gpa && data.summary.gpa !== '—') {
                        gpaDisplay.innerText = data.summary.gpa;
                        gpaNote.innerHTML = '<span class="material-symbols-outlined text-[16px] text-status-success">verified</span> Evaluated & Officially Published';
                    } else {
                        gpaDisplay.innerText = '—';
                        gpaNote.innerHTML = '<span class="material-symbols-outlined text-[16px] text-status-info">schedule</span> Academic Term Ongoing · No Final Grades Released Yet';
                    }
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="10" class="p-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2 block">pending_actions</span>
                                <p class="font-semibold text-lg text-primary">No Published Grades Available Yet</p>
                                <p class="text-xs text-on-surface-variant mt-1 max-w-md mx-auto">Official grades will appear here once approved and published by the Registrar.</p>
                            </td>
                        </tr>
                    `;
                }
            } catch (err) {
                console.error(err);
            }

            // 2. Fetch Document Requests
            try {
                const res = await fetch('/api/student.php?action=get_document_requests');
                const data = await res.json();
                const tbody = document.getElementById('document-requests-tbody');
                const badge = document.getElementById('doc-req-badge');

                if (data.success && data.requests && data.requests.length > 0) {
                    badge.innerText = data.requests.length;
                    tbody.innerHTML = data.requests.map(r => `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 font-mono font-bold text-primary">${escA(r.reference_no)}</td>
                            <td class="py-3 px-6 font-bold">${escA(r.document_type)}</td>
                            <td class="py-3 px-6 text-gray-600">${escA(r.purpose)}</td>
                            <td class="py-3 px-6 font-mono text-gray-500">${new Date(r.requested_at).toLocaleDateString()}</td>
                            <td class="py-3 px-6 text-xs ${r.remarks ? 'text-gray-700' : 'text-gray-400 italic'}">${r.remarks ? escA(r.remarks) : 'No remarks yet'}</td>
                            <td class="py-3 px-6 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${String(r.status) === 'Ready for Pickup' || String(r.status) === 'Released' ? 'bg-emerald-100 text-emerald-800' : String(r.status) === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}">${escA(r.status)}</span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    badge.innerText = '0';
                    tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-400">No document requests submitted yet.</td></tr>';
                }
            } catch (err) {
                console.error(err);
            }

            // 3. Fetch Attendance History
            try {
                const res = await fetch('/api/student.php?action=get_attendance_metrics');
                const data = await res.json();
                const tbody = document.getElementById('student-attendance-tbody');

                if (data.success && data.records && data.records.length > 0) {
                    document.getElementById('academic-attendance-rate').innerText = data.stats.rate;
                    document.getElementById('academic-checkins-count').innerText = `${data.stats.present} On-Time, ${data.stats.late} Late`;

                    // Attendance summary cards + rate ring + delinquency warning
                    const st = data.stats;
                    const presentN = parseInt(st.present) || 0;
                    const lateN = parseInt(st.late) || 0;
                    const absentN = parseInt(st.absent) || 0;
                    const rateNum = parseFloat(String(st.rate).replace('%', '')) || 0;
                    const totalSessions = presentN + lateN + absentN;

                    const wrap = document.getElementById('attendance-summary-wrap');
                    if (wrap) {
                        wrap.classList.remove('hidden');
                        document.getElementById('att-present-count').innerText = presentN;
                        document.getElementById('att-late-count').innerText = lateN;
                        document.getElementById('att-absent-count').innerText = absentN;
                        document.getElementById('att-rate-num').innerText = Math.round(rateNum) + '%';

                        // Animate the ring (circumference = 2πr ≈ 125.6)
                        const arc = document.getElementById('att-rate-arc');
                        if (arc) requestAnimationFrame(() => {
                            arc.style.strokeDashoffset = String(125.6 * (1 - Math.min(rateNum, 100) / 100));
                        });

                        // Ring color shifts as rate drops
                        if (rateNum >= 90) arc.setAttribute('class', 'stroke-status-success transition-all duration-700 ease-out');
                        else if (rateNum >= 75) arc.setAttribute('class', 'stroke-status-warning transition-all duration-700 ease-out');
                        else arc.setAttribute('class', 'stroke-error transition-all duration-700 ease-out');

                        // Delinquency warning — NPC policy: max 20% absences per subject
                        const warnBox = document.getElementById('attendance-warning');
                        const warnText = document.getElementById('attendance-warning-text');
                        const absenceRate = totalSessions > 0 ? (absentN / totalSessions) * 100 : 0;
                        if (absenceRate >= 10 || rateNum < 75) {
                            warnBox.classList.remove('hidden');
                            warnText.innerHTML = absenceRate >= 20
                                ? `<strong>Critical Attendance Record:</strong> ${absentN} absence${absentN !== 1 ? 's' : ''} out of ${totalSessions} sessions (${Math.round(absenceRate)}%). Reaching the 20% limit may disqualify you from exams and passing this course. Please consult your academic adviser or professor immediately.`
                                : `<strong>Attendance Warning:</strong> ${absentN} absence${absentN !== 1 ? 's' : ''} and ${lateN} late out of ${totalSessions} sessions. The 20% absence limit is approaching — ensure attendance in upcoming classes.`;
                        } else {
                            warnBox.classList.add('hidden');
                        }
                    }

                    tbody.innerHTML = data.records.map(r => `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 font-mono font-bold text-primary">${r.session_code}</td>
                            <td class="py-3 px-6 font-mono text-gray-600">${new Date(r.check_in_at).toLocaleString()}</td>
                            <td class="py-3 px-6 uppercase font-mono text-[11px] text-gray-500">${r.method || 'QR Scanner'}</td>
                            <td class="py-3 px-6 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${r.status === 'present' ? 'bg-emerald-100 text-emerald-800' : r.status === 'late' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'}">${r.status.toUpperCase()}</span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-400">No attendance check-ins logged yet.</td></tr>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        function openDocumentRequestModal() {
            document.getElementById('doc-request-modal').classList.remove('hidden');
        }
        function closeDocumentRequestModal() {
            document.getElementById('doc-request-modal').classList.add('hidden');
        }

        async function submitDocumentRequest() {
            const docType = document.getElementById('req-doc-type').value;
            const purpose = document.getElementById('req-doc-purpose').value.trim();

            if (!purpose) {
                alert('Please enter the purpose of your document request.');
                return;
            }

            try {
                const res = await fetch('/api/student.php?action=request_document', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ document_type: docType, purpose: purpose, csrf_token: csrfToken })
                });

                const data = await res.json();
                if (data.success) {
                    alert(`✅ Document Request Submitted!\n\nReference Number: ${data.reference_no}\nPlease check back for status updates.`);
                    closeDocumentRequestModal();
                    await loadStudentData();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('Request error: ' + err.message);
            }
        }

        loadStudentData();
        loadExcuses();
    </script>
</body>
</html>
