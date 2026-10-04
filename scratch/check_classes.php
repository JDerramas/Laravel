<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
print_r($pdo->query("DESCRIBE classes")->fetchAll(PDO::FETCH_ASSOC));
