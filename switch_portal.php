<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * switch_portal.php — Administrative Portal Transition Controller
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Purpose:
 *   Enables authenticated system administrators and registrars to switch their
 *   active portal perspective (between Administrator, Faculty, and Student) for
 *   testing, demonstration, quality assurance, and oversight purposes.
 *
 * Security Architecture & Access Control:
 *   - Strictly validates the user's authentic baseline database role (`base_role`).
 *   - Only users with a permanent database role of 'admin' or 'registrar' are permitted
 *     to utilize this switching mechanism.
 *   - Non-administrators (pure faculty members and students) are explicitly forbidden
 *     from switching portals and are immediately bounced back to their dedicated portal.
 *
 * Supported Target Views:
 *   - `?to=teacher` or `?to=faculty`: Switches active context to Faculty Portal.
 *   - `?to=student`: Switches active context to Student Portal (injects valid demo student metadata if missing).
 *   - `?to=admin`: Reverts active context back to Admin Portal.
 */

require_once __DIR__ . '/includes/auth.php';

// Authenticate session and sync live database state
require_login();

// Retrieve requested target portal from query string
$to = strtolower(trim($_GET['to'] ?? ''));

// Authoritative baseline role retrieved directly from verified session
$baseRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';

/**
 * Access Control Enforcement:
 * Pure student or faculty accounts cannot switch portals. If accessed, bounce immediately.
 */
if (!in_array($baseRole, ['admin', 'registrar'])) {
    $home = in_array($baseRole, ['teacher', 'faculty']) ? '/teacher/index.php' : '/student/index.php';
    header("Location: $home");
    exit();
}

/**
 * Route Request Based on Target View
 */
if ($to === 'teacher' || $to === 'faculty') {
    // Transition to Faculty Portal
    $_SESSION['role'] = 'teacher';
    $_SESSION['active_portal'] = 'faculty';
    header('Location: /teacher/index.php');
    exit();

} elseif ($to === 'student') {
    // Transition to Student Portal
    $_SESSION['role'] = 'student';
    $_SESSION['active_portal'] = 'student';

    // Supply placeholder student identifiers if absent so student database queries execute smoothly
    if (empty($_SESSION['student_number']) || in_array($_SESSION['student_number'], ['N/A', 'ADMIN-001', 'FAC-001', 'GUEST'])) {
        $_SESSION['student_number'] = '2024-00192';
    }
    if (empty($_SESSION['section'])) {
        $_SESSION['section'] = '2A';
    }
    if (empty($_SESSION['program'])) {
        $_SESSION['program'] = 'AIS';
    }

    header('Location: /student/index.php');
    exit();

} else {
    // Default: Return to Administrator Portal
    $_SESSION['role'] = 'admin';
    $_SESSION['active_portal'] = 'admin';
    header('Location: /admin/index.php');
    exit();
}
