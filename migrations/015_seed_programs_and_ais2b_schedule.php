<?php
/**
 * migrations/015_seed_programs_and_ais2b_schedule.php
 *
 * 1. Seeds default academic programs into `programs` table
 * 2. Seeds standard academic sections into `sections` table
 * 3. Seeds subject catalog into `subjects` table
 * 4. Ensures professors exist in `users` table
 * 5. Seeds the official schedule for AIS 2B into `classes` table
 */

require_once __DIR__ . '/../includes/supabase_helper.php';

$db = getDB();

echo "Starting migration: 015_seed_programs_and_ais2b_schedule...\n";

// ── 1. Seed Programs Table ──
$programs = [
    [
        'id' => 'prog-ais',
        'code' => 'AIS',
        'name' => 'Associate in Information Systems',
        'department' => 'College of Computer Studies'
    ],
    [
        'id' => 'prog-bsis',
        'code' => 'BSIS',
        'name' => 'Bachelor of Science in Information Systems',
        'department' => 'College of Computer Studies'
    ],
    [
        'id' => 'prog-bsba-hr',
        'code' => 'BSBA - HR',
        'name' => 'BS Business Administration Major in Human Resource Management',
        'department' => 'College of Business Administration'
    ],
    [
        'id' => 'prog-bsba-fm',
        'code' => 'BSBA - FM',
        'name' => 'BS Business Administration Major in Financial Management',
        'department' => 'College of Business Administration'
    ],
    [
        'id' => 'prog-bsba-mm',
        'code' => 'BSBA - MM',
        'name' => 'BS Business Administration Major in Marketing Management',
        'department' => 'College of Business Administration'
    ],
    [
        'id' => 'prog-bsed',
        'code' => 'BSEd',
        'name' => 'Bachelor of Secondary Education',
        'department' => 'College of Education'
    ],
    [
        'id' => 'prog-beed',
        'code' => 'BEEd',
        'name' => 'Bachelor of Elementary Education',
        'department' => 'College of Education'
    ]
];

$progStmt = $db->prepare("INSERT INTO programs (id, code, name, department) 
    VALUES (?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE name=VALUES(name), department=VALUES(department)");

foreach ($programs as $p) {
    $progStmt->execute([$p['id'], $p['code'], $p['name'], $p['department']]);
    echo "  [Program] Synced: {$p['code']} - {$p['name']}\n";
}

// ── 2. Seed Subjects Table ──
$subjects = [
    ['id' => 'subj-cc107', 'code' => 'CC107', 'title' => 'Computer Programming 3', 'units' => 3.0, 'lecture_hours' => 2, 'lab_hours' => 3],
    ['id' => 'subj-adv02', 'code' => 'ADV02', 'title' => 'Human Computer Interaction', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-dm102', 'code' => 'DM102', 'title' => 'Financial Management', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-pathfit3', 'code' => 'PATHFIT 3', 'title' => 'Dance', 'units' => 2.0, 'lecture_hours' => 2, 'lab_hours' => 0],
    ['id' => 'subj-quameth', 'code' => 'QUAMETH', 'title' => 'Quantitative Methods', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-adv03', 'code' => 'ADV03', 'title' => 'Technopreneurship', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-adv04', 'code' => 'ADV04', 'title' => 'IS Innovations and New Technologies', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-dm103', 'code' => 'DM103', 'title' => 'Business Process Management', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
    ['id' => 'subj-is105', 'code' => 'IS105', 'title' => 'Enterprise Architecture', 'units' => 3.0, 'lecture_hours' => 3, 'lab_hours' => 0],
];

$subjStmt = $db->prepare("INSERT INTO subjects (id, code, title, units, lecture_hours, lab_hours)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title), units=VALUES(units), lecture_hours=VALUES(lecture_hours), lab_hours=VALUES(lab_hours)");

foreach ($subjects as $s) {
    $subjStmt->execute([$s['id'], $s['code'], $s['title'], $s['units'], $s['lecture_hours'], $s['lab_hours']]);
    echo "  [Subject] Synced: {$s['code']} - {$s['title']}\n";
}

// ── 3. Ensure Professors Exist in Users Table ──
$professors = [
    [
        'id' => 'usr-faculty-edsan',
        'email' => 'edsan.moreno@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Edsan Moreno',
        'program' => 'College of Computer Studies',
    ],
    [
        'id' => 'usr-faculty-marivic',
        'email' => 'marivic.borja@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Marivic Borja',
        'program' => 'College of Business Administration',
    ],
    [
        'id' => 'usr-faculty-aquilio',
        'email' => 'aquilio.collingwood@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Aquilio Collingwood',
        'program' => 'Department of Physical Education',
    ],
    [
        'id' => 'usr-faculty-jvku',
        'email' => 'jvku@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Jan Vincent Ku',
        'program' => 'College of Computer Studies',
    ],
    [
        'id' => 'usr-faculty-dhani',
        'email' => 'dhani.sanjose@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Dhani San Jose',
        'program' => 'College of Computer Studies',
    ],
    [
        'id' => 'usr-faculty-roderick',
        'email' => 'roderick.castillo@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Roderick Castillo',
        'program' => 'College of Computer Studies',
    ],
    [
        'id' => 'usr-faculty-frederick',
        'email' => 'frederick.dador@navotaspolytechniccollege.edu.ph',
        'full_name' => 'Prof. Frederick Dador',
        'program' => 'College of Computer Studies',
    ],
];

$userStmt = $db->prepare("INSERT INTO users (id, email, full_name, role, status, is_active, password_hash, program)
    VALUES (?, ?, ?, 'teacher', 'Active', 1, 'oauth', ?)
    ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role='teacher', status='Active', is_active=1");

foreach ($professors as $prof) {
    $userStmt->execute([$prof['id'], $prof['email'], $prof['full_name'], $prof['program']]);
    echo "  [Faculty] Ensured: {$prof['full_name']} ({$prof['email']})\n";
}

// ── 4. Seed Schedule for AIS 2B in Classes Table ──
$ais2bClasses = [
    [
        'id' => 'cls-ais2b-01',
        'code' => 'CC107',
        'title' => 'Computer Programming 3',
        'name' => 'Computer Programming 3',
        'section' => 'AIS 2B',
        'schedule_day' => 'Monday',
        'start_time' => '07:00 AM',
        'end_time' => '10:00 AM',
        'instructor' => 'Moreno, Edsan',
        'instructor_email' => 'edsan.moreno@navotaspolytechniccollege.edu.ph',
        'room' => 'Computer Lab 1',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-02',
        'code' => 'ADV02',
        'title' => 'Human Computer Interaction',
        'name' => 'Human Computer Interaction',
        'section' => 'AIS 2B',
        'schedule_day' => 'Monday',
        'start_time' => '10:30 AM',
        'end_time' => '01:30 PM',
        'instructor' => 'Moreno, Edsan',
        'instructor_email' => 'edsan.moreno@navotaspolytechniccollege.edu.ph',
        'room' => 'Computer Lab 1',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-03',
        'code' => 'DM102',
        'title' => 'Financial Management',
        'name' => 'Financial Management',
        'section' => 'AIS 2B',
        'schedule_day' => 'Monday',
        'start_time' => '05:30 PM',
        'end_time' => '08:30 PM',
        'instructor' => 'Borja, Marivic',
        'instructor_email' => 'marivic.borja@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 201',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-04',
        'code' => 'PATHFIT 3',
        'title' => 'Dance',
        'name' => 'Dance',
        'section' => 'AIS 2B',
        'schedule_day' => 'Thursday',
        'start_time' => '02:30 PM',
        'end_time' => '04:30 PM',
        'instructor' => 'Collingwood, Aquilio',
        'instructor_email' => 'aquilio.collingwood@navotaspolytechniccollege.edu.ph',
        'room' => 'Dance Studio',
        'units' => 2.0
    ],
    [
        'id' => 'cls-ais2b-05',
        'code' => 'QUAMETH',
        'title' => 'Quantitative Methods',
        'name' => 'Quantitative Methods',
        'section' => 'AIS 2B',
        'schedule_day' => 'Friday',
        'start_time' => '10:30 AM',
        'end_time' => '01:30 PM',
        'instructor' => 'Ku, Jan Vincent',
        'instructor_email' => 'jvku@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 202',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-06',
        'code' => 'ADV03',
        'title' => 'Technopreneurship',
        'name' => 'Technopreneurship',
        'section' => 'AIS 2B',
        'schedule_day' => 'Friday',
        'start_time' => '05:30 PM',
        'end_time' => '08:30 PM',
        'instructor' => 'San Jose, Dhani',
        'instructor_email' => 'dhani.sanjose@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 203',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-07',
        'code' => 'ADV04',
        'title' => 'IS Innovations and New Technologies',
        'name' => 'IS Innovations and New Technologies',
        'section' => 'AIS 2B',
        'schedule_day' => 'Saturday',
        'start_time' => '10:30 AM',
        'end_time' => '01:30 PM',
        'instructor' => 'Ku, Jan Vincent',
        'instructor_email' => 'jvku@navotaspolytechniccollege.edu.ph',
        'room' => 'Computer Lab 2',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-08',
        'code' => 'DM103',
        'title' => 'Business Process Management',
        'name' => 'Business Process Management',
        'section' => 'AIS 2B',
        'schedule_day' => 'Saturday',
        'start_time' => '02:00 PM',
        'end_time' => '05:00 PM',
        'instructor' => 'Castillo, Roderick',
        'instructor_email' => 'roderick.castillo@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 204',
        'units' => 3.0
    ],
    [
        'id' => 'cls-ais2b-09',
        'code' => 'IS105',
        'title' => 'Enterprise Architecture',
        'name' => 'Enterprise Architecture',
        'section' => 'AIS 2B',
        'schedule_day' => 'Saturday',
        'start_time' => '05:30 PM',
        'end_time' => '08:30 PM',
        'instructor' => 'Dador, Frederick',
        'instructor_email' => 'frederick.dador@navotaspolytechniccollege.edu.ph',
        'room' => 'Room 205',
        'units' => 3.0
    ]
];

$clsStmt = $db->prepare("INSERT INTO classes (id, code, title, name, section, schedule_day, start_time, end_time, instructor, instructor_email, room, units)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        title=VALUES(title),
        name=VALUES(name),
        section=VALUES(section),
        schedule_day=VALUES(schedule_day),
        start_time=VALUES(start_time),
        end_time=VALUES(end_time),
        instructor=VALUES(instructor),
        instructor_email=VALUES(instructor_email),
        room=VALUES(room),
        units=VALUES(units)");

foreach ($ais2bClasses as $c) {
    $clsStmt->execute([
        $c['id'],
        $c['code'],
        $c['title'],
        $c['name'],
        $c['section'],
        $c['schedule_day'],
        $c['start_time'],
        $c['end_time'],
        $c['instructor'],
        $c['instructor_email'],
        $c['room'],
        $c['units']
    ]);
    echo "  [Class Schedule] Synced: {$c['code']} - {$c['title']} ({$c['section']}) | {$c['schedule_day']} {$c['start_time']}–{$c['end_time']} | {$c['instructor']}\n";
}

echo "Migration completed successfully!\n";
