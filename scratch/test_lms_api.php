<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Simulate student session
$_SESSION['user_id'] = 'usr-student-01';
$_SESSION['email'] = '2024-00192@navotaspolytechniccollege.edu.ph';
$_SESSION['role'] = 'student';
$_SESSION['base_role'] = 'student';
$_SESSION['name'] = 'Juan Dela Cruz';
$_SESSION['student_number'] = '2024-00192';
$_SESSION['section'] = '2A';
$_SESSION['program'] = 'AIS';

// Test get_courses via elms.php
$_GET['action'] = 'get_courses';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_FILENAME'] = 'elms.php';

ob_start();
include __DIR__ . '/../api/elms.php';
$output = ob_get_clean();

$res = json_decode($output, true);
echo "=== GET_COURSES RESPONSE ===\n";
echo "Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
echo "Courses count: " . count($res['courses'] ?? []) . "\n";
foreach ($res['courses'] as $c) {
    echo "- [{$c['code']}] {$c['title']} | Modules: " . count($c['modules']) . " | Tasks: " . count($c['assignments']) . "\n";
    foreach ($c['modules'] as $m) {
        echo "   * Module: {$m['title']} ({$m['file_name']})\n";
    }
    foreach ($c['assignments'] as $a) {
        echo "   * Task: {$a['title']} | Status: {$a['status']} | Score: " . ($a['score'] ?? 'None') . "\n";
    }
}
