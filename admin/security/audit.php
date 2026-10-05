<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$NPC_PORTAL = 'admin';
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>NPC Connect - Security Audit Logs</title>
    <script>
        (function() {
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
                        "on-secondary-container": "rgb(var(--on-secondary-container-rgb) / <alpha-value>)",
                        "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
                        "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
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
    
    <script id="npc-role-meta" type="application/json">
        <?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?>
    </script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
    <?php include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Security Audit Logs</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm">
                    <?= htmlspecialchars($admin_initial) ?>
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-sm font-semibold text-primary leading-tight"><?= htmlspecialchars($admin_name) ?></p>
                    <p class="text-xs text-on-surface-variant font-mono">Administrator</p>
                </div>
            </div>
        </header>

        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-outline-variant/60 pb-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight"><span class="text-shimmer text-primary">Security &amp; Activity Trail</span></h1>
                    <p class="text-sm text-on-surface-variant mt-1">Every login, denial, role change, and data mutation — filterable and exportable.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="btn-clear-all" onclick="clearAllAuditLogs()" class="ripple press bg-error/10 text-error border border-error/20 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-1.5 shadow-sm hover:bg-error/20 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[19px]">delete_sweep</span>
                        Clear All Logs
                    </button>
                    <button id="btn-export" class="ripple press bg-primary text-on-primary px-5 py-3 rounded-xl text-sm font-bold flex items-center gap-2 shadow-md hover:opacity-90 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[19px]">download</span>
                        Export CSV
                    </button>
                </div>
            </div>

            <!-- Stat chips -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="npc-card bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm">
                    <p class="text-[11px] font-mono uppercase tracking-widest text-on-surface-variant">Events loaded</p>
                    <p class="text-2xl font-extrabold text-primary tabular-nums mt-1" id="stat-total">0</p>
                </div>
                <div class="npc-card bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm">
                    <p class="text-[11px] font-mono uppercase tracking-widest text-on-surface-variant">High severity</p>
                    <p class="text-2xl font-extrabold text-error tabular-nums mt-1" id="stat-high">0</p>
                </div>
                <div class="npc-card bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm">
                    <p class="text-[11px] font-mono uppercase tracking-widest text-on-surface-variant">Medium severity</p>
                    <p class="text-2xl font-extrabold text-status-warning tabular-nums mt-1" id="stat-med">0</p>
                </div>
                <div class="npc-card bg-surface-container-lowest rounded-2xl border border-outline-variant p-4 shadow-sm">
                    <p class="text-[11px] font-mono uppercase tracking-widest text-on-surface-variant">Denied actions</p>
                    <p class="text-2xl font-extrabold text-status-warning tabular-nums mt-1" id="stat-denied">0</p>
                </div>
            </section>

            <!-- Filters -->
            <section class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" id="f-search" placeholder="Search event, user, IP…"
                        class="w-full bg-surface border border-outline-variant/60 text-xs rounded-xl pl-8 pr-3 py-2.5 text-primary focus:outline-none focus:border-primary">
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant absolute left-2.5 top-2.5">search</span>
                </div>
                <select id="f-severity" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-primary cursor-pointer">
                    <option value="all">All severities</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                <select id="f-type" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-primary cursor-pointer">
                    <option value="all">All event types</option>
                </select>
                <select id="f-range" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-primary cursor-pointer">
                    <option value="all">All time</option>
                    <option value="24">Last 24 hours</option>
                    <option value="168">Last 7 days</option>
                    <option value="720">Last 30 days</option>
                    <option value="custom">Custom range…</option>
                </select>
                <div id="f-custom-range" class="hidden flex items-center gap-2">
                    <input type="date" id="f-date-from" title="From date" class="bg-surface border border-outline-variant/60 text-primary text-xs rounded-xl px-3 py-2 font-mono focus:outline-none focus:border-primary">
                    <span class="text-on-surface-variant text-xs">→</span>
                    <input type="date" id="f-date-to" title="To date" class="bg-surface border border-outline-variant/60 text-primary text-xs rounded-xl px-3 py-2 font-mono focus:outline-none focus:border-primary">
                </div>
                <button id="f-refresh" class="p-2.5 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container transition-colors cursor-pointer press" data-tip="Refresh">
                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                </button>
            </section>

            <!-- Logs table -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[760px]">
                        <thead>
                            <tr class="bg-surface-container-low font-mono text-xs text-on-surface uppercase tracking-wider">
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Event</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">User</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">IP</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Timestamp</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Severity</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody" class="divide-y divide-outline-variant/30">
                            <tr>
                                <td colspan="6">
                                    <div class="npc-skeleton-group py-2" aria-busy="true">
                                        <div class="sk-table-row">
                                            <div class="skeleton sk-line" style="margin-bottom:0"></div>
                                            <div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div>
                                            <div class="skeleton sk-chip"></div>
                                            <div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div>
                                        </div>
                                        <div class="sk-table-row" style="padding-bottom:0">
                                            <div class="skeleton sk-line" style="margin-bottom:0"></div>
                                            <div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div>
                                            <div class="skeleton sk-chip"></div>
                                            <div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-outline-variant/60 bg-surface-subtle flex items-center justify-between">
                    <p class="text-[11px] font-mono text-on-surface-variant"><span id="showing-count">0</span> events shown (latest 500 kept server-side)</p>
                    <button id="btn-more" class="hidden text-xs font-bold text-primary hover:underline cursor-pointer">Load 500 more…</button>
                </div>
            </section>
        </div>
    </main>

    <script>
        const csrfToken = '<?= htmlspecialchars($csrf_token) ?>' || window.__CSRF__ || '';
        let ALL = [];
        let OFFSET = 0;
        const PAGE = 500;

        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        async function loadMore(reset) {
            const tbody = document.getElementById('logs-tbody');
            if (reset) {
                ALL = [];
                OFFSET = 0;
                tbody.innerHTML = '<tr><td colspan="6"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>';
            }
            try {
                const res = await fetch(`/api/admin.php?action=get_audit_logs&offset=${OFFSET}&limit=${PAGE}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed');
                ALL = ALL.concat(data.logs || []);
                OFFSET += (data.logs || []).length;
                document.getElementById('btn-more').classList.toggle('hidden', (data.logs || []).length < PAGE);
                render();
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-10 text-center text-error text-sm">${esc(err.message)}</td></tr>`;
            }
        }

        function filtered() {
            const q = document.getElementById('f-search').value.toLowerCase().trim();
            const sev = document.getElementById('f-severity').value;
            const typ = document.getElementById('f-type').value;
            const hrs = document.getElementById('f-range').value;
            let cutoff = hrs === 'all' || hrs === 'custom' ? 0 : Date.now() - parseInt(hrs) * 3600000;
            // Custom date range overrides the preset cutoff
            let fromTs = 0,
                toTs = Infinity;
            if (hrs === 'custom') {
                const fv = document.getElementById('f-date-from').value;
                const tv = document.getElementById('f-date-to').value;
                if (fv) fromTs = new Date(fv + 'T00:00:00').getTime();
                if (tv) toTs = new Date(tv + 'T23:59:59').getTime();
                if (!fv && !tv) {
                    cutoff = 0;
                    fromTs = 0;
                    toTs = Infinity;
                }
            }
            return ALL.filter(l => {
                if (sev !== 'all' && (l.severity || 'Low') !== sev) return false;
                if (typ !== 'all' && String(l.event || '').split(':')[0] !== typ) return false;
                const ts = new Date(l.created_at).getTime();
                if (cutoff && ts < cutoff) return false;
                if (ts < fromTs || ts > toTs) return false;
                if (q && ![l.event, l.user_email, l.ip_address].join(' ').toLowerCase().includes(q)) return false;
                return true;
            });
        }

        /* Show/hide custom date inputs when range = custom */
        document.getElementById('f-range')?.addEventListener('change', function() {
            document.getElementById('f-custom-range').classList.toggle('hidden', this.value !== 'custom');
        });

        function render() {
            const rows = filtered();
            const tbody = document.getElementById('logs-tbody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-on-surface-variant">No events match your filters.</td></tr>';
            } else {
                tbody.innerHTML = rows.map(l => {
                    const sev = l.severity || 'Low';
                    const sevCls = sev === 'High' ? 'bg-error/10 text-error border-error/30' :
                        (sev === 'Medium' ? 'bg-status-warning/10 text-status-warning border-status-warning/30' :
                            'bg-surface-container text-primary border-outline-variant');
                    const isDenied = /DENIED|FAIL/i.test(l.event || '');
                    return `<tr class="hover:bg-surface-container-low/60 transition-colors">
                        <td class="py-3 px-6">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[17px] ${isDenied ? 'text-error' : 'text-status-info'}">${isDenied ? 'gpp_bad' : 'bolt'}</span>
                                <span class="font-medium text-on-surface text-xs">${esc(l.event)}</span>
                            </div>
                        </td>
                        <td class="py-3 px-6 font-mono text-xs text-on-surface-variant break-all">${esc(l.user_email || 'system')}</td>
                        <td class="py-3 px-6 font-mono text-xs text-outline">${esc(l.ip_address || '—')}</td>
                        <td class="py-3 px-6 text-xs text-on-surface-variant">${new Date(l.created_at).toLocaleString()}</td>
                        <td class="py-3 px-6"><span class="npc-role-chip ${sevCls}">${esc(sev)}</span></td>
                        <td class="py-3 px-6 text-right">
                            <button onclick="deleteAuditLog('${esc(l.id)}', this)" class="px-2.5 py-1 text-xs font-semibold text-error hover:bg-error/10 rounded-lg transition-colors cursor-pointer inline-flex items-center gap-1" title="Delete audit record">
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Delete</span>
                            </button>
                        </td>
                    </tr>`;
                }).join('');
            }

            /* stats */
            document.getElementById('stat-total').textContent = ALL.length;
            document.getElementById('stat-high').textContent = ALL.filter(l => (l.severity || '') === 'High').length;
            document.getElementById('stat-med').textContent = ALL.filter(l => (l.severity || '') === 'Medium').length;
            document.getElementById('stat-denied').textContent = ALL.filter(l => /DENIED|FAIL/i.test(l.event || '')).length;
            document.getElementById('showing-count').textContent = rows.length;

            /* populate type filter once */
            const sel = document.getElementById('f-type');
            if (sel.options.length <= 1) {
                const types = [...new Set(ALL.map(l => String(l.event || '').split(':')[0]))].sort();
                types.forEach(t => {
                    const o = document.createElement('option');
                    o.value = t;
                    o.textContent = t;
                    sel.appendChild(o);
                });
            }
        }

        /* CSV export of the CURRENTLY FILTERED rows */
        document.getElementById('btn-export').addEventListener('click', () => {
            const rows = filtered();
            if (!rows.length) {
                if (window.notify) window.notify('Nothing to export.', 'warning');
                return;
            }
            const head = ['timestamp', 'event', 'user', 'ip', 'severity'];
            const lines = [head.join(',')].concat(rows.map(l => [new Date(l.created_at).toISOString(), l.event, l.user_email || 'system', l.ip_address || '', l.severity || 'Low']
                .map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',')
            ));
            const blob = new Blob([lines.join('\n')], {
                type: 'text/csv'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'npc-audit-' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
            URL.revokeObjectURL(a.href);
            if (window.notify) window.notify('Exported ' + rows.length + ' events to CSV.', 'success');
            if (window.npcConfetti) window.npcConfetti.burst(50);
        });

        ['f-search', 'f-severity', 'f-type', 'f-range'].forEach(id =>
            document.getElementById(id).addEventListener(id === 'f-search' ? 'input' : 'change', render));
        document.getElementById('f-refresh').addEventListener('click', () => loadMore(true));
        document.getElementById('btn-more').addEventListener('click', () => loadMore(false));

        loadMore(true);

        async function deleteAuditLog(id, btn) {
            if (!id) return;
            if (!await npcConfirm({
                    title: 'Delete Audit Record',
                    message: 'Are you sure you want to delete this security audit record?',
                    type: 'danger',
                    confirmText: 'Delete Record'
                })) return;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">sync</span>';
            }
            try {
                const res = await fetch('/api/admin.php?action=delete_audit_log', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        id: id,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Deletion failed');
                if (window.notify) window.notify('Audit log record deleted.', 'success');
                ALL = ALL.filter(item => String(item.id) !== String(id));
                render();
            } catch (err) {
                if (window.notify) window.notify(err.message, 'error');
                else alert('Error: ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">delete</span><span>Delete</span>';
                }
            }
        }

        async function clearAllAuditLogs() {
            if (!ALL.length) {
                if (window.notify) window.notify('No audit logs to clear.', 'warning');
                return;
            }
            if (!await npcConfirm({
                    title: 'Clear All Audit Logs',
                    message: 'CAUTION: Are you sure you want to clear ALL audit log records? This cannot be undone.',
                    type: 'danger',
                    confirmText: 'Wipe All Logs'
                })) return;
            try {
                const res = await fetch('/api/admin.php?action=delete_audit_log', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        scope: 'all',
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Wipe failed');
                if (window.notify) window.notify('All audit records cleared.', 'success');
                ALL = [];
                render();
            } catch (err) {
                if (window.notify) window.notify(err.message, 'error');
                else alert('Error: ' + err.message);
            }
        }
    </script>
</body>

</html>