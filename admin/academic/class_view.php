<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$class_id = isset($_GET['id']) ? $_GET['id'] : '';
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Class Overview & Attendance - NPC Admin</title>
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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
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
                <a href="/admin/academic/classes.php" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-xs font-semibold">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Classes
                </a>
                <span class="text-gray-300">|</span>
                <h2 class="text-base font-bold text-primary" id="top-title">Class Inspection</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="/teacher/grades.php?class_id=<?= htmlspecialchars($class_id) ?>" class="bg-[#107c41] hover:bg-[#0c5d31] text-white px-3.5 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-sm transition-all">
                    <span class="material-symbols-outlined text-[15px]">table_chart</span> Excel Grade Sheet
                </a>
                <a href="/admin/academic/attendance.php" class="bg-primary text-on-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 npc-navy-card">
                    <span class="material-symbols-outlined text-[15px]">qr_code_scanner</span> Launch Master Attendance
                </a>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            
            <!-- Class Details Banner -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <span class="px-3 py-1 bg-primary text-white font-mono text-xs font-bold rounded-lg" id="view-class-code">DM103</span>
                        <span class="px-3 py-1 bg-surface-container-low text-primary font-mono text-xs font-bold rounded-lg border" id="view-class-section">AIS 2A</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight" id="view-class-title">BUSINESS PROCESS MANAGEMENT</h1>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-on-surface-variant font-medium mt-2">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px] text-status-info">schedule</span> <span id="view-class-time">Saturday (07:00 AM - 10:00 AM)</span></span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px] text-secondary">location_on</span> <span id="view-class-room">Room 302</span></span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px] text-primary">person</span> <span id="view-class-prof">Prof. Roderick Castillo</span></span>
                    </div>
                </div>
            </div>

            <!-- 📊 TOP ATTENDANCE SUMMARY METRICS -->
            <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-[11px] font-mono font-bold uppercase text-on-surface-variant">Enrolled Roster</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-primary mt-1" id="stat-enrolled">0</h3>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Students in section</p>
                </div>

                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-[11px] font-mono font-bold uppercase text-status-success">Present Today</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-status-success mt-1" id="stat-present">0</h3>
                    <p class="text-[11px] text-status-success mt-0.5">Checked-in on time</p>
                </div>

                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-[11px] font-mono font-bold uppercase text-status-warning">Late Students</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-status-warning mt-1" id="stat-late">0</h3>
                    <p class="text-[11px] text-status-warning mt-0.5">Past grace period</p>
                </div>

                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant shadow-sm">
                    <span class="text-[11px] font-mono font-bold uppercase text-error">Absent</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-error mt-1" id="stat-absent">0</h3>
                    <p class="text-[11px] text-error mt-0.5">No record logged</p>
                </div>
            </section>

            <!-- Enrolled Students Roster Table -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 px-6 border-b border-outline-variant bg-surface-subtle flex justify-between items-center">
                    <h3 class="font-bold text-primary text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">group</span>
                        Enrolled Class Roster & Attendance Status
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-surface-container-low font-mono uppercase text-on-surface border-b border-outline-variant">
                                <th class="py-3 px-6 font-bold">Student Name</th>
                                <th class="py-3 px-4 font-bold">ID Number</th>
                                <th class="py-3 px-4 font-bold">Institutional Email</th>
                                <th class="py-3 px-4 font-bold">Check-in Time</th>
                                <th class="py-3 px-6 font-bold text-right">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody id="class-roster-tbody" class="divide-y divide-outline-variant/30 font-medium">
                            <tr><td colspan="8"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);

        const targetClassId = <?= json_encode($class_id) ?>;

        async function loadClassView() {
            try {
                if (!targetClassId || String(targetClassId).trim() === '') {
                    document.getElementById('view-class-title').innerText = 'No class selected';
                    return;
                }
                // 1. Fetch class details
                const { data: classData, error } = await supabaseClient.from('classes').select('*').eq('id', targetClassId).single();
                if (error || !classData) {
                    document.getElementById('view-class-title').innerText = 'Class not found';
                    return;
                }

                document.getElementById('view-class-code').innerText = classData.code;
                document.getElementById('view-class-section').innerText = classData.section || 'AIS 2A';
                document.getElementById('view-class-title').innerText = classData.title;
                document.getElementById('view-class-time').innerText = `${classData.schedule_day || 'TBA'} (${classData.start_time || 'TBA'}${classData.end_time && classData.end_time !== 'TBA' ? ' - ' + classData.end_time : ''})`;
                document.getElementById('view-class-room').innerText = classData.room || 'Room TBA';
                document.getElementById('view-class-prof').innerText = classData.instructor || 'Faculty';

                // 2. Fetch enrolled students
                const { data: students } = await supabaseClient.from('users').select('*');
                const targetSec = (classData.section || 'AIS 2A').toUpperCase();

                const enrolled = (students || []).filter(s => {
                    const sSec = `${s.program || ''} ${s.section || ''}`.trim().toUpperCase();
                    return sSec.includes(targetSec) || targetSec.includes(sSec) || targetSec.includes(s.section ? s.section.toUpperCase() : '');
                });

                enrolled.sort((a, b) => (a.full_name || '').localeCompare(b.full_name || ''));

                // 3. Fetch attendance
                const sessionCode = `NPC-${classData.code.replace(/[^a-zA-Z0-9]/g, '')}-${new Date().toISOString().slice(0,10)}`;
                const { data: logs } = await supabaseClient.from('attendance_records').select('*').eq('session_code', sessionCode);

                const attendanceLogs = logs || [];

                let present = 0;
                let late = 0;
                attendanceLogs.forEach(l => {
                    if (l.status === 'late') late++;
                    else present++;
                });

                const total = enrolled.length;
                const absent = Math.max(0, total - (present + late));

                document.getElementById('stat-enrolled').innerText = total;
                document.getElementById('stat-present').innerText = present;
                document.getElementById('stat-late').innerText = late;
                document.getElementById('stat-absent').innerText = absent;

                // Render table
                const tbody = document.getElementById('class-roster-tbody');
                if (enrolled.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-on-surface-variant">No enrolled students in this section.</td></tr>';
                    return;
                }

                tbody.innerHTML = enrolled.map(s => {
                    const log = attendanceLogs.find(l => l.student_number === s.student_number || l.student_name === s.full_name);
                    let time = '—';
                    let badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-error/10 text-error">Absent</span>';

                    if (log) {
                        time = new Date(log.check_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        if (log.status === 'late') {
                            badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-status-warning/15 text-status-warning">Late</span>';
                        } else {
                            badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-status-success/15 text-status-success">Present</span>';
                        }
                    }

                    return `
                        <tr class="hover:bg-surface-subtle transition-colors">
                            <td class="py-3 px-6 font-bold text-primary">${s.full_name}</td>
                            <td class="py-3 px-4 font-mono">${s.student_number || 'N/A'}</td>
                            <td class="py-3 px-4 font-mono text-on-surface-variant">${s.email}</td>
                            <td class="py-3 px-4 font-mono text-on-surface-variant">${time}</td>
                            <td class="py-3 px-6 text-right">${badge}</td>
                        </tr>
                    `;
                }).join('');

            } catch (err) {
                console.error(err);
            }
        }

        loadClassView();
    </script>
</body>
</html>
