<?php
/**
 * api/rest.php — High-Performance Local REST Gateway
 * Provides direct RESTful query access to local MySQL for frontend components.
 * Replaces remote Supabase REST endpoints (/rest/v1/*) with local PDO queries.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helper.php';

$table = $_GET['table'] ?? '';
if (empty($table)) {
    // Check path info: /api/rest.php/classes or query string table
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $table = trim($pathInfo, '/');
}

if (empty($table)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing table parameter']);
    exit;
}

// Build query string excluding 'table'
$queryParams = $_GET;
unset($queryParams['table']);
$queryString = http_build_query($queryParams);
$endpoint = '/rest/v1/' . $table . ($queryString ? '?' . $queryString : '');

$method = $_SERVER['REQUEST_METHOD'];
$body = null;
if (in_array($method, ['POST', 'PATCH', 'PUT'])) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $body = json_decode($raw, true);
    }
}

$res = supabaseServiceQuery($endpoint, $method, $body);

http_response_code($res['status'] ?? 200);
echo json_encode($res['data'] ?? []);
