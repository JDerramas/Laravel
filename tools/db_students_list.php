<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== CURRENT STUDENTS ===\n";
$stmt2 = $pdo->query("SELECT id, user_id, student_number, full_name, email, program, section, status FROM students");
$students = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $s) {
    echo "ID: {$s['id']} | UserID: {$s['user_id']} | No: {$s['student_number']} | Email: {$s['email']} | Prog: {$s['program']} | Sec: {$s['section']} | Status: {$s['status']} | Name: {$s['full_name']}\n";
}
