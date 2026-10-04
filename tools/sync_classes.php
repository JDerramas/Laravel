<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
$pdo->exec("UPDATE classes SET name = title WHERE (name IS NULL OR name = '') AND (title IS NOT NULL AND title != '')");
echo "Classes name synced!\n";
$stmt = $pdo->query("SELECT id, code, title, name, section, schedule_day, start_time, end_time, instructor FROM classes");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($classes as $c) {
    echo "{$c['code']} - {$c['name']} ({$c['section']}) -> {$c['schedule_day']} {$c['start_time']}-{$c['end_time']} [{$c['instructor']}]\n";
}
