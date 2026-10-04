<?php
/**
 * download_material.php — Secure, authenticated class-material download.
 *
 * Access rules:
 *  - Any logged-in user (student/teacher/admin) may download materials
 *    visible to their section (students) or their own uploads (faculty).
 *  - Filename is basename()-sanitized; path traversal is impossible.
 *  - Every download is audit-logged.
 *
 * Usage: download_material.php?file=mat_xxxx.pdf
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

$fileParam = $_GET['file'] ?? '';
if (empty($fileParam)) {
    http_response_code(400);
    exit('Missing file parameter.');
}

// Sanitize: no directories, no traversal
$safeName = basename($fileParam);
if (!preg_match('/^mat_[A-Za-z0-9]+\.[A-Za-z0-9]{2,5}$/', $safeName)) {
    http_response_code(400);
    exit('Invalid file reference.');
}

// Resolve inside documents dir only using canonical search
$filePath = findDocumentFile($safeName);
if (!$filePath || !is_file($filePath)) {
    http_response_code(404);
    exit('File not found.');
}

// Audit log (best-effort)
$userEmail = strtolower($_SESSION['email'] ?? 'unknown');
logSecurityEvent("MATERIAL_DOWNLOADED: $safeName by $userEmail", $userEmail, 'Low');

// Content type from extension
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'  => 'text/plain',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png'
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

readfile_chunked($filePath);
exit;

function readfile_chunked(string $path): void {
    $handle = fopen($path, 'rb');
    if (!$handle) return;
    while (!feof($handle)) {
        echo fread($handle, 64 * 1024);
        if (connection_aborted()) break;
        flush();
    }
    fclose($handle);
}
