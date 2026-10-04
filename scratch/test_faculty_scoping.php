<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== LMS SUBMISSIONS ===\n";
$subs = $pdo->query("SELECT s.id, s.course_code, s.student_number, s.student_name, s.status, a.title 
                     FROM lms_submissions s 
                     LEFT JOIN lms_assignments a ON a.id = s.assignment_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($subs);

echo "\n=== LMS ASSIGNMENTS ===\n";
$asgs = $pdo->query("SELECT id, course_code, title, points FROM lms_assignments")->fetchAll(PDO::FETCH_ASSOC);
print_r($asgs);
