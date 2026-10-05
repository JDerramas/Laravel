<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();

$userId = $_SESSION['user_id'] ?? '';
$sessionEmail = strtolower(trim($_SESSION['email'] ?? ''));
$db = getDB();

// Handle avatar photo upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_avatar'])) {
    requireCsrf();
    $file = $_FILES['profile_avatar'];
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0 && $file['size'] <= 5 * 1024 * 1024) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowedTypes)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) $ext = 'jpg';
            $filename = 'avatar_' . md5($sessionEmail . time()) . '.' . $ext;
            $destPath = __DIR__ . '/../uploads/avatars/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $avatarWebPath = '/uploads/avatars/' . $filename;
                $db->prepare("UPDATE users SET avatar_url = ? WHERE LOWER(email) = ? OR id = ?")->execute([$avatarWebPath, $sessionEmail, $userId]);
                $db->prepare("UPDATE students SET avatar_url = ? WHERE LOWER(email) = ? OR user_id = ?")->execute([$avatarWebPath, $sessionEmail, $userId]);
                $_SESSION['picture'] = $avatarWebPath;
                $_SESSION['avatar'] = $avatarWebPath;
                header("Location: profile.php?msg=avatar_updated");
                exit();
            }
        }
    }
}

// Fetch fresh student and user records
$studentData = null;
try {
    $stmt = $db->prepare("SELECT s.*, u.avatar_url AS u_avatar, u.full_name AS u_name, u.role AS u_role, u.is_active 
                          FROM students s 
                          LEFT JOIN users u ON (u.email = s.email OR u.id = s.user_id) 
                          WHERE LOWER(s.email) = ? OR s.user_id = ? OR s.student_number = ? 
                          LIMIT 1");
    $stmt->execute([$sessionEmail, $userId, $_SESSION['student_number'] ?? '']);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    error_log('[student/profile.php DB Error]: ' . $e->getMessage());
}

// Fallback to users table if student record isn't in students table yet
if (!$studentData) {
    try {
        $uStmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR id = ? LIMIT 1");
        $uStmt->execute([$sessionEmail, $userId]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($uRow) {
            $studentData = [
                'full_name' => $uRow['full_name'],
                'email' => $uRow['email'],
                'student_number' => $uRow['student_number'],
                'avatar_url' => $uRow['avatar_url'],
                'program' => $uRow['program'] ?? 'AIS',
                'section' => $uRow['section'] ?? '2A',
                'year_level' => 2,
                'status' => 'Enrolled'
            ];
        }
    } catch (\Throwable $e) {}
}

$rawFullName = $studentData['full_name'] ?? $_SESSION['raw_name'] ?? $_SESSION['name'] ?? 'NPC Student';
$fullName = formatLastNameFirst($rawFullName);
$email = $studentData['email'] ?? $sessionEmail;
$studentNumber = $studentData['student_number'] ?? $_SESSION['student_number'] ?? '2024-00192';
$programCode = strtoupper($studentData['program'] ?? $_SESSION['program'] ?? 'AIS');
$section = $studentData['section'] ?? $_SESSION['section'] ?? '2A';
$yearLevel = $studentData['year_level'] ?? 2;
$status = $studentData['status'] ?? 'Enrolled';

// Avatar priority: DB students.avatar_url -> users.avatar_url -> session picture
$avatarUrl = !empty($studentData['avatar_url']) ? $studentData['avatar_url'] 
    : (!empty($studentData['u_avatar']) ? $studentData['u_avatar'] 
    : (!empty($_SESSION['picture']) ? $_SESSION['picture'] 
    : (!empty($_SESSION['avatar']) ? $_SESSION['avatar'] : null)));
$initial = strtoupper(substr($rawFullName, 0, 1));

// Program dictionary
$programsDict = [
    'AIS'  => 'Associate in Information Systems',
    'BSIS' => 'Bachelor of Science in Information Systems',
    'BSIT' => 'Bachelor of Science in Information Technology',
    'BSCS' => 'Bachelor of Science in Computer Science',
    'BSA'  => 'Bachelor of Science in Accountancy',
    'BSBA' => 'Bachelor of Science in Business Administration',
    'BSE'  => 'Bachelor of Science in Entrepreneurship',
    'BEED' => 'Bachelor of Elementary Education',
    'BSED' => 'Bachelor of Secondary Education'
];
$programName = $programsDict[$programCode] ?? 'Information Systems Program';

// Fetch enrolled classes for this student's section
$enrolledClasses = [];
try {
    $secPattern = '%' . $section . '%';
    $cStmt = $db->prepare("SELECT * FROM classes WHERE section LIKE ? ORDER BY code ASC");
    $cStmt->execute([$secPattern]);
    $enrolledClasses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$totalUnits = 0;
foreach ($enrolledClasses as $c) {
    $totalUnits += (int)($c['units'] ?? 3);
}
if ($totalUnits === 0 && count($enrolledClasses) > 0) $totalUnits = count($enrolledClasses) * 3;

$user_id_display = $studentNumber;
$raw_name = $fullName;
$user_name = explode(' ', trim($raw_name))[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    $PAGE_TITLE = 'My Profile · NPC Student Portal';
    include __DIR__ . '/../includes/_head.php'; 
    ?>
    <style>
        .profile-hero-glow {
            background: radial-gradient(circle at top right, rgba(254, 212, 136, 0.15), transparent 50%),
                        radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.12), transparent 50%),
                        rgba(1, 36, 21, 0.75);
        }
        .info-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .info-card:hover {
            border-color: rgba(254, 212, 136, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar -->
        <?php 
        $NPC_PORTAL = 'student';
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
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Student Profile</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <a href="/student/profile.php" class="flex items-center gap-2 px-2 py-1 rounded-xl bg-amber-400/10 border border-amber-400/30 hover:bg-amber-400/20 transition-all group" title="Viewing Profile">
                        <span class="font-mono text-[11px] sm:text-xs font-semibold px-2 py-1 rounded-md bg-surface-container border border-outline-variant text-primary hidden sm:inline shrink-0">
                            ID: <?= htmlspecialchars($studentNumber) ?>
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
                <div class="relative overflow-hidden rounded-3xl profile-hero-glow border border-emerald-500/25 shadow-xl p-6 sm:p-8 md:p-10 text-white">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none hidden md:block">
                        <span class="material-symbols-outlined text-[160px]">school</span>
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6 sm:gap-8 text-center md:text-left">
                        <!-- Large Avatar with Google badge ring -->
                        <div class="relative shrink-0">
                            <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full p-1 border-2 border-dashed border-amber-300/60 shadow-2xl relative">
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
                            <!-- Photo Upload Trigger -->
                            <label for="avatar-file-input" class="absolute bottom-0 left-0 w-8 h-8 rounded-full bg-slate-900/90 hover:bg-slate-900 text-amber-300 border border-amber-400/40 shadow-lg flex items-center justify-center cursor-pointer transition-transform hover:scale-110" title="Upload Custom Profile Photo">
                                <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                            </label>
                            <form id="avatar-upload-form" method="POST" enctype="multipart/form-data" class="hidden">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                <input type="file" id="avatar-file-input" name="profile_avatar" accept="image/*" onchange="document.getElementById('avatar-upload-form').submit();">
                            </form>

                            <!-- Google Sync indicator -->
                            <div class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-white border border-gray-200 shadow-lg flex items-center justify-center" title="Synced with Google Account">
                                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-4 h-4">
                            </div>
                        </div>

                        <!-- Name & Core Credentials -->
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold font-mono uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                    STUDENT PORTAL
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold font-mono uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white/90 border border-white/15">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Active Academic Term
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

                            <!-- Course and Section Subheading -->
                            <p class="text-sm sm:text-base text-white/85 font-medium pt-1">
                                <span class="text-amber-300 font-bold"><?= htmlspecialchars($programCode) ?></span> — <?= htmlspecialchars($programName) ?> 
                                <span class="text-white/40 mx-2">|</span> 
                                Section: <span class="px-2 py-0.5 rounded-md bg-white/15 font-mono font-bold text-white"><?= htmlspecialchars($section) ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ Section 2: 4-Metric Academic Quick Cards ══════════ -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Course / Degree</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">school</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary"><?= htmlspecialchars($programCode) ?></p>
                            <p class="text-[11px] text-on-surface-variant truncate"><?= htmlspecialchars($programName) ?></p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Assigned Section</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">groups</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary"><?= htmlspecialchars($section) ?></p>
                            <p class="text-[11px] text-on-surface-variant"><?= $yearLevel ?><?= ($yearLevel == 1 ? 'st' : ($yearLevel == 2 ? 'nd' : ($yearLevel == 3 ? 'rd' : 'th'))) ?> Year Level</p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Student Number</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">badge</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold font-mono text-primary truncate"><?= htmlspecialchars($studentNumber) ?></p>
                            <p class="text-[11px] text-on-surface-variant font-mono">Verified ID</p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-on-surface-variant mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider">Enrolled Load</span>
                            <span class="material-symbols-outlined text-primary text-[20px]">menu_book</span>
                        </div>
                        <div>
                            <p class="text-lg sm:text-xl font-bold text-primary"><?= count($enrolledClasses) ?> Subjects</p>
                            <p class="text-[11px] text-on-surface-variant font-mono"><?= $totalUnits ?> Total Units</p>
                        </div>
                    </div>
                </div>

                <!-- ══════════ Section 3: Two Column Academic Details ══════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left: Official Academic Dossier (2 Cols) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Academic Record Card -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 sm:p-8 shadow-sm space-y-6">
                            <div class="flex items-center justify-between border-b border-outline-variant pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[22px]">account_box</span>
                                    </div>
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-primary">Academic Registration Details</h3>
                                        <p class="text-xs text-on-surface-variant">Official student record under Registrar's Office</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono text-xs font-bold border border-emerald-500/20">
                                    VERIFIED
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Full Legal Name</p>
                                    <p class="text-sm font-semibold text-primary"><?= htmlspecialchars($fullName) ?></p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Student Number</p>
                                    <p class="text-sm font-mono font-bold text-primary"><?= htmlspecialchars($studentNumber) ?></p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Academic Program / Degree</p>
                                    <p class="text-sm font-semibold text-primary"><?= htmlspecialchars($programName) ?> (<?= htmlspecialchars($programCode) ?>)</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Section & Year Level</p>
                                    <p class="text-sm font-semibold text-primary">Section <?= htmlspecialchars($section) ?> (Year <?= $yearLevel ?>)</p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Enrollment Status</p>
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <?= htmlspecialchars($status) ?> (Regular Student)
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-mono uppercase tracking-wider text-on-surface-variant">Current Academic Term</p>
                                    <p class="text-sm font-semibold text-primary">1st Semester, Academic Year 2026–2027</p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Enrolled Subjects Card -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 sm:p-8 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-outline-variant pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-400/10 text-amber-500 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[22px]">auto_stories</span>
                                    </div>
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-primary">Class Schedule & Enrolled Subjects</h3>
                                        <p class="text-xs text-on-surface-variant">Official subjects registered for Section <?= htmlspecialchars($section) ?></p>
                                    </div>
                                </div>
                                <a href="/student/schedule.php" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                                    Full Schedule <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>

                            <?php if (count($enrolledClasses) > 0): ?>
                                <div class="divide-y divide-outline-variant">
                                    <?php foreach ($enrolledClasses as $cls): ?>
                                        <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-surface-container-low px-2 rounded-xl transition-colors">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary font-mono text-xs font-bold border border-primary/20">
                                                        <?= htmlspecialchars($cls['code'] ?? 'SUBJ') ?>
                                                    </span>
                                                    <span class="text-sm font-bold text-primary"><?= htmlspecialchars($cls['title'] ?? 'Subject') ?></span>
                                                </div>
                                                <p class="text-xs text-on-surface-variant flex items-center gap-3">
                                                    <span><strong class="text-on-surface">Instructor:</strong> <?= htmlspecialchars($cls['instructor'] ?? 'Assigned Faculty') ?></span>
                                                    <span>•</span>
                                                    <span><strong class="text-on-surface">Room:</strong> <?= htmlspecialchars($cls['room'] ?? 'TBA') ?></span>
                                                </p>
                                            </div>
                                            <div class="text-left sm:text-right shrink-0">
                                                <p class="text-xs font-mono font-semibold text-primary">
                                                    <?= htmlspecialchars($cls['schedule_day'] ?? 'Mon/Wed') ?> 
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
                                    <p class="text-sm font-medium">No class records currently assigned to Section <?= htmlspecialchars($section) ?>.</p>
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
                                    Your profile photo and institutional credentials are automatically synchronized via official NPC Google Workspace SSO.
                                </p>
                            </div>
                        </div>

                        <!-- Quick Navigation Actions -->
                        <div class="rounded-3xl bg-surface-container-lowest border border-outline-variant p-6 shadow-sm space-y-3">
                            <h3 class="text-sm font-bold text-primary mb-3">Portal Shortcuts</h3>

                            <a href="/student/index.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">dashboard</span>
                                <span class="text-xs font-bold flex-1">Student Dashboard</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/student/academic.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">school</span>
                                <span class="text-xs font-bold flex-1">Academic Grades & Records</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/student/qrcode.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
                                <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">qr_code_scanner</span>
                                <span class="text-xs font-bold flex-1">Scan Attendance QR</span>
                                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                            </a>

                            <a href="/student/campus_map.php" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container hover:bg-primary/10 text-primary transition-all group">
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
