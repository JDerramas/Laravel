<?php
require_once dirname(__DIR__) . '/includes/supabase_helper.php';
$db = getDB();

// 1. Update jderramas251505 to student in users table
$stmt = $db->prepare("UPDATE users SET 
    role = 'student', 
    full_name = 'Jilo Derramas', 
    program = 'AIS', 
    section = '2A', 
    student_number = '251505',
    status = 'Active',
    is_active = 1
WHERE LOWER(email) = 'jderramas251505@navotaspolytechniccollege.edu.ph'");
$stmt->execute();
echo "Updated jderramas in users: " . $stmt->rowCount() . " rows\n";

// 2. Add or update faculty demo user
$fStmt = $db->prepare("INSERT INTO users (id, email, full_name, role, program, section, student_number, status, is_active)
VALUES ('usr-faculty-01', 'faculty@navotaspolytechniccollege.edu.ph', 'Prof. Edsan Moreno', 'teacher', 'Computer Studies', 'Faculty', 'FAC-001', 'Active', 1)
ON DUPLICATE KEY UPDATE 
    email = 'faculty@navotaspolytechniccollege.edu.ph',
    full_name = 'Prof. Edsan Moreno',
    role = 'teacher',
    program = 'Computer Studies',
    section = 'Faculty',
    student_number = 'FAC-001',
    status = 'Active',
    is_active = 1");
$fStmt->execute();
echo "Updated/inserted faculty demo user: " . $fStmt->rowCount() . " rows\n";

// 3. Ensure students table has jderramas as AIS 2A student
$sStmt = $db->prepare("UPDATE students SET 
    program = 'AIS', 
    section = '2A', 
    student_number = '251505',
    full_name = 'Jilo Derramas',
    status = 'Enrolled',
    scholar_status = 'Scholar'
WHERE LOWER(email) = 'jderramas251505@navotaspolytechniccollege.edu.ph'");
$sStmt->execute();
echo "Updated jderramas in students: " . $sStmt->rowCount() . " rows\n";

// Also ensure student2024001 is AIS 2A
$db->exec("UPDATE students SET program = 'AIS', section = '2A' WHERE user_id = 'usr-student-01'");
$db->exec("UPDATE users SET program = 'AIS', section = '2A' WHERE id = 'usr-student-01'");

echo "\n--- VERIFICATION: CURRENT USERS ---\n";
print_r($db->query("SELECT id, email, full_name, role, program, section, student_number FROM users")->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- VERIFICATION: CURRENT STUDENTS ---\n";
print_r($db->query("SELECT id, student_number, full_name, email, program, section FROM students")->fetchAll(PDO::FETCH_ASSOC));
