<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
echo "USERS:\n";
print_r($pdo->query("SELECT id, email, student_number, role FROM users")->fetchAll(PDO::FETCH_ASSOC));
echo "\nSTUDENTS:\n";
print_r($pdo->query("SELECT id, user_id, student_number, email FROM students")->fetchAll(PDO::FETCH_ASSOC));
