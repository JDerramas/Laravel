<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();
echo "--- CLASSES FOR JDERRAMAS ---\n";
$stmt = $db->query("SELECT id, code, title, instructor, instructor_email FROM classes WHERE instructor_email LIKE '%jderramas%'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['id']} | {$r['code']} | {$r['title']} | {$r['instructor']} | {$r['instructor_email']}\n";
}

echo "\n--- STUDENTS FOR JDERRAMAS ---\n";
$stmt2 = $db->query("SELECT id, student_number, full_name, email, program, section FROM students WHERE email LIKE '%jderramas%'");
while ($r2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r2['id']} | {$r2['student_number']} | {$r2['full_name']} | {$r2['email']} | {$r2['program']} | {$r2['section']}\n";
}
