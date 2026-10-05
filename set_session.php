<?php
/**
 * set_session.php — Secure Server-Side Session Creator
 * 
 * ACCEPTS: { "credential": "eyJ..." } or { "access_token": "eyJ..." }
 * 
 * Flow:
 *  1. Receives Google credential / ID token directly
 *  2. Verifies token server-side (decodes Google JWT and extracts verified email)
 *  3. Looks up the verified user in the MySQL users table
 *  4. Creates a hardened PHP session with VERIFIED data only
 */

// Buffer output and suppress HTML error printing in API responses
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// ─── Session Hardening ─────────────────────────────────────────────────────────
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

require_once __DIR__ . '/includes/supabase_helper.php';
require_once __DIR__ . '/includes/auth.php';

function sendJsonResponse(int $code, array $payload): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

// ─── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, ['success' => false, 'message' => 'Method not allowed']);
}

// ─── Parse Request ─────────────────────────────────────────────────────────────
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$accessToken = trim($data['credential'] ?? $data['id_token'] ?? $data['access_token'] ?? '');

if (empty($accessToken)) {
    sendJsonResponse(400, ['success' => false, 'message' => 'Missing authentication credential']);
}

// ─── Step 1: Verify Token Server-Side ──────────────────────────────────────────
$user = verifySupabaseToken($accessToken);

if (!$user) {
    logSecurityEvent('AUTH_FAIL: Invalid or expired token presented', '', 'Medium');
    sendJsonResponse(401, ['success' => false, 'message' => 'Invalid or expired authentication token']);
}

$email = strtolower(trim($user['email']));
$userId = $user['id'];

$fullName = $user['user_metadata']['full_name'] 
    ?? $user['user_metadata']['name'] 
    ?? $email;

$avatarUrl = $user['user_metadata']['avatar_url'] 
    ?? $user['user_metadata']['picture'] 
    ?? $user['picture'] 
    ?? null;

// ─── Step 2: Strict Database Pre-Registration Verification ───────────────────
// Users CANNOT log in unless they already exist in the MySQL database.
// Auto-provisioning on login is strictly disabled.
// Both @navotaspolytechniccollege.edu.ph and normal @gmail.com are permitted
// IF AND ONLY IF registered in the database by an administrator.

$db = getDB();
$userStmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1");
$userStmt->execute([$email]);
$existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$existingUser) {
    logSecurityEvent("AUTH_DENIED: Unregistered account attempted login: $email", $email, 'High');
    sendJsonResponse(403, [
        'success' => false,
        'message' => 'Account not found in the NPC database. Please contact your campus Administrator or Registrar to register your account.'
    ]);
}

// ─── Step 3: Account Status Check (Active, Banned, Restricted, Snoozed) ───────
$userStatus = $existingUser['status'] ?? ($existingUser['is_active'] ? 'Active' : 'Inactive');
$isActive = (int)($existingUser['is_active'] ?? 1);

if ($userStatus === 'Banned') {
    logSecurityEvent("AUTH_DENIED: Banned account attempted login: $email", $email, 'High');
    sendJsonResponse(403, [
        'success' => false,
        'message' => 'This account has been banned by the Administrator. Access denied.'
    ]);
}

if ($userStatus === 'Restricted') {
    logSecurityEvent("AUTH_DENIED: Restricted account attempted login: $email", $email, 'High');
    sendJsonResponse(403, [
        'success' => false,
        'message' => 'This account has been restricted by the Administrator. Access denied.'
    ]);
}

if ($userStatus === 'Snoozed' || $isActive === 0) {
    logSecurityEvent("AUTH_DENIED: Snoozed/Inactive account attempted login: $email", $email, 'High');
    sendJsonResponse(403, [
        'success' => false,
        'message' => 'This account is currently snoozed/deactivated. Please contact the Administrator.'
    ]);
}

// ─── Step 4: Resolve Profile & Role from Database Record ─────────────────────
$role = $existingUser['role'] ?? 'student';
if ($role === 'faculty') $role = 'teacher';

$currentDbName = trim($existingUser['full_name'] ?? '');
$studentNumber = $existingUser['student_number'] ?? '';
$program = $existingUser['program'] ?? 'AIS';
$section = $existingUser['section'] ?? '2A';
$scholarStatus = 'Non-Scholar';

// Official name provided by Google Workspace / Google OAuth (e.g. "JILO DERRAMAS")
$googleName = trim($user['user_metadata']['full_name'] ?? $user['user_metadata']['name'] ?? '');

$finalName = !empty($currentDbName) ? $currentDbName : (!empty($googleName) ? $googleName : $fullName);

// If student, pull official enrollment & scholar details from `students` table
if ($role === 'student') {
    $stdStmt = $db->prepare("SELECT * FROM students WHERE user_id = ? OR LOWER(email) = ? LIMIT 1");
    $stdStmt->execute([$existingUser['id'], $email]);
    $stdRow = $stdStmt->fetch(PDO::FETCH_ASSOC);

    if ($stdRow) {
        if (!empty($stdRow['program'])) $program = $stdRow['program'];
        if (!empty($stdRow['section'])) $section = $stdRow['section'];
        if (!empty($stdRow['student_number'])) $studentNumber = $stdRow['student_number'];
        if (!empty($stdRow['scholar_status'])) $scholarStatus = $stdRow['scholar_status'];
        if (!empty($stdRow['full_name']) && stripos($stdRow['full_name'], 'Pending') === false) {
            $finalName = $stdRow['full_name'];
        }
    }
}

// ── Google Workspace / NPC Gmail Official Name Synchronization ──
// If the database has a placeholder, pending indicator, or email prefix shortcut,
// automatically upgrade it with the official complete name from Google Workspace (e.g. "JILO DERRAMAS").
if (!empty($googleName) && strtolower($googleName) !== strtolower($email)) {
    $prefixOnly = preg_replace('/[^a-zA-Z]/', '', explode('@', $email)[0]);
    $cleanedCurrent = preg_replace('/[^a-zA-Z]/', '', $currentDbName);

    $isShortcutOrPending = (
        empty($currentDbName) ||
        stripos($currentDbName, 'Pending') !== false ||
        strtolower($cleanedCurrent) === strtolower($prefixOnly) ||
        (!empty($cleanedCurrent) && stripos($prefixOnly, $cleanedCurrent) !== false) ||
        strtolower($currentDbName) === strtolower($email)
    );

    if ($isShortcutOrPending) {
        $finalName = $googleName;
        try {
            $db->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$googleName, $existingUser['id']]);
            if ($role === 'student') {
                $db->prepare("UPDATE students SET full_name = ? WHERE (user_id = ? OR LOWER(email) = ?) AND (full_name = '' OR full_name LIKE 'Pending%' OR full_name = ?)")
                   ->execute([$googleName, $existingUser['id'], $email, $currentDbName]);
            }
        } catch (\Throwable $e) {
            error_log("Failed to sync Google official name: " . $e->getMessage());
        }
    }
}

// Update avatar in DB if newly provided by OAuth
if (!empty($avatarUrl) && (empty($existingUser['avatar_url']) || $avatarUrl !== $existingUser['avatar_url'])) {
    try {
        $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$avatarUrl, $existingUser['id']]);
        if ($role === 'student') {
            $db->prepare("UPDATE students SET avatar_url = ? WHERE user_id = ? OR LOWER(email) = ?")->execute([$avatarUrl, $existingUser['id'], $email]);
        }
    } catch (\Throwable $e) {}
}

// ─── Step 5: Create Hardened PHP Session ──────────────────────────────────────
session_regenerate_id(true);

$formattedName = formatLastNameFirst($finalName);
$baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$portalPath = ($role === 'admin' || $role === 'registrar') ? '/admin/index.php' : (($role === 'teacher' || $role === 'faculty') ? '/teacher/index.php' : '/student/index.php');
$dest = ($baseDir === '' || $baseDir === '/') ? $portalPath : ($baseDir . $portalPath);

$_SESSION['user_id'] = $existingUser['id'];
$_SESSION['email'] = $existingUser['email'];
$_SESSION['name'] = $formattedName;
$_SESSION['raw_name'] = $finalName;
$_SESSION['picture'] = !empty($existingUser['avatar_url']) ? $existingUser['avatar_url'] : $avatarUrl;
$_SESSION['avatar'] = $_SESSION['picture'];
$_SESSION['role'] = $role;
$_SESSION['base_role'] = $role;
$_SESSION['active_portal'] = ($role === 'admin' || $role === 'registrar') ? 'admin' : (($role === 'teacher' || $role === 'faculty') ? 'faculty' : 'student');
$_SESSION['student_number'] = $studentNumber;
$_SESSION['section'] = $section;
$_SESSION['program'] = $program;
$_SESSION['scholar_status'] = $scholarStatus;
$_SESSION['status'] = $userStatus;
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Generate CSRF token for this session
getCsrfToken();

// ─── Step 7: Log Successful Login ──────────────────────────────────────────────
logSecurityEvent("LOGIN_SUCCESS: $email logged in as $role ($formattedName)", $email, 'Low');

sendJsonResponse(200, [
    'success' => true,
    'role' => $role,
    'redirect' => $dest,
    'name' => $formattedName,
    'csrf_token' => $_SESSION['csrf_token']
]);
?>
