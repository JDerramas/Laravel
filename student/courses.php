<?php
/**
 * courses.php — Student ELMS Courses & Modules Hub
 * 
 * Part of NPC ELMS (Electronic Learning Management System)
 * Allows students to view enrolled subjects, lecture modules, download readings,
 * and submit coursework/assignments.
 */

require_once __DIR__ . '/../includes/auth.php';
require_student_area();

$userName = $_SESSION['name'] ?? 'Student';
$studentNumber = $_SESSION['student_number'] ?? '2024-00192';
$studentProgram = $_SESSION['program'] ?? 'AIS 2A';
$userEmail = $_SESSION['email'] ?? '';
if (empty($userEmail) && !empty($studentNumber) && $studentNumber !== 'N/A') {
    $cleanNum = preg_replace('/[^a-zA-Z0-9-]/', '', $studentNumber);
    $userEmail = "{$cleanNum}@navotaspolytechniccollege.edu.ph";
}
$csrfToken = getCsrfToken();

$PAGE_TITLE = 'Courses & LMS Learning Hub · NPC LMS';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <?php include __DIR__ . '/../includes/_head.php'; ?>
    <style>
        .lms-glass { background: rgba(var(--surface-rgb), 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .course-card { transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
        .course-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0, 23, 54, 0.08); }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <div class="flex min-h-screen w-full" id="app-root">
        <!-- Sidebar Navigation -->
        <?php $NPC_PORTAL = 'student'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">
            <!-- Topbar -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC LMS</span>
                    <div class="hidden lg:flex items-center gap-2 text-xs font-mono text-on-surface-variant">
                        <span>Portal</span>
                        <span>/</span>
                        <span class="text-primary font-bold">Courses & Modules Hub</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <a href="/student/profile.php" class="flex items-center gap-2 p-1 rounded-xl hover:bg-surface-container transition-all group" title="View My Profile">
                        <span class="font-mono text-[11px] sm:text-xs font-semibold bg-surface-container px-2 sm:px-3 py-1.5 rounded-md border border-outline-variant text-primary hidden sm:inline shrink-0" id="user-id-chip">
                            ID: <?= htmlspecialchars($studentNumber) ?>
                        </span>
                        <?php 
                        $userAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                        if (!empty($userAvatar)): ?>
                            <img alt="<?= htmlspecialchars($userName) ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-amber-300 shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all"
                                src="<?= htmlspecialchars($userAvatar) ?>" referrerpolicy="no-referrer"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0 group-hover:ring-2 group-hover:ring-amber-400 transition-all">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-sm font-semibold text-primary hidden md:inline group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                            <?= htmlspecialchars($userName) ?>
                        </span>
                    </a>
                </div>
            </header>

            <!-- Main Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 sm:space-y-8" id="canvas-container">
                
                <!-- LMS Header Banner -->
                <div class="relative overflow-hidden rounded-3xl border border-outline-variant/80 p-4 sm:p-6 md:p-8 bg-gradient-to-r from-primary-container/20 via-surface-container-low to-secondary-container/10 shadow-sm">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-primary text-on-primary shadow-xs">
                                    Official Campus LMS
                                </span>
                                <span class="text-xs font-mono text-on-surface-variant font-semibold">
                                    1st Semester · AY 2026–2027
                                </span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-primary tracking-tight">
                                Courses & Learning Modules
                            </h1>
                            <p class="text-sm text-on-surface-variant mt-1 max-w-2xl">
                                Access syllabus outlines, lecture presentation slides, required readings, and submit coursework directly to your instructors.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="/student/academic.php" class="px-4 py-2.5 rounded-xl border border-outline-variant hover:bg-surface-container text-primary font-bold text-xs transition-colors inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">grade</span> Academic Grades
                            </a>
                            <a href="/student/schedule.php" class="px-4 py-2.5 rounded-xl bg-primary text-on-primary hover:opacity-90 font-bold text-xs transition-all inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">calendar_month</span> Class Schedule
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ELMS Metric Summary Cards -->
                <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Enrolled Subjects</span>
                        <h3 class="text-2xl font-bold text-primary mt-1">4 Courses</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5"><?= htmlspecialchars($studentProgram) ?></p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Active Modules</span>
                        <h3 class="text-2xl font-bold text-primary mt-1">8 Handouts</h3>
                        <p class="text-[11px] text-status-success font-medium mt-0.5">All up-to-date</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Pending Tasks</span>
                        <h3 class="text-2xl font-bold text-amber-500 mt-1">3 Tasks</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">Due in next 7 days</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Submitted Work</span>
                        <h3 class="text-2xl font-bold text-primary mt-1">1 Graded</h3>
                        <p class="text-[11px] text-status-success font-bold mt-0.5">Score: 48/50 (96%)</p>
                    </div>
                </section>

                <!-- Navigation Tabs: All Courses vs Modules Library vs Assignments -->
                <div class="flex items-center gap-3 border-b border-outline-variant/60 pb-1">
                    <button id="tab-btn-courses" onclick="switchElmsTab('courses')" class="px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        <span>My Enrolled Courses (4)</span>
                    </button>
                    <button id="tab-btn-assignments" onclick="switchElmsTab('assignments')" class="px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">assignment</span>
                        <span>Assignments & Tasks (<span id="pending-tasks-count">3</span>)</span>
                    </button>
                </div>

                <!-- Live Classroom Alert Banner (Visible only if teacher is live in student's section) -->
                <div id="live-class-alert-banner" class="hidden"></div>

                <!-- ─── TAB 1: Enrolled Courses & Modules ─── -->
                <div id="section-courses" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="courses-container">
                        <!-- Loaded dynamically via JavaScript -->
                    </div>
                </div>

                <!-- ─── TAB 2: Assignments & Submissions ─── -->
                <div id="section-assignments" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-primary">Academic Tasks & Submissions</h3>
                                <p class="text-xs text-on-surface-variant">Course activities, problem sets, and case study submissions.</p>
                            </div>
                            <span class="text-xs font-mono px-2.5 py-1 rounded-full bg-primary/10 text-primary font-bold">
                                AY 2026–2027
                            </span>
                        </div>
                        <div class="divide-y divide-outline-variant/40" id="assignments-list">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ─── Interactive Assignment Submission Modal ─── -->
    <!-- ─── Interactive Assignment Submission Modal (Google Classroom Style) ─── -->
    <div id="submission-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-lg w-full shadow-2xl relative max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Your Work · Turn In Assignment</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono" id="sub-course-label">DM103</p>
                    </div>
                </div>
                <button type="button" onclick="closeSubmissionModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="submission-form" class="space-y-4" onsubmit="handleAssignmentSubmit(event)">
                <input type="hidden" id="sub-asg-id" value="">
                
                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Assignment Title</label>
                    <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/50">
                        <p class="text-xs font-bold text-primary" id="sub-asg-title">—</p>
                        <p class="text-[11px] text-on-surface-variant mt-1 italic" id="sub-asg-instructions"></p>
                    </div>
                </div>

                <!-- Existing submission preview if already turned in -->
                <div id="sub-existing-card" class="hidden p-3.5 rounded-xl bg-status-success/10 border border-status-success/30 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-status-success flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">check_circle</span> Turned In Previously
                        </span>
                        <span class="text-[10px] font-mono text-on-surface-variant" id="sub-existing-time"></span>
                    </div>
                    <div id="sub-existing-files-list" class="space-y-1.5">
                        <!-- Populated dynamically with View in Browser and Download buttons -->
                    </div>
                </div>

                <!-- Google Classroom Style Multi-File Attachment Box -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-primary">
                            Attach File(s) / Proof of Activity <span class="text-error">*</span>
                        </label>
                        <span class="text-[10px] font-mono text-on-surface-variant">Upload 1, 2 or more files (e.g. Proof 1 &amp; Proof 2)</span>
                    </div>
                    
                    <div id="sub-dropzone" class="border-2 border-dashed border-outline-variant hover:border-primary/60 rounded-2xl p-4 text-center bg-surface-container-low transition-colors cursor-pointer" onclick="document.getElementById('sub-file-input').click()">
                        <span class="material-symbols-outlined text-[30px] text-primary">cloud_upload</span>
                        <p class="text-xs font-bold text-primary mt-1" id="sub-file-label">Click or Drag &amp; Drop file(s) here</p>
                        <p class="text-[10px] text-on-surface-variant font-mono mt-0.5">Supports PNG, JPG, WebP, PDF, DOCX, XLSX, PPTX, ZIP, SQL (Up to 50MB each)</p>
                        <input type="file" id="sub-file-input" class="hidden" multiple onchange="onStudentFilesSelected(this)" accept="image/*,.pdf,.docx,.doc,.pptx,.ppt,.xlsx,.xls,.zip,.txt,.sql">
                    </div>

                    <!-- Staged multi-file preview list -->
                    <div id="sub-staged-files-list" class="space-y-2 mt-2.5">
                        <!-- Populated dynamically with staged files and preview buttons -->
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Optional External Link (Google Drive, GitHub, etc.)</label>
                    <input type="url" id="sub-link" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="https://docs.google.com/...">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Private Comments for Professor</label>
                    <textarea id="sub-notes" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Add any notes or context about your submission..."></textarea>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeSubmissionModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="sub-submit-btn" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Turn In Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL: Student Live Classroom Center & Verified Check-in ─── -->
    <div id="student-live-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden relative">
            <div class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-600/15 text-red-600 dark:text-red-400 font-mono text-xs font-bold animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                        LIVE CLASSROOM
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-primary truncate" id="student-live-title">Live Class Center</h3>
                        <p class="text-[11px] font-mono text-on-surface-variant" id="student-live-sub">Section Restricted</p>
                    </div>
                </div>
                <button type="button" onclick="closeStudentLiveModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="p-5 sm:p-6 space-y-4 bg-surface">
                <!-- Class Agenda Card -->
                <div class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/60 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold text-primary" id="student-live-code">DM103</span>
                        <span class="px-2 py-0.5 rounded-md bg-status-info/15 text-status-info font-mono text-[10px] font-bold" id="student-live-section">Section 2A</span>
                    </div>
                    <p class="text-xs text-on-surface-variant" id="student-live-instructor">Instructor: Assigned Faculty</p>
                    <div class="pt-2 border-t border-outline-variant/40">
                        <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider">Topic &amp; Agenda</p>
                        <p class="text-xs font-bold text-primary mt-0.5" id="student-live-topic">—</p>
                        <p class="text-xs text-on-surface mt-1 leading-relaxed" id="student-live-agenda">Synchronous lecture discussion.</p>
                    </div>
                </div>

                <!-- PlugNmeet Launcher CTA -->
                <div id="student-meet-launcher" class="p-4 rounded-2xl border-2 border-primary/20 bg-primary/5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-primary flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-primary">co_present</span> Institutional Virtual Classroom · PlugNmeet
                            </p>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">Auto-detects your presence and tracks your attendance in real time</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-lg bg-surface-container font-mono text-[10px] text-primary font-bold border border-outline-variant/60 inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px] text-primary">verified_user</span>
                                <span id="student-meet-account-pill"><?= htmlspecialchars($userEmail ?: 'NPC Student') ?></span>
                            </span>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 font-mono text-[10px] text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/20" id="student-meet-code-pill">
                                Room: <span id="student-meet-code-text">Virtual Classroom</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 pt-1">
                        <button type="button" id="student-join-meet-btn" onclick="startPlugNmeetPresenceSession(event)" class="w-full sm:flex-1 py-3 px-5 rounded-xl bg-primary text-on-primary hover:opacity-90 font-bold text-xs transition-all shadow-md inline-flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">co_present</span> Enter Live Classroom (PlugNmeet · Auto-Detect) ↗
                        </button>
                        <button type="button" onclick="copyPlugNmeetLinkToClipboard()" class="w-full sm:w-auto py-2.5 px-3 rounded-xl border border-outline-variant hover:bg-surface-container text-primary font-bold text-xs inline-flex items-center justify-center gap-1 cursor-pointer" title="Copy classroom link">
                            <span class="material-symbols-outlined text-[16px]">content_copy</span> Copy Link
                        </button>
                    </div>
                    <div class="pt-2 border-t border-outline-variant/30 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0">speed</span>
                        <p class="text-[11px] text-on-surface-variant font-mono">
                            ⚡ <strong>Auto-Presence Active:</strong> Joining the room automatically registers your presence and ticks your active attendance duration.
                        </p>
                    </div>
                </div>

                <!-- Live Class Presence & Active Duration Tracker HUD -->
                <div id="student-presence-hud" class="hidden p-4 rounded-2xl border border-primary/30 bg-primary/5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span id="hud-pulsing-dot" class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-status-success opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-status-success"></span>
                            </span>
                            <span class="text-xs font-bold text-primary" id="hud-online-status">Connected to PlugNmeet Classroom</span>
                        </div>
                        <span id="hud-leave-badge" class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-status-success/15 text-status-success border border-status-success/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-status-success animate-pulse"></span> Attendance Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between bg-surface-container-lowest p-3.5 rounded-xl border border-outline-variant/60">
                        <div>
                            <p class="text-[10px] uppercase font-mono text-on-surface-variant font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-primary">timer</span> Class Active Duration
                            </p>
                            <p class="text-2xl font-mono font-bold text-primary mt-0.5" id="hud-duration-timer">00:00:00</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="reopenPlugNmeetTab()" class="px-3 py-1.5 rounded-xl border border-primary text-primary hover:bg-primary/10 text-xs font-bold transition-colors inline-flex items-center gap-1 cursor-pointer" title="Re-open Classroom Room">
                                <span class="material-symbols-outlined text-[15px]">open_in_new</span> Room ↗
                            </button>
                            <button type="button" id="hud-leave-toggle-btn" onclick="studentToggleLeavePresence()" class="px-3 py-1.5 rounded-xl bg-error/10 text-error hover:bg-error/20 text-xs font-bold transition-colors inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[15px]">logout</span> Leave
                            </button>
                        </div>
                    </div>
                    <p class="text-[11px] text-on-surface-variant leading-relaxed">
                        <span class="font-bold text-primary">Live Tracking Active:</span> Your class duration is automatically recorded in real time. Please keep this session active while attending class.
                    </p>
                </div>

                <!-- Verified Attendance Check-in Box -->
                <div class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-primary">Verified Attendance Check-in</h4>
                            <p class="text-[11px] font-mono text-on-surface-variant" id="student-checkin-window-label">Status: Open</p>
                        </div>
                        <span id="student-checkin-badge" class="px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-status-info/15 text-status-info">Ready</span>
                    </div>

                    <p class="text-xs text-on-surface-variant leading-relaxed">
                        Official verification is synchronized with server time. Once you have joined the class, click the button below to generate your official electronic attendance receipt.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row gap-2">
                        <button type="button" id="student-checkin-btn" onclick="submitLiveCheckin()" class="flex-1 py-3 rounded-xl bg-status-success text-white font-bold text-xs hover:opacity-90 transition-opacity shadow-md inline-flex items-center justify-center gap-1.5 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">verified</span> Check In for Attendance
                        </button>
                        <button type="button" onclick="openAttendanceIssueModal()" class="px-4 py-3 rounded-xl border border-outline-variant hover:bg-surface-container-low text-on-surface font-semibold text-xs transition-colors cursor-pointer inline-flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">report_problem</span> Report Issue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── MODAL: Official Digital Attendance Receipt ─── -->
    <div id="attendance-receipt-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4 bg-slate-950/85 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-md shadow-2xl overflow-hidden relative">
            <div class="p-6 text-center border-b border-outline-variant/60 bg-surface-container-low">
                <div class="w-12 h-12 rounded-full bg-status-success/15 text-status-success flex items-center justify-center mx-auto mb-2 shadow-inner">
                    <span class="material-symbols-outlined text-[28px]">verified_user</span>
                </div>
                <h3 class="text-base font-bold text-primary">Navotas Polytechnic College</h3>
                <p class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider">Official ELMS Attendance Receipt</p>
                <div class="mt-3 inline-block">
                    <span id="receipt-status-pill" class="px-3.5 py-1 rounded-full font-mono text-xs font-bold uppercase bg-status-success/20 text-status-success">
                        PRESENT · VERIFIED
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-3 bg-surface text-xs" id="receipt-printable-area">
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Reference ID</span>
                    <span class="font-mono font-bold text-primary" id="receipt-ref-id">REF-20260910-A1B2C</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Student Name</span>
                    <span class="font-bold text-on-surface" id="receipt-student-name">Student</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Student Number</span>
                    <span class="font-mono text-on-surface" id="receipt-student-number">—</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Course &amp; Section</span>
                    <span class="font-bold text-primary" id="receipt-course-sec">DM103 · Section 2A</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Instructor</span>
                    <span class="text-on-surface" id="receipt-instructor">Assigned Faculty</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                    <span class="text-on-surface-variant">Check-in Timestamp</span>
                    <span class="font-mono text-on-surface" id="receipt-timestamp">—</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-on-surface-variant">Verification Method</span>
                    <span class="font-mono text-status-info font-semibold" id="receipt-method">Live Portal Check-in</span>
                </div>
            </div>

            <div class="p-4 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-between gap-2">
                <button type="button" onclick="printAttendanceReceipt()" class="px-4 py-2 rounded-xl border border-outline-variant hover:bg-surface text-primary font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">print</span> Print Receipt
                </button>
                <button type="button" onclick="closeAttendanceReceiptModal()" class="px-5 py-2 rounded-xl bg-primary text-on-primary font-bold text-xs hover:opacity-90 transition-opacity cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- ─── MODAL: Report Attendance Issue / Excuse Letter ─── -->
    <div id="attendance-issue-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-md shadow-2xl overflow-hidden relative">
            <div class="px-5 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-status-warning text-[20px]">report_problem</span>
                    <h3 class="text-sm font-bold text-primary">Report Attendance Issue / Excuse</h3>
                </div>
                <button type="button" onclick="closeAttendanceIssueModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="attendance-issue-form" class="p-5 space-y-4" onsubmit="handleAttendanceIssueSubmit(event)">
                <input type="hidden" id="issue-course-code" value="">
                <input type="hidden" id="issue-session-code" value="">
                <input type="hidden" id="issue-faculty-email" value="">

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Reason / Problem Statement <span class="text-error">*</span></label>
                    <textarea id="issue-reason" rows="3" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Provide details (e.g. power interruption, connectivity timeout, or reason for late check-in)..." required minlength="10"></textarea>
                    <p class="text-[10px] text-on-surface-variant mt-1">Minimum of 10 characters. Your professor will receive this report directly.</p>
                </div>

                <div class="pt-2 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeAttendanceIssueModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="issue-submit-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary font-bold text-xs hover:opacity-90 transition-opacity shadow-xs inline-flex items-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">send</span> Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── IN-BROWSER INTERACTIVE FILE PREVIEW MODAL (No Download Required) ─── -->
    <div id="file-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-5xl h-[88vh] flex flex-col shadow-2xl overflow-hidden relative">
            <!-- Header bar -->
            <div class="px-5 py-3.5 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]" id="preview-modal-icon">visibility</span>
                    </div>
                    <div class="truncate">
                        <h4 class="text-sm font-bold text-primary truncate" id="preview-modal-title">Document Preview</h4>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-primary-container text-on-primary" id="preview-modal-badge">PDF</span>
                            <span class="text-[11px] font-mono text-on-surface-variant" id="preview-modal-size"></span>
                            <span class="text-[11px] text-on-surface-variant/80 hidden sm:inline">· In-browser interactive preview (no download needed)</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a id="preview-modal-open-newtab" href="#" target="_blank" class="px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface-container text-xs font-semibold text-on-surface inline-flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">open_in_new</span> Open Tab
                    </a>
                    <a id="preview-modal-download-btn" href="#" download class="px-3 py-1.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold inline-flex items-center gap-1 transition-opacity shadow-xs">
                        <span class="material-symbols-outlined text-[15px]">download</span> Download
                    </a>
                    <button type="button" onclick="closeFilePreviewModal()" class="p-1.5 rounded-xl hover:bg-surface-container text-on-surface-variant cursor-pointer transition-colors" title="Close Preview">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
            </div>

            <!-- Content Viewer Body -->
            <div id="preview-modal-body" class="flex-1 bg-surface overflow-hidden relative flex items-center justify-center">
                <!-- Dynamically populated iframe, img, or text viewer -->
                <div class="flex items-center justify-center p-8 text-on-surface-variant">
                    <span class="material-symbols-outlined text-[24px] animate-spin mr-2">progress_activity</span> Loading file preview...
                </div>
            </div>
        </div>
    </div>

    <!-- ELMS Course Hub Logic -->
    <script>
        var COURSES_CACHE = [];
        var LAST_COURSES_FINGERPRINT = '';
        var REALTIME_SYNC_INTERVAL = null;
        var LAST_ACTIVE_LIVE_CODES = [];

        function switchElmsTab(tab) {
            var btnCourses = document.getElementById('tab-btn-courses');
            var btnAsg = document.getElementById('tab-btn-assignments');
            var secCourses = document.getElementById('section-courses');
            var secAsg = document.getElementById('section-assignments');

            if (tab === 'courses') {
                btnCourses.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                btnAsg.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
                secCourses.classList.remove('hidden');
                secAsg.classList.add('hidden');
            } else {
                btnAsg.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                btnCourses.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
                secAsg.classList.remove('hidden');
                secCourses.classList.add('hidden');
            }
        }

        function updateModalLockState(isLocked) {
            var checkinBtn = document.getElementById('student-checkin-btn');
            var windowLabel = document.getElementById('student-checkin-window-label');
            var badge = document.getElementById('student-checkin-badge');
            if (!checkinBtn || !windowLabel || !badge) return;

            // If already verified, do not revert to lock
            if (badge.textContent.includes('Verified')) return;

            if (isLocked) {
                checkinBtn.disabled = true;
                checkinBtn.className = 'flex-1 py-3 rounded-xl bg-surface-container text-on-surface-variant font-bold text-xs cursor-not-allowed opacity-60 inline-flex items-center justify-center gap-1.5';
                checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">lock</span> Check-in Locked by Instructor';
                windowLabel.textContent = 'Status: Locked by Instructor';
                badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-error/15 text-error';
                badge.textContent = 'Locked';
            } else {
                checkinBtn.disabled = false;
                checkinBtn.className = 'flex-1 py-3 rounded-xl bg-status-success text-white font-bold text-xs hover:opacity-90 transition-opacity shadow-md inline-flex items-center justify-center gap-1.5 cursor-pointer';
                checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">verified</span> Check In for Attendance';
                windowLabel.textContent = 'Status: Open';
                badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-status-success/15 text-status-success';
                badge.textContent = 'Open';
            }
        }

        function computeCoursesFingerprint(courses) {
            if (!courses || !Array.isArray(courses)) return '';
            return JSON.stringify(courses.map(function (c) {
                return {
                    id: c.id,
                    is_live: !!c.is_live,
                    is_live_for_me: !!c.is_live_for_me,
                    live_room: c.live_session ? c.live_session.room_id : '',
                    live_lock: c.live_session ? !!c.live_session.is_attendance_locked : false,
                    live_meet: c.live_session ? c.live_session.meeting_link : '',
                    live_topic: c.live_session ? c.live_session.topic : '',
                    mod_count: (c.modules || []).length,
                    asg_states: (c.assignments || []).map(function (a) {
                        return a.id + ':' + a.status + ':' + (a.score || '');
                    })
                };
            }));
        }

        async function loadElmsData(isInitial) {
            var container = document.getElementById('courses-container');
            var asgList = document.getElementById('assignments-list');
            try {
                var res = await fetch('/api/elms.php?action=get_courses');
                var data = await res.json();
                if (!data.success || !data.courses) {
                    throw new Error(data.error || 'Failed to fetch course data');
                }
                var newFingerprint = computeCoursesFingerprint(data.courses);
                if (newFingerprint !== LAST_COURSES_FINGERPRINT || isInitial) {
                    LAST_COURSES_FINGERPRINT = newFingerprint;

                    // Real-time live class detection & sound alert
                    var currentlyLive = data.courses.filter(function(c) { return c.is_live_for_me === true; });
                    currentlyLive.forEach(function(lc) {
                        if (!LAST_ACTIVE_LIVE_CODES.includes(lc.code) && !isInitial) {
                            if (window.notify) {
                                window.notify('🔴 LIVE ONLINE CLASS STARTED: ' + lc.code + ' — Click to join now!', 'success');
                            }
                            try {
                                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                                var osc = ctx.createOscillator();
                                var gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                                osc.start();
                                osc.stop(ctx.currentTime + 0.36);
                            } catch(e) {}
                        }
                    });
                    LAST_ACTIVE_LIVE_CODES.forEach(function(oldCode) {
                        var stillLive = currentlyLive.some(function(c) { return c.code === oldCode; });
                        if (!stillLive) {
                            if (window.notify) {
                                window.notify('📢 Live online class for ' + oldCode + ' has ended.', 'info');
                            }
                        }
                    });
                    LAST_ACTIVE_LIVE_CODES = currentlyLive.map(function(c) { return c.code; });

                    COURSES_CACHE = data.courses;
                    checkLiveClassBroadcast(COURSES_CACHE);
                    renderCourses(COURSES_CACHE);
                    renderAssignments(COURSES_CACHE);

                    // If student already has the live classroom modal open or session active, live-update lock or auto-terminate on session end
                    if (CURRENT_STUDENT_LIVE_SESSION && CURRENT_STUDENT_LIVE_SESSION.course_code) {
                        var activeC = COURSES_CACHE.find(function (c) {
                            return c.code === CURRENT_STUDENT_LIVE_SESSION.course_code;
                        });
                        var ls = (activeC && activeC.live_session && activeC.live_session.is_active && activeC.is_live_for_me !== false) ? activeC.live_session : null;
                        if (!ls) {
                            handleLiveClassEndedByInstructor(activeC || CURRENT_STUDENT_LIVE_SESSION);
                        } else {
                            CURRENT_STUDENT_LIVE_SESSION.is_attendance_locked = ls.is_attendance_locked;
                            CURRENT_STUDENT_LIVE_SESSION.meeting_link = ls.meeting_link;
                            updateModalLockState(ls.is_attendance_locked);
                        }
                    }
                }
            } catch (err) {
                if (isInitial && container) {
                    container.innerHTML = '<div class="col-span-2 p-6 rounded-2xl bg-error/10 border border-error/30 text-error text-center text-xs">Error loading courses: ' + err.message + '</div>';
                }
            }
        }

        function checkLiveClassBroadcast(courses) {
            var banner = document.getElementById('live-class-alert-banner');
            if (!banner) return;

            var liveCourse = courses.find(function (c) {
                return c.is_live_for_me === true;
            });

            if (!liveCourse) {
                banner.classList.add('hidden');
                banner.innerHTML = '';
                return;
            }

            var sec = liveCourse.section || 'Your Section';
            var topic = (liveCourse.live_session && liveCourse.live_session.topic) || 'Synchronous Virtual Lecture';
            var roomId = liveCourse.live_session && liveCourse.live_session.room_id;
            var platform = 'PlugNmeet WebRTC';

            banner.className = 'relative overflow-hidden rounded-3xl border-2 border-red-500/80 bg-gradient-to-r from-red-500/15 via-surface-container-low to-red-500/5 p-5 shadow-lg ring-4 ring-red-500/10 animate-fade-in mb-6';
            banner.innerHTML = '<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">' +
                               '<div class="flex items-start gap-3.5">' +
                               '<div class="w-11 h-11 rounded-2xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-md">' +
                               '<span class="material-symbols-outlined text-[24px] animate-pulse">sensors</span>' +
                               '</div>' +
                               '<div>' +
                               '<div class="flex items-center gap-2">' +
                               '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-red-600 text-white animate-pulse">🔴 LIVE CLASS IN PROGRESS</span>' +
                               '<span class="text-xs font-mono font-bold text-red-600 dark:text-red-400">Section ' + sec + '</span>' +
                               '<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-primary/10 text-primary">' + platform + '</span>' +
                               '</div>' +
                               '<h4 class="text-base font-bold text-primary mt-1">' + liveCourse.code + ' — ' + liveCourse.title + '</h4>' +
                               '<p class="text-xs text-on-surface-variant">Instructor: <strong class="text-on-surface">' + liveCourse.instructor + '</strong> · Topic: <em class="text-primary font-medium">' + topic + '</em></p>' +
                               '</div>' +
                               '</div>' +
                               '<div class="shrink-0 flex items-center gap-2">' +
                               '<button type="button" onclick="openStudentLiveCenter(\'' + liveCourse.code + '\', \'' + (roomId || '') + '\')" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition-all inline-flex items-center gap-2 cursor-pointer">' +
                               '<span class="material-symbols-outlined text-[18px]">co_present</span> Enter Live Classroom (PlugNmeet)' +
                               '</button>' +
                               '</div>' +
                               '</div>';
            banner.classList.remove('hidden');
        }

        function renderCourses(courses) {
            var container = document.getElementById('courses-container');
            var html = '';

            courses.forEach(function (c) {
                var isLiveForMe = c.is_live_for_me === true;
                var cardBorder = isLiveForMe ? 'border-2 border-red-500/80 ring-4 ring-red-500/10 shadow-md' : 'border border-outline-variant shadow-xs';

                var livePill = '';
                if (isLiveForMe) {
                    livePill = '<div class="mb-3.5 p-2.5 rounded-xl bg-red-600/10 border border-red-600/30 flex items-center justify-between animate-pulse">' +
                               '<span class="text-xs font-bold text-red-600 dark:text-red-400 flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>🔴 Live Class Active (Sec ' + c.section + ')</span>' +
                               '<button type="button" onclick="openStudentLiveCenter(\'' + c.code + '\', \'' + (c.live_session && c.live_session.room_id || '') + '\')" class="px-3 py-1 rounded-lg bg-red-600 text-white text-[11px] font-bold hover:bg-red-700 cursor-pointer inline-flex items-center gap-1 shadow-xs"><span class="material-symbols-outlined text-[14px]">co_present</span> Enter Class</button>' +
                               '</div>';
                }

                var modulesHtml = '';
                (c.modules || []).forEach(function (m) {
                    var icon = m.type === 'slides' ? 'slideshow' : (m.type === 'image' ? 'image' : 'description');
                    var viewUrl = '/api/elms.php?action=view_material&id=' + encodeURIComponent(m.id) + '&inline=1';
                    var dlUrl = '/api/elms.php?action=download_material&id=' + encodeURIComponent(m.id);
                    modulesHtml += '<div class="p-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/40 flex items-center justify-between gap-3 transition-colors">' +
                                   '<div onclick="openFilePreviewModal(\'' + viewUrl + '\', \'' + (m.file_name || m.title).replace(/'/g, "\\'") + '\', \'' + (m.type || '') + '\', \'' + (m.size || '') + '\')" class="flex items-center gap-2 min-w-0 flex-1 cursor-pointer group" title="Click to open file in browser">' +
                                   '<span class="material-symbols-outlined text-[18px] text-primary shrink-0 group-hover:scale-110 transition-transform">' + icon + '</span>' +
                                   '<div class="min-w-0">' +
                                   '<p class="text-xs font-semibold text-primary group-hover:underline truncate">' + m.title + '</p>' +
                                   '<p class="text-[10px] font-mono text-on-surface-variant">' + (m.file_name || 'handout') + ' · ' + m.size + '</p>' +
                                   '</div>' +
                                   '</div>' +
                                   '<div class="flex items-center gap-1 shrink-0">' +
                                   '<a href="' + dlUrl + '" download target="_blank" class="p-1.5 rounded-lg border border-outline-variant bg-surface hover:bg-primary hover:text-white text-primary transition-colors" title="Download ' + m.title.replace(/'/g, "\\'") + '">' +
                                   '<span class="material-symbols-outlined text-[16px]">download</span>' +
                                   '</a>' +
                                   '</div>' +
                                   '</div>';
                });

                html += '<div class="course-card bg-surface-container-lowest ' + cardBorder + ' rounded-2xl p-6 flex flex-col justify-between">' +
                        '<div>' +
                        '<div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-outline-variant/60">' +
                        '<span class="px-2.5 py-1 rounded-lg bg-primary-container text-on-primary font-mono text-xs font-bold">' + c.code + '</span>' +
                        '<span class="text-xs font-mono text-on-surface-variant font-medium">' + c.schedule + '</span>' +
                        '</div>' +

                        livePill +

                        '<h3 class="text-base font-bold text-primary leading-snug">' + c.title + '</h3>' +
                        '<p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> ' + c.instructor + '</p>' +
                        '<p class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">meeting_room</span> ' + c.room + '</p>' +
                        
                        '<div class="mt-4">' +
                        '<div class="flex items-center justify-between text-xs font-mono mb-1">' +
                        '<span class="text-on-surface-variant">Syllabus Progress</span>' +
                        '<span class="font-bold text-primary">' + c.progress + '%</span>' +
                        '</div>' +
                        '<div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">' +
                        '<div class="bg-primary h-full rounded-full" style="width: ' + c.progress + '%"></div>' +
                        '</div>' +
                        '</div>' +

                        '<div class="mt-5 space-y-2">' +
                        '<p class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant/70">Learning Modules & Resources (' + (c.modules || []).length + '):</p>' +
                        modulesHtml +
                        '</div>' +
                        '</div>' +

                        '<div class="mt-5 pt-3 border-t border-outline-variant/50 flex items-center justify-between text-xs">' +
                        '<span class="text-on-surface-variant font-mono text-[11px]">' + (c.assignments || []).length + ' Assignment(s)</span>' +
                        '<button type="button" onclick="switchElmsTab(\'assignments\')" class="text-primary font-bold hover:underline inline-flex items-center gap-1 cursor-pointer">' +
                        'View Tasks →' +
                        '</button>' +
                        '</div>' +
                        '</div>';
            });

            container.innerHTML = html;
        }

        function renderAssignments(courses) {
            var list = document.getElementById('assignments-list');
            var html = '';

            courses.forEach(function (c) {
                (c.assignments || []).forEach(function (asg) {
                    var statusBadge = '';
                    if (asg.status === 'Graded') {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-status-success/15 text-status-success">Graded · ' + (asg.score || 'Recorded') + '</span>';
                    } else if (asg.status === 'Submitted') {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-blue-500/15 text-blue-700 dark:text-blue-300">Turned In · Pending Grade</span>';
                    } else {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300">Assigned · Pending Work</span>';
                    }

                    var remarksHtml = '';
                    if (asg.remarks) {
                        remarksHtml = '<div class="mt-2 p-2.5 rounded-xl bg-status-success/10 border border-status-success/30 text-xs text-status-success font-medium flex items-start gap-1.5">' +
                                      '<span class="material-symbols-outlined text-[16px] shrink-0 mt-0.5">verified</span>' +
                                      '<div><p class="font-bold">Faculty Feedback (' + asg.score + '):</p><p class="text-on-surface-variant text-[11px] mt-0.5">' + asg.remarks + '</p></div>' +
                                      '</div>';
                    }

                    var attachedFilesHtml = '';
                    var files = (asg.files && asg.files.length) ? asg.files : [];
                    if (files.length === 0 && asg.submitted_file_name) {
                        files = [{
                            name: asg.submitted_file_name,
                            size: asg.submitted_file_size || '',
                            type: ''
                        }];
                    }

                    if (files.length > 0) {
                        attachedFilesHtml = '<div class="mt-2.5 space-y-1.5">' +
                                           '<p class="text-[11px] font-semibold text-primary flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">attach_file</span> Submitted Work (' + files.length + ' file' + (files.length > 1 ? 's' : '') + '):</p>' +
                                           '<div class="flex flex-wrap gap-2">';
                        files.forEach(function(f, fIdx) {
                            var viewUrl = '/api/elms.php?action=view_submission&id=' + encodeURIComponent(asg.submission_id) + '&file_idx=' + fIdx + '&inline=1';
                            var dlUrl = '/api/elms.php?action=download_submission&id=' + encodeURIComponent(asg.submission_id) + '&file_idx=' + fIdx;
                            attachedFilesHtml += '<div class="px-2.5 py-1.5 rounded-xl bg-primary/10 border border-primary/25 flex items-center gap-2 text-xs hover:bg-primary/15 transition-colors">' +
                                                '<div onclick="openFilePreviewModal(\'' + viewUrl + '\', \'' + f.name.replace(/'/g, "\\'") + '\', \'' + (f.type || '') + '\', \'' + (f.size || '') + '\')" class="flex items-center gap-2 min-w-0 flex-1 cursor-pointer group" title="Click to view file">' +
                                                '<span class="material-symbols-outlined text-[15px] text-primary group-hover:scale-110 transition-transform">description</span>' +
                                                '<span class="font-bold text-primary group-hover:underline truncate max-w-[150px]" title="' + f.name.replace(/"/g, '&quot;') + '">' + f.name + '</span>' +
                                                (f.size ? '<span class="text-[10px] font-mono text-on-surface-variant">(' + f.size + ')</span>' : '') +
                                                '</div>' +
                                                '<div class="flex items-center gap-1 shrink-0">' +
                                                '<a href="' + dlUrl + '" download target="_blank" class="p-1 rounded-lg bg-surface hover:bg-surface-container text-on-surface text-[11px] border border-outline-variant flex items-center gap-0.5" title="Direct download">' +
                                                '<span class="material-symbols-outlined text-[13px]">download</span>' +
                                                '</a>' +
                                                '<button type="button" onclick="deleteSubmissionFile(\'' + encodeURIComponent(asg.submission_id) + '\', ' + fIdx + ', event)" class="p-1 rounded-lg hover:bg-red-500/20 text-red-500 cursor-pointer" title="Delete this proof file">' +
                                                '<span class="material-symbols-outlined text-[13px]">delete</span>' +
                                                '</button>' +
                                                '</div>' +
                                                '</div>';
                        });
                        attachedFilesHtml += '</div></div>';
                    }

                    var actionBtn = '';
                    if (asg.status === 'Graded') {
                        actionBtn = '<span class="px-3 py-1.5 rounded-xl border border-outline-variant text-xs font-mono font-semibold text-status-success">Evaluated</span>';
                    } else if (asg.status === 'Submitted') {
                        actionBtn = '<button type="button" onclick="openSubmissionModal(\'' + asg.id + '\')" class="px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface-container text-primary text-xs font-bold transition-all inline-flex items-center gap-1 cursor-pointer"><span class="material-symbols-outlined text-[14px]">edit</span> Resubmit</button>';
                    } else {
                        actionBtn = '<button type="button" onclick="openSubmissionModal(\'' + asg.id + '\')" class="px-4 py-2 rounded-xl bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-all inline-flex items-center gap-1.5 shadow-xs cursor-pointer"><span class="material-symbols-outlined text-[15px]">upload</span> Turn In</button>';
                    }

                    html += '<div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-container-low transition-colors">' +
                            '<div class="min-w-0 flex-1">' +
                            '<div class="flex items-center gap-2 mb-1">' +
                            '<span class="font-mono text-xs font-bold text-primary">' + c.code + '</span>' +
                            '<span>·</span>' +
                            '<span class="text-xs font-mono text-on-surface-variant">Due: ' + asg.due_date + '</span>' +
                            statusBadge +
                            '</div>' +
                            '<h4 class="text-sm font-bold text-on-surface">' + asg.title + '</h4>' +
                            '<p class="text-xs text-on-surface-variant mt-0.5">Total Points: ' + asg.points + ' pts' + (asg.instructions ? ' · ' + asg.instructions : '') + '</p>' +
                            attachedFilesHtml +
                            remarksHtml +
                            '</div>' +
                            '<div class="shrink-0 flex items-center">' +
                            actionBtn +
                            '</div>' +
                            '</div>';
                });
            });

            list.innerHTML = html;
        }

        // ─── MULTI-FILE ATTACHMENT & IN-BROWSER PREVIEW SYSTEM ───
        var STAGED_STUDENT_FILES = [];

        function onStudentFilesSelected(input) {
            if (!input.files || input.files.length === 0) return;
            for (var i = 0; i < input.files.length; i++) {
                STAGED_STUDENT_FILES.push(input.files[i]);
            }
            input.value = '';
            renderStagedStudentFiles();
        }

        function removeStagedStudentFile(index) {
            STAGED_STUDENT_FILES.splice(index, 1);
            renderStagedStudentFiles();
        }

        function clearStudentFiles() {
            STAGED_STUDENT_FILES = [];
            var input = document.getElementById('sub-file-input');
            if (input) input.value = '';
            renderStagedStudentFiles();
        }

        function renderStagedStudentFiles() {
            var container = document.getElementById('sub-staged-files-list');
            var dropzone = document.getElementById('sub-dropzone');
            if (!container) return;

            if (STAGED_STUDENT_FILES.length === 0) {
                container.innerHTML = '';
                if (dropzone) dropzone.classList.remove('border-primary');
                return;
            }

            if (dropzone) dropzone.classList.add('border-primary');

            var html = '';
            STAGED_STUDENT_FILES.forEach(function(f, idx) {
                var sz = (f.size >= 1048576) ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                var ext = f.name.split('.').pop().toLowerCase();
                var icon = 'description';
                if (['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(ext)) icon = 'image';
                else if (ext === 'pdf') icon = 'picture_as_pdf';
                else if (['zip', 'rar'].includes(ext)) icon = 'folder_zip';
                else if (['txt', 'sql', 'json', 'js', 'py'].includes(ext)) icon = 'code';

                html += '<div class="p-2.5 rounded-xl bg-primary/10 border border-primary/25 flex items-center justify-between text-xs gap-2 hover:bg-primary/15 transition-colors">' +
                        '<div onclick="previewStagedFile(' + idx + ')" class="flex items-center gap-2 truncate min-w-0 flex-1 cursor-pointer group" title="Click to preview file">' +
                        '<span class="material-symbols-outlined text-primary text-[18px] shrink-0 group-hover:scale-110 transition-transform">' + icon + '</span>' +
                        '<div class="truncate">' +
                        '<p class="font-bold text-primary group-hover:underline truncate">' + f.name + '</p>' +
                        '<p class="text-[10px] font-mono text-on-surface-variant">Proof #' + (idx + 1) + ' · ' + sz + '</p>' +
                        '</div>' +
                        '</div>' +
                        '<div class="flex items-center gap-1.5 shrink-0">' +
                        '<button type="button" onclick="removeStagedStudentFile(' + idx + ')" class="p-1 rounded-lg hover:bg-red-500/20 text-red-500 cursor-pointer" title="Remove proof">' +
                        '<span class="material-symbols-outlined text-[16px]">close</span>' +
                        '</button>' +
                        '</div>' +
                        '</div>';
            });

            container.innerHTML = html;
        }

        function previewStagedFile(idx) {
            var f = STAGED_STUDENT_FILES[idx];
            if (!f) return;
            var blobUrl = URL.createObjectURL(f);
            var sz = (f.size >= 1048576) ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
            openFilePreviewModal(blobUrl, f.name, f.type, sz);
        }

        function findCourseAndAssignment(asgId) {
            for (var i = 0; i < COURSES_CACHE.length; i++) {
                var c = COURSES_CACHE[i];
                if (c.assignments) {
                    for (var j = 0; j < c.assignments.length; j++) {
                        if (String(c.assignments[j].id) === String(asgId)) {
                            return { course: c, assignment: c.assignments[j] };
                        }
                    }
                }
            }
            return null;
        }

        function openSubmissionModal(asgId, title, course, instructions, existingFile, subId, existingLink, existingTime) {
            var modal = document.getElementById('submission-modal');
            var found = findCourseAndAssignment(asgId);
            var asg = found ? found.assignment : null;
            var c = found ? found.course : null;

            title = title || (asg ? asg.title : 'Assignment Submission');
            course = course || (c ? c.code : 'Course');
            instructions = instructions || (asg ? asg.instructions : '');
            existingLink = existingLink || (asg ? asg.submitted_link : '');
            subId = subId || (asg ? asg.submission_id : '');
            existingTime = existingTime || (asg ? asg.submitted_at : '');

            document.getElementById('sub-asg-id').value = asgId;
            document.getElementById('sub-asg-title').textContent = title;
            document.getElementById('sub-asg-instructions').textContent = instructions || 'Follow professor instructions and submit clear proof of coursework.';
            document.getElementById('sub-course-label').textContent = course;
            document.getElementById('sub-link').value = existingLink || '';
            document.getElementById('sub-notes').value = '';
            clearStudentFiles();

            // Handle previous submission files
            var existingCard = document.getElementById('sub-existing-card');
            var filesList = document.getElementById('sub-existing-files-list');
            var timeSpan = document.getElementById('sub-existing-time');

            var prevFiles = (asg && asg.files && asg.files.length) ? asg.files : [];
            if (prevFiles.length === 0 && (existingFile || (asg && asg.submitted_file_name))) {
                var fn = existingFile || asg.submitted_file_name;
                var fsz = asg ? asg.submitted_file_size : '';
                prevFiles = [{ name: fn, size: fsz, type: '' }];
            }

            if (prevFiles.length > 0) {
                existingCard.classList.remove('hidden');
                timeSpan.textContent = existingTime || 'Recorded';
                
                var prevHtml = '';
                prevFiles.forEach(function(f, idx) {
                    var viewUrl = '/api/elms.php?action=view_submission&id=' + encodeURIComponent(subId) + '&file_idx=' + idx + '&inline=1';
                    var dlUrl = '/api/elms.php?action=download_submission&id=' + encodeURIComponent(subId) + '&file_idx=' + idx;
                    prevHtml += '<div class="p-2.5 rounded-xl bg-surface border border-outline-variant/60 flex items-center justify-between text-xs gap-2 hover:bg-surface-container transition-colors">' +
                                '<div onclick="openFilePreviewModal(\'' + viewUrl + '\', \'' + f.name.replace(/'/g, "\\'") + '\', \'' + (f.type || '') + '\', \'' + (f.size || '') + '\')" class="flex items-center gap-2 truncate min-w-0 flex-1 cursor-pointer group" title="Click to view file">' +
                                '<span class="material-symbols-outlined text-primary text-[18px] shrink-0 group-hover:scale-110 transition-transform">task</span>' +
                                '<div class="truncate">' +
                                '<p class="font-bold text-primary group-hover:underline truncate">' + f.name + '</p>' +
                                '<p class="text-[10px] font-mono text-on-surface-variant">Proof #' + (idx + 1) + (f.size ? ' · ' + f.size : '') + '</p>' +
                                '</div>' +
                                '</div>' +
                                '<div class="flex items-center gap-1.5 shrink-0">' +
                                '<a href="' + dlUrl + '" download target="_blank" class="p-1 rounded-lg border border-outline-variant bg-surface hover:bg-surface-container text-on-surface text-[11px] flex items-center gap-1" title="Direct download">' +
                                '<span class="material-symbols-outlined text-[14px]">download</span>' +
                                '</a>' +
                                '<button type="button" onclick="deleteSubmissionFile(\'' + encodeURIComponent(subId) + '\', ' + idx + ', event)" class="p-1 rounded-lg border border-red-500/20 bg-surface hover:bg-red-500 hover:text-white text-red-500 transition-colors cursor-pointer" title="Delete this proof file">' +
                                '<span class="material-symbols-outlined text-[14px]">delete</span>' +
                                '</button>' +
                                '</div>' +
                                '</div>';
                });
                filesList.innerHTML = prevHtml;

                document.getElementById('sub-submit-btn').innerHTML = '<span class="material-symbols-outlined text-[16px]">sync</span> Update / Resubmit Work';
            } else {
                existingCard.classList.add('hidden');
                filesList.innerHTML = '';
                document.getElementById('sub-submit-btn').innerHTML = '<span class="material-symbols-outlined text-[16px]">check_circle</span> Turn In Assignment';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSubmissionModal() {
            var modal = document.getElementById('submission-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function deleteSubmissionProof(subId, asgTitle, event) {
            if (event) event.stopPropagation();
            if (!confirm('Delete ALL proof files for "' + asgTitle + '"? This will clear your entire submission.')) return;
            try {
                var res = await fetch('/api/elms.php?action=delete_submission', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: decodeURIComponent(subId) })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to delete submission');
                if (window.notify) {
                    window.notify('Proof deleted successfully.', 'info');
                } else {
                    alert('Proof deleted successfully.');
                }
                closeSubmissionModal();
                loadElmsData(true);
            } catch (err) {
                alert('Error deleting proof: ' + err.message);
            }
        }

        async function deleteSubmissionFile(subId, fileIdx, event) {
            if (event) event.stopPropagation();
            if (!confirm('Delete this proof file (#' + (fileIdx + 1) + ')? This cannot be undone.')) return;
            try {
                var res = await fetch('/api/elms.php?action=delete_proof', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: decodeURIComponent(subId), file_idx: fileIdx })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to delete proof file');
                if (window.notify) {
                    window.notify(data.message || 'Proof file removed.', 'info');
                } else {
                    alert(data.message || 'Proof file removed.');
                }
                closeSubmissionModal();
                loadElmsData(true);
            } catch (err) {
                alert('Error deleting proof file: ' + err.message);
            }
        }

        async function handleAssignmentSubmit(e) {
            e.preventDefault();
            var asgId = document.getElementById('sub-asg-id').value;
            var link = document.getElementById('sub-link').value.trim();
            var notes = document.getElementById('sub-notes').value.trim();
            var btn = document.getElementById('sub-submit-btn');

            if (STAGED_STUDENT_FILES.length === 0 && !link && document.getElementById('sub-existing-card').classList.contains('hidden')) {
                alert('Please attach your assignment file(s) (e.g. Proof 1, Proof 2) or provide a submission link.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Turning in...';

            try {
                var formData = new FormData();
                formData.append('assignment_id', asgId);
                formData.append('link', link);
                formData.append('notes', notes);
                STAGED_STUDENT_FILES.forEach(function(file) {
                    formData.append('submission_files[]', file);
                });
                if (STAGED_STUDENT_FILES.length > 0) {
                    formData.append('submission_file', STAGED_STUDENT_FILES[0]);
                }

                var res = await fetch('/api/elms.php?action=submit_assignment', {
                    method: 'POST',
                    body: formData
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Submission failed');

                closeSubmissionModal();
                if (window.notify) {
                    window.notify('Assignment turned in successfully to your instructor!', 'success');
                } else {
                    alert('Assignment turned in successfully to your instructor!');
                }

                loadElmsData(true);
            } catch (err) {
                alert('Error submitting assignment: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">check_circle</span> Turn In Assignment';
            }
        }

        // ─── IN-BROWSER INTERACTIVE FILE PREVIEW MODAL ───
        function openFilePreviewModal(fileUrl, fileName, fileType, fileSize) {
            fileName = fileName || 'File Document';
            fileType = (fileType || '').toLowerCase();
            fileSize = fileSize || '';

            var modal = document.getElementById('file-preview-modal');
            var titleEl = document.getElementById('preview-modal-title');
            var badgeEl = document.getElementById('preview-modal-badge');
            var sizeEl = document.getElementById('preview-modal-size');
            var iconEl = document.getElementById('preview-modal-icon');
            var openTabBtn = document.getElementById('preview-modal-open-newtab');
            var dlBtn = document.getElementById('preview-modal-download-btn');
            var bodyEl = document.getElementById('preview-modal-body');

            titleEl.textContent = fileName;
            sizeEl.textContent = fileSize ? '(' + fileSize + ')' : '';
            openTabBtn.href = fileUrl;

            var dlUrl = fileUrl.replace(/([?&])(inline|view)=[^&]*/g, '');
            if (dlUrl.indexOf('?') === -1) dlUrl += '?download=1';
            else dlUrl += '&download=1';
            dlBtn.href = dlUrl;
            dlBtn.setAttribute('download', fileName);

            var ext = fileName.split('.').pop().toLowerCase();
            badgeEl.textContent = (ext || fileType || 'FILE').toUpperCase();

            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
                iconEl.textContent = 'image';
            } else if (ext === 'pdf') {
                iconEl.textContent = 'picture_as_pdf';
            } else if (['txt', 'sql', 'json', 'csv', 'js', 'py', 'php', 'html', 'css'].includes(ext)) {
                iconEl.textContent = 'code';
            } else {
                iconEl.textContent = 'description';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
                bodyEl.innerHTML = '<div class="w-full h-full p-4 sm:p-8 flex items-center justify-center bg-slate-950/20 overflow-auto">' +
                                   '<img src="' + fileUrl + '" alt="' + fileName.replace(/"/g, '&quot;') + '" class="max-h-full max-w-full rounded-xl object-contain shadow-md border border-outline-variant/30">' +
                                   '</div>';
            } else if (ext === 'pdf') {
                bodyEl.innerHTML = '<iframe src="' + fileUrl + '#toolbar=1&navpanes=0" class="w-full h-full border-0 bg-surface"></iframe>';
            } else if (['txt', 'sql', 'json', 'csv', 'js', 'py', 'php', 'html', 'css'].includes(ext)) {
                bodyEl.innerHTML = '<div class="w-full h-full p-6 overflow-auto bg-surface-container-lowest font-mono text-xs text-on-surface flex items-center justify-center">' +
                                   '<span class="material-symbols-outlined text-[20px] animate-spin mr-2">progress_activity</span> Reading file contents...' +
                                   '</div>';
                fetch(fileUrl)
                    .then(function(r) { return r.text(); })
                    .then(function(txt) {
                        var safeTxt = txt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        bodyEl.innerHTML = '<div class="w-full h-full p-6 overflow-auto bg-surface-container-lowest">' +
                                           '<pre class="font-mono text-xs text-on-surface leading-relaxed whitespace-pre-wrap select-text">' + safeTxt + '</pre>' +
                                           '</div>';
                    })
                    .catch(function(err) {
                        bodyEl.innerHTML = '<div class="p-8 text-center text-error">Failed to load text preview: ' + err.message + '</div>';
                    });
            } else {
                var fullFileUrl = window.location.origin + fileUrl;
                bodyEl.innerHTML = '<div class="w-full h-full flex flex-col items-center justify-center p-8 text-center space-y-4">' +
                                   '<div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto">' +
                                   '<span class="material-symbols-outlined text-[36px]">file_present</span>' +
                                   '</div>' +
                                   '<div>' +
                                   '<h4 class="text-base font-bold text-primary">' + fileName + '</h4>' +
                                   '<p class="text-xs text-on-surface-variant max-w-md mx-auto mt-1">This document format (' + ext.toUpperCase() + ') can be inspected via Google Docs Online Viewer or downloaded directly.</p>' +
                                   '</div>' +
                                   '<div class="flex flex-wrap items-center justify-center gap-3 pt-2">' +
                                   '<a href="https://docs.google.com/viewer?url=' + encodeURIComponent(fullFileUrl) + '&embedded=true" target="_blank" class="px-4 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold inline-flex items-center gap-1.5 shadow-sm">' +
                                   '<span class="material-symbols-outlined text-[16px]">visibility</span> Open Google Docs Viewer' +
                                   '</a>' +
                                   '<a href="' + dlUrl + '" download target="_blank" class="px-4 py-2 rounded-xl border border-outline-variant hover:bg-surface-container text-xs font-semibold text-on-surface inline-flex items-center gap-1.5">' +
                                   '<span class="material-symbols-outlined text-[16px]">download</span> Direct Download (' + (fileSize || 'File') + ')' +
                                   '</a>' +
                                   '</div>' +
                                   '</div>';
            }
        }

        function closeFilePreviewModal() {
            var modal = document.getElementById('file-preview-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('preview-modal-body').innerHTML = '';
        }

        // ─── LIVE VIRTUAL CLASSROOM & VERIFIED CHECK-IN HANDLERS ───
        var CURRENT_STUDENT_LIVE_SESSION = null;

        async function openStudentLiveCenter(code, roomId) {
            try {
                var res = await fetch('/api/elms.php?action=join_live_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ course_code: code, room_id: roomId })
                });
                var data = await res.json();
                if (!data.success) {
                    alert(data.error || 'Unable to join virtual classroom.');
                    return;
                }

                CURRENT_STUDENT_LIVE_SESSION = data;

                document.getElementById('student-live-title').textContent = data.course_code + ' · ' + data.course_title;
                document.getElementById('student-live-sub').textContent = 'Section ' + data.section + ' · Verified Attendance Hub';
                document.getElementById('student-live-code').textContent = data.course_code;
                document.getElementById('student-live-section').textContent = 'Section ' + data.section;
                document.getElementById('student-live-instructor').textContent = 'Instructor: ' + (data.instructor || 'Faculty');
                document.getElementById('student-live-topic').textContent = data.topic || 'Synchronous Lecture';
                document.getElementById('student-live-agenda').textContent = data.agenda || 'Official synchronous lecture and real-time electronic attendance verification.';

                var meetBtn = document.getElementById('student-join-meet-btn');
                var codeTextEl = document.getElementById('student-meet-code-text');
                var acctPill = document.getElementById('student-meet-account-pill');

                if (codeTextEl) codeTextEl.textContent = data.room_id || ('NPC-' + data.course_code);
                if (acctPill) acctPill.textContent = 'PlugNmeet Active';
                if (meetBtn) {
                    meetBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">co_present</span> Enter Live Classroom (PlugNmeet · Auto-Detect) ↗';
                    meetBtn.onclick = function(e) { startPlugNmeetPresenceSession(e); };
                }

                // Check attendance lock
                var checkinBtn = document.getElementById('student-checkin-btn');
                var windowLabel = document.getElementById('student-checkin-window-label');
                var badge = document.getElementById('student-checkin-badge');

                if (data.is_attendance_locked) {
                    checkinBtn.disabled = true;
                    checkinBtn.className = 'flex-1 py-3 rounded-xl bg-surface-container text-on-surface-variant font-bold text-xs cursor-not-allowed opacity-60 inline-flex items-center justify-center gap-1.5';
                    checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">lock</span> Check-in Locked by Instructor';
                    windowLabel.textContent = 'Status: Locked';
                    badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-error/15 text-error';
                    badge.textContent = 'Locked';
                } else {
                    checkinBtn.disabled = false;
                    checkinBtn.className = 'flex-1 py-3 rounded-xl bg-status-success text-white font-bold text-xs hover:opacity-90 transition-opacity shadow-md inline-flex items-center justify-center gap-1.5 cursor-pointer';
                    checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">verified</span> Check In for Attendance';
                    windowLabel.textContent = 'Status: Open';
                    badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-status-success/15 text-status-success';
                    badge.textContent = 'Open';
                }

                // Check if already checked in
                if (data.session_code) {
                    fetch('/api/student.php?action=get_attendance_receipt&session_code=' + encodeURIComponent(data.session_code))
                        .then(function (r) { return r.json(); })
                        .then(function (rc) {
                            if (rc.success && rc.receipt) {
                                checkinBtn.disabled = false;
                                checkinBtn.className = 'flex-1 py-3 rounded-xl bg-primary text-on-primary font-bold text-xs hover:opacity-90 transition-opacity shadow-md inline-flex items-center justify-center gap-1.5 cursor-pointer';
                                checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">receipt_long</span> View My Attendance Receipt (' + rc.receipt.status.toUpperCase() + ')';
                                checkinBtn.onclick = function () { openAttendanceReceiptModal(rc.receipt); };
                                badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-status-success/15 text-status-success';
                                badge.textContent = 'Verified (' + rc.receipt.status + ')';
                            }
                        }).catch(function () {});
                }

                var modal = document.getElementById('student-live-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                // If presence session was already started for this room, maintain HUD
                if (STUDENT_PRESENCE_IS_ONLINE) {
                    renderStudentPresenceHudState();
                } else {
                    var launcher = document.getElementById('student-meet-launcher');
                    var hud = document.getElementById('student-presence-hud');
                    if (launcher) launcher.classList.remove('hidden');
                    if (hud) hud.classList.add('hidden');
                }
            } catch (err) {
                alert('Join error: ' + err.message);
            }
        }

        function closeStudentLiveModal() {
            var modal = document.getElementById('student-live-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleLiveClassEndedByInstructor(courseInfo) {
            var courseCode = (courseInfo && (courseInfo.code || courseInfo.course_code)) || '';
            var courseTitle = (courseInfo && (courseInfo.title || courseInfo.course_title)) || 'Online Class';
            var finalDurationHms = formatDurationHms(STUDENT_PRESENCE_SECONDS);

            // 1. Stop all intervals
            if (STUDENT_PRESENCE_HEARTBEAT_INTERVAL) {
                clearInterval(STUDENT_PRESENCE_HEARTBEAT_INTERVAL);
                STUDENT_PRESENCE_HEARTBEAT_INTERVAL = null;
            }
            if (STUDENT_PRESENCE_TICKER_INTERVAL) {
                clearInterval(STUDENT_PRESENCE_TICKER_INTERVAL);
                STUDENT_PRESENCE_TICKER_INTERVAL = null;
            }
            if (STUDENT_MEET_WINDOW_WATCHER) {
                clearInterval(STUDENT_MEET_WINDOW_WATCHER);
                STUDENT_MEET_WINDOW_WATCHER = null;
            }

            // 2. Close Live Classroom window if open
            if (STUDENT_MEET_WINDOW && !STUDENT_MEET_WINDOW.closed) {
                try {
                    STUDENT_MEET_WINDOW.close();
                } catch (e) {
                    console.warn('Could not auto-close student live room window:', e);
                }
                STUDENT_MEET_WINDOW = null;
            }

            // 3. Reset presence state
            STUDENT_PRESENCE_IS_ONLINE = false;
            CURRENT_STUDENT_LIVE_SESSION = null;

            // 4. Close the student live modal
            closeStudentLiveModal();

            // 5. Hide live banner immediately
            var banner = document.getElementById('live-class-alert-banner');
            if (banner) {
                banner.classList.add('hidden');
                banner.innerHTML = '';
            }

            // 6. Play class-ended sound
            try {
                var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(659.25, audioCtx.currentTime);
                osc.frequency.setValueAtTime(523.25, audioCtx.currentTime + 0.15);
                osc.frequency.setValueAtTime(392.00, audioCtx.currentTime + 0.30);
                gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.61);
            } catch (e) {}

            // 7. Notification & alert
            var msg = 'The live class for ' + (courseCode || 'Live Class') + ' has concluded. Recorded attendance duration: ' + finalDurationHms + '.';
            if (window.notify) {
                window.notify('📢 ' + msg, 'info');
            }

            if (window.npcAlert) {
                window.npcAlert({
                    title: '📢 Class Concluded by Instructor',
                    message: 'Your instructor has ended the live session for ' + courseCode + ' (' + courseTitle + ').\n\n' +
                             '⏱️ Recorded Attendance Duration: ' + finalDurationHms + '\n\n' +
                             'Your electronic attendance record has been securely saved to NPC ELMS.',
                    type: 'info'
                });
            }
        }

        // ─── PLUGNMEET PRESENCE, DURATION TIMER & RECONNECT ENGINE ───
        var STUDENT_PRESENCE_HEARTBEAT_INTERVAL = null;
        var STUDENT_PRESENCE_TICKER_INTERVAL = null;
        var STUDENT_PRESENCE_SECONDS = 0;
        var STUDENT_PRESENCE_IS_ONLINE = false;
        var STUDENT_PRESENCE_LEAVE_COUNT = 0;
        var STUDENT_MEET_WINDOW = null;
        var STUDENT_MEET_WINDOW_WATCHER = null;

        function formatDurationHms(totalSecs) {
            var s = Math.max(0, parseInt(totalSecs, 10) || 0);
            var hrs = Math.floor(s / 3600);
            var mins = Math.floor((s % 3600) / 60);
            var secs = s % 60;
            return (hrs < 10 ? '0' : '') + hrs + ':' + (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        async function handleLiveRoomClosed() {
            if (!STUDENT_PRESENCE_IS_ONLINE || !CURRENT_STUDENT_LIVE_SESSION) return;
            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            var stNum = '<?= htmlspecialchars($studentNumber, ENT_QUOTES) ?>';
            try {
                await fetch('/api/elms.php?action=meeting_presence_leave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_code: sc, student_number: stNum })
                });
                STUDENT_PRESENCE_IS_ONLINE = false;
                STUDENT_PRESENCE_LEAVE_COUNT++;
                renderStudentPresenceHudState();
                var statusLabel = document.getElementById('hud-online-status');
                if (statusLabel) statusLabel.textContent = 'Presence Paused (Left Classroom)';
                if (window.notify) {
                    window.notify('Classroom window was closed. Live attendance tracking paused.', 'warning');
                }
            } catch (e) {}
        }

        function copyPlugNmeetLinkToClipboard() {
            if (!CURRENT_STUDENT_LIVE_SESSION) return;
            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            var cc = CURRENT_STUDENT_LIVE_SESSION.course_code;
            var roomId = CURRENT_STUDENT_LIVE_SESSION.room_id || ('NPC-' + cc);
            var roomUrl = window.location.origin + '/live_room.php?session_code=' + encodeURIComponent(sc) + '&course_code=' + encodeURIComponent(cc) + '&room_id=' + encodeURIComponent(roomId);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(roomUrl).then(function () {
                    if (window.notify) window.notify('PlugNmeet classroom link copied to clipboard!', 'success');
                    else alert('Classroom link copied to clipboard!');
                });
            } else {
                prompt('Classroom Link:', roomUrl);
            }
        }

        async function startPlugNmeetPresenceSession(e) {
            if (e && e.preventDefault) e.preventDefault();
            if (!CURRENT_STUDENT_LIVE_SESSION || !CURRENT_STUDENT_LIVE_SESSION.session_code) {
                alert('No active live session found.');
                return;
            }

            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            var cc = CURRENT_STUDENT_LIVE_SESSION.course_code;
            var roomId = CURRENT_STUDENT_LIVE_SESSION.room_id || ('NPC-' + cc);
            var roomUrl = '/live_room.php?session_code=' + encodeURIComponent(sc) + '&course_code=' + encodeURIComponent(cc) + '&room_id=' + encodeURIComponent(roomId);

            try {
                var res = await fetch('/api/elms.php?action=meeting_presence_join', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_code: sc, course_code: cc })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to initialize presence');

                STUDENT_PRESENCE_IS_ONLINE = true;
                STUDENT_PRESENCE_SECONDS = data.duration_seconds || 0;
                STUDENT_PRESENCE_LEAVE_COUNT = data.leave_count || 0;

                // Open Live Room
                STUDENT_MEET_WINDOW = window.open(roomUrl, 'NPC_LiveRoom_' + sc, 'width=1280,height=800,menubar=no,toolbar=no');
                if (!STUDENT_MEET_WINDOW) {
                    window.location.href = roomUrl;
                    return;
                }

                // Switch UI to HUD
                renderStudentPresenceHudState();

                // Start local 1-second ticker
                if (STUDENT_PRESENCE_TICKER_INTERVAL) clearInterval(STUDENT_PRESENCE_TICKER_INTERVAL);
                STUDENT_PRESENCE_TICKER_INTERVAL = setInterval(function () {
                    if (STUDENT_PRESENCE_IS_ONLINE) {
                        STUDENT_PRESENCE_SECONDS++;
                        var timerEl = document.getElementById('hud-duration-timer');
                        if (timerEl) timerEl.textContent = formatDurationHms(STUDENT_PRESENCE_SECONDS);
                    }
                }, 1000);

                // Start 4-second server heartbeat
                if (STUDENT_PRESENCE_HEARTBEAT_INTERVAL) clearInterval(STUDENT_PRESENCE_HEARTBEAT_INTERVAL);
                STUDENT_PRESENCE_HEARTBEAT_INTERVAL = setInterval(sendPresenceHeartbeat, 4000);

                // Window close watcher
                if (STUDENT_MEET_WINDOW_WATCHER) clearInterval(STUDENT_MEET_WINDOW_WATCHER);
                STUDENT_MEET_WINDOW_WATCHER = setInterval(function () {
                    try {
                        if (STUDENT_MEET_WINDOW && STUDENT_MEET_WINDOW.closed) {
                            clearInterval(STUDENT_MEET_WINDOW_WATCHER);
                            STUDENT_MEET_WINDOW = null;
                            handleLiveRoomClosed();
                        }
                    } catch (e) {}
                }, 1500);

                // Silent attendance checkin
                submitLiveCheckinSilently();

                if (window.notify) {
                    window.notify('Virtual Classroom active! Live attendance tracking is running.', 'success');
                }
            } catch (err) {
                alert('Presence error: ' + err.message);
            }
        }

        async function sendPresenceHeartbeat() {
            if (!STUDENT_PRESENCE_IS_ONLINE || !CURRENT_STUDENT_LIVE_SESSION) return;
            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            try {
                var res = await fetch('/api/elms.php?action=meeting_presence_heartbeat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_code: sc })
                });
                var data = await res.json();
                if (data.success) {
                    STUDENT_PRESENCE_SECONDS = data.duration_seconds;
                    STUDENT_PRESENCE_LEAVE_COUNT = data.leave_count;
                    var timerEl = document.getElementById('hud-duration-timer');
                    if (timerEl) timerEl.textContent = formatDurationHms(STUDENT_PRESENCE_SECONDS);
                    updateHudLeaveBadge();
                }
            } catch (e) {}
        }

        function renderStudentPresenceHudState() {
            var launcher = document.getElementById('student-meet-launcher');
            var hud = document.getElementById('student-presence-hud');
            var statusLabel = document.getElementById('hud-online-status');
            var dot = document.getElementById('hud-pulsing-dot');
            var timerEl = document.getElementById('hud-duration-timer');
            var leaveBtn = document.getElementById('hud-leave-toggle-btn');

            if (launcher) launcher.classList.add('hidden');
            if (hud) hud.classList.remove('hidden');

            if (STUDENT_PRESENCE_IS_ONLINE) {
                statusLabel.textContent = 'Active in PlugNmeet Classroom';
                dot.className = 'relative flex h-3 w-3';
                dot.innerHTML = '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-status-success opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-status-success"></span>';
                leaveBtn.innerHTML = '<span class="material-symbols-outlined text-[15px]">logout</span> Leave Class';
                leaveBtn.className = 'px-3 py-2 rounded-xl bg-error/10 text-error hover:bg-error/20 text-xs font-bold transition-colors inline-flex items-center gap-1 cursor-pointer';
            } else {
                statusLabel.textContent = 'Presence Paused (Left Class)';
                dot.className = 'relative flex h-3 w-3';
                dot.innerHTML = '<span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400"></span>';
                leaveBtn.innerHTML = '<span class="material-symbols-outlined text-[15px]">login</span> Rejoin Class';
                leaveBtn.className = 'px-3 py-2 rounded-xl bg-status-success text-white hover:opacity-90 text-xs font-bold transition-opacity inline-flex items-center gap-1 cursor-pointer shadow-xs';
            }

            if (timerEl) timerEl.textContent = formatDurationHms(STUDENT_PRESENCE_SECONDS);
            updateHudLeaveBadge();
        }

        function updateHudLeaveBadge() {
            var badge = document.getElementById('hud-leave-badge');
            if (!badge) return;
            // Strict rule: Leave counter is strictly PROF-ONLY in real time
            if (STUDENT_PRESENCE_IS_ONLINE) {
                badge.className = 'px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-status-success/15 text-status-success border border-status-success/30 flex items-center gap-1';
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-status-success animate-pulse"></span> Attendance Active';
            } else {
                badge.className = 'px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-500/15 text-amber-600 border border-amber-500/30 flex items-center gap-1';
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Attendance Paused';
            }
        }

        function reopenPlugNmeetTab() {
            if (!CURRENT_STUDENT_LIVE_SESSION) return;
            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            var cc = CURRENT_STUDENT_LIVE_SESSION.course_code;
            var roomId = CURRENT_STUDENT_LIVE_SESSION.room_id || ('NPC-' + cc);
            var roomUrl = '/live_room.php?session_code=' + encodeURIComponent(sc) + '&course_code=' + encodeURIComponent(cc) + '&room_id=' + encodeURIComponent(roomId);
            STUDENT_MEET_WINDOW = window.open(roomUrl, 'NPC_LiveRoom_' + sc, 'width=1280,height=800');
            if (!STUDENT_MEET_WINDOW) window.location.href = roomUrl;
        }

        async function studentToggleLeavePresence() {
            if (!CURRENT_STUDENT_LIVE_SESSION) return;
            var sc = CURRENT_STUDENT_LIVE_SESSION.session_code;
            var stNum = '<?= htmlspecialchars($studentNumber, ENT_QUOTES) ?>';

            if (STUDENT_PRESENCE_IS_ONLINE) {
                if (!confirm('Are you sure you want to leave the online class?\n\nYour active timer will pause and your leave will be logged on the instructor roster.')) return;
                try {
                    await fetch('/api/elms.php?action=meeting_presence_leave', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ session_code: sc, student_number: stNum })
                    });
                    STUDENT_PRESENCE_IS_ONLINE = false;
                    STUDENT_PRESENCE_LEAVE_COUNT++;
                    if (STUDENT_MEET_WINDOW && !STUDENT_MEET_WINDOW.closed) {
                        try { STUDENT_MEET_WINDOW.close(); } catch(e) {}
                    }
                    renderStudentPresenceHudState();
                    if (window.notify) window.notify('You left the online class. Timer paused.', 'warning');
                } catch (e) {}
            } else {
                // Rejoin
                await startPlugNmeetPresenceSession();
            }
        }

        // Automatic Disconnect beacon on tab close
        window.addEventListener('beforeunload', function () {
            if (STUDENT_PRESENCE_IS_ONLINE && CURRENT_STUDENT_LIVE_SESSION && CURRENT_STUDENT_LIVE_SESSION.session_code) {
                var payload = JSON.stringify({
                    action: 'meeting_presence_leave',
                    session_code: CURRENT_STUDENT_LIVE_SESSION.session_code,
                    student_number: '<?= htmlspecialchars($studentNumber, ENT_QUOTES) ?>'
                });
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/api/elms.php?action=meeting_presence_leave', payload);
                }
            }
        });

        // Fast keepalive ping whenever student returns to the ELMS companion tab
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && STUDENT_PRESENCE_IS_ONLINE) {
                sendPresenceHeartbeat();
            }
        });

        async function submitLiveCheckinSilently() {
            if (!CURRENT_STUDENT_LIVE_SESSION || !CURRENT_STUDENT_LIVE_SESSION.session_code) return;
            try {
                var res = await fetch('/api/student.php?action=check_in_attendance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: CURRENT_STUDENT_LIVE_SESSION.session_code, method: 'live_portal' })
                });
                var data = await res.json();
                var checkinBtn = document.getElementById('student-checkin-btn');
                var badge = document.getElementById('student-checkin-badge');
                if (data.receipt || (data.success && data.receipt)) {
                    var rc = data.receipt;
                    if (checkinBtn) {
                        checkinBtn.disabled = false;
                        checkinBtn.className = 'flex-1 py-3 rounded-xl bg-primary text-on-primary font-bold text-xs hover:opacity-90 transition-opacity shadow-md inline-flex items-center justify-center gap-1.5 cursor-pointer';
                        checkinBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">receipt_long</span> View My Attendance Receipt (' + (rc.status || 'PRESENT').toUpperCase() + ')';
                        checkinBtn.onclick = function () { openAttendanceReceiptModal(rc); };
                    }
                    if (badge) {
                        badge.className = 'px-2.5 py-1 rounded-full font-mono text-[10px] font-bold uppercase bg-status-success/15 text-status-success';
                        badge.textContent = 'Verified (' + (rc.status || 'present') + ')';
                    }
                }
            } catch (e) {}
        }

        async function submitLiveCheckin() {
            if (!CURRENT_STUDENT_LIVE_SESSION || !CURRENT_STUDENT_LIVE_SESSION.session_code) {
                return alert('Session code not found for this live class.');
            }

            var btn = document.getElementById('student-checkin-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Verifying Attendance...';

            try {
                var res = await fetch('/api/student.php?action=check_in_attendance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: CURRENT_STUDENT_LIVE_SESSION.session_code,
                        method: 'live_portal'
                    })
                });
                var data = await res.json();

                if (data.duplicate && data.receipt) {
                    if (window.notify) window.notify('You are already checked in for this class!', 'info');
                    openAttendanceReceiptModal(data.receipt);
                    return;
                }

                if (!data.success) {
                    throw new Error(data.message || 'Attendance check-in failed.');
                }

                if (window.notify) {
                    window.notify('Attendance successfully recorded as ' + (data.status || 'present').toUpperCase() + '!', 'success');
                }

                if (data.receipt) {
                    openAttendanceReceiptModal(data.receipt);
                }
            } catch (err) {
                alert('Check-in error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">verified</span> Check In for Attendance';
            }
        }

        function openAttendanceReceiptModal(receipt) {
            if (!receipt) return;
            document.getElementById('receipt-ref-id').textContent = receipt.reference_id || 'REF-VERIFIED';
            document.getElementById('receipt-student-name').textContent = receipt.student_name || 'Student';
            document.getElementById('receipt-student-number').textContent = receipt.student_number || '—';
            document.getElementById('receipt-course-sec').textContent = (receipt.class_code || '') + ' · Section ' + (receipt.section || '');
            document.getElementById('receipt-instructor').textContent = receipt.instructor || 'Assigned Faculty';
            document.getElementById('receipt-timestamp').textContent = receipt.formatted_time || receipt.timestamp || new Date().toLocaleString();
            document.getElementById('receipt-method').textContent = receipt.verified_via || 'Live Portal Check-in';

            var st = (receipt.status || 'present').toLowerCase();
            var pill = document.getElementById('receipt-status-pill');
            if (st === 'present') {
                pill.className = 'px-3.5 py-1 rounded-full font-mono text-xs font-bold uppercase bg-status-success/20 text-status-success';
                pill.textContent = 'PRESENT · VERIFIED';
            } else if (st === 'late') {
                pill.className = 'px-3.5 py-1 rounded-full font-mono text-xs font-bold uppercase bg-status-warning/20 text-status-warning';
                pill.textContent = 'LATE · RECORDED';
            } else {
                pill.className = 'px-3.5 py-1 rounded-full font-mono text-xs font-bold uppercase bg-primary/20 text-primary';
                pill.textContent = st.toUpperCase() + ' · RECORDED';
            }

            var modal = document.getElementById('attendance-receipt-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAttendanceReceiptModal() {
            var modal = document.getElementById('attendance-receipt-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function printAttendanceReceipt() {
            window.print();
        }

        function openAttendanceIssueModal() {
            if (!CURRENT_STUDENT_LIVE_SESSION) return;
            document.getElementById('issue-course-code').value = CURRENT_STUDENT_LIVE_SESSION.course_code || '';
            document.getElementById('issue-session-code').value = CURRENT_STUDENT_LIVE_SESSION.session_code || '';
            document.getElementById('issue-reason').value = '';

            var modal = document.getElementById('attendance-issue-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('issue-reason').focus();
        }

        function closeAttendanceIssueModal() {
            var modal = document.getElementById('attendance-issue-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function handleAttendanceIssueSubmit(e) {
            e.preventDefault();
            var course = document.getElementById('issue-course-code').value;
            var reason = document.getElementById('issue-reason').value.trim();
            var btn = document.getElementById('issue-submit-btn');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Submitting...';

            try {
                var res = await fetch('/api/student.php?action=submit_excuse', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        class_code: course,
                        faculty_email: (CURRENT_STUDENT_LIVE_SESSION && CURRENT_STUDENT_LIVE_SESSION.instructor) ? (CURRENT_STUDENT_LIVE_SESSION.instructor.toLowerCase().replace(/[^a-z]/g, '') + '@npc.edu.ph') : 'faculty@npc.edu.ph',
                        absence_date: new Date().toISOString().slice(0, 10),
                        reason: reason
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.message || 'Submission failed');

                closeAttendanceIssueModal();
                if (window.notify) {
                    window.notify('Attendance issue report sent to your instructor.', 'success');
                } else {
                    alert('Attendance issue report sent to your instructor.');
                }
            } catch (err) {
                alert('Submission error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">send</span> Submit Report';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadElmsData(true).then(function() {
                var urlParams = new URLSearchParams(window.location.search);
                var autoJoin = urlParams.get('join_course') || urlParams.get('course');
                if (autoJoin && COURSES_CACHE && COURSES_CACHE.length) {
                    var target = COURSES_CACHE.find(function(c) {
                        return c.code.toLowerCase().replace(/[^a-z0-9]/g, '') === autoJoin.toLowerCase().replace(/[^a-z0-9]/g, '');
                    });
                    if (target) {
                        var roomId = target.live_session ? target.live_session.room_id : '';
                        openStudentLiveCenter(target.code, roomId);
                    }
                }
            });
            if (REALTIME_SYNC_INTERVAL) clearInterval(REALTIME_SYNC_INTERVAL);
            REALTIME_SYNC_INTERVAL = setInterval(function() {
                loadElmsData(false);
            }, 2500);
        });
    </script>
</body>
</html>
