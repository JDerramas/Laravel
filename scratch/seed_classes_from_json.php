<?php
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$jsonPath = __DIR__ . '/../backend/elms_courses.json';
if (!file_exists($jsonPath)) {
    die("elms_courses.json not found\n");
}

$data = json_decode(file_get_contents($jsonPath), true);
$courses = $data['courses'] ?? [];

$stmt = $db->prepare("INSERT INTO classes (id, code, title, name, section, instructor, instructor_email, meeting_link, room, schedule_day, start_time, end_time) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
title = VALUES(title), name = VALUES(name), section = VALUES(section), instructor = VALUES(instructor), instructor_email = VALUES(instructor_email), meeting_link = VALUES(meeting_link), room = VALUES(room)");

$count = 0;
foreach ($courses as $c) {
    $id = $c['id'] ?? ('crs-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $c['code'])));
    $code = $c['code'] ?? 'UNK';
    $title = $c['title'] ?? 'Course';
    $name = $title;
    $section = $c['section'] ?? '2A';
    
    // Assign Prof. Jilo Derramas as instructor for AIS 201 and IS 204 or match
    $inst = $c['instructor'] ?? 'JILO DERRAMAS';
    $instEmail = $c['instructor_email'] ?? 'jderramas251505@navotaspolytechniccollege.edu.ph';
    if (strpos($code, 'AIS 201') !== false || strpos($code, 'IS 204') !== false) {
        $inst = 'JILO DERRAMAS';
        $instEmail = 'jderramas251505@navotaspolytechniccollege.edu.ph';
    }

    $meet = $c['meeting_link'] ?? 'https://meet.google.com/vpm-zskq-yuj';
    $room = $c['room'] ?? 'IT Lab 3 / Online';
    $day = 'Monday / Wednesday';
    $start = '08:00';
    $end = '10:00';

    $stmt->execute([$id, $code, $title, $name, $section, $inst, $instEmail, $meet, $room, $day, $start, $end]);
    $count++;
}

echo "Successfully seeded/updated $count classes into MySQL!\n";
