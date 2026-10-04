<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT id, code, title, section, instructor, instructor_email, room, schedule_day, start_time, end_time FROM classes");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Classes count: " . count($classes) . "\n";
print_r($classes);
