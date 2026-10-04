<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$csrf_token = getCsrfToken();
$jsConfig = getJsConfig();

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
    <title>NPC Connect - User & Role Management</title>
    <!-- Theme bootstrap -->
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
    <script src="/assets/js/npc.js"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>

    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">User & Role Management</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card">
                        <?= htmlspecialchars($admin_initial) ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-primary leading-tight"><?= htmlspecialchars($admin_name) ?></p>
                        <p class="text-xs text-on-surface-variant font-mono">Administrator</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">

            <!-- Intro + Add button -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-outline-variant/60 pb-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight"><span class="text-shimmer text-primary">Accounts & Access Control</span></h1>
                    <p class="text-sm text-on-surface-variant mt-1">Create admin, faculty, or student accounts using their official NPC Gmail. Roles decide which portal each person can enter.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="btn-bulk-import" class="ripple press border border-outline-variant bg-surface-container-low text-primary px-5 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm hover:bg-surface-container transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[19px]">upload_file</span>
                        Bulk Import CSV
                    </button>
                    <button id="btn-add-user" class="ripple press npc-navy-card text-white px-5 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-md hover:opacity-90 transition-all hover:-translate-y-0.5 cursor-pointer shrink-0">
                        <span class="material-symbols-outlined text-[19px] text-white">person_add</span>
                        Add New Account
                    </button>
                </div>
            </div>

            <!-- Role stat chips -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="role-stats">
                <button data-filter-role="all" class="role-stat npc-card press text-left bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex items-center gap-3 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-surface-container-high text-primary flex items-center justify-center"><span class="material-symbols-outlined">groups</span></div>
                    <div><p class="text-xl font-extrabold text-primary tabular-nums" id="stat-all">0</p><p class="text-[11px] font-mono uppercase text-on-surface-variant">All accounts</p></div>
                </button>
                <button data-filter-role="student" class="role-stat npc-card press text-left bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex items-center gap-3 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl npc-navy-card text-white flex items-center justify-center"><span class="material-symbols-outlined text-white">school</span></div>
                    <div><p class="text-xl font-extrabold text-primary tabular-nums" id="stat-student">0</p><p class="text-[11px] font-mono uppercase text-on-surface-variant">Students</p></div>
                </button>
                <button data-filter-role="teacher" class="role-stat npc-card press text-left bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex items-center gap-3 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center"><span class="material-symbols-outlined">cast_for_education</span></div>
                    <div><p class="text-xl font-extrabold text-primary tabular-nums" id="stat-teacher">0</p><p class="text-[11px] font-mono uppercase text-on-surface-variant">Faculty</p></div>
                </button>
                <button data-filter-role="admin" class="role-stat npc-card press text-left bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm flex items-center gap-3 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center"><span class="material-symbols-outlined">shield_person</span></div>
                    <div><p class="text-xl font-extrabold text-primary tabular-nums" id="stat-admin">0</p><p class="text-[11px] font-mono uppercase text-on-surface-variant">Admins</p></div>
                </button>
            </section>

            <!-- Search bar -->
            <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">search</span>
                <input type="text" id="user-search" placeholder="Search by name, email, or student number…"
                       class="flex-1 bg-transparent border-none outline-none text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:ring-0" />
                <span class="kbd">Ctrl K</span>
            </div>

            <!-- Accounts table -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[760px]">
                        <thead>
                            <tr class="bg-surface-container-low font-mono text-xs text-on-surface uppercase tracking-wider">
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Person</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">NPC Gmail</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">ID / Dept</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Role</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Status</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody" class="divide-y divide-outline-variant/30">
                            <tr><td colspan="8"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs text-on-surface-variant flex items-start gap-2 -mt-2">
                <span class="material-symbols-outlined text-[15px] text-status-info mt-0.5">info</span>
                Role changes update user access in real time across all authenticated devices. Protected administrator accounts are safeguarded from deletion. All administrative actions are automatically recorded in the security audit log.
            </p>
        </div>
    </main>

    <!-- ══════════ Add-Account Modal ══════════ -->
    <div id="add-user-modal" class="npc-modal-backdrop hidden">
        <div class="npc-modal-card max-w-lg" role="dialog" aria-label="Add new account">
            <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-subtle">
                <h3 class="font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">person_add</span> Create New Account
                </h3>
                <button id="modal-close" class="p-1.5 rounded-full hover:bg-surface-container transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="add-user-form" class="p-6 overflow-y-auto flex flex-col gap-4 text-sm">
                <!-- Role picker -->
                <div>
                    <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-2">Account Type *</label>
                    <div class="grid grid-cols-3 gap-2" id="role-picker">
                        <label class="cursor-pointer">
                            <input type="radio" name="new-role" value="student" class="peer sr-only" checked>
                            <div class="rounded-xl border border-outline-variant p-3 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary hover:bg-surface-container-low">
                                <span class="material-symbols-outlined block mx-auto mb-1 text-[22px]">school</span>
                                <span class="text-xs font-bold">Student</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="new-role" value="teacher" class="peer sr-only">
                            <div class="rounded-xl border border-outline-variant p-3 text-center transition-all peer-checked:border-secondary peer-checked:bg-secondary-container/40 peer-checked:text-on-secondary-container hover:bg-surface-container-low">
                                <span class="material-symbols-outlined block mx-auto mb-1 text-[22px]">cast_for_education</span>
                                <span class="text-xs font-bold">Teacher</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="new-role" value="admin" class="peer sr-only">
                            <div class="rounded-xl border border-outline-variant p-3 text-center transition-all peer-checked:border-error peer-checked:bg-error/10 peer-checked:text-error hover:bg-surface-container-low">
                                <span class="material-symbols-outlined block mx-auto mb-1 text-[22px]">shield_person</span>
                                <span class="text-xs font-bold">Admin</span>
                            </div>
                        </label>
                    </div>
                    <p id="role-hint" class="text-[11px] text-on-surface-variant mt-2 leading-snug"></p>
                </div>

                <!-- Email Address (Accepts Any Gmail or NPC College Email) -->
                <div>
                    <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Email Address *</label>
                    <input type="email" id="nu-email" required placeholder="e.g. juandelacruz251505@navotaspolytechniccollege.edu.ph or user@gmail.com"
                           class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <p class="text-[11px] text-on-surface-variant mt-1">Accepts any official NPC Google Workspace account or personal Gmail.</p>
                </div>

                <!-- Name Resolution Notice -->
                <div class="bg-surface-subtle border border-outline-variant/60 rounded-xl p-3 flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">badge</span>
                    <div class="text-[11px] text-on-surface-variant leading-relaxed">
                        <strong class="text-primary font-semibold">Official Full Name:</strong> Synced automatically from the user's complete Google Workspace / NPC Gmail profile upon login, or managed via the Student Management Directory.
                    </div>
                </div>

                <!-- Student Academic Placement & Scholarship -->
                <div id="student-fields" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Course / Program *</label>
                            <select id="nu-program" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:border-primary">
                                <?php foreach ($programsList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>"><?= htmlspecialchars($p['code']) ?> (<?= htmlspecialchars($p['name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Section *</label>
                            <select id="nu-section" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2.5 text-xs font-mono font-bold focus:outline-none focus:border-primary">
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
                            <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Scholarship Status</label>
                            <select id="nu-scholar" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:border-primary">
                                <option value="Non-Scholar">Non-Scholar</option>
                                <option value="Scholar">Scholar (Full / LGU / Academic)</option>
                            </select>
                        </div>
                        <div class="bg-surface-subtle border border-outline-variant/40 rounded-xl p-2.5">
                            <span class="block font-mono text-[9px] uppercase tracking-wider text-on-surface-variant">Auto Student ID</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="material-symbols-outlined text-[15px] text-emerald-600">badge</span>
                                <span id="nu-detected-id" class="font-mono text-xs font-bold text-primary">Extracted from email</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="staff-fields" class="hidden">
                    <label class="block font-mono text-[10px] uppercase tracking-widest text-on-surface-variant mb-1">Department / Designation</label>
                    <input type="text" id="nu-dept" placeholder="e.g. College of Computer Studies"
                           class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary">
                </div>

            </form>

            <div class="px-6 py-4 border-t border-outline-variant bg-surface-subtle flex justify-end items-center gap-3">
                <button type="button" id="modal-cancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer px-3 py-2">Cancel</button>
                <button type="submit" form="add-user-form" id="btn-create" class="ripple press npc-navy-card text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:opacity-90 cursor-pointer flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px] text-white">check_circle</span> Create Account
                </button>
            </div>
        </div>
    </div>

    <!-- 2. BULK IMPORT MODAL (CSV) -->
    <div id="import-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-xl max-w-lg w-full">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-bold text-primary flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">upload_file</span> Bulk Import Accounts (CSV)</h3>
                <button onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error text-xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <p class="text-on-surface-variant">Upload a CSV file containing the following column headers:</p>
                <code class="block bg-surface-container px-3 py-2 rounded-lg font-mono text-[11px] text-primary overflow-x-auto">full_name,email,role,program,section</code>
                <ul class="list-disc pl-5 space-y-1 text-on-surface-variant">
                    <li><strong>email</strong> — Full email address (Gmail, NPC email, etc.) or email username prefix.</li>
                    <li><strong>role</strong> — student | teacher | admin</li>
                    <li>Program and section are assigned for students; optional for faculty and staff.</li>
                    <li>Interactive preview and error report are displayed before committing the import.</li>
                </ul>
                <input type="file" id="import-csv-file" accept=".csv,text/csv" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs cursor-pointer">
                <div id="import-preview" class="hidden max-h-48 overflow-y-auto bg-surface rounded-xl border border-outline-variant/50 p-3 font-mono text-[11px]"></div>
                <div id="import-result" class="hidden rounded-xl border p-3 text-[11px] font-semibold"></div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-outline-variant font-bold hover:bg-surface-container">Cancel</button>
                    <button type="button" id="btn-import-run" disabled class="px-5 py-2 rounded-xl bg-primary text-on-primary font-bold shadow-sm disabled:opacity-40 cursor-pointer">Import N rows</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>
    <script>
        const CSRF = <?= json_encode($csrf_token) ?>;
        const MY_EMAIL = <?= json_encode(strtolower($admin_email)) ?>;

        /* ── State ─────────────────────────────────────────── */
        let USERS = [];
        let filterRole = 'all';
        let searchText = '';

        const ROLE_BADGE = {
            student: { label: 'Student', icon: 'school', cls: 'bg-primary/10 text-primary border border-primary/25' },
            teacher: { label: 'Faculty', icon: 'cast_for_education', cls: 'bg-secondary-container text-on-secondary-container border border-secondary-container' },
            admin:   { label: 'Admin', icon: 'shield_person', cls: 'bg-error/10 text-error border border-error/30' }
        };

        /**
         * Function: loadUsers
         * Asynchronously retrieves the full roster of registered user accounts from the backend API.
         * Automatically populates local cache, updates header count-up statistics, and renders the table.
         *
         * @return {Promise<void>}
         */
        async function loadUsers() {
            const tbody = document.getElementById('users-tbody');
            try {
                const res = await fetch('/api/admin.php?action=list_users', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load accounts.');
                USERS = data.users || [];
                renderStats();
                renderTable();
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-10 text-center text-error text-sm">${(err.message||'Could not load accounts.')}</td></tr>`;
            }
        }

        /**
         * Function: initials
         * Computes a 1 or 2 letter uppercase monogram for avatar generation.
         *
         * @param {string} name - The user's full name.
         * @return {string} Uppercase initials.
         */
        function initials(name) {
            return String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
        }

        /**
         * Function: esc
         * Sanitizes arbitrary text strings for safe injection into the DOM to prevent Cross-Site Scripting (XSS).
         *
         * @param {string} s - Raw input string.
         * @return {string} HTML-escaped string.
         */
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        /**
         * Function: renderStats
         * Computes demographic statistics by role (All Accounts, Students, Faculty, Admins)
         * and triggers animated count-up visualizations on stat chip cards.
         *
         * @return {void}
         */
        function renderStats() {
            document.getElementById('stat-all').setAttribute('data-countup', USERS.length);
            document.getElementById('stat-student').setAttribute('data-countup', USERS.filter(u => u.role === 'student').length);
            document.getElementById('stat-teacher').setAttribute('data-countup', USERS.filter(u => u.role === 'teacher').length);
            document.getElementById('stat-admin').setAttribute('data-countup', USERS.filter(u => u.role === 'admin').length);
            ['stat-all','stat-student','stat-teacher','stat-admin'].forEach(id => {
                const el = document.getElementById(id);
                if (window.npcCountUp) window.npcCountUp(el); else el.textContent = el.getAttribute('data-countup');
            });
        }

        /**
         * Function: filteredUsers
         * Filters the cached USERS collection according to the active role tab and live search query.
         *
         * @return {Array<Object>} Filtered array of user objects.
         */
        function filteredUsers() {
            const q = searchText.toLowerCase().trim();
            return USERS.filter(u => {
                if (filterRole !== 'all' && u.role !== filterRole) return false;
                if (!q) return true;
                return [u.full_name, u.email, u.student_number].join(' ').toLowerCase().includes(q);
            });
        }

        /**
         * Function: isProtected
         * Identifies core system administrator emails that should be guarded from accidental deletion.
         *
         * @param {string} email - The email address to inspect.
         * @return {boolean} True if guarded from deletion; false otherwise.
         */
        function isProtected(email) {
            const protectedList = [
                'admin@navotaspolytechniccollege.edu.ph',
                'jderramas251505@navotaspolytechniccollege.edu.ph'
            ];
            return protectedList.includes(String(email || '').toLowerCase());
        }

        /**
         * Function: renderTable
         * Renders all rows in the user accounts table, generating:
         *  - Avatar and user identity with YOU / PROTECTED badges.
         *  - Official NPC Gmail address.
         *  - Academic Program, Section, and Scholar indicator or Department.
         *  - Material role chip badge.
         *  - Account status badge (Active, Restricted, Snoozed, or Banned).
         *  - Real-time action controls (Role selector, Status selector, Permanent deletion).
         *
         * @return {void}
         */
        function renderTable() {
            const tbody = document.getElementById('users-tbody');
            const rows = filteredUsers();

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-on-surface-variant">No accounts match your filters.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((u, i) => {
                const badge = ROLE_BADGE[u.role] || ROLE_BADGE.student;
                const prot = isProtected(u.email);
                const self = String(u.email || '').toLowerCase() === MY_EMAIL;
                
                const scholarTag = u.scholar_status === 'Scholar' 
                    ? '<span class="ml-1 text-[10px] font-bold text-amber-600 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded">Scholar</span>' 
                    : '';
                const sub = u.role === 'student'
                    ? `${esc(u.program || 'AIS')} · ${esc(u.section || '2A')}${scholarTag}`
                    : esc(u.program || 'Faculty / Staff');

                let statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>';
                if (u.status === 'Banned') {
                    statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-600 border border-rose-500/20 inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Banned</span>';
                } else if (u.status === 'Restricted') {
                    statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 border border-amber-500/20 inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Restricted</span>';
                } else if (u.status === 'Snoozed' || u.is_active == 0) {
                    statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-500 border border-slate-500/20 inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Snoozed</span>';
                }

                const opts = ['student','teacher','admin'].map(r =>
                    `<option value="${r}" ${r === u.role ? 'selected' : ''}>${ROLE_BADGE[r].label}</option>`).join('');

                const isBanned = u.status === 'Banned';
                const isRestricted = u.status === 'Restricted';
                const isSnoozed = u.status === 'Snoozed' || u.is_active == 0;
                const isActive = (u.status === 'Active' || !u.status) && u.is_active != 0;

                let cleanName = (u.full_name && !u.full_name.startsWith('Pending')) ? u.full_name : '';
                if (!cleanName) {
                    const emPrefix = (u.email || '').split('@')[0].replace(/\d+$/, '');
                    if (emPrefix.toLowerCase() === 'admin') cleanName = 'System Administrator';
                    else if (emPrefix.toLowerCase() === 'faculty') cleanName = 'Faculty Member';
                    else if (emPrefix.includes('.')) cleanName = emPrefix.split('.').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(' ');
                    else if (emPrefix.length >= 3) cleanName = emPrefix.charAt(0).toUpperCase() + '. ' + emPrefix.slice(1).charAt(0).toUpperCase() + emPrefix.slice(2);
                    else cleanName = emPrefix ? emPrefix.charAt(0).toUpperCase() + emPrefix.slice(1) : 'NPC User';
                }
                const displayName = esc(cleanName);
                const avatarText = initials(cleanName);

                return `
                <tr class="hover:bg-surface-container-low/60 transition-colors user-row" data-search="${esc([u.full_name,u.email,u.student_number].join(' ').toLowerCase())}">
                    <td class="py-3 px-6">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs shrink-0 npc-navy-card">${avatarText}</div>
                            <div>
                                <p class="font-semibold text-on-surface leading-tight">${displayName}${self ? ' <span class=\"ml-1 text-[10px] font-mono text-status-success\">YOU</span>' : ''}${prot ? ' <span class=\"ml-1 text-[10px] font-mono text-error\">PROTECTED</span>' : ''}</p>
                                <p class="text-[11px] font-mono text-on-surface-variant">${esc(u.student_number || 'STAFF')}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-6 font-mono text-xs text-on-surface-variant break-all">${esc(u.email)}</td>
                    <td class="py-3 px-6 text-xs text-on-surface-variant font-medium">${sub}</td>
                    <td class="py-3 px-6">
                        <span class="npc-role-chip ${badge.cls}"><span class="material-symbols-outlined" style="font-size:13px;">${badge.icon}</span>${badge.label}</span>
                    </td>
                    <td class="py-3 px-6">
                        ${statusBadge}
                    </td>
                    <td class="py-3 px-6 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Role Change Selector -->
                            <select onchange="changeRole('${esc(u.id)}', this.value, '${esc(u.full_name)}')"
                                    class="bg-surface border border-outline-variant rounded-lg text-xs font-semibold text-primary px-2 py-1.5 focus:outline-none focus:border-primary cursor-pointer">
                                ${opts}
                            </select>
                            <!-- Account Status Selector -->
                            <select onchange="setUserStatus('${esc(u.id)}', this.value, '${esc(u.full_name)}')" ${self ? 'disabled title="You cannot modify your own status"' : ''}
                                    class="bg-surface border border-outline-variant rounded-lg text-xs font-semibold text-on-surface px-2 py-1.5 focus:outline-none focus:border-primary cursor-pointer disabled:opacity-40">
                                <option value="Active" ${isActive ? 'selected' : ''}>Active</option>
                                <option value="Restricted" ${isRestricted ? 'selected' : ''}>Restrict</option>
                                <option value="Snoozed" ${isSnoozed ? 'selected' : ''}>Snooze</option>
                                <option value="Banned" ${isBanned ? 'selected' : ''}>Ban</option>
                            </select>
                            <!-- Permanent Deletion Button -->
                            ${prot || self ? '' : `
                            <button onclick="deleteAccount('${esc(u.id)}', '${esc(u.full_name)}', '${esc(u.email)}')"
                                    class="p-1.5 rounded-lg border border-error/30 text-error hover:bg-error/10 transition-colors cursor-pointer"
                                    title="Permanently Delete Account">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                            `}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        /**
         * Function: changeRole
         * Submits a request to update an account role (Student, Faculty, Admin).
         * If the current administrator changes their own role (e.g. testing the Faculty portal),
         * the backend immediately updates their active session and returns a redirection URL to their new portal.
         *
         * @param {string} id - Database identifier for the user account.
         * @param {string} newRole - Target role ('student', 'teacher', or 'admin').
         * @param {string} name - User's display name for dialog prompts.
         * @return {Promise<void>}
         */
        async function changeRole(id, newRole, name) {
            if (!await npcConfirm({
                title: 'Change User Role',
                message: `Change role for ${name} to "${newRole.toUpperCase()}"?\nPortal access and security permissions will update in real time.`,
                type: 'warning',
                confirmText: 'Change Role'
            })) {
                loadUsers();
                return;
            }
            try {
                const res = await fetch('/api/admin.php?action=set_user_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body: JSON.stringify({ id, role: newRole })
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || 'Role updated.', data.success ? 'success' : 'error');
                if (data.success && data.redirect) {
                    // Instantly redirect to the new portal
                    window.location.href = data.redirect;
                    return;
                }
            } catch (err) {
                if (window.notify) window.notify('Request failed: ' + err.message, 'error');
            }
            loadUsers();
        }

        /**
         * Function: setUserStatus
         * Updates a user's operating status (Active, Restricted, Snoozed, or Banned).
         * Blocked users are immediately evicted from active sessions on all devices.
         *
         * @param {string} id - Database identifier for the user account.
         * @param {string} newStatus - Target status ('Active', 'Restricted', 'Snoozed', or 'Banned').
         * @param {string} name - User's display name.
         * @return {Promise<void>}
         */
        async function setUserStatus(id, newStatus, name) {
            if (!await npcConfirm({
                title: `Set Account to ${newStatus}`,
                message: `Are you sure you want to set the account of ${name} to "${newStatus}"?\n\n` +
                         (newStatus === 'Active' ? 'The user will have normal access.' : 'The user will be immediately blocked or restricted from portal access in real time.'),
                type: newStatus === 'Active' ? 'info' : (newStatus === 'Banned' ? 'danger' : 'warning'),
                confirmText: `Set as ${newStatus}`
            })) {
                loadUsers();
                return;
            }
            try {
                const res = await fetch('/api/admin.php?action=set_user_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body: JSON.stringify({ id, status: newStatus })
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || 'Status updated.', data.success ? 'success' : 'error');
            } catch (err) {
                if (window.notify) window.notify('Request failed: ' + err.message, 'error');
            }
            loadUsers();
        }

        /**
         * Function: deleteAccount
         * Permanently removes a user account from the system and terminates all active sessions.
         *
         * @param {string} id - The user ID to delete.
         * @param {string} name - The user's full name.
         * @param {string} email - The user's email address.
         * @return {Promise<void>}
         */
        async function deleteAccount(id, name, email) {
            if (!await npcConfirm({
                title: 'Delete User Account',
                message: `Permanently delete account for ${name} (${email})?\n\nThis cannot be undone. Active sessions will be terminated immediately in real time.`,
                type: 'danger',
                confirmText: 'Delete Permanently'
            })) return;

            try {
                const res = await fetch('/api/admin.php?action=delete_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || 'Account deleted.', data.success ? 'success' : 'error');
            } catch (err) {
                if (window.notify) window.notify('Delete failed: ' + err.message, 'error');
            }
            loadUsers();
        }

        /* ── Filters ───────────────────────────────────────── */
        document.querySelectorAll('.role-stat').forEach(btn => {
            btn.addEventListener('click', () => {
                filterRole = btn.getAttribute('data-filter-role');
                document.querySelectorAll('.role-stat').forEach(b =>
                    b.classList.toggle('ring-2', b === btn) || b.classList.toggle('ring-primary', b === btn));
                renderTable();
            });
        });
        document.getElementById('user-search').addEventListener('input', e => {
            searchText = e.target.value;
            renderTable();
        });

        /* ── Add-account modal ─────────────────────────────── */
        const modal = document.getElementById('add-user-modal');
        const roleHint = document.getElementById('role-hint');
        const HINTS = {
            student: 'Students see schedules, grades, QR attendance and the campus AI — nothing else.',
            teacher: 'Teachers get the Faculty portal: classes, attendance QR, grade encoding and their teaching AI.',
            admin:   'Admins get full access: every portal, approvals, directory, announcements and user management.'
        };

        function openModal() {
            modal.classList.remove('hidden');
            updateHints();
            const detId = document.getElementById('nu-detected-id');
            if (detId) detId.textContent = 'Extracted from email';
            setTimeout(() => document.getElementById('nu-email').focus(), 60);
        }
        function closeModal() { modal.classList.add('hidden'); }
        function updateHints() {
            const sel = document.querySelector('input[name="new-role"]:checked').value;
            roleHint.textContent = HINTS[sel];
            document.getElementById('student-fields').classList.toggle('hidden', sel !== 'student');
            document.getElementById('staff-fields').classList.toggle('hidden', sel === 'student');
        }
        document.getElementById('role-picker').addEventListener('change', updateHints);
        document.getElementById('btn-add-user').addEventListener('click', openModal);
        document.getElementById('modal-close').addEventListener('click', closeModal);
        document.getElementById('modal-cancel').addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        /* ── Real-Time Email Parser: Auto Student Number ── */
        const emailInput = document.getElementById('nu-email');
        const idBadge = document.getElementById('nu-detected-id');

        emailInput.addEventListener('input', () => {
            const raw = emailInput.value.trim();
            const prefix = raw.split('@')[0] || '';

            // Detect student ID digits in email prefix (e.g. jderramas251505 -> 251505)
            const digits = prefix.match(/\d{4,}/);
            if (idBadge) {
                if (digits) {
                    idBadge.textContent = digits[0];
                } else if (raw) {
                    idBadge.textContent = 'Auto YYYY-XXXXX';
                } else {
                    idBadge.textContent = 'Extracted from email';
                }
            }
        });

        /* ── Submit create ─────────────────────────────────── */
        document.getElementById('add-user-form').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = document.getElementById('btn-create');
            const role = document.querySelector('input[name="new-role"]:checked').value;
            const email = document.getElementById('nu-email').value.trim();

            // Extract student number from email prefix if student role
            let autoNumber = '';
            const prefix = email.split('@')[0] || '';
            const digitsMatch = prefix.match(/\d{4,}/);
            if (digitsMatch) {
                autoNumber = digitsMatch[0];
            }

            const payload = {
                role,
                full_name: '',
                email: email,
                number: autoNumber,
                program: role === 'student' ? document.getElementById('nu-program').value.trim()
                                            : document.getElementById('nu-dept').value.trim(),
                section: role === 'student' ? document.getElementById('nu-section').value.trim() : '',
                scholar_status: role === 'student' ? document.getElementById('nu-scholar').value : 'Non-Scholar',
                send_invite: false
            };
            if (!payload.number && role !== 'student') delete payload.number;
            if (!payload.section) delete payload.section;

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[17px] animate-spin">progress_activity</span> Creating…';

            try {
                const res = await fetch('/api/admin.php?action=create_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || (data.success ? 'Account created.' : 'Failed.'), data.success ? 'success' : 'error', 5200);
                if (data.success) {
                    if (window.npcConfetti) window.npcConfetti.burst(70);
                    closeModal();
                    e.target.reset();
                    if (idBadge) idBadge.textContent = 'Extracted from email';
                    loadUsers();
                }
            } catch (err) {
                if (window.notify) window.notify('Request failed: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[17px]">check_circle</span> Create Account';
            }
        });

        loadUsers();

        /* ── Bulk CSV Import ─────────────────────────────────── */
        const importModal = document.getElementById('import-modal');
        let pendingRows = [];

        document.getElementById('btn-bulk-import')?.addEventListener('click', () => {
            pendingRows = [];
            document.getElementById('import-csv-file').value = '';
            document.getElementById('import-preview').classList.add('hidden');
            document.getElementById('import-result').classList.add('hidden');
            const run = document.getElementById('btn-import-run');
            run.disabled = true; run.textContent = 'Import N rows';
            importModal.classList.remove('hidden');
        });

        document.getElementById('import-csv-file')?.addEventListener('change', function () {
            const file = this.files[0];
            if (!file || typeof Papa === 'undefined') return;
            Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                complete: (res) => {
                    const headers = (res.meta.fields || []).map(h => h.trim().toLowerCase());
                    const hasEmail = headers.includes('email') || headers.includes('email_prefix');
                    const hasName = headers.includes('full_name') || headers.includes('name');
                    const hasRole = headers.includes('role');
                    const prev = document.getElementById('import-preview');
                    const run = document.getElementById('btn-import-run');

                    if (!hasEmail || !hasName || !hasRole) {
                        prev.classList.remove('hidden');
                        prev.innerHTML = '<span class="text-error font-bold">CSV must contain full_name, email, and role columns.</span>';
                        run.disabled = true;
                        return;
                    }
                    pendingRows = res.data
                        .map(r => {
                            const rawEmail = String(r.email || r.email_prefix || '').trim();
                            const fullEmail = rawEmail.includes('@') ? rawEmail.toLowerCase() : (rawEmail.toLowerCase() + '@navotaspolytechniccollege.edu.ph');
                            return {
                                full_name: String(r.full_name || r.name || '').trim(),
                                email: fullEmail,
                                number: String(r.number || r.student_number || '').trim().toUpperCase(),
                                role: ['student','teacher','admin'].includes(String(r.role||'').trim().toLowerCase()) ? String(r.role).trim().toLowerCase() : 'student',
                                program: String(r.program || r.course || '').trim(),
                                section: String(r.section || '').trim(),
                                scholar_status: String(r.scholar_status || r.scholar || 'Non-Scholar').trim()
                            };
                        })
                        .filter(r => r.full_name && /^[a-z0-9._-]+@[a-z]/i.test(r.email));
                    prev.classList.remove('hidden');
                    prev.innerHTML =
                        '<p class="font-bold mb-1">Preview: ' + pendingRows.length + ' valid rows</p>' +
                        pendingRows.slice(0, 8).map(r => '<div>' + r.email + ' → ' + r.role + (r.program ? ' (' + r.program + '-' + r.section + ')' : '') + '</div>').join('') +
                        (pendingRows.length > 8 ? '<div class="text-on-surface-variant">… and ' + (pendingRows.length - 8) + ' more</div>' : '');
                    run.disabled = pendingRows.length === 0;
                    run.textContent = 'Import ' + pendingRows.length + ' rows';
                }
            });
        });

        document.getElementById('btn-import-run')?.addEventListener('click', async function () {
            if (!pendingRows.length) return;
            const btn = this;
            btn.disabled = true;
            let ok = 0, fail = 0;
            const errors = [];
            for (let i = 0; i < pendingRows.length; i++) {
                const r = pendingRows[i];
                try {
                    const res = await fetch('/api/admin.php?action=create_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                        body: JSON.stringify({ ...r, csrf_token: CSRF })
                    });
                    const data = await res.json();
                    if (data.success) ok++; else { fail++; errors.push(r.email + ': ' + (data.message || 'failed')); }
                } catch (e) { fail++; errors.push(r.email + ': network error'); }
                btn.textContent = 'Importing… ' + (i + 1) + '/' + pendingRows.length;
            }
            const out = document.getElementById('import-result');
            out.classList.remove('hidden');
            out.className = 'rounded-xl border p-3 text-[11px] font-semibold ' + (fail ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800');
            out.innerHTML = 'Done: <strong>' + ok + ' created</strong>, ' + fail + ' failed.' +
                (errors.length ? '<div class="mt-1 max-h-24 overflow-y-auto font-mono">' + errors.slice(0, 10).map(e => '<div>• ' + e + '</div>').join('') + '</div>' : '');
            btn.textContent = 'Import complete';
            loadUsers();
        });
    </script>
</body>
</html>
