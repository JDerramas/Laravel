<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();

// Load dynamic academic degree programs from database
require_once __DIR__ . '/../../includes/db.php';
$programsList = [];
try {
    $db = getDB();
    $stmtProg = $db->query("SELECT code, name FROM programs ORDER BY code ASC");
    if ($stmtProg) {
        $programsList = $stmtProg->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {}

if (empty($programsList)) {
    $programsList = [
        ['code' => 'AIS', 'name' => 'Associate in Information Systems'],
        ['code' => 'BSIS', 'name' => 'Bachelor of Science in Information Systems'],
        ['code' => 'BSBA - HR', 'name' => 'BSBA - Human Resource Management'],
        ['code' => 'BSBA - FM', 'name' => 'BSBA - Financial Management'],
        ['code' => 'BSBA - MM', 'name' => 'BSBA - Marketing Management'],
        ['code' => 'BSEd', 'name' => 'Bachelor of Secondary Education'],
        ['code' => 'BEEd', 'name' => 'Bachelor of Elementary Education'],
    ];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Student Directory - NPC Connect Admin</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
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
                <h2 class="text-xl font-bold text-primary hidden lg:block">Student Directory & Enrollment</h2>
            </div>
            <div class="flex items-center gap-3">
                <button id="import-btn" class="bg-secondary-container text-on-secondary-container px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-bold hover:brightness-95 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    Import Student File
                </button>
                <button id="add-student-btn" class="bg-primary text-on-primary px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold hover:opacity-90 transition-opacity npc-navy-card">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Add Student
                </button>
            </div>
        </header>

        <!-- Canvas Container -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            
            <!-- Header & Filter Bar -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight">Enrolled Students Directory</h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">Filter by academic program, section, or search student numbers and emails.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" id="search-input" onkeyup="filterStudents()" placeholder="Search name, ID, email..." class="bg-surface border border-outline-variant/60 text-xs rounded-xl pl-8 pr-3 py-2 text-primary focus:outline-none focus:border-primary w-48 sm:w-56">
                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant absolute left-2.5 top-2.5">search</span>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <select id="program-filter" onchange="filterStudents()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="all">All Programs</option>
                            <?php foreach ($programsList as $p): ?>
                                <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Filter -->
                    <div>
                        <select id="section-filter" onchange="filterStudents()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="all">All Sections</option>
                            <option value="1A">Section 1A</option>
                            <option value="1B">Section 1B</option>
                            <option value="1C">Section 1C</option>
                            <option value="2A">Section 2A</option>
                            <option value="2B">Section 2B</option>
                            <option value="2C">Section 2C</option>
                            <option value="3A">Section 3A</option>
                            <option value="3B">Section 3B</option>
                            <option value="3C">Section 3C</option>
                            <option value="4A">Section 4A</option>
                            <option value="4B">Section 4B</option>
                            <option value="4C">Section 4C</option>
                        </select>
                    </div>

                    <!-- Sort Dropdown (by Course, Section, Name, Student ID) -->
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px] text-on-surface-variant">sort</span>
                        <select id="sort-by" onchange="filterStudents()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="program-asc">Sort: Course / Program (A → Z)</option>
                            <option value="program-desc">Sort: Course / Program (Z → A)</option>
                            <option value="section-asc">Sort: Section (Ascending)</option>
                            <option value="section-desc">Sort: Section (Descending)</option>
                            <option value="name-asc">Sort: Full Name (A → Z)</option>
                            <option value="name-desc">Sort: Full Name (Z → A)</option>
                            <option value="number-asc">Sort: Student ID (Ascending)</option>
                            <option value="number-desc">Sort: Student ID (Descending)</option>
                            <option value="scholar-asc">Sort: Scholarship Status</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Students Table Container -->
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
                <div class="p-4 px-6 border-b border-outline-variant bg-surface-subtle flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-primary text-sm">Student Records</span>
                        <span id="student-count-badge" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-primary-container text-white">0</span>
                    </div>
                    <span class="text-[11px] text-on-surface-variant font-mono hidden sm:inline">Click table headers to quick-sort</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-xs font-mono text-on-surface uppercase tracking-wider select-none">
                                <th onclick="toggleSort('number')" class="py-3 px-6 font-semibold border-b border-outline-variant cursor-pointer hover:text-primary transition-colors">
                                    <div class="flex items-center gap-1">Student ID <span id="sort-icon-number" class="material-symbols-outlined text-[14px]">unfold_more</span></div>
                                </th>
                                <th onclick="toggleSort('name')" class="py-3 px-6 font-semibold border-b border-outline-variant cursor-pointer hover:text-primary transition-colors">
                                    <div class="flex items-center gap-1">Full Name <span id="sort-icon-name" class="material-symbols-outlined text-[14px]">unfold_more</span></div>
                                </th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Email Address</th>
                                <th onclick="toggleSort('program')" class="py-3 px-6 font-semibold border-b border-outline-variant cursor-pointer hover:text-primary transition-colors">
                                    <div class="flex items-center gap-1">Course / Section <span id="sort-icon-program" class="material-symbols-outlined text-[14px]">unfold_more</span></div>
                                </th>
                                <th onclick="toggleSort('scholar')" class="py-3 px-6 font-semibold border-b border-outline-variant cursor-pointer hover:text-primary transition-colors">
                                    <div class="flex items-center gap-1">Scholarship <span id="sort-icon-scholar" class="material-symbols-outlined text-[14px]">unfold_more</span></div>
                                </th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Status</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="students-list" class="divide-y divide-outline-variant/30 text-sm">
                            <tr><td colspan="7"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD SINGLE STUDENT MODAL -->
        <div id="add-student-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <h3 class="text-xl font-bold text-primary">Add Single Student</h3>
                    <button onclick="document.getElementById('add-student-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Email Address (NPC Email or any Gmail) *</label>
                        <input type="email" id="student-email" oninput="autoFillStudentDetails()" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono" placeholder="e.g. juandelacruz251505@navotaspolytechniccollege.edu.ph or user@gmail.com">
                        <p class="text-[11px] text-on-surface-variant mt-1">Accepts official NPC college email or any personal Gmail address.</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-on-surface">Full Name <span class="font-normal text-on-surface-variant text-[11px]">(Optional - auto-syncs from Google Workspace)</span></label>
                        </div>
                        <input type="text" id="student-name" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm" placeholder="Leave blank to auto-sync Google name, or type full name">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Program / Course *</label>
                            <select id="student-program" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Section *</label>
                            <select id="student-section" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold font-mono">
                                <option value="1A">Section 1A</option>
                                <option value="1B">Section 1B</option>
                                <option value="1C">Section 1C</option>
                                <option value="2A">Section 2A</option>
                                <option value="2B">Section 2B</option>
                                <option value="2C">Section 2C</option>
                                <option value="3A">Section 3A</option>
                                <option value="3B">Section 3B</option>
                                <option value="3C">Section 3C</option>
                                <option value="4A">Section 4A</option>
                                <option value="4B">Section 4B</option>
                                <option value="4C">Section 4C</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Scholarship Status</label>
                            <select id="student-scholar" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <option value="Non-Scholar">Non-Scholar</option>
                                <option value="Scholar">Scholar (Full / LGU / Academic)</option>
                            </select>
                        </div>
                        <div class="bg-surface-subtle border border-outline-variant/40 rounded-xl p-2.5">
                            <span class="block font-mono text-[9px] uppercase tracking-wider text-on-surface-variant">Auto Student ID</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="material-symbols-outlined text-[15px] text-emerald-600">badge</span>
                                <span id="student-detected-id" class="font-mono text-xs font-bold text-primary">Extracted from email</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('add-student-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="save-btn" class="px-4 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Student</button>
                </div>
            </div>
        </div>

        <!-- 2. EDIT STUDENT MODAL -->
        <div id="edit-student-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary">Edit Student Details</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Changes sync to the student's active session in real-time</p>
                    </div>
                    <button onclick="document.getElementById('edit-student-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <input type="hidden" id="edit-student-id">
                <div class="p-6 flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Full Name</label>
                        <input type="text" id="edit-student-name" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Email Address</label>
                        <input type="email" id="edit-student-email" readonly class="w-full px-4 py-2 bg-surface-subtle border border-outline-variant/30 rounded-xl text-sm font-mono text-on-surface-variant cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Student Number</label>
                        <input type="text" id="edit-student-number" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Program / Course *</label>
                            <select id="edit-student-program" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Section *</label>
                            <select id="edit-student-section" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold font-mono">
                                <option value="1A">Section 1A</option>
                                <option value="1B">Section 1B</option>
                                <option value="1C">Section 1C</option>
                                <option value="2A">Section 2A</option>
                                <option value="2B">Section 2B</option>
                                <option value="2C">Section 2C</option>
                                <option value="3A">Section 3A</option>
                                <option value="3B">Section 3B</option>
                                <option value="3C">Section 3C</option>
                                <option value="4A">Section 4A</option>
                                <option value="4B">Section 4B</option>
                                <option value="4C">Section 4C</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Scholarship Status</label>
                        <select id="edit-student-scholar" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                            <option value="Non-Scholar">Non-Scholar</option>
                            <option value="Scholar">Scholar</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('edit-student-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="update-btn" class="px-4 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- 3. BULK EXCEL FILE IMPORT MODAL -->
        <div id="import-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-excel-green">table_view</span>
                            Import Students via Excel (.xlsx / .xls)
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Automatically parses Name, Email (NPC/Gmail), Student No, Course, Section, and Scholarship</p>
                    </div>
                    <button onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
                    <!-- Default Fallback Program / Section / Scholarship if columns missing in Excel -->
                    <div class="grid grid-cols-3 gap-3 bg-surface-subtle p-3.5 rounded-xl border border-outline-variant/40">
                        <div>
                            <label class="block text-[11px] font-bold text-primary mb-1">Fallback Program</label>
                            <select id="bulk-program" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant/50 rounded-lg text-xs font-semibold">
                                <option value="BSIS">BSIS</option>
                                <option value="BSBA - HR">BSBA - HR</option>
                                <option value="BSBA - FM">BSBA - FM</option>
                                <option value="BSBA - MM">BSBA - MM</option>
                                <option value="BSEd">BSEd</option>
                                <option value="BEEd">BEEd</option>
                                <option value="AIS">AIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-primary mb-1">Fallback Section</label>
                            <select id="bulk-section" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant/50 rounded-lg text-xs font-semibold font-mono">
                                <option value="1A">Section 1A</option>
                                <option value="1B">Section 1B</option>
                                <option value="2A">Section 2A</option>
                                <option value="2B">Section 2B</option>
                                <option value="3A">Section 3A</option>
                                <option value="3B">Section 3B</option>
                                <option value="4A">Section 4A</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-primary mb-1">Fallback Scholarship</label>
                            <select id="bulk-scholar" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant/50 rounded-lg text-xs font-semibold">
                                <option value="Non-Scholar">Non-Scholar</option>
                                <option value="Scholar">Scholar</option>
                            </select>
                        </div>
                    </div>

                    <!-- File Drag and Drop Box -->
                    <div>
                        <div id="dropzone" class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-primary hover:bg-surface-subtle transition-all cursor-pointer">
                            <input type="file" id="bulk-file-input" accept=".xlsx,.xls,.csv" class="hidden">
                            <span class="material-symbols-outlined text-[40px] text-excel-green mb-1 block">upload_file</span>
                            <p class="text-sm font-semibold text-primary" id="file-label">Click or drag & drop Excel file (.xlsx, .xls) here</p>
                            <p class="text-[11px] text-on-surface-variant mt-1">Accepts Microsoft Excel spreadsheets (.xlsx, .xls) and CSV</p>
                        </div>
                    </div>

                    <!-- Interactive Live Preview Table -->
                    <div id="excel-preview-container" class="hidden space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-primary" id="preview-count-label">0 students detected</span>
                            <span class="text-on-surface-variant text-[11px]">Previewing parsed data ready for import</span>
                        </div>
                        <div class="max-h-48 overflow-y-auto border border-outline-variant/50 rounded-xl">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-surface-container-low font-mono uppercase text-[10px] text-on-surface sticky top-0">
                                    <tr>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">Name</th>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">Email</th>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">ID No.</th>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">Course</th>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">Sec</th>
                                        <th class="py-2 px-3 border-b border-outline-variant/50">Scholar</th>
                                    </tr>
                                </thead>
                                <tbody id="excel-preview-tbody" class="divide-y divide-outline-variant/20 font-mono">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status Notification Box -->
                    <div id="import-status-box" class="hidden p-4 rounded-xl border text-xs"></div>
                </div>

                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-between items-center">
                    <span class="text-[11px] text-on-surface-variant">Auto-detects columns: Name, Email, Student No, Course, Section, Scholarship</span>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                        <button id="process-import-btn" disabled class="px-5 py-2 bg-primary text-on-primary rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-90 transition-opacity font-semibold text-sm flex items-center gap-2 npc-navy-card">
                            <span class="material-symbols-outlined text-[16px]">sync</span>
                            Import & Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;
        let allStudents = [];
        let parsedExcelStudents = [];

        async function loadStudents() {
            const tbody = document.getElementById('students-list');
            try {
                const res = await fetch('/api/admin.php?action=list_users&role=student');
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load');
                allStudents = data.users || [];
                document.getElementById('student-count-badge').innerText = allStudents.length;
                renderStudents(allStudents);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-error">Failed to load student records from database.</td></tr>';
            }
        }

        function renderStudents(list) {
            const tbody = document.getElementById('students-list');
            if (!list || list.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="p-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2 block">group</span>
                            <p class="font-semibold text-lg">No students found</p>
                            <p class="text-xs text-on-surface-variant mt-1">Import an Excel file or add a student to pre-register them in the database.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = list.map(s => {
                const scholar = s.scholar_status || 'Non-Scholar';
                const isScholar = scholar.toLowerCase().includes('scholar') && !scholar.toLowerCase().includes('non');
                const scholarBadge = isScholar
                    ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Scholar</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-surface-container-low text-on-surface-variant">Non-Scholar</span>`;
                
                const status = s.status || (s.is_active ? 'Active' : 'Inactive');
                const statusColors = {
                    Active: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                    Restricted: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
                    Snoozed: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
                    Banned: 'bg-red-500/10 text-red-600 border-red-500/20',
                    Inactive: 'bg-gray-500/10 text-gray-600 border-gray-500/20'
                };
                const statusClass = statusColors[status] || 'bg-gray-500/10 text-gray-600 border-gray-500/20';

                let cleanStudentName = (s.full_name && !s.full_name.startsWith('Pending')) ? s.full_name : '';
                if (!cleanStudentName) {
                    const emPrefix = (s.email || '').split('@')[0].replace(/\d+$/, '');
                    if (emPrefix.includes('.')) cleanStudentName = emPrefix.split('.').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(' ');
                    else if (emPrefix.length >= 3) cleanStudentName = emPrefix.charAt(0).toUpperCase() + '. ' + emPrefix.slice(1).charAt(0).toUpperCase() + emPrefix.slice(2);
                    else cleanStudentName = emPrefix ? emPrefix.charAt(0).toUpperCase() + emPrefix.slice(1) : 'NPC Student';
                }
                const studentNameDisplay = escapeHtml(cleanStudentName);

                return `
                <tr class="hover:bg-surface-subtle transition-colors">
                    <td class="py-3.5 px-6 font-mono text-xs font-bold text-primary">${escapeHtml(s.student_number || 'N/A')}</td>
                    <td class="py-3.5 px-6 font-bold text-on-surface">${studentNameDisplay}</td>
                    <td class="py-3.5 px-6 text-xs text-on-surface-variant font-mono">${escapeHtml(s.email)}</td>
                    <td class="py-3.5 px-6">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-surface-container text-primary font-mono">${escapeHtml(s.program || 'BSIS')}</span>
                            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-surface-container-low text-on-surface-variant">${escapeHtml(s.section || '1A')}</span>
                        </div>
                    </td>
                    <td class="py-3.5 px-6">${scholarBadge}</td>
                    <td class="py-3.5 px-6">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${statusClass}">
                            ${escapeHtml(status)}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-right">
                        <div class="inline-flex items-center gap-2">
                            <button onclick="openEditStudent('${s.id}')" class="text-primary hover:underline text-xs font-semibold inline-flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[14px]">edit</span> Edit
                            </button>
                            <button onclick="deleteStudent('${s.id}', '${escapeHtml(s.full_name || s.email)}')" class="text-error hover:underline text-xs font-semibold inline-flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[14px]">delete</span> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        let currentSortField = 'program';
        let currentSortDir = 'asc';

        /**
         * Toggles column sorting order between Ascending and Descending.
         * Updates the dropdown selector and triggers filtering/re-rendering.
         */
        function toggleSort(field) {
            if (currentSortField === field) {
                currentSortDir = (currentSortDir === 'asc') ? 'desc' : 'asc';
            } else {
                currentSortField = field;
                currentSortDir = 'asc';
            }
            const sortSel = document.getElementById('sort-by');
            if (sortSel) {
                sortSel.value = `${field}-${currentSortDir}`;
            }
            filterStudents();
        }

        /**
         * Filters and sorts the student directory based on search query,
         * academic program, section, and sorting parameters.
         */
        function filterStudents() {
            const query = (document.getElementById('search-input').value || '').toLowerCase().trim();
            const programVal = document.getElementById('program-filter').value;
            const sectionVal = document.getElementById('section-filter').value;
            const sortVal = document.getElementById('sort-by').value;
            const [sortField, sortDir] = sortVal.split('-');

            currentSortField = sortField;
            currentSortDir = sortDir;

            let filtered = allStudents.filter(s => {
                const matchesQuery = !query || 
                    (s.full_name && s.full_name.toLowerCase().includes(query)) ||
                    (s.student_number && s.student_number.toLowerCase().includes(query)) ||
                    (s.email && s.email.toLowerCase().includes(query));

                const matchesProgram = programVal === 'all' || (s.program && s.program === programVal);
                const matchesSection = sectionVal === 'all' || (s.section && s.section === sectionVal);

                return matchesQuery && matchesProgram && matchesSection;
            });

            // Multi-criteria sorting logic
            filtered.sort((a, b) => {
                let valA = '';
                let valB = '';
                if (sortField === 'program') {
                    valA = ((a.program || '') + ' ' + (a.section || '')).toLowerCase();
                    valB = ((b.program || '') + ' ' + (b.section || '')).toLowerCase();
                } else if (sortField === 'section') {
                    valA = ((a.section || '') + ' ' + (a.program || '')).toLowerCase();
                    valB = ((b.section || '') + ' ' + (b.program || '')).toLowerCase();
                } else if (sortField === 'name') {
                    valA = (a.full_name || '').toLowerCase();
                    valB = (b.full_name || '').toLowerCase();
                } else if (sortField === 'number') {
                    valA = (a.student_number || '').toLowerCase();
                    valB = (b.student_number || '').toLowerCase();
                } else if (sortField === 'scholar') {
                    valA = (a.scholar_status || '').toLowerCase();
                    valB = (b.scholar_status || '').toLowerCase();
                }

                if (valA < valB) return sortDir === 'asc' ? -1 : 1;
                if (valA > valB) return sortDir === 'asc' ? 1 : -1;
                return 0;
            });

            // Update interactive sort arrows in table headers
            ['number', 'name', 'program', 'scholar'].forEach(col => {
                const icon = document.getElementById('sort-icon-' + col);
                if (icon) {
                    if (sortField === col) {
                        icon.textContent = (sortDir === 'asc') ? 'arrow_upward' : 'arrow_downward';
                        icon.classList.add('text-primary');
                    } else {
                        icon.textContent = 'unfold_more';
                        icon.classList.remove('text-primary');
                    }
                }
            });

            renderStudents(filtered);
        }

        /**
         * Ensures that a given option value exists within a <select> element,
         * creating and appending it if it is not present.
         */
        function ensureOptionExists(selectEl, value, label) {
            if (!value || !selectEl) return;
            for (let i = 0; i < selectEl.options.length; i++) {
                if (selectEl.options[i].value.toLowerCase() === value.toLowerCase()) {
                    selectEl.selectedIndex = i;
                    return;
                }
            }
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label || value;
            selectEl.appendChild(opt);
            selectEl.value = value;
        }

        /**
         * Opens the student edit modal and populates it with existing student information.
         */
        function openEditStudent(id) {
            const s = allStudents.find(x => String(x.id) === String(id));
            if (!s) return;
            document.getElementById('edit-student-id').value = s.id;
            document.getElementById('edit-student-name').value = s.full_name || '';
            document.getElementById('edit-student-email').value = s.email || '';
            document.getElementById('edit-student-number').value = s.student_number || '';

            const progSel = document.getElementById('edit-student-program');
            ensureOptionExists(progSel, s.program || 'BSIS', s.program || 'BSIS');

            const secSel = document.getElementById('edit-student-section');
            ensureOptionExists(secSel, s.section || '1A', 'Section ' + (s.section || '1A'));

            document.getElementById('edit-student-scholar').value = (s.scholar_status && s.scholar_status.toLowerCase().includes('scholar') && !s.scholar_status.toLowerCase().includes('non')) ? 'Scholar' : 'Non-Scholar';
            document.getElementById('edit-student-modal').classList.remove('hidden');
        }

        /**
         * Commits student profile and academic placement changes to the backend.
         */
        document.getElementById('update-btn').onclick = async () => {
            const btn = document.getElementById('update-btn');
            const id = document.getElementById('edit-student-id').value;
            const name = document.getElementById('edit-student-name').value.trim();
            const number = document.getElementById('edit-student-number').value.trim();
            const program = document.getElementById('edit-student-program').value;
            const section = document.getElementById('edit-student-section').value;
            const scholar = document.getElementById('edit-student-scholar').value;

            if (!name) {
                if (window.notify) window.notify('Name cannot be empty.', 'error');
                else alert('Name cannot be empty.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">progress_activity</span> Saving…';

            try {
                const res = await fetch('/api/admin.php?action=update_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        id,
                        full_name: name,
                        student_number: number,
                        program,
                        section,
                        scholar_status: scholar
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Update failed');
                if (window.notify) window.notify(data.message, 'success');
                else alert(data.message);
                document.getElementById('edit-student-modal').classList.add('hidden');
                loadStudents();
            } catch (err) {
                if (window.notify) window.notify('Error updating student: ' + err.message, 'error');
                else alert('Error updating student: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            }
        };

        async function deleteStudent(id, name) {
            if (!await npcConfirm({
                title: 'Delete Student Record',
                message: `Are you sure you want to permanently delete the student account for ${name}?\n\nTheir active sessions will be terminated immediately in real time.`,
                type: 'danger',
                confirmText: 'Yes, Delete Permanently'
            })) return;
            try {
                const res = await fetch('/api/admin.php?action=delete_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Delete failed');
                if (window.notify) window.notify(data.message, 'success');
                loadStudents();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        /**
         * Real-time input listener for Add Student modal.
         * Auto-populates full name and auto-detects student number from email address.
         */
        const sNameInput = document.getElementById('student-name');
        const sEmailInput = document.getElementById('student-email');
        const sIdBadge = document.getElementById('student-detected-id');

        function autoFillStudentDetails() {
            const raw = sEmailInput.value.trim();
            const prefix = raw.split('@')[0] || '';

            // Detect student ID digits in email prefix
            const digits = prefix.match(/\d{4,}/);
            if (sIdBadge) {
                if (digits) {
                    sIdBadge.textContent = digits[0];
                } else if (raw) {
                    sIdBadge.textContent = 'Auto YYYY-XXXXX';
                } else {
                    sIdBadge.textContent = 'Extracted from email';
                }
            }
        }

        // Add Single Student Handlers
        document.getElementById('add-student-btn').onclick = () => {
            sNameInput.value = '';
            sEmailInput.value = '';
            if (sIdBadge) sIdBadge.textContent = 'Extracted from email';
            document.getElementById('add-student-modal').classList.remove('hidden');
            setTimeout(() => sEmailInput.focus(), 60);
        };

        document.getElementById('save-btn').onclick = async () => {
            const btn = document.getElementById('save-btn');
            const name = sNameInput.value.trim();
            const email = sEmailInput.value.trim();
            const program = document.getElementById('student-program').value;
            const section = document.getElementById('student-section').value;
            const scholar = document.getElementById('student-scholar').value;

            // Auto-detect student number from email digits
            let autoNumber = '';
            const prefix = email.split('@')[0] || '';
            const digitsMatch = prefix.match(/\d{4,}/);
            if (digitsMatch) {
                autoNumber = digitsMatch[0];
            }

            if (!email) {
                if (window.notify) window.notify('Please enter a valid student email address.', 'error');
                else alert('Please enter a valid student email address.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">progress_activity</span> Adding…';

            try {
                const res = await fetch('/api/admin.php?action=create_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        full_name: name,
                        email: email,
                        student_number: autoNumber || 'AUTO',
                        program: program,
                        section: section,
                        scholar_status: scholar,
                        role: 'student'
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Save failed');
                if (window.notify) window.notify(data.message, 'success');
                if (window.npcConfetti) window.npcConfetti.burst(60);
                document.getElementById('add-student-modal').classList.add('hidden');
                sNameInput.value = '';
                sEmailInput.value = '';
                delete sNameInput.dataset.manualEdited;
                loadStudents();
            } catch (err) {
                if (window.notify) window.notify('Error saving student: ' + err.message, 'error');
                else alert('Error saving student: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Student';
            }
        };

        // ─── Bulk Excel File Import Logic (SheetJS) ─────────────────────────────
        document.getElementById('import-btn').onclick = () => {
            parsedExcelStudents = [];
            document.getElementById('excel-preview-container').classList.add('hidden');
            document.getElementById('import-status-box').classList.add('hidden');
            document.getElementById('process-import-btn').disabled = true;
            document.getElementById('file-label').innerText = 'Click or drag & drop Excel file (.xlsx, .xls) here';
            document.getElementById('bulk-file-input').value = '';
            document.getElementById('import-modal').classList.remove('hidden');
        };

        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('bulk-file-input');
        const fileLabel = document.getElementById('file-label');

        dropzone.onclick = () => fileInput.click();

        dropzone.ondragover = (e) => { e.preventDefault(); dropzone.classList.add('border-primary', 'bg-surface-subtle'); };
        dropzone.ondragleave = (e) => { e.preventDefault(); dropzone.classList.remove('border-primary', 'bg-surface-subtle'); };
        dropzone.ondrop = (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-surface-subtle');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleExcelFile(fileInput.files[0]);
            }
        };

        fileInput.onchange = () => {
            if (fileInput.files.length > 0) {
                handleExcelFile(fileInput.files[0]);
            }
        };

        function handleExcelFile(file) {
            if (!file) return;
            fileLabel.innerText = 'Selected: ' + file.name;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                    if (!rows || rows.length < 2) {
                        showStatus('Excel file is empty or missing data rows.', 'error');
                        return;
                    }

                    // Parse header row
                    const header = rows[0].map(h => String(h || '').trim().toLowerCase());
                    let emailCol = -1, nameCol = -1, numberCol = -1, progCol = -1, secCol = -1, scholarCol = -1;

                    header.forEach((h, idx) => {
                        if (h.includes('email') || h.includes('gmail') || h.includes('mail')) emailCol = idx;
                        else if (h.includes('name') || h.includes('pangalan') || h.includes('fullname')) nameCol = idx;
                        else if (h.includes('student no') || h.includes('student_number') || h.includes('student number') || h.includes('id number') || h.includes('student id')) numberCol = idx;
                        else if (h.includes('course') || h.includes('program') || h.includes('degree')) progCol = idx;
                        else if (h.includes('section') || h.includes('sec')) secCol = idx;
                        else if (h.includes('scholar')) scholarCol = idx;
                    });

                    // Fallback to row content inspection if headers were not detected
                    if (emailCol === -1) {
                        for (let c = 0; c < (rows[1] ? rows[1].length : 0); c++) {
                            if (String(rows[1][c] || '').includes('@')) { emailCol = c; break; }
                        }
                    }

                    if (emailCol === -1) {
                        showStatus('Could not find an Email column in this Excel sheet. Please ensure columns include Email and Name.', 'error');
                        return;
                    }

                    const defaultProg = document.getElementById('bulk-program').value;
                    const defaultSec = document.getElementById('bulk-section').value;
                    const defaultScholar = document.getElementById('bulk-scholar').value;

                    parsedExcelStudents = [];
                    for (let r = 1; r < rows.length; r++) {
                        const row = rows[r];
                        if (!row || row.length === 0) continue;

                        const email = String(row[emailCol] || '').trim().toLowerCase();
                        if (!email || !email.includes('@')) continue;

                        let name = nameCol !== -1 && row[nameCol] ? String(row[nameCol]).trim() : '';
                        if (!name) {
                            name = email.split('@')[0].replace(/[._-]/g, ' ');
                            name = name.charAt(0).toUpperCase() + name.slice(1);
                        }

                        let number = numberCol !== -1 && row[numberCol] ? String(row[numberCol]).trim() : '';
                        if (!number) {
                            const digits = email.split('@')[0].match(/\d+/);
                            number = digits ? digits[0] : '';
                        }

                        let program = progCol !== -1 && row[progCol] ? String(row[progCol]).trim() : defaultProg;
                        let section = secCol !== -1 && row[secCol] ? String(row[secCol]).trim() : defaultSec;
                        
                        let scholarRaw = scholarCol !== -1 && row[scholarCol] ? String(row[scholarCol]).trim() : defaultScholar;
                        let scholar = (scholarRaw.toLowerCase().includes('scholar') && !scholarRaw.toLowerCase().includes('non')) || scholarRaw.toLowerCase() === 'yes' ? 'Scholar' : 'Non-Scholar';

                        parsedExcelStudents.push({
                            name,
                            full_name: name,
                            email,
                            student_number: number,
                            program,
                            section,
                            scholar_status: scholar
                        });
                    }

                    if (parsedExcelStudents.length === 0) {
                        showStatus('No valid student email addresses found in this spreadsheet.', 'error');
                        return;
                    }

                    // Render Preview Table
                    const previewTbody = document.getElementById('excel-preview-tbody');
                    previewTbody.innerHTML = parsedExcelStudents.slice(0, 15).map(p => `
                        <tr>
                            <td class="py-1.5 px-3 font-sans font-bold text-on-surface">${escapeHtml(p.name)}</td>
                            <td class="py-1.5 px-3 text-on-surface-variant">${escapeHtml(p.email)}</td>
                            <td class="py-1.5 px-3 text-primary">${escapeHtml(p.student_number || 'Auto')}</td>
                            <td class="py-1.5 px-3"><span class="px-1.5 py-0.5 rounded bg-surface-container font-semibold">${escapeHtml(p.program)}</span></td>
                            <td class="py-1.5 px-3">${escapeHtml(p.section)}</td>
                            <td class="py-1.5 px-3 ${p.scholar_status === 'Scholar' ? 'text-emerald-600 font-bold' : 'text-on-surface-variant'}">${escapeHtml(p.scholar_status)}</td>
                        </tr>
                    `).join('');

                    document.getElementById('preview-count-label').innerText = `${parsedExcelStudents.length} student record(s) detected (${parsedExcelStudents.length > 15 ? 'showing first 15' : 'all shown'})`;
                    document.getElementById('excel-preview-container').classList.remove('hidden');
                    document.getElementById('process-import-btn').disabled = false;
                    showStatus(`Successfully parsed ${parsedExcelStudents.length} student(s) from Excel file. Click 'Import & Save' to write to database.`, 'success');

                } catch (err) {
                    console.error(err);
                    showStatus('Failed to read Excel file: ' + err.message, 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function showStatus(msg, type) {
            const box = document.getElementById('import-status-box');
            box.classList.remove('hidden');
            if (type === 'success') {
                box.className = 'p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 text-xs font-semibold block';
                box.innerHTML = '✅ ' + escapeHtml(msg);
            } else if (type === 'loading') {
                box.className = 'p-3 rounded-xl border border-outline-variant bg-surface-subtle text-primary text-xs block';
                box.innerHTML = '<span class="flex items-center gap-2"><span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span> ' + escapeHtml(msg) + '</span>';
            } else {
                box.className = 'p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 text-xs block';
                box.innerHTML = '❌ ' + escapeHtml(msg);
            }
        }

        document.getElementById('process-import-btn').onclick = async () => {
            if (parsedExcelStudents.length === 0) return;

            showStatus(`Saving ${parsedExcelStudents.length} students to database...`, 'loading');
            document.getElementById('process-import-btn').disabled = true;

            try {
                const res = await fetch('/api/admin.php?action=bulk_import_students', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        students: parsedExcelStudents
                    })
                });
                const result = await res.json();

                if (result.success) {
                    showStatus(result.message || 'Students imported successfully!', 'success');
                    if (window.notify) window.notify(result.message, 'success');
                    setTimeout(() => {
                        document.getElementById('import-modal').classList.add('hidden');
                        loadStudents();
                    }, 1200);
                } else {
                    showStatus(result.message || 'Import failed', 'error');
                    document.getElementById('process-import-btn').disabled = false;
                }
            } catch (err) {
                console.error(err);
                showStatus('Server communication error during import: ' + err.message, 'error');
                document.getElementById('process-import-btn').disabled = false;
            }
        };

        loadStudents();
    </script>
</body>
</html>
