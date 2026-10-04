<?php
/**
 * programs.php — Degree Programs & Courses Management
 * NPC Connect LMS Admin Portal
 *
 * Allows administrators and registrars to:
 * 1. View all official degree programs and courses in the database
 * 2. Add new academic courses / programs
 * 3. Edit course codes, titles, and departments (e.g. correcting AIS title)
 * 4. Delete courses that have no enrolled students
 */

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
    <title>Courses & Degree Programs - NPC Connect Admin</title>
    <!-- Pre-paint theme -->
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
                        "secondary": "rgb(var(--secondary-rgb) / <alpha-value>)",
                        "secondary-container": "rgb(var(--secondary-container-rgb) / <alpha-value>)",
                        "on-secondary": "rgb(var(--on-secondary-rgb) / <alpha-value>)",
                        "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
                        "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
                        "surface-container-lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
                        "surface-container-low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
                        "surface-container": "rgb(var(--surface-container-rgb) / <alpha-value>)",
                        "on-surface": "rgb(var(--on-surface-rgb) / <alpha-value>)",
                        "on-surface-variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
                        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
                        "error": "rgb(var(--error-rgb) / <alpha-value>)"
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
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>

    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Academic Programs & Courses</h2>
            </div>
            <div class="flex items-center gap-3">
                <button id="btn-add-program" class="bg-primary text-on-primary px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold hover:opacity-90 transition-opacity npc-navy-card cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                    Add Course / Program
                </button>
            </div>
        </header>

        <!-- Canvas Container -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 flex-1">
            
            <!-- Quick Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[26px]">school</span>
                    </div>
                    <div>
                        <span class="block text-xs font-mono uppercase tracking-wider text-on-surface-variant font-semibold">Total Courses</span>
                        <span id="stat-total-programs" class="text-2xl font-bold text-primary">0</span>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[26px]">apartment</span>
                    </div>
                    <div>
                        <span class="block text-xs font-mono uppercase tracking-wider text-on-surface-variant font-semibold">Departments</span>
                        <span id="stat-total-depts" class="text-2xl font-bold text-emerald-600">0</span>
                    </div>
                </div>
                <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[26px]">groups</span>
                    </div>
                    <div>
                        <span class="block text-xs font-mono uppercase tracking-wider text-on-surface-variant font-semibold">Enrolled Students</span>
                        <span id="stat-total-students" class="text-2xl font-bold text-amber-600">0</span>
                    </div>
                </div>
            </div>

            <!-- Header & Filter Bar -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight">College Degree Programs</h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">Manage academic degrees, edit course descriptions (e.g. AIS, BSIS), and add new curricula.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-[18px]">search</span>
                        <input type="text" id="search-input" oninput="filterPrograms()" placeholder="Search program code or title..." class="w-full pl-9 pr-4 py-2 bg-surface border border-outline-variant/60 rounded-xl text-xs focus:outline-none focus:border-primary">
                    </div>

                    <!-- Department Filter -->
                    <div>
                        <select id="dept-filter" onchange="filterPrograms()" class="bg-surface border border-outline-variant/60 text-primary text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            <option value="all">All Departments</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Programs Table Container -->
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
                <div class="p-4 px-6 border-b border-outline-variant bg-surface-subtle flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-primary text-sm">Course Directory</span>
                        <span id="program-count-badge" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-primary-container text-white">0</span>
                    </div>
                    <span class="text-[11px] text-on-surface-variant font-mono">Real-time sync to all class & user dropdowns</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-xs font-mono text-on-surface uppercase tracking-wider select-none">
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Program Code</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Degree Program Title</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant">Department / College</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-center">Enrolled</th>
                                <th class="py-3.5 px-6 font-semibold border-b border-outline-variant text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="programs-list" class="divide-y divide-outline-variant/30 text-sm">
                            <tr>
                                <td colspan="5" class="p-8 text-center text-on-surface-variant">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>
                                        <span>Loading academic programs...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD PROGRAM MODAL -->
        <div id="add-program-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">add_circle</span> Add Course / Program
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Creates a new degree course in the database</p>
                    </div>
                    <button onclick="document.getElementById('add-program-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Program / Course Code *</label>
                        <input type="text" id="add-prog-code" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono uppercase font-bold" placeholder="e.g. AIS, BSCS, BLIS">
                        <p class="text-[11px] text-on-surface-variant mt-1">Short acronym used in student sections (e.g. AIS, BSIS).</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Full Degree Title / Description *</label>
                        <input type="text" id="add-prog-name" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm" placeholder="e.g. Associate in Information Systems">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Academic Department / College</label>
                        <input type="text" id="add-prog-dept" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm" placeholder="e.g. College of Computer Studies" value="College of Computer Studies">
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('add-program-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="save-new-program-btn" class="px-4 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Course</button>
                </div>
            </div>
        </div>

        <!-- 2. EDIT PROGRAM MODAL -->
        <div id="edit-program-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-outline-variant/20">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">edit</span> Edit Course / Program
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Correct program titles or update curriculum details</p>
                    </div>
                    <button onclick="document.getElementById('edit-program-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <input type="hidden" id="edit-prog-id">
                <input type="hidden" id="edit-prog-old-code">
                <div class="p-6 flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Program / Course Code *</label>
                        <input type="text" id="edit-prog-code" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm font-mono uppercase font-bold">
                        <p class="text-[11px] text-on-surface-variant mt-1">Changing the code automatically updates student enrollments.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Full Degree Title / Description *</label>
                        <input type="text" id="edit-prog-name" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Academic Department / College</label>
                        <input type="text" id="edit-prog-dept" class="w-full px-4 py-2 bg-surface border border-outline-variant/50 rounded-xl focus:outline-none focus:border-primary text-sm">
                    </div>
                </div>
                <div class="p-4 border-t border-outline-variant/30 bg-surface-subtle flex justify-end gap-3">
                    <button onclick="document.getElementById('edit-program-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-variant transition-colors font-semibold text-sm">Cancel</button>
                    <button id="save-edit-program-btn" class="px-4 py-2 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-semibold text-sm npc-navy-card">Save Changes</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        const csrfToken = '<?= $csrf_token ?>';
        let allPrograms = [];

        async function loadPrograms() {
            const tbody = document.getElementById('programs-list');
            try {
                const res = await fetch('/api/admin.php?action=list_programs');
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load programs');

                allPrograms = data.programs || [];
                document.getElementById('program-count-badge').innerText = allPrograms.length;
                document.getElementById('stat-total-programs').innerText = allPrograms.length;

                // Unique departments
                const depts = new Set(allPrograms.map(p => p.department).filter(Boolean));
                document.getElementById('stat-total-depts').innerText = depts.size;

                // Total enrolled students
                const totalStudents = allPrograms.reduce((acc, p) => acc + parseInt(p.student_count || 0), 0);
                document.getElementById('stat-total-students').innerText = totalStudents;

                // Populate Dept Filter
                const deptSel = document.getElementById('dept-filter');
                const currentVal = deptSel.value;
                deptSel.innerHTML = '<option value="all">All Departments</option>' + 
                    Array.from(depts).map(d => `<option value="${escapeHtml(d)}" ${d === currentVal ? 'selected' : ''}>${escapeHtml(d)}</option>`).join('');

                renderPrograms(allPrograms);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-error font-semibold">Failed to load courses from database: ${escapeHtml(err.message)}</td></tr>`;
            }
        }

        function renderPrograms(list) {
            const tbody = document.getElementById('programs-list');
            if (!list || list.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="p-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2 block">school</span>
                            <p class="font-semibold text-lg">No degree courses found</p>
                            <p class="text-xs text-on-surface-variant mt-1">Click "Add Course / Program" above to create a new degree curriculum.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = list.map(p => {
                const count = parseInt(p.student_count || 0);
                const classCount = parseInt(p.class_count || 0);
                return `
                    <tr class="hover:bg-surface-subtle transition-colors">
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-primary text-white npc-navy-card">
                                    ${escapeHtml(p.code)}
                                </span>
                            </div>
                        </td>
                        <td class="py-3.5 px-6 font-bold text-on-surface text-sm">
                            ${escapeHtml(p.name)}
                        </td>
                        <td class="py-3.5 px-6 text-xs text-on-surface-variant font-medium">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container text-primary">
                                <span class="material-symbols-outlined text-[14px]">apartment</span>
                                ${escapeHtml(p.department || 'General Academics')}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold ${count > 0 ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20' : 'bg-surface-container-low text-on-surface-variant'}">
                                ${count} student${count === 1 ? '' : 's'}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button onclick="openEditProgram('${escapeHtml(p.id)}')" class="text-primary hover:underline text-xs font-semibold inline-flex items-center gap-0.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-[15px]">edit</span> Edit
                                </button>
                                <button onclick="deleteProgram('${escapeHtml(p.id)}', '${escapeHtml(p.code)}', ${count})" class="text-error hover:underline text-xs font-semibold inline-flex items-center gap-0.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-[15px]">delete</span> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function filterPrograms() {
            const query = (document.getElementById('search-input').value || '').toLowerCase().trim();
            const deptVal = document.getElementById('dept-filter').value;

            const filtered = allPrograms.filter(p => {
                const matchesQuery = !query || 
                    (p.code && p.code.toLowerCase().includes(query)) ||
                    (p.name && p.name.toLowerCase().includes(query)) ||
                    (p.department && p.department.toLowerCase().includes(query));

                const matchesDept = deptVal === 'all' || (p.department && p.department === deptVal);

                return matchesQuery && matchesDept;
            });

            renderPrograms(filtered);
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Add Program Handlers
        document.getElementById('btn-add-program').onclick = () => {
            document.getElementById('add-prog-code').value = '';
            document.getElementById('add-prog-name').value = '';
            document.getElementById('add-program-modal').classList.remove('hidden');
            setTimeout(() => document.getElementById('add-prog-code').focus(), 60);
        };

        document.getElementById('save-new-program-btn').onclick = async () => {
            const btn = document.getElementById('save-new-program-btn');
            const code = document.getElementById('add-prog-code').value.trim();
            const name = document.getElementById('add-prog-name').value.trim();
            const dept = document.getElementById('add-prog-dept').value.trim();

            if (!code || !name) {
                if (window.notify) window.notify('Please provide both Course Code and Program Name.', 'error');
                else alert('Please provide both Course Code and Program Name.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">progress_activity</span> Saving…';

            try {
                const res = await fetch('/api/admin.php?action=create_program', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ code, name, department: dept })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Creation failed');
                if (window.notify) window.notify(data.message, 'success');
                if (window.npcConfetti) window.npcConfetti.burst(60);
                document.getElementById('add-program-modal').classList.add('hidden');
                loadPrograms();
            } catch (err) {
                if (window.notify) window.notify('Error: ' + err.message, 'error');
                else alert('Error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Course';
            }
        };

        // Edit Program Handlers
        function openEditProgram(id) {
            const p = allPrograms.find(x => String(x.id) === String(id));
            if (!p) return;
            document.getElementById('edit-prog-id').value = p.id;
            document.getElementById('edit-prog-old-code').value = p.code;
            document.getElementById('edit-prog-code').value = p.code;
            document.getElementById('edit-prog-name').value = p.name;
            document.getElementById('edit-prog-dept').value = p.department || '';
            document.getElementById('edit-program-modal').classList.remove('hidden');
            setTimeout(() => document.getElementById('edit-prog-name').focus(), 60);
        }

        document.getElementById('save-edit-program-btn').onclick = async () => {
            const btn = document.getElementById('save-edit-program-btn');
            const id = document.getElementById('edit-prog-id').value;
            const oldCode = document.getElementById('edit-prog-old-code').value;
            const code = document.getElementById('edit-prog-code').value.trim();
            const name = document.getElementById('edit-prog-name').value.trim();
            const dept = document.getElementById('edit-prog-dept').value.trim();

            if (!code || !name) {
                if (window.notify) window.notify('Program Code and Name cannot be empty.', 'error');
                else alert('Program Code and Name cannot be empty.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px] animate-spin">progress_activity</span> Updating…';

            try {
                const res = await fetch('/api/admin.php?action=update_program', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id, code, name, department: dept, old_code: oldCode })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Update failed');
                if (window.notify) window.notify(data.message, 'success');
                if (window.npcConfetti) window.npcConfetti.burst(60);
                document.getElementById('edit-program-modal').classList.add('hidden');
                loadPrograms();
            } catch (err) {
                if (window.notify) window.notify('Error updating program: ' + err.message, 'error');
                else alert('Error updating program: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            }
        };

        // Delete Program Handler
        async function deleteProgram(id, code, studentCount) {
            if (studentCount > 0) {
                if (window.notify) window.notify(`Cannot delete "${code}" because ${studentCount} student(s) are currently enrolled.`, 'error');
                else alert(`Cannot delete "${code}" because ${studentCount} student(s) are currently enrolled.`);
                return;
            }

            if (!await npcConfirm({
                title: 'Delete Degree Program',
                message: `Are you sure you want to remove degree program "${code}"?\n\nThis will remove it from the college catalog.`,
                type: 'danger',
                confirmText: 'Yes, Delete Program'
            })) return;

            try {
                const res = await fetch('/api/admin.php?action=delete_program', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id, code })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Delete failed');
                if (window.notify) window.notify(data.message, 'success');
                loadPrograms();
            } catch (err) {
                if (window.notify) window.notify('Error: ' + err.message, 'error');
                else alert('Error: ' + err.message);
            }
        }

        // Initialize on load
        loadPrograms();
    </script>
</body>
</html>
