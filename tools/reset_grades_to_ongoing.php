<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

// 1. Reset all grades in grades table to Ongoing / No numerical grade
$pdo->exec("UPDATE grades SET grade = NULL, status = 'Ongoing', is_published = 1");

// 2. Clear student_grades detailed period breakdowns so there are no fake preliminary grades
$pdo->exec("TRUNCATE TABLE student_grades");

echo "=== GRADES RESET TO ONGOING (NO NUMERICAL GRADES YET) ===\n";
$stmt = $pdo->query("SELECT id, student_number, subject_code, description, units, grade, status FROM grades");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['subject_code']} - {$r['description']} | Units: {$r['units']} | Grade: " . ($r['grade'] !== null ? $r['grade'] : 'NULL') . " | Status: {$r['status']}\n";
}
