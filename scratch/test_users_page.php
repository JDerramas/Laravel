<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
$_SESSION['user_id'] = 'usr-admin-01';
$_SESSION['email'] = 'admin@navotaspolytechniccollege.edu.ph';
$_SESSION['role'] = 'admin';
$_SESSION['base_role'] = 'admin';
$_SESSION['name'] = 'System Administrator';

$_SERVER['PHP_SELF'] = '/admin/security/users.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
try {
    include __DIR__ . '/../admin/security/users.php';
    $output = ob_get_clean();
    echo "SUCCESS: Output length = " . strlen($output) . " bytes\n";
    echo "First 200 chars:\n" . substr($output, 0, 200) . "\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "CAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
