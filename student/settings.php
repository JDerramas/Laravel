<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
// Refresh session-derived identity after the hardened guard
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$full_name = $is_logged_in ? (string)$_SESSION['name'] : 'Guest User';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
// CSRF token for the Profile Update Request API
if (!function_exists('getCsrfToken')) {
    require_once __DIR__ . '/../includes/db_helper.php';
}
$npc_csrf = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NPC Connect - Settings & Profile</title>
    <!-- Tailwind CSS CDN -->
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
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js"></script>
    <script>if (typeof THREE === 'undefined') { const s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js'; document.head.appendChild(s); }</script>
    <script src="/assets/js/npc-three.js"></script>
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
    </script>
    <style>
        .scanner-frame { position: relative; overflow: hidden; }
        /* Toggle Switch */
        .toggle-checkbox:checked { right: 0; border-color: #68D391; }
        .toggle-checkbox:checked + .toggle-label { background-color: #001736; }
    </style>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar (Sticky Desktop navigation) -->
        <?php $NPC_PORTAL = 'student'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC Connect</span>
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Settings</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <span class="font-mono text-[11px] sm:text-xs font-semibold bg-surface-container px-2 sm:px-3 py-1.5 rounded-md border border-outline-variant text-primary hidden sm:inline shrink-0" id="user-id-chip">ID: <?php echo htmlspecialchars($user_id_display); ?></span>
                    <?php 
                    $userAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                    if (!empty($userAvatar)): ?>
                        <img alt="<?php echo htmlspecialchars($full_name); ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-outline-variant shadow-sm shrink-0"
                            src="<?php echo htmlspecialchars($userAvatar); ?>" referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-primary hidden md:inline" id="user-name-display"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 overflow-y-auto px-margin-mobile md:px-margin-desktop py-12 pb-32">
<!-- Header Section -->
<div class="mb-10 max-w-container-max mx-auto">
<h1 class="font-display-lg text-display-lg text-on-surface mb-2">Account Settings</h1>
<p class="font-body-lg text-body-lg text-on-surface-variant">Manage your personal information, security protocols, and notification preferences.</p>
</div>
<!-- Main Layout Grid -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-gutter max-w-container-max mx-auto">
<!-- Inner Sidebar Navigation -->
<nav class="col-span-1 md:col-span-3 flex flex-row md:flex-col gap-2 overflow-x-auto md:overflow-visible pb-4 md:pb-0 hide-scrollbar">
<a class="flex items-center gap-3 px-4 py-3 bg-surface-container-lowest text-primary font-bold rounded-xl border border-outline-variant shadow-[0_2px_4px_rgba(0,0,0,0.04)] whitespace-nowrap shrink-0" href="#profile">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
                        Profile
                    </a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface rounded-xl transition-colors whitespace-nowrap shrink-0" href="#security">
<span class="material-symbols-outlined">lock</span>
                        Security
                    </a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface rounded-xl transition-colors whitespace-nowrap shrink-0" href="#notifications">
<span class="material-symbols-outlined">notifications_active</span>
                        Notifications
                    </a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface rounded-xl transition-colors whitespace-nowrap shrink-0" href="#privacy">
<span class="material-symbols-outlined">visibility_off</span>
                        Privacy
                    </a>
</nav>
<!-- Content Panels -->
<div class="col-span-1 md:col-span-9 flex flex-col gap-8">
<!-- Profile Section -->
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8" id="profile">
<div class="mb-8 border-b border-outline-variant/40 pb-4">
<h2 class="font-headline-md text-headline-md text-on-surface">Profile Information</h2>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Update your display details and contact information.</p>
</div>
<!-- Avatar Upload -->
<div class="flex items-center gap-6 mb-8">
<div class="relative group cursor-pointer">
<div class="w-24 h-24 rounded-full overflow-hidden border border-outline-variant group-hover:border-primary transition-colors">
<img alt="Profile Picture" class="w-full h-full object-cover" data-alt="A close-up, high-quality photograph of an empty, modern geometric picture frame or avatar placeholder graphic. The setting is a clean, bright white studio environment, reflecting a polished, minimalist digital interface aesthetic appropriate for a premium web application profile page." src="https://lh3.googleusercontent.com/aida-public/AB6AXuB7LETJXrYL19yJRsVwtkpIi9FTewRCFdcseQt11Hu-C02K6v4DoRcL6T47L0ZNo2kaE1wRtbw5Nm2UJrmCWYCaX8UUxCvrRnECas0v5iAsOv8ProCmLnThZWX-sU-2jXFWO_G1ZOV8m6ED3K3UBwVVUoN6f96OYu8Jwuo2y9wwVyR9fX_G0_ALadot6KzSG0QgvGG8JMlkevCvEQSg23uS2-1HIKcVxvh7g_p5s1mWFPVSRQ6CqsWAtg"/>
</div>
<div class="absolute inset-0 bg-primary/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
<span class="material-symbols-outlined text-on-primary">photo_camera</span>
</div>
</div>
<div>
<button class="px-4 py-2 border border-outline hover:border-primary text-on-surface hover:text-primary font-label-md text-label-md rounded-lg transition-colors bg-surface flex items-center gap-2">
<span class="material-symbols-outlined text-sm">upload</span>
                                    Upload New Picture
                                </button>
<p class="text-label-sm font-label-sm text-on-surface-variant mt-2">JPG, GIF or PNG. Max size of 800K</p>
</div>
</div>
<!-- Form Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Display Name</label>
<input class="w-full bg-surface-subtle border border-outline-variant/50 rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface-variant outline-none cursor-not-allowed" disabled type="text" value="<?= htmlspecialchars($full_name) ?>"/>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Primary Role</label>
<input class="w-full bg-surface-subtle border border-outline-variant/50 rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface-variant outline-none cursor-not-allowed" disabled="" type="text" value="Undergraduate Student"/>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Student Number</label>
<input class="w-full bg-surface-subtle border border-outline-variant/50 rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface-variant outline-none cursor-not-allowed font-mono" disabled type="text" value="<?= htmlspecialchars($user_id_display) ?>"/>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">NPC Google Account</label>
<input class="w-full bg-surface-subtle border border-outline-variant/50 rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface-variant outline-none cursor-not-allowed" disabled type="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"/>
</div>
<div class="flex flex-col gap-2 md:col-span-1">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Phone Number</label>
<input id="pu-phone" class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="+63 9XX XXX XXXX" type="tel"/>
</div>
<div class="flex flex-col gap-2 md:col-span-1">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Section Correction (optional)</label>
<input id="pu-section" class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="e.g. AIS 2B — if incorrectly registered" type="text"/>
</div>
<div class="flex flex-col gap-2 md:col-span-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Address Update (optional)</label>
<input id="pu-address" class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="New home address if changed" type="text"/>
</div>
<div class="flex flex-col gap-2 md:col-span-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Guardian Update (optional)</label>
<input id="pu-guardian" class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="Guardian name + contact number" type="text"/>
</div>
<div class="flex flex-col gap-2 md:col-span-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Emergency Contact (optional)</label>
<input id="pu-emergency" class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="Emergency contact person — name + mobile number" type="text"/>
</div>
<div class="flex flex-col gap-2 md:col-span-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Reason for Request <span class="text-error">*</span></label>
<textarea id="pu-reason" rows="3" required class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50 resize-y" placeholder="Explain why this change is necessary (required by the Registrar)."></textarea>
<p class="text-[11px] text-on-surface-variant mt-1 flex items-start gap-1"><span class="material-symbols-outlined text-[14px] mt-0.5 shrink-0">info</span> Official record fields are locked for data integrity. The information entered here will be submitted as an <strong>update request</strong> to the Registrar for verification — it will not change automatically.</p>
</div>
<div class="md:col-span-2 flex justify-end">
<button onclick="submitProfileUpdateRequest()" id="pu-submit-btn" class="px-5 py-2.5 rounded-lg bg-primary text-white font-label-md font-bold hover:bg-primary/90 shadow-sm transition-all active:scale-95 flex items-center gap-2">
<span class="material-symbols-outlined text-sm">send</span> Submit Update Request
</button>
</div>
</div>
<div id="pu-success-banner" class="hidden mt-6 rounded-xl border border-status-success/30 bg-status-success/10 p-4 flex items-start gap-3">
<span class="material-symbols-outlined text-status-success text-[20px] shrink-0">check_circle</span>
<div class="text-xs text-on-surface leading-relaxed">
<p class="font-bold text-status-success">Profile update request submitted!</p>
<p class="mt-0.5 text-on-surface-variant">You will receive a notification once this has been reviewed by the Registrar. You can also check the bell icon above for status updates.</p>
</div>
</div>
</section>
<!-- Security Section -->
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8" id="security">
<div class="mb-8 border-b border-outline-variant/40 pb-4 flex justify-between items-end">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Security Settings</h2>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage your password and authentication methods.</p>
</div>
<span class="material-symbols-outlined text-on-surface-variant text-4xl opacity-20">shield_lock</span>
</div>
<!-- Change Password -->
<div class="mb-10">
<h3 class="font-body-lg text-body-lg text-on-surface font-semibold mb-4">Change Password</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="flex flex-col gap-2 md:col-span-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Current Password</label>
<input class="w-full md:w-1/2 bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="••••••••" type="password"/>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">New Password</label>
<input class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="••••••••" type="password"/>
</div>
<div class="flex flex-col gap-2">
<label class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Confirm New Password</label>
<input class="w-full bg-surface border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary rounded-lg px-4 py-3 text-body-md font-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50" placeholder="••••••••" type="password"/>
</div>
</div>
</div>
<!-- Two-Factor Authentication -->
<div class="bg-surface-subtle border border-outline-variant/30 rounded-lg p-6 flex items-center justify-between">
<div class="pr-8">
<h3 class="font-body-lg text-body-lg text-on-surface font-semibold mb-1">Two-Factor Authentication (2FA)</h3>
<p class="font-body-md text-body-md text-on-surface-variant">Add an extra layer of security to your account by requiring more than just a password to sign in.</p>
</div>
<!-- Custom Toggle -->
<div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in shrink-0">
<input checked="" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-outline-variant z-10 top-0 left-0 transition-all duration-300" id="toggle-2fa" name="toggle" type="checkbox"/>
<label class="toggle-label block overflow-hidden h-6 rounded-full bg-outline-variant/30 cursor-pointer transition-colors duration-300" for="toggle-2fa"></label>
</div>
</div>
</section>
<!-- Notifications Section -->
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8" id="notifications">
<div class="mb-8 border-b border-outline-variant/40 pb-4">
<h2 class="font-headline-md text-headline-md text-on-surface">Notification Preferences</h2>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Control how and when you receive updates from NPC Connect.</p>
</div>
<div class="flex flex-col gap-6">
<!-- Setting Item 1 -->
<div class="flex items-start md:items-center justify-between border-b border-outline-variant/20 pb-6 gap-4">
<div class="flex-1">
<h3 class="font-body-lg text-body-lg text-on-surface font-semibold">Academic Alerts</h3>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Receive immediate updates for new grades, assignments, and attendance warnings.</p>
</div>
<div class="flex flex-col md:flex-row gap-4 shrink-0">
<label class="flex items-center gap-2 cursor-pointer group">
<input checked="" class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Email</span>
</label>
<label class="flex items-center gap-2 cursor-pointer group">
<input checked="" class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Push</span>
</label>
</div>
</div>
<!-- Setting Item 2 -->
<div class="flex items-start md:items-center justify-between border-b border-outline-variant/20 pb-6 gap-4">
<div class="flex-1">
<h3 class="font-body-lg text-body-lg text-on-surface font-semibold">Institutional News</h3>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Updates regarding campus events, administrative announcements, and policy changes.</p>
</div>
<div class="flex flex-col md:flex-row gap-4 shrink-0">
<label class="flex items-center gap-2 cursor-pointer group">
<input checked="" class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Email</span>
</label>
<label class="flex items-center gap-2 cursor-pointer group">
<input class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Push</span>
</label>
</div>
</div>
<!-- Setting Item 3 -->
<div class="flex items-start md:items-center justify-between pb-2 gap-4">
<div class="flex-1">
<h3 class="font-body-lg text-body-lg text-on-surface font-semibold flex items-center gap-2">
                                        AI Assistant Updates
                                        <span class="px-2 py-0.5 bg-surface-container text-primary text-xs font-bold rounded uppercase tracking-wider">New</span>
</h3>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Weekly summaries and proactive study suggestions generated by your academic AI.</p>
</div>
<div class="flex flex-col md:flex-row gap-4 shrink-0">
<label class="flex items-center gap-2 cursor-pointer group">
<input class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Email</span>
</label>
<label class="flex items-center gap-2 cursor-pointer group">
<input checked="" class="form-checkbox h-5 w-5 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-surface-container-lowest transition-all" type="checkbox"/>
<span class="font-label-md text-on-surface group-hover:text-primary transition-colors">Push</span>
</label>
</div>
</div>
</div>
</section>
</div>
</div>
</main>
<!-- Floating Save Action Bar -->
<div class="fixed bottom-0 right-0 left-0 md:left-64 bg-surface-container-lowest/90 backdrop-blur-md border-t border-outline-variant/30 p-4 md:px-margin-desktop shadow-[0_-4px_24px_rgba(0,0,0,0.02)] flex justify-end gap-4 z-30">
<button class="px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface font-label-md hover:bg-surface-container-low transition-colors">
                Discard Changes
            </button>
<button class="px-8 py-2.5 rounded-lg bg-primary text-on-primary font-label-md font-bold hover:bg-primary/90 shadow-[0_2px_8px_rgba(0,23,54,0.2)] transition-all active:scale-95 flex items-center gap-2">
<span class="material-symbols-outlined text-sm">save</span>
                Save Changes
            </button>

        </div>
    </div>

    <script>
        /* Profile Update Request → api_student.php?action=request_profile_update
           Official fields stay locked; edits go to the Registrar as a request. */
        function submitProfileUpdateRequest() {
            var changes = {};
            var phone = document.getElementById('pu-phone').value.trim();
            var section = document.getElementById('pu-section').value.trim();
            var address = document.getElementById('pu-address').value.trim();
            var guardian = document.getElementById('pu-guardian').value.trim();
            var reason = document.getElementById('pu-reason').value.trim();

            if (phone) changes.contact_number = phone;
            if (section) changes.section_correction = section;
            if (address) changes.address = address;
            var emergency = document.getElementById('pu-emergency').value.trim();
            if (emergency) changes.emergency_contact = emergency;
            if (guardian) changes.guardian = guardian;

            if (!reason || Object.keys(changes).length === 0) {
                showToast('Please specify at least one change and provide a reason.', 'error');
                return;
            }

            var btn = document.getElementById('pu-submit-btn');
            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> Submitting…';

            fetch('/api/student.php?action=request_profile_update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': <?= json_encode($npc_csrf) ?> },
                body: JSON.stringify({ requested_changes: changes, reason: reason, csrf_token: <?= json_encode($npc_csrf) ?> })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    document.getElementById('pu-success-banner').classList.remove('hidden');
                    ['pu-phone', 'pu-section', 'pu-address', 'pu-guardian', 'pu-reason'].forEach(function (id) {
                        document.getElementById(id).value = '';
                    });
                    showToast('Profile update request sent to Registrar!', 'success');
                } else {
                    showToast(data.message || 'Request failed. Please try again.', 'error');
                }
            })
            .catch(function () { showToast('Network error. Please try again.', 'error'); })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            });
        }

        /* Minimal toast (matches app style; no external deps) */
        function showToast(msg, kind) {
            var existing = document.getElementById('pu-toast');
            if (existing) existing.remove();
            var t = document.createElement('div');
            t.id = 'pu-toast';
            t.className = 'fixed bottom-20 right-6 z-50 px-4 py-3 rounded-xl shadow-2xl text-xs font-semibold flex items-center gap-2 transition-all duration-300 ' +
                (kind === 'error' ? 'bg-error text-white' : 'bg-status-success text-white');
            t.innerHTML = '<span class="material-symbols-outlined text-[16px]">' + (kind === 'error' ? 'error' : 'check_circle') + '</span>' +
                String(msg).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            document.body.appendChild(t);
            setTimeout(function () { t.remove(); }, 4000);
        }
    </script>
</body>
</html>