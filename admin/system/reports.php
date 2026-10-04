<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Reports &amp; Analytics - NPC Connect Admin</title>
    <script>
        (function () { try {
            var t = localStorage.getItem('npc-theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (e) {} })();
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/npc.js"></script>
    <script id="tailwind-config">
        tailwind.config = { darkMode: "class", theme: { extend: { colors: {
            "primary": "rgb(var(--primary-rgb) / <alpha-value>)",
            "primary-container": "rgb(var(--primary-container-rgb) / <alpha-value>)",
            "on-primary": "rgb(var(--on-primary-rgb) / <alpha-value>)",
            "on-primary-container": "rgb(var(--on-primary-container-rgb) / <alpha-value>)",
            "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
            "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
            "surface-container-lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
            "surface-container-low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
            "surface-container": "rgb(var(--surface-container-rgb) / <alpha-value>)",
            "on-surface": "rgb(var(--on-surface-rgb) / <alpha-value>)",
            "on-surface-variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
            "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
            "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
            "status-info": "rgb(var(--status-info-rgb) / <alpha-value>)",
            "status-success": "rgb(var(--status-success-rgb) / <alpha-value>)",
            "status-warning": "rgb(var(--status-warning-rgb) / <alpha-value>)",
            "error": "rgb(var(--error-rgb) / <alpha-value>)",
            "secondary": "rgb(var(--secondary-rgb) / <alpha-value>)",
            "secondary-container": "rgb(var(--secondary-container-rgb) / <alpha-value>)",
            "on-secondary-container": "rgb(var(--on-secondary-container-rgb) / <alpha-value>)"
        }, fontFamily: { "sans": ["Geist","sans-serif"], "mono": ["JetBrains Mono","monospace"] } } } };
    </script>
    <style>@media print { aside, #topbar, .no-print { display:none !important; } }</style>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm no-print" id="topbar">
            <h2 class="text-xl font-bold text-primary">Reports &amp; Analytics</h2>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm npc-navy-card"><?= htmlspecialchars($admin_initial) ?></div>
                <span class="text-sm font-semibold text-primary hidden sm:inline"><?= htmlspecialchars($admin_name) ?></span>
            </div>
        </header>

        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            <!-- Report picker -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print" id="report-picker">
                <button onclick="runReport('master_list')"        data-type="master_list"        class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-primary text-[24px]">groups</span><p class="text-sm font-bold text-on-surface mt-2">Student Master List</p><p class="text-[11px] font-mono text-on-surface-variant">All enrolled students</p></button>
                <button onclick="runReport('class_roster')"       data-type="class_roster"       class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-primary text-[24px]">list_alt</span><p class="text-sm font-bold text-on-surface mt-2">Class Roster</p><p class="text-[11px] font-mono text-on-surface-variant">Students per section</p></button>
                <button onclick="runReport('attendance_summary')" data-type="attendance_summary" class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-primary text-[24px]">fact_check</span><p class="text-sm font-bold text-on-surface mt-2">Attendance Report</p><p class="text-[11px] font-mono text-on-surface-variant">Present/late/absent per student</p></button>
                <button onclick="runReport('grade_report')"       data-type="grade_report"       class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-primary text-[24px]">military_tech</span><p class="text-sm font-bold text-on-surface mt-2">Grade Report</p><p class="text-[11px] font-mono text-on-surface-variant">All official grades</p></button>
                <button onclick="runReport('failing_students')"   data-type="failing_students"   class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-error text-[24px]">trending_down</span><p class="text-sm font-bold text-on-surface mt-2">Failing Students</p><p class="text-[11px] font-mono text-on-surface-variant">Grade &gt; 3.00 or INC</p></button>
                <button onclick="runReport('pending_documents')"  data-type="pending_documents"  class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-status-warning text-[24px]">pending_actions</span><p class="text-sm font-bold text-on-surface mt-2">Pending Documents</p><p class="text-[11px] font-mono text-on-surface-variant">Not yet released</p></button>
                <button onclick="runReport('teacher_load')"       data-type="teacher_load"       class="report-btn npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm text-left"><span class="material-symbols-outlined text-secondary text-[24px]">cast_for_education</span><p class="text-sm font-bold text-on-surface mt-2">Teacher Load</p><p class="text-[11px] font-mono text-on-surface-variant">Subjects/units per prof</p></button>
                <a href="/admin/security/audit.php" class="npc-card ripple press bg-surface-container-lowest rounded-2xl border border-outline-variant p-5 shadow-sm block"><span class="material-symbols-outlined text-status-success text-[24px]">history</span><p class="text-sm font-bold text-on-surface mt-2">Audit Logs</p><p class="text-[11px] font-mono text-on-surface-variant">Full activity trail →</p></a>
            </section>

            <!-- Result panel -->
            <section id="result-panel" class="hidden bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                    <div>
                        <h3 class="font-bold text-primary text-base flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">table_view</span>
                            <span id="report-title">Report</span>
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5"><span id="report-count">0</span> rows generated</p>
                    </div>
                    <div class="flex items-center gap-2 no-print">
                        <button onclick="window.print()" class="px-3.5 py-2 bg-surface hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-bold text-primary flex items-center gap-1.5 shadow-sm"><span class="material-symbols-outlined text-[16px]">print</span> Print/PDF</button>
                        <button onclick="exportCsv()" class="px-3.5 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary-container flex items-center gap-1.5 shadow-sm"><span class="material-symbols-outlined text-[16px]">download</span> Export CSV</button>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead id="report-thead" class="sticky top-0"></thead>
                        <tbody id="report-tbody" class="divide-y divide-outline-variant/30"></tbody>
                    </table>
                </div>
            </section>

            <div id="empty-hint" class="text-center py-16 text-on-surface-variant">
                <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2 block">analytics</span>
                <p class="text-sm font-semibold">Select a report above to begin.</p>
                <p class="text-xs mt-1">All reports can be exported as CSV or printed as PDF.</p>
            </div>
        </div>
    </main>

    <div id="toast" class="fixed bottom-6 right-6 bg-gray-900 text-white text-xs px-4 py-3 rounded-xl shadow-2xl z-50 hidden items-center gap-2.5">
        <span class="material-symbols-outlined text-[18px] text-emerald-400" id="toast-icon">check_circle</span>
        <span id="toast-message">Ready</span>
    </div>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;
        let CURRENT = { title: '', columns: [], rows: [] };

        function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

        function toast(msg, kind) {
            const t = document.getElementById('toast');
            document.getElementById('toast-icon').textContent = kind === 'error' ? 'error' : 'check_circle';
            document.getElementById('toast-message').textContent = msg;
            t.classList.remove('hidden'); t.classList.add('flex');
            clearTimeout(window.__rt);
            window.__rt = setTimeout(() => { t.classList.add('hidden'); t.classList.remove('flex'); }, 3500);
        }

        async function runReport(type) {
            // highlight active card
            document.querySelectorAll('.report-btn').forEach(b => {
                b.classList.toggle('ring-2', b.dataset.type === type);
                b.classList.toggle('ring-primary', b.dataset.type === type);
            });
            try {
                const res = await fetch('/api/admin.php?action=get_report&type=' + encodeURIComponent(type), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to generate report.');

                CURRENT = { title: data.title, columns: data.columns, rows: data.rows };
                document.getElementById('report-title').textContent = data.title;
                document.getElementById('report-count').textContent = data.rows.length;
                renderTable();
                document.getElementById('result-panel').classList.remove('hidden');
                document.getElementById('empty-hint').classList.add('hidden');
                toast(data.title + ' generated (' + data.rows.length + ' rows)', 'success');
            } catch (err) {
                toast('Error: ' + err.message, 'error');
            }
        }

        function renderTable() {
            const head = document.getElementById('report-thead');
            const body = document.getElementById('report-tbody');
            if (!CURRENT.rows.length) {
                head.innerHTML = '';
                body.innerHTML = '<tr><td class="p-10 text-center text-on-surface-variant">No data available for this report.</td></tr>';
                return;
            }
            head.innerHTML = '<tr class="bg-surface-container-low font-mono text-[10px] text-on-surface uppercase tracking-wider">' +
                CURRENT.columns.map(c => `<th class="py-3 px-4 font-semibold border-b border-outline-variant whitespace-nowrap">${esc(String(c).replace(/_/g, ' '))}</th>`).join('') + '</tr>';
            body.innerHTML = CURRENT.rows.map(r =>
                '<tr class="hover:bg-surface-subtle transition-colors">' +
                CURRENT.columns.map(c => `<td class="py-2.5 px-4 break-words">${esc(r[c] ?? '')}</td>`).join('') +
                '</tr>').join('');
        }

        function exportCsv() {
            if (!CURRENT.rows.length) { toast('No report data to export.', 'error'); return; }
            const q = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
            const lines = [CURRENT.columns.map(q).join(',')];
            CURRENT.rows.forEach(r => lines.push(CURRENT.columns.map(c => q(r[c] ?? '')).join(',')));
            const blob = new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'npc-' + CURRENT.title.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        }

        /* Class roster needs a section prompt */
        document.querySelectorAll('.report-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.type === 'class_roster') {
                    const sec = prompt('Section (e.g. AIS 2A):', '');
                    if (!sec || !sec.trim()) return;
                    fetch('/api/admin.php?action=get_report&type=class_roster&section=' + encodeURIComponent(sec.trim()), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json()).then(data => {
                            if (!data.success) throw new Error(data.message || 'Failed');
                            CURRENT = { title: data.title + ' — ' + sec.trim(), columns: data.columns, rows: data.rows };
                            document.getElementById('report-title').textContent = CURRENT.title;
                            document.getElementById('report-count').textContent = data.rows.length;
                            renderTable();
                            document.getElementById('result-panel').classList.remove('hidden');
                            document.getElementById('empty-hint').classList.add('hidden');
                        }).catch(e => toast('Error: ' + e.message, 'error'));
                }
            });
        });
    </script>
</body>
</html>
