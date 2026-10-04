<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== 1. ADDING COLUMNS IF MISSING ===\n";

// Check if status in users
$colsUsers = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetchAll();
if (empty($colsUsers)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Active' AFTER is_active");
    echo "Added status column to users table.\n";
} else {
    echo "Column status already exists in users.\n";
}

// Check if scholar_status in students
$colsStudents = $pdo->query("SHOW COLUMNS FROM students LIKE 'scholar_status'")->fetchAll();
if (empty($colsStudents)) {
    $pdo->exec("ALTER TABLE students ADD COLUMN scholar_status VARCHAR(64) NOT NULL DEFAULT 'Non-Scholar' AFTER status");
    echo "Added scholar_status column to students table.\n";
} else {
    echo "Column scholar_status already exists in students.\n";
}

echo "\n=== 2. CLEANING UP USERS AND STUDENTS ===\n";

$keepUserIds = [
    'usr-admin-01',
    '872b0d96-9f14-41fd-b4c0-af848788ca8b',
    'usr-student-01'
];

$inKeep = "'" . implode("','", $keepUserIds) . "'";

// Find users to delete
$toDelete = $pdo->query("SELECT id, email, full_name FROM users WHERE id NOT IN ($inKeep)")->fetchAll(PDO::FETCH_ASSOC);
echo "Users to delete count: " . count($toDelete) . "\n";
foreach ($toDelete as $u) {
    echo " - Deleting: {$u['id']} ({$u['email']})\n";
}

// Find student IDs to delete
$studentsToDelete = $pdo->query("SELECT id, user_id, email, student_number FROM students WHERE user_id NOT IN ($inKeep)")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($studentsToDelete)) {
    $studentIds = array_column($studentsToDelete, 'id');
    $stIn = implode(',', $studentIds);
    $pdo->exec("DELETE FROM attendance_records WHERE student_id IN ($stIn)");
    $pdo->exec("DELETE FROM enrollments WHERE student_id IN ($stIn)");
    $pdo->exec("DELETE FROM grades WHERE student_id IN ($stIn)");
    $stNumbers = array_filter(array_column($studentsToDelete, 'student_number'));
    if (!empty($stNumbers)) {
        $stNumIn = "'" . implode("','", array_map('addslashes', $stNumbers)) . "'";
        $pdo->exec("DELETE FROM student_grades WHERE student_number IN ($stNumIn)");
    }
    $pdo->exec("DELETE FROM students WHERE id IN ($stIn)");
    echo "Cleaned up non-default records from students and student associations.\n";
}

// Delete other user references
$delUserIds = array_column($toDelete, 'id');
if (!empty($delUserIds)) {
    $delIn = "'" . implode("','", $delUserIds) . "'";
    try {
        $pdo->exec("DELETE FROM chat_messages WHERE conversation_id IN (SELECT id FROM chat_conversations WHERE user_id IN ($delIn))");
    } catch (\Exception $e) {}
    try {
        $pdo->exec("DELETE FROM chat_conversations WHERE user_id IN ($delIn)");
    } catch (\Exception $e) {}
    try {
        $pdo->exec("DELETE FROM user_notification_preferences WHERE user_id IN ($delIn)");
    } catch (\Exception $e) {}
    try {
        $pdo->exec("DELETE FROM audit_logs WHERE user_id IN ($delIn)");
    } catch (\Exception $e) {}
    try {
        $pdo->exec("DELETE FROM security_logs WHERE user_id IN ($delIn)");
    } catch (\Exception $e) {}
    try {
        $pdo->exec("DELETE FROM live_online_presence WHERE user_id IN ($delIn)");
    } catch (\Exception $e) {}
    $pdo->exec("DELETE FROM users WHERE id IN ($delIn)");
    echo "Deleted users outside the 3 default accounts.\n";
}

echo "\n=== 3. VERIFYING REMAINING USERS ===\n";
$remUsers = $pdo->query("SELECT id, email, role, full_name, is_active, status, program, section FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($remUsers as $u) {
    echo "User: {$u['id']} | {$u['email']} | Role: {$u['role']} | Status: {$u['status']} | Prog: {$u['program']} | Sec: {$u['section']}\n";
}

echo "\n=== 4. VERIFYING REMAINING STUDENTS ===\n";
$remStudents = $pdo->query("SELECT id, user_id, student_number, full_name, email, program, section, status, scholar_status FROM students")->fetchAll(PDO::FETCH_ASSOC);
foreach ($remStudents as $s) {
    echo "Student: {$s['id']} | UserID: {$s['user_id']} | {$s['email']} | Prog: {$s['program']} | Sec: {$s['section']} | Status: {$s['status']} | Scholar: {$s['scholar_status']}\n";
}
