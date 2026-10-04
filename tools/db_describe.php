<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== USERS SCHEMA ===\n";
$stmt = $pdo->query("DESCRIBE users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n=== STUDENTS SCHEMA ===\n";
$stmt2 = $pdo->query("DESCRIBE students");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}
