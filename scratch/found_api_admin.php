<?php
require_once 'auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : 'admin@navotaspolytechniccollege.edu.ph';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>NPC Connect - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#001736",
                        "primary-container": "#002b5c",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#7594cb",
                        "primary-fixed": "#d6e3ff",
                        "primary-fixed-dim": "#aac7ff",
                        "secondary": "#775a19",
                        "secondary-container": "#fed488",
                        "on-secondary-container": "#785a1a",
                        "surface": "#f8f9ff",
                        "surface-subtle": "#F8FAFC",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#eff4ff",
                        "surface-container": "#e5eeff",
                        "surface-container-high": "#dce9ff",
                        "surface-container-highest": "#d3e4fe",
                        "on-surface": "#0b1c30",
                        "on-surface-variant": "#43474f",
                        "outline": "#747780",
                        "outline-variant": "#c4c6d0",
                        "status-info": "#0EA5E9",
                        "status-success": "#15803d",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6"
                    },
                    fontFamily: {
                        "sans": ["Geist", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                }
            }
        }
    </script>
    
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <!-- SideNavBar Desktop -->
    <nav class="hidden lg:flex flex-col w-64 h-screen fixed left-0 top-0 z-40 py-6 bg-primary shadow-md">
        <div class="px-6 mb-8 flex flex-col items-center">
            <img class="w-16 h-16 rounded-full mb-3 object-cover bg-white p-1 shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBw2c0cnwCv_1oeRDX8RrHqB8stLSsvw54RTFe98wFq4BWHUYCUWe_n4VIn0TTBVuKRAIGEEstk3Ke_R0xZIOIGA7_KVCxmBnue7ebhQU5KAPQFjEYS4Q_1Od8flcRGIrJQJJ4_ZTwrY1ZB2LpoHuv_Tfu6eqPO7_bctjIIOYu6rZwcGbg5SKlN21OW-8M3k0Aebeq1lrjfeZMMH7m2opfoykjE6dUN9304WLzTxc2OwOn_cSbFUlisvg">
            <h1 class="text-xl font-bold text-white tracking-tight text-center">NPC Connect</h1>
            <p class="text-xs text-on-primary-container font-mono uppercase tracking-wider mt-1">Admin Portal</p>
        </div>
        <div class="flex-1 px-4 space-y-1.5 font-medium text-sm overflow-y-auto">
            <a class="flex items-center gap-3 px-4 py-3 bg-secondary-container text-on-secondary-container shadow-sm font-semibold rounded-xl transition-all" href="admin.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_grades.php">
                <span class="material-symbols-outlined text-[20px]">verified</span>
                <span>Grade Approvals</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_classes.php">
                <span class="material-symbols-outlined text-[20px]">school</span>
                <span>Classes & Rosters</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_schedules.php">
                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                <span>Student Schedules</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_attendance.php">
                <span class="material-symbols-outlined text-[20px]">qr_code_scanner</span>
                <span>Live Attendance QR</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_students.php">
                <span class="material-symbols-outlined text-[20px]">group</span>
                <span>Student Directory</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_announcements.php">
                <span class="material-symbols-outlined text-[20px]">campaign</span>
                <span>Announcements</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_docs.php">
                <span class="material-symbols-outlined text-[20px]">description</span>
                <span>Documents & AI</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all" href="admin_settings.php">
                <span class="material-symbols-outlined text-[20px]">settings</span>
                <span>System Settings</span>
            </a>
        </div>
        <div class="px-4 mt-auto space-y-1.5 pt-4 border-t border-white/10">
            <a class="flex items-center gap-3 px-4 py-2.5 text-on-primary-container hover:bg-white/10 hover:text-white rounded-xl transition-all text-sm font-semibold" href="teacher_grades.php">
                <span class="material-symbols-outlined text-[20px]">grade</span>
                <span>Faculty Gradebook</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 text-error hover:bg-error/10 rounded-xl transition-all text-sm font-semibold" href="/logout.php">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span>Sign Out</span>
            </a>
        </div>
    </nav>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64" id="main-wrapper">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Administrative Master Panel</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm">
                        <?= htmlspecialchars($admin_initial) ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-primary leading-tight"><?= htmlspecialchars($admin_name) ?></p>
                        <p class="text-xs text-on-surface-variant font-mono">Administrator</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 flex-1">
            <div>
                <h1 class="text-2xl font-bold text-primary mb-1">Administrative Overview</h1>
                <p class="text-sm text-on-surface-variant">Live metrics, pending approvals, student services, and security status for Navotas Polytechnic College.</p>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Metric 1: Total Students -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Registered Students</span>
                        <div class="w-9 h-9 rounded-xl bg-primary-container text-on-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">group</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-primary" id="metric-total-students">0</div>
                    <div class="mt-3 flex items-center gap-1 text-xs text-on-surface-variant">
                        <a href="admin_students.php" class="text-primary font-semibold hover:underline flex items-center gap-1">
                            Manage directory <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Metric 2: Pending Grade Approvals -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Pending Grade Approvals</span>
                        <div class="w-9 h-9 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">verified</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-primary" id="metric-pending-grades">0</div>
                    <div class="mt-3 flex items-center gap-1 text-xs text-on-surface-variant">
                        <a href="admin_grades.php" class="text-primary font-semibold hover:underline flex items-center gap-1">
                            Review grade sheets <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Metric 3: Active Classes -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Active Classes</span>
                        <div class="w-9 h-9 rounded-xl bg-surface-container-high text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">school</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-primary" id="metric-total-classes">0</div>
                    <div class="mt-3 flex items-center gap-1 text-xs text-on-surface-variant">
                        <a href="admin_classes.php" class="text-primary font-semibold hover:underline flex items-center gap-1">
                            Manage schedules <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Metric 4: Pending Document Requests -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-mono font-semibold text-on-surface-variant uppercase tracking-wider">Pending Document Requests</span>
                        <div class="w-9 h-9 rounded-xl bg-primary-fixed text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-primary" id="metric-pending-docs">0</div>
                    <div class="mt-3 flex items-center gap-1 text-xs text-on-surface-variant">
                        <a href="admin_settings.php" class="text-primary font-semibold hover:underline flex items-center gap-1">
                            Process requests <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action Banner -->
            <div class="bg-primary text-on-primary rounded-2xl p-6 shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-lg font-bold mb-1">Administrative Control Center</h3>
                    <p class="text-sm text-on-primary-container max-w-xl">Review and approve faculty grade sheets, assign course loads, verify document requests, and manage security audit logs.</p>
                </div>
                <div class="flex items-center gap-3 relative z-10 shrink-0">
                    <a href="admin_grades.php" class="bg-secondary-container text-on-secondary-container font-semibold px-4 py-2.5 rounded-xl hover:opacity-90 transition-opacity text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        Grade Approvals
                    </a>
                    <a href="admin_students.php" class="bg-white/10 text-white font-semibold px-4 py-2.5 rounded-xl hover:bg-white/20 transition-all text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">group_add</span>
                        Manage Users
                    </a>
                </div>
            </div>

            <!-- Recent Security & System Audit Logs -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">security</span>
                        System Security & Activity Logs
                    </h3>
                    <span class="text-xs font-mono text-on-surface-variant bg-surface-container px-2.5 py-1 rounded-md border border-outline-variant">Live Audit Feed</span>
                </div>

                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-surface-container-low font-mono text-xs text-on-surface uppercase tracking-wider">
                                    <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Action / Event</th>
                                    <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">User / Account</th>
                                    <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Timestamp</th>
                                    <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Severity</th>
                                </tr>
                            </thead>
                            <tbody id="security-logs-tbody" class="divide-y divide-outline-variant/30">
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-on-surface-variant">Loading system audit records...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadAdminStats() {
            try {
                const res = await fetch('api_admin.php?action=get_dashboard_metrics');
                const data = await res.json();

                if (data.success) {
                    document.getElementById('metric-total-students').innerText = data.metrics.total_students || 0;
                    document.getElementById('metric-pending-grades').innerText = data.metrics.pending_grades || 0;
                    document.getElementById('metric-total-classes').innerText = data.metrics.active_classes || 0;
                    document.getElementById('metric-pending-docs').innerText = data.metrics.pending_docs || 0;

                    const tbody = document.getElementById('security-logs-tbody');
                    const logs = data.recent_logs || [];

                    if (logs.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-400">No security events logged yet.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = logs.map(l => `
                        <tr class="hover:bg-surface-subtle transition-colors">
                            <td class="py-3.5 px-6 font-medium text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-status-info text-sm">shield</span>
                                ${l.event_type || 'SYSTEM_EVENT'}
                            </td>
                            <td class="py-3.5 px-6 font-mono text-xs text-on-surface-variant">${l.user_email || 'system'}</td>
                            <td class="py-3.5 px-6 text-on-surface-variant text-xs">${new Date(l.created_at).toLocaleString()}</td>
                            <td class="py-3.5 px-6">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${l.severity === 'High' ? 'bg-red-100 text-error' : (l.severity === 'Medium' ? 'bg-amber-100 text-amber-800' : 'bg-surface-container text-primary')}">
                                    ${l.severity || 'Low'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error('Error loading admin stats:', err);
            }
        }

        loadAdminStats();
    </script>
</body>
</html>
