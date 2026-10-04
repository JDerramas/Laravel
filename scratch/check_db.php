<?php
require_once __DIR__ . '/../includes/db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT room_id, room_code, status, occupied_by_name, occupied_by_email FROM campus_room_occupancy");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
