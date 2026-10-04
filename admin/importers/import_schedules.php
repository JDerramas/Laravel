<?php
/**
 * import_schedules.php — Hardened bulk schedule importer (admin only)
 *
 * Security measures:
 *  - CSRF + admin session required
 *  - Extension AND real MIME type validation (finfo)
 *  - Temp files written OUTSIDE the web root (system temp dir)
 *  - Parsed rows sanitized (code/title/day/time/room/units) + row cap
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/supabase_helper.php';

require_admin();
requireCsrf();

header('Content-Type: application/json');

const MAX_IMPORT_ROWS = 1000;

// Validate section labels
$default_section = isset($_POST['section']) && !empty(trim($_POST['section'])) ? trim($_POST['section']) : 'AIS 2A';
if (isset($_POST['program']) && isset($_POST['section_code'])) {
    $default_section = trim($_POST['program']) . ' ' . trim($_POST['section_code']);
}
if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 \-]{0,47}$/', $default_section)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid section value.']);
    exit;
}

$parsed_classes = [];

/**
 * Run the python parser on a file in the SYSTEM temp dir.
 */
function runScheduleParser(string $tempPath): array {
    $cmd = 'python ' . escapeshellarg(__DIR__ . '/parse_schedule_text.py') . ' ' . escapeshellarg($tempPath);
    $output = shell_exec($cmd);
    @unlink($tempPath);

    if (!$output) return [];
    $json_res = json_decode($output, true);
    if (!is_array($json_res)) return [];
    $classes = $json_res['classes'] ?? [];
    return is_array($classes) ? $classes : [];
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

    // Real MIME validation
    $allowed_mimes = [
        'text/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/zip' // xlsx container
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        http_response_code(415);
        logSecurityEvent("IMPORT_REJECTED: schedules file with MIME $mime by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
        echo json_encode(['success' => false, 'message' => "File content ($mime) does not look like a valid CSV/Excel document."]);
        exit;
    }

    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'File exceeds the 10MB limit.']);
        exit;
    }

    // Store outside the web root
    $temp_dest = tempnam(sys_get_temp_dir(), 'npc_sched_');
    move_uploaded_file($tmp_name, $temp_dest);

    $parsed_classes = runScheduleParser($temp_dest);
}
// ─── 2. Pasted text path ───────────────────────────────────────────────────────
elseif (isset($_POST['schedule_text']) && !empty(trim($_POST['schedule_text']))) {
    if (strlen($_POST['schedule_text']) > 500000) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Pasted text is too large.']);
        exit;
    }
    $temp_dest = tempnam(sys_get_temp_dir(), 'npc_stxt_');
    file_put_contents($temp_dest, $_POST['schedule_text']);

    $parsed_classes = runScheduleParser($temp_dest);
}

if (empty($parsed_classes)) {
    echo json_encode([
        'success' => false,
        'message' => 'No valid course schedule rows detected. Please check the format.'
    ]);
    exit;
}

// ─── 3. Sanitize every parsed row ──────────────────────────────────────────────
$cleanText = fn($v, $max) => substr(trim(strip_tags((string)$v)), 0, $max);

$records_to_insert = [];
$creatorEmail = ($_SESSION['email'] ?? '') !== '' ? strtolower($_SESSION['email']) : 'admin@navotaspolytechniccollege.edu.ph';

foreach ($parsed_classes as $c) {
    $code = strtoupper(preg_replace('/[^A-Za-z0-9\- ]/', '', (string)($c['code'] ?? '')));
    $title = $cleanText($c['title'] ?? '', 160);
    if ($code === '' || $title === '') continue;

    $records_to_insert[] = [
        'code' => $cleanText($code, 32),
        'title' => $title,
        'section' => $cleanText($c['section'] ?? $default_section, 48),
        'schedule_day' => $cleanText($c['schedule_day'] ?? 'TBA', 24),
        'start_time' => $cleanText($c['start_time'] ?? 'TBA', 16),
        'end_time' => $cleanText($c['end_time'] ?? 'TBA', 16),
        'instructor' => $cleanText($c['instructor'] ?? 'TBA', 120),
        'created_by_email' => $creatorEmail,
        'created_by_name' => $cleanText($_SESSION['name'] ?? 'Administrator', 120),
        'room' => $cleanText($c['room'] ?? 'Room TBA', 64),
        'units' => max(0, min(12, floatval($c['units'] ?? 3.0)))
    ];

    if (count($records_to_insert) >= MAX_IMPORT_ROWS) break;
}

if (empty($records_to_insert)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All parsed schedule rows were invalid.']);
    exit;
}

// Batch insert via Service Role Key
$result = supabaseServiceQuery(
    "/rest/v1/classes",
    'POST',
    $records_to_insert,
    ["Prefer: return=minimal"]
);

if ($result['status'] >= 200 && $result['status'] < 300) {
    logSecurityEvent("SCHEDULES_IMPORTED: " . count($records_to_insert) . " classes for $default_section by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
    echo json_encode([
        'success' => true,
        'count' => count($records_to_insert),
        'message' => "Successfully imported " . count($records_to_insert) . " schedule records to $default_section!"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Supabase error: ' . json_encode($result['data'])
    ]);
}
