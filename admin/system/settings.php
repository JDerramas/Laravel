<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>System Settings & Document Services - NPC Connect Admin</title>
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
    </script>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Workspace -->
    <main class="flex-1 lg:pl-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">System Configuration & Student Services</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card">
                    <?= htmlspecialchars($admin_initial) ?>
                </div>
                <span class="text-sm font-semibold text-primary hidden sm:inline"><?= htmlspecialchars($admin_name) ?></span>
            </div>
        </header>

        <!-- Canvas Container -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 flex-1">
            <div class="border-b border-outline-variant/60 pb-6">
                <h1 class="text-2xl font-bold text-primary">Student Document Requests & System Settings</h1>
                <p class="text-sm text-on-surface-variant mt-1">Process registrar document requests, update statuses, and configure institutional portal security parameters.</p>
            </div>

            <!-- 1. REGISTRAR DOCUMENT REQUEST QUEUE -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                    <div>
                        <h3 class="font-bold text-primary text-base flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                            Registrar Document Processing Queue
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Manage student requests for COR, COE, Good Moral, and Transcript certifications.</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-primary/10 text-primary px-3 py-1 rounded-full" id="doc-queue-count">0 Requests</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-surface-container-low font-mono text-on-surface uppercase tracking-wider">
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Reference #</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Student</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Document</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Purpose</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Date</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Status</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="document-requests-tbody" class="divide-y divide-outline-variant/30">
                            <tr><td colspan="8"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 2. INSTITUTIONAL SETTINGS -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2 border-b border-outline-variant/60 pb-3">
                    <span class="material-symbols-outlined text-primary text-[20px]">domain</span>
                    <h3 class="font-bold text-primary text-base">Institutional Configuration</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Institution Name:</label>
                        <input type="text" value="Navotas Polytechnic College" readonly class="w-full bg-surface-container-low border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold">
                    </div>
                    <div id="term-control">
                        <label class="block font-bold text-gray-700 mb-1">Active Academic Term:</label>
                        <select id="active-term" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Loading terms…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Registrar Contact Email:</label>
                        <input type="text" value="registrar@navotaspolytechniccollege.edu.ph" readonly class="w-full bg-surface-container-low border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Attendance Late Grace Period (minutes):</label>
                        <input type="number" id="grace-period" min="0" max="30" value="5" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </section>

            <!-- 3. FEATURE SETTINGS (app_settings-backed) -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2 border-b border-outline-variant/60 pb-3">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                    <h3 class="font-bold text-primary text-base">Feature Settings</h3>
                    <span class="text-xs font-mono text-on-surface-variant ml-auto">attendance &amp; upload behavior</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1" title="QR session: minutes students can check in as Present">QR Present Window (min):</label>
                        <input type="number" id="set-present-window" min="1" max="180" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-[10px] text-on-surface-variant mt-1">Default: 10 min while QR code is active.</p>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1" title="Extra minutes after Present window, still counts as Late">QR Late Window (min):</label>
                        <input type="number" id="set-late-window" min="0" max="180" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-[10px] text-on-surface-variant mt-1">Default: 5 min after Present window.</p>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1" title="Minutes before a check-in is considered Late instead of On Time">Late Grace Period (min):</label>
                        <input type="number" id="grace-period" min="0" max="60" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-[10px] text-on-surface-variant mt-1">Minutes before check-in is marked Late.</p>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1" title="Maximum file size for document/material uploads">Max Upload Size (MB):</label>
                        <input type="number" id="set-upload-max" min="1" max="100" class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-[10px] text-on-surface-variant mt-1">Default: 10 MB per file.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <p class="text-[11px] text-on-surface-variant flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                        Values configured here apply immediately to QR attendance and upload features.
                    </p>
                    <button id="btn-save-feature-settings" class="px-5 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-bold shadow-sm hover:opacity-90 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span> Save Feature Settings
                    </button>
                </div>
                <div id="feature-settings-msg" class="hidden rounded-xl px-4 py-2.5 text-xs font-semibold"></div>
            </section>
        </div>
    </main>

    <script>
        const csrfToken = <?= json_encode($csrf_token) ?>;

        /* ── Academic Term Switcher ── */
        async function loadAcademicTerms() {
            try {
                const res = await fetch('/api/admin.php?action=get_academic_settings');
                const data = await res.json();
                const sel = document.getElementById('active-term');
                if (!sel || !data.success) return;
                sel.innerHTML = data.terms.map(t =>
                    `<option value="${t.id}" ${t.is_active ? 'selected' : ''}>${t.year_label} — ${t.semester} ${t.is_active ? '✓' : ''}</option>`
                ).join('');
                if (!data.terms.length) {
                    sel.innerHTML = '<option value="">No terms configured</option>';
                }
            } catch (e) {
                console.error('Could not load academic terms', e);
            }
        }
        document.getElementById('active-term')?.addEventListener('change', async function () {
            const termId = this.value;
            if (!termId) return;
            try {
                const res = await fetch('/api/admin.php?action=set_active_term', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ term_id: termId, csrf_token: csrfToken })
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || 'Term updated.', data.success ? 'success' : 'error');
            } catch (e) {
                if (window.notify) window.notify('Failed to update term: ' + e.message, 'error');
            }
        });

        /* ── Attendance Grace Period ── */
        async function loadGracePeriod() {
            try {
                const res = await fetch('/api/admin.php?action=get_app_settings');
                const data = await res.json();
                if (!data.success) return;
                const grace = data.settings.find(s => s.setting_key === 'attendance_grace_minutes');
                const el = document.getElementById('grace-period');
                if (grace && el) el.value = parseInt(grace.setting_value) || 5;
            } catch (e) { console.error(e); }
        }
        document.getElementById('grace-period')?.addEventListener('change', async function () {
            const val = parseInt(this.value);
            if (isNaN(val) || val < 0) return;
            try {
                const res = await fetch('/api/admin.php?action=set_academic_setting', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ key: 'attendance_grace_minutes', value: val, csrf_token: csrfToken })
                });
                const data = await res.json();
                if (window.notify) window.notify(data.message || 'Grace period saved.', data.success ? 'success' : 'error');
            } catch (e) {
                if (window.notify) window.notify('Failed to save grace period: ' + e.message, 'error');
            }
        });

        async function loadDocumentRequests() {
            try {
                const res = await fetch('/api/admin.php?action=get_document_requests');
                const data = await res.json();
                const tbody = document.getElementById('document-requests-tbody');
                const countBadge = document.getElementById('doc-queue-count');

                if (data.success && data.requests && data.requests.length > 0) {
                    countBadge.innerText = `${data.requests.length} Requests`;
                    tbody.innerHTML = data.requests.map(r => `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 font-mono font-bold text-primary">${r.reference_no}</td>
                            <td class="py-3 px-6">
                                <div class="font-bold">${r.student_name || 'Student'}</div>
                                <div class="font-mono text-[10px] text-gray-500">${r.student_number || ''}</div>
                            </td>
                            <td class="py-3 px-6 font-bold text-gray-800">${r.document_type}</td>
                            <td class="py-3 px-6 text-gray-600">${r.purpose}</td>
                            <td class="py-3 px-6 font-mono text-gray-500">${new Date(r.requested_at).toLocaleDateString()}</td>
                            <td class="py-3 px-6">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold ${r.status === 'Ready for Pickup' || r.status === 'Released' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">${r.status}</span>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <select onchange="updateDocStatus('${r.id}', this.value)" class="bg-white border border-gray-300 rounded-lg px-2 py-1 text-[11px] font-semibold">
                                    <option value="">Update Status...</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Ready for Pickup">Ready for Pickup</option>
                                    <option value="Released">Released</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    countBadge.innerText = '0 Requests';
                    tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-gray-400">No document requests submitted yet.</td></tr>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function updateDocStatus(id, status) {
            if (!status) return;
            const remarks = prompt(`Enter optional remarks for setting status to ${status}:`, '');
            if (remarks === null) return;

            try {
                const res = await fetch('/api/admin.php?action=update_document_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ request_id: id, status: status, remarks: remarks, csrf_token: csrfToken })
                });

                const data = await res.json();
                if (data.success) {
                    await loadDocumentRequests();
                } else {
                    if (window.notify) window.notify(data.message || 'Error updating status.', 'error');
                }
            } catch (err) {
                if (window.notify) window.notify('Update Error: ' + err.message, 'error');
            }
        }

        /* ── Feature Settings (app_settings-backed) ── */
        const FEATURE_KEYS = [
            { id: 'set-present-window', key: 'attendance_present_window_minutes' },
            { id: 'set-late-window',    key: 'attendance_late_window_minutes' },
            { id: 'grace-period',       key: 'attendance_grace_minutes' },
            { id: 'set-upload-max',     key: 'upload_max_size_mb' }
        ];

        async function loadFeatureSettings() {
            try {
                const res = await fetch('/api/admin.php?action=get_app_settings');
                const data = await res.json();
                if (!data.success) return;
                FEATURE_KEYS.forEach(({id, key}) => {
                    const el = document.getElementById(id);
                    const row = data.settings.find(s => s.setting_key === key);
                    if (el && row) el.value = row.setting_value;
                });
            } catch (e) { console.error(e); }
        }

        document.getElementById('btn-save-feature-settings')?.addEventListener('click', async function () {
            const btn = this;
            const msgEl = document.getElementById('feature-settings-msg');
            btn.disabled = true;

            let allOk = true;
            for (const {id, key} of FEATURE_KEYS) {
                const el = document.getElementById(id);
                if (!el || el.value === '') continue;
                try {
                    const res = await fetch('/api/admin.php?action=set_academic_setting', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ key, value: el.value, csrf_token: csrfToken })
                    });
                    const data = await res.json();
                    if (!data.success) { allOk = false; showFeatureMsg(msgEl, data.message || 'Save failed.', false); break; }
                } catch (e) { allOk = false; showFeatureMsg(msgEl, 'Network error: ' + e.message, false); break; }
            }
            if (allOk) showFeatureMsg(msgEl, '✓ Feature settings saved!', true);
            btn.disabled = false;
        });

        function showFeatureMsg(el, text, ok) {
            el.classList.remove('hidden');
            el.className = 'rounded-xl px-4 py-2.5 text-xs font-semibold ' +
                (ok ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                    : 'bg-red-50 border border-red-200 text-error');
            el.textContent = text;
        }

        loadAcademicTerms();
        loadGracePeriod();
        loadFeatureSettings();
        loadDocumentRequests();
    </script>
</body>
</html>
