<?php
/**
 * health.php — System status probe (public, safe: no sensitive data).
 * Returns JSON with per-subsystem status. Use for uptime monitors and
 * the admin dashboard "System Health" card.
 *
 * Checks:
 *  - php:      version + session extension
 *  - database: Supabase REST reachability (1-row query, service key)
 *  - ai:       OpenRouter key present + query_ai.py present (no LLM call — fast & free)
 *  - storage:  documents dir writable
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

$started = microtime(true);
$checks = [];

// PHP
$checks['php'] = ['status' => 'ok', 'version' => PHP_VERSION];

// Database
try {
    require_once __DIR__ . '/../includes/supabase_helper.php';
    $r = supabaseServiceQuery('/rest/v1/users?select=id&limit=1');
    $checks['database'] = ['status' => ($r['status'] === 200 ? 'ok' : 'degraded'), 'http' => $r['status']];
} catch (Throwable $e) {
    $checks['database'] = ['status' => 'down', 'error' => 'unreachable'];
}

// AI subsystem
require_once __DIR__ . '/../includes/supabase_helper.php';
$env = function_exists('loadEnv') ? loadEnv() : [];
$aiKeyPresent = !empty($env['OPENROUTER_API_KEY']) || !empty(getenv('OPENROUTER_API_KEY'));
$scriptPresent = is_file(dirname(__DIR__) . '/backend/query_ai.py') || is_file(dirname(__DIR__) . '/query_ai.py') || is_file(__DIR__ . '/query_ai.py');
$checks['ai'] = [
    'status' => $scriptPresent ? 'ok' : 'degraded',
    'mode' => $aiKeyPresent ? 'Cloud LLM + Knowledge Base' : 'Local Knowledge Base Engine',
    'key_configured' => $aiKeyPresent,
    'script_present' => $scriptPresent
];

// Storage
require_once __DIR__ . '/../includes/auth.php';
$docDir = getDocumentsDir();
$checks['storage'] = ['status' => (is_dir($docDir) && is_writable($docDir)) ? 'ok' : 'degraded'];

$overall = 'ok';
foreach ($checks as $c) if (($c['status'] ?? '') !== 'ok') $overall = 'degraded';

echo json_encode([
    'status' => $overall,
    'timestamp' => date('c'),
    'latency_ms' => round((microtime(true) - $started) * 1000, 1),
    'checks' => $checks
], JSON_PRETTY_PRINT);
