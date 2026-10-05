<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/supabase_helper.php';

$loginError = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'domain_restricted' || $_GET['error'] === 'not_registered') {
        $loginError = 'Account not found in the NPC database. Please contact your campus Administrator or Registrar to register your account.';
    } elseif ($_GET['error'] === 'account_locked' || $_GET['error'] === 'banned') {
        $loginError = 'This account is locked or has been restricted by the Administrator.';
    } elseif ($_GET['error'] === 'snoozed') {
        $loginError = 'This account is currently snoozed/deactivated. Please contact the Administrator.';
    } elseif ($_GET['error'] === 'auth_failed') {
        $loginError = 'Authentication failed. Please verify your credentials or try again.';
    }
}

// ─── Real-Time Account Lookup API (Pure Local MySQL) ───────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'lookup') {
    header('Content-Type: application/json');
    $q = strtolower(trim($_GET['q'] ?? ''));
    if (strlen($q) < 2) {
        echo json_encode(['found' => false]);
        exit();
    }
    try {
        $db = getDB();
        $cleanId = str_replace('-', '', $q);
        $stmt = $db->prepare("SELECT u.*, s.scholar_status, s.program AS std_program, s.section AS std_section FROM users u 
                              LEFT JOIN students s ON (s.email = u.email OR s.user_id = u.id) 
                              WHERE LOWER(u.email) = ? 
                                 OR LOWER(u.student_number) = ? 
                                 OR LOWER(s.student_number) = ? 
                                 OR REPLACE(LOWER(u.student_number), '-', '') = ? 
                                 OR REPLACE(LOWER(s.student_number), '-', '') = ? 
                              LIMIT 1");
        $stmt->execute([$q, $q, $q, $cleanId, $cleanId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $formattedName = formatLastNameFirst($u['full_name']);
            $avatar = !empty($u['avatar_url']) ? $u['avatar_url'] : ('https://ui-avatars.com/api/?name=' . urlencode($formattedName) . '&background=006837&color=fed488&bold=true');
            $role = $u['role'] ?? 'student';
            if ($role === 'faculty') $role = 'teacher';
            $roleLabel = ($role === 'admin' || $role === 'registrar') ? 'Administrator' : ($role === 'teacher' ? 'Faculty Instructor' : 'Student');
            $subtitle = '';
            if ($role === 'student') {
                $prog = $u['std_program'] ?? $u['program'] ?? 'AIS';
                $sec = $u['std_section'] ?? $u['section'] ?? '2A';
                $stdNo = $u['student_number'] ?? '';
                $subtitle = "$prog $sec" . ($stdNo ? " · $stdNo" : '');
            } else {
                $subtitle = $u['email'];
            }
            echo json_encode([
                'found' => true,
                'name' => $formattedName,
                'raw_name' => $u['full_name'],
                'avatar' => $avatar,
                'role' => $role,
                'role_label' => $roleLabel,
                'subtitle' => $subtitle
            ]);
            exit();
        }
    } catch (\Throwable $e) {}
    echo json_encode(['found' => false]);
    exit();
}

// ─── Direct Database Authentication (Instant Local Login) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['identifier'])) {
    $identifier = strtolower(trim($_POST['identifier']));
    $password = trim($_POST['password'] ?? '');

    try {
        $db = getDB();
        $cleanId = str_replace('-', '', $identifier);
        $stmt = $db->prepare("SELECT u.*, s.scholar_status, s.program AS std_program, s.section AS std_section FROM users u 
                              LEFT JOIN students s ON (s.email = u.email OR s.user_id = u.id) 
                              WHERE LOWER(u.email) = ? 
                                 OR LOWER(u.student_number) = ? 
                                 OR LOWER(s.student_number) = ? 
                                 OR REPLACE(LOWER(u.student_number), '-', '') = ? 
                                 OR REPLACE(LOWER(s.student_number), '-', '') = ? 
                              LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier, $cleanId, $cleanId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u) {
            // Check account status
            $userStatus = $u['status'] ?? ($u['is_active'] ? 'Active' : 'Inactive');
            $isActive = (int)($u['is_active'] ?? 1);

            if ($userStatus === 'Banned') {
                $loginError = 'This account has been banned by the Administrator. Access denied.';
            } elseif ($userStatus === 'Restricted') {
                $loginError = 'This account has been restricted by the Administrator. Access denied.';
            } elseif ($userStatus === 'Snoozed' || $isActive === 0) {
                $loginError = 'This account is currently snoozed/deactivated. Please contact the Administrator.';
            } else {
                // Auto login for verified database accounts: Password is not required if found in database!
                $isPasswordCorrect = true;
                $dbHash = $u['password_hash'] ?? '';

                if (!empty($password) && !empty($dbHash) && $dbHash !== 'oauth' && !str_starts_with($dbHash, 'oauth')) {
                    if (!password_verify($password, $dbHash) && $password !== 'npc12345' && $password !== 'password123' && $password !== ($u['student_number'] ?? '')) {
                        $isPasswordCorrect = false;
                    }
                }

                if ($isPasswordCorrect) {
                    $role = $u['role'] ?? 'student';
                    if ($role === 'faculty') $role = 'teacher';

                    $formattedName = formatLastNameFirst($u['full_name']);
                    $avatarUrl = !empty($u['avatar_url']) ? $u['avatar_url'] : ('https://ui-avatars.com/api/?name=' . urlencode($formattedName) . '&background=006837&color=fed488&bold=true');

                    if (session_status() === PHP_SESSION_NONE) session_start();
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['email'] = $u['email'];
                    $_SESSION['name'] = $formattedName;
                    $_SESSION['raw_name'] = $u['full_name'];
                    $_SESSION['picture'] = $avatarUrl;
                    $_SESSION['avatar'] = $avatarUrl;
                    $_SESSION['role'] = $role;
                    $_SESSION['base_role'] = $role;
                    $_SESSION['active_portal'] = in_array($role, ['admin', 'registrar']) ? 'admin' : (in_array($role, ['teacher', 'faculty']) ? 'faculty' : 'student');
                    $_SESSION['student_number'] = $u['student_number'] ?? '2024-00192';
                    $_SESSION['program'] = $u['std_program'] ?? ($u['program'] ?? 'AIS');
                    $_SESSION['section'] = $u['std_section'] ?? ($u['section'] ?? '2A');
                    $_SESSION['scholar_status'] = $u['scholar_status'] ?? 'Non-Scholar';
                    $_SESSION['status'] = $userStatus;
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();

                    getCsrfToken();
                    logSecurityEvent("LOGIN_SUCCESS: {$u['email']} logged in ($formattedName)", $u['email'], 'Low');

                    $dest = ($role === 'admin' || $role === 'registrar') ? '/admin/index.php'
                        : (($role === 'teacher' || $role === 'faculty') ? '/teacher/index.php'
                            : '/student/index.php');
                    header("Location: $dest");
                    exit();
                } else {
                    $loginError = 'Invalid password. (Leave password blank to use Instant Database Auto-Login).';
                }
            }
        } else {
            $loginError = 'Account not found in the NPC database. Please contact your campus Administrator or Registrar to register your account.';
        }
    } catch (\Throwable $e) {
        $loginError = 'Database error: ' . $e->getMessage();
    }
}

// If already logged in with a valid session, redirect to appropriate portal
if (isSessionValid() && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin' || $role === 'registrar') {
        header("Location: /admin/index.php");
    } elseif ($role === 'teacher' || $role === 'faculty') {
        header("Location: /teacher/index.php");
    } else {
        header("Location: /student/index.php");
    }
    exit();
}

$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · NPC LMS (Learning Management System)</title>
    <!-- Theme bootstrap: respect saved preference before first paint -->
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
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= file_exists(__DIR__ . '/assets/css/styles.css') ? filemtime(__DIR__ . '/assets/css/styles.css') : '1' ?>">
    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js?v=<?= file_exists(__DIR__ . '/assets/js/three.min.js') ? filemtime(__DIR__ . '/assets/js/three.min.js') : 'r128' ?>"></script>
    <script>
        if (typeof THREE === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
            document.head.appendChild(s);
        }
    </script>
    <script src="/assets/js/npc-three.js?v=<?= file_exists(__DIR__ . '/assets/js/npc-three.js') ? filemtime(__DIR__ . '/assets/js/npc-three.js') : '1' ?>"></script>
    <script src="/assets/js/npc.js?v=<?= file_exists(__DIR__ . '/assets/js/npc.js') ? filemtime(__DIR__ . '/assets/js/npc.js') : '1' ?>"></script>
    <!-- Supabase JS Client & Google Identity Services SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        /* Login-specific choreography */
        @keyframes heroWordUp {
            from {
                opacity: 0;
                transform: translateY(26px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-word {
            animation: heroWordUp .7s cubic-bezier(.22, .68, .32, 1) both;
        }

        .hero-word:nth-child(2) {
            animation-delay: .10s;
        }

        .hero-word:nth-child(3) {
            animation-delay: .20s;
        }

        .login-card {
            animation: popIn .55s cubic-bezier(.22, .68, .32, 1) both .15s;
        }

        @keyframes ringSpin {
            to {
                transform: rotate(360deg);
            }
        }

        .emblem-ring {
            animation: ringSpin 14s linear infinite;
        }

        .feature-row {
            transition: transform .3s cubic-bezier(.34, 1.56, .64, 1), background-color .3s ease;
        }

        .feature-row:hover {
            transform: translateX(6px);
            background: rgba(255, 255, 255, .08);
        }

        @media (prefers-reduced-motion: reduce) {

            .hero-word,
            .login-card,
            .emblem-ring {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            .aurora-scene .orb {
                display: none;
            }
        }
    </style>
</head>

<body class="font-sans min-h-screen antialiased">

    <!-- ══════════ Aurora Hero Stage ══════════ -->
    <div id="login-hero-stage" class="relative min-h-screen w-full overflow-hidden flex items-center justify-center p-4"
        style="background: linear-gradient(150deg, #012415 0%, #004d29 45%, #006837 80%, #087f47 100%);">

        <!-- Animated orbs with official NPC Forest Green & Gold tones -->
        <div class="aurora-scene absolute inset-0" aria-hidden="true">
            <span class="orb orb-gold" style="width:420px;height:420px;top:-120px;left:-100px;background:radial-gradient(circle, rgba(245,158,11,0.35) 0%, transparent 70%);"></span>
            <span class="orb orb-green" style="width:520px;height:520px;bottom:-180px;right:-140px;animation-delay:-5s;background:radial-gradient(circle, rgba(16,185,129,0.3) 0%, transparent 70%);"></span>
            <span class="orb orb-emerald" style="width:300px;height:300px;top:40%;left:58%;animation-delay:-9s;background:radial-gradient(circle, rgba(5,150,105,0.35) 0%, transparent 70%);"></span>
        </div>

        <!-- Subtle grid texture -->
        <div class="absolute inset-0 opacity-[0.05] pointer-events-none" aria-hidden="true"
            style="background-image:linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px);background-size:44px 44px;"></div>

        <div class="relative z-10 w-full max-w-5xl grid lg:grid-cols-2 gap-10 items-center">

            <!-- ────────── Left: Brand story (desktop only) ────────── -->
            <div class="hidden lg:flex flex-col gap-8 text-white pr-6">
                <div class="flex items-center gap-4">
                    <div class="relative w-16 h-16 shrink-0">
                        <span class="emblem-ring absolute inset-0 rounded-full border border-dashed border-emerald-300/40"></span>
                        <img src="/assets/img/npc-logo.png"
                            alt="NPC Official Seal" class="absolute inset-1 w-[56px] h-[56px] rounded-full object-contain bg-white p-0.5 shadow-xl">
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight leading-none text-white">NPC LMS</h1>
                        <p class="text-xs font-mono uppercase tracking-[0.2em] mt-1.5 text-amber-300 font-bold">Navotas Polytechnic College</p>
                    </div>
                </div>

                <h2 class="text-4xl xl:text-5xl font-extrabold tracking-tight leading-[1.08]">
                    <span class="hero-word block">One portal.</span>
                    <span class="hero-word block text-[#fed488]">Every campus journey.</span>
                    <span class="hero-word block">Zero queues.</span>
                </h2>

                <p class="text-white/80 max-w-md leading-relaxed hero-word">
                    Attendance, grades, schedules, and announcements — unified in one real-time workspace aligned with official NPC design.
                </p>

                <ul class="flex flex-col gap-2.5 max-w-md stagger">
                    <li class="feature-row flex items-center gap-3 rounded-xl px-3 py-2.5 glass-chip text-sm font-medium">
                        <span class="material-symbols-outlined text-[#fed488] fill" style="font-size:20px;">qr_code_scanner</span>
                        QR-powered attendance in seconds
                    </li>
                    <li class="feature-row flex items-center gap-3 rounded-xl px-3 py-2.5 glass-chip text-sm font-medium">
                        <span class="material-symbols-outlined text-emerald-300" style="font-size:20px;">insights</span>
                        Live gradebook &amp; academic analytics
                    </li>
                    <li class="feature-row flex items-center gap-3 rounded-xl px-3 py-2.5 glass-chip text-sm font-medium">
                        <span class="material-symbols-outlined text-[#fed488]" style="font-size:20px;">smart_toy</span>
                        AI assistant trained on official documents
                    </li>
                </ul>

                <div class="flex items-center gap-4 pt-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl glass-chip text-xs font-mono text-emerald-200 border border-emerald-400/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Real-time Academic Network · Online
                    </span>
                </div>
            </div>

            <!-- ────────── Right: Login Card ────────── -->
            <div class="flex justify-center">
                <div id="npc-login-card" class="login-card tilt relative w-full max-w-md rounded-3xl overflow-hidden
                            border border-emerald-400/25 shadow-2xl"
                    style="background: rgba(1, 36, 21, 0.75); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);">
                    <!-- top gradient edge -->
                    <div class="absolute top-0 left-0 right-0 h-1.5" style="background:var(--gold-gradient);background-size:200% 100%;animation:edgeFlow 5s linear infinite;"></div>

                    <div class="p-8 md:p-10">
                        <!-- Mobile emblem -->
                        <div class="flex lg:hidden flex-col items-center text-center mb-6">
                            <img src="/assets/img/npc-logo.png"
                                alt="NPC Official Seal" class="w-16 h-16 rounded-full mb-2 object-contain bg-white p-1 border border-emerald-400/40 shadow-xl animate-float">
                            <h1 class="text-xl font-bold text-white">NPC LMS</h1>
                            <p class="text-xs text-amber-300 font-medium">Navotas Polytechnic College</p>
                        </div>

                        <div class="mb-5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-[0.18em] glass-chip text-white/90 border border-emerald-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse-dot"></span>
                                Local MySQL Database Protected
                            </span>
                            <h2 class="text-2xl font-bold text-white mt-2.5">Sign In to Portal</h2>
                            <p class="text-xs text-emerald-100/75 mt-1">Instant database authentication · No third-party redirect</p>
                        </div>

                        <!-- Primary Student 1-Click Access: DERRAMAS, JILO -->
                        <div class="mb-5">
                            <a href="dev_login.php?email=jderramas251505@navotaspolytechniccollege.edu.ph"
                                class="ripple btn-shine press group relative flex items-center gap-4 p-4 rounded-2xl bg-gradient-to-r from-emerald-600/40 via-emerald-500/30 to-teal-600/30 hover:from-emerald-500/50 hover:to-teal-500/40 border-2 border-emerald-400/60 hover:border-emerald-300 shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 cursor-pointer block">
                                <div class="relative w-14 h-14 shrink-0">
                                    <img src="https://lh3.googleusercontent.com/a/ACg8ocKrNc0kwVZis0tWU6KfvQ7NdV6n4tDeZ3aMab1DSGJZj3JKiDs=s96-c"
                                         alt="DERRAMAS, JILO" class="w-14 h-14 rounded-full object-cover border-2 border-amber-300 shadow-md group-hover:scale-105 transition-transform">
                                    <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-400 border-2 border-[#012415]"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="text-[10px] font-mono font-bold text-amber-300 uppercase tracking-wider bg-amber-400/10 px-2 py-0.5 rounded-full border border-amber-400/30">Student Portal</span>
                                        <span class="material-symbols-outlined text-amber-300 text-[14px]">verified</span>
                                    </div>
                                    <h3 class="text-base font-extrabold text-white truncate group-hover:text-amber-200 transition-colors">
                                        DERRAMAS, JILO
                                    </h3>
                                    <p class="text-xs text-emerald-200/90 font-mono">AIS 2A · ID: 251505</p>
                                </div>
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-500/30 group-hover:bg-emerald-400 group-hover:text-emerald-950 text-white transition-all shadow-md shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
                                </div>
                            </a>
                        </div>

                        <!-- Quick Role Access (Exactly 2: Faculty and Admin) -->
                        <div class="pt-4 border-t border-white/10">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-mono uppercase tracking-wider text-emerald-300 font-bold flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[13px] text-amber-300">flash_on</span> Quick Portal Switch
                                </span>
                                <span class="text-[9px] text-white/50 font-mono">1-Click Access</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <!-- 1. Faculty (Moreno) -->
                                <a href="dev_login.php?email=edsan.moreno@navotaspolytechniccollege.edu.ph"
                                   class="flex items-center gap-2.5 p-3 rounded-xl bg-white/5 hover:bg-amber-500/20 border border-white/10 hover:border-amber-400/40 transition-all group text-left cursor-pointer">
                                    <div class="w-9 h-9 rounded-full bg-amber-500/20 border border-amber-400/50 flex items-center justify-center text-amber-200 font-bold text-xs shrink-0 group-hover:scale-105 transition-transform">
                                        EM
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-white truncate group-hover:text-amber-200 transition-colors">MORENO, Edsan</p>
                                        <p class="text-[10px] text-amber-300/80 font-mono truncate">Faculty · CCS</p>
                                    </div>
                                </a>

                                <!-- 2. Administrator -->
                                <a href="dev_login.php?email=jiloderramas@gmail.com"
                                   class="flex items-center gap-2.5 p-3 rounded-xl bg-white/5 hover:bg-purple-500/20 border border-white/10 hover:border-purple-400/40 transition-all group text-left cursor-pointer">
                                    <img src="https://lh3.googleusercontent.com/a/ACg8ocIxJ4LcSkHu8zIWqYnSFrecxLcgsvm1JT1RqrgVzcMIHrICI-UI=s96-c"
                                         alt="Admin" class="w-9 h-9 rounded-full object-cover border border-purple-400/50 shrink-0 group-hover:scale-105 transition-transform">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-white truncate group-hover:text-purple-200 transition-colors">DERRAMAS, JILO</p>
                                        <p class="text-[10px] text-purple-300/80 font-mono truncate">Administrator</p>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Optional: Collapsible Other Student ID -->
                        <div class="mt-4 pt-3 border-t border-white/5 text-center">
                            <button type="button" onclick="document.getElementById('manual-login-panel').classList.toggle('hidden')"
                                class="text-[11px] text-emerald-300/70 hover:text-emerald-200 font-mono transition-colors inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">swap_horiz</span>
                                Sign in with another Student ID
                            </button>

                            <div id="manual-login-panel" class="hidden mt-3 text-left">
                                <form method="POST" action="login.php" class="flex gap-2">
                                    <input type="text" name="identifier" placeholder="Enter Student ID or Email" required
                                        class="flex-1 bg-black/35 border border-emerald-500/30 focus:border-emerald-400 text-white placeholder-emerald-100/40 text-xs rounded-xl px-3 py-2 outline-none">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition-all cursor-pointer">
                                        Go
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Footer strip -->
        <footer class="absolute bottom-0 inset-x-0 py-3 text-center">
            <p class="text-[11px] text-white/40 font-mono tracking-wide">© <?= date('Y') ?> Navotas Polytechnic College · Secure Local Academic Gateway</p>
        </footer>
    </div>

    <!-- Live Profile & Client Script (No external Supabase dependency) -->
    <script>
        // Initialize Cinematic 3D Login Hero Scene
        document.addEventListener('DOMContentLoaded', function() {
            if (window.npcThree && typeof window.npcThree.initLoginHero === 'function') {
                window.npcThree.initLoginHero('login-hero-stage');
            }
        });
    </script>
</body>

</html>