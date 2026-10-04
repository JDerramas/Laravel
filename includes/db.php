<?php
/**
 * db.php — High-Performance Local MySQL Connection (PDO)
 * Navotas Polytechnic College (NPC) ELMS
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $db   = 'npc_elms';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new \PDOException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode());
    }
}
