<?php
/**
 * delete_document.php — Secure Document Deletion Handler
 * 
 * Hardened to include:
 * - CSRF verification
 * - Security logging
 * - Path traversal prevention
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();
requireCsrf();

header('Content-Type: application/json');

$target_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'documents';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Use basename to prevent path traversal (e.g., ../../../etc/passwd)
$filename = isset($data['filename']) ? basename($data['filename']) : '';
$dbId = trim($data['id'] ?? '');

if (empty($filename) && empty($dbId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Filename or document id is required']);
    exit;
}

$file_path = $target_dir . DIRECTORY_SEPARATOR . $filename;
$fileDeleted = true;

if ($filename !== '' && file_exists($file_path)) {
    $fileDeleted = unlink($file_path);
} elseif ($filename === '') {
    // No filename given — resolve it from the DB row before deleting
    $rowQuery = supabaseServiceQuery("/rest/v1/documents?id=eq." . rawurlencode($dbId) . "&select=file_url&limit=1");
    if ($rowQuery['status'] === 200 && !empty($rowQuery['data'])) {
        $resolved = basename($rowQuery['data'][0]['file_url'] ?? '');
        if ($resolved !== '') {
            $candidate = $target_dir . DIRECTORY_SEPARATOR . $resolved;
            if (file_exists($candidate)) $fileDeleted = unlink($candidate);
        }
    }
}

// Remove the database record server-side
if ($dbId !== '') {
    supabaseServiceQuery("/rest/v1/documents?id=eq." . rawurlencode($dbId), 'DELETE');
}

logSecurityEvent("DOCUMENT_DELETED: $filename (id: $dbId) by {$_SESSION['email']}", $_SESSION['email'], 'Medium');

echo json_encode([
    'success' => true,
    'file_deleted' => $fileDeleted,
    'note' => $fileDeleted ? '' : 'File could not be removed from disk but the record was deleted'
]);
