<?php
/**
 * set_session.php — Secure Server-Side Session Creator
 * 
 * ACCEPTS ONLY: { "access_token": "eyJ..." }
 * 
 * Flow:
 *  1. Receives the Supabase access_token from the browser
 *  2. Verifies it server-side via Supabase Auth API
 *  3. Looks up the user's role from the users table (service key)
 *  4. Creates a hardened PHP session with VERIFIED data only
 * 
 * NEVER trusts role, name, or user_id from the browser.
 */

require_once __DIR__ . '/includes/supabase_helper.php';

// ─── Session Hardening ─────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// Enable secure cookies when on HTTPS
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start();

header('Content-Type: application/json');

// ─── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// ─── Parse Request ─────────────────────────────────────────────────────────────
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['access_token']) || empty(trim($data['access_token']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing access token']);
    exit();
}

$accessToken = trim($data['access_token']);

// ─── Step 1: Verify Token Server-Side ──────────────────────────────────────────
$user = verifySupabaseToken($accessToken);

if (!$user) {
    http_response_code(401);
    logSecurityEvent('AUTH_FAIL: Invalid or expired token presented', '', 'Medium');
    echo json_encode(['success' => false, 'message' => 'Invalid or expired authentication token']);
    exit();
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
    http_response_code(403);
    logSecurityEvent("AUTH_DENIED: Unregistered account attempted login: $email", $email, 'High');
    echo json_encode([
        'success' => false,
        'message' => 'Account not found in the NPC database. Please contact your campus Administrator or Registrar to register your account.'
    ]);
    exit();
}

// ─── Step 3: Account Status Check (Active, Banned, Restricted, Snoozed) ───────
$userStatus = $existingUser['status'] ?? ($existingUser['is_active'] ? 'Active' : 'Inactive');
$isActive = (int)($existingUser['is_active'] ?? 1);

if ($userStatus === 'Banned') {
    http_response_code(403);
    logSecurityEvent("AUTH_DENIED: Banned account attempted login: $email", $email, 'High');
    echo json_encode([
        'success' => false,
        'message' => 'This account has been banned by the Administrator. Access denied.'
    ]);
    exit();
}

if ($userStatus === 'Restricted') {
    http_response_code(403);
    logSecurityEvent("AUTH_DENIED: Restricted account attempted login: $email", $email, 'High');
    echo json_encode([
        'success' => false,
        'message' => 'This account has been restricted by the Administrator. Access denied.'
    ]);
    exit();
}

if ($userStatus === 'Snoozed' || $isActive === 0) {
    http_response_code(403);
    logSecurityEvent("AUTH_DENIED: Snoozed/Inactive account attempted login: $email", $email, 'High');
    echo json_encode([
        'success' => false,
        'message' => 'This account is currently snoozed/deactivated. Please contact the Administrator.'
    ]);
    exit();
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

$_SESSION['user_id'] = $existingUser['id'];
$_SESSION['email'] = $existingUser['email'];
$_SESSION['name'] = $finalName;
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
logSecurityEvent("LOGIN_SUCCESS: $email logged in as $role", $email, 'Low');

http_response_code(200);
echo json_encode([
    'success' => true,
    'role' => $role,
    'csrf_token' => $_SESSION['csrf_token']
]);
?>
