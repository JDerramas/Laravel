<?php
require_once __DIR__ . '/../includes/auth.php';
require_teacher();
$teacher_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Faculty Professor';
$teacher_initial = strtoupper(substr($teacher_name, 0, 1));
$teacher_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar');
$selected_class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$csrf_token = getCsrfToken();
$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Faculty Assessment Studio - Navotas Polytechnic College</title>
    <!-- Pre-paint theme: apply saved night-mode before first paint -->
    <script>
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
                        "secondary": "rgb(var(--secondary-rgb) / <alpha-value>)",
                        "secondary-container": "rgb(var(--secondary-container-rgb) / <alpha-value>)",
                        "on-secondary": "rgb(var(--on-secondary-rgb) / <alpha-value>)",
                        "on-secondary-container": "rgb(var(--on-secondary-container-rgb) / <alpha-value>)",
                        "background": "rgb(var(--background-rgb) / <alpha-value>)",
                        "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
                        "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
                        "surface-variant": "rgb(var(--surface-variant-rgb) / <alpha-value>)",
                        "surface-container-lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
                        "surface-container-low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
                        "surface-container": "rgb(var(--surface-container-rgb) / <alpha-value>)",
                        "surface-container-high": "rgb(var(--surface-container-high-rgb) / <alpha-value>)",
                        "on-surface": "rgb(var(--on-surface-rgb) / <alpha-value>)",
                        "on-surface-variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
                        "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
                        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
                        "status-info": "rgb(var(--status-info-rgb) / <alpha-value>)",
                        "status-success": "rgb(var(--status-success-rgb) / <alpha-value>)",
                        "status-warning": "rgb(var(--status-warning-rgb) / <alpha-value>)",
                        "error": "rgb(var(--error-rgb) / <alpha-value>)",
                        "on-error": "rgb(var(--on-error-rgb) / <alpha-value>)",
                        "error-container": "rgb(var(--error-container-rgb) / <alpha-value>)"
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
        /* Faculty Assessment Studio Styling */
        .fas-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .fas-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .fas-scrollbar::-webkit-scrollbar-thumb { background: rgb(var(--outline-variant-rgb)); border-radius: 9999px; }
        .fas-scrollbar::-webkit-scrollbar-thumb:hover { background: rgb(var(--primary-rgb) / 0.5); }

        /* Topbar cleanup: hide Search and role dropdown since sidebar already has portal switch */
        #npc-search-hint,
        #npc-portal-switch {
            display: none !important;
        }

        .comp-card-active {
            border-color: rgb(var(--primary-rgb));
            background: rgb(var(--primary-rgb) / 0.06);
            box-shadow: 0 0 0 2px rgb(var(--primary-rgb) / 0.2);
        }

        .student-item-active {
            border-color: rgb(var(--primary-rgb)) !important;
            background: rgb(var(--primary-rgb) / 0.08) !important;
            box-shadow: 0 2px 10px rgb(var(--primary-rgb) / 0.12);
        }

        @media print {
            aside, #topbar, #assessment-navigator, #queue-pane, #insights-pane, #more-actions-menu, .no-print { display: none !important; }
            #main-wrapper, main { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
            #print-official-sheet { display: block !important; }
            body { background: #fff !important; color: #000 !important; }
        }
        #print-official-sheet { display: none; }
    </style>
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'teacher', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'faculty'; include __DIR__ . '/../includes/_sidebar.php'; ?>

    <!-- Main Studio Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">
        
        <!-- Top Context Header -->
        <header class="min-h-16 h-auto py-2.5 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="text-base sm:text-lg font-bold text-primary lg:hidden truncate">NPC Faculty</span>
                <div class="hidden sm:flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm md:text-base font-bold text-primary leading-tight">Faculty Assessment Studio</h1>
                        </div>
                        <p class="text-[11px] font-mono text-on-surface-variant hidden md:block">Learner Evaluation & Academic Gradebook Workspace</p>
                    </div>
                </div>
            </div>

            <!-- Right Header Controls: Class Switcher & Teacher Badge -->
            <div class="flex items-center gap-2 md:gap-3 shrink-0">
                <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px]">map</span>
                    <span class="hidden sm:inline">NPC Map</span>
                </a>
                <!-- Class Switcher -->
                <div class="flex items-center gap-1.5">
                    <label for="active-class-select" class="text-xs font-bold text-on-surface-variant font-mono hidden xl:block">CLASS:</label>
                    <select id="active-class-select" onchange="onClassDropdownChange()" class="bg-surface-container-low border border-outline-variant text-xs font-bold rounded-xl px-3 py-1.5 text-primary focus:ring-2 focus:ring-primary focus:outline-none min-w-[180px] max-w-[260px] truncate">
                        <option value="">Loading assigned classes...</option>
                    </select>
                    <span id="privacy-badge-pill" class="hidden md:inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 shadow-2xs" title="Faculty Grade Privacy Protected: Only your assigned subjects are visible">
                        <span class="material-symbols-outlined text-[13px]">lock</span>
                        <span id="privacy-badge-text">Privacy Scoped</span>
                    </span>
                </div>

                <!-- Save State Indicator -->
                <div id="save-status-pill" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-surface-container text-on-surface-variant border border-outline-variant">
                    <span class="w-2 h-2 rounded-full bg-status-success" id="save-status-dot"></span>
                    <span id="save-status-text">All changes saved</span>
                </div>

                <!-- More Actions Menu Dropdown -->
                <div class="relative">
                    <button onclick="toggleMoreActionsMenu()" id="btn-more-actions" class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-xl border border-outline-variant transition-colors" title="More Actions">
                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                    </button>
                    <div id="more-actions-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xl z-50 py-1.5 text-xs text-on-surface space-y-0.5">
                        <button onclick="openSchemeEditor(); toggleMoreActionsMenu(false);" class="w-full text-left px-3.5 py-2 hover:bg-surface-container flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-primary">tune</span> Edit Grading Scheme
                        </button>
                        <button onclick="openRapidScoringModal(); toggleMoreActionsMenu(false);" class="w-full text-left px-3.5 py-2 hover:bg-surface-container flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-secondary">bolt</span> Rapid Scoring Mode
                        </button>
                        <hr class="border-outline-variant/60 my-1">
                        <button onclick="printOfficialGradeSheet(); toggleMoreActionsMenu(false);" class="w-full text-left px-3.5 py-2 hover:bg-surface-container flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">print</span> Print Official Grade Sheet
                        </button>
                        <button onclick="exportGradeSheetCsv(); toggleMoreActionsMenu(false);" class="w-full text-left px-3.5 py-2 hover:bg-surface-container flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">description</span> Export CSV Archive
                        </button>
                        <?php if ($is_admin): ?>
                        <hr class="border-outline-variant/60 my-1">
                        <button onclick="toggleAdminScope(); toggleMoreActionsMenu(false);" id="btn-admin-scope-toggle" class="w-full text-left px-3.5 py-2 hover:bg-surface-container flex items-center gap-2 text-primary font-semibold">
                            <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                            <span id="admin-scope-toggle-label">View All Classes (Admin Mode)</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teacher Profile Chip -->
                <div class="flex items-center gap-2 pl-2 border-l border-outline-variant/60">
                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        <?= $teacher_initial ?>
                    </div>
                    <span class="text-xs font-semibold text-primary hidden lg:inline truncate max-w-[130px]"><?= htmlspecialchars($teacher_name) ?></span>
                </div>
            </div>
        </header>

        <!-- Main Studio Body -->
        <main class="flex-1 flex flex-col p-3 md:p-5 gap-4 max-w-[1720px] w-full mx-auto" id="studio-main-canvas">
            
            <!-- Section A: Class Banner & Lifecycle Status Card -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 md:p-5 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 bg-primary text-white font-mono text-xs font-bold rounded-lg shadow-sm" id="banner-code">—</span>
                        <span class="px-2.5 py-0.5 bg-surface-container text-primary font-mono text-xs font-bold rounded-lg border border-outline-variant" id="banner-section">—</span>
                        <!-- Lifecycle Status Badge -->
                        <span id="grade-sheet-status-badge" class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300/40">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Draft Mode (Editable)
                        </span>
                        <!-- Saved Time Tag -->
                        <span id="last-saved-timestamp" class="text-[11px] font-mono text-on-surface-variant">Never saved</span>
                    </div>
                    <h2 class="text-lg md:text-xl font-bold text-primary tracking-tight" id="banner-title">Loading Class Gradebook...</h2>
                    <div class="flex items-center gap-4 text-xs text-on-surface-variant font-medium flex-wrap">
                        <span>Instructor: <strong class="text-on-surface" id="banner-prof"><?= htmlspecialchars($teacher_name) ?></strong></span>
                        <span>•</span>
                        <span><strong class="text-on-surface" id="banner-enrolled-count">0</strong> Students Enrolled</span>
                        <span>•</span>
                        <span>Term: <strong class="text-on-surface">1st Sem A.Y. 2026-2027</strong></span>
                    </div>
                </div>

                <!-- Studio Primary Actions & Status Summary -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Progress summary chips -->
                    <div class="hidden xl:flex items-center gap-3 pr-2 border-r border-outline-variant/60">
                        <div class="text-right">
                            <p class="text-[10px] font-mono text-on-surface-variant uppercase font-bold">Evaluated</p>
                            <p class="text-xs font-mono font-extrabold text-primary" id="kpi-evaluated-ratio">0 / 0 (0%)</p>
                        </div>
                        <div class="w-20 h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div id="kpi-progress-bar" class="h-full bg-primary rounded-full transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>

                    <button onclick="openRapidScoringModal()" id="btn-rapid-mode" class="px-3.5 py-2 inline-flex items-center gap-1.5 bg-surface-container hover:bg-surface-container-high text-primary border border-outline-variant rounded-xl text-xs font-bold transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[17px] text-secondary">bolt</span> Rapid Scoring
                    </button>

                    <button onclick="saveDraftGradesToServer()" id="btn-save-draft" title="Save Draft (Ctrl+S)" class="px-3.5 py-2 inline-flex items-center gap-1.5 bg-surface-container hover:bg-surface-container-high text-primary border border-outline-variant rounded-xl text-xs font-bold transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[17px]">save</span> Save Draft
                    </button>

                    <button onclick="submitGradeSheetForReview()" id="btn-submit-grades" title="Submit to Registrar for Official Approval" class="px-4 py-2 inline-flex items-center gap-1.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[17px]">send</span> Submit to Registrar
                    </button>

                    <button onclick="openGradeChangeModal()" id="btn-request-change" title="Submit Official Grade Change Request" class="hidden px-4 py-2 inline-flex items-center gap-1.5 bg-secondary text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[17px]">edit_document</span> Request Grade Change
                    </button>
                </div>
            </section>

            <!-- Section B: Assessment Navigator (Period Segmented Control & Component Cards) -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-sm space-y-3" id="assessment-navigator">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant pb-3">
                    <!-- Period Tabs -->
                    <div class="bg-surface-container p-1 rounded-xl flex items-center gap-1 overflow-x-auto" id="period-tabs-container">
                        <button onclick="selectGradingPeriod('overall')" id="period-tab-overall" class="px-4 py-1.5 rounded-lg text-xs font-bold text-primary transition-all flex items-center gap-1.5 shadow-sm bg-surface-container-lowest">
                            <span class="material-symbols-outlined text-[15px]">table_chart</span>
                            <span>Overall Grade Sheet</span>
                        </button>
                        <button onclick="selectGradingPeriod('prelim')" id="period-tab-prelim" class="px-4 py-1.5 rounded-lg text-xs font-bold text-on-surface-variant hover:text-primary transition-all flex items-center gap-1.5">
                            <span>Prelim</span>
                            <span class="px-1.5 py-0.2 rounded-md bg-primary/10 text-primary text-[10px] font-mono" id="tab-weight-prelim">30%</span>
                        </button>
                        <button onclick="selectGradingPeriod('midterm')" id="period-tab-midterm" class="px-4 py-1.5 rounded-lg text-xs font-bold text-on-surface-variant hover:text-primary transition-all flex items-center gap-1.5">
                            <span>Midterm</span>
                            <span class="px-1.5 py-0.2 rounded-md bg-surface-container-high text-on-surface-variant text-[10px] font-mono" id="tab-weight-midterm">30%</span>
                        </button>
                        <button onclick="selectGradingPeriod('final')" id="period-tab-final" class="px-4 py-1.5 rounded-lg text-xs font-bold text-on-surface-variant hover:text-primary transition-all flex items-center gap-1.5">
                            <span>Final</span>
                            <span class="px-1.5 py-0.2 rounded-md bg-surface-container-high text-on-surface-variant text-[10px] font-mono" id="tab-weight-final">40%</span>
                        </button>
                    </div>

                    <!-- Scheme Configure Action -->
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-mono text-on-surface-variant" id="period-components-count">3 components (100%)</span>
                        <button onclick="openSchemeEditor()" class="px-3 py-1 bg-surface-container-low hover:bg-surface-container border border-outline-variant rounded-lg text-[11px] font-bold text-primary flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-[15px]">tune</span> Configure Scheme
                        </button>
                    </div>
                </div>

                <!-- Horizontal Component Cards Carousel / Chips -->
                <div class="flex items-center gap-3 overflow-x-auto pb-1 fas-scrollbar" id="components-carousel">
                    <!-- Populated dynamically: Cards for Quiz 1, Hands-on, Prelim Exam, etc. -->
                </div>
            </section>

            <!-- Scheme Editor Drawer / Modal Container -->
            <div id="scheme-editor-drawer" class="hidden bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-lg space-y-4 no-print transition-all"></div>

            <!-- Mobile Navigation Segmented Tabs (< md:) -->
            <div class="grid grid-cols-3 gap-1 bg-surface-container p-1 rounded-xl md:hidden" id="mobile-nav-tabs">
                <button onclick="switchMobileView('queue')" id="m-tab-queue" class="py-1.5 text-xs font-bold rounded-lg bg-surface-container-lowest text-primary shadow-sm text-center">
                    Students (<span id="m-queue-count">0</span>)
                </button>
                <button onclick="switchMobileView('eval')" id="m-tab-eval" class="py-1.5 text-xs font-bold rounded-lg text-on-surface-variant text-center">
                    Scoring Panel
                </button>
                <button onclick="switchMobileView('insights')" id="m-tab-insights" class="py-1.5 text-xs font-bold rounded-lg text-on-surface-variant text-center">
                    Review & Insights
                </button>
            </div>

            <!-- Section C, D, E: Three-Pane Desktop Studio Grid -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start flex-1" id="three-pane-container">
                
                <!-- LEFT PANE: Student Evaluation Queue (4 cols on md, 3.5 on xl) -->
                <div class="md:col-span-5 lg:col-span-4 xl:col-span-3 bg-surface-container-lowest border border-outline-variant rounded-2xl flex flex-col shadow-sm max-h-[750px] overflow-hidden" id="queue-pane">
                    <!-- Queue Header & Search -->
                    <div class="p-3.5 bg-surface-container-low border-b border-outline-variant space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-[18px]">group</span>
                                <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Evaluation Queue</h3>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-on-surface-variant" id="queue-count-badge">0 / 0</span>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[16px]">search</span>
                            <input type="text" id="queue-search-input" oninput="onQueueSearch(this.value)" placeholder="Search student name or #..." class="w-full pl-8 pr-3 py-1.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-medium text-primary focus:ring-1 focus:ring-primary focus:outline-none">
                        </div>

                        <!-- Filter Pills & Sorting -->
                        <div class="flex items-center justify-between gap-1">
                            <div class="flex items-center gap-1 overflow-x-auto fas-scrollbar pb-0.5" id="queue-filter-pills">
                                <button onclick="setQueueFilter('all')" class="q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-primary text-white" data-filter="all">All</button>
                                <button onclick="setQueueFilter('ungraded')" class="q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container text-on-surface-variant hover:text-primary" data-filter="ungraded">Unscored</button>
                                <button onclick="setQueueFilter('atrisk')" class="q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container text-on-surface-variant hover:text-primary" data-filter="atrisk">At Risk</button>
                                <button onclick="setQueueFilter('passed')" class="q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container text-on-surface-variant hover:text-primary" data-filter="passed">Passed</button>
                            </div>
                            <select id="queue-sort-select" onchange="onQueueSortChange(this.value)" class="bg-surface-container-lowest border border-outline-variant rounded-lg text-[10px] font-mono font-bold px-1.5 py-0.5 text-on-surface-variant">
                                <option value="alpha">A-Z</option>
                                <option value="score_asc">Lowest</option>
                                <option value="score_desc">Highest</option>
                                <option value="incomplete">Missing</option>
                            </select>
                        </div>
                    </div>

                    <!-- Student List Roster -->
                    <div class="flex-1 overflow-y-auto p-2 space-y-1.5 fas-scrollbar" id="queue-students-list">
                        <!-- Populated dynamically with student cards -->
                    </div>
                </div>

                <!-- CENTER PANE: Learner Evaluation Panel (Main Workspace: 7 cols on md, 5.5 on xl) -->
                <div class="md:col-span-7 lg:col-span-8 xl:col-span-6 bg-surface-container-lowest border border-outline-variant rounded-2xl flex flex-col shadow-sm overflow-hidden" id="eval-pane">
                    
                    <!-- Selected Student Profile Banner -->
                    <div class="p-4 bg-surface-container-low border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-3" id="eval-student-banner">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-primary text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-sm" id="eval-student-avatar">
                                ST
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-primary leading-tight" id="eval-student-name">Select a Student</h3>
                                <div class="flex items-center gap-2 text-xs font-mono text-on-surface-variant mt-0.5 flex-wrap">
                                    <span id="eval-student-number">#2024-0000</span>
                                    <span>•</span>
                                    <span id="eval-student-section">AIS 2A</span>
                                    <span>•</span>
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.2 rounded-md bg-surface-container font-mono text-on-surface-variant border border-outline-variant" id="eval-student-attendance">
                                        <span class="material-symbols-outlined text-[12px] text-status-success">done_all</span> 100% Att.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Computed Performance Badge for Student -->
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-[10px] font-mono text-on-surface-variant uppercase font-bold">Overall GPA</p>
                                <div class="flex items-center gap-1.5 justify-end">
                                    <span class="text-xl font-extrabold font-mono text-primary" id="eval-student-gpa">—</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-surface-container text-on-surface-variant" id="eval-student-remark">Ongoing</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Scoring Card -->
                    <div class="p-5 space-y-5" id="eval-scoring-card">
                        
                        <!-- Term Period Grades (Direct Encoding for Active Student) -->
                        <div class="bg-primary/5 rounded-2xl border border-primary/20 p-4 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[18px]">edit_note</span>
                                    <h4 class="text-xs font-bold text-primary uppercase tracking-wide">Term Period Grades (Direct Input)</h4>
                                </div>
                                <span class="text-[10px] font-mono text-on-surface-variant font-medium">Type 0–100% or use sub-assessments below</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5 text-center font-mono">
                                <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant space-y-1">
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase">Prelim (30%)</label>
                                    <input type="number" id="card-direct-prelim" min="0" max="100" step="0.1" placeholder="0.0"
                                           oninput="onDirectPeriodGradeInput(activeStudentSn(), 'prelim', this.value)"
                                           class="w-full text-center py-1.5 font-mono font-bold text-base bg-surface-container border border-outline-variant rounded-lg text-primary focus:ring-1 focus:ring-primary focus:border-primary">
                                </div>
                                <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant space-y-1">
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase">Midterm (30%)</label>
                                    <input type="number" id="card-direct-midterm" min="0" max="100" step="0.1" placeholder="0.0"
                                           oninput="onDirectPeriodGradeInput(activeStudentSn(), 'midterm', this.value)"
                                           class="w-full text-center py-1.5 font-mono font-bold text-base bg-surface-container border border-outline-variant rounded-lg text-primary focus:ring-1 focus:ring-primary focus:border-primary">
                                </div>
                                <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant space-y-1">
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase">Final (40%)</label>
                                    <input type="number" id="card-direct-final" min="0" max="100" step="0.1" placeholder="0.0"
                                           oninput="onDirectPeriodGradeInput(activeStudentSn(), 'final', this.value)"
                                           class="w-full text-center py-1.5 font-mono font-bold text-base bg-surface-container border border-outline-variant rounded-lg text-primary focus:ring-1 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Focus Title -->
                        <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-primary" id="scoring-dot"></span>
                                    <h4 class="text-sm font-bold text-primary uppercase tracking-wide" id="scoring-component-title">Quiz 1</h4>
                                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-[10px] font-mono font-bold" id="scoring-component-weight">20% of Prelim</span>
                                </div>
                                <span class="text-xs font-mono text-on-surface-variant" id="scoring-max-pts">Max: 20 pts</span>
                            </div>

                            <!-- Numeric Score Input with Validation -->
                            <div class="flex flex-col sm:flex-row items-center gap-3">
                                <div class="relative flex-1 w-full">
                                    <label for="active-score-input" class="sr-only">Student Score</label>
                                    <input type="number" id="active-score-input" min="0" max="100" step="0.5" placeholder="0"
                                           oninput="onScoreCardInput(this.value)"
                                           onkeydown="onScoreInputKeydown(event)"
                                           class="w-full text-center py-3 text-2xl font-black font-mono bg-surface-container-lowest border-2 border-outline-variant focus:border-primary rounded-2xl focus:ring-4 focus:ring-primary/10 text-primary transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 font-mono text-xs text-on-surface-variant font-bold" id="score-input-out-of">/ 20</span>
                                </div>

                                <!-- Quick Preset Steppers -->
                                <div class="flex items-center gap-1.5 w-full sm:w-auto shrink-0 justify-center">
                                    <button onclick="setScorePreset(0)" class="px-2.5 py-2 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-bold text-error border border-outline-variant transition-colors" title="Mark Zero / Missing">0</button>
                                    <button onclick="setScorePreset('half')" class="px-2.5 py-2 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-bold text-on-surface border border-outline-variant transition-colors" title="50% Score">Half</button>
                                    <button onclick="setScorePreset('full')" class="px-3 py-2 bg-primary/10 hover:bg-primary/20 rounded-xl text-xs font-bold text-primary border border-primary/30 transition-colors" title="Full Max Score">Max</button>
                                    <button onclick="adjustScoreStep(-1)" class="p-2 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-bold text-on-surface border border-outline-variant" title="Minus 1">-1</button>
                                    <button onclick="adjustScoreStep(1)" class="p-2 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-bold text-on-surface border border-outline-variant" title="Plus 1">+1</button>
                                </div>
                            </div>

                            <!-- Live Calculation Feedback Row -->
                            <div class="flex items-center justify-between text-xs font-mono pt-1 text-on-surface-variant">
                                <div>Percentage: <strong class="text-primary font-bold" id="calc-percentage-preview">0.0%</strong></div>
                                <div>Contribution: <strong class="text-status-success font-bold" id="calc-contribution-preview">0.0%</strong> to period</div>
                            </div>
                        </div>

                        <!-- Quick Flags / Remarks Options -->
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <span class="text-on-surface-variant font-bold">Flags:</span>
                            <button onclick="flagStudentStatus('missing')" class="px-2.5 py-1 rounded-lg border border-outline-variant bg-surface hover:bg-error/10 hover:text-error text-on-surface-variant text-[11px] font-semibold transition-colors">
                                Missing
                            </button>
                            <button onclick="flagStudentStatus('excused')" class="px-2.5 py-1 rounded-lg border border-outline-variant bg-surface hover:bg-status-info/10 hover:text-status-info text-on-surface-variant text-[11px] font-semibold transition-colors">
                                Excused
                            </button>
                            <button onclick="flagStudentStatus('review')" class="px-2.5 py-1 rounded-lg border border-outline-variant bg-surface hover:bg-amber-500/10 hover:text-amber-600 text-on-surface-variant text-[11px] font-semibold transition-colors">
                                Needs Review
                            </button>
                            <button onclick="flagStudentStatus('inc')" class="px-2.5 py-1 rounded-lg border border-outline-variant bg-surface hover:bg-purple-500/10 hover:text-purple-600 text-on-surface-variant text-[11px] font-semibold transition-colors">
                                Incomplete (INC)
                            </button>
                        </div>

                        <!-- Private Faculty Evaluation Note -->
                        <div>
                            <label for="eval-faculty-note" class="block text-xs font-bold text-on-surface-variant mb-1">Private Faculty Note / Feedback (Optional):</label>
                            <input type="text" id="eval-faculty-note" onchange="onFacultyNoteChange(this.value)" placeholder="e.g. Excellent recitation contribution; verified submission..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:ring-1 focus:ring-primary focus:outline-none">
                        </div>

                        <!-- Compact Period Assessment Matrix for Active Student -->
                        <div class="space-y-2 pt-2 border-t border-outline-variant">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-bold text-primary uppercase" id="compact-period-label">Prelim Period Components</h5>
                                <span class="text-[10px] font-mono text-on-surface-variant">Click component card to switch</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5" id="compact-student-components-grid">
                                <!-- Populated dynamically -->
                            </div>
                        </div>

                        <!-- Navigation & Shortcut Bar -->
                        <div class="flex items-center justify-between pt-3 border-t border-outline-variant gap-2">
                            <button onclick="navigateStudent(-1)" class="px-3.5 py-2 rounded-xl border border-outline-variant hover:bg-surface-container text-xs font-bold text-primary flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Previous (K)
                            </button>

                            <div class="hidden sm:flex items-center gap-1.5 text-[10px] font-mono text-on-surface-variant bg-surface-container px-2.5 py-1 rounded-lg border border-outline-variant">
                                <span>Enter: Save & Next</span> • <span>J / K: Nav</span> • <span>Ctrl+S: Draft</span>
                            </div>

                            <button onclick="navigateStudent(1)" class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primary/90 text-xs font-bold flex items-center gap-1 shadow-sm transition-colors">
                                Next Student (J) <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>

                    <!-- Overall Summary View (Active when Period === 'overall') -->
                    <div class="p-4 md:p-5 space-y-5 hidden" id="overall-summary-view">
                        <!-- Student Period Weighted Calculation Card -->
                        <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4 space-y-4 shadow-sm">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-status-success"></span>
                                    <h4 class="text-xs md:text-sm font-bold text-primary uppercase tracking-wide">Academic Term Composite Formula</h4>
                                </div>
                                <span class="text-xs font-mono font-bold text-on-surface-variant" id="overall-formula-pill">Prelim 30% + Midterm 30% + Final 40%</span>
                            </div>

                            <!-- 3 Period Contribution Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Prelim Card -->
                                <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant space-y-1.5 shadow-xs">
                                    <div class="flex items-center justify-between text-[11px] font-mono text-on-surface-variant font-bold">
                                        <span>PRELIM</span>
                                        <span class="text-primary font-bold" id="overall-prelim-weight">30%</span>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-lg font-black font-mono text-primary" id="overall-prelim-score">0.0%</span>
                                        <span class="text-xs font-mono font-bold text-status-success" id="overall-prelim-contrib">+0.00%</span>
                                    </div>
                                    <p class="text-[10px] text-on-surface-variant font-mono truncate" id="overall-prelim-meta">0 components</p>
                                </div>

                                <!-- Midterm Card -->
                                <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant space-y-1.5 shadow-xs">
                                    <div class="flex items-center justify-between text-[11px] font-mono text-on-surface-variant font-bold">
                                        <span>MIDTERM</span>
                                        <span class="text-primary font-bold" id="overall-midterm-weight">30%</span>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-lg font-black font-mono text-primary" id="overall-midterm-score">0.0%</span>
                                        <span class="text-xs font-mono font-bold text-status-success" id="overall-midterm-contrib">+0.00%</span>
                                    </div>
                                    <p class="text-[10px] text-on-surface-variant font-mono truncate" id="overall-midterm-meta">0 components</p>
                                </div>

                                <!-- Final Card -->
                                <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant space-y-1.5 shadow-xs">
                                    <div class="flex items-center justify-between text-[11px] font-mono text-on-surface-variant font-bold">
                                        <span>FINAL</span>
                                        <span class="text-primary font-bold" id="overall-final-weight">40%</span>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-lg font-black font-mono text-primary" id="overall-final-score">0.0%</span>
                                        <span class="text-xs font-mono font-bold text-status-success" id="overall-final-contrib">+0.00%</span>
                                    </div>
                                    <p class="text-[10px] text-on-surface-variant font-mono truncate" id="overall-final-meta">0 components</p>
                                </div>
                            </div>

                            <!-- Total Summary Bar -->
                            <div class="p-3 bg-primary/5 rounded-xl border border-primary/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">calculate</span>
                                    <div>
                                        <p class="text-xs font-bold text-primary">Final Transmuted Academic Standing</p>
                                        <p class="text-[10px] font-mono text-on-surface-variant" id="overall-transmutation-detail">Based on NPC Official Scale (75.0% = 3.00)</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <span class="text-[10px] font-mono uppercase text-on-surface-variant block font-bold">Calculated Raw</span>
                                        <span class="text-sm font-black font-mono text-primary" id="overall-raw-total">0.00%</span>
                                    </div>
                                    <div class="h-8 w-px bg-outline-variant"></div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-mono uppercase text-on-surface-variant block font-bold">Equivalent GPA</span>
                                        <span class="text-xl font-black font-mono text-primary" id="overall-gpa-badge">—</span>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-surface-container text-on-surface-variant" id="overall-remark-badge">Ongoing</span>
                                </div>
                            </div>
                        </div>

                        <!-- Class Masterlist Roster Table -->
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div>
                                    <h4 class="text-xs md:text-sm font-bold text-primary flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">table_chart</span> Class Overall Gradebook Masterlist
                                    </h4>
                                    <p class="text-[11px] text-on-surface-variant">Alphabetical list by Last Name with live auto-calculated period composites and transmuted GPAs.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="overall-roster-search" oninput="filterOverallMasterlist(this.value)" placeholder="Search student..." class="px-2.5 py-1 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:ring-1 focus:ring-primary w-36 sm:w-48">
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-outline-variant max-h-[380px] fas-scrollbar">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-surface-container-low font-mono text-[11px] text-on-surface uppercase sticky top-0 z-10 shadow-xs">
                                        <tr>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant">#</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant">Student No.</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant">Student Name (Last Name First)</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-center" id="th-p-weight">Prelim (30%)</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-center" id="th-m-weight">Midterm (30%)</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-center" id="th-f-weight">Final (40%)</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-center">Total %</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-center">GPA</th>
                                            <th class="py-2.5 px-3 font-bold border-b border-outline-variant text-right">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody id="overall-masterlist-tbody" class="divide-y divide-outline-variant/30 text-xs">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANE: Grade Review & Insights (3 cols on xl, full on tablet/mobile) -->
                <div class="md:col-span-12 xl:col-span-3 bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 md:p-5 flex flex-col shadow-sm space-y-5" id="insights-pane">
                    
                    <!-- 1. Grade Distribution -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-[18px]">bar_chart</span>
                                <h4 class="text-xs font-bold text-primary uppercase tracking-wider">Grade Distribution</h4>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-status-success" id="stat-pass-rate">0% Pass</span>
                        </div>

                        <!-- Distribution Bars -->
                        <div class="space-y-2 text-xs font-mono" id="grade-distribution-bars">
                            <div>
                                <div class="flex justify-between text-[11px] mb-0.5">
                                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">1.00 - 1.75 (Excellent)</span>
                                    <span id="dist-count-exc">0</span>
                                </div>
                                <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden">
                                    <div id="dist-bar-exc" class="bg-emerald-500 h-full rounded-full transition-all" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-[11px] mb-0.5">
                                    <span class="text-blue-700 dark:text-blue-400 font-bold">2.00 - 3.00 (Passing)</span>
                                    <span id="dist-count-pass">0</span>
                                </div>
                                <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden">
                                    <div id="dist-bar-pass" class="bg-blue-500 h-full rounded-full transition-all" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-[11px] mb-0.5">
                                    <span class="text-error font-bold">5.00 (Failing)</span>
                                    <span id="dist-count-fail">0</span>
                                </div>
                                <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden">
                                    <div id="dist-bar-fail" class="bg-error h-full rounded-full transition-all" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-[11px] mb-0.5">
                                    <span class="text-amber-700 dark:text-amber-400 font-bold">INC / Ongoing</span>
                                    <span id="dist-count-inc">0</span>
                                </div>
                                <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden">
                                    <div id="dist-bar-inc" class="bg-amber-500 h-full rounded-full transition-all" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Completion Health -->
                    <div class="space-y-2 pt-2 border-t border-outline-variant">
                        <h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">health_metrics</span> Evaluation Health
                        </h4>
                        <div class="grid grid-cols-2 gap-2 text-center text-xs">
                            <div class="p-2.5 bg-surface-container-low rounded-xl border border-outline-variant">
                                <p class="text-[10px] font-mono text-on-surface-variant font-bold uppercase">Fully Scored</p>
                                <p class="text-base font-extrabold font-mono text-status-success" id="health-fully-scored">0</p>
                            </div>
                            <div class="p-2.5 bg-surface-container-low rounded-xl border border-outline-variant">
                                <p class="text-[10px] font-mono text-on-surface-variant font-bold uppercase">Missing Scores</p>
                                <p class="text-base font-extrabold font-mono text-amber-600" id="health-missing-scores">0</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Submission Readiness Checklist -->
                    <div class="space-y-2.5 pt-2 border-t border-outline-variant">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px]">checklist</span> Submission Checklist
                            </h4>
                            <span class="text-[10px] font-mono font-bold text-on-surface-variant" id="checklist-progress">0/6 Met</span>
                        </div>

                        <ul class="space-y-1.5 text-xs" id="submission-checklist-items">
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-enrolled">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>All enrolled students have records</span>
                            </li>
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-components">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>All components scored or marked INC</span>
                            </li>
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-weights">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>Grading schemes total 100%</span>
                            </li>
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-invalid">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>No invalid/out-of-bounds scores</span>
                            </li>
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-draft">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>Draft is saved to database</span>
                            </li>
                            <li class="flex items-center gap-2 text-on-surface-variant" id="chk-reviewed">
                                <span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span>
                                <span>At-risk students reviewed</span>
                            </li>
                        </ul>

                        <!-- Block Message -->
                        <div id="checklist-block-alert" class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-800 dark:text-amber-300 space-y-1">
                            <p class="font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">info</span> Submission Locked
                            </p>
                            <p id="checklist-block-reason">Complete all evaluations before submitting.</p>
                        </div>
                    </div>

                    <!-- 4. Calculation Transparency (Active Student Breakdown) -->
                    <div class="space-y-2 pt-2 border-t border-outline-variant text-xs">
                        <h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">calculate</span> Calculation Transparency
                        </h4>
                        <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant space-y-1 font-mono text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Prelim Composite (30%):</span>
                                <span class="font-bold text-primary" id="calc-prelim-val">0.0%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Midterm Composite (30%):</span>
                                <span class="font-bold text-primary" id="calc-midterm-val">0.0%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Final Composite (40%):</span>
                                <span class="font-bold text-primary" id="calc-final-val">0.0%</span>
                            </div>
                            <div class="border-t border-outline-variant my-1 pt-1 flex justify-between font-bold">
                                <span>Computed Rating:</span>
                                <span class="text-primary" id="calc-computed-rating">0.00% -> —</span>
                            </div>
                            <p class="text-[9px] text-on-surface-variant pt-0.5 leading-tight">
                                NPC Transmutation: 75.0% = 3.00 (Passing) • 97.0%+ = 1.00 (Excellent).
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Print Header & Table for Official Submission (Rendered on window.print()) -->
            <div id="print-official-sheet" class="space-y-4">
                <div class="flex items-center gap-3 border-b-2 border-black pb-3">
                    <div>
                        <h2 class="font-bold text-base uppercase">Navotas Polytechnic College</h2>
                        <p class="text-xs">Office of the Registrar — Official Faculty Grade Sheet</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 text-xs border border-black p-2 gap-1 font-mono">
                    <p><strong>Course:</strong> <span id="print-code">DM103</span> - <span id="print-title">Business Process Management</span></p>
                    <p><strong>Section:</strong> <span id="print-section">AIS 2A</span></p>
                    <p><strong>Faculty:</strong> <?= htmlspecialchars($teacher_name) ?></p>
                    <p><strong>Term:</strong> 1st Semester, AY 2026-2027</p>
                </div>
                <table class="w-full border-collapse border border-black text-xs font-mono mt-3">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-black p-1.5 text-left">Student #</th>
                            <th class="border border-black p-1.5 text-left">Full Name</th>
                            <th class="border border-black p-1.5 text-center">Prelim (30%)</th>
                            <th class="border border-black p-1.5 text-center">Midterm (30%)</th>
                            <th class="border border-black p-1.5 text-center">Final (40%)</th>
                            <th class="border border-black p-1.5 text-center">Final GPA</th>
                            <th class="border border-black p-1.5 text-center">Remark</th>
                        </tr>
                    </thead>
                    <tbody id="print-sheet-tbody">
                        <!-- Populated dynamically on print -->
                    </tbody>
                </table>
                <div class="pt-8 grid grid-cols-2 text-xs font-mono">
                    <div>
                        <p>Certified Correct:</p>
                        <p class="mt-6 border-t border-black w-60 pt-1 font-bold"><?= htmlspecialchars($teacher_name) ?></p>
                        <p class="text-[10px] text-gray-600">Instructor Signature & Date</p>
                    </div>
                    <div>
                        <p>Approved & Received:</p>
                        <p class="mt-6 border-t border-black w-60 pt-1 font-bold">Office of the College Registrar</p>
                        <p class="text-[10px] text-gray-600">Official Institutional Stamp</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Rapid Scoring Focus Overlay Modal -->
    <div id="rapid-scoring-modal" class="fixed inset-0 bg-black/70 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5 animate-scale-in">
            <div class="flex items-center justify-between border-b border-outline-variant pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[22px]">bolt</span>
                    <div>
                        <h3 class="text-base font-bold text-primary">Rapid Scoring Mode</h3>
                        <p class="text-[11px] font-mono text-on-surface-variant" id="rapid-active-comp-title">Scoring: Quiz 1 (Max 20 pts)</p>
                    </div>
                </div>
                <button onclick="closeRapidScoringModal()" class="p-1.5 text-on-surface-variant hover:text-primary rounded-xl hover:bg-surface-container">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Student Card in Rapid Mode -->
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-5 text-center space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-primary text-white font-extrabold text-xl flex items-center justify-center mx-auto shadow-sm" id="rapid-avatar">
                    ST
                </div>
                <div>
                    <h4 class="text-lg font-bold text-primary" id="rapid-student-name">ARNIELYN SENITA</h4>
                    <p class="text-xs font-mono text-on-surface-variant" id="rapid-student-num">#251684 • AIS 2A</p>
                </div>

                <!-- Big Focus Input -->
                <div class="max-w-[220px] mx-auto relative pt-2">
                    <input type="number" id="rapid-score-input" min="0" max="100" step="0.5" placeholder="0"
                           onkeydown="onRapidInputKeydown(event)"
                           class="w-full text-center py-4 text-3xl font-black font-mono bg-surface-container-lowest border-2 border-primary rounded-2xl focus:ring-4 focus:ring-primary/20 text-primary shadow-inner">
                    <span class="absolute right-3 top-1/2 translate-y-1 font-mono text-xs text-on-surface-variant font-bold" id="rapid-max-label">/ 20</span>
                </div>
                <p class="text-[11px] font-mono text-on-surface-variant">Press <strong>Enter</strong> to save & jump to next student.</p>
            </div>

            <!-- Progress & Controls -->
            <div class="flex items-center justify-between text-xs font-mono">
                <span id="rapid-progress-label">Student 1 of 1 (100%)</span>
                <div class="flex items-center gap-2">
                    <button onclick="rapidAdvance(-1)" class="px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface-container">Prev</button>
                    <button onclick="rapidSkip()" class="px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface-container">Skip</button>
                    <button onclick="rapidAdvance(1)" class="px-4 py-1.5 rounded-xl bg-primary text-white hover:bg-primary/90 font-bold">Enter ↵</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Grade Change Request Modal -->
    <div id="grade-change-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-surface-container-lowest rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-outline-variant space-y-4">
            <div class="flex items-center justify-between border-b border-outline-variant pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[22px]">edit_document</span>
                    <h3 class="text-base font-bold text-primary">Official Grade Change Request</h3>
                </div>
                <button onclick="closeGradeChangeModal()" class="text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <p class="text-xs text-on-surface-variant">
                This grade sheet has been officially approved and locked. Any grade revisions require formal submission to the Registrar and College Dean.
            </p>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-on-surface mb-1">Select Student:</label>
                    <select id="gcr-student-select" onchange="onGcrStudentSelected()" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 text-xs font-semibold focus:ring-1 focus:ring-primary">
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-on-surface-variant mb-1">Current Approved Grade:</label>
                        <input type="text" id="gcr-current-grade" readonly class="w-full bg-surface-container border border-outline-variant rounded-xl px-3 py-2 font-mono font-bold text-primary">
                    </div>
                    <div>
                        <label class="block font-bold text-primary mb-1">Proposed New Grade:</label>
                        <input type="number" id="gcr-proposed-grade" step="0.25" min="1.00" max="5.00" placeholder="e.g. 1.75" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-3 py-2 font-mono font-bold text-primary focus:ring-1 focus:ring-primary">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-on-surface mb-1">Detailed Justification / Official Reason:</label>
                    <textarea id="gcr-reason" rows="3" placeholder="Provide full justification (e.g., overlooked project submission, calculation adjustment)..." class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-3 text-xs text-on-surface focus:ring-1 focus:ring-primary"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant">
                <button onclick="closeGradeChangeModal()" class="px-4 py-2 border border-outline-variant rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container">
                    Cancel
                </button>
                <button onclick="submitGradeChangeRequest()" class="px-4 py-2 bg-secondary text-white rounded-xl text-xs font-bold hover:bg-secondary/90 shadow-sm">
                    Submit Request to Registrar
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="fas-toast" class="fixed bottom-6 right-6 bg-gray-900 text-white text-xs px-4 py-3 rounded-2xl shadow-2xl z-50 flex items-center gap-2.5 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-2">
        <span class="material-symbols-outlined text-[18px] text-emerald-400" id="toast-icon">check_circle</span>
        <span id="toast-message">Ready</span>
    </div>

    <!-- JavaScript Application Logic -->
    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);

        const currentTeacherName = <?= json_encode($teacher_name) ?>;
        const currentTeacherEmail = <?= json_encode($teacher_email) ?>;
        const isAdmin = <?= json_encode($is_admin) ?>;
        const csrfToken = <?= json_encode($csrf_token) ?>;

        // Core State
        let activeClasses = [];
        let currentClass = null;
        let isSheetLocked = false;
        let isSheetSubmitted = false;

        let studentsList = [];
        let componentsList = [];
        let periodWeights = { prelim: 30, midterm: 30, final: 40 };
        let studentGradesMap = {}; // sNum -> { prelim, midterm, final, gpa, remarks, component_scores, notes }
        let attendanceStatsMap = {};

        // Active View State
        let activePeriod = 'prelim'; // 'prelim', 'midterm', 'final', 'overall'
        let activeComponentId = null; // component UUID
        let activeStudentIndex = 0;
        let queueSearchQuery = '';
        let queueFilter = 'all';
        let queueSort = 'alpha';

        let isDirty = false;
        let autosaveTimer = null;

        // NPC Transmutation Scale
        function transmuteNpc(pct) {
            if (pct >= 97.0) return { gpa: 1.00, remark: 'PASSED', label: 'Excellent' };
            if (pct >= 94.0) return { gpa: 1.25, remark: 'PASSED', label: 'Superior' };
            if (pct >= 91.0) return { gpa: 1.50, remark: 'PASSED', label: 'Very Good' };
            if (pct >= 88.0) return { gpa: 1.75, remark: 'PASSED', label: 'Good' };
            if (pct >= 85.0) return { gpa: 2.00, remark: 'PASSED', label: 'Meritorious' };
            if (pct >= 82.0) return { gpa: 2.25, remark: 'PASSED', label: 'Satisfactory' };
            if (pct >= 79.0) return { gpa: 2.50, remark: 'PASSED', label: 'Fair' };
            if (pct >= 76.0) return { gpa: 2.75, remark: 'PASSED', label: 'Passing' };
            if (pct >= 75.0) return { gpa: 3.00, remark: 'PASSED', label: 'Passing' };
            if (pct > 0)     return { gpa: 5.00, remark: 'FAILED', label: 'Failed' };
            return { gpa: 0.00, remark: 'INC', label: 'Incomplete' };
        }

        // Helper: Last Name First Formatting & Sorting
        function parseName(fullName) {
            if (!fullName || typeof fullName !== 'string') return { lastName: '', firstName: '', formatted: '' };
            const clean = fullName.trim();
            if (!clean) return { lastName: '', firstName: '', formatted: '' };

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
                return { lastName: tokens[0], firstName: '', formatted: tokens[0] };
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
            return { lastName, firstName, formatted };
        }

        function formatLastNameFirst(name) {
            return parseName(name).formatted;
        }

        function compareByLastName(aName, bName) {
            const a = parseName(aName);
            const b = parseName(bName);
            const cmp = a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' });
            if (cmp !== 0) return cmp;
            return a.firstName.localeCompare(b.firstName, undefined, { sensitivity: 'base' });
        }

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

        let allInstitutionClasses = [];
        let assignedFacultyClasses = [];
        let isCollegeWideAdminMode = false;

        // Initialize Studio
        async function initAssessmentStudio() {
            try {
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
                            classes = all;
                        }
                    } catch (e) {
                        console.error('REST API error:', e);
                    }
                }

                allInstitutionClasses = classes || [];
                const myEmail = (currentTeacherEmail || '').toLowerCase().trim();
                const myName = (currentTeacherName || '').toLowerCase().trim();

                // STRICT FACULTY PRIVACY: Scope to classes assigned to this faculty member
                assignedFacultyClasses = allInstitutionClasses.filter(c => isClassAssignedToTeacher(c, myEmail, myName));

                // Default is always strict privacy scoping
                activeClasses = assignedFacultyClasses;
                isCollegeWideAdminMode = false;

                // If user is admin and has no assigned classes of their own, allow college-wide review
                if (isAdmin && assignedFacultyClasses.length === 0) {
                    activeClasses = allInstitutionClasses;
                    isCollegeWideAdminMode = true;
                }

                updateClassDropdownAndPrivacyUI();

                const urlParams = new URLSearchParams(window.location.search);
                const initialClassId = urlParams.get('class_id');

                if (initialClassId) {
                    const isAllowed = activeClasses.some(c => c.id === initialClassId);
                    if (isAllowed) {
                        const select = document.getElementById('active-class-select');
                        if (select) select.value = initialClassId;
                        await loadStudioData(initialClassId);
                    } else {
                        // Class requested is not assigned to this teacher!
                        const attemptedTarget = allInstitutionClasses.find(c => c.id === initialClassId);
                        const targetLabel = attemptedTarget ? `${attemptedTarget.code} (${attemptedTarget.section || 'AIS 2A'})` : 'requested class';
                        
                        await npcAlert({
                            title: 'Academic Privacy Restriction',
                            message: `Access to ${targetLabel} is restricted. Faculty gradebooks are confidential and only accessible to the officially designated instructor.`,
                            type: 'warning'
                        });

                        if (activeClasses.length > 0) {
                            const select = document.getElementById('active-class-select');
                            if (select) select.value = activeClasses[0].id;
                            await loadStudioData(activeClasses[0].id);
                        } else {
                            renderNoClassesAssignedState();
                        }
                    }
                } else if (activeClasses.length > 0) {
                    const select = document.getElementById('active-class-select');
                    if (select) select.value = activeClasses[0].id;
                    await loadStudioData(activeClasses[0].id);
                } else {
                    renderNoClassesAssignedState();
                }
            } catch (err) {
                console.error("Init error:", err);
                showToast('Initialization error: ' + err.message, 'error');
            }
        }

        function updateClassDropdownAndPrivacyUI() {
            const select = document.getElementById('active-class-select');
            const badgePill = document.getElementById('privacy-badge-pill');
            const badgeText = document.getElementById('privacy-badge-text');
            const toggleLabel = document.getElementById('admin-scope-toggle-label');

            if (isCollegeWideAdminMode) {
                if (badgePill) {
                    badgePill.className = 'hidden md:inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-mono font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 shadow-2xs';
                    badgePill.title = 'Administrative Oversight Mode: Viewing all institution classes';
                }
                if (badgeText) badgeText.innerText = 'Admin All-Classes';
                if (toggleLabel) toggleLabel.innerText = 'Switch to Private Faculty Scoping';
            } else {
                if (badgePill) {
                    badgePill.className = 'hidden md:inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 shadow-2xs';
                    badgePill.title = 'Faculty Grade Privacy Protected: Only your assigned subjects are visible';
                }
                if (badgeText) badgeText.innerText = 'Privacy Scoped';
                if (toggleLabel) toggleLabel.innerText = 'View All Classes (Admin Mode)';
            }

            if (!select) return;
            if (activeClasses.length === 0) {
                select.innerHTML = '<option value="">No assigned classes found</option>';
                return;
            }

            select.innerHTML = '<option value="">Select an assigned class gradebook...</option>' + 
                activeClasses.map(c => `<option value="${c.id}">${c.code} - ${c.title} (${c.section || 'AIS 2A'})</option>`).join('');
        }

        async function toggleAdminScope() {
            if (!isAdmin) return;
            isCollegeWideAdminMode = !isCollegeWideAdminMode;
            if (isCollegeWideAdminMode) {
                activeClasses = allInstitutionClasses;
                showToast('Switched to College-Wide Classes view (Admin Mode)', 'info');
            } else {
                activeClasses = assignedFacultyClasses;
                showToast('Switched to Private Faculty Scoping (Your Assigned Classes)', 'success');
            }
            updateClassDropdownAndPrivacyUI();
            if (activeClasses.length > 0) {
                const select = document.getElementById('active-class-select');
                if (select) select.value = activeClasses[0].id;
                await loadStudioData(activeClasses[0].id);
            } else {
                renderNoClassesAssignedState();
            }
        }

        function renderNoClassesAssignedState() {
            const nav = document.getElementById('assessment-navigator');
            if (nav) nav.classList.add('hidden');
            const queuePane = document.getElementById('queue-pane');
            if (queuePane) queuePane.classList.add('hidden');
            const insightsPane = document.getElementById('insights-pane');
            if (insightsPane) insightsPane.classList.add('hidden');

            document.getElementById('banner-code').innerText = 'NO ASSIGNMENT';
            document.getElementById('banner-section').innerText = 'N/A';
            document.getElementById('banner-title').innerText = 'No Assigned Subjects Found';
            document.getElementById('banner-prof').innerText = currentTeacherName || 'Faculty Professor';
            document.getElementById('banner-enrolled-count').innerText = '0';

            let emptyCard = document.getElementById('privacy-no-classes-card');
            if (!emptyCard) {
                emptyCard = document.createElement('div');
                emptyCard.id = 'privacy-no-classes-card';
                emptyCard.className = 'bg-surface-container-lowest border border-outline-variant rounded-2xl p-8 md:p-12 text-center text-on-surface-variant shadow-sm max-w-xl mx-auto my-8';
                emptyCard.innerHTML = `
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-[36px]">lock_person</span>
                    </div>
                    <h3 class="font-bold text-lg text-primary mb-2">Faculty Grade Privacy Active</h3>
                    <p class="text-xs text-on-surface-variant mb-4 leading-relaxed">
                        In accordance with NPC ELMS Academic Privacy Policies, faculty gradebooks are strictly isolated. You may only view, evaluate, and encode student records for classes assigned to your faculty profile.
                    </p>
                    <div class="p-3 bg-surface-container-low rounded-xl text-[11px] text-on-surface-variant font-mono mb-4 text-left border border-outline-variant/60 space-y-1">
                        <div>• Faculty Name: <strong class="text-on-surface">${currentTeacherName}</strong></div>
                        <div>• Account Email: <strong class="text-on-surface">${currentTeacherEmail}</strong></div>
                        <div>• Status: <span class="text-status-warning font-bold">No active teaching loads linked</span></div>
                    </div>
                    <p class="text-xs text-on-surface-variant/80">
                        If you are assigned to teach this semester, please coordinate with the Registrar or your Department Head to link your faculty account to your subjects.
                    </p>
                `;
                const canvas = document.getElementById('studio-main-canvas');
                if (canvas) canvas.appendChild(emptyCard);
            } else {
                emptyCard.classList.remove('hidden');
            }
        }

        async function onClassDropdownChange() {
            const classId = document.getElementById('active-class-select').value;
            if (classId) {
                await loadStudioData(classId);
            }
        }

        async function loadStudioData(classId) {
            currentClass = activeClasses.find(c => c.id === classId);
            if (!currentClass) return;

            showToast('Loading class assessment records...', 'info');

            // Banner Info
            document.getElementById('banner-code').innerText = currentClass.code;
            document.getElementById('banner-section').innerText = currentClass.section || 'AIS 2A';
            document.getElementById('banner-title').innerText = currentClass.title;
            document.getElementById('banner-prof').innerText = currentClass.instructor || currentTeacherName;

            document.getElementById('print-code').innerText = currentClass.code;
            document.getElementById('print-title').innerText = currentClass.title;
            document.getElementById('print-section').innerText = currentClass.section || 'AIS 2A';

            try {
                const res = await fetch(`/api/gradebook.php?action=get_class_grades&class_id=${classId}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load class grades');

                isSheetLocked = !!data.is_locked;
                isSheetSubmitted = data.submission && data.submission.status === 'Submitted';
                updateLifecycleBadge(data.submission);

                studentsList = (data.students || []).sort((a, b) => compareByLastName(a.full_name || '', b.full_name || ''));
                componentsList = data.components || [];
                periodWeights = data.period_weights || { prelim: 30, midterm: 30, final: 40 };
                attendanceStatsMap = data.attendance_stats || {};

                document.getElementById('banner-enrolled-count').innerText = `${studentsList.length}`;
                document.getElementById('m-queue-count').innerText = `${studentsList.length}`;

                // Populate Period Weights in tabs
                document.getElementById('tab-weight-prelim').innerText = `${periodWeights.prelim}%`;
                document.getElementById('tab-weight-midterm').innerText = `${periodWeights.midterm}%`;
                document.getElementById('tab-weight-final').innerText = `${periodWeights.final}%`;

                // Build Student Grades Map
                studentGradesMap = {};
                (data.grades || []).forEach(g => {
                    let cs = {};
                    try {
                        cs = typeof g.component_scores === 'string' ? JSON.parse(g.component_scores || '{}') : (g.component_scores || {});
                    } catch (e) { cs = {}; }

                    studentGradesMap[g.student_number] = {
                        prelim: parseFloat(g.prelim) || 0,
                        midterm: parseFloat(g.midterm) || 0,
                        final: parseFloat(g.final) || 0,
                        raw: parseFloat(g.raw_grade) || 0,
                        gpa: parseFloat(g.equivalent_grade) || 0,
                        remarks: g.remarks || 'Ongoing',
                        component_scores: cs,
                        notes: cs.notes || ''
                    };
                });

                // Ensure every student has an entry in studentGradesMap
                studentsList.forEach(s => {
                    const sn = s.student_number || s.id;
                    if (!studentGradesMap[sn]) {
                        studentGradesMap[sn] = {
                            prelim: 0, midterm: 0, final: 0, raw: 0, gpa: 0,
                            remarks: 'Ongoing', component_scores: { prelim: {}, midterm: {}, final: {} },
                            notes: ''
                        };
                    }
                });

                // Compute all composite formulas
                recalculateAllGrades();

                // Select default period & first component
                selectGradingPeriod('overall');

                showToast(`Loaded ${studentsList.length} students for ${currentClass.code}`, 'success');
                markClean();
            } catch (err) {
                console.error("Load error:", err);
                showToast("Error: " + err.message, "error");
            }
        }

        function updateLifecycleBadge(submission) {
            const badge = document.getElementById('grade-sheet-status-badge');
            const saveBtn = document.getElementById('btn-save-draft');
            const submitBtn = document.getElementById('btn-submit-grades');
            const changeBtn = document.getElementById('btn-request-change');
            const rapidBtn = document.getElementById('btn-rapid-mode');

            const status = submission ? (submission.status || 'Draft') : 'Draft';

            if (status === 'Approved' || status === 'Published') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-status-success dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-400/40';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-status-success"></span> 🔒 Approved & Locked';
                saveBtn?.classList.add('hidden');
                submitBtn?.classList.add('hidden');
                rapidBtn?.classList.add('hidden');
                changeBtn?.classList.remove('hidden');
            } else if (status === 'Submitted') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-400/40';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> ⏳ Submitted (Under Review)';
                saveBtn?.classList.add('hidden');
                submitBtn?.classList.add('hidden');
                rapidBtn?.classList.add('hidden');
                changeBtn?.classList.add('hidden');
            } else if (status === 'Returned') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-400/40';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-500"></span> ↩ Returned for Revision';
                saveBtn?.classList.remove('hidden');
                submitBtn?.classList.remove('hidden');
                rapidBtn?.classList.remove('hidden');
                changeBtn?.classList.add('hidden');
            } else {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-400/40';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500"></span> ✏️ Draft Mode (Editable)';
                saveBtn?.classList.remove('hidden');
                submitBtn?.classList.remove('hidden');
                rapidBtn?.classList.remove('hidden');
                changeBtn?.classList.add('hidden');
            }
        }

        // Period Switching
        function selectGradingPeriod(period) {
            activePeriod = period;

            // Highlight Tab
            ['overall', 'prelim', 'midterm', 'final'].forEach(p => {
                const btn = document.getElementById(`period-tab-${p}`);
                if (btn) {
                    if (p === period) {
                        btn.className = 'px-4 py-1.5 rounded-lg text-xs font-bold text-primary transition-all flex items-center gap-1.5 shadow-sm bg-surface-container-lowest';
                    } else {
                        btn.className = 'px-4 py-1.5 rounded-lg text-xs font-bold text-on-surface-variant hover:text-primary transition-all flex items-center gap-1.5';
                    }
                }
            });

            if (period === 'overall') {
                activeComponentId = null;
                recalculateAllGrades();
                document.getElementById('components-carousel')?.classList.add('hidden');
                document.getElementById('period-components-count')?.classList.add('hidden');
                document.getElementById('eval-scoring-card')?.classList.add('hidden');
                document.getElementById('overall-summary-view')?.classList.remove('hidden');
                renderOverallSummaryView();
            } else {
                document.getElementById('components-carousel')?.classList.remove('hidden');
                document.getElementById('period-components-count')?.classList.remove('hidden');
                document.getElementById('overall-summary-view')?.classList.add('hidden');
                document.getElementById('eval-scoring-card')?.classList.remove('hidden');
                renderAssessmentComponentsCarousel();
                // Auto-select first component in this period
                const compsInPeriod = componentsList.filter(c => (c.grading_period || '').toLowerCase() === period);
                if (compsInPeriod.length > 0) {
                    selectComponent(compsInPeriod[0].id);
                } else {
                    activeComponentId = null;
                    renderLearnerEvaluationPanel();
                }
            }

            renderStudentQueue();
            updateInsightsAndChecklist();
        }

        // Render Assessment Components Carousel
        function renderAssessmentComponentsCarousel() {
            const carousel = document.getElementById('components-carousel');
            if (!carousel) return;

            if (activePeriod === 'overall') {
                carousel.innerHTML = `
                    <div class="px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant text-xs text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                        <span>Viewing Overall Academic Term Summary across Prelim (${periodWeights.prelim}%), Midterm (${periodWeights.midterm}%), and Final (${periodWeights.final}%).</span>
                    </div>
                `;
                document.getElementById('period-components-count').innerText = 'Summary View';
                return;
            }

            const compsInPeriod = componentsList.filter(c => (c.grading_period || '').toLowerCase() === activePeriod);
            const totalWeight = compsInPeriod.reduce((sum, c) => sum + (parseFloat(c.percentage_weight) || 0), 0);
            document.getElementById('period-components-count').innerText = `${compsInPeriod.length} components (${totalWeight.toFixed(0)}%)`;

            if (compsInPeriod.length === 0) {
                carousel.innerHTML = `
                    <div class="px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant text-xs text-on-surface-variant flex items-center justify-between w-full">
                        <span>No components configured for ${activePeriod.toUpperCase()} yet.</span>
                        <button onclick="openSchemeEditor()" class="px-3 py-1 bg-primary text-white rounded-lg font-bold text-xs">+ Add Assessment</button>
                    </div>
                `;
                return;
            }

            carousel.innerHTML = compsInPeriod.map(c => {
                const max = parseFloat(c.max_score) || 100;
                const weight = parseFloat(c.percentage_weight) || 0;
                const isActive = c.id === activeComponentId;

                // Compute stats for this component
                let scoredCount = 0;
                let scoreSum = 0;
                studentsList.forEach(s => {
                    const sn = s.student_number || s.id;
                    const scores = (studentGradesMap[sn]?.component_scores || {})[activePeriod] || {};
                    const val = parseFloat(scores[c.id]);
                    if (!isNaN(val)) {
                        scoredCount++;
                        scoreSum += val;
                    }
                });

                const avg = scoredCount > 0 ? (scoreSum / scoredCount).toFixed(1) : '—';
                const isComplete = scoredCount === studentsList.length && studentsList.length > 0;

                return `
                    <div onclick="selectComponent('${c.id}')"
                         class="cursor-pointer shrink-0 rounded-2xl border p-3 min-w-[200px] max-w-[240px] transition-all ${isActive ? 'comp-card-active' : 'bg-surface-container-low border-outline-variant hover:border-primary/40'}">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="text-[10px] font-mono font-bold uppercase text-on-surface-variant">${escapeHtml(c.description || 'Assessment')}</span>
                            <span class="text-[10px] font-mono font-extrabold px-1.5 py-0.2 rounded-md ${isComplete ? 'bg-status-success/10 text-status-success' : 'bg-primary/10 text-primary'}">
                                ${weight}%
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-primary truncate leading-tight mb-2">${escapeHtml(c.component_name)}</h4>
                        <div class="flex items-center justify-between text-[10px] font-mono text-on-surface-variant pt-1 border-t border-outline-variant/50">
                            <span>${scoredCount}/${studentsList.length} scored</span>
                            <span>Avg: ${avg} / ${max}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function selectComponent(compId) {
            activeComponentId = compId;
            renderAssessmentComponentsCarousel();
            renderLearnerEvaluationPanel();
            renderStudentQueue();
        }

        // Student Queue Rendering
        function renderStudentQueue() {
            const listEl = document.getElementById('queue-students-list');
            if (!listEl) return;

            let filtered = studentsList.filter(s => {
                const sn = String(s.student_number || s.id || '').toLowerCase();
                const name = String(s.full_name || '').toLowerCase();
                const q = queueSearchQuery.toLowerCase().trim();
                const matchesSearch = !q || sn.includes(q) || name.includes(q);
                if (!matchesSearch) return false;

                const grade = studentGradesMap[s.student_number || s.id] || {};
                const gpa = grade.gpa || 0;
                const scores = (grade.component_scores || {})[activePeriod] || {};
                const hasCurrentScore = activeComponentId ? (scores[activeComponentId] !== undefined && scores[activeComponentId] !== '') : false;

                if (queueFilter === 'ungraded') return !hasCurrentScore;
                if (queueFilter === 'atrisk') return (gpa >= 2.75 && gpa <= 3.00) || gpa === 5.00;
                if (queueFilter === 'passed') return gpa > 0 && gpa <= 3.00;
                return true;
            });

            // Sorting
            filtered.sort((a, b) => {
                const ga = studentGradesMap[a.student_number || a.id] || {};
                const gb = studentGradesMap[b.student_number || b.id] || {};
                if (queueSort === 'alpha') return compareByLastName(a.full_name || '', b.full_name || '');
                if (queueSort === 'score_asc') return (ga.gpa || 99) - (gb.gpa || 99);
                if (queueSort === 'score_desc') return (gb.gpa || 0) - (ga.gpa || 0);
                if (queueSort === 'incomplete') {
                    const sa = activeComponentId ? (ga.component_scores?.[activePeriod]?.[activeComponentId] !== undefined) : true;
                    const sb = activeComponentId ? (gb.component_scores?.[activePeriod]?.[activeComponentId] !== undefined) : true;
                    return sa === sb ? 0 : (sa ? 1 : -1);
                }
                return 0;
            });

            document.getElementById('queue-count-badge').innerText = `${filtered.length} of ${studentsList.length}`;

            if (filtered.length === 0) {
                listEl.innerHTML = `
                    <div class="p-8 text-center text-xs text-on-surface-variant font-medium">
                        <span class="material-symbols-outlined text-[32px] text-outline block mb-1">search_off</span>
                        No students match the current filters.
                    </div>
                `;
                return;
            }

            listEl.innerHTML = filtered.map(s => {
                const realIdx = studentsList.findIndex(st => (st.student_number || st.id) === (s.student_number || s.id));
                const isSelected = realIdx === activeStudentIndex;
                const sn = s.student_number || s.id;
                const grade = studentGradesMap[sn] || {};
                const scores = (grade.component_scores || {})[activePeriod] || {};
                const activeScore = activeComponentId ? scores[activeComponentId] : null;

                const initials = (s.full_name || 'ST').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
                const gpa = grade.gpa || 0;
                let gpaBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-surface-container text-on-surface-variant">Ongoing</span>`;
                if (gpa > 0 && gpa <= 3.00) {
                    gpaBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-status-success/10 text-status-success">${gpa.toFixed(2)}</span>`;
                } else if (gpa === 5.00) {
                    gpaBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-error/10 text-error">5.00</span>`;
                }

                const att = attendanceStatsMap[sn];
                const attPill = att ? `<span class="text-[9px] font-mono text-on-surface-variant">${att.rate}% att</span>` : '';

                return `
                    <div onclick="selectStudentByIndex(${realIdx})"
                         class="cursor-pointer p-3 rounded-2xl border transition-all flex items-center justify-between gap-2.5 ${isSelected ? 'student-item-active' : 'bg-surface-container-lowest border-outline-variant hover:border-primary/30'}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0">
                                ${escapeHtml(initials)}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-primary truncate leading-tight">${escapeHtml(formatLastNameFirst(s.full_name || 'Student Name'))}</p>
                                <div class="flex items-center gap-1.5 text-[10px] font-mono text-on-surface-variant">
                                    <span>${escapeHtml(sn)}</span>
                                    ${attPill ? '• ' + attPill : ''}
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-mono text-xs font-extrabold ${activeScore !== null && activeScore !== undefined && activeScore !== '' ? 'text-primary' : 'text-on-surface-variant/60'}">
                                ${activeScore !== null && activeScore !== undefined && activeScore !== '' ? activeScore : '—'}
                            </div>
                            <div class="mt-0.5">${gpaBadge}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function selectStudentByIndex(idx) {
            if (idx < 0 || idx >= studentsList.length) return;
            activeStudentIndex = idx;
            renderStudentQueue();
            if (activePeriod === 'overall') {
                renderOverallSummaryView();
            } else {
                renderLearnerEvaluationPanel();
            }
            updateTransparencyPanel();
        }

        function onQueueSearch(val) {
            queueSearchQuery = val;
            renderStudentQueue();
        }

        function setQueueFilter(f) {
            queueFilter = f;
            document.querySelectorAll('.q-filter').forEach(btn => {
                if (btn.dataset.filter === f) {
                    btn.className = 'q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-primary text-white';
                } else {
                    btn.className = 'q-filter px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container text-on-surface-variant hover:text-primary';
                }
            });
            renderStudentQueue();
        }

        function onQueueSortChange(val) {
            queueSort = val;
            renderStudentQueue();
        }

        // Center Pane: Learner Evaluation Panel
        function renderLearnerEvaluationPanel() {
            const student = studentsList[activeStudentIndex];
            if (!student) return;

            const sn = student.student_number || student.id;
            const grade = studentGradesMap[sn] || {};
            const initials = (student.full_name || 'ST').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();

            // Student profile
            document.getElementById('eval-student-avatar').innerText = initials;
            document.getElementById('eval-student-name').innerText = formatLastNameFirst(student.full_name || 'Student Name');
            document.getElementById('eval-student-number').innerText = '#' + sn;
            document.getElementById('eval-student-section').innerText = currentClass ? (currentClass.section || 'AIS 2A') : 'AIS 2A';

            // Attendance
            const att = attendanceStatsMap[sn];
            const attEl = document.getElementById('eval-student-attendance');
            if (att) {
                attEl.innerHTML = `<span class="material-symbols-outlined text-[12px] text-status-success">done_all</span> ${att.rate}% Att. (${att.present}/${att.total})`;
            } else {
                attEl.innerHTML = `<span class="material-symbols-outlined text-[12px] text-status-info">schedule</span> Attendance OK`;
            }

            // GPA & Remarks
            const gpa = grade.gpa || 0;
            const gpaEl = document.getElementById('eval-student-gpa');
            const remEl = document.getElementById('eval-student-remark');
            if (gpa > 0) {
                gpaEl.innerText = gpa.toFixed(2);
                remEl.innerText = grade.remarks || 'PASSED';
                remEl.className = gpa <= 3.00 ? 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-status-success/10 text-status-success border border-status-success/30' : 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-error/10 text-error border border-error/30';
            } else {
                gpaEl.innerText = '—';
                remEl.innerText = 'Ongoing';
                remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-surface-container text-on-surface-variant border border-outline-variant';
            }

            // Note
            document.getElementById('eval-faculty-note').value = grade.notes || '';

            // Selected Component Card
            const comp = componentsList.find(c => c.id === activeComponentId);
            const scoreInput = document.getElementById('active-score-input');

            if (comp) {
                document.getElementById('scoring-component-title').innerText = comp.component_name;
                document.getElementById('scoring-component-weight').innerText = `${comp.percentage_weight}% of ${activePeriod.toUpperCase()}`;
                document.getElementById('scoring-max-pts').innerText = `Max: ${comp.max_score} pts`;
                document.getElementById('score-input-out-of').innerText = `/ ${comp.max_score}`;
                scoreInput.max = comp.max_score;

                const scores = (grade.component_scores || {})[activePeriod] || {};
                const currVal = scores[comp.id];
                scoreInput.value = (currVal !== undefined && currVal !== null && currVal !== '') ? currVal : '';
                updateScoreCalculations(scoreInput.value, comp.max_score, comp.percentage_weight);
            } else {
                document.getElementById('scoring-component-title').innerText = 'Select an Assessment';
                document.getElementById('scoring-component-weight').innerText = '—';
                document.getElementById('scoring-max-pts').innerText = '—';
                document.getElementById('score-input-out-of').innerText = '';
                scoreInput.value = '';
                updateScoreCalculations('', 100, 0);
            }

            // Sync direct period grade inputs
            syncCardDirectInputs(grade);

            // Compact Grid of other components in this period
            renderCompactPeriodGrid(sn);
        }

        function renderCompactPeriodGrid(sn) {
            const grid = document.getElementById('compact-student-components-grid');
            const label = document.getElementById('compact-period-label');
            if (!grid) return;

            label.innerText = `${activePeriod.toUpperCase()} Period Components`;
            const compsInPeriod = componentsList.filter(c => (c.grading_period || '').toLowerCase() === activePeriod);
            const grade = studentGradesMap[sn] || {};
            const scores = (grade.component_scores || {})[activePeriod] || {};

            grid.innerHTML = compsInPeriod.map(c => {
                const val = scores[c.id];
                const hasScore = val !== undefined && val !== null && val !== '';
                const max = parseFloat(c.max_score) || 100;
                const isCurrent = c.id === activeComponentId;
                const pct = hasScore ? Math.round((parseFloat(val) / max) * 100) : null;

                return `
                    <div onclick="selectComponent('${c.id}')"
                         class="cursor-pointer p-2.5 rounded-xl border transition-all ${isCurrent ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'bg-surface-container-low border-outline-variant hover:border-primary/40'}">
                        <div class="flex items-center justify-between text-[10px] font-mono text-on-surface-variant mb-0.5">
                            <span class="truncate max-w-[100px]">${escapeHtml(c.component_name)}</span>
                            <span class="font-bold text-primary">${c.percentage_weight}%</span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-black font-mono ${hasScore ? 'text-primary' : 'text-on-surface-variant/40'}">${hasScore ? val : '—'}</span>
                            <span class="text-[10px] font-mono text-on-surface-variant">${hasScore ? pct + '%' : '/ ' + max}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateScoreCalculations(val, maxScore, weight) {
            const pctEl = document.getElementById('calc-percentage-preview');
            const contEl = document.getElementById('calc-contribution-preview');

            const num = parseFloat(val);
            const max = parseFloat(maxScore) || 100;
            const w = parseFloat(weight) || 0;

            if (!isNaN(num) && num >= 0 && max > 0) {
                const pct = (num / max) * 100;
                const contrib = (pct * (w / 100));
                pctEl.innerText = `${pct.toFixed(1)}%`;
                contEl.innerText = `${contrib.toFixed(1)}%`;
            } else {
                pctEl.innerText = '0.0%';
                contEl.innerText = '0.0%';
            }
        }

        // Score Input & Presets
        function onScoreCardInput(val) {
            if (isSheetLocked) return;
            const student = studentsList[activeStudentIndex];
            if (!student || !activeComponentId) return;

            const sn = student.student_number || student.id;
            const comp = componentsList.find(c => c.id === activeComponentId);
            if (!comp) return;

            const max = parseFloat(comp.max_score) || 100;
            let num = parseFloat(val);

            if (val !== '' && !isNaN(num)) {
                if (num < 0) num = 0;
                if (num > max) num = max;
                val = num;
                document.getElementById('active-score-input').value = val;
            }

            if (!studentGradesMap[sn].component_scores[activePeriod]) {
                studentGradesMap[sn].component_scores[activePeriod] = {};
            }
            studentGradesMap[sn].component_scores[activePeriod][activeComponentId] = val;

            updateScoreCalculations(val, max, comp.percentage_weight);
            recalculateStudentGrade(sn);
            renderStudentQueue();
            updateInsightsAndChecklist();
            updateTransparencyPanel();
            markDirty();
        }

        function setScorePreset(preset) {
            if (isSheetLocked || !activeComponentId) return;
            const comp = componentsList.find(c => c.id === activeComponentId);
            if (!comp) return;
            const max = parseFloat(comp.max_score) || 100;

            let val = 0;
            if (preset === 'full') val = max;
            else if (preset === 'half') val = Math.round((max / 2) * 10) / 10;
            else if (typeof preset === 'number') val = preset;

            document.getElementById('active-score-input').value = val;
            onScoreCardInput(val);
        }

        function adjustScoreStep(step) {
            if (isSheetLocked || !activeComponentId) return;
            const comp = componentsList.find(c => c.id === activeComponentId);
            if (!comp) return;
            const max = parseFloat(comp.max_score) || 100;

            let curr = parseFloat(document.getElementById('active-score-input').value) || 0;
            curr = Math.min(max, Math.max(0, curr + step));
            document.getElementById('active-score-input').value = curr;
            onScoreCardInput(curr);
        }

        function flagStudentStatus(type) {
            if (isSheetLocked) return;
            if (type === 'missing') {
                setScorePreset(0);
                showToast('Marked as Missing (Score: 0)', 'info');
            } else if (type === 'excused') {
                setScorePreset('full');
                showToast('Marked as Excused (Full credit)', 'info');
            } else if (type === 'review') {
                showToast('Flagged for manual review', 'info');
            } else if (type === 'inc') {
                const student = studentsList[activeStudentIndex];
                if (student) {
                    const sn = student.student_number || student.id;
                    studentGradesMap[sn].remarks = 'INC';
                    recalculateStudentGrade(sn);
                    renderStudentQueue();
                    renderLearnerEvaluationPanel();
                    showToast('Marked as Incomplete (INC)', 'info');
                    markDirty();
                }
            }
        }

        function onFacultyNoteChange(note) {
            const student = studentsList[activeStudentIndex];
            if (!student) return;
            const sn = student.student_number || student.id;
            studentGradesMap[sn].notes = note;
            studentGradesMap[sn].component_scores.notes = note;
            markDirty();
        }

        // Navigation
        function navigateStudent(direction) {
            const newIdx = activeStudentIndex + direction;
            if (newIdx >= 0 && newIdx < studentsList.length) {
                selectStudentByIndex(newIdx);
            }
        }

        function onScoreInputKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                navigateStudent(1);
            }
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    saveDraftGradesToServer();
                }
                return;
            }

            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                saveDraftGradesToServer();
            } else if (e.key === 'j' || e.key === 'J' || e.key === 'ArrowDown') {
                e.preventDefault();
                navigateStudent(1);
            } else if (e.key === 'k' || e.key === 'K' || e.key === 'ArrowUp') {
                e.preventDefault();
                navigateStudent(-1);
            }
        });

        function activeStudentSn() {
            const s = studentsList[activeStudentIndex];
            return s ? (s.student_number || s.id) : '';
        }

        function onDirectPeriodGradeInput(sn, period, val) {
            if (isSheetLocked) return;
            const g = studentGradesMap[sn];
            if (!g) return;

            let num = parseFloat(val);
            if (isNaN(num) || val === '') num = 0;
            if (num < 0) num = 0;
            if (num > 100) num = 100;

            g[period] = num;
            recalculateStudentGrade(sn);

            // Update row in table directly without destroying input DOM/losing focus
            updateMasterlistRowInPlace(sn);

            // If this student is currently selected, update active card inputs and calculations
            const activeSn = activeStudentSn();
            if (activeSn === sn) {
                syncCardDirectInputs(g);
                renderOverallSummaryView(false); // update summary KPI cards without re-rendering table
            }

            renderStudentQueue();
            updateInsightsAndChecklist();
            updateTransparencyPanel();
            markDirty();
        }

        function syncCardDirectInputs(g) {
            if (!g) return;
            const pIn = document.getElementById('card-direct-prelim');
            const mIn = document.getElementById('card-direct-midterm');
            const fIn = document.getElementById('card-direct-final');
            if (pIn && document.activeElement !== pIn) pIn.value = (g.prelim > 0 ? g.prelim : '');
            if (mIn && document.activeElement !== mIn) mIn.value = (g.midterm > 0 ? g.midterm : '');
            if (fIn && document.activeElement !== fIn) fIn.value = (g.final > 0 ? g.final : '');

            const gpaEl = document.getElementById('eval-student-gpa');
            const remEl = document.getElementById('eval-student-remark');
            if (gpaEl) gpaEl.innerText = g.gpa > 0 ? g.gpa.toFixed(2) : '—';
            if (remEl) {
                remEl.innerText = g.remarks || 'Ongoing';
                if (g.gpa > 0 && g.gpa <= 3.00) {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-status-success/10 text-status-success border border-status-success/30';
                } else if (g.gpa === 5.00) {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-error/10 text-error border border-error/30';
                } else {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-surface-container text-on-surface-variant border border-outline-variant';
                }
            }
        }

        function updateMasterlistRowInPlace(sn) {
            const g = studentGradesMap[sn];
            if (!g) return;

            const totEl = document.getElementById(`cell-total-${sn}`);
            const gpaEl = document.getElementById(`cell-gpa-${sn}`);
            const remEl = document.getElementById(`cell-rem-${sn}`);

            if (totEl) totEl.innerText = g.raw > 0 ? g.raw.toFixed(2) + '%' : '—';
            if (gpaEl) gpaEl.innerText = g.gpa > 0 ? g.gpa.toFixed(2) : '—';
            if (remEl) {
                remEl.innerText = g.remarks || 'Ongoing';
                if (g.gpa > 0 && g.gpa <= 3.00) {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-status-success/15 text-status-success';
                } else if (g.gpa === 5.00) {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-error/15 text-error';
                } else {
                    remEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-surface-container text-on-surface-variant';
                }
            }

            // Sync inputs if not currently focused
            const pIn = document.getElementById(`table-input-prelim-${sn}`);
            const mIn = document.getElementById(`table-input-midterm-${sn}`);
            const fIn = document.getElementById(`table-input-final-${sn}`);
            if (pIn && document.activeElement !== pIn) pIn.value = g.prelim > 0 ? g.prelim : '';
            if (mIn && document.activeElement !== mIn) mIn.value = g.midterm > 0 ? g.midterm : '';
            if (fIn && document.activeElement !== fIn) fIn.value = g.final > 0 ? g.final : '';
        }

        // Recalculation Engine (NPC Standard Transmutation)
        function recalculateStudentGrade(sn) {
            const g = studentGradesMap[sn];
            if (!g) return;

            const pWeight = (parseFloat(periodWeights.prelim) || 30) / 100;
            const mWeight = (parseFloat(periodWeights.midterm) || 30) / 100;
            const fWeight = (parseFloat(periodWeights.final) || 40) / 100;

            function calcPeriodPct(periodKey) {
                const comps = componentsList.filter(c => (c.grading_period || '').toLowerCase() === periodKey);
                if (comps.length === 0) return null;

                const scores = (g.component_scores || {})[periodKey] || {};
                let weightedSum = 0;
                let weightTotal = 0;
                let hasAnyScore = false;

                comps.forEach(c => {
                    const raw = parseFloat(scores[c.id]);
                    const max = parseFloat(c.max_score) || 100;
                    const w = parseFloat(c.percentage_weight) || 0;
                    if (!isNaN(raw) && raw >= 0) {
                        weightedSum += ((raw / max) * 100) * (w / 100);
                        hasAnyScore = true;
                    }
                    weightTotal += w;
                });

                if (!hasAnyScore) return null;
                return weightTotal > 0 ? (weightedSum / (weightTotal / 100)) : null;
            }

            // Only overwrite period grade from sub-components if sub-components actually have scores entered
            const computedP = calcPeriodPct('prelim');
            if (computedP !== null) g.prelim = computedP;

            const computedM = calcPeriodPct('midterm');
            if (computedM !== null) g.midterm = computedM;

            const computedF = calcPeriodPct('final');
            if (computedF !== null) g.final = computedF;

            let composite = 0;
            if (g.final > 0 && g.midterm > 0 && g.prelim > 0) {
                composite = (g.prelim * pWeight) + (g.midterm * mWeight) + (g.final * fWeight);
            } else if (g.final > 0) {
                composite = g.final;
            } else if (g.midterm > 0) {
                composite = g.prelim > 0 ? (g.prelim * 0.5 + g.midterm * 0.5) : g.midterm;
            } else {
                composite = g.prelim || 0;
            }

            g.raw = composite;

            // Transmute and assign remarks
            if (g.prelim > 0 && g.midterm > 0 && g.final > 0) {
                const trans = transmuteNpc(composite);
                g.gpa = trans.gpa;
                g.remarks = trans.remark; // 'PASSED' or 'FAILED' (never INC when complete!)
            } else if (composite > 0) {
                const trans = transmuteNpc(composite);
                g.gpa = trans.gpa;
                g.remarks = 'Ongoing';
            } else {
                g.gpa = 0.00;
                g.remarks = 'INC';
            }
        }

        function recalculateAllGrades() {
            studentsList.forEach(s => {
                recalculateStudentGrade(s.student_number || s.id);
            });
        }

        // Section E: Right Pane (Insights, Distribution & Checklist)
        function updateInsightsAndChecklist() {
            let excCount = 0, passCount = 0, failCount = 0, incCount = 0, totalCount = studentsList.length;
            let fullyScored = 0, missingCount = 0;

            studentsList.forEach(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                const gpa = g.gpa || 0;

                if (g.remarks === 'INC') incCount++;
                else if (gpa >= 1.00 && gpa <= 1.75) excCount++;
                else if (gpa > 1.75 && gpa <= 3.00) passCount++;
                else if (gpa === 5.00) failCount++;
                else incCount++;

                // Check completeness across all sub-components OR direct period grades
                let hasAllComponents = componentsList.length > 0;
                componentsList.forEach(c => {
                    const pk = (c.grading_period || '').toLowerCase();
                    const sc = (g.component_scores?.[pk] || {})[c.id];
                    if (sc === undefined || sc === null || sc === '') hasAllComponents = false;
                });
                const hasDirectPeriods = (parseFloat(g.prelim) > 0 && parseFloat(g.midterm) > 0 && parseFloat(g.final) > 0);

                if (hasAllComponents || hasDirectPeriods) fullyScored++;
                else missingCount++;
            });

            // Distribution bars
            const setBar = (id, count) => {
                const pct = totalCount > 0 ? (count / totalCount) * 100 : 0;
                document.getElementById(`dist-count-${id}`).innerText = count;
                document.getElementById(`dist-bar-${id}`).style.width = `${pct}%`;
            };
            setBar('exc', excCount);
            setBar('pass', passCount);
            setBar('fail', failCount);
            setBar('inc', incCount);

            // Pass rate
            const validEvals = excCount + passCount + failCount;
            const passRate = validEvals > 0 ? Math.round(((excCount + passCount) / validEvals) * 100) : 0;
            document.getElementById('stat-pass-rate').innerText = `${passRate}% Pass`;

            // Health
            document.getElementById('health-fully-scored').innerText = fullyScored;
            document.getElementById('health-missing-scores').innerText = missingCount;

            // Overall KPI in Banner
            document.getElementById('kpi-evaluated-ratio').innerText = `${fullyScored} / ${totalCount} (${totalCount > 0 ? Math.round((fullyScored / totalCount) * 100) : 0}%)`;
            document.getElementById('kpi-progress-bar').style.width = `${totalCount > 0 ? (fullyScored / totalCount) * 100 : 0}%`;

            // Submission Checklist Verification
            const chkEnrolled = totalCount > 0;
            const chkComponents = missingCount === 0 && totalCount > 0;
            const pWeightSum = (periodWeights.prelim || 0) + (periodWeights.midterm || 0) + (periodWeights.final || 0);
            const chkWeights = Math.abs(pWeightSum - 100) < 0.01;
            const chkInvalid = true; // enforced by numeric constraints
            const chkDraft = !isDirty;
            const chkReviewed = true;

            let passedRules = 0;
            const CHECKLIST_LABELS = {
                'chk-enrolled': 'All enrolled students have records',
                'chk-components': 'All student marks complete',
                'chk-weights': 'Grading schemes total 100%',
                'chk-invalid': 'No invalid/out-of-bounds scores',
                'chk-draft': 'Draft is saved to database',
                'chk-reviewed': 'At-risk students reviewed'
            };

            const updateChkItem = (id, ok) => {
                const el = document.getElementById(id);
                if (!el) return;
                const label = CHECKLIST_LABELS[id] || (el.getAttribute('data-label') || 'Requirement');
                if (ok) {
                    passedRules++;
                    el.className = 'flex items-center gap-2 text-status-success font-bold';
                    el.innerHTML = `<span class="material-symbols-outlined text-[16px] text-status-success">check_circle</span> <span>${label}</span>`;
                } else {
                    el.className = 'flex items-center gap-2 text-on-surface-variant';
                    el.innerHTML = `<span class="material-symbols-outlined text-[16px] text-outline">radio_button_unchecked</span> <span>${label}</span>`;
                }
            };

            updateChkItem('chk-enrolled', chkEnrolled);
            updateChkItem('chk-components', chkComponents);
            updateChkItem('chk-weights', chkWeights);
            updateChkItem('chk-invalid', chkInvalid);
            updateChkItem('chk-draft', chkDraft);
            updateChkItem('chk-reviewed', chkReviewed);

            document.getElementById('checklist-progress').innerText = `${passedRules}/6 Met`;

            const submitBtn = document.getElementById('btn-submit-grades');
            const alertBox = document.getElementById('checklist-block-alert');
            const reasonEl = document.getElementById('checklist-block-reason');

            if (passedRules >= 6) {
                if (submitBtn) submitBtn.disabled = false;
                if (alertBox) alertBox.classList.add('hidden');
            } else {
                if (submitBtn) submitBtn.disabled = true;
                if (alertBox) alertBox.classList.remove('hidden');
                let reason = 'Requirements pending: ';
                if (!chkComponents) reason += `${missingCount} students have incomplete marks. `;
                if (!chkDraft) reason += 'Unsaved draft changes exist. ';
                if (reasonEl) reasonEl.innerText = reason;
            }
        }

        // Section E.2: Overall Summary Masterlist & Calculation View
        let overallMasterlistSearch = '';

        function filterOverallMasterlist(val) {
            overallMasterlistSearch = (val || '').toLowerCase().trim();
            renderOverallMasterlistTable();
        }

        function renderOverallSummaryView(renderTable = true) {
            const student = studentsList[activeStudentIndex];
            if (!student) return;

            const sn = student.student_number || student.id;
            const grade = studentGradesMap[sn] || {};
            const pWeight = parseFloat(periodWeights.prelim) || 30;
            const mWeight = parseFloat(periodWeights.midterm) || 30;
            const fWeight = parseFloat(periodWeights.final) || 40;

            const prelimScore = parseFloat(grade.prelim) || 0;
            const midtermScore = parseFloat(grade.midterm) || 0;
            const finalScore = parseFloat(grade.final) || 0;

            const prelimContrib = (prelimScore * pWeight) / 100;
            const midtermContrib = (midtermScore * mWeight) / 100;
            const finalContrib = (finalScore * fWeight) / 100;
            const rawTotal = grade.raw || (prelimContrib + midtermContrib + finalContrib);
            const gpa = grade.gpa || 0;

            // Update formula weights
            document.getElementById('overall-formula-pill').innerText = `Prelim ${pWeight}% + Midterm ${mWeight}% + Final ${fWeight}%`;
            document.getElementById('overall-prelim-weight').innerText = `${pWeight}%`;
            document.getElementById('overall-midterm-weight').innerText = `${mWeight}%`;
            document.getElementById('overall-final-weight').innerText = `${fWeight}%`;

            // Scores & Contributions
            document.getElementById('overall-prelim-score').innerText = `${prelimScore.toFixed(1)}%`;
            document.getElementById('overall-prelim-contrib').innerText = `+${prelimContrib.toFixed(2)}%`;
            const pComps = componentsList.filter(c => (c.grading_period || '').toLowerCase() === 'prelim');
            document.getElementById('overall-prelim-meta').innerText = pComps.length > 0 ? `${pComps.length} components` : 'Direct Term Grade';

            document.getElementById('overall-midterm-score').innerText = `${midtermScore.toFixed(1)}%`;
            document.getElementById('overall-midterm-contrib').innerText = `+${midtermContrib.toFixed(2)}%`;
            const mComps = componentsList.filter(c => (c.grading_period || '').toLowerCase() === 'midterm');
            document.getElementById('overall-midterm-meta').innerText = `${mComps.length} components`;

            document.getElementById('overall-final-score').innerText = `${finalScore.toFixed(1)}%`;
            document.getElementById('overall-final-contrib').innerText = `+${finalContrib.toFixed(2)}%`;
            const fComps = componentsList.filter(c => (c.grading_period || '').toLowerCase() === 'final');
            document.getElementById('overall-final-meta').innerText = `${fComps.length} components`;

            // Overall Total Bar
            document.getElementById('overall-raw-total').innerText = `${rawTotal.toFixed(2)}%`;
            document.getElementById('overall-gpa-badge').innerText = gpa > 0 ? gpa.toFixed(2) : '—';
            
            const remBadge = document.getElementById('overall-remark-badge');
            const remarkText = grade.remarks || 'Ongoing';
            remBadge.innerText = remarkText;
            if (gpa > 0 && gpa <= 3.00) {
                remBadge.className = 'px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-status-success/15 text-status-success border border-status-success/30';
            } else if (gpa === 5.00) {
                remBadge.className = 'px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-error/15 text-error border border-error/30';
            } else {
                remBadge.className = 'px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-surface-container text-on-surface-variant border border-outline-variant';
            }

            // Headers in masterlist table showing period weights
            const thP = document.getElementById('th-p-weight');
            const thM = document.getElementById('th-m-weight');
            const thF = document.getElementById('th-f-weight');
            if (thP) thP.innerText = `Prelim (${pWeight}%)`;
            if (thM) thM.innerText = `Midterm (${mWeight}%)`;
            if (thF) thF.innerText = `Final (${fWeight}%)`;

            // Render table if requested
            if (renderTable) {
                renderOverallMasterlistTable();
            }
        }

        function renderOverallMasterlistTable() {
            const tbody = document.getElementById('overall-masterlist-tbody');
            if (!tbody) return;

            let list = [...studentsList];
            // Sort alphabetically by Last Name
            list.sort((a, b) => compareByLastName(a.full_name || '', b.full_name || ''));

            if (overallMasterlistSearch) {
                list = list.filter(s => {
                    const q = overallMasterlistSearch.toLowerCase();
                    return (s.student_number || '').toLowerCase().includes(q) || (s.full_name || '').toLowerCase().includes(q);
                });
            }

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-on-surface-variant font-mono">No students found.</td></tr>';
                return;
            }

            tbody.innerHTML = list.map((s, idx) => {
                const realIdx = studentsList.findIndex(st => (st.student_number || st.id) === (s.student_number || s.id));
                const isSelected = realIdx === activeStudentIndex;
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                const gpa = g.gpa || 0;
                const p = g.prelim > 0 ? g.prelim : '';
                const m = g.midterm > 0 ? g.midterm : '';
                const f = g.final > 0 ? g.final : '';
                const raw = (g.raw || 0).toFixed(2);
                const rem = g.remarks || 'Ongoing';

                let remClass = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-surface-container text-on-surface-variant';
                if (gpa > 0 && gpa <= 3.00) {
                    remClass = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-status-success/15 text-status-success';
                } else if (gpa === 5.00) {
                    remClass = 'px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-error/15 text-error';
                }

                return `
                    <tr onclick="selectStudentByIndex(${realIdx})" class="cursor-pointer transition-colors ${isSelected ? 'bg-primary/10 font-semibold' : 'hover:bg-surface-container-low'}">
                        <td class="py-2.5 px-3 font-mono text-on-surface-variant">${idx + 1}</td>
                        <td class="py-2.5 px-3 font-mono font-bold ${isSelected ? 'text-primary' : 'text-on-surface'}">${escapeHtml(sn)}</td>
                        <td class="py-2.5 px-3 font-bold ${isSelected ? 'text-primary' : 'text-on-surface'}">
                            ${escapeHtml(formatLastNameFirst(s.full_name || 'Student'))}
                        </td>
                        <td class="py-2 px-2 text-center" onclick="event.stopPropagation()">
                            <input type="number" id="table-input-prelim-${sn}" min="0" max="100" step="0.1"
                                   value="${p}" placeholder="0.0"
                                   ${isSheetLocked ? 'disabled' : ''}
                                   oninput="onDirectPeriodGradeInput('${sn}', 'prelim', this.value)"
                                   class="w-16 sm:w-20 px-1.5 py-1 text-center font-mono font-bold text-xs bg-surface-container border border-outline-variant rounded-lg text-primary focus:border-primary focus:ring-1 focus:ring-primary">
                        </td>
                        <td class="py-2 px-2 text-center" onclick="event.stopPropagation()">
                            <input type="number" id="table-input-midterm-${sn}" min="0" max="100" step="0.1"
                                   value="${m}" placeholder="0.0"
                                   ${isSheetLocked ? 'disabled' : ''}
                                   oninput="onDirectPeriodGradeInput('${sn}', 'midterm', this.value)"
                                   class="w-16 sm:w-20 px-1.5 py-1 text-center font-mono font-bold text-xs bg-surface-container border border-outline-variant rounded-lg text-primary focus:border-primary focus:ring-1 focus:ring-primary">
                        </td>
                        <td class="py-2 px-2 text-center" onclick="event.stopPropagation()">
                            <input type="number" id="table-input-final-${sn}" min="0" max="100" step="0.1"
                                   value="${f}" placeholder="0.0"
                                   ${isSheetLocked ? 'disabled' : ''}
                                   oninput="onDirectPeriodGradeInput('${sn}', 'final', this.value)"
                                   class="w-16 sm:w-20 px-1.5 py-1 text-center font-mono font-bold text-xs bg-surface-container border border-outline-variant rounded-lg text-primary focus:border-primary focus:ring-1 focus:ring-primary">
                        </td>
                        <td class="py-2.5 px-3 font-mono font-bold text-center text-xs">
                            <span id="cell-total-${sn}">${parseFloat(raw) > 0 ? raw + '%' : '—'}</span>
                        </td>
                        <td class="py-2.5 px-3 font-mono font-extrabold text-primary text-center text-xs">
                            <span id="cell-gpa-${sn}">${gpa > 0 ? gpa.toFixed(2) : '—'}</span>
                        </td>
                        <td class="py-2.5 px-3 text-right">
                            <span id="cell-rem-${sn}" class="${remClass}">${escapeHtml(rem)}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Calculation Transparency Panel Update
        function updateTransparencyPanel() {
            const student = studentsList[activeStudentIndex];
            if (!student) return;
            const sn = student.student_number || student.id;
            const g = studentGradesMap[sn] || {};

            document.getElementById('calc-prelim-val').innerText = `${(g.prelim || 0).toFixed(1)}%`;
            document.getElementById('calc-midterm-val').innerText = `${(g.midterm || 0).toFixed(1)}%`;
            document.getElementById('calc-final-val').innerText = `${(g.final || 0).toFixed(1)}%`;

            const gpa = g.gpa > 0 ? g.gpa.toFixed(2) : '—';
            document.getElementById('calc-computed-rating').innerText = `${(g.raw || 0).toFixed(1)}% -> ${gpa} (${g.remarks || 'Ongoing'})`;
        }

        // Rapid Scoring Mode
        function openRapidScoringModal() {
            if (!activeComponentId) {
                showToast('Please select an assessment component first.', 'error');
                return;
            }
            const comp = componentsList.find(c => c.id === activeComponentId);
            if (!comp) return;

            document.getElementById('rapid-active-comp-title').innerText = `Scoring: ${comp.component_name} (Max: ${comp.max_score} pts)`;
            document.getElementById('rapid-max-label').innerText = `/ ${comp.max_score}`;
            document.getElementById('rapid-scoring-modal').classList.remove('hidden');

            renderRapidCard();
            setTimeout(() => document.getElementById('rapid-score-input')?.focus(), 150);
        }

        function closeRapidScoringModal() {
            document.getElementById('rapid-scoring-modal').classList.add('hidden');
        }

        function renderRapidCard() {
            const student = studentsList[activeStudentIndex];
            if (!student) return;
            const sn = student.student_number || student.id;
            const comp = componentsList.find(c => c.id === activeComponentId);
            const initials = (student.full_name || 'ST').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();

            document.getElementById('rapid-avatar').innerText = initials;
            document.getElementById('rapid-student-name').innerText = formatLastNameFirst(student.full_name || 'Student Name');
            document.getElementById('rapid-student-num').innerText = `#${sn} • ${currentClass?.section || 'AIS 2A'}`;
            document.getElementById('rapid-progress-label').innerText = `Student ${activeStudentIndex + 1} of ${studentsList.length}`;

            const scores = (studentGradesMap[sn]?.component_scores || {})[activePeriod] || {};
            const val = scores[activeComponentId];
            document.getElementById('rapid-score-input').value = (val !== undefined && val !== null && val !== '') ? val : '';
        }

        function onRapidInputKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = document.getElementById('rapid-score-input').value;
                onScoreCardInput(val);
                rapidAdvance(1);
            }
        }

        function rapidAdvance(dir) {
            const newIdx = activeStudentIndex + dir;
            if (newIdx >= 0 && newIdx < studentsList.length) {
                selectStudentByIndex(newIdx);
                renderRapidCard();
                document.getElementById('rapid-score-input')?.focus();
            } else if (newIdx >= studentsList.length) {
                showToast('Completed rapid scoring for all students!', 'success');
                closeRapidScoringModal();
            }
        }

        function rapidSkip() {
            rapidAdvance(1);
        }

        // Mobile View Switching
        function switchMobileView(view) {
            const queuePane = document.getElementById('queue-pane');
            const evalPane = document.getElementById('eval-pane');
            const insightsPane = document.getElementById('insights-pane');

            ['queue', 'eval', 'insights'].forEach(v => {
                const tab = document.getElementById(`m-tab-${v}`);
                if (v === view) {
                    tab.className = 'py-1.5 text-xs font-bold rounded-lg bg-surface-container-lowest text-primary shadow-sm text-center';
                } else {
                    tab.className = 'py-1.5 text-xs font-bold rounded-lg text-on-surface-variant text-center';
                }
            });

            queuePane.classList.toggle('hidden', view !== 'queue');
            evalPane.classList.toggle('hidden', view !== 'eval');
            insightsPane.classList.toggle('hidden', view !== 'insights');
        }

        // Scheme Configuration Drawer
        function openSchemeEditor() {
            const drawer = document.getElementById('scheme-editor-drawer');
            if (!drawer) return;

            if (!drawer.classList.contains('hidden')) {
                drawer.classList.add('hidden');
                return;
            }

            const periods = ['prelim', 'midterm', 'final'];
            drawer.innerHTML = `
                <div class="flex items-center justify-between border-b border-outline-variant pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                        <h3 class="text-sm font-bold text-primary">Grading Scheme & Formula Architecture</h3>
                    </div>
                    <button onclick="document.getElementById('scheme-editor-drawer').classList.add('hidden')" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4" id="scheme-period-columns">
                    ${periods.map(p => {
                        const comps = componentsList.filter(c => (c.grading_period || '').toLowerCase() === p);
                        const total = comps.reduce((s, c) => s + (parseFloat(c.percentage_weight) || 0), 0);
                        const isOk = Math.abs(total - 100) < 0.01;

                        return `
                            <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4 space-y-3">
                                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                                    <h4 class="text-xs font-bold text-primary uppercase">${p} Period (${periodWeights[p]}%)</h4>
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full ${isOk ? 'bg-status-success/10 text-status-success' : 'bg-error/10 text-error'}">
                                        ${total.toFixed(0)}% / 100%
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    ${comps.map(c => `
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <input type="text" value="${escapeHtml(c.component_name)}" onchange="updateComponentField('${c.id}', 'component_name', this.value)" class="flex-1 min-w-0 px-2 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs font-bold">
                                            <input type="number" min="1" max="100" value="${c.percentage_weight}" onchange="updateComponentField('${c.id}', 'percentage_weight', this.value)" class="w-14 px-1.5 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-center font-bold text-xs" title="Weight %">
                                            <input type="number" min="1" max="1000" value="${c.max_score}" onchange="updateComponentField('${c.id}', 'max_score', this.value)" class="w-14 px-1.5 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-center text-xs" title="Max Score">
                                            <button onclick="deleteComponent('${c.id}', '${escapeHtml(c.component_name)}')" class="p-1 text-error/70 hover:text-error"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                                        </div>
                                    `).join('')}
                                </div>
                                <div class="flex items-center gap-1 pt-2 border-t border-outline-variant">
                                    <input type="text" id="new-name-${p}" placeholder="e.g. Activity 1" class="flex-1 min-w-0 px-2 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs">
                                    <input type="number" id="new-weight-${p}" placeholder="%" class="w-14 px-1.5 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-center font-bold text-xs">
                                    <input type="number" id="new-max-${p}" value="50" placeholder="max" class="w-14 px-1.5 py-1 bg-surface-container-lowest border border-outline-variant rounded-lg text-center text-xs">
                                    <button onclick="addComponent('${p}')" class="px-2.5 py-1 bg-primary text-white rounded-lg text-xs font-bold shrink-0">+ Add</button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-outline-variant text-xs">
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-primary">Period Weights:</span>
                        <label class="flex items-center gap-1 font-mono">Prelim <input type="number" id="pw-prelim" value="${periodWeights.prelim}" class="w-14 px-1.5 py-1 bg-surface-container-low border rounded-lg text-center font-bold text-xs"> %</label>
                        <label class="flex items-center gap-1 font-mono">Midterm <input type="number" id="pw-midterm" value="${periodWeights.midterm}" class="w-14 px-1.5 py-1 bg-surface-container-low border rounded-lg text-center font-bold text-xs"> %</label>
                        <label class="flex items-center gap-1 font-mono">Final <input type="number" id="pw-final" value="${periodWeights.final}" class="w-14 px-1.5 py-1 bg-surface-container-low border rounded-lg text-center font-bold text-xs"> %</label>
                    </div>
                    <button onclick="savePeriodWeights()" class="px-4 py-2 bg-primary text-white rounded-xl font-bold text-xs shadow-sm hover:bg-primary/90">
                        Save Scheme Formulas
                    </button>
                </div>
            `;
            drawer.classList.remove('hidden');
        }

        async function addComponent(period) {
            if (!currentClass) return;
            const name = document.getElementById(`new-name-${period}`).value.trim();
            const weight = parseFloat(document.getElementById(`new-weight-${period}`).value);
            const maxScore = parseFloat(document.getElementById(`new-max-${period}`).value) || 100;
            if (!name || isNaN(weight) || weight <= 0) {
                showToast('Enter valid component name and weight (0-100%).', 'error'); return;
            }

            try {
                const res = await fetch('/api/gradebook.php?action=add_component', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        component_name: name,
                        percentage_weight: weight,
                        max_score: maxScore,
                        grading_period: period.charAt(0).toUpperCase() + period.slice(1),
                        sort_order: componentsList.length + 1,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to add component');
                showToast('Component added successfully!', 'success');
                await loadStudioData(currentClass.id);
                openSchemeEditor();
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        }

        async function updateComponentField(id, field, value) {
            try {
                const res = await fetch('/api/gradebook.php?action=update_component', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id, [field]: value, csrf_token: csrfToken })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Update failed');
                const comp = componentsList.find(c => c.id === id);
                if (comp) comp[field] = (field === 'percentage_weight' || field === 'max_score') ? parseFloat(value) : value;
                recalculateAllGrades();
                renderAssessmentComponentsCarousel();
                renderLearnerEvaluationPanel();
                showToast('Component updated', 'success');
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        }

        async function deleteComponent(id, name) {
            if (!await npcConfirm({
                title: 'Delete Component',
                message: `Delete assessment component "${name}"?`,
                type: 'danger',
                confirmText: 'Yes, Delete'
            })) return;
            try {
                const res = await fetch('/api/gradebook.php?action=delete_component', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id, csrf_token: csrfToken })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Delete failed');
                showToast('Component deleted', 'success');
                await loadStudioData(currentClass.id);
                openSchemeEditor();
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        }

        async function savePeriodWeights() {
            const p = parseFloat(document.getElementById('pw-prelim').value) || 0;
            const m = parseFloat(document.getElementById('pw-midterm').value) || 0;
            const f = parseFloat(document.getElementById('pw-final').value) || 0;
            if (Math.abs((p + m + f) - 100) > 0.01) {
                showToast(`Period weights must equal 100% (currently ${p + m + f}%).`, 'error');
                return;
            }

            try {
                const res = await fetch('/api/gradebook.php?action=save_period_weights', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        period_weights: { prelim: p, midterm: m, final: f },
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to save weights');
                periodWeights = { prelim: p, midterm: m, final: f };
                document.getElementById('tab-weight-prelim').innerText = `${p}%`;
                document.getElementById('tab-weight-midterm').innerText = `${m}%`;
                document.getElementById('tab-weight-final').innerText = `${f}%`;
                recalculateAllGrades();
                renderAssessmentComponentsCarousel();
                renderLearnerEvaluationPanel();
                document.getElementById('scheme-editor-drawer').classList.add('hidden');
                showToast('Period weights saved!', 'success');
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        }

        // Server Draft Save & Submit
        async function saveDraftGradesToServer() {
            if (!currentClass || isSheetLocked) return;

            const gradesPayload = studentsList.map(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return {
                    student_number: sn,
                    student_name: s.full_name || '',
                    prelim: g.prelim || 0,
                    midterm: g.midterm || 0,
                    final: g.final || 0,
                    raw_grade: g.raw || 0,
                    weighted_grade: g.raw || 0,
                    equivalent_grade: g.gpa || 0,
                    final_rating: g.gpa || 0,
                    remarks: g.remarks || 'INC',
                    component_scores: g.component_scores || {}
                };
            });

            showToast('Saving draft to server...', 'info');

            try {
                const res = await fetch('/api/gradebook.php?action=save_draft', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        class_code: currentClass.code,
                        section: currentClass.section || 'AIS 2A',
                        weight_prelim: periodWeights.prelim,
                        weight_midterm: periodWeights.midterm,
                        weight_final: periodWeights.final,
                        grades: gradesPayload,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Save failed');
                markClean();
                showToast('✅ Draft saved successfully!', 'success');
            } catch (e) {
                showToast('Save Error: ' + e.message, 'error');
            }
        }

        async function submitGradeSheetForReview() {
            if (!currentClass || isSheetLocked) return;

            // Check if any student has incomplete period grades
            const incompleteStudents = studentsList.filter(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return !(parseFloat(g.prelim) > 0 && parseFloat(g.midterm) > 0 && parseFloat(g.final) > 0);
            });

            if (incompleteStudents.length > 0) {
                const msg = `Attention: ${incompleteStudents.length} of ${studentsList.length} student(s) have incomplete period grades (Prelim, Midterm, or Final is 0 or missing), which will record remarks as "INC".\n\nAre you sure you want to proceed and submit to the Registrar?`;
                if (!await npcConfirm({
                    title: 'Incomplete Grades Warning',
                    message: msg,
                    type: 'warning',
                    confirmText: 'Proceed & Submit'
                })) return;
            } else {
                if (!await npcConfirm({
                    title: 'Submit Grade Sheet',
                    message: `Are you sure you want to submit the official completed grade sheet for ${currentClass.code} (${currentClass.section || 'AIS 2A'}) to the Registrar?\n\nAll ${studentsList.length} students have complete evaluative marks. Once submitted, the grade sheet is locked for faculty edits pending approval.`,
                    type: 'info',
                    confirmText: 'Submit to Registrar'
                })) {
                    return;
                }
            }

            const gradesPayload = studentsList.map(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return {
                    student_number: sn,
                    student_name: s.full_name || '',
                    prelim: g.prelim || 0,
                    midterm: g.midterm || 0,
                    final: g.final || 0,
                    raw_grade: g.raw || 0,
                    weighted_grade: g.raw || 0,
                    equivalent_grade: g.gpa || 0,
                    final_rating: g.gpa || 0,
                    remarks: g.remarks || 'INC',
                    component_scores: g.component_scores || {}
                };
            });

            showToast('Submitting official grade sheet to Registrar...', 'info');

            try {
                const res = await fetch('/api/gradebook.php?action=submit_grades', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        class_code: currentClass.code,
                        section: currentClass.section || 'AIS 2A',
                        weight_prelim: periodWeights.prelim,
                        weight_midterm: periodWeights.midterm,
                        weight_final: periodWeights.final,
                        grades: gradesPayload,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Submission failed');
                showToast('✅ Grade sheet successfully submitted to Registrar!', 'success');
                await loadStudioData(currentClass.id);
            } catch (e) {
                alert('Submission Error: ' + e.message);
            }
        }

        // Official Grade Change Request Modal
        function openGradeChangeModal() {
            const select = document.getElementById('gcr-student-select');
            select.innerHTML = studentsList.map((s, idx) => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return `<option value="${idx}">${sn} - ${s.full_name} (Current GPA: ${g.gpa > 0 ? g.gpa.toFixed(2) : '3.00'})</option>`;
            }).join('');

            onGcrStudentSelected();
            document.getElementById('grade-change-modal').classList.remove('hidden');
        }

        function closeGradeChangeModal() {
            document.getElementById('grade-change-modal').classList.add('hidden');
        }

        function onGcrStudentSelected() {
            const idx = parseInt(document.getElementById('gcr-student-select').value) || 0;
            const s = studentsList[idx];
            if (s) {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                document.getElementById('gcr-current-grade').value = g.gpa > 0 ? g.gpa.toFixed(2) : '3.00';
            }
        }

        async function submitGradeChangeRequest() {
            const idx = parseInt(document.getElementById('gcr-student-select').value) || 0;
            const s = studentsList[idx];
            const proposed = parseFloat(document.getElementById('gcr-proposed-grade').value);
            const reason = document.getElementById('gcr-reason').value.trim();

            if (!s || isNaN(proposed) || proposed < 1.00 || proposed > 5.00 || !reason) {
                alert('Please provide a valid proposed grade (1.00 - 5.00) and complete justification.');
                return;
            }

            const sn = s.student_number || s.id;
            const g = studentGradesMap[sn] || {};

            try {
                const res = await fetch('/api/gradebook.php?action=request_grade_change', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        class_id: currentClass.id,
                        class_code: currentClass.code,
                        student_number: sn,
                        student_name: s.full_name || '',
                        original_grade: g.gpa || 3.00,
                        proposed_grade: proposed,
                        reason: reason,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Request failed');
                alert('✅ Grade change request submitted to Registrar for official review!');
                closeGradeChangeModal();
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // More Actions Menu
        function toggleMoreActionsMenu(force) {
            const menu = document.getElementById('more-actions-dropdown');
            if (force !== undefined) {
                menu.classList.toggle('hidden', !force);
            } else {
                menu.classList.toggle('hidden');
            }
        }

        // Print-Ready Official Grade Sheet
        function printOfficialGradeSheet() {
            const tbody = document.getElementById('print-sheet-tbody');
            tbody.innerHTML = studentsList.map(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return `
                    <tr>
                        <td class="border border-black p-1 text-left">${sn}</td>
                        <td class="border border-black p-1 text-left">${escapeHtml(formatLastNameFirst(s.full_name || ''))}</td>
                        <td class="border border-black p-1 text-center">${(g.prelim || 0).toFixed(1)}%</td>
                        <td class="border border-black p-1 text-center">${(g.midterm || 0).toFixed(1)}%</td>
                        <td class="border border-black p-1 text-center">${(g.final || 0).toFixed(1)}%</td>
                        <td class="border border-black p-1 text-center font-bold">${g.gpa > 0 ? g.gpa.toFixed(2) : '—'}</td>
                        <td class="border border-black p-1 text-center">${escapeHtml(g.remarks || 'Ongoing')}</td>
                    </tr>
                `;
            }).join('');

            window.print();
        }

        // CSV Export
        function exportGradeSheetCsv() {
            if (!currentClass) return;
            const headers = ['Student Number', 'Student Name', 'Prelim (%)', 'Midterm (%)', 'Final (%)', 'Overall GPA', 'Remark'];
            const rows = studentsList.map(s => {
                const sn = s.student_number || s.id;
                const g = studentGradesMap[sn] || {};
                return [
                    `"${sn}"`,
                    `"${formatLastNameFirst(s.full_name || '')}"`,
                    (g.prelim || 0).toFixed(2),
                    (g.midterm || 0).toFixed(2),
                    (g.final || 0).toFixed(2),
                    g.gpa > 0 ? g.gpa.toFixed(2) : '—',
                    `"${g.remarks || 'Ongoing'}"`
                ].join(',');
            });

            const csv = [headers.join(','), ...rows].join('\n');
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `NPC_Grades_${currentClass.code}_${currentClass.section || 'AIS2A'}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('CSV exported successfully!', 'success');
        }

        // Dirty State Tracking
        function markDirty() {
            isDirty = true;
            document.getElementById('save-status-dot').className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
            document.getElementById('save-status-text').innerText = 'Unsaved changes (Ctrl+S)';
            document.title = '● Faculty Assessment Studio';

            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(() => {
                saveDraftGradesToServer();
            }, 10000); // 10s auto-debounce
        }

        function markClean() {
            isDirty = false;
            document.getElementById('save-status-dot').className = 'w-2 h-2 rounded-full bg-status-success';
            document.getElementById('save-status-text').innerText = 'All changes saved';
            document.getElementById('last-saved-timestamp').innerText = 'Saved ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            document.title = 'Faculty Assessment Studio - Navotas Polytechnic College';
        }

        window.addEventListener('beforeunload', (e) => {
            if (!isDirty) return;
            e.preventDefault();
            e.returnValue = '';
        });

        // Toast Feedback
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('fas-toast');
            const msgEl = document.getElementById('toast-message');
            const iconEl = document.getElementById('toast-icon');

            msgEl.innerText = msg;
            if (type === 'error') {
                iconEl.innerText = 'error';
                iconEl.className = 'material-symbols-outlined text-[18px] text-red-400';
            } else if (type === 'info') {
                iconEl.innerText = 'info';
                iconEl.className = 'material-symbols-outlined text-[18px] text-blue-400';
            } else {
                iconEl.innerText = 'check_circle';
                iconEl.className = 'material-symbols-outlined text-[18px] text-emerald-400';
            }

            toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
            clearTimeout(window.toastTimeout);
            window.toastTimeout = setTimeout(() => {
                toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            }, 3000);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#btn-more-actions') && !e.target.closest('#more-actions-dropdown')) {
                toggleMoreActionsMenu(false);
            }
        });

        // Initialize on boot
        initAssessmentStudio();
    </script>
</body>
</html>
