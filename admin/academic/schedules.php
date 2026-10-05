<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/db.php';

$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$admin_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();

$db = getDB();
$serverClasses = [];
$serverFaculty = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, code, title, name, section, schedule_day, start_time, end_time, instructor, instructor_email, room, units FROM classes ORDER BY section ASC, code ASC");
        $serverClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    try {
        $stmt = $db->query("SELECT id, email, full_name, role FROM users WHERE role IN ('teacher', 'faculty') ORDER BY full_name ASC");
        $serverFaculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $programsList = [];
    try {
        $stmtProg = $db->query("SELECT code, name FROM programs ORDER BY code ASC");
        if ($stmtProg) {
            $programsList = $stmtProg->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}

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
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Section Schedules - NPC Admin</title>
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
                <h2 class="text-xl font-bold text-primary hidden lg:block">Student Section Schedules</h2>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('import-sched-modal').classList.remove('hidden')" class="bg-surface-container hover:bg-surface-container-high text-primary px-3.5 py-2 rounded-xl flex items-center gap-1.5 text-xs font-bold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">table_view</span>
                    Bulk Import Sched
                </button>
                <button onclick="openAddSchedModal()" class="bg-primary text-on-primary px-4 py-2 rounded-xl flex items-center gap-1.5 text-xs font-bold hover:opacity-90 transition-opacity shadow-sm npc-navy-card">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    + Add Schedule
                </button>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-8 max-w-7xl w-full mx-auto space-y-6 flex-1">
            <!-- Filter Bar -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight">Section Timetables & Offerings</h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">Manage schedules grouped by course and section (e.g. AIS 2A). Synchronizes live to student timetables.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Box -->
                    <div class="relative">
                        <input type="text" id="search-sched-input" onkeyup="filterSchedules()" placeholder="Search code, title, professor..." class="bg-surface border border-outline-variant/60 text-xs rounded-xl pl-8 pr-3 py-2 text-primary focus:outline-none focus:border-primary w-48 sm:w-56">
                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant absolute left-2.5 top-2.5">search</span>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <select id="course-filter" onchange="filterSchedules()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="all">All Programs</option>
                            <?php foreach ($programsList as $p): ?>
                                <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Filter -->
                    <div>
                        <select id="section-filter" onchange="filterSchedules()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary font-mono">
                            <option value="all">All Sections</option>
                            <option value="2A">Section 2A</option>
                            <option value="1A">Section 1A</option>
                            <option value="1B">Section 1B</option>
                            <option value="2B">Section 2B</option>
                            <option value="3A">Section 3A</option>
                            <option value="4A">Section 4A</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Schedules Grouped by Section Container -->
            <div id="schedules-container" class="space-y-6">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant shadow-sm"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></div>
            </div>
        </div>

        <!-- 1. ADD / EDIT SCHEDULE MODAL -->
        <div id="sched-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary" id="sched-modal-title">Add Subject Schedule</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Select course, subject dropdown, day, and 3-hour period</p>
                    </div>
                    <button onclick="document.getElementById('sched-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
                    <input type="hidden" id="modal-sched-id">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Course / Program</label>
                            <select id="modal-program" onchange="populateSubjectDropdown()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>" <?= $p['code'] === 'AIS' ? 'selected' : '' ?>><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Section</label>
                            <select id="modal-section" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold font-mono">
                                <option value="2A" selected>2A</option>
                                <option value="1A">1A</option>
                                <option value="1B">1B</option>
                                <option value="2B">2B</option>
                                <option value="3A">3A</option>
                                <option value="3B">3B</option>
                                <option value="4A">4A</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dependent Subject Dropdown based on Course -->
                    <div>
                        <label class="block text-xs font-bold text-primary mb-1">Select Subject (Auto-Filtered by Course)</label>
                        <select id="modal-subject-select" onchange="onSubjectSelectChange()" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl focus:outline-none focus:border-primary text-xs font-bold text-primary">
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Subject Code</label>
                            <input type="text" id="modal-code" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/50 rounded-xl font-mono uppercase font-bold text-xs" readonly>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Subject Description</label>
                            <input type="text" id="modal-title" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/50 rounded-xl font-semibold text-xs text-on-surface" readonly>
                            <input type="hidden" id="modal-units" value="3.0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Schedule Day</label>
                            <select id="modal-day" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday" selected>Saturday</option>
                                <option value="Sunday">Sunday</option>
                                <option value="TBA">TBA / Arranged</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Time Slot (+3h Standard Period)</label>
                            <select id="modal-time-range" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono font-semibold">
                                <option value="06:00 AM - 09:00 AM">06:00 AM - 09:00 AM</option>
                                <option value="06:30 AM - 09:30 AM">06:30 AM - 09:30 AM</option>
                                <option value="07:00 AM - 10:00 AM" selected>07:00 AM - 10:00 AM</option>
                                <option value="07:30 AM - 10:30 AM">07:30 AM - 10:30 AM</option>
                                <option value="08:00 AM - 11:00 AM">08:00 AM - 11:00 AM</option>
                                <option value="08:30 AM - 11:30 AM">08:30 AM - 11:30 AM</option>
                                <option value="09:00 AM - 12:00 PM">09:00 AM - 12:00 PM</option>
                                <option value="09:30 AM - 12:30 PM">09:30 AM - 12:30 PM</option>
                                <option value="10:00 AM - 01:00 PM">10:00 AM - 01:00 PM</option>
                                <option value="10:30 AM - 01:30 PM">10:30 AM - 01:30 PM</option>
                                <option value="11:00 AM - 02:00 PM">11:00 AM - 02:00 PM</option>
                                <option value="01:00 PM - 04:00 PM">01:00 PM - 04:00 PM</option>
                                <option value="01:30 PM - 04:30 PM">01:30 PM - 04:30 PM</option>
                                <option value="02:00 PM - 05:00 PM">02:00 PM - 05:00 PM</option>
                                <option value="02:30 PM - 05:30 PM">02:30 PM - 05:30 PM</option>
                                <option value="03:00 PM - 06:00 PM">03:00 PM - 06:00 PM</option>
                                <option value="03:30 PM - 06:30 PM">03:30 PM - 06:30 PM</option>
                                <option value="04:00 PM - 07:00 PM">04:00 PM - 07:00 PM</option>
                                <option value="04:30 PM - 07:30 PM">04:30 PM - 07:30 PM</option>
                                <option value="05:00 PM - 08:00 PM">05:00 PM - 08:00 PM</option>
                                <option value="05:30 PM - 08:30 PM">05:30 PM - 08:30 PM</option>
                                <option value="06:00 PM - 09:00 PM">06:00 PM - 09:00 PM</option>
                                <option value="06:30 PM - 09:30 PM">06:30 PM - 09:30 PM</option>
                                <option value="TBA">TBA / Arranged</option>
                            </select>
                        </div>
                    </div>

                    <!-- Professor Assignment by Gmail -->
                    <div class="bg-surface-subtle p-3.5 rounded-xl border border-outline-variant/60 space-y-2.5">
                        <label class="block text-xs font-bold text-primary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-secondary">badge</span>
                            Assign Professor (By Faculty Gmail)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Registered Faculty Dropdown</label>
                                <select id="modal-prof-select" onchange="onModalProfSelectChange()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold text-primary">
                                    <option value="custom">-- Custom / Type New Gmail --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Institutional Gmail</label>
                                <input type="email" id="modal-instructor-email" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono text-primary font-bold" placeholder="faculty@navotaspolytechniccollege.edu.ph">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Full Name</label>
                                <input type="text" id="modal-instructor" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold" placeholder="e.g. CASTILLO, RODERICK">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Room Assignment</label>
                                <input type="text" id="modal-room" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold" placeholder="e.g. Room 302 / Lab 2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('sched-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button onclick="saveScheduleModal()" class="px-5 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Schedule</button>
                </div>
            </div>
        </div>

        <!-- 2. BULK SCHEDULE IMPORT MODAL -->
        <div id="import-sched-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary">Import Section Schedule Table</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Paste raw schedule text with subject codes, days, times, and professors</p>
                    </div>
                    <button onclick="document.getElementById('import-sched-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
                    <!-- Target Course & Section Dropdowns (From Database) -->
                    <div class="bg-surface-subtle p-4 rounded-xl border border-outline-variant/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-primary flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-secondary">domain</span>
                                Target Academic Offering (Database Verified)
                            </label>
                            <span id="bulk-sched-target-badge" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-primary text-white">Target: AIS 2A</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Course / Program</label>
                                <select id="bulk-sched-program" onchange="updateBulkSchedTarget()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-bold text-primary">
                                    <?php foreach ($programsList as $p): ?>
                                        <option value="<?= htmlspecialchars($p['code']) ?>" <?= $p['code'] === 'AIS' ? 'selected' : '' ?>><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Section</label>
                                <select id="bulk-sched-section-select" onchange="updateBulkSchedTarget()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-bold text-primary font-mono">
                                    <option value="2A" selected>Section 2A</option>
                                    <option value="1A">Section 1A</option>
                                    <option value="1B">Section 1B</option>
                                    <option value="2B">Section 2B</option>
                                    <option value="3A">Section 3A</option>
                                    <option value="3B">Section 3B</option>
                                    <option value="4A">Section 4A</option>
                                    <option value="custom">[+] Custom Section Code</option>
                                </select>
                            </div>
                        </div>

                        <!-- Custom Section text input if "custom" is selected -->
                        <div id="bulk-sched-custom-container" class="hidden">
                            <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Enter Custom Section Code</label>
                            <input type="text" id="bulk-sched-custom-input" oninput="updateBulkSchedTarget()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono uppercase" placeholder="e.g. AIS 2A">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Paste Raw Schedule Table</label>
                        <textarea id="bulk-sched-text" rows="7" class="w-full px-4 py-2.5 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono" placeholder="AIS 2A	DM103	BUSINESS PROCESS MANAGEMENT	S	07:00 AM-10:00 AM	CASTILLO, RODERICK&#10;AIS 2A	CC107	COMPUTER PROGRAMMING 3	M	02:00 PM-05:00 PM	MORENO, EDSAN&#10;AIS 2A	IS105	ENTERPRISE ARCHITECTURE	S	10:30 AM-01:30 PM	DADOR, FREDERICK"></textarea>
                    </div>

                    <div id="sched-import-status-box" class="hidden p-4 rounded-xl border text-xs"></div>
                </div>

                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-between items-center">
                    <span class="text-[11px] text-on-surface-variant">Auto-detects days: M, T, W, Th, F, S, Su, TBA.</span>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('import-sched-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                        <button onclick="processBulkScheduleImport()" class="px-5 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm flex items-center gap-2 npc-navy-card">
                            <span class="material-symbols-outlined text-[16px]">sync</span>
                            Import All Subjects
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;
        let allSchedules = <?= json_encode($serverClasses) ?>;
        let registeredFaculty = <?= json_encode($serverFaculty) ?>;

        const npcCurriculum = {
            'AIS': [
                { code: 'DM103', title: 'BUSINESS PROCESS MANAGEMENT', units: 3.0 },
                { code: 'CC107', title: 'COMPUTER PROGRAMMING 3', units: 3.0 },
                { code: 'PATHFIT 3', title: 'DANCE', units: 2.0 },
                { code: 'IS105', title: 'ENTERPRISE ARCHITECTURE', units: 3.0 },
                { code: 'DM102', title: 'FINANCIAL MANAGEMENT', units: 3.0 },
                { code: 'ADV02', title: 'HUMAN COMPUTER INTERACTION', units: 3.0 },
                { code: 'ADV04', title: 'IS INNOVATIONS AND NEW TECHNOLOGIES', units: 3.0 },
                { code: 'QUAMETH', title: 'QUANTITATIVE METHODS', units: 3.0 },
                { code: 'ADV03', title: 'TECHNOPRENUERSHIP', units: 3.0 },
                { code: 'CC101', title: 'INTRODUCTION TO COMPUTING', units: 3.0 },
                { code: 'CC102', title: 'COMPUTER PROGRAMMING 1', units: 3.0 },
                { code: 'CC103', title: 'COMPUTER PROGRAMMING 2', units: 3.0 },
                { code: 'IS101', title: 'FUNDAMENTALS OF INFORMATION SYSTEMS', units: 3.0 },
                { code: 'IS102', title: 'IT INFRASTRUCTURE AND NETWORK', units: 3.0 },
                { code: 'IS103', title: 'SYSTEMS ANALYSIS AND DESIGN', units: 3.0 },
                { code: 'IS104', title: 'DATABASE MANAGEMENT SYSTEMS', units: 3.0 }
            ],
            'BSIS': [
                { code: 'IS101', title: 'FUNDAMENTALS OF INFORMATION SYSTEMS', units: 3.0 },
                { code: 'IS102', title: 'IT INFRASTRUCTURE AND NETWORK', units: 3.0 },
                { code: 'IS103', title: 'SYSTEMS ANALYSIS AND DESIGN', units: 3.0 },
                { code: 'IS104', title: 'DATABASE MANAGEMENT SYSTEMS', units: 3.0 },
                { code: 'IS105', title: 'ENTERPRISE ARCHITECTURE', units: 3.0 },
                { code: 'IS106', title: 'INFORMATION ASSURANCE AND SECURITY', units: 3.0 },
                { code: 'IS107', title: 'IS STRATEGY, MANAGEMENT, AND ACQUISITION', units: 3.0 },
                { code: 'IS108', title: 'DATA MINING AND WAREHOUSING', units: 3.0 },
                { code: 'CC101', title: 'INTRODUCTION TO COMPUTING', units: 3.0 },
                { code: 'CC102', title: 'COMPUTER PROGRAMMING 1', units: 3.0 },
                { code: 'CC103', title: 'COMPUTER PROGRAMMING 2', units: 3.0 },
                { code: 'CC104', title: 'DATA STRUCTURES AND ALGORITHMS', units: 3.0 },
                { code: 'CC105', title: 'APPLICATION DEV AND EMERGING TECH', units: 3.0 },
                { code: 'CC106', title: 'OBJECT-ORIENTED PROGRAMMING', units: 3.0 },
                { code: 'CC107', title: 'COMPUTER PROGRAMMING 3', units: 3.0 },
                { code: 'QUAMETH', title: 'QUANTITATIVE METHODS', units: 3.0 },
                { code: 'TECHNO', title: 'TECHNOPRENEURSHIP 101', units: 3.0 },
                { code: 'CAPSTONE1', title: 'IS CAPSTONE PROJECT 1', units: 3.0 },
                { code: 'CAPSTONE2', title: 'IS CAPSTONE PROJECT 2', units: 3.0 },
                { code: 'OJT', title: 'INDUSTRY PRACTICUM', units: 6.0 }
            ],
            'BSBA - HR': [
                { code: 'HR101', title: 'HUMAN RESOURCE MANAGEMENT', units: 3.0 },
                { code: 'HR102', title: 'RECRUITMENT AND SELECTION', units: 3.0 },
                { code: 'HR103', title: 'TRAINING AND DEVELOPMENT', units: 3.0 },
                { code: 'HR104', title: 'COMPENSATION AND BENEFITS', units: 3.0 },
                { code: 'HR105', title: 'LABOR LAWS AND LEGISLATION', units: 3.0 },
                { code: 'HR106', title: 'ORGANIZATIONAL BEHAVIOR', units: 3.0 },
                { code: 'BA101', title: 'PRINCIPLES OF MANAGEMENT', units: 3.0 },
                { code: 'BA102', title: 'BUSINESS COMMUNICATIONS', units: 3.0 },
                { code: 'BA103', title: 'TOTAL QUALITY MANAGEMENT', units: 3.0 }
            ],
            'BSBA - FM': [
                { code: 'FM101', title: 'FINANCIAL MANAGEMENT 1', units: 3.0 },
                { code: 'FM102', title: 'FINANCIAL MANAGEMENT 2', units: 3.0 },
                { code: 'FM103', title: 'BANKING AND FINANCIAL INSTITUTIONS', units: 3.0 },
                { code: 'FM104', title: 'INVESTMENT AND PORTFOLIO MANAGEMENT', units: 3.0 },
                { code: 'FM105', title: 'PUBLIC FINANCE', units: 3.0 },
                { code: 'FM106', title: 'CREDIT AND COLLECTION', units: 3.0 },
                { code: 'BA101', title: 'PRINCIPLES OF MANAGEMENT', units: 3.0 },
                { code: 'BA104', title: 'BUSINESS TAXATION', units: 3.0 }
            ],
            'BSBA - MM': [
                { code: 'MM101', title: 'MARKETING MANAGEMENT', units: 3.0 },
                { code: 'MM102', title: 'CONSUMER BEHAVIOR', units: 3.0 },
                { code: 'MM103', title: 'PROFESSIONAL SALESMANSHIP', units: 3.0 },
                { code: 'MM104', title: 'ADVERTISING AND SALES PROMOTION', units: 3.0 },
                { code: 'MM105', title: 'STRATEGIC MARKETING', units: 3.0 },
                { code: 'MM106', title: 'E-COMMERCE AND DIGITAL MARKETING', units: 3.0 },
                { code: 'BA101', title: 'PRINCIPLES OF MANAGEMENT', units: 3.0 }
            ],
            'BSEd': [
                { code: 'ED101', title: 'CHILD AND ADOLESCENT LEARNERS', units: 3.0 },
                { code: 'ED102', title: 'THE TEACHING PROFESSION', units: 3.0 },
                { code: 'ED103', title: 'ASSESSMENT IN LEARNING 1', units: 3.0 },
                { code: 'ED104', title: 'ASSESSMENT IN LEARNING 2', units: 3.0 },
                { code: 'ED105', title: 'CURRICULUM DEVELOPMENT', units: 3.0 },
                { code: 'ED106', title: 'TEACHING STRATEGIES AND METHODOLOGIES', units: 3.0 },
                { code: 'ED107', title: 'EDUCATIONAL TECHNOLOGY', units: 3.0 },
                { code: 'ED108', title: 'FIELD STUDY AND PRACTICE TEACHING', units: 6.0 }
            ],
            'BEEd': [
                { code: 'BED101', title: 'TEACHING LITERACY IN ELEMENTARY GRADES', units: 3.0 },
                { code: 'BED102', title: 'TEACHING SCIENCE IN ELEMENTARY GRADES', units: 3.0 },
                { code: 'BED103', title: 'TEACHING MATHEMATICS IN PRIMARY GRADES', units: 3.0 },
                { code: 'BED104', title: 'TEACHING SOCIAL STUDIES IN ELEMENTARY', units: 3.0 },
                { code: 'ED101', title: 'CHILD AND ADOLESCENT DEVELOPMENT', units: 3.0 },
                { code: 'ED102', title: 'THE TEACHING PROFESSION', units: 3.0 },
                { code: 'ED105', title: 'CURRICULUM DEVELOPMENT', units: 3.0 },
                { code: 'BED108', title: 'ELEMENTARY FIELD STUDY & TEACHING', units: 6.0 }
            ]
        };

        function populateSubjectDropdown() {
            const progEl = document.getElementById('modal-program');
            const select = document.getElementById('modal-subject-select');
            if (!progEl || !select) return;
            const prog = progEl.value;
            const list = npcCurriculum[prog] || npcCurriculum['AIS'];

            select.innerHTML = list.map((item, idx) => `
                <option value="${idx}">${item.code} - ${item.title} (${item.units} Units)</option>
            `).join('');

            onSubjectSelectChange();
        }

        function onSubjectSelectChange() {
            const progEl = document.getElementById('modal-program');
            const select = document.getElementById('modal-subject-select');
            if (!progEl || !select) return;
            const prog = progEl.value;
            const list = npcCurriculum[prog] || npcCurriculum['AIS'];
            const chosen = list[select.selectedIndex] || list[0];

            if (chosen) {
                const codeEl = document.getElementById('modal-code');
                if (codeEl) codeEl.value = chosen.code;
                const titleEl = document.getElementById('modal-title');
                if (titleEl) titleEl.value = chosen.title;
                const unitsEl = document.getElementById('modal-units');
                if (unitsEl) unitsEl.value = chosen.units;
            }
        }

        
        async function loadFacultyList() {
            try {
                const res = await fetch('/api/admin.php?action=get_faculty', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success && Array.isArray(data.faculty)) {
                    registeredFaculty = data.faculty;
                }
            } catch(e) {
                console.warn('Faculty list load error:', e);
            }

            const select = document.getElementById('modal-prof-select');
            if (select) {
                let opts = '<option value="custom">-- Custom / Type New Institutional Email --</option>';
                registeredFaculty.forEach(u => {
                    opts += `<option value="${u.email}" data-name="${u.full_name || ''}">${u.full_name || u.email} (${u.email})</option>`;
                });
                select.innerHTML = opts;
            }
        }

        function onModalProfSelectChange() {
            const select = document.getElementById('modal-prof-select');
            const chosenEmail = select.value;
            const emailInput = document.getElementById('modal-instructor-email');
            const nameInput = document.getElementById('modal-instructor');

            if (chosenEmail === 'custom') {
                emailInput.value = '';
                nameInput.value = '';
                emailInput.focus();
            } else {
                const opt = select.options[select.selectedIndex];
                const name = opt.getAttribute('data-name');
                emailInput.value = chosenEmail;
                nameInput.value = name || chosenEmail.split('@')[0];
            }
        }

        async function loadSchedules() {
            const container = document.getElementById('schedules-container');
            try {
                const res = await fetch('/api/admin.php?action=get_classes', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success && Array.isArray(data.classes)) {
                    allSchedules = data.classes;
                }
                renderSchedulesGrouped(allSchedules);
            } catch (err) {
                console.error(err);
                if (allSchedules && allSchedules.length > 0) {
                    renderSchedulesGrouped(allSchedules);
                } else {
                    container.innerHTML = '<div class="p-8 text-center text-error bg-surface-container-lowest rounded-2xl border border-outline-variant">Failed to load schedules from database.</div>';
                }
            }
        }

        function renderSchedulesGrouped(list) {
            const container = document.getElementById('schedules-container');
            if (!list || list.length === 0) {
                container.innerHTML = `
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant shadow-sm">
                        <span class="material-symbols-outlined text-[48px] text-outline-variant mb-3 block">calendar_month</span>
                        <h3 class="font-bold text-lg text-primary mb-1">No Section Schedules Found</h3>
                        <p class="text-xs text-on-surface-variant mb-4">Add your first section schedule using the button below or import a raw timetable.</p>
                        <div class="flex items-center justify-center gap-3">
                            <button onclick="openAddSchedModal()" class="bg-primary text-on-primary px-4 py-2 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm npc-navy-card">
                                <span class="material-symbols-outlined text-[16px]">add</span> Add Schedule
                            </button>
                            <button onclick="document.getElementById('import-sched-modal').classList.remove('hidden')" class="bg-surface-container text-primary px-4 py-2 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">table_view</span> Bulk Import
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            // Group by Section (e.g. "AIS 2A", "BSIS 3A")
            const groups = {};
            list.forEach(item => {
                const sec = (item.section || 'Unassigned Section').trim();
                if (!groups[sec]) groups[sec] = [];
                groups[sec].push(item);
            });

            container.innerHTML = Object.keys(groups).map(secName => {
                const secClasses = groups[secName];
                let totalSecUnits = 0;
                secClasses.forEach(c => totalSecUnits += (parseFloat(c.units) || 3.0));

                const rowsHtml = secClasses.map(c => `
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-primary">${c.code}</td>
                        <td class="py-3 px-4 font-semibold text-on-surface">${c.title}</td>
                        <td class="py-3 px-4 font-mono text-xs font-bold text-status-info">${c.schedule_day || 'TBA'}</td>
                        <td class="py-3 px-4 font-mono text-xs text-on-surface-variant whitespace-nowrap">${c.start_time || 'TBA'}${c.end_time && c.end_time !== 'TBA' ? ' - ' + c.end_time : ''}</td>
                        <td class="py-3 px-4 font-medium text-xs text-on-surface">${c.instructor || 'TBA'}</td>
                        <td class="py-3 px-4 text-xs font-mono">${c.room || 'Room TBA'}</td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-primary">${c.units || '3.0'}</td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditSchedModal('${c.id}')" class="p-1.5 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-primary transition-colors" title="Edit Schedule">
                                    <span class="material-symbols-outlined text-[15px]">edit</span>
                                </button>
                                <button onclick="deleteScheduleItem('${c.id}')" class="p-1.5 rounded-lg bg-error/10 hover:bg-error hover:text-white text-error transition-colors" title="Delete">
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                return `
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-4 px-6 border-b border-outline-variant/70 bg-surface-subtle flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary text-[22px]">calendar_today</span>
                                <h2 class="text-base font-bold text-primary">Section: <span class="font-mono bg-primary text-white px-2.5 py-0.5 rounded-lg text-xs tracking-wider">${secName}</span></h2>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-mono text-on-surface-variant">
                                <span>${secClasses.length} Subjects</span>
                                <span>•</span>
                                <span>Total: <strong>${totalSecUnits.toFixed(1)} Units</strong></span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low font-mono uppercase text-on-surface border-b border-outline-variant text-[11px]">
                                        <th class="py-2.5 px-4 font-bold">Code</th>
                                        <th class="py-2.5 px-4 font-bold">Subject Description</th>
                                        <th class="py-2.5 px-4 font-bold">Day</th>
                                        <th class="py-2.5 px-4 font-bold">Time Period</th>
                                        <th class="py-2.5 px-4 font-bold">Professor</th>
                                        <th class="py-2.5 px-4 font-bold">Room</th>
                                        <th class="py-2.5 px-4 font-bold text-center">Units</th>
                                        <th class="py-2.5 px-4 font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30">
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openAddSchedModal() {
            document.getElementById('sched-modal-title').innerText = 'Add Subject Schedule';
            document.getElementById('modal-sched-id').value = '';
            document.getElementById('modal-instructor').value = '';
            document.getElementById('modal-instructor-email').value = '';
            document.getElementById('modal-prof-select').value = 'custom';
            document.getElementById('modal-room').value = '';
            populateSubjectDropdown();
            document.getElementById('sched-modal').classList.remove('hidden');
        }

        function openEditSchedModal(id) {
            const target = allSchedules.find(c => c.id === id);
            if (!target) return;

            document.getElementById('sched-modal-title').innerText = 'Edit Subject Schedule';
            document.getElementById('modal-sched-id').value = target.id;

            const parts = (target.section || 'AIS 2A').split(' ');
            if (parts.length >= 2) {
                document.getElementById('modal-program').value = parts[0];
                document.getElementById('modal-section').value = parts[1];
            }

            populateSubjectDropdown();

            document.getElementById('modal-code').value = target.code || '';
            document.getElementById('modal-title').value = target.title || '';
            document.getElementById('modal-day').value = target.schedule_day || 'Monday';

            let timeRange = '';
            if (target.start_time && target.start_time !== 'TBA') {
                timeRange = `${target.start_time}${target.end_time && target.end_time !== 'TBA' ? ' - ' + target.end_time : ''}`;
            } else {
                timeRange = 'TBA';
            }

            const timeSelect = document.getElementById('modal-time-range');
            let matched = false;
            for (let i = 0; i < timeSelect.options.length; i++) {
                const optVal = timeSelect.options[i].value.replace(/\s+/g, '').toUpperCase();
                const cleanTime = timeRange.replace(/\s+/g, '').toUpperCase();
                if (optVal === cleanTime || optVal.includes(cleanTime)) {
                    timeSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched && timeRange !== 'TBA') {
                const opt = document.createElement('option');
                opt.value = timeRange;
                opt.innerText = timeRange;
                opt.selected = true;
                timeSelect.appendChild(opt);
            }

            document.getElementById('modal-instructor').value = target.instructor || '';
            const tEmail = target.instructor_email || target.created_by_email || '';
            document.getElementById('modal-instructor-email').value = tEmail;
            const profSelect = document.getElementById('modal-prof-select');
            if (tEmail) {
                profSelect.value = tEmail;
                if (!profSelect.value) profSelect.value = 'custom';
            } else {
                profSelect.value = 'custom';
            }
            document.getElementById('modal-room').value = target.room || '';
            document.getElementById('modal-units').value = target.units || 3.0;

            document.getElementById('sched-modal').classList.remove('hidden');
        }

        async function saveScheduleModal() {
            const id = document.getElementById('modal-sched-id').value;
            const program = document.getElementById('modal-program').value;
            const section = document.getElementById('modal-section').value;
            const combinedSection = `${program} ${section}`;
            const code = document.getElementById('modal-code').value.trim();
            const title = document.getElementById('modal-title').value.trim();
            const day = document.getElementById('modal-day').value;
            const timeRange = document.getElementById('modal-time-range').value;
            const instructor = document.getElementById('modal-instructor').value.trim();
            const room = document.getElementById('modal-room').value.trim();
            const units = parseFloat(document.getElementById('modal-units').value) || 3.0;

            if (!code || !title) return alert('Please select a valid subject.');

            let startTime = 'TBA';
            let endTime = 'TBA';
            if (timeRange.includes('-')) {
                const p = timeRange.split('-');
                startTime = p[0].trim();
                endTime = p[1].trim();
            } else if (timeRange) {
                startTime = timeRange;
            }

            const instructorEmail = document.getElementById('modal-instructor-email').value.trim();

            const payload = {
                code: code,
                title: title,
                section: combinedSection,
                schedule_day: day,
                start_time: startTime,
                end_time: endTime,
                instructor: instructor || (instructorEmail ? instructorEmail.split('@')[0] : 'TBA'),
                instructor_email: instructorEmail,
                room: room || 'Room TBA',
                units: units,
                csrf_token: csrfToken
            };

            if (id) payload.id = id;
            if (!id) {
                payload.created_by_name = instructor || (instructorEmail ? instructorEmail.split('@')[0] : 'TBA');
                payload.created_by_email = instructorEmail || 'admin@navotaspolytechniccollege.edu.ph';
            }

            // ── Schedule Conflict Checker (server-side truth) ──
            if (startTime !== 'TBA' && endTime !== 'TBA') {
                try {
                    const cUrl = '/api/admin.php?action=check_schedule_conflicts'
                        + '&section=' + encodeURIComponent(combinedSection)
                        + '&schedule_day=' + encodeURIComponent(day)
                        + '&start_time=' + encodeURIComponent(startTime)
                        + '&end_time=' + encodeURIComponent(endTime)
                        + '&instructor=' + encodeURIComponent(instructor || '')
                        + '&room=' + encodeURIComponent(room || '')
                        + (id ? '&exclude_id=' + encodeURIComponent(id) : '');
                    const cRes = await fetch(cUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const cData = await cRes.json();
                    if (cData.success && Array.isArray(cData.conflicts) && cData.conflicts.length > 0) {
                        const lines = cData.conflicts.slice(0, 6).map(cf => `• [${String(cf.type).toUpperCase()}] ${cf.a} ↔ ${cf.b} @ ${cf.window}`);
                        const proceed = await npcConfirm({
                            title: 'Schedule Conflict Detected',
                            message: lines.join('\n') + '\n\nDo you want to save anyway?',
                            type: 'warning',
                            confirmText: 'Save Anyway'
                        });
                        if (!proceed) return;
                    }
                } catch (cErr) { console.warn('Conflict check skipped:', cErr); }
            }

            let ok = false;
            try {
                const res = await fetch('/api/admin.php?action=save_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Save failed');
                ok = true;
            } catch (err) {
                alert('Error saving schedule: ' + err.message);
            }

            if (ok) {
                document.getElementById('sched-modal').classList.add('hidden');
                await loadSchedules();
            }
        }

        async function deleteScheduleItem(id) {
            if (!await npcConfirm({
                title: 'Delete Schedule Offering',
                message: 'Are you sure you want to delete this schedule offering?',
                type: 'danger',
                confirmText: 'Yes, Delete'
            })) return;
            let ok = false;
            let msg = '';
            try {
                const res = await fetch('/api/admin.php?action=delete_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id: id, csrf_token: csrfToken })
                });
                const data = await res.json();
                ok = !!data.success;
                msg = data.message || '';
            } catch (err) {
                msg = err.message;
            }
            if (!ok) {
                alert('Error deleting schedule: ' + (msg || 'Delete failed.'));
            } else {
                await loadSchedules();
            }
        }

        function filterSchedules() {
            const query = document.getElementById('search-sched-input').value.toLowerCase();
            const courseVal = document.getElementById('course-filter').value;
            const sectionVal = document.getElementById('section-filter').value;

            const filtered = allSchedules.filter(c => {
                const sec = c.section ? c.section.toUpperCase() : '';
                const code = c.code ? c.code.toUpperCase() : '';
                const title = c.title ? c.title.toUpperCase() : '';
                const prof = c.instructor ? c.instructor.toUpperCase() : '';

                const matchesQuery = !query || code.toLowerCase().includes(query) || title.toLowerCase().includes(query) || prof.toLowerCase().includes(query) || sec.toLowerCase().includes(query);
                const matchesCourse = courseVal === 'all' || sec.includes(courseVal.toUpperCase()) || code.includes(courseVal.toUpperCase());
                const matchesSection = sectionVal === 'all' || sec.includes(sectionVal.toUpperCase());

                return matchesQuery && matchesCourse && matchesSection;
            });

            renderSchedulesGrouped(filtered);
        }

        // 🔄 Dynamic Academic Offerings & Sections from Database
        function loadAvailableSectionsAndCourses() {
            try {
                const discoveredPrograms = new Set(['AIS', 'BSIS', 'BSBA - HR', 'BSBA - FM', 'BSBA - MM', 'BSEd', 'BEEd']);
                const discoveredSections = new Set(['1A', '1B', '2A', '2B', '3A', '3B', '4A']);

                if (Array.isArray(allSchedules)) {
                    allSchedules.forEach(c => {
                        if (c.section) {
                            const trimmed = c.section.trim();
                            const parts = trimmed.split(' ');
                            if (parts.length >= 2) {
                                discoveredPrograms.add(parts.slice(0, -1).join(' '));
                                discoveredSections.add(parts[parts.length - 1]);
                            } else {
                                discoveredSections.add(trimmed);
                            }
                        }
                    });
                }

                // Update bulk import dropdowns if present
                const progSelect = document.getElementById('bulk-sched-program');
                const secSelect = document.getElementById('bulk-sched-section-select');

                if (progSelect) {
                    const currentProg = progSelect.value || 'AIS';
                    const progList = Array.from(discoveredPrograms);
                    progSelect.innerHTML = progList.map(p => `<option value="${p}" ${p === currentProg ? 'selected' : ''}>${p}</option>`).join('');
                }

                if (secSelect) {
                    const currentSec = secSelect.value || '2B';
                    const secList = Array.from(discoveredSections).sort();
                    let secHtml = secList.map(s => `<option value="${s}" ${s === currentSec ? 'selected' : ''}>Section ${s}</option>`).join('');
                    secHtml += '<option value="custom">[+] Custom Section Code</option>';
                    secSelect.innerHTML = secHtml;
                }

                // Update filter dropdowns
                const filterSec = document.getElementById('section-filter');
                if (filterSec) {
                    const curVal = filterSec.value || 'all';
                    const sortedSecs = Array.from(discoveredSections).sort();
                    let optHtml = '<option value="all">All Sections</option>';
                    sortedSecs.forEach(s => {
                        optHtml += `<option value="${s}" ${s === curVal ? 'selected' : ''}>Section ${s}</option>`;
                    });
                    filterSec.innerHTML = optHtml;
                }

                const filterCourse = document.getElementById('course-filter');
                if (filterCourse) {
                    const curCourse = filterCourse.value || 'all';
                    const sortedProgs = Array.from(discoveredPrograms).sort();
                    let cHtml = '<option value="all">All Programs</option>';
                    sortedProgs.forEach(p => {
                        cHtml += `<option value="${p}" ${p === curCourse ? 'selected' : ''}>${p}</option>`;
                    });
                    filterCourse.innerHTML = cHtml;
                }

                updateBulkSchedTarget();
            } catch (err) {
                console.warn('Error loading dynamic courses/sections:', err);
            }
        }

        function updateBulkSchedTarget() {
            const progSelect = document.getElementById('bulk-sched-program');
            const secSelect = document.getElementById('bulk-sched-section-select');
            const customContainer = document.getElementById('bulk-sched-custom-container');
            const customInput = document.getElementById('bulk-sched-custom-input');
            const targetBadge = document.getElementById('bulk-sched-target-badge');

            if (!progSelect || !secSelect) return 'AIS 2A';

            let finalSection = '';
            if (secSelect.value === 'custom') {
                if (customContainer) customContainer.classList.remove('hidden');
                finalSection = customInput && customInput.value.trim() ? customInput.value.trim().toUpperCase() : `${progSelect.value} Custom`;
            } else {
                if (customContainer) customContainer.classList.add('hidden');
                finalSection = `${progSelect.value} ${secSelect.value}`.trim();
            }

            if (targetBadge) {
                targetBadge.innerText = `Target: ${finalSection}`;
            }
            return finalSection;
        }

        async function processBulkScheduleImport() {
            const text = document.getElementById('bulk-sched-text').value.trim();
            const section = updateBulkSchedTarget();
            const statusBox = document.getElementById('sched-import-status-box');

            if (!text) return alert('Please paste the schedule table.');

            const formData = new FormData();
            formData.append('schedule_text', text);
            formData.append('section', section || 'AIS 2A');
            if (window.__CSRF__) formData.append('csrf_token', window.__CSRF__);

            statusBox.className = 'p-4 rounded-xl border border-outline-variant bg-surface-subtle text-primary block';
            statusBox.innerHTML = '<span class="flex items-center gap-2"><span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span> Parsing schedule rows...</span>';

            try {
                const res = await fetch('/admin/importers/import_schedules.php', {
                    method: 'POST',
                    headers: window.__CSRF__ ? { 'X-CSRF-Token': window.__CSRF__ } : {},
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    statusBox.className = 'p-4 rounded-xl border border-status-success/40 bg-status-success/10 text-status-success block font-semibold';
                    statusBox.innerHTML = `✅ ${result.message}`;
                    setTimeout(() => {
                        document.getElementById('import-sched-modal').classList.add('hidden');
                        statusBox.classList.add('hidden');
                        document.getElementById('bulk-sched-text').value = '';
                        loadFacultyList();
                        loadAvailableSectionsAndCourses();
                        loadSchedules();
                    }, 1200);
                } else {
                    statusBox.className = 'p-4 rounded-xl border border-error/40 bg-error/10 text-error block';
                    statusBox.innerHTML = `❌ ${result.message}`;
                }
            } catch (err) {
                console.error(err);
                statusBox.className = 'p-4 rounded-xl border border-error/40 bg-error/10 text-error block';
                statusBox.innerHTML = '❌ Server communication error during schedule parsing.';
            }
        }

        populateSubjectDropdown();
        loadFacultyList();
        loadAvailableSectionsAndCourses();
        renderSchedulesGrouped(allSchedules);
        loadSchedules();
    </script>
</body>
</html>
