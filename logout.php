<?php
/**
 * logout.php — Secure Local Session Logout Handler
 * Navotas Polytechnic College (NPC) ELMS
 * 
 * Completely clears PHP session, authentication cookies, and redirects to login.
 * 100% offline & zero cloud dependency.
 */
require_once __DIR__ . '/includes/db_helper.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logging out... - NPC Connect</title>
    <script>
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
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #001736; color: white; margin: 0;">
    <div style="text-align: center;">
        <p style="font-size: 16px; font-weight: 600; letter-spacing: 0.02em;">Signing you out...</p>
    </div>
    <script>
        try {
            sessionStorage.clear();
            localStorage.removeItem('supabase.auth.token');
            localStorage.removeItem('sb-woscjghjrleqxxyezlyu-auth-token');
        } catch(e) {}
        window.location.replace('/login.php');
    </script>
</body>
</html>
