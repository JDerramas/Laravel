<?php
/**
 * auth_callback.php — High-Tech 3D OAuth Callback & Verification Portal
 *
 * After Google OAuth via Supabase, this page:
 *  1. Captures the Supabase session (client-side, after redirect)
 *     - Supports BOTH implicit (#access_token) and PKCE (?code) flows
 *  2. Displays an immersive 3D holographic verification visual experience
 *  3. Sends the access_token to set_session.php (server-side verification)
 *  4. Smoothly redirects to the appropriate portal (admin, teacher, or student)
 *
 * Fully defensive:
 *  - Theme-aware (dark mode / light mode seamless sync)
 *  - Interactive Three.js 3D particle vortex & holographic security ring
 */
require_once __DIR__ . '/includes/db_helper.php';
$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticating Credentials - NPC Connect</title>

    <!-- Pre-paint dark mode bootstrap -->
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

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- NPC Design System -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <!-- Three.js 3D Engine & NPC Visuals -->
    <script src="/assets/js/three.min.js"></script>
    <script src="/assets/js/npc-three.js"></script>
    <script src="/assets/js/npc.js"></script>

    <!-- Official Supabase JS SDK for OAuth Handling -->
    

    <style>
        /* 3D Canvas Background */
        #auth-3d-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
        }

        /* Cyber Ring Animations */
        @keyframes ringSpinCW {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes ringSpinCCW {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.5; transform: scale(1); filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.4)); }
            50% { opacity: 0.9; transform: scale(1.06); filter: drop-shadow(0 0 25px rgba(254, 212, 136, 0.7)); }
        }
        @keyframes scanline {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(1000%); }
        }

        .ring-cw { animation: ringSpinCW 14s linear infinite; }
        .ring-ccw { animation: ringSpinCCW 8s linear infinite; }
        .core-pulse { animation: pulseGlow 2.4s ease-in-out infinite; }

        /* Glassmorphism Card */
        .auth-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 50px rgba(0, 23, 54, 0.15), 0 0 30px rgba(56, 189, 248, 0.12);
        }
        .dark .auth-card {
            background: rgba(10, 26, 47, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(56, 189, 248, 0.15);
        }

        /* Progress Bar Shimmer */
        .progress-bar-fill {
            background: linear-gradient(90deg, #38bdf8, #fed488, #38bdf8);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
        }
        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex items-center justify-center relative overflow-hidden antialiased select-none">

    <!-- 3D Three.js Background Canvas -->
    <canvas id="auth-3d-canvas"></canvas>

    <!-- Foreground Verification HUD Card -->
    <main class="relative z-10 w-full max-w-md p-6 mx-4">
        <div class="auth-card rounded-3xl p-8 text-center relative overflow-hidden transition-all duration-500">
            
            <!-- Ambient Top Accent Gradient -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-sky-400 via-amber-300 to-sky-500"></div>

            <!-- 3D Holographic Scanner Crest -->
            <div class="relative w-32 h-32 mx-auto mb-6 flex items-center justify-center">
                <!-- Outer Dashed Ring -->
                <svg class="absolute inset-0 w-full h-full ring-cw opacity-80" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" class="text-sky-400/40 dark:text-sky-400/50" stroke-width="1.5" stroke-dasharray="8 6"/>
                    <circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" class="text-amber-300 dark:text-amber-400" stroke-width="2" stroke-dasharray="16 120"/>
                </svg>

                <!-- Middle Cyber Ring -->
                <svg class="absolute inset-2 w-28 h-28 ring-ccw opacity-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" class="text-amber-400/40" stroke-width="1.5" stroke-dasharray="12 10 4 10"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" class="text-sky-400" stroke-width="2.5" stroke-dasharray="30 180"/>
                </svg>

                <!-- Glowing Inner Core Emblem -->
                <div class="relative z-10 w-16 h-16 rounded-2xl bg-gradient-to-br from-primary via-slate-900 to-slate-950 flex items-center justify-center shadow-lg border border-sky-400/30 core-pulse">
                    <span class="material-symbols-outlined text-amber-300 text-3xl" id="core-icon">verified_user</span>
                </div>
            </div>

            <!-- Status Headline & Details -->
            <h1 class="text-2xl font-bold tracking-tight text-on-surface mb-2 font-sans flex items-center justify-center gap-2" id="status-title">
                Authenticating...
            </h1>
            <p class="text-sm text-on-surface-variant font-medium mb-6 transition-all duration-300 h-10 flex items-center justify-center" id="status-msg">
                Verifying institutional credentials with Navotas Polytechnic College...
            </p>

            <!-- Holographic Progress Tracker -->
            <div class="w-full bg-surface-container-high dark:bg-slate-800/80 rounded-full h-2 overflow-hidden mb-5 border border-outline-variant/30">
                <div id="progress-bar" class="progress-bar-fill h-full rounded-full transition-all duration-500 ease-out" style="width: 28%;"></div>
            </div>

            <!-- Technical Status Terminal Chip -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-container dark:bg-slate-900/60 border border-outline-variant/40 text-xs font-mono text-on-surface-variant">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span id="tech-indicator">HANDSHAKE: ACTIVE</span>
            </div>

            <!-- Error Retry Action (Hidden by default) -->
            <div id="error-box" class="hidden mt-6 pt-4 border-t border-error/20">
                <p id="err-detail" class="text-xs font-mono text-error bg-error-container/20 rounded-xl p-3 mb-4 text-left break-all"></p>
                <button id="retry-btn" onclick="window.location.href='/login.php'" class="w-full py-2.5 px-4 rounded-xl bg-primary text-on-primary font-semibold text-sm hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    <span>Return to Sign In</span>
                </button>
            </div>

        </div>

        <!-- Security Footer Badge -->
        <div class="mt-4 text-center text-xs text-on-surface-variant/70 flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-sky-400">lock</span>
            <span>256-Bit Encrypted Session Authentication</span>
        </div>
    </main>

    <!-- ── Three.js 3D Background Vortex Engine ─────────────────────────────── -->
    <script>
        (function initAuth3D() {
            var canvas = document.getElementById('auth-3d-canvas');
            if (!canvas || typeof THREE === 'undefined') return;

            var scene = new THREE.Scene();
            var camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
            camera.position.z = 180;

            var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

            // Dynamic Particle Field
            var count = window.innerWidth < 768 ? 90 : 180;
            var geo = new THREE.BufferGeometry();
            var pos = new Float32Array(count * 3);
            var col = new Float32Array(count * 3);

            var cAzure = new THREE.Color(0x38bdf8);
            var cGold = new THREE.Color(0xfed488);

            for (var i = 0; i < count; i++) {
                var i3 = i * 3;
                pos[i3] = (Math.random() - 0.5) * 450;
                pos[i3 + 1] = (Math.random() - 0.5) * 350;
                pos[i3 + 2] = (Math.random() - 0.5) * 200;

                var c = Math.random() > 0.5 ? cAzure : cGold;
                col[i3] = c.r;
                col[i3 + 1] = c.g;
                col[i3 + 2] = c.b;
            }
            geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
            geo.setAttribute('color', new THREE.BufferAttribute(col, 3));

            var mat = new THREE.PointsMaterial({
                size: 3.5,
                vertexColors: true,
                transparent: true,
                opacity: 0.75,
                blending: THREE.AdditiveBlending
            });
            var particles = new THREE.Points(geo, mat);
            scene.add(particles);

            // Rotating 3D Torus Rings
            var ringGeo1 = new THREE.TorusGeometry(85, 0.7, 16, 100);
            var ringMat1 = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.35, wireframe: true });
            var ring1 = new THREE.Mesh(ringGeo1, ringMat1);
            ring1.rotation.x = Math.PI / 3;
            scene.add(ring1);

            var ringGeo2 = new THREE.TorusGeometry(110, 0.5, 16, 100);
            var ringMat2 = new THREE.MeshBasicMaterial({ color: 0xfed488, transparent: true, opacity: 0.25, wireframe: true });
            var ring2 = new THREE.Mesh(ringGeo2, ringMat2);
            ring2.rotation.y = Math.PI / 4;
            scene.add(ring2);

            // Mouse parallax interaction
            var mouseX = 0, mouseY = 0;
            window.addEventListener('mousemove', function (e) {
                mouseX = (e.clientX / window.innerWidth - 0.5) * 20;
                mouseY = (e.clientY / window.innerHeight - 0.5) * 20;
            });

            window.addEventListener('resize', function () {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });

            var clock = new THREE.Clock();
            function animate() {
                requestAnimationFrame(animate);
                var delta = clock.getDelta();
                particles.rotation.y += delta * 0.04;
                particles.rotation.x += delta * 0.02;

                ring1.rotation.z += delta * 0.2;
                ring2.rotation.z -= delta * 0.15;

                camera.position.x += (mouseX - camera.position.x) * 0.05;
                camera.position.y += (-mouseY - camera.position.y) * 0.05;
                camera.lookAt(scene.position);

                renderer.render(scene, camera);
            }
            animate();
        })();
    </script>

    <!-- ── Authentication & Supabase Session Engine ────────────────────────── -->
    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = (typeof supabase !== 'undefined' && supabase.createClient) ? supabase.createClient(supabaseUrl, supabaseKey) : null;

        const statusTitle   = document.getElementById('status-title');
        const statusMsg     = document.getElementById('status-msg');
        const progressBar   = document.getElementById('progress-bar');
        const techIndicator = document.getElementById('tech-indicator');
        const coreIcon      = document.getElementById('core-icon');
        const errorBox      = document.getElementById('error-box');
        const errDetail     = document.getElementById('err-detail');

        function setStatus(title, msg, isError = false, detail = '', progress = null) {
            if (statusTitle) statusTitle.innerText = title;
            if (statusMsg) statusMsg.innerText = msg;
            if (progress !== null && progressBar) {
                progressBar.style.width = progress + '%';
            }

            if (isError) {
                if (coreIcon) {
                    coreIcon.innerText = 'error';
                    coreIcon.className = 'material-symbols-outlined text-red-400 text-3xl';
                }
                if (statusTitle) statusTitle.className = 'text-2xl font-bold tracking-tight text-red-500 mb-2 font-sans';
                if (techIndicator) {
                    techIndicator.parentElement.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/30 text-xs font-mono text-red-400';
                    techIndicator.previousElementSibling.className = 'w-2 h-2 rounded-full bg-red-400';
                    techIndicator.innerText = 'STATUS: REJECTED';
                }
                if (errorBox) errorBox.classList.remove('hidden');
                if (detail && errDetail) {
                    errDetail.textContent = detail;
                }
                console.error('[NPC Auth]', title, '—', msg, detail || '');
            }
        }

        let isProcessing = false;

        async function finishLogin(accessToken) {
            if (isProcessing) return;
            isProcessing = true;
            try {
                setStatus('Verifying Token...', 'Establishing secure encrypted handshake with server...', false, '', 60);
                if (techIndicator) techIndicator.innerText = 'SESSION: SIGNING';

                const sessionEndpoint = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'set_session.php';
                const res = await fetch(sessionEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ access_token: accessToken })
                });
                const result = await res.json();

                if (res.ok && result.success) {
                    if (result.csrf_token) sessionStorage.setItem('csrf_token', result.csrf_token);
                    setStatus('Access Granted!', 'Session encrypted. Transferring to your portal...', false, '', 100);
                    if (techIndicator) {
                        techIndicator.innerText = 'STATUS: AUTHORIZED';
                        techIndicator.parentElement.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-xs font-mono text-emerald-400';
                    }
                    if (coreIcon) {
                        coreIcon.innerText = 'task_alt';
                        coreIcon.className = 'material-symbols-outlined text-emerald-400 text-3xl';
                    }

                    const role = result.role || 'student';
                    setTimeout(() => {
                        window.location.href = (role === 'admin' || role === 'registrar') ? '/admin/index.php'
                                              : (role === 'teacher' || role === 'faculty') ? '/teacher/index.php'
                                              : '/student/index.php';
                    }, 700);
                } else {
                    try { await supabaseClient.auth.signOut(); } catch(e) {}
                    setStatus('Access Denied', result.message || 'Server rejected the session verification.', true);
                    return;
                }
            } catch (err) {
                try { await supabaseClient.auth.signOut(); } catch(e) {}
                setStatus('Login Failed', err.message || 'An unexpected verification error occurred.', true, err.stack || err.message);
            }
        }

        function tryImplicitToken() {
            // 1. Check URL hash (#access_token=... or #id_token=... or #token=...)
            const h = window.location.hash || '';
            if (h.length > 1) {
                const params = new URLSearchParams(h.substring(1));
                const err = params.get('error');
                if (err) {
                    setStatus('Sign-In Error', decodeURIComponent(params.get('error_description') || err), true, 'provider=' + err);
                    return 'handled';
                }
                const tok = params.get('access_token') || params.get('id_token') || params.get('token');
                if (tok) return tok;
            }

            // 2. Check URL query parameters (?access_token=... or ?token=...)
            const q = new URLSearchParams(window.location.search);
            const qTok = q.get('access_token') || q.get('id_token') || q.get('token');
            if (qTok) return qTok;

            // 3. Check Session Storage (from Google Sign-In Chooser)
            try {
                const storedTok = sessionStorage.getItem('npc_google_auth_token');
                if (storedTok) {
                    sessionStorage.removeItem('npc_google_auth_token');
                    return storedTok;
                }
            } catch (e) {}

            return null;
        }

        function tryQueryError() {
            const q = new URLSearchParams(window.location.search);
            if (q.get('error')) {
                setStatus('Sign-In Error', q.get('error_description') || q.get('error'), true, 'code=' + q.get('error_code', ''));
                return true;
            }
            return false;
        }

        async function processSession(session) {
            if (isProcessing) return;
            try {
                if (!session || !session.access_token) {
                    throw new Error('No active session token was returned.');
                }
                const userEmail = session.user?.email?.toLowerCase() || '';
                setStatus('Validating Account...', 'Verifying registration for ' + userEmail + '...', false, '', 45);
                await finishLogin(session.access_token);
            } catch (err) {
                try { await supabaseClient.auth.signOut(); } catch(e) {}
                setStatus('Authentication Error', err.message || 'Unexpected auth failure.', true, err.message);
            }
        }

        async function initAuth() {
            if (tryQueryError()) return;

            const implicit = tryImplicitToken();
            if (implicit === 'handled') return;
            if (implicit) {
                setStatus('Processing Token...', 'Capturing OAuth credentials...', false, '', 35);
                await finishLogin(implicit);
                return;
            }

            // PKCE code exchange if redirected with ?code=
            const qCode = new URLSearchParams(window.location.search).get('code');
            if (qCode && supabaseClient && supabaseClient.auth && typeof supabaseClient.auth.exchangeCodeForSession === 'function') {
                try {
                    setStatus('Exchanging Authorization Code...', 'Validating credentials with identity provider...', false, '', 40);
                    const { data: cData, error: cErr } = await supabaseClient.auth.exchangeCodeForSession(qCode);
                    if (!cErr && cData?.session?.access_token) {
                        await finishLogin(cData.session.access_token);
                        return;
                    }
                } catch(e) {
                    console.warn('[PKCE Exchange Error]', e);
                }
            }

            supabaseClient.auth.onAuthStateChange(async (event, session) => {
                if (session) await processSession(session);
            });

            const { data, error } = await supabaseClient.auth.getSession();
            if (error) {
                setStatus('OAuth Session Error', error.message, true);
                return;
            }

            if (data?.session) {
                await processSession(data.session);
            } else {
                setTimeout(() => {
                    if (!isProcessing) {
                        setStatus(
                            'Session Handshake Incomplete',
                            'No active session token was detected. Please verify browser third-party cookie permissions and retry.',
                            true,
                            'TIP: Ensure your browser is not blocking cross-origin storage for Supabase authentication.'
                        );
                    }
                }, 10000);
            }
        }

        initAuth();
    </script>
</body>
</html>
