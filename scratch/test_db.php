<?php
require 'includes/db.php';
try {
    $stmt = getDB()->query('SELECT code, section, room, schedule_day, start_time, end_time FROM classes');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
