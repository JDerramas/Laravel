<?php
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

foreach (['faculty_materials', 'faculty_assignments', 'enrollments', 'classes'] as $t) {
    echo "\n--- Columns in $t ---\n";
    $cols = $db->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "{$c['Field']} ({$c['Type']})\n";
    }
}
