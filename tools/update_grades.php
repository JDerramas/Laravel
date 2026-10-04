<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== GRADES COLUMNS ===\n";
$cols = $pdo->query('DESCRIBE grades')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

// Remove old demo grades that are not part of AIS 2A
$pdo->exec("DELETE FROM grades WHERE subject_code IN ('IT101', 'IS201', 'GE104')");

// List of official 9 AIS 2A subjects
$subjects = [
    ['code' => 'CC107', 'title' => 'COMPUTER PROGRAMMING 3', 'units' => 3.0, 'grade' => 1.50, 'status' => 'Passed'],
    ['code' => 'ADV02', 'title' => 'HUMAN COMPUTER INTERACTION', 'units' => 3.0, 'grade' => 1.25, 'status' => 'Passed'],
    ['code' => 'DM102', 'title' => 'FINANCIAL MANAGEMENT', 'units' => 3.0, 'grade' => 1.75, 'status' => 'Passed'],
    ['code' => 'ADV03', 'title' => 'TECHNOPRENEURSHIP', 'units' => 3.0, 'grade' => 1.50, 'status' => 'Passed'],
    ['code' => 'QUAMETH', 'title' => 'QUANTITATIVE METHODS', 'units' => 3.0, 'grade' => 1.75, 'status' => 'Passed'],
    ['code' => 'DM103', 'title' => 'BUSINESS PROCESS MANAGEMENT', 'units' => 3.0, 'grade' => 1.25, 'status' => 'Passed'],
    ['code' => 'IS105', 'title' => 'ENTERPRISE ARCHITECTURE', 'units' => 3.0, 'grade' => 1.50, 'status' => 'Passed'],
    ['code' => 'ADV04', 'title' => 'IS INNOVATIONS AND NEW TECHNOLOGIES', 'units' => 3.0, 'grade' => 1.25, 'status' => 'Passed'],
    ['code' => 'PATHFIT 3', 'title' => 'DANCE', 'units' => 2.0, 'grade' => 1.00, 'status' => 'Passed']
];

$stmt = $pdo->prepare("INSERT INTO grades (student_id, student_number, subject_code, description, units, grade, status, semester, is_published, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, '1st Semester, 2026-2027', 1, NOW())
                       ON DUPLICATE KEY UPDATE description = VALUES(description), units = VALUES(units), grade = VALUES(grade), status = VALUES(status), is_published = 1");

$students = [
    ['id' => '872b0d96-9f14-41fd-b4c0-af848788ca8b', 'num' => '251505'],
    ['id' => 'usr-student-01', 'num' => '2024-00192']
];

foreach ($students as $s) {
    foreach ($subjects as $sub) {
        $stmt->execute([
            $s['id'],
            $s['num'],
            $sub['code'],
            $sub['title'],
            $sub['units'],
            $sub['grade'],
            $sub['status']
        ]);
    }
}

echo "Seeded updated grades for AIS 2A subjects successfully!\n";
