<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== ALL TABLES ===\n";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables) . "\n\n";

echo "=== PROGRAMS ===\n";
$progs = $pdo->query("SELECT * FROM programs")->fetchAll(PDO::FETCH_ASSOC);
foreach ($progs as $p) { echo "{$p['id']} - {$p['code']} ({$p['name']})\n"; }

echo "=== SECTIONS ===\n";
$secs = $pdo->query("SELECT * FROM sections")->fetchAll(PDO::FETCH_ASSOC);
foreach ($secs as $s) { echo "{$s['id']} - {$s['name']} (prog: {$s['program_id']})\n"; }



echo "=== GRADES TABLE ===\n";
$gr = $pdo->query("SELECT * FROM grades")->fetchAll(PDO::FETCH_ASSOC);
echo "Grades count: " . count($gr) . "\n";
foreach ($gr as $g) { print_r($g); }

echo "=== STUDENT_GRADES TABLE ===\n";
$sgr = $pdo->query("SELECT * FROM student_grades")->fetchAll(PDO::FETCH_ASSOC);
echo "Student grades count: " . count($sgr) . "\n";
foreach ($sgr as $sg) { print_r($sg); }


$stmt = $pdo->query("SELECT id, email, role, full_name, is_active FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id']} | Email: {$u['email']} | Role: {$u['role']} | Name: {$u['full_name']} | Active: {$u['is_active']}\n";
}

echo "\n=== CURRENT STUDENTS ===\n";
$stmt2 = $pdo->query("SELECT id, student_number, full_name, email, program, section FROM students");
$students = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $s) {
    echo "ID: {$s['id']} | No: {$s['student_number']} | Email: {$s['email']} | Program: {$s['program']} | Sec: {$s['section']} | Name: {$s['full_name']}\n";
}
echo "\n=== CURRENT CLASSES ===\n";
echo "\n=== CURRENT CLASSES COLUMNS ===\n";
$stmt3 = $pdo->query("DESCRIBE classes");
$cols = $stmt3->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "{$col['Field']} ({$col['Type']})\n";
}
echo "\n=== CURRENT CLASSES ROWS ===\n";
$stmt4 = $pdo->query("SELECT id, code, title, name, section, instructor, instructor_email, room, schedule_day, start_time, end_time, units FROM classes");
$classes = $stmt4->fetchAll(PDO::FETCH_ASSOC);
echo "Total classes: " . count($classes) . "\n";
foreach ($classes as $c) {
    echo "ID: {$c['id']} | Code: {$c['code']} | Title: '{$c['title']}' | Name: '{$c['name']}' | Sec: '{$c['section']}' | Day: '{$c['schedule_day']}' Time: '{$c['start_time']}' - '{$c['end_time']}' | Room: '{$c['room']}' | Inst: '{$c['instructor']}'\n";
}


