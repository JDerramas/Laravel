<?php
require_once dirname(__DIR__) . '/includes/supabase_helper.php';
$db = getDB();

$stmt = $db->prepare("UPDATE students SET program = 'AIS', section = '2A' WHERE user_id = 'usr-student-01' OR email = 'student2024001@navotaspolytechniccollege.edu.ph'");
$stmt->execute();
echo "Updated students table rows: " . $stmt->rowCount() . "\n";

$students = $db->query('SELECT * FROM students')->fetchAll(PDO::FETCH_ASSOC);
print_r($students);
