<?php
/**
 * Migration: Create campus_room_occupancy & campus_room_logs tables
 */
require_once __DIR__ . '/../../includes/db.php';

try {
    $pdo = getDB();

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS campus_room_occupancy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL UNIQUE,
        room_code VARCHAR(50) NOT NULL,
        room_name VARCHAR(150) NOT NULL,
        floor VARCHAR(10) NOT NULL,
        status ENUM('available', 'occupied', 'reserved', 'maintenance') NOT NULL DEFAULT 'occupied',
        occupied_by_name VARCHAR(100) NOT NULL,
        occupied_by_email VARCHAR(100) NOT NULL,
        occupied_by_role VARCHAR(50) NOT NULL,
        activity_type VARCHAR(100) DEFAULT 'Faculty / Administrative Session',
        announcement TEXT NULL,
        is_faculty_admin_only TINYINT(1) NOT NULL DEFAULT 1,
        virtual_meeting_url VARCHAR(255) NULL,
        started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expected_end_at DATETIME NULL,
        ended_at DATETIME NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_room (room_id),
        INDEX idx_status (status),
        INDEX idx_floor (floor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS campus_room_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL,
        action ENUM('claim', 'release', 'update') NOT NULL,
        occupied_by_name VARCHAR(100) NOT NULL,
        occupied_by_email VARCHAR(100) NOT NULL,
        occupied_by_role VARCHAR(50) NOT NULL,
        announcement TEXT NULL,
        logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "SUCCESS: Tables created successfully in database.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
