<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
echo "USERS SCHEMA:\n";
print_r($pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC));
echo "\nSTUDENTS SCHEMA:\n";
print_r($pdo->query("DESCRIBE students")->fetchAll(PDO::FETCH_ASSOC));
