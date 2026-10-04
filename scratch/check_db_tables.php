<?php
require_once __DIR__ . '/../includes/db.php';
$db = getDB();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "=== NPC_ELMS TABLES ===\n";
print_r($tables);

foreach (['course_materials', 'assignments', 'assignment_submissions', 'classes', 'courses', 'students', 'users'] as $t) {
    if (in_array($t, $tables)) {
        echo "\n--- Columns in $t ---\n";
        $cols = $db->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "{$c['Field']} ({$c['Type']})\n";
        }
    } else {
        echo "\n--- Table $t DOES NOT EXIST ---\n";
    }
}
