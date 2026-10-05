<?php
require_once dirname(__DIR__) . '/includes/db_helper.php';
$db = getDB();

echo "=== ALL STUDENTS ===\n";
print_r($db->query('SELECT * FROM students')->fetchAll(PDO::FETCH_ASSOC));

echo "=== ALL USERS ===\n";
print_r($db->query('SELECT id, email, full_name, role, program, section FROM users')->fetchAll(PDO::FETCH_ASSOC));
