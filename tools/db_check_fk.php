<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$keepUserIds = ['usr-admin-01', '872b0d96-9f14-41fd-b4c0-af848788ca8b', 'usr-student-01'];

// Check tables with user_id or student_id
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in npc_elms:\n";
print_r($tables);

foreach ($tables as $t) {
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $userCols = array_intersect($cols, ['user_id', 'student_id', 'teacher_id', 'faculty_id', 'created_by']);
    if (!empty($userCols)) {
        echo "Table $t has cols: " . implode(', ', $userCols) . "\n";
    }
}
