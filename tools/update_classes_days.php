<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

// 1. Update days in classes table to complete names
$dayMap = [
    'M' => 'Monday',
    'T' => 'Tuesday',
    'W' => 'Wednesday',
    'Th' => 'Thursday',
    'TH' => 'Thursday',
    'F' => 'Friday',
    'S' => 'Saturday',
    'Sun' => 'Sunday'
];

foreach ($dayMap as $abbr => $full) {
    $stmt = $pdo->prepare("UPDATE classes SET schedule_day = ? WHERE schedule_day = ?");
    $stmt->execute([$full, $abbr]);
}

// 2. Ensure instructor_email is populated in classes table
$instEmails = [
    'DM103' => ['name' => 'CASTILLO, RODERICK', 'email' => 'roderick.castillo@navotaspolytechniccollege.edu.ph'],
    'CC107' => ['name' => 'MORENO, EDSAN', 'email' => 'faculty@navotaspolytechniccollege.edu.ph'],
    'PATHFIT 3' => ['name' => 'COSMIANO, ERNIFER', 'email' => 'ernifer.cosmiano@navotaspolytechniccollege.edu.ph'],
    'IS105' => ['name' => 'DADOR, FREDERICK', 'email' => 'frederick.dador@navotaspolytechniccollege.edu.ph'],
    'DM102' => ['name' => 'BORJA, MARIVIC', 'email' => 'marivic.borja@navotaspolytechniccollege.edu.ph'],
    'ADV02' => ['name' => 'MORENO, EDSAN', 'email' => 'faculty@navotaspolytechniccollege.edu.ph'],
    'ADV04' => ['name' => 'KU, JAN VINCENT', 'email' => 'jvku@navotaspolytechniccollege.edu.ph'],
    'QUAMETH' => ['name' => 'KU, JAN VINCENT', 'email' => 'jvku@navotaspolytechniccollege.edu.ph'],
    'ADV03' => ['name' => 'BINAVICE, CHARLIE', 'email' => 'oict.binavicecharlie@navotaspolytechniccollege.edu.ph']
];

foreach ($instEmails as $code => $info) {
    $stmt = $pdo->prepare("UPDATE classes SET instructor = ?, instructor_email = ? WHERE code = ?");
    $stmt->execute([$info['name'], $info['email'], $code]);
}

echo "=== CLASSES AFTER DAY & EMAIL UPDATE ===\n";
$stmt = $pdo->query("SELECT id, code, title, section, schedule_day, start_time, end_time, instructor, instructor_email FROM classes");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['code']} | Sec: {$r['section']} | Day: {$r['schedule_day']} ({$r['start_time']}-{$r['end_time']}) | Prof: {$r['instructor']} ({$r['instructor_email']})\n";
}
