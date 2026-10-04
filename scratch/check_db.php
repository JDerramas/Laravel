<?php
require_once dirname(__DIR__) . '/includes/supabase_helper.php';
$db = getDB();

echo "=== CLASSES COLUMNS ===\n";
$cols = $db->query('DESCRIBE classes')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n=== ALL CLASSES ROWS ===\n";
$rows = $db->query('SELECT * FROM classes')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
