<?php
/**
 * import_students.php — Hardened bulk student importer (admin only)
 *
 * Security measures:
 *  - CSRF + admin session required
 *  - Extension AND real MIME type validation (finfo)
 *  - Temp files written OUTSIDE the web root (system temp dir)
 *  - Parsed rows validated: official email domain, name/number format, row cap
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/supabase_helper.php';

require_admin();
requireCsrf();

header('Content-Type: application/json');

const OFFICIAL_EMAIL_DOMAIN = '@navotaspolytechniccollege.edu.ph';
const MAX_IMPORT_ROWS = 2000;

// Validate program/section labels: letters, digits, spaces, dashes only
$program = isset($_POST['program']) ? trim($_POST['program']) : 'BSIS';
$section = isset($_POST['section']) ? trim($_POST['section']) : '1A';
if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 \-]{0,31}$/', $program) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9\-]{0,15}$/', $section)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid program or section value.']);
    exit;
}

$parsed_students = [];

/**
 * Run the python parser on a file that lives in the SYSTEM temp dir.
 */
function runParser(string $tempPath): array {
    $cmd = 'python ' . escapeshellarg(__DIR__ . '/parse_students_file.py') . ' ' . escapeshellarg($tempPath);
    $output = shell_exec($cmd);
    @unlink($tempPath);

    if (!$output) return [];
    $json_res = json_decode($output, true);
    if (!is_array($json_res)) return [];
    $students = $json_res['students'] ?? [];
    return is_array($students) ? $students : [];
}

// ─── 1. File upload path ───────────────────────────────────────────────────────
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['file']['tmp_name'];
    $orig_name = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // Extension whitelist
    $allowed_ext = ['csv', 'xlsx', 'xls', 'txt'];
    if (!in_array($ext, $allowed_ext)) {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: csv, xlsx, xls, txt']);
        exit;
    }

    // Real MIME validation (content-based, not extension-based)
    $allowed_mimes = [
        'text/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/zip' // xlsx is a zip container; some finfo builds report this
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        http_response_code(415);
        logSecurityEvent("IMPORT_REJECTED: students file with MIME $mime by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
        echo json_encode(['success' => false, 'message' => "File content ($mime) does not look like a valid CSV/Excel document."]);
        exit;
    }

    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'File exceeds the 10MB limit.']);
        exit;
    }

    // Store outside the web root so it can never be executed or fetched directly
    $temp_dest = tempnam(sys_get_temp_dir(), 'npc_imp_');
    move_uploaded_file($tmp_name, $temp_dest);

    $parsed_students = runParser($temp_dest);
}
// ─── 2. Pasted emails path ─────────────────────────────────────────────────────
elseif (isset($_POST['emails_text']) && !empty(trim($_POST['emails_text']))) {
    if (strlen($_POST['emails_text']) > 500000) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Pasted text is too large.']);
        exit;
    }
    $temp_dest = tempnam(sys_get_temp_dir(), 'npc_txt_');
    file_put_contents($temp_dest, $_POST['emails_text']);

    $parsed_students = runParser($temp_dest);
}

if (empty($parsed_students)) {
    echo json_encode([
        'success' => false,
        'message' => 'No valid student emails found. Please upload a valid document or paste email addresses.'
    ]);
    exit;
}

// ─── 3. Sanitize every parsed row before touching the database ─────────────────
$records_to_insert = [];
$rejected = 0;

foreach ($parsed_students as $st) {
    $email = strtolower(trim((string)($st['email'] ?? '')));
    $full_name = trim(strip_tags((string)($st['full_name'] ?? '')));
    $student_number = preg_replace('/[^A-Za-z0-9\-]/', '', (string)($st['student_number'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $rejected++; continue; }
    if (!str_ends_with($email, OFFICIAL_EMAIL_DOMAIN)) { $rejected++; continue; }
    if ($full_name === '' || mb_strlen($full_name) > 120) { $rejected++; continue; }

    $records_to_insert[] = [
        'email' => $email,
        'full_name' => substr($full_name, 0, 120),
        'student_number' => $student_number !== '' ? substr($student_number, 0, 24) : null,
        'role' => 'student',
        'program' => $program,
        'section' => $section,
        'password_hash' => 'oauth'
    ];

    if (count($records_to_insert) >= MAX_IMPORT_ROWS) break;
}

if (empty($records_to_insert)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All parsed rows were invalid. Students must use official ' . OFFICIAL_EMAIL_DOMAIN . ' addresses.']);
    exit;
}

// Batch upsert via Service Role Key
$result = supabaseServiceQuery(
    "/rest/v1/users",
    'POST',
    $records_to_insert,
    ["Prefer: resolution=merge-duplicates"]
);

if ($result['status'] >= 200 && $result['status'] < 300) {
    logSecurityEvent("STUDENTS_IMPORTED: " . count($records_to_insert) . " students to $program-$section by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
    echo json_encode([
        'success' => true,
        'total_found' => count($parsed_students),
        'imported' => count($records_to_insert),
        'rejected_invalid' => $rejected,
        'program' => $program,
        'section' => $section,
        'students' => $records_to_insert,
        'message' => "Successfully imported " . count($records_to_insert) . " students to $program - Section $section!" .
                     ($rejected > 0 ? " ($rejected rows skipped as invalid.)" : '')
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save to Supabase: ' . json_encode($result['data']),
        'students' => $records_to_insert
    ]);
}
