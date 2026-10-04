<?php
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$accounts = [
    [
        'id' => 'usr-admin-01',
        'email' => 'admin@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Administrator',
        'student_number' => 'ADMIN-001',
        'role' => 'admin',
        'program' => 'Administration',
        'section' => 'Staff',
        'status' => 'Active',
        'scholar' => 'Non-Scholar'
    ],
    [
        'id' => '872b0d96-9f14-41fd-b4c0-af848788ca8b',
        'email' => 'jderramas251505@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Jilo Derramas',
        'student_number' => '251505',
        'role' => 'student',
        'program' => 'AIS',
        'section' => '2A',
        'status' => 'Active',
        'scholar' => 'Scholar'
    ],
    [
        'id' => 'usr-faculty-01',
        'email' => 'faculty@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Edsan Moreno',
        'student_number' => 'FAC-001',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty',
        'status' => 'Active',
        'scholar' => 'Non-Scholar'
    ],
    [
        'id' => 'usr-student-01',
        'email' => 'student2024001@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Lovi Student',
        'student_number' => '2024-00192',
        'role' => 'student',
        'program' => 'AIS',
        'section' => '2A',
        'status' => 'Active',
        'scholar' => 'Scholar'
    ]
];

$passHash = password_hash('npc12345', PASSWORD_DEFAULT);

foreach ($accounts as $acc) {
    // Check if exists in users
    $chk = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ? OR id = ?");
    $chk->execute([strtolower($acc['email']), $acc['id']]);
    $existing = $chk->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $db->prepare("UPDATE users SET email = ?, full_name = ?, student_number = ?, role = ?, program = ?, section = ?, is_active = 1, status = 'Active', password_hash = ? WHERE id = ?")
           ->execute([$acc['email'], $acc['full_name'], $acc['student_number'], $acc['role'], $acc['program'], $acc['section'], $passHash, $existing['id']]);
        $uid = $existing['id'];
    } else {
        $db->prepare("INSERT INTO users (id, email, full_name, student_number, role, program, section, is_active, status, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Active', ?, NOW())")
           ->execute([$acc['id'], $acc['email'], $acc['full_name'], $acc['student_number'], $acc['role'], $acc['program'], $acc['section'], $passHash]);
        $uid = $acc['id'];
    }

    // If student, ensure in students table
    if ($acc['role'] === 'student') {
        $sChk = $db->prepare("SELECT id FROM students WHERE user_id = ? OR LOWER(email) = ?");
        $sChk->execute([$uid, strtolower($acc['email'])]);
        $sRow = $sChk->fetch(PDO::FETCH_ASSOC);

        if ($sRow) {
            $db->prepare("UPDATE students SET user_id = ?, student_number = ?, full_name = ?, email = ?, program = ?, section = ?, year_level = 2, status = 'Enrolled', scholar_status = ? WHERE id = ?")
               ->execute([$uid, $acc['student_number'], $acc['full_name'], $acc['email'], $acc['program'], $acc['section'], $acc['scholar'], $sRow['id']]);
        } else {
            $db->prepare("INSERT INTO students (user_id, student_number, full_name, email, program, section, year_level, status, scholar_status, created_at) VALUES (?, ?, ?, ?, ?, ?, 2, 'Enrolled', ?, NOW())")
               ->execute([$uid, $acc['student_number'], $acc['full_name'], $acc['email'], $acc['program'], $acc['section'], $acc['scholar']]);
        }
    }
}

echo "=== SEEDED DEFAULT ACCOUNTS SUCCESSFULLY ===\n";
$users = $db->query("SELECT id, email, role, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
$stds = $db->query("SELECT id, user_id, email, full_name, program, section, scholar_status FROM students")->fetchAll(PDO::FETCH_ASSOC);
print_r($stds);
