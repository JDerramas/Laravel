<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * _sidebar.php — Unified Global Navigation Component for NPC LMS
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Architecture & Purpose:
 *   This file serves as the single source of truth for navigation across all
 *   three dedicated portal ecosystems:
 *     1. Administrator Portal (`/admin/...`)
 *     2. Faculty Portal (`/teacher/...`)
 *     3. Student Portal (`/student/...`)
 *
 * Key Capabilities:
 *   - Auto-detects the currently visited URL and highlights the matching link.
 *   - Dynamically resolves legacy aliases and nested URLs to their parent menu items.
 *   - Renders a persistent navy desktop sidebar and a responsive slide-over drawer for mobile devices.
 *   - Enforces role-based visibility so students and teachers only see their own navigation,
 *     while administrators are provided an optional preview switcher.
 *
 * Usage Example:
 *   $NPC_PORTAL = 'admin'; // 'admin' | 'faculty' | 'student'
 *   include __DIR__ . '/_sidebar.php';
 */

// Initialize session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Security Check: Direct Browser Access Prevention
 * Prevents unauthorized users from requesting /includes/_sidebar.php directly in the browser address bar.
 * This component is intended strictly for server-side `include` or `require`.
 */
if (basename($_SERVER['PHP_SELF']) === '_sidebar.php') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        exit('Forbidden: Direct access to component files is prohibited.');
    }
}

// Normalize the active portal context ('teacher' is canonically represented as 'faculty')
$NPC_PORTAL = isset($NPC_PORTAL) ? $NPC_PORTAL : 'student';
if ($NPC_PORTAL === 'teacher') {
    $NPC_PORTAL = 'faculty';
}

// Extract current session identity details
$npcUserName  = $_SESSION['name'] ?? 'User';
$npcUserEmail = $_SESSION['email'] ?? '';
$npcInitial   = strtoupper(substr($npcUserName, 0, 1));
$curSelf      = $_SERVER['PHP_SELF'] ?? '';
$curBase      = basename($curSelf);

/**
 * ─── Portal Navigation Registry ──────────────────────────────────────────────
 * Defines the complete navigational structure and icons for each user role.
 * Icons correspond to Google Material Symbols Outlined.
 */
$NPC_MENUS = [
    // Administrator & Registrar navigation items
    'admin' => [
        ['href' => '/admin/index.php',                      'icon' => 'dashboard',        'label' => 'Dashboard'],
        ['href' => '/admin/academic/lms.php',               'icon' => 'menu_book',        'label' => 'Master LMS Hub'],
        ['href' => '/admin/academic/grades.php',            'icon' => 'verified',         'label' => 'Grade Approvals'],
        ['href' => '/admin/academic/classes.php',           'icon' => 'school',           'label' => 'Classes & Rosters'],
        ['href' => '/admin/academic/schedules.php',         'icon' => 'calendar_month',   'label' => 'Student Schedules'],
        ['href' => '/admin/academic/attendance.php',        'icon' => 'qr_code_scanner',  'label' => 'Live Attendance QR'],
        ['href' => '/admin/academic/students.php',          'icon' => 'group',            'label' => 'Student Directory'],
        ['href' => '/admin/academic/programs.php',          'icon' => 'account_tree',     'label' => 'Courses & Programs'],
        ['href' => '/admin/security/users.php',             'icon' => 'manage_accounts',  'label' => 'User Management'],
        ['href' => '/admin/security/audit.php',             'icon' => 'security',         'label' => 'Security Audit'],
        ['href' => '/admin/communication/announcements.php', 'icon' => 'campaign',        'label' => 'Announcements'],
        ['href' => '/admin/communication/docs.php',         'icon' => 'description',      'label' => 'Documents & AI'],
        ['href' => '/admin/campus_map.php',                 'icon' => 'map',              'label' => 'NPC Map'],
        ['href' => '/admin/system/settings.php',            'icon' => 'settings',         'label' => 'System Settings'],
        ['href' => '/admin/system/reports.php',             'icon' => 'analytics',        'label' => 'Reports'],
    ],

    // Faculty (Teacher) portal navigation items
    'faculty' => [
        ['href' => '/teacher/index.php',                    'icon' => 'dashboard',       'label' => 'Dashboard'],
        ['href' => '/teacher/campus_map.php',               'icon' => 'map',             'label' => 'NPC Map'],
        ['href' => '/teacher/courses.php',                  'icon' => 'menu_book',       'label' => 'Courses & Modules Hub'],
        ['href' => '/teacher/classes.php',                  'icon' => 'school',          'label' => 'My Assigned Classes'],
        ['href' => '/teacher/attendance.php',               'icon' => 'qr_code_scanner', 'label' => 'Live Attendance QR'],
        ['href' => '/teacher/grades.php',                   'icon' => 'grade',           'label' => 'Grade Encoding'],
        ['href' => '/teacher/ai_assistant.php',             'icon' => 'smart_toy',       'label' => 'Teaching Assistant AI'],
        ['href' => '/teacher/profile.php',                  'icon' => 'account_circle',  'label' => 'My Profile'],
    ],

    // Student portal navigation items
    'student' => [
        ['href' => '/student/index.php',                    'icon' => 'dashboard',       'label' => 'Dashboard'],
        ['href' => '/student/profile.php',                  'icon' => 'account_circle',  'label' => 'My Profile'],
        ['href' => '/student/campus_map.php',               'icon' => 'map',             'label' => 'NPC Map'],
        ['href' => '/student/courses.php',                  'icon' => 'menu_book',       'label' => 'Courses & LMS'],
        ['href' => '/student/academic.php',                 'icon' => 'school',          'label' => 'Academic & Grades'],
        ['href' => '/student/ai_assistant.php',             'icon' => 'smart_toy',       'label' => 'AI Study Tutor'],
        ['href' => '/student/schedule.php',                 'icon' => 'calendar_month',  'label' => 'Schedule'],
        ['href' => '/student/qrcode.php',                   'icon' => 'qr_code_scanner', 'label' => 'Scan QR Code'],
    ],
];

// Select menu set based on active portal context
$items = $NPC_MENUS[$NPC_PORTAL] ?? $NPC_MENUS['student'];

/**
 * Portal branding metadata: subtitle and primary icon displayed below the college logo.
 */
$PORTAL_META = [
    'admin'   => ['LMS Admin Center',     'admin_panel_settings'],
    'faculty' => ['LMS Faculty Portal',   'cast_for_education'],
    'student' => ['LMS Student Portal',   'school'],
];
$pm = $PORTAL_META[$NPC_PORTAL] ?? $PORTAL_META['student'];

/**
 * Sub-Page Aliases:
 * Maps child pages and sub-modules to their corresponding parent sidebar menu link,
 * ensuring the parent link stays highlighted when the user navigates into child routes.
 */
$ALIASES = [
    'courses.php' => '/student/courses.php',
    'lms.php' => '/admin/academic/lms.php',
    'elms.php' => '/admin/academic/lms.php',
    'class_view.php' => '/admin/academic/classes.php',
    'admin_class_view.php' => '/admin/academic/classes.php',
    'admin_classes.php' => '/admin/academic/classes.php',
    'admin_grades.php' => '/admin/academic/grades.php',
    'admin_schedules.php' => '/admin/academic/schedules.php',
    'admin_attendance.php' => '/admin/academic/attendance.php',
    'admin_students.php' => '/admin/academic/students.php',
    'programs.php' => '/admin/academic/programs.php',
    'admin_programs.php' => '/admin/academic/programs.php',
    'admin_users.php' => '/admin/security/users.php',
    'admin_audit.php' => '/admin/security/audit.php',
    'admin_announcements.php' => '/admin/communication/announcements.php',
    'admin_docs.php' => '/admin/communication/docs.php',
    'admin_settings.php' => '/admin/system/settings.php',
    'admin_reports.php' => '/admin/system/reports.php',
    'admin.php' => '/admin/index.php',
    'teacher.php' => '/teacher/index.php',
    'teacher_classes.php' => '/teacher/classes.php',
    'teacher_attendance.php' => '/teacher/attendance.php',
    'teacher_grades.php' => '/teacher/grades.php',
    'teacher_ai_assistant.php' => '/teacher/ai_assistant.php',
    'campus_map.php' => ($NPC_PORTAL === 'admin') ? '/admin/campus_map.php' : (($NPC_PORTAL === 'faculty') ? '/teacher/campus_map.php' : '/student/campus_map.php'),
];

/**
 * Function: isItemActive
 *
 * Evaluates whether a sidebar navigation item should be marked as "active" (visually highlighted).
 *
 * Detailed Logic:
 *   1. Direct Path Match: Checks if the current request script exactly equals the menu item link.
 *   2. Basename Match: Checks if the filename matches (e.g. `index.php`), while disambiguating
 *      multi-portal pages such as `campus_map.php` and `profile.php` by verifying the directory path.
 *   3. Aliases Lookup: Checks the `$ALIASES` dictionary to highlight parent tabs when a sub-feature is open.
 *
 * @param string $itemHref  The destination URL defined on the sidebar menu item.
 * @param string $curSelf   The current page's $_SERVER['PHP_SELF'] path.
 * @param string $curBase   The filename of the current page (e.g. 'index.php').
 * @param array  $ALIASES   Dictionary mapping sub-pages to parent navigation links.
 * @return bool             Returns true if the item is currently active, false otherwise.
 */
if (!function_exists('isItemActive')) {
    function isItemActive(string $itemHref, string $curSelf, string $curBase, array $ALIASES): bool
    {
        // Case 1: Exact URL match
        if ($curSelf === $itemHref) {
            return true;
        }

        // Case 2: Matching filename
        if ($curBase === basename($itemHref)) {
            // Disambiguate pages existing across multiple portals (e.g. campus_map.php and profile.php)
            if (basename($itemHref) === 'campus_map.php' || basename($itemHref) === 'profile.php') {
                return dirname($curSelf) === dirname($itemHref);
            }
            return true;
        }

        // Case 3: Alias match for sub-features
        if (isset($ALIASES[$curBase]) && $ALIASES[$curBase] === $itemHref) {
            return true;
        }

        return false;
    }
}
?>
<!-- ══════════ NPC Shared Sidebar ══════════ -->
<!-- Self-contained critical styles: sidebar renders correctly even if
     the main stylesheet is cached or fails to load. -->
<style>
    #npc-sidebar.npc-side-nav {
        background: radial-gradient(600px 300px at -20% 0%, var(--nav-glow-rgba, rgba(234, 179, 8, .16)), transparent 60%), rgb(var(--nav-bg-rgb, 1 36 21));
        border-right: 1px solid rgba(255, 255, 255, .06)
    }

    #npc-sidebar .npc-brand-title {
        color: #fff
    }

    #npc-sidebar .npc-brand-sub {
        color: rgba(167, 243, 208, .85)
    }

    #npc-sidebar .npc-side-link {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .7rem 1rem;
        border-radius: .75rem;
        font-size: .875rem;
        font-weight: 500;
        color: rgba(209, 250, 229, .95);
        transition: background-color .18s ease, color .18s ease, transform .18s ease
    }

    #npc-sidebar .npc-side-link:hover {
        background: rgba(255, 255, 255, .10);
        color: #fff;
        transform: translateX(2px)
    }

    #npc-sidebar .npc-side-link.active {
        background: rgb(var(--secondary-container-rgb, 254 212 136));
        color: rgb(var(--on-secondary-container-rgb, 78 58 12));
        font-weight: 600;
        box-shadow: 0 4px 14px -4px rgba(254, 212, 136, .45)
    }

    @media (max-width: 1023px) {
        #npc-sidebar.npc-drawer {
            display: flex !important;
            transform: translateX(-100%) !important;
            transition: transform .3s cubic-bezier(.22, .68, .32, 1) !important;
            z-index: 50 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important
        }

        #npc-sidebar.npc-drawer.open {
            transform: translateX(0) !important
        }
    }
</style>
<nav id="npc-sidebar" class="npc-side-nav npc-drawer hidden lg:flex flex-col w-64 h-screen fixed left-0 top-0 z-40 py-6 shadow-md">
    <!-- Mobile Close Button -->
    <div class="lg:hidden absolute top-4 right-4 z-50">
        <button type="button" onclick="toggleNpcSidebar()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>

    <div class="px-6 mb-8 flex flex-col items-center">
        <img class="w-16 h-16 rounded-full mb-3 object-contain bg-white p-1 shadow-sm border border-emerald-500/30" alt="NPC Emblem"
            src="/assets/img/npc-logo.png">
        <h1 class="text-xl font-bold tracking-tight text-center npc-brand-title">NPC LMS</h1>
        <p class="text-[10px] font-mono uppercase tracking-wider mt-0.5 text-center font-bold text-amber-300">Navotas Polytechnic College</p>
        <p class="text-[10px] font-mono uppercase tracking-wider mt-0.5 npc-brand-sub"><?= $pm[0] ?></p>
    </div>

    <?php 
    /**
     * Role & Privilege Synchronization:
     *   - `$npcUserRole`: Represents the active role of the current portal view.
     *   - `$npcBaseRole`: Represents the authoritative base account role in the database.
     *   - `$isAdminUser`: True only if the base account is a verified Administrator or Registrar.
     * 
     * Security Guarantee:
     *   Even if a user manually changes URL parameters, this check strictly validates against
     *   the authenticated baseline session to prevent non-admins from rendering the Admin Switcher.
     */
    $npcUserRole = $_SESSION['role'] ?? $_SESSION['base_role'] ?? 'student';
    $npcBaseRole = $_SESSION['base_role'] ?? $npcUserRole;
    $isAdminUser = ($npcBaseRole === 'admin' || $npcBaseRole === 'registrar');
    ?>
    <?php if ($isAdminUser): ?>
        <!-- ══════════ Administrator Portal Switcher ══════════ -->
        <!-- Exclusively rendered for verified administrators to preview student and faculty views -->
        <div class="px-4 mb-3 pb-3 border-b border-white/10">
            <p class="text-[10px] font-mono uppercase tracking-widest text-white/60 mb-2 px-1 text-center font-semibold">Admin Switcher</p>
            <div class="grid grid-cols-3 gap-1 bg-white/10 p-1 rounded-xl border border-white/15 text-center text-xs font-semibold">
                <a href="/switch_portal.php?to=student" class="py-1.5 px-1 rounded-lg transition-colors <?= ($NPC_PORTAL === 'student') ? 'bg-amber-400 text-slate-950 font-bold shadow-sm' : 'text-blue-100 hover:bg-white/15' ?>" title="Student Portal">
                    Student
                </a>
                <a href="/switch_portal.php?to=teacher" class="py-1.5 px-1 rounded-lg transition-colors <?= ($NPC_PORTAL === 'faculty') ? 'bg-amber-400 text-slate-950 font-bold shadow-sm' : 'text-blue-100 hover:bg-white/15' ?>" title="Faculty Portal">
                    Faculty
                </a>
                <a href="/switch_portal.php?to=admin" class="py-1.5 px-1 rounded-lg transition-colors <?= ($NPC_PORTAL === 'admin') ? 'bg-amber-400 text-slate-950 font-bold shadow-sm' : 'text-blue-100 hover:bg-white/15' ?>" title="Admin Portal">
                    Admin
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ══════════ Navigation Links Area ══════════ -->
    <div class="flex-1 px-4 space-y-1.5 font-medium text-sm overflow-y-auto" id="npc-side-links">
        <?php foreach ($items as $it):
            $isActive = isItemActive($it['href'], $curSelf, $curBase, $ALIASES); ?>
            <a href="<?= $it['href'] ?>"
                class="npc-side-link<?= $isActive ? ' active' : '' ?>">
                <span class="material-symbols-outlined text-[20px]"><?= $it['icon'] ?></span>
                <span><?= $it['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php
    /**
     * User Profile Badge & Footer Configurations:
     * Resolves the profile destination link, user avatar, and subtitle text (Section vs. Faculty Member).
     */
    $profileLink = ($NPC_PORTAL === 'faculty') ? '/teacher/profile.php' : '/student/profile.php';
    $userAvatarSide = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
    $userSecOrRole = !empty($_SESSION['section']) ? 'Section ' . $_SESSION['section'] : ucfirst($npcUserRole);
    if ($npcUserRole === 'teacher' || $npcUserRole === 'faculty') {
        $userSecOrRole = 'Faculty Member';
    }
    ?>
    <!-- ══════════ Footer Profile & Sign Out ══════════ -->
    <div class="px-4 mt-auto pt-3 border-t border-white/10 space-y-1.5">
        <a href="<?= $profileLink ?>" class="flex items-center gap-3 p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all group" title="View My Profile">
            <?php if (!empty($userAvatarSide)): ?>
                <img src="<?= htmlspecialchars($userAvatarSide) ?>" alt="<?= htmlspecialchars($npcUserName) ?>" class="w-8 h-8 rounded-full object-cover border border-amber-300/40 shadow-sm shrink-0" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-slate-950 font-bold text-xs items-center justify-center shadow-sm shrink-0 hidden">
                    <?= $npcInitial ?>
                </div>
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center shadow-sm shrink-0">
                    <?= $npcInitial ?>
                </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate group-hover:text-amber-300 transition-colors"><?= htmlspecialchars($npcUserName) ?></p>
                <p class="text-[10px] font-mono text-emerald-200/70 truncate"><?= htmlspecialchars($userSecOrRole) ?></p>
            </div>
            <span class="material-symbols-outlined text-[16px] text-white/40 group-hover:text-amber-300 group-hover:translate-x-0.5 transition-all">chevron_right</span>
        </a>
        <a href="/logout.php" class="npc-side-link hover:!bg-red-500/20 text-red-300 hover:!text-red-200 transition-colors">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span>Sign Out</span>
        </a>
    </div>
</nav>

<!-- ══════════ Mobile Drawer Overlay ══════════ -->
<!-- Semi-transparent backdrop enabling tap-to-dismiss functionality on mobile devices -->
<div id="npc-drawer-overlay" onclick="toggleNpcSidebar()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[45] hidden lg:hidden"></div>

<!-- ══════════ Mobile Drawer Controller Script ══════════ -->
<script>
    /**
     * Function: toggleNpcSidebar
     * 
     * Toggles the mobile slide-out navigation drawer and its backdrop overlay.
     * When opened, sets the CSS `.open` class to animate into view from the left.
     * When closed, hides both the drawer and overlay from the viewport.
     */
    function toggleNpcSidebar() {
        const sb = document.getElementById('npc-sidebar');
        const ov = document.getElementById('npc-drawer-overlay');
        if (sb) {
            sb.classList.toggle('open');
            sb.classList.toggle('hidden');
        }
        if (ov) {
            ov.classList.toggle('hidden');
        }
    }
</script>