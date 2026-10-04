<?php
session_start();
$_SESSION['user_id'] = '872b0d96-9f14-41fd-b4c0-af848788ca8b';
$_SESSION['email'] = 'jderramas251505@navotaspolytechniccollege.edu.ph';
$_SESSION['name'] = 'Jilo Derramas';
$_SESSION['role'] = 'student';
$_SESSION['base_role'] = 'student';
$_SESSION['student_number'] = '251505';
$_SESSION['program'] = 'AIS';
$_SESSION['section'] = '2A';
$_SESSION['last_activity'] = time();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_enrolled_schedule';

ob_start();
include __DIR__ . '/../api/student.php';
$res = ob_get_clean();

echo "Response from api/student.php?action=get_enrolled_schedule:\n";
$data = json_decode($res, true);
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "Section: " . ($data['section'] ?? '') . "\n";
echo "Class count: " . count($data['classes'] ?? []) . "\n";
foreach ($data['classes'] ?? [] as $c) {
    echo "- {$c['code']}: {$c['title']} ({$c['schedule_day']} {$c['start_time']}-{$c['end_time']}) - {$c['instructor']}\n";
}
