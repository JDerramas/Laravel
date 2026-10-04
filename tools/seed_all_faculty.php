<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$faculty = [
    [
        'id' => 'usr-faculty-01',
        'email' => 'faculty@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Edsan Moreno',
        'student_number' => 'FAC-001',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-02',
        'email' => 'roderick.castillo@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Roderick Castillo',
        'student_number' => 'FAC-002',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-03',
        'email' => 'ernifer.cosmiano@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Ernifer Cosmiano',
        'student_number' => 'FAC-003',
        'role' => 'teacher',
        'program' => 'PE & Athletics',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-04',
        'email' => 'frederick.dador@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Frederick Dador',
        'student_number' => 'FAC-004',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-05',
        'email' => 'marivic.borja@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Marivic Borja',
        'student_number' => 'FAC-005',
        'role' => 'teacher',
        'program' => 'Business Administration',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-06',
        'email' => 'jvku@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Jan Vincent Ku',
        'student_number' => 'FAC-006',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty'
    ],
    [
        'id' => 'usr-faculty-07',
        'email' => 'oict.binavicecharlie@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Charlie Binavice',
        'student_number' => 'FAC-007',
        'role' => 'teacher',
        'program' => 'Computer Studies',
        'section' => 'Faculty'
    ]
];

$passHash = password_hash('npc12345', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (id, email, full_name, student_number, role, program, section, is_active, status, password_hash, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Active', ?, NOW())
                       ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), student_number = VALUES(student_number), role = VALUES(role), program = VALUES(program), section = VALUES(section), is_active = 1, status = 'Active'");

foreach ($faculty as $f) {
    $stmt->execute([
        $f['id'],
        strtolower($f['email']),
        $f['full_name'],
        $f['student_number'],
        $f['role'],
        $f['program'],
        $f['section'],
        $passHash
    ]);
}

echo "=== SEEDED ALL 7 AIS 2A FACULTY MEMBERS ===\n";
$stmt2 = $pdo->query("SELECT id, email, full_name, role FROM users WHERE role = 'teacher'");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Email: {$r['email']} | Name: {$r['full_name']}\n";
}
