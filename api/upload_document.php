<?php
/**
 * upload_document.php — Secure Document Upload Handler
 * 
 * Hardened to include:
 * - CSRF verification
 * - Strict MIME type whitelisting
 * - Size limitations (10MB max)
 * - Randomized, secure filenames
 * - Security logging
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();
requireCsrf();

header('Content-Type: application/json');

$target_dir = getDocumentsDir();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error occurred']);
    exit;
}

$file = $_FILES['file'];

// ─── 1. Size Validation (limit configurable via app_settings) ────────────────
$maxSize = intval(getSetting('upload_max_size_mb', '10')) * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(413);
    logSecurityEvent("UPLOAD_REJECTED: File too large ({$file['size']} bytes) by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
    echo json_encode(['error' => 'File exceeds the 10MB limit']);
    exit;
}

// ─── 2. MIME Type Validation ───────────────────────────────────────────────────
$allowedMimeTypes = [
    'application/pdf',
    'application/vnd.ms-excel', // xls
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
    'text/csv',
    'application/msword', // doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
    'application/vnd.ms-powerpoint', // ppt
    'application/vnd.openxmlformats-officedocument.presentationml.presentation', // pptx
    'image/jpeg',
    'image/png'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    http_response_code(415);
    logSecurityEvent("UPLOAD_REJECTED: Invalid file type ($mimeType) by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
    echo json_encode(['error' => 'Invalid file type. Allowed: PDF, Excel, Word, PPT, JPG, PNG']);
    exit;
}

// ─── 3. File Name Randomization ────────────────────────────────────────────────
$title = isset($_POST['title']) && trim($_POST['title']) !== '' ? trim($_POST['title']) : pathinfo($file['name'], PATHINFO_FILENAME);
$category = isset($_POST['category']) ? trim($_POST['category']) : 'Academic';

// Extract the original extension safely
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
// Ensure the extension maps to a safe known type (avoids .php.jpg tricks)
$safeExtensions = ['pdf', 'xls', 'xlsx', 'csv', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $safeExtensions)) {
    $ext = 'bin'; // fallback
}

// Create a randomized filename: hash(uniqid + original_name) + ext
$randomHash = hash('sha256', uniqid('', true) . $file['name']);
$filename = substr($randomHash, 0, 16) . '.' . $ext;
$target_file = $target_dir . DIRECTORY_SEPARATOR . $filename;

// ─── 4. File Size Formatting ───────────────────────────────────────────────────
$bytes = $file['size'];
if ($bytes >= 1048576) {
    $size_formatted = number_format($bytes / 1048576, 1) . ' MB';
} elseif ($bytes >= 1024) {
    $size_formatted = number_format($bytes / 1024, 1) . ' KB';
} else {
    $size_formatted = $bytes . ' B';
}

// ─── 5. Move File, Index in Database & Log ─────────────────────────────────────
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    // Register the document in Supabase server-side (service role)
    $dbRes = supabaseServiceQuery("/rest/v1/documents", 'POST', [[
        'title' => $title,
        'category' => $category,
        'file_url' => $filename,
        'file_size' => $size_formatted,
        'uploaded_by' => $_SESSION['email'] ?? 'Admin'
    ]]);

    if ($dbRes['status'] < 200 || $dbRes['status'] >= 300) {
        // Roll the file back so disk and DB stay consistent
        @unlink($target_file);
        http_response_code(502);
        logSecurityEvent("UPLOAD_DB_FAILED: Could not index $title for {$_SESSION['email']}", $_SESSION['email'], 'High');
        echo json_encode(['error' => 'File saved but database indexing failed. Please retry.']);
        exit;
    }

    logSecurityEvent("DOCUMENT_UPLOADED: $title ($filename) by {$_SESSION['email']}", $_SESSION['email'], 'Low');

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'title' => $title,
        'category' => $category,
        'file_size' => $size_formatted,
        'message' => 'Document uploaded and indexed securely'
    ]);
} else {
    http_response_code(500);
    logSecurityEvent("UPLOAD_FAILED: Could not move file for {$_SESSION['email']}", $_SESSION['email'], 'High');
    echo json_encode(['error' => 'Failed to save file to documents folder']);
}
