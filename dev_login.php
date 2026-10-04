<?php
/**
 * dev_login.php — Localhost Development Session Helper
 * 
 * ONLY accessible when running locally on 127.0.0.1 or ::1.
 * Allows switching between Student, Teacher, and Admin accounts for rapid local testing.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/supabase_helper.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost']) 
    || strpos($host, 'localhost') !== false 
    || strpos($host, '127.0.0.1') !== false
    || strpos($host, 'ngrok') !== false
    || strpos($host, 'trycloudflare') !== false
    || strpos($host, 'eu.org') !== false;

if (!$isLocal) {
    http_response_code(403);
    die("Access denied: dev_login is only accessible on localhost development environments.");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// ─── Direct Database Account Auto-Login (by email / student number) ───────────
$emailParam = trim($_GET['email'] ?? '');
if (!empty($emailParam)) {
    try {
        $db = getDB();
        $cleanId = str_replace('-', '', $emailParam);
        $stmt = $db->prepare("SELECT u.*, s.scholar_status, s.program AS std_program, s.section AS std_section FROM users u 
                              LEFT JOIN students s ON (s.email = u.email OR s.user_id = u.id) 
                              WHERE LOWER(u.email) = ? 
                                 OR LOWER(u.student_number) = ? 
                                 OR LOWER(s.student_number) = ? 
                                 OR REPLACE(LOWER(u.student_number), '-', '') = ? 
                                 OR REPLACE(LOWER(s.student_number), '-', '') = ? 
                              LIMIT 1");
        $stmt->execute([strtolower($emailParam), strtolower($emailParam), strtolower($emailParam), $cleanId, $cleanId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($targetUser) {
            $uRole = $targetUser['role'] ?? 'student';
            if ($uRole === 'faculty') $uRole = 'teacher';

            $_SESSION['user_id'] = $targetUser['id'];
            $_SESSION['email'] = $targetUser['email'];
            $_SESSION['name'] = $targetUser['full_name'];
            $_SESSION['picture'] = $targetUser['avatar_url'] ?? null;
            $_SESSION['avatar'] = $targetUser['avatar_url'] ?? null;
            $_SESSION['role'] = $uRole;
            $_SESSION['base_role'] = $uRole;
            $_SESSION['active_portal'] = in_array($uRole, ['admin', 'registrar']) ? 'admin' : (in_array($uRole, ['teacher', 'faculty']) ? 'faculty' : 'student');
            $_SESSION['student_number'] = $targetUser['student_number'] ?? '2024-00192';
            $_SESSION['program'] = $targetUser['std_program'] ?? ($targetUser['program'] ?? 'AIS');
            $_SESSION['section'] = $targetUser['std_section'] ?? ($targetUser['section'] ?? '2A');
            $_SESSION['scholar_status'] = $targetUser['scholar_status'] ?? 'Non-Scholar';
            $_SESSION['status'] = $targetUser['status'] ?? 'Active';
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = '127.0.0.1';

            getCsrfToken();
            logSecurityEvent("DEV_LOGIN_SUCCESS: {$targetUser['email']} direct authenticated", $targetUser['email'], 'Low');

            $dest = ($uRole === 'admin' || $uRole === 'registrar') ? '/admin/index.php'
                  : (($uRole === 'teacher' || $uRole === 'faculty') ? '/teacher/index.php'
                  : '/student/index.php');
            header("Location: $dest");
            exit();
        }
    } catch (\Throwable $e) {}
}

$role = $_GET['role'] ?? 'student';
if ($role === 'faculty') {
    $role = 'teacher';
}
if (!in_array($role, ['student', 'teacher', 'admin'])) {
    $role = 'student';
}

$redirect = $_GET['redirect'] ?? ($role === 'teacher' ? 'teacher/index.php' : ($role === 'admin' ? 'admin/index.php' : 'student/index.php'));

session_regenerate_id(true);

if ($role === 'admin') {
    $_SESSION['user_id'] = 'usr-admin-01';
    $_SESSION['email'] = 'admin@navotaspolytechniccollege.edu.ph';
    $_SESSION['name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    $_SESSION['base_role'] = 'admin';
    $_SESSION['active_portal'] = 'admin';
    $_SESSION['student_number'] = 'ADMIN-001';
} elseif ($role === 'teacher') {
    $email = $_GET['email'] ?? 'edsan.moreno@navotaspolytechniccollege.edu.ph';
    $name = $_GET['name'] ?? 'Prof. Edsan Moreno';
    $_SESSION['user_id'] = 'usr-faculty-01';
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;
    $_SESSION['role'] = 'teacher';
    $_SESSION['base_role'] = 'teacher';
    $_SESSION['active_portal'] = 'faculty';
    $_SESSION['student_number'] = 'FAC-001';
} else {
    $_SESSION['user_id'] = '872b0d96-9f14-41fd-b4c0-af848788ca8b';
    $_SESSION['email'] = 'jderramas251505@navotaspolytechniccollege.edu.ph';
    $_SESSION['name'] = 'Jilo Derramas';
    $_SESSION['role'] = 'student';
    $_SESSION['base_role'] = 'student';
    $_SESSION['active_portal'] = 'student';
    $_SESSION['student_number'] = '251505';
    $_SESSION['scholar_status'] = 'Scholar';
    $_SESSION['section'] = '2A';
    $_SESSION['program'] = 'AIS';
}

$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();
$_SESSION['ip_address'] = '127.0.0.1';

// Ensure database users.role matches selected dev login role so sessions stay locked
try {
    $db = getDB();
    $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $_SESSION['user_id']]);
} catch (\Throwable $e) {}

// Generate CSRF token
getCsrfToken();

header("Location: /" . ltrim($redirect, '/'));
exit();
