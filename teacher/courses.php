<?php
/**
 * teacher/courses.php — Faculty ELMS Courses, Modules & Submissions Center
 * 
 * Part of NPC ELMS (Electronic Learning Management System)
 * Allows instructors to:
 *  - View assigned courses
 *  - Upload learning modules (slides, readings, syllabus)
 *  - Create & publish assignments/tasks
 *  - Review student submissions and encode grades with feedback
 */

require_once __DIR__ . '/../includes/auth.php';
require_teacher();

$teacherName = $_SESSION['name'] ?? 'Faculty Instructor';
$teacherEmail = $_SESSION['email'] ?? '';
$teacherInitial = strtoupper(substr($teacherName, 0, 1));
$csrfToken = getCsrfToken();

$PAGE_TITLE = 'LMS Courses & Submissions Hub · NPC LMS Faculty';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <?php include __DIR__ . '/../includes/_head.php'; ?>
    <style>
        .course-box { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .course-box:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(0, 23, 54, 0.08); }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <div class="flex min-h-screen w-full" id="app-root">
        <!-- Sidebar Navigation -->
        <?php $NPC_PORTAL = 'faculty'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">
            <!-- Topbar -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC LMS</span>
                    <div class="hidden lg:flex items-center gap-2 text-xs font-mono text-on-surface-variant">
                        <span>Faculty Portal</span>
                        <span>/</span>
                        <span class="text-primary font-bold">Courses & Modules Hub</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <button type="button" onclick="openUploadModal()" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl border border-primary/40 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold transition-all inline-flex items-center gap-1 cursor-pointer shrink-0">
                        <span class="material-symbols-outlined text-[16px]">upload_file</span> <span class="hidden sm:inline">+ Module</span>
                    </button>
                    <button type="button" onclick="openCreateAsgModal()" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all inline-flex items-center gap-1 shadow-xs cursor-pointer shrink-0">
                        <span class="material-symbols-outlined text-[16px]">add_task</span> <span class="hidden sm:inline">+ Assignment</span>
                    </button>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0">
                        <?= $teacherInitial ?>
                    </div>
                    <span class="text-sm font-semibold text-primary hidden md:inline">
                        <?= htmlspecialchars($teacherName) ?>
                    </span>
                </div>
            </header>

            <!-- Main Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto space-y-6 sm:space-y-8" id="canvas-container">
                
                <!-- LMS Faculty Banner -->
                <div class="rounded-3xl border border-outline-variant/80 p-6 md:p-8 bg-gradient-to-r from-primary-container/20 via-surface-container-low to-amber-400/10 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-primary text-on-primary shadow-xs">
                                    LMS Instructor Management
                                </span>
                                <span class="text-xs font-mono text-on-surface-variant font-semibold">
                                    1st Semester · AY 2026–2027
                                </span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-primary tracking-tight">
                                Assigned Courses, Modules & Submissions
                            </h1>
                            <p class="text-sm text-on-surface-variant mt-1 max-w-2xl">
                                Publish syllabus outlines, upload lecture slides, create homework/project assignments, and evaluate student coursework submissions in real time.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="/teacher/grades.php" class="px-4 py-2.5 rounded-xl border border-outline-variant hover:bg-surface-container text-primary font-bold text-xs transition-colors inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">grade</span> Official Gradebook
                            </a>
                            <a href="/teacher/attendance.php" class="px-4 py-2.5 rounded-xl bg-primary text-on-primary hover:opacity-90 font-bold text-xs transition-all inline-flex items-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span> Live Attendance
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Summary -->
                <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Active Classes</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-total-courses">4 Subjects</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">AIS 2A & Collegiate</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Published Modules</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-total-modules">8 Handouts</h3>
                        <p class="text-[11px] text-status-success font-medium mt-0.5">Live on Student Portals</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Active Tasks</span>
                        <h3 class="text-2xl font-bold text-primary mt-1" id="stat-total-assignments">5 Tasks</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">Across all classes</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant shadow-xs">
                        <span class="text-[11px] font-mono text-on-surface-variant uppercase tracking-wider font-semibold">Student Submissions</span>
                        <h3 class="text-2xl font-bold text-amber-500 mt-1" id="stat-total-subs">1 Recorded</h3>
                        <p class="text-[11px] text-on-surface-variant/80 mt-0.5">Ready for review</p>
                    </div>
                </section>

                <!-- Navigation Tabs: Courses / Modules vs Submissions Review -->
                <div class="flex items-center gap-3 border-b border-outline-variant/60 pb-1">
                    <button id="tab-btn-classes" onclick="switchFacultyTab('classes')" class="px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        <span>My Courses & Modules</span>
                    </button>
                    <button id="tab-btn-subs" onclick="switchFacultyTab('subs')" class="px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">rate_review</span>
                        <span>Student Submissions & Grading (<span id="subs-badge">1</span>)</span>
                    </button>
                </div>

                <!-- ─── TAB 1: Faculty Classes & Modules List ─── -->
                <div id="section-classes" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="faculty-courses-grid">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- ─── TAB 2: Student Submissions Review Table ─── -->
                <div id="section-subs" class="hidden space-y-6">
                    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-primary">Student Coursework Submissions</h3>
                                <p class="text-xs text-on-surface-variant">Review submitted links, verify work, and encode official scores & remarks.</p>
                            </div>
                            <span class="text-xs font-mono px-3 py-1 rounded-full bg-primary/10 text-primary font-bold">
                                Live Submissions
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-outline-variant/60 bg-surface-container-low/50 font-mono text-on-surface-variant uppercase text-[10px]">
                                        <th class="p-4">Student</th>
                                        <th class="p-4">Course</th>
                                        <th class="p-4">Assignment</th>
                                        <th class="p-4">Submitted Link</th>
                                        <th class="p-4">Date</th>
                                        <th class="p-4">Status / Score</th>
                                        <th class="p-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="submissions-table-body" class="divide-y divide-outline-variant/40">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ─── MODAL 1: Upload Learning Module ─── -->
    <div id="upload-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Upload Learning Module</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono">Publish handout/slides for students</p>
                    </div>
                </div>
                <button type="button" onclick="closeUploadModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="upload-module-form" class="space-y-4" onsubmit="handleModuleUpload(event)">
                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Target Course <span class="text-error">*</span></label>
                    <select id="mod-course" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" required>
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Module Title <span class="text-error">*</span></label>
                    <input type="text" id="mod-title" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Module 3: Internal Control Safeguards" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Brief Description / Topic Summary</label>
                    <textarea id="mod-desc" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Syllabus coverage, learning objectives, or reading guide..."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Select File to Upload <span class="text-error">*</span></label>
                    <div class="border-2 border-dashed border-outline-variant hover:border-primary/60 rounded-xl p-3.5 text-center bg-surface-container-low transition-colors cursor-pointer" onclick="document.getElementById('mod-file').click()">
                        <span class="material-symbols-outlined text-[26px] text-primary">upload_file</span>
                        <p class="text-xs font-bold text-primary mt-1" id="mod-file-name">Click or drag handout/slides here</p>
                        <p class="text-[10px] text-on-surface-variant font-mono mt-0.5">PDF, DOCX, PPTX, XLSX, Images, ZIP (Up to 50MB)</p>
                        <input type="file" id="mod-file" class="hidden" onchange="onModuleFileSelected(this)" accept=".pdf,.docx,.doc,.pptx,.ppt,.xlsx,.xls,.png,.jpg,.jpeg,.webp,.zip,.txt">
                    </div>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeUploadModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="upload-submit-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">cloud_upload</span> Publish to Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 2: Create Assignment ─── -->
    <div id="create-asg-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">add_task</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Create Course Assignment</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono">Publish a new task with deadline</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateAsgModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="create-asg-form" class="space-y-4" onsubmit="handleAssignmentCreate(event)">
                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Target Course <span class="text-error">*</span></label>
                    <select id="asg-course" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" required>
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Assignment Title <span class="text-error">*</span></label>
                    <input type="text" id="asg-title" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Case Analysis 2: Financial Fraud Safeguards" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Instructions & Guidelines</label>
                    <textarea id="asg-instructions" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Requirements, rubrics, and submission instructions..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Total Points</label>
                        <input type="number" id="asg-points" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" value="100" min="10" max="200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Deadline Date</label>
                        <input type="date" id="asg-due" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono" required>
                    </div>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeCreateAsgModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="create-asg-submit-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span> Publish Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 3: Grade Student Submission ─── -->
    <div id="grade-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">rate_review</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Grade Submission</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono" id="grade-student-label">Student Evaluation</p>
                    </div>
                </div>
                <button type="button" onclick="closeGradeModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="grade-form" class="space-y-4" onsubmit="handleGradeSubmit(event)">
                <input type="hidden" id="grade-sub-id" value="">

                <div class="p-3.5 bg-surface-container-low rounded-xl border border-outline-variant/50 space-y-2 text-xs">
                    <p><strong>Assignment:</strong> <span id="grade-asg-title">—</span></p>
                    <div id="grade-sub-file-container" class="hidden">
                        <p class="text-on-surface-variant mb-1.5 font-semibold">Submitted Files / Evidence:</p>
                        <div id="grade-sub-files-list" class="space-y-1.5">
                            <!-- Populated dynamically with preview and download buttons -->
                        </div>
                    </div>
                    <div id="grade-sub-link-container">
                        <p><strong>Submitted Link:</strong> <a id="grade-sub-link" href="#" target="_blank" class="text-primary font-bold underline">View Student Work ↗</a></p>
                    </div>
                    <p id="grade-sub-notes" class="text-on-surface-variant italic">Notes: —</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Official Score <span class="text-error">*</span></label>
                    <input type="text" id="grade-score" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-mono font-bold" placeholder="e.g. 95/100 or 48/50" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Instructor Feedback & Remarks</label>
                    <textarea id="grade-remarks" rows="3" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Commendations, corrections, rubrics breakdown..."></textarea>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeGradeModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="grade-submit-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Save & Send Grade
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 4: Start Live Virtual Class ─── -->
    <div id="start-live-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-lg w-full shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">video_camera_front</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-primary">Schedule &amp; Launch Live Class</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono">PlugNmeet WebRTC + Verified Institutional Attendance</p>
                    </div>
                </div>
                <button type="button" onclick="closeStartLiveModal()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="start-live-form" class="space-y-4" onsubmit="handleStartLiveSubmit(event)">
                <input type="hidden" id="live-course-code">
                <input type="hidden" id="live-course-id">
                <input type="hidden" id="live-course-section">

                <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/60">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold text-primary" id="live-modal-course-badge">DM103</span>
                        <span class="px-2 py-0.5 rounded-md bg-status-info/15 text-status-info font-mono text-[10px] font-bold" id="live-modal-section-badge">Section 2A</span>
                    </div>
                    <p class="text-xs font-semibold text-on-surface mt-1" id="live-modal-course-title">Accounting Information Systems</p>
                </div>

                <!-- Virtual Classroom Platform Engine (PlugNmeet Enforced) -->
                <input type="hidden" name="live-platform" value="plugnmeet">
                <div class="p-3.5 rounded-2xl border-2 border-emerald-500/40 bg-emerald-500/10 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">co_present</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-primary flex items-center gap-1.5">
                                PlugNmeet Virtual Classroom (Official)
                            </h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                                Unli Time ∞ · Real-Time Auto-Detect
                            </span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-0.5 leading-relaxed">
                            Zero cutoff, built-in whiteboard, screenshare &amp; automatic electronic presence detection for all enrolled students.
                        </p>
                    </div>
                </div>

                <!-- Session Duration Mode -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Session Duration Mode</label>
                        <select id="live-timer-mode" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-semibold" onchange="toggleDurationMinsField(this.value)">
                            <option value="unlimited" selected>∞ Unlimited Time (No Cutoff)</option>
                            <option value="timed">⏱️ Custom Duration Timer</option>
                        </select>
                    </div>
                    <div id="live-duration-mins-container" class="hidden">
                        <label class="block text-xs font-semibold text-primary mb-1">Duration (Minutes)</label>
                        <select id="live-duration-mins" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-semibold">
                            <option value="45">45 Minutes</option>
                            <option value="60" selected>60 Minutes (1 Hour)</option>
                            <option value="90">90 Minutes (1.5 Hours)</option>
                            <option value="120">120 Minutes (2 Hours)</option>
                            <option value="180">180 Minutes (3 Hours)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Lecture Topic <span class="text-error">*</span></label>
                    <input type="text" id="live-topic" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Enterprise ERP Safeguards & DFDs" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-primary mb-1">Session Agenda &amp; Instructions</label>
                    <textarea id="live-agenda" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Key topics to discuss, deliverables, or reading reminders..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Attendance Grace Period</label>
                        <select id="live-grace-period" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-semibold">
                            <option value="10">10 Minutes</option>
                            <option value="15" selected>15 Minutes (Standard)</option>
                            <option value="20">20 Minutes</option>
                            <option value="30">30 Minutes</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-primary mb-1">Target Section</label>
                        <input type="text" id="live-section-readonly" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface-container-low text-on-surface-variant font-mono cursor-not-allowed" readonly value="Section 2A">
                    </div>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeStartLiveModal()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="start-live-submit-btn" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-md cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">sensors</span> Start Class &amp; Control Panel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 5: ELMS Class Control Panel & Live Attendance Console ─── -->
    <div id="classroom-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md animate-fade-in" role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-5xl h-[92vh] flex flex-col shadow-2xl overflow-hidden relative">
            <!-- Header bar -->
            <div class="px-5 py-3.5 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-600/15 text-red-600 dark:text-red-400 font-mono text-xs font-bold animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                        LIVE CLASS ACTIVE
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-primary truncate max-w-xs sm:max-w-md" id="room-header-title">Virtual Class Room</h3>
                        <p class="text-[11px] font-mono text-on-surface-variant truncate" id="room-header-sub">Section Restricted · ELMS Verified Attendance</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a id="control-join-meet-btn" href="#" target="_blank" class="px-4 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold shadow-xs inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">co_present</span> Open Classroom Room ↗
                    </a>
                    <button type="button" onclick="closeClassroomModal()" class="px-3.5 py-2 rounded-xl border border-outline-variant hover:bg-surface-container text-on-surface text-xs font-semibold inline-flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">close</span> Minimize
                    </button>
                </div>
            </div>

            <!-- Main Control Panel Grid -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 bg-surface">
                <!-- Left Column: Session Meta & Quick Controls (4 cols) -->
                <div class="lg:col-span-4 space-y-4">
                    <!-- Session Card -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-sm space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-outline-variant/50">
                            <span class="text-xs font-mono font-bold text-on-surface-variant uppercase tracking-wider">Session Details</span>
                            <span class="text-xs font-mono font-bold text-primary" id="panel-session-code">NPC-AIS-2026</span>
                        </div>
                        <div>
                            <p class="text-[11px] text-on-surface-variant">Lecture Topic</p>
                            <p class="text-xs font-bold text-primary" id="panel-topic">—</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-on-surface-variant">Agenda</p>
                            <p class="text-xs text-on-surface leading-relaxed" id="panel-agenda">Synchronous discussion and real-time check-in.</p>
                        </div>
                        <div class="pt-2 border-t border-outline-variant/40 flex items-center justify-between text-xs">
                            <span class="text-on-surface-variant">Meeting URL</span>
                            <a id="panel-meet-url" href="#" target="_blank" class="font-mono text-primary font-bold underline truncate max-w-[160px]">Open Link</a>
                        </div>
                    </div>

                    <!-- Classroom Room Link Card -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-on-surface-variant uppercase tracking-wider">Classroom Room Link</span>
                            <span id="panel-sync-badge" class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">PlugNmeet Active</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <input type="text" id="panel-quick-link-input" readonly class="flex-1 px-3 py-1.5 text-xs rounded-xl border border-outline-variant bg-surface font-mono text-primary focus:outline-none select-all">
                            <button type="button" onclick="copyClassroomLinkFromPanel()" class="px-3 py-1.5 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold shrink-0 inline-flex items-center gap-1 cursor-pointer" title="Copy Classroom Link">
                                <span class="material-symbols-outlined text-[14px]">content_copy</span> Copy
                            </button>
                        </div>
                        <p class="text-[10px] text-on-surface-variant leading-relaxed">
                            🔒 <strong>Institutional WebRTC:</strong> Synchronous room URL automatically delivered to all enrolled students. Auto-presence tracking runs continuously.
                        </p>
                    </div>

                    <!-- Attendance Policy & Live Controls -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-sm space-y-3">
                        <h4 class="text-xs font-mono font-bold text-on-surface-variant uppercase tracking-wider">Attendance Controls</h4>
                        
                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-outline-variant/50">
                            <div>
                                <p class="text-xs font-bold text-primary">Check-in Window</p>
                                <p class="text-[11px] font-mono text-on-surface-variant" id="panel-timer-display">Open (Calculating...)</p>
                            </div>
                            <button type="button" id="panel-lock-toggle-btn" onclick="toggleCurrentSessionLock()" class="px-3 py-1.5 rounded-xl border border-outline-variant text-xs font-bold hover:bg-surface-container transition-colors inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">lock</span> Lock Check-in
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" onclick="extendCurrentSessionGrace(10)" class="flex-1 py-2 rounded-xl border border-primary/40 bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs transition-colors inline-flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">add_alarm</span> +10 Mins
                            </button>
                            <button type="button" onclick="extendCurrentSessionGrace(15)" class="flex-1 py-2 rounded-xl border border-primary/40 bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs transition-colors inline-flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">add_alarm</span> +15 Mins
                            </button>
                        </div>

                        <button type="button" onclick="exportLiveRosterCsv()" class="w-full py-2.5 rounded-xl border border-outline-variant hover:bg-surface-container-low text-on-surface font-bold text-xs transition-colors inline-flex items-center justify-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-[16px]">download</span> Export Attendance (CSV)
                        </button>

                        <button type="button" onclick="endLiveSessionFromPanel()" class="w-full py-2.5 rounded-xl bg-error text-on-error hover:opacity-90 font-bold text-xs transition-opacity inline-flex items-center justify-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-[16px]">stop_circle</span> End Live Class &amp; Close Session
                        </button>
                    </div>
                </div>

                <!-- Right Column: Live Attendance Roster (8 cols) -->
                <div class="lg:col-span-8 flex flex-col bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-outline-variant/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-subtle">
                        <div>
                            <h4 class="text-sm font-bold text-primary">Live Attendance Roster</h4>
                            <p class="text-[11px] text-on-surface-variant font-mono">Real-time verification log for this session</p>
                        </div>
                        <!-- Stats pill -->
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-status-success/15 text-status-success font-mono text-xs font-bold flex items-center gap-1.5 shadow-xs" id="panel-online-count">
                                <span class="w-2 h-2 rounded-full bg-status-success animate-ping"></span> 0 in PlugNmeet
                            </span>
                            <span class="px-2.5 py-1 rounded-lg bg-error/15 text-error font-mono text-xs font-bold" id="panel-left-count">0 Disconnected</span>
                            <span class="px-2.5 py-1 rounded-lg bg-primary/10 text-primary font-mono text-xs font-bold" id="panel-present-count">0 Present</span>
                            <span class="px-2.5 py-1 rounded-lg bg-status-warning/15 text-status-warning font-mono text-xs font-bold" id="panel-late-count">0 Late</span>
                            <span class="px-2.5 py-1 rounded-lg bg-surface-container text-on-surface-variant font-mono text-xs font-bold" id="panel-enrolled-count">0 Enrolled</span>
                            <button type="button" onclick="refreshLiveRoster()" class="p-1 rounded-lg hover:bg-surface-container text-on-surface-variant cursor-pointer" title="Refresh Live Roster">
                                <span class="material-symbols-outlined text-[18px]">refresh</span>
                            </button>
                        </div>
                    </div>

                    <!-- Roster Table Container -->
                    <div class="flex-1 overflow-y-auto max-h-[480px]">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="sticky top-0 bg-surface-container-low border-b border-outline-variant/60 text-on-surface-variant font-mono text-[11px]">
                                <tr>
                                    <th class="py-2.5 px-3 font-semibold">Student (Last Name A-Z)</th>
                                    <th class="py-2.5 px-3 font-semibold">Live Presence</th>
                                    <th class="py-2.5 px-3 font-semibold">Class Duration</th>
                                    <th class="py-2.5 px-3 font-semibold text-error"><span class="flex items-center gap-1 font-bold" title="Strictly visible only to the professor in real time"><span class="material-symbols-outlined text-[14px]">visibility_off</span> Leaves (Prof Only)</span></th>
                                    <th class="py-2.5 px-3 font-semibold">Initial Check-in</th>
                                    <th class="py-2.5 px-3 font-semibold">Status</th>
                                    <th class="py-2.5 px-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="panel-roster-tbody" class="divide-y divide-outline-variant/30">
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-on-surface-variant italic">Waiting for student check-ins...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- ─── MODAL 6: In-Browser Interactive File Preview Modal (No Download Required) ─── -->
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
                            <span class="text-[11px] text-on-surface-variant/80 hidden sm:inline">· In-browser interactive preview (no forced download)</span>
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

    <!-- Script Engine -->
    <script>
        var FACULTY_COURSES = [];
        var ALL_SUBMISSIONS = [];

        function switchFacultyTab(tab) {
            var btnClasses = document.getElementById('tab-btn-classes');
            var btnSubs = document.getElementById('tab-btn-subs');
            var secClasses = document.getElementById('section-classes');
            var secSubs = document.getElementById('section-subs');

            if (tab === 'classes') {
                btnClasses.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                btnSubs.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
                secClasses.classList.remove('hidden');
                secSubs.classList.add('hidden');
            } else {
                btnSubs.className = 'px-4 py-2.5 text-xs font-bold text-primary border-b-2 border-primary flex items-center gap-2 cursor-pointer';
                btnClasses.className = 'px-4 py-2.5 text-xs font-bold text-on-surface-variant hover:text-primary border-b-2 border-transparent flex items-center gap-2 cursor-pointer';
                secSubs.classList.remove('hidden');
                secClasses.classList.add('hidden');
            }
        }

        var LAST_FACULTY_ELMS_FINGERPRINT = '';
        var FACULTY_REALTIME_SYNC_INTERVAL = null;

        async function loadFacultyElms(forceRender) {
            try {
                var [resCourses, resSubs] = await Promise.all([
                    fetch('/api/elms.php?action=get_courses'),
                    fetch('/api/elms.php?action=get_submissions')
                ]);

                var dataCourses = await resCourses.json();
                var dataSubs = await resSubs.json();

                var courses = (dataCourses.success && dataCourses.courses) ? dataCourses.courses : [];
                var subs = (dataSubs.success && dataSubs.submissions) ? dataSubs.submissions : [];

                var fingerprint = JSON.stringify(courses.map(function(c) {
                    return {
                        code: c.code,
                        live: c.live_session ? {
                            is_live: c.live_session.is_live,
                            session_code: c.live_session.session_code,
                            locked: c.live_session.is_attendance_locked,
                            meet: c.live_session.meeting_link,
                            attendees_count: (c.live_session.attendees || []).length
                        } : null,
                        modules_count: (c.modules || []).length,
                        assignments_count: (c.assignments || []).length
                    };
                })) + '_' + subs.length + '_' + (subs[0] ? (subs[0].id + '_' + subs[0].status) : '');

                if (forceRender || fingerprint !== LAST_FACULTY_ELMS_FINGERPRINT) {
                    LAST_FACULTY_ELMS_FINGERPRINT = fingerprint;
                    FACULTY_COURSES = courses;
                    renderFacultyCourses(FACULTY_COURSES);
                    populateCourseDropdowns(FACULTY_COURSES);

                    ALL_SUBMISSIONS = subs;
                    renderSubmissionsTable(ALL_SUBMISSIONS);
                }
            } catch (err) {
                console.error('Error loading faculty ELMS:', err);
            }
        }

        function populateCourseDropdowns(courses) {
            var modSel = document.getElementById('mod-course');
            var asgSel = document.getElementById('asg-course');
            var opts = '';
            courses.forEach(function (c) {
                opts += '<option value="' + c.code + '">' + c.code + ' - ' + c.title + '</option>';
            });
            if (modSel) modSel.innerHTML = opts;
            if (asgSel) asgSel.innerHTML = opts;
        }

        function renderFacultyCourses(courses) {
            var container = document.getElementById('faculty-courses-grid');
            var totalModules = 0;
            var totalAsgs = 0;
            var html = '';

            courses.forEach(function (c) {
                totalModules += (c.modules || []).length;
                totalAsgs += (c.assignments || []).length;

                var modulesList = '';
                (c.modules || []).forEach(function (m) {
                    var icon = m.type === 'slides' ? 'slideshow' : (m.type === 'image' ? 'image' : 'description');
                    modulesList += '<div class="p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/40 flex items-center justify-between gap-2 text-xs hover:bg-surface-container transition-colors">' +
                                  '<div class="flex items-center gap-2 min-w-0">' +
                                  '<span class="material-symbols-outlined text-[17px] text-primary shrink-0">' + icon + '</span>' +
                                  '<div class="min-w-0">' +
                                  '<p class="font-medium text-primary truncate">' + m.title + '</p>' +
                                  '<p class="text-[10px] font-mono text-on-surface-variant">' + (m.file_name || 'handout') + ' · ' + m.size + '</p>' +
                                  '</div>' +
                                  '</div>' +
                                  '<div class="flex items-center gap-1 shrink-0">' +
                                  '<button type="button" onclick="openFilePreviewModal(\'/api/elms.php?action=view_material&id=' + m.id + '&inline=1\', \'' + m.file_name.replace(/'/g, &quot;\\'&quot;) + '\', \'' + m.type + '\', \'' + m.size + '\')" class="p-1.5 rounded-lg border border-primary/30 bg-primary/10 hover:bg-primary hover:text-white text-primary transition-colors inline-flex items-center cursor-pointer" title="View Handout in Browser">' +
                                  '<span class="material-symbols-outlined text-[15px]">visibility</span>' +
                                  '</button>' +
                                  '<a href="/api/elms.php?action=download_material&id=' + m.id + '" target="_blank" class="p-1.5 rounded-lg border border-outline-variant bg-surface hover:bg-primary hover:text-white text-primary transition-colors inline-flex items-center" title="Download Handout">' +
                                  '<span class="material-symbols-outlined text-[15px]">download</span>' +
                                  '</a>' +
                                  '<button type="button" onclick="deleteModule(' + m.id + ', \'' + m.title.replace(/'/g, "\\'") + '\')" class="p-1.5 rounded-lg border border-red-500/20 bg-surface hover:bg-red-500 hover:text-white text-red-500 transition-colors inline-flex items-center cursor-pointer" title="Delete Handout">' +
                                  '<span class="material-symbols-outlined text-[15px]">delete</span>' +
                                  '</button>' +
                                  '</div>' +
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

                var isLive = c.live_session && c.live_session.is_active;
                var boxBorder = isLive ? 'border-2 border-red-500/70 shadow-md ring-4 ring-red-500/10' : 'border border-outline-variant shadow-xs';
                
                var liveBoxHtml = '';
                if (isLive) {
                    liveBoxHtml = '<div class="mt-4 p-3 rounded-xl bg-red-500/10 border border-red-500/40 animate-pulse">' +
                                  '<div class="flex items-center justify-between text-xs font-bold text-red-600 dark:text-red-400 mb-1.5">' +
                                  '<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>🔴 LIVE CLASS ACTIVE</span>' +
                                  '<span class="font-mono text-[10px] bg-red-500/20 px-2 py-0.5 rounded">Section ' + c.section + '</span>' +
                                  '</div>' +
                                  '<p class="text-[11px] text-on-surface-variant italic truncate mb-2.5">Topic: ' + (c.live_session.topic || 'Live Virtual Class') + '</p>' +
                                  '<div class="flex items-center gap-2">' +
                                  '<button type="button" onclick="openLiveClassControlPanel(\'' + c.code + '\', \'' + c.title.replace(/'/g, &quot;\\'&quot;) + '\', \'' + c.section + '\', ' + JSON.stringify(c.live_session || {}).replace(/"/g, '&quot;') + ', \'' + (c.id || '') + '\')" class="flex-1 py-2 rounded-xl bg-red-600 text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:opacity-90 transition-all shadow-sm cursor-pointer">' +
                                  '<span class="material-symbols-outlined text-[16px]">settings_input_component</span> Class Control Panel' +
                                  '</button>' +
                                  '<button type="button" onclick="toggleLiveClass(\'' + c.code + '\', \'end\', \'' + c.section + '\', \'' + (c.id || '') + '\')" class="px-3 py-2 rounded-xl border border-red-500/30 text-red-600 dark:text-red-400 bg-surface hover:bg-red-500/10 font-bold text-xs transition-colors cursor-pointer inline-flex items-center gap-1">' +
                                  '<span class="material-symbols-outlined text-[15px]">stop_circle</span> End' +
                                  '</button>' +
                                  '</div>' +
                                  '</div>';
                } else {
                    liveBoxHtml = '<div class="mt-4">' +
                                  '<button type="button" onclick="openStartLiveModal(\'' + c.code + '\', \'' + c.title.replace(/'/g, &quot;\\'&quot;) + '\', \'' + c.section + '\', \'' + (c.id || '') + '\')" class="w-full py-2 rounded-xl border border-red-500/40 bg-red-500/5 hover:bg-red-500/15 text-red-600 dark:text-red-400 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">' +
                                  '<span class="material-symbols-outlined text-[16px]">video_call</span> 🔴 Start Live Virtual Class (Sec ' + c.section + ')' +
                                  '</button>' +
                                  '</div>';
                }

                html += '<div class="course-box bg-surface-container-lowest ' + boxBorder + ' rounded-2xl p-6 flex flex-col justify-between">' +
                        '<div>' +
                        '<div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-outline-variant/60">' +
                        '<span class="px-2.5 py-1 rounded-lg bg-primary-container text-on-primary font-mono text-xs font-bold">' + c.code + '</span>' +
                        '<span class="text-xs font-mono text-on-surface-variant font-medium">' + c.schedule + '</span>' +
                        '</div>' +
                        '<h3 class="text-base font-bold text-primary leading-snug">' + c.title + '</h3>' +
                        '<p class="text-xs text-on-surface-variant mt-1">' + c.instructor + ' · ' + c.room + '</p>' +

                        liveBoxHtml +

                        '<div class="mt-4 flex items-center justify-between gap-2">' +
                        '<span class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant">Modules & Readings (' + (c.modules || []).length + '):</span>' +
                        '<button type="button" onclick="openUploadModalFor(\'' + c.code + '\')" class="text-primary text-[11px] font-bold hover:underline inline-flex items-center gap-0.5 cursor-pointer">' +
                        '<span class="material-symbols-outlined text-[14px]">add</span> Upload Handout' +
                        '</button>' +
                        '</div>' +
                        '<div class="mt-2 space-y-1.5">' + (modulesList || '<p class="text-xs text-on-surface-variant/70 italic p-2">No modules uploaded yet.</p>') + '</div>' +

                        '<div class="mt-5 flex items-center justify-between gap-2">' +
                        '<span class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant">Course Tasks (' + (c.assignments || []).length + '):</span>' +
                        '<button type="button" onclick="openCreateAsgModalFor(\'' + c.code + '\')" class="text-primary text-[11px] font-bold hover:underline inline-flex items-center gap-0.5 cursor-pointer">' +
                        '<span class="material-symbols-outlined text-[14px]">add</span> Post Task' +
                        '</button>' +
                        '</div>' +
                        '<div class="mt-2 space-y-1.5">' + (asgList || '<p class="text-xs text-on-surface-variant/70 italic p-2">No assignments posted.</p>') + '</div>' +
                        '</div>' +

                        '<div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs">' +
                        '<span class="text-on-surface-variant font-mono text-[11px]">' + c.program + ' ' + c.section + '</span>' +
                        '<button type="button" onclick="switchFacultyTab(\'subs\')" class="text-primary font-bold hover:underline inline-flex items-center gap-1 cursor-pointer">' +
                        'Check Submissions →' +
                        '</button>' +
                        '</div>' +
                        '</div>';
            });

            container.innerHTML = html;
            document.getElementById('stat-total-courses').textContent = courses.length + ' Subjects';
            document.getElementById('stat-total-modules').textContent = totalModules + ' Handouts';
            document.getElementById('stat-total-assignments').textContent = totalAsgs + ' Tasks';
        }

        var ALL_SUBMISSIONS_BY_ID = {};

        function formatStudentCollegiateName(rawName) {
            if (!rawName) return 'STUDENT';
            var clean = rawName.trim().replace(/\s+/g, ' ');
            var parts = clean.split(' ');
            if (parts.length <= 1) return parts[0].toUpperCase();
            var last = parts.pop();
            return last.toUpperCase() + ', ' + parts.join(' ');
        }

        function renderSubmissionsTable(subs) {
            subs = subs || ALL_SUBMISSIONS || [];
            var tbody = document.getElementById('submissions-table-body');
            var badge = document.getElementById('subs-badge');
            badge.textContent = subs.length;
            document.getElementById('stat-total-subs').textContent = subs.length + ' Recorded';

            ALL_SUBMISSIONS_BY_ID = {};
            if (subs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-on-surface-variant italic">No student coursework submissions recorded yet.</td></tr>';
                return;
            }

            var html = '';
            subs.forEach(function (s) {
                ALL_SUBMISSIONS_BY_ID[s.id] = s;

                var statusBadge = s.status === 'Graded'
                    ? '<span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-status-success/15 text-status-success">Graded · ' + (s.score || 'Recorded') + '</span>'
                    : '<span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300">Needs Grading</span>';

                var gradeBtn = '<button type="button" onclick="openGradeModalById(\'' + s.id + '\')" class="px-3 py-1.5 rounded-xl bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-all shadow-xs cursor-pointer inline-flex items-center gap-1">' +
                               '<span class="material-symbols-outlined text-[14px]">edit</span> ' + (s.status === 'Graded' ? 'Edit Grade' : 'Grade') +
                               '</button>';

                var displayName = s.formatted_name || formatStudentCollegiateName(s.student_name);

                // Build attached files list (Supports multi-file upload e.g. Proof 1 and Proof 2)
                var fileOrLinkHtml = '';
                var filesList = (s.files && s.files.length) ? s.files : [];
                if (!filesList.length && s.file_name) {
                    filesList = [{
                        file_name: s.file_name,
                        file_path: s.file_path,
                        file_type: s.file_type || 'file',
                        file_size: s.file_size || ''
                    }];
                }

                if (filesList.length > 0) {
                    fileOrLinkHtml += '<div class="space-y-1.5 max-w-[280px]">';
                    filesList.forEach(function (f, idx) {
                        fileOrLinkHtml += '<div class="flex items-center justify-between gap-2 p-1.5 rounded-xl bg-surface border border-outline-variant/60 text-xs shadow-2xs">' +
                                          '<div class="flex items-center gap-1.5 min-w-0">' +
                                          '<span class="material-symbols-outlined text-[16px] text-primary shrink-0">description</span>' +
                                          '<span class="truncate font-semibold text-primary text-[11px]" title="' + f.file_name + '">' + f.file_name + '</span>' +
                                          '<span class="text-[9px] font-mono text-on-surface-variant shrink-0">(' + (f.file_size || 'file') + ')</span>' +
                                          '</div>' +
                                          '<div class="flex items-center gap-1 shrink-0">' +
                                          '<button type="button" onclick="openFilePreviewModal(\'/api/elms.php?action=view_submission&id=' + s.id + '&file_idx=' + idx + '&inline=1\', \'' + f.file_name.replace(/'/g, &quot;\\'&quot;) + '\', \'' + (f.file_type || '') + '\', \'' + (f.file_size || '') + '\')" class="px-2 py-0.5 rounded-lg bg-primary/10 hover:bg-primary text-primary hover:text-white font-bold text-[10px] transition-colors inline-flex items-center gap-0.5 cursor-pointer" title="View inside Browser">' +
                                          '<span class="material-symbols-outlined text-[13px]">visibility</span> View' +
                                          '</button>' +
                                          '<a href="/api/elms.php?action=download_submission&id=' + s.id + '&file_idx=' + idx + '" target="_blank" class="p-1 rounded-lg border border-outline-variant hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors inline-flex items-center" title="Download">' +
                                          '<span class="material-symbols-outlined text-[13px]">download</span>' +
                                          '</a>' +
                                          '</div>' +
                                          '</div>';
                    });
                    fileOrLinkHtml += '</div>';
                }

                if (s.submitted_link) {
                    fileOrLinkHtml += '<div class="mt-1"><a href="' + s.submitted_link + '" target="_blank" class="text-primary underline font-medium hover:opacity-80 inline-flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-[13px]">link</span> Proof Link ↗</a></div>';
                }

                if (!filesList.length && !s.submitted_link) {
                    fileOrLinkHtml = '<span class="text-on-surface-variant/60 italic text-xs">No attachment</span>';
                }

                html += '<tr class="hover:bg-surface-container-low transition-colors border-b border-outline-variant/40">' +
                        '<td class="p-4">' +
                        '<p class="font-bold text-primary tracking-wide">' + displayName + '</p>' +
                        '<p class="font-mono text-[10px] text-on-surface-variant">' + (s.student_number || s.student_email || 'Enrolled Student') + '</p>' +
                        '</td>' +
                        '<td class="p-4"><span class="px-2 py-0.5 rounded-lg bg-primary/10 text-primary font-mono text-xs font-bold">' + s.course_code + '</span></td>' +
                        '<td class="p-4 font-semibold text-on-surface">' + (s.assignment_title || 'Course Task') + '</td>' +
                        '<td class="p-4">' + fileOrLinkHtml + '</td>' +
                        '<td class="p-4 font-mono text-[11px] text-on-surface-variant">' + (s.submitted_at || 'Just now') + '</td>' +
                        '<td class="p-4">' + statusBadge + '</td>' +
                        '<td class="p-4 text-right">' + gradeBtn + '</td>' +
                        '</tr>';
            });

            tbody.innerHTML = html;
        }

        // ─── Modal Openers / Closers ───
        function openUploadModal() {
            document.getElementById('upload-modal').classList.remove('hidden');
            document.getElementById('upload-modal').classList.add('flex');
        }
        function openUploadModalFor(courseCode) {
            var sel = document.getElementById('mod-course');
            if (sel) sel.value = courseCode;
            openUploadModal();
        }
        function closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
            document.getElementById('upload-modal').classList.remove('flex');
        }

        function openCreateAsgModal() {
            var dueInput = document.getElementById('asg-due');
            var nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
            dueInput.value = nextWeek.toISOString().split('T')[0];
            document.getElementById('create-asg-modal').classList.remove('hidden');
            document.getElementById('create-asg-modal').classList.add('flex');
        }
        function openCreateAsgModalFor(courseCode) {
            var sel = document.getElementById('asg-course');
            if (sel) sel.value = courseCode;
            openCreateAsgModal();
        }
        function closeCreateAsgModal() {
            document.getElementById('create-asg-modal').classList.add('hidden');
            document.getElementById('create-asg-modal').classList.remove('flex');
        }

        function openGradeModalById(subId) {
            var s = ALL_SUBMISSIONS_BY_ID[subId];
            if (!s) return;
            openGradeModal(s.id, s.formatted_name || s.student_name, s.student_number, s.assignment_title, s.submitted_link, s.notes, s.score, s.remarks, s.files, s);
        }

        function openGradeModal(subId, studentName, studentNum, asgTitle, link, notes, currentScore, currentRemarks, files, submissionObj) {
            document.getElementById('grade-sub-id').value = subId;
            document.getElementById('grade-student-label').textContent = studentName + ' (' + studentNum + ')';
            document.getElementById('grade-asg-title').textContent = asgTitle || 'Coursework Assignment';
            
            var fileContainer = document.getElementById('grade-sub-file-container');
            var filesListEl = document.getElementById('grade-sub-files-list');

            var filesToRender = [];
            if (Array.isArray(files) && files.length > 0) {
                filesToRender = files;
            } else if (submissionObj && submissionObj.files && submissionObj.files.length > 0) {
                filesToRender = submissionObj.files;
            } else if (submissionObj && submissionObj.file_name) {
                filesToRender = [{
                    file_name: submissionObj.file_name,
                    file_type: submissionObj.file_type || 'file',
                    file_size: submissionObj.file_size || ''
                }];
            }

            if (filesToRender.length > 0 && filesListEl) {
                fileContainer.classList.remove('hidden');
                var fHtml = '';
                filesToRender.forEach(function(f, idx) {
                    fHtml += '<div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-surface border border-outline-variant/60">' +
                             '<div class="flex items-center gap-2 min-w-0">' +
                             '<span class="material-symbols-outlined text-[17px] text-primary shrink-0">description</span>' +
                             '<span class="truncate font-semibold text-primary text-xs">' + f.file_name + '</span>' +
                             '<span class="text-[10px] font-mono text-on-surface-variant shrink-0">(' + (f.file_size || '') + ')</span>' +
                             '</div>' +
                             '<div class="flex items-center gap-1.5 shrink-0">' +
                             '<button type="button" onclick="openFilePreviewModal(\'/api/elms.php?action=view_submission&id=' + subId + '&file_idx=' + idx + '&inline=1\', \'' + f.file_name.replace(/'/g, &quot;\\'&quot;) + '\', \'' + (f.file_type || '') + '\', \'' + (f.file_size || '') + '\')" class="px-2.5 py-1 rounded-lg bg-primary/10 hover:bg-primary text-primary hover:text-white font-bold text-xs transition-colors inline-flex items-center gap-1 cursor-pointer">' +
                             '<span class="material-symbols-outlined text-[14px]">visibility</span> View Work' +
                             '</button>' +
                             '<a href="/api/elms.php?action=download_submission&id=' + subId + '&file_idx=' + idx + '" target="_blank" class="p-1 rounded-lg border border-outline-variant hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors inline-flex items-center" title="Download">' +
                             '<span class="material-symbols-outlined text-[14px]">download</span>' +
                             '</a>' +
                             '</div>' +
                             '</div>';
                });
                filesListEl.innerHTML = fHtml;
            } else {
                fileContainer.classList.add('hidden');
            }

            var linkContainer = document.getElementById('grade-sub-link-container');
            var linkEl = document.getElementById('grade-sub-link');
            if (link && link !== 'null' && link !== '') {
                linkContainer.classList.remove('hidden');
                linkEl.href = link;
            } else {
                linkContainer.classList.add('hidden');
            }

            document.getElementById('grade-sub-notes').textContent = 'Notes: ' + (notes && notes !== 'null' ? notes : 'None provided');
            document.getElementById('grade-score').value = currentScore && currentScore !== 'null' ? currentScore : '95/100';
            document.getElementById('grade-remarks').value = currentRemarks && currentRemarks !== 'null' ? currentRemarks : '';

            document.getElementById('grade-modal').classList.remove('hidden');
            document.getElementById('grade-modal').classList.add('flex');
        }
        function closeGradeModal() {
            document.getElementById('grade-modal').classList.add('hidden');
            document.getElementById('grade-modal').classList.remove('flex');
        }

        function onModuleFileSelected(input) {
            var label = document.getElementById('mod-file-name');
            var titleInput = document.getElementById('mod-title');
            if (input.files && input.files[0]) {
                var f = input.files[0];
                var sz = (f.size >= 1048576) ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                label.innerHTML = '<strong>' + f.name + '</strong> (' + sz + ')';
                if (!titleInput.value.trim()) {
                    var cleanTitle = f.name.replace(/\.[^/.]+$/, '').replace(/[_\-\.]+/g, ' ');
                    titleInput.value = cleanTitle;
                }
            }
        }

        async function deleteModule(modId, modTitle) {
            if (!confirm('Are you sure you want to remove the learning material "' + modTitle + '"?')) return;
            try {
                var formData = new FormData();
                formData.append('id', modId);
                var res = await fetch('/api/elms.php?action=delete_material', {
                    method: 'POST',
                    body: formData
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to delete material');
                if (window.notify) window.notify('Material successfully removed.', 'info');
                loadFacultyElms();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        // ─── Form Handlers ───
        async function handleModuleUpload(e) {
            e.preventDefault();
            var courseCode = document.getElementById('mod-course').value;
            var title = document.getElementById('mod-title').value.trim();
            var desc = document.getElementById('mod-desc').value.trim();
            var fileInput = document.getElementById('mod-file');
            var btn = document.getElementById('upload-submit-btn');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Uploading & Publishing...';

            try {
                var formData = new FormData();
                formData.append('course_code', courseCode);
                formData.append('title', title);
                formData.append('description', desc);
                if (fileInput.files && fileInput.files[0]) {
                    formData.append('material_file', fileInput.files[0]);
                }

                var res = await fetch('/api/elms.php?action=upload_material', {
                    method: 'POST',
                    body: formData
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to upload module');

                closeUploadModal();
                document.getElementById('upload-module-form').reset();
                document.getElementById('mod-file-name').textContent = 'Click or drag handout/slides here';
                if (window.notify) {
                    window.notify('Module published to students in ' + courseCode + '!', 'success');
                } else {
                    alert('Module published successfully to ' + courseCode + '!');
                }
                loadFacultyElms(true);
            } catch (err) {
                alert('Error uploading module: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">cloud_upload</span> Publish to Students';
            }
        }

        async function handleAssignmentCreate(e) {
            e.preventDefault();
            var courseCode = document.getElementById('asg-course').value;
            var title = document.getElementById('asg-title').value.trim();
            var instructions = document.getElementById('asg-instructions').value.trim();
            var points = parseInt(document.getElementById('asg-points').value) || 100;
            var dueDate = document.getElementById('asg-due').value + ' 23:59';
            var btn = document.getElementById('create-asg-submit-btn');

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

                closeCreateAsgModal();
                if (window.notify) {
                    window.notify('Assignment posted for ' + courseCode + '!', 'success');
                } else {
                    alert('Assignment posted successfully!');
                }
                loadFacultyElms();
            } catch (err) {
                alert('Error creating assignment: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">save</span> Publish Assignment';
            }
        }

        async function handleGradeSubmit(e) {
            e.preventDefault();
            var subId = document.getElementById('grade-sub-id').value;
            var score = document.getElementById('grade-score').value.trim();
            var remarks = document.getElementById('grade-remarks').value.trim();
            var btn = document.getElementById('grade-submit-btn');

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Saving...';

            try {
                var res = await fetch('/api/elms.php?action=grade_submission', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        submission_id: subId,
                        score: score,
                        remarks: remarks
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to grade submission');

                closeGradeModal();
                if (window.notify) {
                    window.notify('Student grade recorded: ' + score, 'success');
                } else {
                    alert('Student grade recorded!');
                }
                loadFacultyElms();
            } catch (err) {
                alert('Error grading submission: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">check_circle</span> Save & Send Grade';
            }
        }

        // ─── LIVE VIRTUAL CLASSROOM & CONTROL PANEL HANDLERS ───
        var CURRENT_LIVE_SESSION = null;
        var CURRENT_ROSTER_DATA = [];
        var ROSTER_POLL_INTERVAL = null;
        var COUNTDOWN_TIMER = null;
        var TEACHER_MEET_WINDOW = null;

        function openStartLiveModal(code, title, section, courseId) {
            document.getElementById('live-course-code').value = code;
            if (document.getElementById('live-course-id')) document.getElementById('live-course-id').value = courseId || '';
            if (document.getElementById('live-course-section')) document.getElementById('live-course-section').value = section || '2A';
            document.getElementById('live-modal-course-badge').textContent = code;
            document.getElementById('live-modal-section-badge').textContent = 'Section ' + section;
            document.getElementById('live-modal-course-title').textContent = title;
            document.getElementById('live-section-readonly').value = 'Section ' + section;
            document.getElementById('live-topic').value = title + ' - Synchronous Lecture';
            document.getElementById('live-agenda').value = '';
            
            var modal = document.getElementById('start-live-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('live-topic').focus();
        }

        function closeStartLiveModal() {
            var modal = document.getElementById('start-live-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function toggleDurationMinsField(mode) {
            var container = document.getElementById('live-duration-mins-container');
            if (!container) return;
            if (mode === 'timed') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        async function handleStartLiveSubmit(e) {
            e.preventDefault();
            var code = document.getElementById('live-course-code').value;
            var courseId = document.getElementById('live-course-id')?.value || '';
            var sec = document.getElementById('live-course-section')?.value || document.getElementById('live-section-readonly').value.replace(/^Section\s+/i, '');
            var topic = document.getElementById('live-topic').value.trim();
            var agenda = document.getElementById('live-agenda').value.trim();
            var grace = parseInt(document.getElementById('live-grace-period').value, 10) || 15;
            var btn = document.getElementById('start-live-submit-btn');

            var platform = 'plugnmeet';
            var timerMode = document.getElementById('live-timer-mode')?.value || 'unlimited';
            var durationMins = (timerMode === 'unlimited') ? 0 : parseInt(document.getElementById('live-duration-mins')?.value || '60', 10);
            var meetLink = '';
            var roomWindow = null;

            try {
                roomWindow = window.open('about:blank', 'NPC_LiveRoom_' + code, 'width=1280,height=800,menubar=no,toolbar=no');
            } catch (e) {}

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Creating Live Class...';

            try {
                var res = await fetch('/api/elms.php?action=toggle_live_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_id: courseId,
                        course_code: code,
                        section: sec,
                        state: 'start',
                        topic: topic,
                        agenda: agenda,
                        platform: 'plugnmeet',
                        timer_mode: timerMode,
                        duration_minutes: durationMins,
                        meeting_link: meetLink,
                        grace_period: grace
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to start live class');

                closeStartLiveModal();
                if (window.notify) {
                    window.notify('PlugNmeet / WebRTC virtual class started for ' + code + ' (Section ' + sec + ')!', 'success');
                }

                var roomUrl = '/live_room.php?session_code=' + encodeURIComponent(data.live_session?.session_code || '') + '&course_code=' + encodeURIComponent(code) + '&room_id=' + encodeURIComponent(data.live_session?.room_id || '');
                if (roomWindow && !roomWindow.closed) {
                    roomWindow.location.href = roomUrl;
                    TEACHER_MEET_WINDOW = roomWindow;
                } else {
                    window.location.href = roomUrl;
                    return;
                }

                // Open ELMS Class Control Panel
                openLiveClassControlPanel(code, data.course?.title || topic, data.course?.section || sec, data.live_session, courseId);
                loadFacultyElms();
            } catch (err) {
                if (roomWindow && !roomWindow.closed) roomWindow.close();
                alert('Error starting live class: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">sensors</span> Start Class &amp; Control Panel';
            }
        }

        function openLiveClassControlPanel(code, title, section, session, courseId) {
            CURRENT_LIVE_SESSION = session || {};
            CURRENT_LIVE_SESSION.course_id = courseId || '';
            CURRENT_LIVE_SESSION.course_code = code;
            CURRENT_LIVE_SESSION.course_title = title;
            CURRENT_LIVE_SESSION.section = section;

            var roomUrl = '/live_room.php?session_code=' + encodeURIComponent(session.session_code || '') + '&course_code=' + encodeURIComponent(code) + '&room_id=' + encodeURIComponent(session.room_id || '');

            document.getElementById('room-header-title').textContent = code + ' · ' + title;
            document.getElementById('room-header-sub').textContent = 'Section ' + section + ' · PlugNmeet WebRTC Classroom';
            document.getElementById('panel-session-code').textContent = session.session_code || ('NPC-' + code + '-' + new Date().toISOString().slice(0,10));
            document.getElementById('panel-topic').textContent = session.topic || (title + ' Live Lecture');
            document.getElementById('panel-agenda').textContent = session.agenda || 'Synchronous discussion and real-time attendance.';
            
            var meetLinkEl = document.getElementById('panel-meet-url');
            if (meetLinkEl) {
                meetLinkEl.href = roomUrl;
                meetLinkEl.textContent = 'Open Live Classroom Room';
            }

            var meetBtn = document.getElementById('control-join-meet-btn');
            if (meetBtn) {
                meetBtn.href = roomUrl;
                meetBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">co_present</span> Enter PlugNmeet Room ↗';
                meetBtn.className = 'px-4 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary-container text-xs font-bold shadow-xs inline-flex items-center gap-1.5 transition-colors';
            }

            // Sync input & badge
            var quickInput = document.getElementById('panel-quick-link-input');
            var syncBadge = document.getElementById('panel-sync-badge');
            if (quickInput) {
                quickInput.value = window.location.origin + roomUrl;
            }
            if (syncBadge) {
                syncBadge.textContent = 'PlugNmeet Active';
                syncBadge.className = 'px-2 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
            }

            // Lock button state
            updateLockBtnState(session.is_attendance_locked);

            // Timer display
            startSessionTimer(session.present_until, session.late_until);

            var modal = document.getElementById('classroom-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Refresh roster immediately and start polling (every 3.5s for real-time presence)
            refreshLiveRoster();
            if (ROSTER_POLL_INTERVAL) clearInterval(ROSTER_POLL_INTERVAL);
            ROSTER_POLL_INTERVAL = setInterval(refreshLiveRoster, 3500);

            if (LIVE_ROSTER_TICKER_INTERVAL) clearInterval(LIVE_ROSTER_TICKER_INTERVAL);
            LIVE_ROSTER_TICKER_INTERVAL = setInterval(tickRosterTimers, 1000);
        }

        function copyClassroomLinkFromPanel() {
            var quickInput = document.getElementById('panel-quick-link-input');
            if (!quickInput || !quickInput.value) return;
            navigator.clipboard.writeText(quickInput.value).then(function() {
                if (window.notify) {
                    window.notify('PlugNmeet classroom link copied to clipboard!', 'success');
                } else {
                    alert('PlugNmeet link copied to clipboard!');
                }
            }).catch(function() {
                quickInput.select();
                document.execCommand('copy');
                if (window.notify) window.notify('PlugNmeet link copied!', 'success');
            });
        }

        function closeClassroomModal() {
            var modal = document.getElementById('classroom-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (ROSTER_POLL_INTERVAL) {
                clearInterval(ROSTER_POLL_INTERVAL);
                ROSTER_POLL_INTERVAL = null;
            }
            if (LIVE_ROSTER_TICKER_INTERVAL) {
                clearInterval(LIVE_ROSTER_TICKER_INTERVAL);
                LIVE_ROSTER_TICKER_INTERVAL = null;
            }
            if (COUNTDOWN_TIMER) {
                clearInterval(COUNTDOWN_TIMER);
                COUNTDOWN_TIMER = null;
            }
        }

        var LIVE_ROSTER_TICKER_INTERVAL = null;

        function formatDurationHms(totalSecs) {
            var s = Math.max(0, parseInt(totalSecs, 10) || 0);
            var hrs = Math.floor(s / 3600);
            var mins = Math.floor((s % 3600) / 60);
            var secs = s % 60;
            return (hrs < 10 ? '0' : '') + hrs + ':' + (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        function tickRosterTimers() {
            if (!CURRENT_ROSTER_DATA || !CURRENT_ROSTER_DATA.length) return;
            CURRENT_ROSTER_DATA.forEach(function (r) {
                if (r.is_online) {
                    r.duration_seconds = (r.duration_seconds || 0) + 1;
                    var el = document.getElementById('roster-timer-' + r.student_number);
                    if (el) {
                        el.innerHTML = '<span class="material-symbols-outlined text-[13px] text-status-success">timer</span> ' + formatDurationHms(r.duration_seconds);
                    }
                }
            });
        }

        function updateLockBtnState(isLocked) {
            var btn = document.getElementById('panel-lock-toggle-btn');
            if (!btn) return;
            if (isLocked) {
                btn.className = 'px-3 py-1.5 rounded-xl bg-error text-on-error font-bold text-xs inline-flex items-center gap-1 shadow-xs';
                btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">lock_open</span> Unlock Check-in';
            } else {
                btn.className = 'px-3 py-1.5 rounded-xl border border-outline-variant hover:bg-surface-container font-bold text-xs text-primary inline-flex items-center gap-1';
                btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">lock</span> Lock Check-in';
            }
        }

        function startSessionTimer(presUntil, lateUntil) {
            var timerEl = document.getElementById('panel-timer-display');
            if (!timerEl) return;
            if (COUNTDOWN_TIMER) clearInterval(COUNTDOWN_TIMER);

            function tick() {
                if (CURRENT_LIVE_SESSION && CURRENT_LIVE_SESSION.is_attendance_locked) {
                    timerEl.textContent = 'Locked by Instructor';
                    timerEl.className = 'text-[11px] font-mono font-bold text-error';
                    return;
                }
                var now = Date.now();
                var presEnd = presUntil ? new Date(presUntil).getTime() : 0;
                var lateEnd = lateUntil ? new Date(lateUntil).getTime() : 0;

                if (presEnd && now < presEnd) {
                    var diffSec = Math.floor((presEnd - now) / 1000);
                    var m = Math.floor(diffSec / 60);
                    var s = diffSec % 60;
                    timerEl.textContent = 'Present Window: ' + m + 'm ' + (s < 10 ? '0' : '') + s + 's left';
                    timerEl.className = 'text-[11px] font-mono font-bold text-status-success';
                } else if (lateEnd && now < lateEnd) {
                    var diffSec = Math.floor((lateEnd - now) / 1000);
                    var m = Math.floor(diffSec / 60);
                    var s = diffSec % 60;
                    timerEl.textContent = 'Late Grace: ' + m + 'm ' + (s < 10 ? '0' : '') + s + 's left';
                    timerEl.className = 'text-[11px] font-mono font-bold text-status-warning';
                } else {
                    timerEl.textContent = 'Check-in Window Expired';
                    timerEl.className = 'text-[11px] font-mono font-bold text-on-surface-variant';
                }
            }
            tick();
            COUNTDOWN_TIMER = setInterval(tick, 1000);
        }

        async function refreshLiveRoster() {
            if (document.hidden) return;
            if (!CURRENT_LIVE_SESSION || !CURRENT_LIVE_SESSION.session_code) return;
            var sc = CURRENT_LIVE_SESSION.session_code;
            var sec = CURRENT_LIVE_SESSION.section || '';
            var tbody = document.getElementById('panel-roster-tbody');

            try {
                var res = await fetch('/api/elms.php?action=get_live_presence_roster&session_code=' + encodeURIComponent(sc) + '&section=' + encodeURIComponent(sec));
                var data = await res.json();
                if (!data.success) return;

                var records = data.roster || [];
                CURRENT_ROSTER_DATA = records;

                // Update summary counters
                var onlineCountEl = document.getElementById('panel-online-count');
                var leftCountEl = document.getElementById('panel-left-count');
                var presCountEl = document.getElementById('panel-present-count');
                var lateCountEl = document.getElementById('panel-late-count');
                var enrolledCountEl = document.getElementById('panel-enrolled-count');

                if (onlineCountEl) onlineCountEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-status-success animate-ping"></span> ' + (data.online_count || 0) + ' in PlugNmeet';
                if (leftCountEl) leftCountEl.textContent = (data.left_count || 0) + ' Disconnected';
                if (presCountEl) presCountEl.textContent = (data.present_count || 0) + ' Present';
                if (lateCountEl) lateCountEl.textContent = (data.late_count || 0) + ' Late';
                if (enrolledCountEl) enrolledCountEl.textContent = (data.total_enrolled || records.length) + ' Enrolled';

                if (!records.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-on-surface-variant italic font-mono text-xs">No students enrolled or recorded for this section yet.</td></tr>';
                } else {
                    var rows = '';
                    records.forEach(function (r) {
                        var st = (r.status || 'absent').toLowerCase();
                        var isOnline = !!r.is_online;
                        var leaveCount = parseInt(r.leave_count, 10) || 0;
                        var durationSecs = parseInt(r.duration_seconds, 10) || 0;

                        // 1. Presence pill
                        var presenceBadge = isOnline 
                            ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-status-success/15 text-status-success border border-status-success/30 shadow-xs"><span class="w-2 h-2 rounded-full bg-status-success animate-ping"></span> In PlugNmeet</span>'
                            : (leaveCount > 0 || (r.check_in_at && st !== 'absent'))
                                ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-error/10 text-error"><span class="w-1.5 h-1.5 rounded-full bg-error"></span> Left / Offline</span>'
                                : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono text-on-surface-variant bg-surface-container">Not Joined</span>';

                        // 2. Duration Timer
                        var durationDisplay = '';
                        if (isOnline) {
                            durationDisplay = '<span class="font-mono font-bold text-xs text-primary flex items-center gap-1" id="roster-timer-' + r.student_number + '"><span class="material-symbols-outlined text-[13px] text-status-success">timer</span> ' + formatDurationHms(durationSecs) + '</span>';
                        } else if (durationSecs > 0) {
                            durationDisplay = '<span class="font-mono text-xs text-on-surface-variant font-medium flex items-center gap-1" id="roster-timer-' + r.student_number + '"><span class="material-symbols-outlined text-[13px] text-gray-400">pause_circle</span> Paused: ' + formatDurationHms(durationSecs) + '</span>';
                        } else {
                            durationDisplay = '<span class="font-mono text-xs text-on-surface-variant">—</span>';
                        }

                        // 3. Leaves / Reconnects (Strictly PROF-ONLY Telemetry)
                        var leaveBadge = '';
                        if (leaveCount === 0) {
                            leaveBadge = '<span class="font-mono text-xs text-on-surface-variant font-semibold">0</span>';
                        } else if (leaveCount === 1) {
                            leaveBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-amber-500/15 text-amber-600 border border-amber-500/30 shadow-xs" title="Student disconnected once"><span class="material-symbols-outlined text-[13px]">sync_problem</span> ⚠️ 1x Left</span>';
                        } else {
                            leaveBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-error/15 text-error border border-error/30 shadow-xs animate-pulse" title="Student disconnected ' + leaveCount + ' times"><span class="material-symbols-outlined text-[13px]">warning</span> 🔴 ' + leaveCount + 'x Left</span>';
                        }

                        // 4. Initial Check-in Time
                        var timeStr = r.check_in_at ? new Date(r.check_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';

                        // 5. Official Status Badge
                        var badgeClass = st === 'present' ? 'bg-status-success/15 text-status-success'
                            : st === 'late' ? 'bg-status-warning/15 text-status-warning'
                            : st === 'excused' ? 'bg-primary/15 text-primary'
                            : 'bg-error/10 text-error';

                        rows += '<tr class="hover:bg-surface-container-low/50 transition-colors">' +
                            '<td class="py-2.5 px-3">' +
                            '<p class="font-bold text-primary font-mono text-xs">' + (r.formatted_name || r.full_name || 'Student') + '</p>' +
                            '<p class="font-mono text-[10px] text-on-surface-variant">' + (r.student_number || 'N/A') + ' · ' + (r.section || '2A') + '</p>' +
                            '</td>' +
                            '<td class="py-2.5 px-3">' + presenceBadge + '</td>' +
                            '<td class="py-2.5 px-3">' + durationDisplay + '</td>' +
                            '<td class="py-2.5 px-3">' + leaveBadge + '</td>' +
                            '<td class="py-2.5 px-3 font-mono text-on-surface-variant text-[11px]">' + timeStr + '</td>' +
                            '<td class="py-2.5 px-3">' +
                            '<span class="px-2 py-0.5 rounded font-mono font-bold uppercase text-[10px] ' + badgeClass + '">' + st + '</span>' +
                            '</td>' +
                            '<td class="py-2.5 px-3 text-right">' +
                            '<button type="button" onclick="promptUpdateAttendanceStatus(\'' + (r.id || '') + '\', \'' + (r.student_number || '') + '\', \'' + (r.full_name || r.formatted_name || '').replace(/'/g, "\\'") + '\', \'' + st + '\')" class="p-1 rounded-lg hover:bg-surface-container text-primary font-bold text-[11px] inline-flex items-center gap-0.5 cursor-pointer" title="Modify Status">' +
                            '<span class="material-symbols-outlined text-[15px]">edit_note</span> Edit' +
                            '</button>' +
                            '</td>' +
                            '</tr>';
                    });
                    tbody.innerHTML = rows;
                }
            } catch (e) {}
        }

        async function toggleCurrentSessionLock() {
            if (!CURRENT_LIVE_SESSION || !CURRENT_LIVE_SESSION.course_code) return;
            var isLocked = !CURRENT_LIVE_SESSION.is_attendance_locked;

            try {
                var res = await fetch('/api/elms.php?action=toggle_live_attendance_lock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_code: CURRENT_LIVE_SESSION.course_code,
                        locked: isLocked
                    })
                });
                var data = await res.json();
                if (data.success) {
                    CURRENT_LIVE_SESSION.is_attendance_locked = isLocked;
                    updateLockBtnState(isLocked);
                    if (window.notify) {
                        window.notify(isLocked ? 'Attendance check-in is now locked.' : 'Attendance check-in is now unlocked.', 'info');
                    }
                }
            } catch (err) {
                alert('Lock error: ' + err.message);
            }
        }

        async function extendCurrentSessionGrace(mins) {
            if (!CURRENT_LIVE_SESSION || !CURRENT_LIVE_SESSION.course_code) return;
            try {
                var res = await fetch('/api/elms.php?action=extend_live_grace_period', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_code: CURRENT_LIVE_SESSION.course_code,
                        minutes: mins
                    })
                });
                var data = await res.json();
                if (data.success && data.times) {
                    CURRENT_LIVE_SESSION.present_until = data.times.present_until;
                    CURRENT_LIVE_SESSION.late_until = data.times.late_until;
                    CURRENT_LIVE_SESSION.is_attendance_locked = false;
                    updateLockBtnState(false);
                    startSessionTimer(data.times.present_until, data.times.late_until);
                    if (window.notify) {
                        window.notify('Grace period extended by ' + mins + ' minutes.', 'success');
                    }
                }
            } catch (err) {
                alert('Extend error: ' + err.message);
            }
        }

        async function promptUpdateAttendanceStatus(recordId, studentNumber, studentName, currentStatus) {
            var newStatus = prompt('Update status for ' + studentName + ' (' + studentNumber + ')\nEnter: present, late, excused, or absent:', currentStatus);
            if (!newStatus) return;
            newStatus = newStatus.trim().toLowerCase();
            if (!['present', 'late', 'excused', 'absent'].includes(newStatus)) {
                return alert('Invalid status. Must be: present, late, excused, or absent.');
            }
            var reason = prompt('Reason for attendance change (required for audit log):', 'Instructor manual correction in Live Class Control Panel');
            if (!reason) return alert('A reason is mandatory for grade and attendance audit logs.');

            try {
                var res = await fetch('/api/faculty.php?action=update_attendance_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        record_id: recordId,
                        student_number: studentNumber,
                        student_name: studentName,
                        session_code: CURRENT_LIVE_SESSION.session_code,
                        status: newStatus,
                        reason: reason
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.message || 'Update failed');
                if (window.notify) window.notify('Status updated to ' + newStatus, 'success');
                refreshLiveRoster();
            } catch (err) {
                alert('Error updating status: ' + err.message);
            }
        }

        function exportLiveRosterCsv() {
            if (!CURRENT_ROSTER_DATA || !CURRENT_ROSTER_DATA.length) {
                return alert('No attendance records to export.');
            }
            var csv = 'Student Number,Last Name,First Name,Full Name,Presence State,Active Duration (HMS),Duration Seconds,Disconnect/Leave Count,Status,Check-in Time,Verified Via,Session Code\n';
            CURRENT_ROSTER_DATA.forEach(function (r) {
                var isOnlineStr = r.is_online ? 'In PlugNmeet' : (r.leave_count > 0 ? 'Disconnected' : 'Not Joined');
                var hms = formatDurationHms(r.duration_seconds || 0);
                csv += '"' + (r.student_number || '') + '",' +
                       '"' + (r.last_name || '').replace(/"/g, '""') + '",' +
                       '"' + (r.first_name || '').replace(/"/g, '""') + '",' +
                       '"' + (r.full_name || r.formatted_name || '').replace(/"/g, '""') + '",' +
                       '"' + isOnlineStr + '",' +
                       '"' + hms + '",' +
                       '"' + (r.duration_seconds || 0) + '",' +
                       '"' + (r.leave_count || 0) + '",' +
                       '"' + (r.status || 'absent') + '",' +
                       '"' + (r.check_in_at || '') + '",' +
                       '"' + (r.verified_via || 'plugnmeet_verified') + '",' +
                       '"' + (CURRENT_LIVE_SESSION.session_code || '') + '"\n';
            });

            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'LiveAttendance_' + (CURRENT_LIVE_SESSION.course_code || 'PlugNmeet') + '_' + (CURRENT_LIVE_SESSION.session_code || new Date().toISOString().slice(0,10)) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        async function endLiveSessionFromPanel() {
            if (!CURRENT_LIVE_SESSION || !CURRENT_LIVE_SESSION.course_code) return;
            if (!await npcConfirm({
                title: 'End Live Session',
                message: 'Are you sure you want to end this live class session?\n\nAttendance check-in will be permanently closed and all students will be automatically exited.',
                type: 'danger',
                confirmText: 'End Session'
            })) {
                return;
            }
            if (TEACHER_MEET_WINDOW && !TEACHER_MEET_WINDOW.closed) {
                try { TEACHER_MEET_WINDOW.close(); } catch (e) {}
                TEACHER_MEET_WINDOW = null;
            }
            await toggleLiveClass(CURRENT_LIVE_SESSION.course_code, 'end', CURRENT_LIVE_SESSION.section, CURRENT_LIVE_SESSION.course_id || CURRENT_LIVE_SESSION.id);
            closeClassroomModal();
        }

        async function toggleLiveClass(code, state, section, courseId) {
            try {
                if (state === 'end' && TEACHER_MEET_WINDOW && !TEACHER_MEET_WINDOW.closed) {
                    try { TEACHER_MEET_WINDOW.close(); } catch (e) {}
                    TEACHER_MEET_WINDOW = null;
                }
                var res = await fetch('/api/elms.php?action=toggle_live_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_id: courseId || '',
                        course_code: code,
                        section: section || '',
                        state: state
                    })
                });
                var data = await res.json();
                if (!data.success) throw new Error(data.error || 'Failed to update live class state');

                if (window.notify) {
                    window.notify(data.message, 'info');
                } else {
                    alert(data.message);
                }
                loadFacultyElms();
            } catch (err) {
                alert('Error: ' + err.message);
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

        document.addEventListener('DOMContentLoaded', function() {
            loadFacultyElms(true).then(function() {
                var urlParams = new URLSearchParams(window.location.search);
                var launchClass = urlParams.get('launch_class') || urlParams.get('course');
                if (launchClass && FACULTY_COURSES && FACULTY_COURSES.length) {
                    var target = FACULTY_COURSES.find(function(c) {
                        return c.id === launchClass || c.code.toLowerCase().replace(/[^a-z0-9]/g, '') === launchClass.toLowerCase().replace(/[^a-z0-9]/g, '');
                    });
                    if (target) {
                        if (target.live_session && target.live_session.is_active) {
                            openLiveClassControlPanel(target.code, target.title, target.section || '2A', target.live_session);
                        } else {
                            openStartLiveModal(target.code, target.title, target.section || '2A');
                        }
                    }
                }
            });
            if (FACULTY_REALTIME_SYNC_INTERVAL) clearInterval(FACULTY_REALTIME_SYNC_INTERVAL);
            FACULTY_REALTIME_SYNC_INTERVAL = setInterval(function() {
                var anyModalOpen = document.querySelector('#modal-add-module:not(.hidden), #modal-create-assignment:not(.hidden), #modal-grade-submission:not(.hidden), #modal-start-live:not(.hidden)');
                if (!anyModalOpen) {
                    loadFacultyElms(false);
                }
            }, 3000);
        });
    </script>
</body>
</html>
