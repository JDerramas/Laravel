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
    <title>Classes & Schedules - NPC Connect Admin</title>
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
                <h2 class="text-xl font-bold text-primary hidden lg:block">Class Schedules Management</h2>
            </div>
            <div class="flex items-center gap-3">
                <button id="import-sched-btn" class="bg-secondary-container text-on-secondary-container px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-bold hover:brightness-95 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">table_view</span>
                    Import Section Schedule
                </button>
                <button id="add-class-btn" class="bg-primary text-on-primary px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold hover:opacity-90 transition-opacity npc-navy-card">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Single Class
                </button>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight">Course Offerings & Weekly Schedules</h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">Edit, modify, reschedule, or remove subject offerings across Sunday to Saturday.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Box -->
                    <div class="relative">
                        <input type="text" id="search-class-input" onkeyup="filterAdminClasses()" placeholder="Search code, title, professor..." class="bg-surface border border-outline-variant/60 text-xs rounded-xl pl-8 pr-3 py-2 text-primary focus:outline-none focus:border-primary w-48 sm:w-56">
                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant absolute left-2.5 top-2.5">search</span>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <select id="course-filter" onchange="filterAdminClasses()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="all">All Programs</option>
                            <?php foreach ($programsList as $p): ?>
                                <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Filter -->
                    <div>
                        <select id="section-filter" onchange="filterAdminClasses()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary font-mono">
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

            <!-- Grid of Classes -->
            <div id="classes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="col-span-full bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></div>
            </div>
        </div>

        <!-- 1. ADD SINGLE CLASS MODAL -->
        <div id="add-class-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary">Add Single Subject Schedule</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Select program, section, day (Sun-Sat), and standard 3-hour period dropdown</p>
                    </div>
                    <button id="close-modal-btn" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Course / Program</label>
                            <select id="class-program" onchange="populateAdminSubjectDropdown('class')" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>" <?= $p['code'] === 'AIS' ? 'selected' : '' ?>><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Section</label>
                            <select id="class-section" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold font-mono">
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
                        <select id="class-subject-select" onchange="onAdminSubjectSelectChange('class')" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl focus:outline-none focus:border-primary text-xs font-bold text-primary">
                            <!-- Populated dynamically -->
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Subject Code</label>
                            <input type="text" id="class-code" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/50 rounded-xl font-mono uppercase font-bold text-xs" readonly>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Subject Description</label>
                            <input type="text" id="class-title" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/50 rounded-xl font-semibold text-xs text-on-surface" readonly>
                            <input type="hidden" id="class-units" value="3.0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Schedule Day</label>
                            <select id="class-day" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
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
                            <select id="class-time-range" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono font-semibold">
                                <!-- Morning 3-hour Slots -->
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

                                <!-- Afternoon 3-hour Slots -->
                                <option value="01:00 PM - 04:00 PM">01:00 PM - 04:00 PM</option>
                                <option value="01:30 PM - 04:30 PM">01:30 PM - 04:30 PM</option>
                                <option value="02:00 PM - 05:00 PM">02:00 PM - 05:00 PM</option>
                                <option value="02:30 PM - 05:30 PM">02:30 PM - 05:30 PM</option>
                                <option value="03:00 PM - 06:00 PM">03:00 PM - 06:00 PM</option>
                                <option value="03:30 PM - 06:30 PM">03:30 PM - 06:30 PM</option>
                                <option value="04:00 PM - 07:00 PM">04:00 PM - 07:00 PM</option>
                                <option value="04:30 PM - 07:30 PM">04:30 PM - 07:30 PM</option>

                                <!-- Evening 3-hour Slots -->
                                <option value="05:00 PM - 08:00 PM">05:00 PM - 08:00 PM</option>
                                <option value="05:30 PM - 08:30 PM">05:30 PM - 08:30 PM</option>
                                <option value="06:00 PM - 09:00 PM">06:00 PM - 09:00 PM</option>
                                <option value="06:30 PM - 09:30 PM">06:30 PM - 09:30 PM</option>
                                <option value="TBA">TBA / To be arranged</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-surface-subtle p-3.5 rounded-xl border border-outline-variant/60 space-y-2.5">
                        <label class="block text-xs font-bold text-primary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-secondary">badge</span>
                            Assign Professor (By Faculty Gmail)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Registered Faculty Dropdown</label>
                                <select id="class-prof-select" onchange="onClassProfSelectChange()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold text-primary">
                                    <option value="custom">-- Custom / Type New Gmail --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Institutional Gmail</label>
                                <input type="email" id="class-instructor-email" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono text-primary font-bold" placeholder="faculty@navotaspolytechniccollege.edu.ph">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Full Name</label>
                                <input type="text" id="class-instructor" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold" placeholder="e.g. CASTILLO, RODERICK">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Room Assignment</label>
                                <input type="text" id="class-room" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold" placeholder="e.g. Room 302 / Lab 2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button id="cancel-modal-btn" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="save-class-btn" class="px-4 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Class</button>
                </div>
            </div>
        </div>

        <!-- 2. EDIT CLASS MODAL (WITH DROPDOWNS) -->
        <div id="edit-class-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">edit_calendar</span>
                            Edit Subject Schedule
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Select standard time period and program dropdowns to avoid typos</p>
                    </div>
                    <button onclick="document.getElementById('edit-class-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">
                    <input type="hidden" id="edit-class-id">

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Subject Code</label>
                            <input type="text" id="edit-class-code" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono uppercase font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Program</label>
                            <select id="edit-class-program" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Section</label>
                            <select id="edit-class-section" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold font-mono">
                                <option value="2A">2A</option>
                                <option value="1A">1A</option>
                                <option value="1B">1B</option>
                                <option value="2B">2B</option>
                                <option value="3A">3A</option>
                                <option value="3B">3B</option>
                                <option value="4A">4A</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Subject Description / Title</label>
                        <input type="text" id="edit-class-title" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Schedule Day</label>
                            <select id="edit-class-day" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                                <option value="TBA">TBA / Arranged</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Time Slot (+3h Standard Period)</label>
                            <select id="edit-class-time-range" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono font-semibold">
                                <!-- Morning 3-hour Slots -->
                                <option value="06:00 AM - 09:00 AM">06:00 AM - 09:00 AM</option>
                                <option value="06:30 AM - 09:30 AM">06:30 AM - 09:30 AM</option>
                                <option value="07:00 AM - 10:00 AM">07:00 AM - 10:00 AM</option>
                                <option value="07:30 AM - 10:30 AM">07:30 AM - 10:30 AM</option>
                                <option value="08:00 AM - 11:00 AM">08:00 AM - 11:00 AM</option>
                                <option value="08:30 AM - 11:30 AM">08:30 AM - 11:30 AM</option>
                                <option value="09:00 AM - 12:00 PM">09:00 AM - 12:00 PM</option>
                                <option value="09:30 AM - 12:30 PM">09:30 AM - 12:30 PM</option>
                                <option value="10:00 AM - 01:00 PM">10:00 AM - 01:00 PM</option>
                                <option value="10:30 AM - 01:30 PM">10:30 AM - 01:30 PM</option>
                                <option value="11:00 AM - 02:00 PM">11:00 AM - 02:00 PM</option>

                                <!-- Afternoon 3-hour Slots -->
                                <option value="01:00 PM - 04:00 PM">01:00 PM - 04:00 PM</option>
                                <option value="01:30 PM - 04:30 PM">01:30 PM - 04:30 PM</option>
                                <option value="02:00 PM - 05:00 PM">02:00 PM - 05:00 PM</option>
                                <option value="02:30 PM - 05:30 PM">02:30 PM - 05:30 PM</option>
                                <option value="03:00 PM - 06:00 PM">03:00 PM - 06:00 PM</option>
                                <option value="03:30 PM - 06:30 PM">03:30 PM - 06:30 PM</option>
                                <option value="04:00 PM - 07:00 PM">04:00 PM - 07:00 PM</option>
                                <option value="04:30 PM - 07:30 PM">04:30 PM - 07:30 PM</option>

                                <!-- Evening 3-hour Slots -->
                                <option value="05:00 PM - 08:00 PM">05:00 PM - 08:00 PM</option>
                                <option value="05:30 PM - 08:30 PM">05:30 PM - 08:30 PM</option>
                                <option value="06:00 PM - 09:00 PM">06:00 PM - 09:00 PM</option>
                                <option value="06:30 PM - 09:30 PM">06:30 PM - 09:30 PM</option>
                                <option value="TBA">TBA / To be arranged</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-surface-subtle p-3.5 rounded-xl border border-outline-variant/60 space-y-2.5">
                        <label class="block text-xs font-bold text-primary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-secondary">badge</span>
                            Assign Professor (By Faculty Gmail)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Registered Faculty Dropdown</label>
                                <select id="edit-class-prof-select" onchange="onEditClassProfSelectChange()" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold text-primary">
                                    <option value="custom">-- Custom / Type New Gmail --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Institutional Gmail</label>
                                <input type="email" id="edit-class-instructor-email" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono text-primary font-bold">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Professor Full Name</label>
                                <input type="text" id="edit-class-instructor" class="w-full px-3 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Room Assignment</label>
                                <input type="text" id="edit-class-room" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-semibold">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Units</label>
                        <input type="number" step="0.5" id="edit-class-units" class="w-28 px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono font-bold" value="3.0">
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('edit-class-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="update-class-btn" onclick="saveEditedClass()" class="px-5 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm flex items-center gap-1.5 npc-navy-card">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. BULK SCHEDULE IMPORT MODAL -->
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
                        <label class="block text-xs font-semibold text-on-surface mb-1">Paste Raw Schedule Table (Tab or Space separated)</label>
                        <textarea id="bulk-sched-text" rows="7" class="w-full px-4 py-2.5 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-xs font-mono" placeholder="AIS 2A	DM103	BUSINESS PROCESS MANAGEMENT	S	07:00 AM-10:00 AM	CASTILLO, RODERICK&#10;AIS 2A	CC107	COMPUTER PROGRAMMING 3	M	02:00 PM-05:00 PM	MORENO, EDSAN&#10;AIS 2A	IS105	ENTERPRISE ARCHITECTURE	S	10:30 AM-01:30 PM	DADOR, FREDERICK"></textarea>
                    </div>

                    <div id="sched-import-status-box" class="hidden p-4 rounded-xl border text-xs"></div>
                </div>

                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-between items-center">
                    <span class="text-[11px] text-on-surface-variant">Auto-detects days: M, T, W, Th, F, S, Su, TBA.</span>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('import-sched-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                        <button id="process-sched-import-btn" class="px-5 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm flex items-center gap-2 npc-navy-card">
                            <span class="material-symbols-outlined text-[16px]">sync</span>
                            Import All Subjects
                        </button>
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

        let allClasses = [];


        const npcCurriculum = {
            'AIS': [{
                    code: 'DM103',
                    title: 'BUSINESS PROCESS MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'CC107',
                    title: 'COMPUTER PROGRAMMING 3',
                    units: 3.0
                },
                {
                    code: 'PATHFIT 3',
                    title: 'DANCE',
                    units: 2.0
                },
                {
                    code: 'IS105',
                    title: 'ENTERPRISE ARCHITECTURE',
                    units: 3.0
                },
                {
                    code: 'DM102',
                    title: 'FINANCIAL MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'ADV02',
                    title: 'HUMAN COMPUTER INTERACTION',
                    units: 3.0
                },
                {
                    code: 'ADV04',
                    title: 'IS INNOVATIONS AND NEW TECHNOLOGIES',
                    units: 3.0
                },
                {
                    code: 'QUAMETH',
                    title: 'QUANTITATIVE METHODS',
                    units: 3.0
                },
                {
                    code: 'ADV03',
                    title: 'TECHNOPRENUERSHIP',
                    units: 3.0
                },
                {
                    code: 'CC101',
                    title: 'INTRODUCTION TO COMPUTING',
                    units: 3.0
                },
                {
                    code: 'CC102',
                    title: 'COMPUTER PROGRAMMING 1',
                    units: 3.0
                },
                {
                    code: 'CC103',
                    title: 'COMPUTER PROGRAMMING 2',
                    units: 3.0
                },
                {
                    code: 'IS101',
                    title: 'FUNDAMENTALS OF INFORMATION SYSTEMS',
                    units: 3.0
                },
                {
                    code: 'IS102',
                    title: 'IT INFRASTRUCTURE AND NETWORK',
                    units: 3.0
                },
                {
                    code: 'IS103',
                    title: 'SYSTEMS ANALYSIS AND DESIGN',
                    units: 3.0
                },
                {
                    code: 'IS104',
                    title: 'DATABASE MANAGEMENT SYSTEMS',
                    units: 3.0
                }
            ],
            'BSIS': [{
                    code: 'IS101',
                    title: 'FUNDAMENTALS OF INFORMATION SYSTEMS',
                    units: 3.0
                },
                {
                    code: 'IS102',
                    title: 'IT INFRASTRUCTURE AND NETWORK',
                    units: 3.0
                },
                {
                    code: 'IS103',
                    title: 'SYSTEMS ANALYSIS AND DESIGN',
                    units: 3.0
                },
                {
                    code: 'IS104',
                    title: 'DATABASE MANAGEMENT SYSTEMS',
                    units: 3.0
                },
                {
                    code: 'IS105',
                    title: 'ENTERPRISE ARCHITECTURE',
                    units: 3.0
                },
                {
                    code: 'IS106',
                    title: 'INFORMATION ASSURANCE AND SECURITY',
                    units: 3.0
                },
                {
                    code: 'IS107',
                    title: 'IS STRATEGY, MANAGEMENT, AND ACQUISITION',
                    units: 3.0
                },
                {
                    code: 'IS108',
                    title: 'DATA MINING AND WAREHOUSING',
                    units: 3.0
                },
                {
                    code: 'CC101',
                    title: 'INTRODUCTION TO COMPUTING',
                    units: 3.0
                },
                {
                    code: 'CC102',
                    title: 'COMPUTER PROGRAMMING 1',
                    units: 3.0
                },
                {
                    code: 'CC103',
                    title: 'COMPUTER PROGRAMMING 2',
                    units: 3.0
                },
                {
                    code: 'CC104',
                    title: 'DATA STRUCTURES AND ALGORITHMS',
                    units: 3.0
                },
                {
                    code: 'CC105',
                    title: 'APPLICATION DEV AND EMERGING TECH',
                    units: 3.0
                },
                {
                    code: 'CC106',
                    title: 'OBJECT-ORIENTED PROGRAMMING',
                    units: 3.0
                },
                {
                    code: 'CC107',
                    title: 'COMPUTER PROGRAMMING 3',
                    units: 3.0
                },
                {
                    code: 'QUAMETH',
                    title: 'QUANTITATIVE METHODS',
                    units: 3.0
                },
                {
                    code: 'TECHNO',
                    title: 'TECHNOPRENEURSHIP 101',
                    units: 3.0
                },
                {
                    code: 'CAPSTONE1',
                    title: 'IS CAPSTONE PROJECT 1',
                    units: 3.0
                },
                {
                    code: 'CAPSTONE2',
                    title: 'IS CAPSTONE PROJECT 2',
                    units: 3.0
                },
                {
                    code: 'OJT',
                    title: 'INDUSTRY PRACTICUM',
                    units: 6.0
                }
            ],
            'BSBA - HR': [{
                    code: 'HR101',
                    title: 'HUMAN RESOURCE MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'HR102',
                    title: 'RECRUITMENT AND SELECTION',
                    units: 3.0
                },
                {
                    code: 'HR103',
                    title: 'TRAINING AND DEVELOPMENT',
                    units: 3.0
                },
                {
                    code: 'HR104',
                    title: 'COMPENSATION AND BENEFITS',
                    units: 3.0
                },
                {
                    code: 'HR105',
                    title: 'LABOR LAWS AND LEGISLATION',
                    units: 3.0
                },
                {
                    code: 'HR106',
                    title: 'ORGANIZATIONAL BEHAVIOR',
                    units: 3.0
                },
                {
                    code: 'BA101',
                    title: 'PRINCIPLES OF MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'BA102',
                    title: 'BUSINESS COMMUNICATIONS',
                    units: 3.0
                },
                {
                    code: 'BA103',
                    title: 'TOTAL QUALITY MANAGEMENT',
                    units: 3.0
                }
            ],
            'BSBA - FM': [{
                    code: 'FM101',
                    title: 'FINANCIAL MANAGEMENT 1',
                    units: 3.0
                },
                {
                    code: 'FM102',
                    title: 'FINANCIAL MANAGEMENT 2',
                    units: 3.0
                },
                {
                    code: 'FM103',
                    title: 'BANKING AND FINANCIAL INSTITUTIONS',
                    units: 3.0
                },
                {
                    code: 'FM104',
                    title: 'INVESTMENT AND PORTFOLIO MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'FM105',
                    title: 'PUBLIC FINANCE',
                    units: 3.0
                },
                {
                    code: 'FM106',
                    title: 'CREDIT AND COLLECTION',
                    units: 3.0
                },
                {
                    code: 'BA101',
                    title: 'PRINCIPLES OF MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'BA104',
                    title: 'BUSINESS TAXATION',
                    units: 3.0
                }
            ],
            'BSBA - MM': [{
                    code: 'MM101',
                    title: 'MARKETING MANAGEMENT',
                    units: 3.0
                },
                {
                    code: 'MM102',
                    title: 'CONSUMER BEHAVIOR',
                    units: 3.0
                },
                {
                    code: 'MM103',
                    title: 'PROFESSIONAL SALESMANSHIP',
                    units: 3.0
                },
                {
                    code: 'MM104',
                    title: 'ADVERTISING AND SALES PROMOTION',
                    units: 3.0
                },
                {
                    code: 'MM105',
                    title: 'STRATEGIC MARKETING',
                    units: 3.0
                },
                {
                    code: 'MM106',
                    title: 'E-COMMERCE AND DIGITAL MARKETING',
                    units: 3.0
                },
                {
                    code: 'BA101',
                    title: 'PRINCIPLES OF MANAGEMENT',
                    units: 3.0
                }
            ],
            'BSEd': [{
                    code: 'ED101',
                    title: 'CHILD AND ADOLESCENT LEARNERS',
                    units: 3.0
                },
                {
                    code: 'ED102',
                    title: 'THE TEACHING PROFESSION',
                    units: 3.0
                },
                {
                    code: 'ED103',
                    title: 'ASSESSMENT IN LEARNING 1',
                    units: 3.0
                },
                {
                    code: 'ED104',
                    title: 'ASSESSMENT IN LEARNING 2',
                    units: 3.0
                },
                {
                    code: 'ED105',
                    title: 'CURRICULUM DEVELOPMENT',
                    units: 3.0
                },
                {
                    code: 'ED106',
                    title: 'TEACHING STRATEGIES AND METHODOLOGIES',
                    units: 3.0
                },
                {
                    code: 'ED107',
                    title: 'EDUCATIONAL TECHNOLOGY',
                    units: 3.0
                },
                {
                    code: 'ED108',
                    title: 'FIELD STUDY AND PRACTICE TEACHING',
                    units: 6.0
                }
            ],
            'BEEd': [{
                    code: 'BED101',
                    title: 'TEACHING LITERACY IN ELEMENTARY GRADES',
                    units: 3.0
                },
                {
                    code: 'BED102',
                    title: 'TEACHING SCIENCE IN ELEMENTARY GRADES',
                    units: 3.0
                },
                {
                    code: 'BED103',
                    title: 'TEACHING MATHEMATICS IN PRIMARY GRADES',
                    units: 3.0
                },
                {
                    code: 'BED104',
                    title: 'TEACHING SOCIAL STUDIES IN ELEMENTARY',
                    units: 3.0
                },
                {
                    code: 'ED101',
                    title: 'CHILD AND ADOLESCENT DEVELOPMENT',
                    units: 3.0
                },
                {
                    code: 'ED102',
                    title: 'THE TEACHING PROFESSION',
                    units: 3.0
                },
                {
                    code: 'ED105',
                    title: 'CURRICULUM DEVELOPMENT',
                    units: 3.0
                },
                {
                    code: 'BED108',
                    title: 'ELEMENTARY FIELD STUDY & TEACHING',
                    units: 6.0
                }
            ]
        };

        function populateAdminSubjectDropdown(prefix) {
            const prog = document.getElementById(`${prefix}-program`).value;
            const select = document.getElementById(`${prefix}-subject-select`);
            const list = npcCurriculum[prog] || npcCurriculum['AIS'];

            select.innerHTML = list.map((item, idx) => `
                <option value="${idx}">${item.code} - ${item.title} (${item.units} Units)</option>
            `).join('');

            onAdminSubjectSelectChange(prefix);
        }

        function onAdminSubjectSelectChange(prefix) {
            const progEl = document.getElementById(`${prefix}-program`);
            const select = document.getElementById(`${prefix}-subject-select`);
            if (!progEl || !select) return;
            const prog = progEl.value;
            const list = npcCurriculum[prog] || npcCurriculum['AIS'];
            const chosen = list[select.selectedIndex] || list[0];

            if (chosen) {
                const codeEl = document.getElementById(`${prefix}-code`);
                if (codeEl) codeEl.value = chosen.code;
                const titleEl = document.getElementById(`${prefix}-title`);
                if (titleEl) titleEl.value = chosen.title;
                const unitsEl = document.getElementById(`${prefix}-units`);
                if (unitsEl) unitsEl.value = chosen.units;
            }
        }

        
        let registeredFaculty = [];

        async function loadFacultyList() {
            try {
                const { data } = await supabaseClient.from('users').select('id, email, full_name, role');
                registeredFaculty = data || [];

                ['class-prof-select', 'edit-class-prof-select'].forEach(id => {
                    const select = document.getElementById(id);
                    if (select) {
                        let opts = '<option value="custom">-- Custom / Type New Gmail --</option>';
                        registeredFaculty.forEach(u => {
                            opts += `<option value="${u.email}" data-name="${u.full_name || ''}">${u.full_name || u.email} (${u.email})</option>`;
                        });
                        select.innerHTML = opts;
                    }
                });
            } catch(e) {
                console.warn('Faculty list load error:', e);
            }
        }

        function onClassProfSelectChange() {
            const select = document.getElementById('class-prof-select');
            const emailInput = document.getElementById('class-instructor-email');
            const nameInput = document.getElementById('class-instructor');
            if (select.value === 'custom') {
                emailInput.value = '';
                nameInput.value = '';
            } else {
                const opt = select.options[select.selectedIndex];
                emailInput.value = select.value;
                nameInput.value = opt.getAttribute('data-name') || select.value.split('@')[0];
            }
        }

        function onEditClassProfSelectChange() {
            const select = document.getElementById('edit-class-prof-select');
            const emailInput = document.getElementById('edit-class-instructor-email');
            const nameInput = document.getElementById('edit-class-instructor');
            if (select.value === 'custom') {
                emailInput.value = '';
                nameInput.value = '';
            } else {
                const opt = select.options[select.selectedIndex];
                emailInput.value = select.value;
                nameInput.value = opt.getAttribute('data-name') || select.value.split('@')[0];
            }
        }

        async function loadClasses() {
            const grid = document.getElementById('classes-grid');
            try {
                const {
                    data,
                    error
                } = await supabaseClient.from('classes').select('*').order('created_at', {
                    ascending: false
                });
                if (error) throw error;
                allClasses = data || [];
                renderAdminClasses(allClasses);
            } catch (err) {
                console.error(err);
                grid.innerHTML = '<div class="col-span-full p-8 text-center text-error">Failed to load classes from database.</div>';
            }
        }

        function renderAdminClasses(list) {
            const grid = document.getElementById('classes-grid');
            if (!list || list.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant shadow-sm">
                        <span class="material-symbols-outlined text-[48px] text-outline-variant mb-3 block">calendar_month</span>
                        <h3 class="font-bold text-lg text-primary mb-1">No Classes Scheduled</h3>
                        <p class="text-xs text-on-surface-variant mb-4">Add or import subject schedules for your sections.</p>
                        <button onclick="document.getElementById('import-sched-modal').classList.remove('hidden')" class="bg-primary text-on-primary px-4 py-2 rounded-xl text-xs font-semibold hover:opacity-90 inline-flex items-center gap-2 npc-navy-card">
                            <span class="material-symbols-outlined text-[16px]">table_view</span> Import Section Schedule
                        </button>
                    </div>
                `;
                return;
            }

            grid.innerHTML = list.map(c => `
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2.5 py-0.5 bg-primary/10 text-primary font-mono text-xs font-bold rounded-lg">${c.code}</span>
                            <span class="text-xs font-mono text-on-surface-variant font-bold bg-surface-container-low px-2 py-0.5 rounded">${c.section || 'AIS 2A'}</span>
                        </div>
                        <h3 class="font-bold text-sm text-primary mb-2">${c.title}</h3>
                        <div class="space-y-1 text-xs text-on-surface-variant font-medium">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[15px] text-status-info">schedule</span>
                                <span>${c.schedule_day || 'TBA'} (${c.start_time || 'TBA'}${c.end_time && c.end_time !== 'TBA' ? ' - ' + c.end_time : ''})</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[15px] text-primary">person</span>
                                <span>${c.instructor || c.created_by_name || 'TBA'}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] font-mono text-primary/80 bg-surface-container-low px-2 py-1 rounded-md border border-outline-variant/30 mt-1">
                                <span class="material-symbols-outlined text-[13px] text-status-success">badge</span>
                                <span>Created by: <strong>${c.created_by_name || c.instructor || 'Faculty'}</strong> (${c.created_by_email || 'Verified Gmail'})</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[15px] text-secondary">location_on</span>
                                <span>${c.room || 'Room TBA'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-outline-variant/30 flex justify-between items-center text-xs">
                        <span class="text-on-surface-variant">Units: <strong>${c.units || '3.0'}</strong></span>
                        <div class="flex items-center gap-3">
                            <a href="/admin/academic/class_view.php?id=${c.id}" class="bg-primary/10 text-primary px-2.5 py-1 rounded-lg font-bold hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">visibility</span> View & Attendance
                            </a>
                            <div class="flex items-center gap-2">
                                <button onclick="openEditModal('${c.id}')" class="text-primary hover:underline flex items-center gap-0.5 font-bold">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                </button>
                                <button onclick="deleteClass('${c.id}')" class="text-error hover:underline flex items-center gap-0.5 font-semibold">
                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterAdminClasses() {
            const query = document.getElementById('search-class-input').value.toLowerCase();
            const courseVal = document.getElementById('course-filter').value;
            const sectionVal = document.getElementById('section-filter').value;

            const filtered = allClasses.filter(c => {
                const sec = c.section ? c.section.toUpperCase() : '';
                const code = c.code ? c.code.toUpperCase() : '';
                const title = c.title ? c.title.toUpperCase() : '';
                const prof = c.instructor ? c.instructor.toUpperCase() : '';

                const matchesQuery = !query || code.toLowerCase().includes(query) || title.toLowerCase().includes(query) || prof.toLowerCase().includes(query) || sec.toLowerCase().includes(query);
                const matchesCourse = courseVal === 'all' || sec.includes(courseVal.toUpperCase()) || code.includes(courseVal.toUpperCase());
                const matchesSection = sectionVal === 'all' || sec.includes(sectionVal.toUpperCase());

                return matchesQuery && matchesCourse && matchesSection;
            });

            renderAdminClasses(filtered);
        }

        // Open Edit Modal with Pre-filled data
        function openEditModal(classId) {
            const target = allClasses.find(c => c.id === classId);
            if (!target) return;

            document.getElementById('edit-class-id').value = target.id;
            document.getElementById('edit-class-code').value = target.code || '';
            document.getElementById('edit-class-title').value = target.title || '';
            document.getElementById('edit-class-day').value = target.schedule_day || 'Monday';

            // Split Program & Section if possible (e.g. "AIS 2A" -> Program: AIS, Section: 2A)
            const secParts = (target.section || 'AIS 2A').split(' ');
            if (secParts.length >= 2) {
                document.getElementById('edit-class-program').value = secParts[0];
                document.getElementById('edit-class-section').value = secParts[1];
            } else {
                document.getElementById('edit-class-program').value = 'AIS';
                document.getElementById('edit-class-section').value = target.section || '2A';
            }

            // Format time range to match dropdown option
            let timeRange = '';
            if (target.start_time && target.start_time !== 'TBA') {
                timeRange = `${target.start_time}${target.end_time && target.end_time !== 'TBA' ? ' - ' + target.end_time : ''}`;
            } else {
                timeRange = 'TBA';
            }

            // Set time range in dropdown if exact match or closest match
            const timeSelect = document.getElementById('edit-class-time-range');
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
                // If custom, add temporary option so no data is lost
                const opt = document.createElement('option');
                opt.value = timeRange;
                opt.innerText = timeRange;
                opt.selected = true;
                timeSelect.appendChild(opt);
            }

            document.getElementById('edit-class-instructor').value = target.instructor || '';
            document.getElementById('edit-class-room').value = target.room || '';
            document.getElementById('edit-class-units').value = target.units || 3.0;

            document.getElementById('edit-class-modal').classList.remove('hidden');
        }

        // Save Edited Class
        async function saveEditedClass() {
            const id = document.getElementById('edit-class-id').value;
            const code = document.getElementById('edit-class-code').value.trim();
            const program = document.getElementById('edit-class-program').value;
            const section = document.getElementById('edit-class-section').value;
            const combinedSection = `${program} ${section}`;
            const title = document.getElementById('edit-class-title').value.trim();
            const day = document.getElementById('edit-class-day').value;
            const timeRange = document.getElementById('edit-class-time-range').value;
            const instructor = document.getElementById('edit-class-instructor').value.trim();
            const room = document.getElementById('edit-class-room').value.trim();
            const units = parseFloat(document.getElementById('edit-class-units').value) || 3.0;

            if (!code || !title) return alert('Please enter at least Subject Code and Title.');

            let startTime = 'TBA';
            let endTime = 'TBA';
            if (timeRange.includes('-')) {
                const parts = timeRange.split('-');
                startTime = parts[0].trim();
                endTime = parts[1].trim();
            } else if (timeRange) {
                startTime = timeRange;
            }

            const payload = {
                id: id,
                code: code,
                title: title,
                section: combinedSection,
                schedule_day: day,
                start_time: startTime,
                end_time: endTime,
                instructor: instructor || 'TBA',
                room: room || 'Room TBA',
                units: units,
                csrf_token: csrfToken
            };

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
                alert('Error updating schedule: ' + err.message);
            }

            if (ok) {
                document.getElementById('edit-class-modal').classList.add('hidden');
                populateAdminSubjectDropdown('class');
                loadFacultyList();
        loadClasses();
            }
        }

        async function deleteClass(id) {
            if (!await npcConfirm({
                title: 'Delete Class Subject',
                message: 'Are you sure you want to delete this subject?',
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
            if (!ok) alert('Error: ' + (msg || 'Delete failed.'));
            populateAdminSubjectDropdown('class');
            loadFacultyList();
        loadClasses();
        }

        // Single Class Add Handlers
        document.getElementById('add-class-btn').onclick = () => document.getElementById('add-class-modal').classList.remove('hidden');
        document.getElementById('close-modal-btn').onclick = () => document.getElementById('add-class-modal').classList.add('hidden');
        document.getElementById('cancel-modal-btn').onclick = () => document.getElementById('add-class-modal').classList.add('hidden');

        document.getElementById('save-class-btn').onclick = async () => {
            const code = document.getElementById('class-code').value.trim();
            const program = document.getElementById('class-program').value;
            const section = document.getElementById('class-section').value;
            const combinedSection = `${program} ${section}`;
            const title = document.getElementById('class-title').value.trim();
            const day = document.getElementById('class-day').value;
            const timeRange = document.getElementById('class-time-range').value;
            const instructor = document.getElementById('class-instructor').value.trim();
            const room = document.getElementById('class-room').value.trim();

            if (!code || !title) return alert('Please enter at least Subject Code and Title.');

            let startTime = 'TBA';
            let endTime = 'TBA';
            if (timeRange.includes('-')) {
                const parts = timeRange.split('-');
                startTime = parts[0].trim();
                endTime = parts[1].trim();
            } else if (timeRange) {
                startTime = timeRange;
            }

            let ok = false;
            try {
                const res = await fetch('/api/admin.php?action=save_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        code: code,
                        title: title,
                        section: combinedSection,
                        schedule_day: day,
                        start_time: startTime,
                        end_time: endTime,
                        instructor: instructor || 'TBA',
                        room: room || 'Room TBA',
                        units: 3.0,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Save failed');
                ok = true;
            } catch (err) {
                alert('Error saving class: ' + err.message);
            }

            if (ok) {
                document.getElementById('add-class-modal').classList.add('hidden');
                document.getElementById('class-code').value = '';
                document.getElementById('class-title').value = '';
                document.getElementById('class-instructor').value = '';
                document.getElementById('class-room').value = '';
                populateAdminSubjectDropdown('class');
                loadFacultyList();
        loadClasses();
            }
        };

        // 🔄 Dynamic Academic Offerings & Sections from Database
        async function loadAvailableSectionsAndCourses() {
            try {
                const { data: classesData } = await supabaseClient.from('classes').select('section');
                const { data: usersData } = await supabaseClient.from('users').select('program, section');

                const discoveredPrograms = new Set(['AIS', 'BSIS', 'BSBA - HR', 'BSBA - FM', 'BSBA - MM', 'BSEd', 'BEEd']);
                const discoveredSections = new Set(['1A', '1B', '2A', '2B', '3A', '3B', '4A']);

                if (classesData) {
                    classesData.forEach(c => {
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

                if (usersData) {
                    usersData.forEach(u => {
                        if (u.program) discoveredPrograms.add(u.program.trim());
                        if (u.section) discoveredSections.add(u.section.trim());
                    });
                }

                // Dynamically fetch from programs API
                try {
                    const progRes = await fetch('/api/admin.php?action=list_programs');
                    if (progRes.ok) {
                        const pData = await progRes.json();
                        if (pData && Array.isArray(pData.programs)) {
                            pData.programs.forEach(p => discoveredPrograms.add(p.code));
                        }
                    }
                } catch (e) {}

                // Update bulk import dropdowns if present
                const progSelect = document.getElementById('bulk-sched-program');
                const secSelect = document.getElementById('bulk-sched-section-select');

                if (progSelect) {
                    const currentProg = progSelect.value || 'AIS';
                    const progList = Array.from(discoveredPrograms).sort();
                    progSelect.innerHTML = progList.map(p => `<option value="${p}" ${p === currentProg ? 'selected' : ''}>${p}</option>`).join('');
                }

                if (secSelect) {
                    const currentSec = secSelect.value || '2B';
                    const secList = Array.from(discoveredSections).sort();
                    let secHtml = secList.map(s => `<option value="${s}" ${s === currentSec ? 'selected' : ''}>Section ${s}</option>`).join('');
                    secHtml += '<option value="custom">[+] Custom Section Code</option>';
                    secSelect.innerHTML = secHtml;
                }

                // Update main filter dropdowns
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

        // Bulk Sched Handlers
        document.getElementById('import-sched-btn').onclick = () => document.getElementById('import-sched-modal').classList.remove('hidden');

        document.getElementById('process-sched-import-btn').onclick = async () => {
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
                        populateAdminSubjectDropdown('class');
                        loadFacultyList();
                        loadAvailableSectionsAndCourses();
                        loadClasses();
                    }, 1500);
                } else {
                    statusBox.className = 'p-4 rounded-xl border border-error/40 bg-error/10 text-error block';
                    statusBox.innerHTML = `❌ ${result.message}`;
                }
            } catch (err) {
                console.error(err);
                statusBox.className = 'p-4 rounded-xl border border-error/40 bg-error/10 text-error block';
                statusBox.innerHTML = '❌ Server communication error during schedule parsing.';
            }
        };

        populateAdminSubjectDropdown('class');
        loadFacultyList();
        loadAvailableSectionsAndCourses();
        loadClasses();
    </script>
</body>

</html>