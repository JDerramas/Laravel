<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$full_name = $is_logged_in ? (string)$_SESSION['name'] : 'Guest User';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
$user_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Resolve student enrolled program and section with normalization
$student_program = isset($_SESSION['program']) && !empty($_SESSION['program']) ? trim($_SESSION['program']) : 'AIS';
$student_section = isset($_SESSION['section']) && !empty($_SESSION['section']) ? trim($_SESSION['section']) : '2A';

if (!empty($student_program) && stripos($student_section, $student_program) === 0) {
    $assigned_section = $student_section;
} else {
    $assigned_section = trim("$student_program $student_section");
}

$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();

// Fast Server-Side Hydration: Query classes directly from MySQL
$classesRes = supabaseServiceQuery("/rest/v1/classes?order=code.asc");
$allClasses = ($classesRes['status'] === 200 && is_array($classesRes['data'])) ? $classesRes['data'] : [];

$secUpper = strtoupper($assigned_section);
$progUpper = strtoupper($student_program);
$secOnlyUpper = strtoupper($student_section);

$myClasses = array_filter($allClasses, function($c) use ($secUpper, $progUpper, $secOnlyUpper) {
    $cSec = strtoupper(trim($c['section'] ?? ''));
    if ($cSec === $secUpper) return true;
    if (!empty($progUpper) && !empty($secOnlyUpper)) {
        if (str_contains($cSec, $progUpper) && str_contains($cSec, $secOnlyUpper)) {
            return true;
        }
    }
    return false;
});
$myClasses = array_values($myClasses);

// Chronological Day-of-Week & Start-Time Sorter
$dayRank = [
    'M' => 1, 'MON' => 1, 'MONDAY' => 1,
    'T' => 2, 'TUE' => 2, 'TUESDAY' => 2,
    'W' => 3, 'WED' => 3, 'WEDNESDAY' => 3,
    'TH' => 4, 'THU' => 4, 'THURSDAY' => 4,
    'F' => 5, 'FRI' => 5, 'FRIDAY' => 5,
    'S' => 6, 'SAT' => 6, 'SATURDAY' => 6,
    'SU' => 7, 'SUN' => 7, 'SUNDAY' => 7,
    'TBA' => 8
];

$toMinutes = function($t) {
    if (empty($t) || strtoupper(trim($t)) === 'TBA') return 9999;
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)?/i', $t, $m)) {
        $h = (int)$m[1];
        $min = (int)$m[2];
        $ampm = strtoupper($m[3] ?? '');
        if ($ampm === 'PM' && $h < 12) $h += 12;
        if ($ampm === 'AM' && $h === 12) $h = 0;
        return $h * 60 + $min;
    }
    return 9999;
};

usort($myClasses, function($a, $b) use ($dayRank, $toMinutes) {
    $dA = $dayRank[strtoupper(trim($a['schedule_day'] ?? 'TBA'))] ?? 8;
    $dB = $dayRank[strtoupper(trim($b['schedule_day'] ?? 'TBA'))] ?? 8;
    if ($dA !== $dB) return $dA <=> $dB;
    return $toMinutes($a['start_time'] ?? '') <=> $toMinutes($b['start_time'] ?? '');
});

$totalUnits = 0;
foreach ($myClasses as $c) {
    $totalUnits += floatval($c['units'] ?? 3.0);
}

// Helper to expand day abbreviations to full name
function getFullDayName($abbr) {
    $map = [
        'M' => 'Monday', 'MON' => 'Monday',
        'T' => 'Tuesday', 'TUE' => 'Tuesday',
        'W' => 'Wednesday', 'WED' => 'Wednesday',
        'TH' => 'Thursday', 'THU' => 'Thursday',
        'F' => 'Friday', 'FRI' => 'Friday',
        'S' => 'Saturday', 'SAT' => 'Saturday',
        'SU' => 'Sunday', 'SUN' => 'Sunday'
    ];
    return $map[strtoupper(trim($abbr ?? ''))] ?? ($abbr ?: 'TBA');
}

// Group classes by day for the Day-by-Day timeline view
$classesByDay = [];
foreach ($myClasses as $c) {
    $dName = getFullDayName($c['schedule_day'] ?? 'TBA');
    $classesByDay[$dName][] = $c;
}
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NPC Connect - My Class Schedule</title>
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
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js" defer></script>
    <script src="/assets/js/npc-three.js" defer></script>
    <script src="/assets/js/npc.js" defer></script>
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
        /* 100% Screen-Fit Architecture: No horizontal scrolling or swiping anywhere */
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }

        /* Responsive Table-to-Card Engine */
        @media (max-width: 1023px) {
            .sched-table-wrapper {
                overflow-x: hidden !important;
            }
            .sched-adaptive-table {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .sched-adaptive-table thead {
                display: none !important;
            }
            .sched-adaptive-table tbody {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 12px !important;
            }
            .sched-adaptive-table tbody tr {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                border-radius: 14px !important;
                border: 1px solid rgb(var(--outline-variant-rgb) / 0.8) !important;
                padding: 14px 16px !important;
                background: rgb(var(--surface-container-lowest-rgb)) !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
            }
            .sched-adaptive-table tbody td {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 3px 0 !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                font-size: 13px !important;
            }
            .sched-adaptive-table tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: rgb(var(--on-surface-variant-rgb));
                flex-shrink: 0;
                margin-right: 12px;
            }
            .sched-adaptive-table tbody td.subject-title-cell {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 2px !important;
            }
            .sched-adaptive-table tfoot {
                display: block !important;
                width: 100% !important;
                padding: 8px 12px 14px 12px !important;
            }
            .sched-adaptive-table tfoot tr {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                border-radius: 12px !important;
                border: 1px solid rgb(var(--outline-variant-rgb)) !important;
                padding: 10px 14px !important;
                background: rgb(var(--surface-container-low-rgb)) !important;
            }
            .sched-adaptive-table tfoot td {
                border: none !important;
                padding: 0 !important;
            }
        }

        /* 🖨️ PERFECT PRINT STYLES */
        @media print {
            @page {
                size: letter portrait;
                margin: 12mm 15mm;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10pt !important;
            }
            aside, #topbar, #student-nav, .no-print, button, #view-toggle-bar, #tba-subjects-container, #grid-view-container, #timeline-view-container {
                display: none !important;
            }
            #app-root, #main-wrapper, main, #canvas-container {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }
            #print-header {
                display: block !important;
                border-bottom: 2px solid #000000;
                padding-bottom: 8px;
                margin-bottom: 15px;
            }
            #table-view-container {
                display: block !important;
                border: 1px solid #000000 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .sched-adaptive-table,
            .sched-adaptive-table thead,
            .sched-adaptive-table tbody,
            .sched-adaptive-table tfoot { display: table !important; width: 100% !important; }
            .sched-adaptive-table tr { display: table-row !important; }
            .sched-adaptive-table th, .sched-adaptive-table td { display: table-cell !important; }
            .sched-adaptive-table thead { display: table-header-group !important; }
            .sched-adaptive-table tbody td::before { display: none !important; }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #333333 !important;
                padding: 6px 8px !important;
                font-size: 9pt !important;
                color: #000000 !important;
            }
            th {
                background-color: #f0f0f0 !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
            .time-cell {
                white-space: nowrap !important;
                font-weight: 600 !important;
            }
            #print-footer {
                display: block !important;
                margin-top: 20px;
                font-size: 8pt;
                color: #555555;
            }
        }
        #print-header, #print-footer {
            display: none;
        }

        /* Visual Timetable Grid Styles */
        .timetable-7day-grid {
            display: grid;
            grid-template-columns: 75px repeat(7, minmax(130px, 1fr));
            gap: 1px;
            background-color: #cbd5e1;
        }
        .timetable-cell {
            background-color: #ffffff;
            padding: 4px;
            min-height: 80px;
            position: relative;
        }
        .timetable-header {
            background-color: #F8FAFC;
            padding: 8px 4px;
            font-weight: 700;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #0b1c30;
        }
        .event-lecture {
            background-color: #eff4ff;
            border-left: 3.5px solid #002b5c;
        }
        .event-lab {
            background-color: #fef3c7;
            border-left: 3.5px solid #b45309;
        }
        .event-evening {
            background-color: #f3e8ff;
            border-left: 3.5px solid #6b21a8;
        }
    </style>
    
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased overflow-x-hidden">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full overflow-x-hidden" id="app-root">

        <!-- SideNavBar Desktop -->
        <?php $NPC_PORTAL = 'student'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-8 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-base sm:text-lg font-bold text-primary lg:hidden truncate">NPC Schedule</span>
                    <h2 class="text-lg font-bold text-primary hidden lg:block" id="page-title">My Class Schedule</h2>
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
                        <img alt="<?php echo htmlspecialchars($full_name); ?>" class="w-8 h-8 rounded-full object-cover border border-outline-variant shadow-sm shrink-0"
                            src="<?php echo htmlspecialchars($userAvatar); ?>" referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary font-bold text-xs items-center justify-center shadow-sm shrink-0 hidden">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-xs font-semibold text-primary hidden md:inline truncate max-w-[120px]" id="user-name-display"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
            </header>

            <!-- Page Canvas: 100% Fit, Zero Horizontal Overflow -->
            <main class="flex-1 p-3 sm:p-5 md:p-8 max-w-7xl w-full mx-auto space-y-5 flex flex-col overflow-x-hidden" id="canvas-container">
                
                <!-- 🖨️ PRINT-ONLY OFFICIAL HEADER -->
                <div id="print-header">
                    <div class="flex items-center gap-4 mb-3">
                        <img src="/assets/img/npc-logo.png" class="w-14 h-14 object-contain" alt="NPC Logo">
                        <div>
                            <h2 class="text-lg font-bold uppercase tracking-wide text-black">Navotas Polytechnic College</h2>
                            <p class="text-xs text-gray-700">Office of the College Registrar • Academic Year 2026-2027</p>
                            <h3 class="text-sm font-bold uppercase text-black mt-0.5">Official Student Class Schedule</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs border-t border-b border-gray-400 py-2">
                        <div>
                            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($user_id_display); ?></p>
                        </div>
                        <div class="text-right">
                            <p><strong>Course & Section:</strong> <span id="print-section-display"><?php echo htmlspecialchars($assigned_section); ?></span></p>
                            <p><strong>Date Printed:</strong> <?php echo date('F d, Y'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- On-Screen Student Section Banner (Locked to Section, Responsive Actions) -->
                <div class="bg-surface-container-lowest p-4 sm:p-5 rounded-2xl border border-outline-variant shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-primary tracking-tight">Enrolled Class Schedule</h1>
                            <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-primary text-white shadow-sm" id="student-section-pill"><?php echo htmlspecialchars($assigned_section); ?></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Enrolled (AY 2026-2027)</span>
                        </div>
                        <p class="text-xs text-on-surface-variant">Database-driven official class schedule locked strictly to your enrolled section.</p>
                    </div>

                    <!-- Action Controls & View Switcher -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0" id="view-toggle-bar">
                        <!-- View Toggles -->
                        <div class="flex items-center bg-surface border border-outline-variant/60 rounded-xl p-1 shrink-0">
                            <button id="view-table-btn" onclick="setViewMode('table')" class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white flex items-center gap-1 shadow-sm transition-all">
                                <span class="material-symbols-outlined text-[15px]">table_rows</span> <span class="hidden sm:inline">Fit</span> Table
                            </button>
                            <button id="view-timeline-btn" onclick="setViewMode('timeline')" class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[15px]">view_timeline</span> By Day
                            </button>
                            <button id="view-grid-btn" onclick="setViewMode('grid')" class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[15px]">grid_view</span> 7-Day Grid
                            </button>
                            <button id="view-today-btn" onclick="filterToday()" title="Show only today's classes" class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[15px]">today</span> Today
                            </button>
                        </div>

                        <!-- Print Button -->
                        <button onclick="window.print()" class="px-3.5 py-1.5 bg-primary text-on-primary hover:opacity-90 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm">
                            <span class="material-symbols-outlined text-[15px]">print</span> <span class="hidden sm:inline">Print</span>
                        </button>
                    </div>
                </div>

                <!-- Irregular-student schedule conflict warning -->
                <div id="schedule-conflict-banner" class="hidden rounded-2xl border border-status-warning/40 bg-status-warning/10 p-4 flex items-start gap-3">
                    <span class="material-symbols-outlined text-status-warning text-[22px] shrink-0">warning</span>
                    <div class="text-xs">
                        <p class="font-bold text-on-surface">Schedule overlap detected:</p>
                        <ul id="schedule-conflict-list" class="list-disc pl-5 mt-1 space-y-0.5 text-on-surface-variant"></ul>
                    </div>
                </div>

                <!-- 1. RESPONSIVE TABLE & CARDS VIEW (DEFAULT - 100% FIT ON ALL SCREENS) -->
                <div id="table-view-container" class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden w-full max-w-full">
                    <div class="p-3.5 sm:p-4 px-4 sm:px-6 border-b border-outline-variant bg-surface-subtle flex flex-wrap justify-between items-center gap-2 no-print">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[18px]">calendar_view_week</span>
                            <h3 class="font-bold text-primary text-xs sm:text-sm">Enrolled Subject Offerings</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-primary px-2.5 py-1 rounded-lg bg-surface border border-outline-variant" id="total-units-badge">Total: <?= number_format($totalUnits, 1) ?> Units (<?= count($myClasses) ?> Subjects)</span>
                        </div>
                    </div>

                    <div class="sched-table-wrapper w-full max-w-full overflow-hidden">
                        <table class="sched-adaptive-table w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="bg-surface-container-low font-mono uppercase text-on-surface border-b border-outline-variant text-[11px]">
                                    <th class="py-3 px-3.5 font-bold w-[12%]">Section</th>
                                    <th class="py-3 px-3.5 font-bold w-[13%]">Code</th>
                                    <th class="py-3 px-3.5 font-bold w-[30%]">Subject Description</th>
                                    <th class="py-3 px-3.5 font-bold w-[10%]">Day</th>
                                    <th class="py-3 px-3.5 font-bold w-[18%]">Time</th>
                                    <th class="py-3 px-3.5 font-bold w-[20%]">Professor & Contact</th>
                                    <th class="py-3 px-3.5 font-bold text-right w-[7%]">Units</th>
                                </tr>
                            </thead>
                            <tbody id="schedule-table-tbody" class="divide-y divide-outline-variant/30 font-medium">
                                <?php if (!empty($myClasses)): ?>
                                    <?php foreach ($myClasses as $c): 
                                        $cSec = htmlspecialchars($c['section'] ?? $assigned_section);
                                        $cCode = htmlspecialchars($c['code'] ?? '');
                                        $cTitle = htmlspecialchars($c['title'] ?? '');
                                        $cDayAbbr = htmlspecialchars($c['schedule_day'] ?? 'TBA');
                                        $cDayFull = htmlspecialchars(getFullDayName($c['schedule_day'] ?? 'TBA'));
                                        $cTime = htmlspecialchars($c['start_time'] ?? 'TBA') . (!empty($c['end_time']) && $c['end_time'] !== 'TBA' ? ' - ' . htmlspecialchars($c['end_time']) : '');
                                        $cProf = htmlspecialchars($c['instructor'] ?? 'TBA');
                                        $cEmail = htmlspecialchars($c['instructor_email'] ?? '');
                                        $cUnits = number_format(floatval($c['units'] ?? 3.0), 1);
                                        $dataDay = htmlspecialchars(strval($c['schedule_day'] ?? ''));

                                        // Badge color for days
                                        $dayColorClass = 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/20';
                                        if (in_array(strtoupper($cDayAbbr), ['T', 'TUE', 'TUESDAY'])) {
                                            $dayColorClass = 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20';
                                        } elseif (in_array(strtoupper($cDayAbbr), ['TH', 'THU', 'THURSDAY'])) {
                                            $dayColorClass = 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20';
                                        } elseif (in_array(strtoupper($cDayAbbr), ['F', 'FRI', 'FRIDAY'])) {
                                            $dayColorClass = 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/20';
                                        } elseif (in_array(strtoupper($cDayAbbr), ['S', 'SAT', 'SATURDAY'])) {
                                            $dayColorClass = 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/20';
                                        }
                                    ?>
                                    <tr class="hover:bg-surface-subtle transition-colors" data-day="<?= $dataDay ?>" data-full-day="<?= $cDayFull ?>">
                                        <td class="py-3 px-3.5 font-mono font-bold text-primary" data-label="Section">
                                            <span class="px-2 py-0.5 rounded bg-primary/10 text-primary font-mono text-[11px] font-bold"><?= $cSec ?></span>
                                        </td>
                                        <td class="py-3 px-3.5 font-mono font-bold text-primary" data-label="Subject Code">
                                            <span class="font-mono font-bold text-primary text-xs"><?= $cCode ?></span>
                                        </td>
                                        <td class="py-3 px-3.5 font-semibold text-on-surface subject-title-cell" data-label="Description">
                                            <span class="font-bold text-on-surface text-xs leading-snug break-words"><?= $cTitle ?></span>
                                        </td>
                                        <td class="py-3 px-3.5 font-mono" data-label="Day">
                                            <span class="px-2.5 py-1 rounded-md font-bold text-[11px] border <?= $dayColorClass ?>"><?= $cDayFull ?></span>
                                        </td>
                                        <td class="py-3 px-3.5 font-mono text-primary font-bold time-cell" data-label="Time">
                                            <div class="inline-flex items-center gap-1.5 text-xs text-primary font-semibold">
                                                <span class="material-symbols-outlined text-[13px] text-primary/70">schedule</span>
                                                <span><?= $cTime ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3.5 text-on-surface-variant" data-label="Professor">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-xs text-on-surface truncate"><?= $cProf ?></span>
                                                <?php if (!empty($cEmail)): ?>
                                                    <a href="mailto:<?= $cEmail ?>" class="text-[11px] text-primary/80 hover:text-primary hover:underline truncate max-w-[210px] inline-flex items-center gap-1" title="<?= $cEmail ?>">
                                                        <span class="material-symbols-outlined text-[12px]">mail</span>
                                                        <span class="truncate"><?= $cEmail ?></span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3.5 text-right font-mono font-bold" data-label="Units">
                                            <span class="px-2 py-0.5 rounded bg-surface border border-outline-variant font-mono font-bold text-xs text-primary"><?= $cUnits ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="p-8 text-center text-on-surface-variant">No subjects assigned for your section yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="border-t-2 border-outline-variant bg-surface-subtle font-bold">
                                <tr>
                                    <td colspan="6" class="py-3 px-3.5 text-right uppercase font-mono text-xs text-on-surface-variant">Total Academic Units:</td>
                                    <td class="py-3 px-3.5 text-right font-mono font-bold text-primary text-xs" id="table-total-units"><?= number_format($totalUnits, 1) ?> Units</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- 2. DAY-BY-DAY TIMELINE VIEW (PERFECT COMPACT FIT ON ANY PHONE/TABLET) -->
                <div id="timeline-view-container" class="hidden space-y-4 w-full max-w-full">
                    <!-- Day Filter Chips -->
                    <div class="flex flex-wrap gap-1.5 p-1 bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                        <button onclick="filterTimelineDay('ALL')" id="tday-btn-ALL" class="tday-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-white transition-all shadow-sm">All Days (<?= count($myClasses) ?>)</button>
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayKey): 
                            if (!empty($classesByDay[$dayKey])):
                        ?>
                            <button onclick="filterTimelineDay('<?= $dayKey ?>')" id="tday-btn-<?= $dayKey ?>" class="tday-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary transition-all">
                                <?= $dayKey ?> (<?= count($classesByDay[$dayKey]) ?>)
                            </button>
                        <?php endif; endforeach; ?>
                    </div>

                    <!-- Day Group Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="timeline-groups-wrapper">
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayKey): 
                            if (!empty($classesByDay[$dayKey])):
                                $dayClasses = $classesByDay[$dayKey];
                        ?>
                            <div class="timeline-day-block bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex flex-col justify-between" data-day-group="<?= $dayKey ?>">
                                <div>
                                    <div class="flex items-center justify-between border-b border-outline-variant/60 pb-2.5 mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-primary text-[18px]">today</span>
                                            <h4 class="font-bold text-sm text-primary uppercase tracking-wide"><?= $dayKey ?></h4>
                                        </div>
                                        <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary"><?= count($dayClasses) ?> <?= count($dayClasses) === 1 ? 'Class' : 'Classes' ?></span>
                                    </div>

                                    <div class="space-y-3">
                                        <?php foreach ($dayClasses as $dc): 
                                            $dcTime = htmlspecialchars($dc['start_time'] ?? 'TBA') . (!empty($dc['end_time']) && $dc['end_time'] !== 'TBA' ? ' - ' . htmlspecialchars($dc['end_time']) : '');
                                        ?>
                                            <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/50 hover:border-primary/40 transition-all space-y-2">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-mono font-bold text-xs text-primary"><?= htmlspecialchars($dc['code'] ?? '') ?></span>
                                                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-surface border border-outline-variant/60"><?= number_format(floatval($dc['units'] ?? 3.0), 1) ?> Units</span>
                                                </div>
                                                <h5 class="font-bold text-xs text-on-surface line-clamp-2 leading-snug"><?= htmlspecialchars($dc['title'] ?? '') ?></h5>
                                                <div class="flex items-center gap-1.5 text-[11px] font-mono text-primary font-semibold">
                                                    <span class="material-symbols-outlined text-[13px]">schedule</span>
                                                    <span><?= $dcTime ?></span>
                                                </div>
                                                <div class="text-[11px] text-on-surface-variant flex items-center justify-between border-t border-outline-variant/40 pt-1.5">
                                                    <span class="truncate max-w-[140px] font-medium" title="<?= htmlspecialchars($dc['instructor'] ?? '') ?>"><?= htmlspecialchars($dc['instructor'] ?? 'Faculty') ?></span>
                                                    <?php if (!empty($dc['instructor_email'])): ?>
                                                        <a href="mailto:<?= htmlspecialchars($dc['instructor_email']) ?>" class="text-primary hover:underline text-[10px] flex items-center gap-0.5" title="<?= htmlspecialchars($dc['instructor_email']) ?>">
                                                            <span class="material-symbols-outlined text-[12px]">mail</span> Mail
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <!-- 3. TIMETABLE GRID VIEW (7 DAYS: SUNDAY TO SATURDAY) -->
                <div id="grid-view-container" class="hidden bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col flex-1 w-full max-w-full">
                    <div class="p-3 sm:p-4 px-4 sm:px-6 border-b border-outline-variant bg-surface-subtle flex justify-between items-center no-print">
                        <h3 class="font-bold text-primary text-xs sm:text-sm flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">calendar_view_month</span>
                            Weekly Visual Timetable (7-Day Grid)
                        </h3>
                        <span class="text-[11px] text-on-surface-variant font-mono">Horizontal scroll enabled for desktop grid view</span>
                    </div>

                    <div class="overflow-x-auto w-full">
                        <div class="min-w-[960px]">
                            <!-- Grid Headers -->
                            <div class="timetable-7day-grid border-b border-outline-variant">
                                <div class="timetable-header border-r border-outline-variant/50">TIME</div>
                                <div class="timetable-header">SUNDAY</div>
                                <div class="timetable-header">MONDAY</div>
                                <div class="timetable-header">TUESDAY</div>
                                <div class="timetable-header">WEDNESDAY</div>
                                <div class="timetable-header">THURSDAY</div>
                                <div class="timetable-header">FRIDAY</div>
                                <div class="timetable-header bg-primary/5 text-primary">SATURDAY</div>
                            </div>

                            <!-- Grid Time Slots -->
                            <div id="timetable-body">
                                <?php 
                                $slots = [
                                    '07:00 AM' => '0700',
                                    '08:00 AM' => '0800',
                                    '09:00 AM' => '0900',
                                    '10:00 AM' => '1000',
                                    '11:00 AM' => '1100',
                                    '12:00 PM' => '1200',
                                    '01:00 PM' => '1300',
                                    '02:00 PM' => '1400',
                                    '03:00 PM' => '1500',
                                    '04:00 PM' => '1600',
                                    '05:00 PM' => '1700',
                                    '06:00 PM' => '1800',
                                    '07:00 PM' => '1900',
                                    '08:00 PM' => '2000'
                                ];
                                foreach ($slots as $label => $key): 
                                    if ($label === '12:00 PM'): ?>
                                        <div class="timetable-7day-grid bg-surface-subtle/80">
                                            <div class="timetable-cell bg-surface-subtle text-right text-[11px] font-mono font-bold text-on-surface-variant border-r border-outline-variant/50 flex flex-col justify-start pt-2 pr-1.5">12:00 PM</div>
                                            <div class="col-span-7 flex items-center justify-center border-t border-b border-outline-variant/30 text-xs font-mono text-on-surface-variant uppercase tracking-widest py-2 bg-surface-container-low/40">
                                                ☕ Midday Break
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="timetable-7day-grid">
                                            <div class="timetable-cell bg-surface-subtle text-right text-[11px] font-mono font-bold text-on-surface-variant border-r border-outline-variant/50 flex flex-col justify-start pt-2 pr-1.5"><?= $label ?></div>
                                            <div class="timetable-cell" id="slot-Sunday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Monday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Tuesday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Wednesday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Thursday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Friday-<?= $key ?>"></div>
                                            <div class="timetable-cell" id="slot-Saturday-<?= $key ?>"></div>
                                        </div>
                                    <?php endif; 
                                endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TBA / Arranged Subjects Card Section -->
                <div id="tba-subjects-container" class="hidden bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-6 shadow-sm no-print w-full">
                    <h3 class="font-bold text-primary text-xs sm:text-sm mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[18px]">schedule</span>
                        TBA / Arranged Schedule Subjects
                    </h3>
                    <div id="tba-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
                </div>

                <!-- 🖨️ PRINT-ONLY OFFICIAL FOOTER -->
                <div id="print-footer">
                    <div class="flex justify-between items-center text-xs mt-6 pt-4 border-t border-gray-400">
                        <div>
                            <p>Certified by: <strong>Office of Academic Affairs & Registrar</strong></p>
                            <p class="text-[9px] text-gray-500 mt-1">This document serves as the official class schedule for the stated academic term.</p>
                        </div>
                        <div class="text-right">
                            <p>Registrar Signature: _______________________</p>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Client-Side Schedule Logic & Dynamic Interactions -->
    <script>
    (function () {
        var currentStudentSection = <?= json_encode($assigned_section) ?>;
        var currentStudentEmail = <?= json_encode($user_email) ?>;
        var myClasses = <?= json_encode($myClasses) ?>;

        function escA(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }
        window.escA = escA;

        async function initStudentSchedule() {
            try {
                if (myClasses && myClasses.length > 0) {
                    renderGrid(myClasses);
                    renderTba(myClasses);
                    detectScheduleConflicts(myClasses);
                }

                // Background revalidation
                if (window.supabase && typeof window.supabase.createClient === 'function') {
                    const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
                    const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
                    const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);

                    if (currentStudentEmail) {
                        const { data: userProfile } = await supabaseClient.from('users').select('program, section').eq('email', currentStudentEmail).single();
                        if (userProfile && userProfile.program && userProfile.section) {
                            const sec = `${userProfile.program} ${userProfile.section}`.trim();
                            const pill = document.getElementById('student-section-pill');
                            if (pill) pill.innerText = sec;
                            const printPill = document.getElementById('print-section-display');
                            if (printPill) printPill.innerText = sec;
                        }
                    }
                }
            } catch (err) {
                console.warn('Schedule background sync notice:', err);
            }
        }

        /* Conflict Detector */
        function detectScheduleConflicts(classes) {
            const banner = document.getElementById('schedule-conflict-banner');
            if (!banner) return;
            const byDay = {};
            classes.forEach(c => {
                if (!c.schedule_day || String(c.schedule_day).toUpperCase() === 'TBA') return;
                const day = String(c.schedule_day).toUpperCase();
                (byDay[day] = byDay[day] || []).push(c);
            });
            const conflicts = [];
            Object.entries(byDay).forEach(([day, list]) => {
                for (let i = 0; i < list.length; i++) {
                    for (let j = i + 1; j < list.length; j++) {
                        const a = list[i], b = list[j];
                        const toMin = t => { const [h, m] = String(t || '').split(':').map(Number); return (h || 0) * 60 + (m || 0); };
                        const aS = toMin(a.start_time), aE = toMin(a.end_time);
                        const bS = toMin(b.start_time), bE = toMin(b.end_time);
                        if (aS < bE && bS < aE) {
                            conflicts.push(`${day}: ${a.code || a.subject_code} (${a.start_time}–${a.end_time}) overlaps ${b.code || b.subject_code} (${b.start_time}–${b.end_time})`);
                        }
                    }
                }
            });
            if (conflicts.length) {
                banner.classList.remove('hidden');
                document.getElementById('schedule-conflict-list').innerHTML =
                    conflicts.map(c => `<li>${escA(c)}</li>`).join('');
            }
        }

        /* Show only today's classes */
        window.filterToday = function filterToday() {
            const btn = document.getElementById('view-today-btn');
            if (!btn) return;
            const active = btn.classList.toggle('ring-2');
            btn.classList.toggle('bg-primary/20', active);

            const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const todayName = dayNames[new Date().getDay()];

            // Filter in Table View
            const rows = document.querySelectorAll('#schedule-table-tbody tr[data-day]');
            rows.forEach(r => {
                const fullDay = r.getAttribute('data-full-day') || '';
                r.classList.toggle('hidden', active && fullDay.toUpperCase() !== todayName.toUpperCase());
            });

            // Filter in Timeline View
            const timelineBlocks = document.querySelectorAll('.timeline-day-block');
            timelineBlocks.forEach(b => {
                const bDay = b.getAttribute('data-day-group') || '';
                b.classList.toggle('hidden', active && bDay.toUpperCase() !== todayName.toUpperCase());
            });

            const icon = btn.querySelector('span');
            if (icon) icon.textContent = active ? 'view_week' : 'today';
        };

        /* View Mode Switcher: Table, Timeline, Grid */
        window.setViewMode = function setViewMode(mode) {
            const tableContainer = document.getElementById('table-view-container');
            const timelineContainer = document.getElementById('timeline-view-container');
            const gridContainer = document.getElementById('grid-view-container');

            const tableBtn = document.getElementById('view-table-btn');
            const timelineBtn = document.getElementById('view-timeline-btn');
            const gridBtn = document.getElementById('view-grid-btn');

            if (!tableContainer || !timelineContainer || !gridContainer) return;

            // Reset visibility
            tableContainer.classList.add('hidden');
            timelineContainer.classList.add('hidden');
            gridContainer.classList.add('hidden');

            const inactiveBtnClass = 'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary flex items-center gap-1 transition-all';
            const activeBtnClass = 'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white flex items-center gap-1 shadow-sm transition-all';

            if (tableBtn) tableBtn.className = inactiveBtnClass;
            if (timelineBtn) timelineBtn.className = inactiveBtnClass;
            if (gridBtn) gridBtn.className = inactiveBtnClass;

            if (mode === 'timeline') {
                timelineContainer.classList.remove('hidden');
                if (timelineBtn) timelineBtn.className = activeBtnClass;
            } else if (mode === 'grid') {
                gridContainer.classList.remove('hidden');
                if (gridBtn) gridBtn.className = activeBtnClass;
            } else {
                tableContainer.classList.remove('hidden');
                if (tableBtn) tableBtn.className = activeBtnClass;
            }
        };

        /* Filter timeline day chips */
        window.filterTimelineDay = function filterTimelineDay(selectedDay) {
            const btns = document.querySelectorAll('.tday-btn');
            btns.forEach(b => {
                b.className = 'tday-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant hover:text-primary transition-all';
            });
            const activeBtn = document.getElementById('tday-btn-' + selectedDay);
            if (activeBtn) {
                activeBtn.className = 'tday-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-white transition-all shadow-sm';
            }

            const blocks = document.querySelectorAll('.timeline-day-block');
            blocks.forEach(block => {
                if (selectedDay === 'ALL') {
                    block.classList.remove('hidden');
                } else {
                    const d = block.getAttribute('data-day-group');
                    block.classList.toggle('hidden', d !== selectedDay);
                }
            });
        };

        /* Resolve day abbreviation to full day name for grid slot IDs */
        function resolveDay(abbr) {
            if (!abbr) return '';
            const map = {
                'M': 'Monday', 'T': 'Tuesday', 'W': 'Wednesday',
                'Th': 'Thursday', 'F': 'Friday', 'S': 'Saturday',
                'Su': 'Sunday'
            };
            const fullNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            for (const full of fullNames) {
                if (abbr.toLowerCase() === full.toLowerCase()) return full;
            }
            if (abbr.trim().toUpperCase() === 'TH') return 'Thursday';
            return map[abbr.trim()] || abbr;
        }

        /* Parse a 12-hour time like "05:30 PM" to 24h slot key like "1700" */
        function timeToSlot(timeStr) {
            if (!timeStr) return '0800';
            const m = timeStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
            if (!m) return '0800';
            let h = parseInt(m[1], 10);
            const ampm = (m[3] || '').toUpperCase();
            if (ampm === 'PM' && h < 12) h += 12;
            if (ampm === 'AM' && h === 12) h = 0;
            return String(h).padStart(2, '0') + '00';
        }

        function renderGrid(classesToRender) {
            const slots = document.querySelectorAll('.timetable-cell[id^="slot-"]');
            slots.forEach(slot => slot.innerHTML = '');

            classesToRender.forEach(c => {
                const day = c.schedule_day || '';
                if (day.toUpperCase() === 'TBA') return;

                const targetDay = resolveDay(day);
                if (!targetDay) return;

                const timeSlot = timeToSlot(c.start_time);
                const targetSlot = document.getElementById(`slot-${targetDay}-${timeSlot}`);

                let blockClass = 'event-lecture';
                if ((c.title && c.title.toLowerCase().includes('lab')) || (c.room && c.room.toLowerCase().includes('lab'))) {
                    blockClass = 'event-lab';
                } else if (parseInt(timeSlot, 10) >= 1700) {
                    blockClass = 'event-evening';
                }

                if (targetSlot) {
                    const card = document.createElement('div');
                    card.className = `${blockClass} rounded-lg p-2 shadow-sm hover:shadow-md transition-shadow mb-1 flex flex-col justify-between cursor-pointer`;
                    card.innerHTML = `
                        <div>
                            <span class="font-mono text-xs font-bold text-primary">${c.code}</span>
                            <p class="text-[11px] font-bold text-primary line-clamp-1 leading-tight mb-1" title="${c.title}">${c.title}</p>
                        </div>
                        <div class="pt-1 border-t border-black/5 text-[9px] text-on-surface-variant font-mono space-y-0.5">
                            <div class="flex items-center gap-1 font-semibold text-primary">
                                <span class="material-symbols-outlined text-[10px]">schedule</span>
                                <span>${c.start_time || ''}${c.end_time ? ' - ' + c.end_time : ''}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>${c.instructor || 'Faculty'}</span>
                                <span>${c.room || 'TBA'}</span>
                            </div>
                        </div>
                    `;
                    targetSlot.appendChild(card);
                }
            });
        }

        function renderTba(classesToRender) {
            const tbaContainer = document.getElementById('tba-subjects-container');
            const tbaGrid = document.getElementById('tba-grid');

            const tbaList = classesToRender.filter(c => (c.schedule_day && c.schedule_day.toUpperCase() === 'TBA') || (c.start_time && c.start_time.toUpperCase() === 'TBA'));

            if (tbaList.length === 0) {
                tbaContainer.classList.add('hidden');
                return;
            }

            tbaContainer.classList.remove('hidden');
            tbaGrid.innerHTML = tbaList.map(c => `
                <div class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-4 flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 bg-primary/10 text-primary font-mono text-xs font-bold rounded">${c.code}</span>
                        <span class="text-[10px] font-mono font-bold bg-surface px-2 py-0.5 rounded border">${c.section || 'AIS 2A'}</span>
                    </div>
                    <h4 class="font-bold text-sm text-primary mb-2">${c.title}</h4>
                    <div class="text-xs text-on-surface-variant space-y-1">
                        <div class="flex items-center gap-1 font-semibold text-secondary"><span class="material-symbols-outlined text-[14px]">info</span> Schedule to be arranged</div>
                        <div class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> ${c.instructor || 'Faculty Staff'}</div>
                    </div>
                </div>
            `).join('');
        }

        initStudentSchedule();
    })();
    </script>
</body>
</html>
