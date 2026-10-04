<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
$pdo->exec("UPDATE users SET student_number = '2024-00192' WHERE email = 'student2024001@navotaspolytechniccollege.edu.ph'");
echo "Updated student_number in users table.\n";
