<?php
require_once dirname(__DIR__) . '/includes/supabase_helper.php';
$db = getDB();

echo "=== USERS IN MYSQL ===\n";
print_r($db->query("SELECT id, email, full_name, role, program, section FROM users")->fetchAll(PDO::FETCH_ASSOC));

echo "=== STUDENTS IN MYSQL ===\n";
print_r($db->query("SELECT id, user_id, email, program, section, student_number FROM students")->fetchAll(PDO::FETCH_ASSOC));

echo "=== COURSES IN backend/elms_courses.json ===\n";
$elms = json_decode(file_get_contents(dirname(__DIR__) . '/backend/elms_courses.json'), true);
foreach ($elms['courses'] as $c) {
    echo "ID: {$c['id']} | Code: {$c['code']} | Title: {$c['title']} | Section: {$c['section']} | Instructor: {$c['instructor']} ({$c['instructor_email']})\n";
}
