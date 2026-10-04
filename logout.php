<?php
/**
 * logout.php — Secure Logout Handler
 * 
 * Properly destroys the PHP session (cookies, data, ID),
 * then signs out from Supabase client-side.
 */
require_once __DIR__ . '/includes/supabase_helper.php';

// Log the logout event before destroying the session
if (session_status() === PHP_SESSION_NONE) session_start();
$logEmail = $_SESSION['email'] ?? 'unknown';
logSecurityEvent("LOGOUT: $logEmail logged out", $logEmail, 'Low');

// Destroy session completely
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

$jsConfig = getJsConfig();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logging out... - NPC Connect</title>
    <script>
        /* Respect night mode during logout transition */
        (function () {
            try {
                var t = localStorage.getItem('npc-theme');
                var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) {
                    document.documentElement.style.background = '#090f19';
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <!-- Three.js 3D Engine & NPC Visuals -->
    <script src="/assets/js/three.min.js"></script>
    <script src="/assets/js/npc-three.js"></script>
    <script src="/assets/js/npc.js"></script>
</head>
<body style="font-family: 'Geist', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--surface-rgb, #f8f9ff); margin: 0;">
    <div style="text-align: center;">
        <p style="color: var(--on-surface-variant, #64748b); font-size: 16px;">Signing you out...</p>
    </div>
    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
        
        async function doLogout() {
            try {
                await supabaseClient.auth.signOut();
            } catch(e) {
                console.warn('Supabase signout error:', e);
            }
            // Clear any stored CSRF tokens
            sessionStorage.removeItem('csrf_token');
            window.location.href = '/login.php';
        }
        
        doLogout();
    </script>
</body>
</html>
