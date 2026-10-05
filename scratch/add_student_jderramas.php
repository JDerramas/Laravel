<?php
require_once dirname(__DIR__) . '/includes/db_helper.php';
$db = getDB();

$db->exec("INSERT INTO students (user_id, student_number, full_name, email, program, section, year_level, status, scholar_status) 
VALUES ('872b0d96-9f14-41fd-b4c0-af848788ca8b', '2024-251505', 'Jilo Derramas', 'jderramas251505@navotaspolytechniccollege.edu.ph', 'AIS', '2A', 2, 'Enrolled', 'Scholar')
ON DUPLICATE KEY UPDATE program = 'AIS', section = '2A'");

echo "Students in DB:\n";
print_r($db->query('SELECT id, user_id, email, program, section FROM students')->fetchAll(PDO::FETCH_ASSOC));
