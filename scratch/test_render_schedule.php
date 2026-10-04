<?php
session_start();
$_SESSION['user_id'] = 'usr-student-01';
$_SESSION['email'] = 'student2024001@navotaspolytechniccollege.edu.ph';
$_SESSION['name'] = 'Lovi Student';
$_SESSION['role'] = 'student';
$_SESSION['base_role'] = 'student';
$_SESSION['student_number'] = '2024-00192';
$_SESSION['program'] = 'AIS';
$_SESSION['section'] = '2A';
$_SESSION['last_activity'] = time();

ob_start();
include __DIR__ . '/../student/schedule.php';
$html = ob_get_clean();

// Check if assigned section is AIS 2A
preg_match('/id="student-section-pill">([^<]+)<\/span>/', $html, $m);
echo "Student section pill: " . ($m[1] ?? 'NOT FOUND') . "\n";

// Check total units badge
preg_match('/id="total-units-badge">([^<]+)<\/span>/', $html, $m2);
echo "Total units badge: " . ($m2[1] ?? 'NOT FOUND') . "\n";

// Count table tr in #schedule-table-tbody
preg_match('/<tbody id="schedule-table-tbody"[^>]*>(.*?)<\/tbody>/s', $html, $tb);
if (!empty($tb[1])) {
    $trCount = substr_count($tb[1], '<tr');
    echo "Rows in schedule-table-tbody: " . $trCount . "\n";
    preg_match_all('/data-label="Subject Code"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/s', $tb[1], $codes);
    echo "Subject codes rendered: " . implode(', ', array_map('trim', $codes[1] ?? [])) . "\n";
} else {
    echo "Tbody not found!\n";
}
