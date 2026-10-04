<?php
/**
 * admin/academic/elms.php — Master Campus ELMS Oversight Center
 * 
 * Part of NPC ELMS (Electronic Learning Management System)
 * Provides administrators with campus-wide oversight of:
 *  - All collegiate courses & enrolled sections
 *  - Published learning handouts, syllabi & lecture slides
 *  - Course assignments across departments
 *  - Student submission compliance & grading audit
 */

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$adminName = $_SESSION['name'] ?? 'Administrator';
$adminEmail = $_SESSION['email'] ?? '';
$adminInitial = strtoupper(substr($adminName, 0, 1));
$csrfToken = getCsrfToken();

$PAGE_TITLE = 'Master LMS Center · NPC LMS Admin';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <?php include __DIR__ . '/../../includes/_head.php'; ?>
    <style>
        .admin-course-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .admin-course-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(0, 23, 54, 0.08); }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>

    <div class="flex min-h-screen w-full" id="app-root">
        <!-- Sidebar Navigation -->
        <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 bg-surface" id="main-wrapper">
            <!-- Topbar -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-4">
                    <span class="text-xl font-bold text-primary lg:hidden">NPC LMS</span>
                    <div class="hidden lg:flex items-center gap-2 text-xs font-mono text-on-surface-variant">
                        <span>Admin Portal</span>
                        <span>/</span>
                        <span class="text-primary font-bold">Master LMS Oversight Hub</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="openAdminUploadModal()" class="px-3.5 py-1.5 rounded-xl border border-primary/40 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold transition-all inline-flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">upload_file</span> + Campus Module
                    </button>
                    <button type="button" onclick="openAdminAsgModal()" class="px-3.5 py-1.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all inline-flex items-center gap-1 shadow-xs cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">add_task</span> + Assignment
                    </button>
                    <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card">
                        <?= $adminInitial ?>
                    </div>
                    <span class="text-sm font-semibold text-primary hidden sm:inline">
                        <?= htmlspecialchars($adminName) ?>
                    </span>
                </div>
            </header>

            <!-- Main Canvas -->
            <main class="flex-1 p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 lg:pl-64" id="canvas-container">
                
                <!-- Master ELMS Banner -->
                <div class="rounded-3xl border border-outline-variant/80 p-6 md:p-8 bg-gradient-to-r from-primary-container/20 via-surface-container-low to-secondary-container/10 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-primary text-on-primary shadow-xs">
                                    ELMS Administrative Center
                                </span>
                                <span class="text-xs font-mono text-on-surface-variant font-semibold">
                                    1st Semester · AY 2026–2027
                                </span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-primary tracking-tight">
                                Master Course Hub & Curriculum Oversight
                            </h1>
                            <p class="text-sm text-on-surface-variant mt-1 max-w-2xl">
                                Campus-wide monitoring of course materials, professor syllabus compliance, active coursework tasks, and institutional student submission performance.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="/admin/academic/schedules.php" class="px-4 py-2.5 rounded-xl border border-outline-variant hover:bg-surface-container text-primary font-bold text-xs transition-colors inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">calendar_month</span> Master Schedules
                            </a>
                            <a href="/admin/academic/grades.php" class="px-4 py-2.5 rounded-xl bg-primary text-on-primary hover:opacity-90 font-bold text-xs transition-all inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">grade</span> Grade Approvals
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Metrics Overview -->
                <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Collegiate Courses</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-admin-courses">4 Subjects</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">AIS 2A Active</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Published Handouts</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-admin-modules">8 Modules</h3>
                        <p class="text-[11px] text-status-success font-medium mt-0.5">Available for Students</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Course Assignments</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-admin-asgs">5 Tasks</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">With Set Deadlines</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Student Submissions</span>
                        <h3 class="text-2xl font-bold text-amber-500 mt-1" id="stat-admin-subs">1 Submitted</h3>
                        <p class="text-[11px] text-status-success font-bold mt-0.5">Compliance: 100%</p>
                    </div>
                </section>

                <!-- Navigation Tabs -->
                <div class="flex items-center gap-3 border-b border-outline-variant/60 pb-1">
                    <button id="tab-btn-admin-courses" onclick="switchAdminTab('courses')" class="px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        <span>Campus Courses & Modules</span>
                    </button>
                    <button id="tab-btn-admin-subs" onclick="switchAdminTab('subs')" class="px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Student Submissions Audit (<span id="admin-subs-badge">1</span>)</span>
                    </button>
                    <button id="tab-btn-admin-live" onclick="switchAdminTab('live')" class="px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">videocam</span>
                        <span>Live Virtual Classrooms (<span id="admin-live-badge">0</span>)</span>
                    </button>
                </div>

                <!-- TAB 1: Courses & Modules -->
                <div id="section-admin-courses" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="admin-courses-grid">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- TAB 2: Submissions Audit Table -->
                <div id="section-admin-subs" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-primary">Master Student Submissions Roster</h3>
                                <p class="text-xs text-on-surface-variant">Campus-wide verification of student coursework and faculty grading status.</p>
                            </div>
                            <span class="text-xs font-mono px-3 py-1 rounded-full bg-primary/10 text-primary font-bold">
                                Academic Audit
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-outline-variant/60 bg-surface-container-low/50 font-mono text-on-surface-variant uppercase text-[10px]">
                                        <th class="p-4">Student</th>
                                        <th class="p-4">Course</th>
                                        <th class="p-4">Assignment</th>
                                        <th class="p-4">Submitted Work</th>
                                        <th class="p-4">Date</th>
                                        <th class="p-4">Grading Status</th>
                                        <th class="p-4">Evaluator</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-submissions-tbody" class="divide-y divide-outline-variant/40">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Campus Live Virtual Classrooms -->
                <div id="section-admin-live" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-primary">Active Campus Virtual Broadcasts</h3>
                                <p class="text-xs text-on-surface-variant">Real-time oversight of online classes across sections.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-red-600/10 text-red-600 font-mono text-xs font-bold animate-pulse flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                                Live Monitoring
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-outline-variant/60 bg-surface-container-low/50 font-mono text-on-surface-variant uppercase text-[10px]">
                                        <th class="p-4">Course</th>
                                        <th class="p-4">Section</th>
                                        <th class="p-4">Instructor</th>
                                        <th class="p-4">Topic / Agenda</th>
                                        <th class="p-4">Started At</th>
                                        <th class="p-4">Room ID</th>
                                        <th class="p-4 text-right">Admin Action</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-live-table-body" class="divide-y divide-outline-variant/40">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ─── Admin Module Upload Modal ─── -->
    <div id="admin-upload-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Publish Campus Module</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono">Institutional handout or curriculum guide</p>
                    </div>
                </div>
                <button type="button" onclick="closeAdminUploadModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="admin-upload-form" class="space-y-4" onsubmit="handleAdminModuleUpload(event)">
                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Target Course <span class="text-error">*</span></label>
                    <select id="admin-mod-course" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" required></select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Module Title <span class="text-error">*</span></label>
                    <input type="text" id="admin-mod-title" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Course Syllabus & Department Policy 2026" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Type</label>
                        <select id="admin-mod-type" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none">
                            <option value="pdf">📄 PDF Document</option>
                            <option value="slides">📊 Presentation Slides</option>
                            <option value="doc">📝 Study Guide</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">File Size</label>
                        <input type="text" id="admin-mod-size" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" value="3.5 MB">
                    </div>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeAdminUploadModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="admin-upload-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">cloud_upload</span> Publish Module
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Admin Assignment Create Modal ─── -->
    <div id="admin-asg-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">add_task</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Publish Coursework Task</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono">Create assignment for collegiate class</p>
                    </div>
                </div>
                <button type="button" onclick="closeAdminAsgModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="admin-asg-form" class="space-y-4" onsubmit="handleAdminAsgCreate(event)">
                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Target Course <span class="text-error">*</span></label>
                    <select id="admin-asg-course" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" required></select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Assignment Title <span class="text-error">*</span></label>
                    <input type="text" id="admin-asg-title" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Midterm Practical Examination Project" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Instructions</label>
                    <textarea id="admin-asg-instructions" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Requirements and grading rubrics..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Points</label>
                        <input type="number" id="admin-asg-points" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" value="100">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Deadline Date</label>
                        <input type="date" id="admin-asg-due" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" required>
                    </div>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeAdminAsgModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="admin-asg-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span> Publish Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Embedded Virtual Classroom Viewer for Admin Audit ─── -->
    <div id="classroom-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-2 sm:p-4 bg-slate-950/90 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-6xl h-[92vh] flex flex-col shadow-2xl overflow-hidden relative">
            <div class="px-4 py-3 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-600/15 text-red-600 dark:text-red-400 font-mono text-xs font-bold animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                        ADMIN LIVE AUDIT
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-primary truncate max-w-xs sm:max-w-md" id="admin-room-title">Virtual Class Room</h3>
                        <p class="text-[10px] font-mono text-on-surface-variant truncate" id="admin-room-sub">PlugNmeet WebRTC Live Classroom · Campus Oversight</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openRoomInNewTab()" class="px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface text-primary text-xs font-bold hidden sm:inline-flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">open_in_new</span> Pop Out
                    </button>
                    <button type="button" onclick="closeClassroomModal()" class="px-3.5 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">close</span> Exit Audit
                    </button>
                </div>
            </div>
            <div class="flex-1 bg-black relative">
                <iframe id="classroom-iframe" class="w-full h-full border-0" allow="camera; microphone; fullscreen; display-capture; autoplay" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <!-- Admin ELMS Engine -->
    <script>
        var ADMIN_COURSES = [];
        var ADMIN_SUBMISSIONS = [];

        function switchAdminTab(tab) {
            var btnCourses = document.getElementById('tab-btn-admin-courses');
            var btnSubs = document.getElementById('tab-btn-admin-subs');
            var btnLive = document.getElementById('tab-btn-admin-live');
            var secCourses = document.getElementById('section-admin-courses');
            var secSubs = document.getElementById('section-admin-subs');
            var secLive = document.getElementById('section-admin-live');

            btnCourses.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
            btnSubs.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
            if (btnLive) btnLive.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';

            secCourses.classList.add('hidden');
            secSubs.classList.add('hidden');
            if (secLive) secLive.classList.add('hidden');

            if (tab === 'courses') {
                btnCourses.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                secCourses.classList.remove('hidden');
            } else if (tab === 'subs') {
                btnSubs.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                secSubs.classList.remove('hidden');
            } else if (tab === 'live') {
                if (btnLive) btnLive.className = 'px-4 py-2.5 text-xs font-bold text-red-600 border-b-2 border-red-600 flex items-center gap-2 cursor-pointer';
                if (secLive) secLive.classList.remove('hidden');
            }
        }

        async function loadAdminElms() {
            try {
                var [resCourses, resSubs] = await Promise.all([
                    fetch('/api/elms.php?action=get_courses'),
                    fetch('/api/elms.php?action=get_submissions')
                ]);

                var dataCourses = await resCourses.json();
                var dataSubs = await resSubs.json();

                if (dataCourses.success && dataCourses.courses) {
                    ADMIN_COURSES = dataCourses.courses;
                    renderAdminCourses(ADMIN_COURSES);
                    populateAdminDropdowns(ADMIN_COURSES);
                    renderAdminLiveSessions(ADMIN_COURSES);
                }

                if (dataSubs.success && dataSubs.submissions) {
                    ADMIN_SUBMISSIONS = dataSubs.submissions;
                    renderAdminSubmissions(ADMIN_SUBMISSIONS);
                }
            } catch (err) {
                console.error('Error loading Admin ELMS:', err);
            }
        }

        function populateAdminDropdowns(courses) {
            var modSel = document.getElementById('admin-mod-course');
            var asgSel = document.getElementById('admin-asg-course');
            var opts = '';
            courses.forEach(function (c) {
                opts += '<option value="' + c.code + '">' + c.code + ' - ' + c.title + '</option>';
            });
            if (modSel) modSel.innerHTML = opts;
            if (asgSel) asgSel.innerHTML = opts;
        }

        function renderAdminCourses(courses) {
            var container = document.getElementById('admin-courses-grid');
            var totalModules = 0;
            var totalAsgs = 0;
            var html = '';

            courses.forEach(function (c) {
                totalModules += (c.modules || []).length;
                totalAsgs += (c.assignments || []).length;

                var isLive = c.live_session && c.live_session.is_active;
                var cardBorder = isLive ? 'border-2 border-red-500/70 shadow-md ring-4 ring-red-500/10' : 'border border-outline-variant shadow-xs';
                var livePill = '';
                if (isLive) {
                    livePill = '<div class="mt-3.5 p-2.5 rounded-xl bg-red-600/10 border border-red-600/30 flex items-center justify-between text-xs animate-pulse">' +
                               '<span class="font-bold text-red-600 dark:text-red-400 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>🔴 Live Class Active (Sec ' + c.section + ')</span>' +
                               '<div class="flex items-center gap-1.5">' +
                               '<button type="button" onclick="adminAuditRoom(\'' + c.code + '\', \'' + c.title.replace(/'/g, "\\'") + '\', \'' + c.section + '\', \'' + (c.live_session.room_id || '') + '\')" class="px-2.5 py-1 rounded-lg bg-red-600 text-white text-[10px] font-bold hover:bg-red-700 cursor-pointer inline-flex items-center gap-1">Audit</button>' +
                               '<button type="button" onclick="adminEndRoom(\'' + c.code + '\')" class="px-2 py-1 rounded-lg border border-red-500/30 text-red-600 bg-surface hover:bg-red-500/10 text-[10px] font-bold cursor-pointer inline-flex items-center gap-0.5">End</button>' +
                               '</div>' +
                               '</div>';
                }

                var modulesList = '';
                (c.modules || []).forEach(function (m) {
                    var icon = m.type === 'slides' ? 'slideshow' : 'description';
                    modulesList += '<div class="p-2 rounded-xl bg-surface-container-low border border-outline-variant/40 flex items-center justify-between gap-2 text-xs">' +
                                  '<div class="flex items-center gap-2 min-w-0">' +
                                  '<span class="material-symbols-outlined text-[16px] text-primary shrink-0">' + icon + '</span>' +
                                  '<span class="font-medium text-primary truncate">' + m.title + '</span>' +
                                  '</div>' +
                                  '<span class="font-mono text-[10px] text-on-surface-variant shrink-0">' + m.size + '</span>' +
                                  '</div>';
                });

                var asgList = '';
                (c.assignments || []).forEach(function (a) {
                    asgList += '<div class="p-2 rounded-xl bg-surface-container-low border border-outline-variant/40 flex items-center justify-between gap-2 text-xs">' +
                               '<div class="min-w-0">' +
                               '<p class="font-bold text-on-surface truncate">' + a.title + '</p>' +
                               '<p class="text-[10px] font-mono text-on-surface-variant">Due: ' + a.due_date + ' · ' + a.points + ' pts</p>' +
                               '</div>' +
                               '</div>';
                });

                html += '<div class="admin-course-card bg-surface-container-lowest ' + cardBorder + ' rounded-2xl p-6 flex flex-col justify-between">' +
                        '<div>' +
                        '<div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-outline-variant/60">' +
                        '<span class="px-2.5 py-1 rounded-lg bg-primary-container text-on-primary font-mono text-xs font-bold">' + c.code + '</span>' +
                        '<span class="text-xs font-mono text-on-surface-variant font-medium">' + c.schedule + '</span>' +
                        '</div>' +
                        '<h3 class="text-base font-bold text-primary leading-snug">' + c.title + '</h3>' +
                        '<p class="text-xs text-on-surface-variant mt-1">' + c.instructor + ' · ' + c.room + '</p>' +

                        livePill +

                        '<div class="mt-4 flex items-center justify-between gap-2">' +
                        '<span class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant">Modules (' + (c.modules || []).length + '):</span>' +
                        '<button type="button" onclick="openAdminUploadModalFor(\'' + c.code + '\')" class="text-primary text-[11px] font-bold hover:underline inline-flex items-center gap-0.5 cursor-pointer">' +
                        '<span class="material-symbols-outlined text-[14px]">add</span> Add Module' +
                        '</button>' +
                        '</div>' +
                        '<div class="mt-2 space-y-1.5">' + (modulesList || '<p class="text-xs text-on-surface-variant/70 italic p-2">No modules uploaded.</p>') + '</div>' +

                        '<div class="mt-5 flex items-center justify-between gap-2">' +
                        '<span class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant">Assignments (' + (c.assignments || []).length + '):</span>' +
                        '<button type="button" onclick="openAdminAsgModalFor(\'' + c.code + '\')" class="text-primary text-[11px] font-bold hover:underline inline-flex items-center gap-0.5 cursor-pointer">' +
                        '<span class="material-symbols-outlined text-[14px]">add</span> Post Task' +
                        '</button>' +
                        '</div>' +
                        '<div class="mt-2 space-y-1.5">' + (asgList || '<p class="text-xs text-on-surface-variant/70 italic p-2">No assignments posted.</p>') + '</div>' +
                        '</div>' +

                        '<div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs">' +
                        '<span class="text-on-surface-variant font-mono text-[11px]">' + c.program + ' ' + c.section + '</span>' +
                        '<button type="button" onclick="switchAdminTab(\'subs\')" class="text-primary font-bold hover:underline inline-flex items-center gap-1 cursor-pointer">' +
                        'View Submissions Audit →' +
                        '</button>' +
                        '</div>' +
                        '</div>';
            });

            container.innerHTML = html;
            document.getElementById('stat-admin-courses').textContent = courses.length + ' Subjects';
            document.getElementById('stat-admin-modules').textContent = totalModules + ' Handouts';
            document.getElementById('stat-admin-asgs').textContent = totalAsgs + ' Tasks';
        }

        function renderAdminSubmissions(subs) {
            subs = subs || ADMIN_SUBMISSIONS || [];
            var tbody = document.getElementById('admin-submissions-tbody');
            document.getElementById('admin-subs-badge').textContent = subs.length;
            document.getElementById('stat-admin-subs').textContent = subs.length + ' Recorded';

            if (subs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-on-surface-variant italic">No student submissions found.</td></tr>';
                return;
            }

            var html = '';
            subs.forEach(function (s) {
                var statusBadge = s.status === 'Graded'
                    ? '<span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-status-success/15 text-status-success">Graded · ' + s.score + '</span>'
                    : '<span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300">Awaiting Grade</span>';

                html += '<tr class="hover:bg-surface-container-low transition-colors">' +
                        '<td class="p-4"><p class="font-bold text-primary">' + s.student_name + '</p><p class="font-mono text-[10px] text-on-surface-variant">' + s.student_number + '</p></td>' +
                        '<td class="p-4 font-mono font-bold text-primary">' + s.course_code + '</td>' +
                        '<td class="p-4 font-semibold text-on-surface">' + s.assignment_title + '</td>' +
                        '<td class="p-4"><a href="' + s.submitted_link + '" target="_blank" class="text-primary underline font-medium hover:opacity-80 inline-flex items-center gap-1">Open Submission <span class="material-symbols-outlined text-[13px]">open_in_new</span></a></td>' +
                        '<td class="p-4 font-mono text-[11px] text-on-surface-variant">' + s.submitted_at + '</td>' +
                        '<td class="p-4">' + statusBadge + '</td>' +
                        '<td class="p-4 font-mono text-[11px] text-on-surface-variant">' + (s.graded_by || '—') + '</td>' +
                        '</tr>';
            });

            tbody.innerHTML = html;
        }

        function openAdminUploadModal() {
            document.getElementById('admin-upload-modal').classList.remove('hidden');
            document.getElementById('admin-upload-modal').classList.add('flex');
        }
        function openAdminUploadModalFor(courseCode) {
            var sel = document.getElementById('admin-mod-course');
            if (sel) sel.value = courseCode;
            openAdminUploadModal();
        }
        function closeAdminUploadModal() {
            document.getElementById('admin-upload-modal').classList.add('hidden');
            document.getElementById('admin-upload-modal').classList.remove('flex');
        }

        function openAdminAsgModal() {
            var dueInput = document.getElementById('admin-asg-due');
            var nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
            dueInput.value = nextWeek.toISOString().split('T')[0];
            document.getElementById('admin-asg-modal').classList.remove('hidden');
            document.getElementById('admin-asg-modal').classList.add('flex');
        }
        function openAdminAsgModalFor(courseCode) {
            var sel = document.getElementById('admin-asg-course');
            if (sel) sel.value = courseCode;
            openAdminAsgModal();
        }
        function closeAdminAsgModal() {
            document.getElementById('admin-asg-modal').classList.add('hidden');
            document.getElementById('admin-asg-modal').classList.remove('flex');
        }

        async function handleAdminModuleUpload(e) {
            e.preventDefault();
            var courseCode = document.getElementById('admin-mod-course').value;
            var title = document.getElementById('admin-mod-title').value.trim();
            var type = document.getElementById('admin-mod-type').value;
            var size = document.getElementById('admin-mod-size').value.trim();
            var btn = document.getElementById('admin-upload-btn');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Publishing...';

            try {
                var res = await fetch('/api/elms.php?action=add_module', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_code: courseCode,
                        title: title,
                        type: type,
                        size: size
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to upload module');

                closeAdminUploadModal();
                if (window.notify) {
                    window.notify('Campus module published to ' + courseCode + '!', 'success');
                } else {
                    alert('Module published successfully!');
                }
                loadAdminElms();
            } catch (err) {
                alert('Error uploading module: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">cloud_upload</span> Publish Module';
            }
        }

        async function handleAdminAsgCreate(e) {
            e.preventDefault();
            var courseCode = document.getElementById('admin-asg-course').value;
            var title = document.getElementById('admin-asg-title').value.trim();
            var instructions = document.getElementById('admin-asg-instructions').value.trim();
            var points = parseInt(document.getElementById('admin-asg-points').value) || 100;
            var dueDate = document.getElementById('admin-asg-due').value + ' 23:59';
            var btn = document.getElementById('admin-asg-btn');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Publishing...';

            try {
                var res = await fetch('/api/elms.php?action=create_assignment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_code: courseCode,
                        title: title,
                        instructions: instructions,
                        points: points,
                        due_date: dueDate
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to create assignment');

                closeAdminAsgModal();
                if (window.notify) {
                    window.notify('Assignment posted for ' + courseCode + '!', 'success');
                } else {
                    alert('Assignment posted successfully!');
                }
                loadAdminElms();
            } catch (err) {
                alert('Error creating assignment: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">save</span> Publish Assignment';
            }
        }

        // ─── ADMIN LIVE VIRTUAL CLASSROOM OVERSIGHT ───
        var CURRENT_ADMIN_ROOM_URL = '';

        function renderAdminLiveSessions(courses) {
            var tbody = document.getElementById('admin-live-table-body');
            var badge = document.getElementById('admin-live-badge');
            if (!tbody) return;

            var active = courses.filter(function (c) {
                return c.live_session && c.live_session.is_active;
            });

            if (badge) badge.textContent = active.length;

            if (active.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-on-surface-variant italic">No live virtual classrooms currently broadcasting across campus.</td></tr>';
                return;
            }

            var html = '';
            active.forEach(function (c) {
                var s = c.live_session;
                html += '<tr class="hover:bg-surface-container-low transition-colors">' +
                        '<td class="p-4"><p class="font-bold text-primary">' + c.code + '</p><p class="text-[11px] text-on-surface-variant truncate max-w-xs">' + c.title + '</p></td>' +
                        '<td class="p-4"><span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-primary/10 text-primary">Section ' + c.section + '</span></td>' +
                        '<td class="p-4 text-xs font-semibold text-on-surface">' + c.instructor + '</td>' +
                        '<td class="p-4 text-xs text-on-surface-variant font-medium italic">' + (s.topic || 'Synchronous Lecture') + '</td>' +
                        '<td class="p-4 font-mono text-[11px] text-on-surface-variant">' + (s.started_at || 'Just now') + '</td>' +
                        '<td class="p-4 font-mono text-[11px] text-on-surface-variant truncate max-w-xs">' + s.room_id + '</td>' +
                        '<td class="p-4 text-right">' +
                        '<div class="flex items-center justify-end gap-1.5">' +
                        '<button type="button" onclick="adminAuditRoom(\'' + c.code + '\', \'' + c.title.replace(/'/g, "\\'") + '\', \'' + c.section + '\', \'' + s.room_id + '\', \'plugnmeet\', \'' + (s.meeting_link || '').replace(/'/g, "\\'") + '\')" class="px-3 py-1.5 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 cursor-pointer inline-flex items-center gap-1 shadow-xs">' +
                        '<span class="material-symbols-outlined text-[15px]">co_present</span> Audit Room' +
                        '</button>' +
                        '<button type="button" onclick="adminEndRoom(\'' + c.code + '\')" class="px-2.5 py-1.5 rounded-xl border border-red-500/30 bg-surface hover:bg-red-500/10 text-red-600 text-xs font-bold cursor-pointer inline-flex items-center gap-0.5">' +
                        '<span class="material-symbols-outlined text-[15px]">stop_circle</span> End' +
                        '</button>' +
                        '</div>' +
                        '</td>' +
                        '</tr>';
            });
            tbody.innerHTML = html;
        }

        function adminAuditRoom(code, title, section, roomId, platform, meetingLink) {
            var roomUrl = (meetingLink && meetingLink.includes('live_room.php')) 
                ? meetingLink 
                : ('/live_room.php?course_code=' + encodeURIComponent(code) + '&room_id=' + encodeURIComponent(roomId));
            CURRENT_ADMIN_ROOM_URL = roomUrl;

            window.open(roomUrl, '_blank');
            if (window.notify) window.notify('Opening PlugNmeet virtual classroom in dedicated audit tab...', 'info');
        }

        async function adminEndRoom(code) {
            if (!await npcConfirm({
                title: 'Terminate Live Session',
                message: 'Are you sure you want to end this live session for ' + code + ' as Administrator?\n\nThis will terminate the meeting room for both instructor and students.',
                type: 'danger',
                confirmText: 'Terminate Session'
            })) {
                return;
            }
            try {
                var res = await fetch('/api/elms.php?action=toggle_live_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_code: code,
                        state: 'end'
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to end live class');
                if (window.notify) {
                    window.notify(data.message, 'info');
                } else {
                    alert(data.message);
                }
                loadAdminElms();
            } catch (err) {
                alert('Admin End error: ' + err.message);
            }
        }

        function closeClassroomModal() {
            var modal = document.getElementById('classroom-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('classroom-iframe').src = 'about:blank';
        }

        function openRoomInNewTab() {
            if (CURRENT_ADMIN_ROOM_URL) {
                window.open(CURRENT_ADMIN_ROOM_URL, '_blank');
            }
        }

        document.addEventListener('DOMContentLoaded', loadAdminElms);
    </script>
</body>
</html>
