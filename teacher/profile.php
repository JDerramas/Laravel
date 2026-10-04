<?php
require_once __DIR__ . '/../includes/auth.php';
require_teacher();

$userId = $_SESSION['user_id'] ?? '';
$sessionEmail = strtolower(trim($_SESSION['email'] ?? ''));
$db = getDB();

// Fetch fresh faculty records
$teacherData = null;
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR id = ? LIMIT 1");
    $stmt->execute([$sessionEmail, $userId]);
    $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    error_log('[teacher/profile.php DB Error]: ' . $e->getMessage());
}

$fullName = $teacherData['full_name'] ?? $_SESSION['name'] ?? 'Faculty Member';
$email = $teacherData['email'] ?? $sessionEmail;
$employeeNumber = $teacherData['student_number'] ?? $_SESSION['student_number'] ?? '251505';
$roleTitle = 'Faculty Instructor';

// Avatar priority: Google OAuth picture in session -> DB avatar_url -> null
$avatarUrl = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? $teacherData['avatar_url'] ?? null;
$initial = strtoupper(substr($fullName, 0, 1));

// Fetch assigned classes for this instructor
$assignedClasses = [];
try {
    $cStmt = $db->prepare("SELECT * FROM classes WHERE LOWER(instructor_email) = ? OR LOWER(instructor) LIKE ? ORDER BY code ASC");
    $namePattern = '%' . strtolower(trim($fullName)) . '%';
    $cStmt->execute([$sessionEmail, $namePattern]);
    $assignedClasses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$totalTeachingUnits = 0;
foreach ($assignedClasses as $c) {
    $totalTeachingUnits += (int)($c['units'] ?? 3);
}
if ($totalTeachingUnits === 0 && count($assignedClasses) > 0) $totalTeachingUnits = count($assignedClasses) * 3;

$user_name = explode(' ', trim($fullName))[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    $PAGE_TITLE = 'Faculty Profile · NPC LMS';
    include __DIR__ . '/../includes/_head.php'; 
    ?>
    <style>
        .faculty-hero-glow {
            background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 50%),
                        radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.12), transparent 50%),
                        rgba(1, 36, 21, 0.85);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar -->
        <?php 
        $NPC_PORTAL = 'faculty';
        include __DIR__ . '/../includes/_sidebar.php'; 
        ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-3">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-base sm:text-lg font-bold text-primary lg:hidden truncate">NPC LMS</span>
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Faculty Profile</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <a href="/teacher/profile.php" class="flex items-center gap-2 px-2 py-1 rounded-xl bg-amber-400/10 border border-amber-400/30 hover:bg-amber-400/20 transition-all group" title="Viewing Faculty Profile">
                        <span class="font-mono text-[11px] sm:text-xs font-semibold px-2 py-1 rounded-md bg-surface-container border border-outline-variant text-primary hidden sm:inline shrink-0">
                            ID: <?= htmlspecialchars($employeeNumber) ?>
                        </span>
                        <?php if (!empty($avatarUrl)): ?>
                            <img alt="<?= htmlspecialchars($fullName) ?>" class="w-8 h-8 rounded-full object-cover border border-amber-300 shadow-sm shrink-0"
                                src="<?= htmlspecialchars($avatarUrl) ?>" referrerpolicy="no-referrer"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 rounded-full bg-amber-400 text-slate-950 font-bold text-xs items-center justify-center shadow-sm shrink-0 hidden">
                                <?= $initial ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center shadow-sm shrink-0">
                                <?= $initial ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-xs font-bold text-primary hidden md:inline truncate max-w-[120px] group-hover:text-amber-500 transition-colors">
                            <?= htmlspecialchars($user_name) ?>
                        </span>
                    </a>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-6xl w-full mx-auto space-y-6 sm:space-y-8" id="profile-container">

                <!-- ══════════ Section 1: Hero Identity Banner ══════════ -->
                <div class="relative overflow-hidden rounded-3xl faculty-hero-glow border border-amber-500/30 shadow-xl p-6 sm:p-8 md:p-10 text-white">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none hidden md:block">
                        <span class="material-symbols-outlined text-[160px]">cast_for_education</span>
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6 sm:gap-8 text-center md:text-left">
                        <!-- Large Avatar with Google badge ring -->
                        <div class="relative shrink-0">
                            <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full p-1 border-2 border-dashed border-amber-300 shadow-2xl relative">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($fullName) ?>" 
                                         class="w-full h-full rounded-full object-cover shadow-inner bg-white/10"
                                         referrerpolicy="no-referrer"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-slate-950 font-extrabold text-3xl sm:text-4xl items-center justify-center hidden">
                                        <?= $initial ?>
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-full rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-slate-950 font-extrabold text-3xl sm:text-4xl flex items-center justify-center">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Google Sync indicator -->
                            <div class="absolute bottom-1 right-1 w-9 h-9 rounded-full bg-white border border-gray-200 shadow-lg flex items-center justify-center" title="Synced with Google Account">
                                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-5 h-5">
                            </div>
                        </div>

                        <!-- Name & Core Credentials -->
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold font-mono uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                    FACULTY INSTRUCTOR
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold font-mono uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                    ACTIVE TEACHING LOAD
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white/90 border border-white/15">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Official Faculty Profile
                                </span>
                            </div>

                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                                <?= htmlspecialchars($fullName) ?>
                            </h1>

                            <!-- Institutional Email Pill -->
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 pt-1">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 border border-white/20 text-white font-mono text-xs sm:text-sm">
                                    <span class="material-symbols-outlined text-[18px] text-amber-300">verified</span>
                                    <span><?= htmlspecialchars($email) ?></span>
                                </div>
                                <span class="text-xs text-emerald-200/80 font-mono hidden sm:inline">Official NPC Google Account</span>
                            </div>

                            <!-- Role & Employee ID -->
                            <p class="text-sm sm:text-base text-white/85 font-medium pt-1">
                                <span class="text-amber-300 font-bold">Faculty Instructor</span>
                                <span class="text-white/40 mx-2">|</span> 
                                Employee ID: <span class="px-2 py-0.5 rounded-md bg-white/15 font-mono font-bold text-white"><?= htmlspecialchars($employeeNumber) ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ Section 2: 4-Metric Faculty Cards ══════════ -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Assigned Classes</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">school</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary"><?= count($assignedClasses) ?> Classes</p>
                            <p class="text-[11px] text-on-surface-variant">Active Load</p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Teaching Units</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">menu_book</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary"><?= $totalTeachingUnits ?> Units</p>
                            <p class="text-[11px] text-on-surface-variant font-mono">1st Semester 2026–2027</p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Employee Number</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">badge</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold font-mono text-primary truncate"><?= htmlspecialchars($employeeNumber) ?></p>
                            <p class="text-[11px] text-on-surface-variant font-mono">Faculty ID</p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Faculty Status</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">verified_user</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary">Active</p>
                            <p class="text-[11px] text-on-surface-variant truncate">Full-time Faculty</p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ Section 3: Two Column Faculty Details ══════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left: Official Teaching Load & Assigned Classes (2 Cols) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Assigned Classes Card -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 sm:p-8 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-outline-variant pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[22px]">format_list_bulleted</span>
                                    </div>
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-primary">My Assigned Teaching Load</h3>
                                        <p class="text-xs text-on-surface-variant">Classes registered under <?= htmlspecialchars($fullName) ?></p>
                                    </div>
                                </div>
                                <a href="/teacher/classes.php" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                                    Class Manager <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>

                            <?php if (count($assignedClasses) > 0): ?>
                                <div class="divide-y divide-outline-variant">
                                    <?php foreach ($assignedClasses as $cls): ?>
                                        <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-surface-container-low px-2 rounded-xl transition-colors">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary font-mono text-xs font-bold border border-primary/20">
                                                        <?= htmlspecialchars($cls['code'] ?? 'SUBJ') ?>
                                                    </span>
                                                    <span class="text-sm font-bold text-primary"><?= htmlspecialchars($cls['title'] ?? 'Subject') ?></span>
                                                </div>
                                                <p class="text-xs text-on-surface-variant flex items-center gap-3">
                                                    <span><strong class="text-on-surface">Section:</strong> <?= htmlspecialchars($cls['section'] ?? 'TBA') ?></span>
                                                    <span>•</span>
                                                    <span><strong class="text-on-surface">Room:</strong> <?= htmlspecialchars($cls['room'] ?? 'TBA') ?></span>
                                                </p>
                                            </div>
                                            <div class="text-left sm:text-right shrink-0">
                                                <p class="text-xs font-mono font-semibold text-primary">
                                                    <?= htmlspecialchars($cls['schedule_day'] ?? 'TBA') ?> 
                                                    <?= !empty($cls['start_time']) ? htmlspecialchars(date('g:i A', strtotime($cls['start_time']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($cls['end_time']))) : '' ?>
                                                </p>
                                                <span class="text-[11px] font-mono text-on-surface-variant"><?= (int)($cls['units'] ?? 3) ?> Units</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8 text-on-surface-variant space-y-2">
                                    <span class="material-symbols-outlined text-4xl text-outline">calendar_today</span>
                                    <p class="text-sm font-medium">No classes currently assigned to this faculty profile.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Institutional Google Account Status & Quick Actions -->
                    <div class="space-y-6">

                        <!-- Google Account Sync Card -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 shadow-sm space-y-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center shadow-sm">
                                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-5 h-5">
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-primary">Google Institutional Identity</h3>
                                    <p class="text-[11px] text-on-surface-variant">Workspace for Education</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant font-mono">Domain:</span>
                                    <span class="font-bold text-primary font-mono truncate max-w-[170px]">navotaspolytechniccollege.edu.ph</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant font-mono">Avatar Sync:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                        Google Photo
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant font-mono">Security:</span>
                                    <span class="font-bold text-primary">OAuth 2.0 / 2FA</span>
                                </div>
                            </div>

                            <div class="pt-1">
                                <p class="text-[11px] text-on-surface-variant leading-relaxed">
                                    Your faculty credentials, profile photo, and institutional access are verified through official NPC Google Workspace single sign-on.
                                </p>
                            </div>
                        </div>

                        <!-- Quick Navigation Actions -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 shadow-sm space-y-3">
                            <h3 class="text-sm font-bold text-primary mb-3">Faculty Tools</h3>

                            <a href="/teacher/index.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">dashboard</span>
                                <span class="text-xs font-bold flex-1">Faculty Dashboard</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/teacher/attendance.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">qr_code_scanner</span>
                                <span class="text-xs font-bold flex-1">Live Class Attendance QR</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/teacher/grades.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">grade</span>
                                <span class="text-xs font-bold flex-1">Grade Encoding & Submissions</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/teacher/classes.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">school</span>
                                <span class="text-xs font-bold flex-1">My Assigned Classes</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/teacher/campus_map.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">map</span>
                                <span class="text-xs font-bold flex-1">Campus Map & Rooms</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
