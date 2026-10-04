<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * index.php — Root Entry Point & Strict Role-Based Portal Router
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Architecture & Purpose:
 *   Acts as the traffic controller when a user visits the root domain (e.g. `http://localhost/`).
 *   Rather than serving a generic homepage, it validates the authenticated session
 *   against the live MySQL database and routes the client directly into their
 *   dedicated, role-locked portal ecosystem:
 *
 * Routing Rules:
 *   - Faculty / Teachers: Strictly redirected to `/teacher/index.php`.
 *   - Students: Strictly redirected to `/student/index.php`.
 *   - System Administrators & Registrars: Strictly redirected to `/admin/index.php`.
 *   - Unauthenticated Guests: Intercepted by `require_login()` and redirected to `/login.php`.
 *
 * Privacy & Security Guarantee:
 *   By invoking `require_login()`, this file triggers `isSessionValid()`, ensuring that any
 *   role changes made in the database take effect immediately on page visit or refresh.
 */

require_once __DIR__ . '/includes/auth.php';

// Validates the session and synchronizes roles with live MySQL records
require_login();

// Retrieve role context
$role = $_SESSION['role'] ?? 'student';
$baseRole = $_SESSION['base_role'] ?? $role;

/**
 * 1. Faculty / Teacher Portal Routing:
 * Non-admin faculty accounts must never enter the admin portal.
 */
if (in_array($role, ['teacher', 'faculty']) || in_array($baseRole, ['teacher', 'faculty'])) {
    if (!in_array($baseRole, ['admin', 'registrar'])) {
        header('Location: /teacher/index.php');
        exit();
    }
}

/**
 * 2. Student Portal Routing:
 * Non-admin student accounts must never enter the admin portal.
 */
if ($role === 'student' && !in_array($baseRole, ['admin', 'registrar'])) {
    header('Location: /student/index.php');
    exit();
}

/**
 * 3. Administrator Portal Routing:
 * Verified administrators and registrars entering the default root are routed to the admin hub.
 */
if (in_array($role, ['admin', 'registrar']) && in_array($baseRole, ['admin', 'registrar'])) {
    header('Location: /admin/index.php');
    exit();
}

/**
 * 4. Fallback Routing:
 * Active teacher sessions go to faculty portal; all others default to student portal.
 */
if (in_array($role, ['teacher', 'faculty'])) {
    header('Location: /teacher/index.php');
    exit();
}

header('Location: /student/index.php');
exit();
