<?php
/**
 * import_teachers.php — Bulk faculty importer (admin only).
 *
 * Accepts either an uploaded CSV/Excel or pasted text. Each line may be:
 *   - a plain official email  →  name derived from email prefix
 *   - "Full Name, email"      →  explicit name
 *   - "email, Full Name"      →  also accepted (auto-detected)
 *
 * Security: admin session + CSRF, MIME validation, temp files outside
 * web root, domain whitelist, row cap, audit-logged.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_helper.php';

require_admin();
requireCsrf();

header('Content-Type: application/json');

const OFFICIAL_EMAIL_DOMAIN = '@navotaspolytechniccollege.edu.ph';
const MAX_IMPORT_ROWS = 300;

$rawLines = [];

// ─── 1. File upload path ───────────────────────────────────────────────────────
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt', 'xls', 'xlsx'], true)) {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: csv, txt']);
        exit;
    }

    // Content sniff — reject anything that is not text-like or spreadsheet-ish
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    $okMimes = ['text/csv', 'text/plain', 'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'];
    if (!in_array($mime, $okMimes, true)) {
        http_response_code(415);
        logSecurityEvent("IMPORT_REJECTED: teachers file MIME $mime by {$_SESSION['email']}", $_SESSION['email'], 'Medium');
        echo json_encode(['success' => false, 'message' => "File content ($mime) does not look like CSV/Excel."]);
        exit;
    }

    if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'File exceeds the 5MB limit.']);
        exit;
    }

    // For csv/txt parse directly; for xls/xlsx try the python parser used by students
    if (in_array($ext, ['csv', 'txt'], true)) {
        $rawLines = file($_FILES['file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    } else {
        $tempDest = tempnam(sys_get_temp_dir(), 'npc_tch_');
        move_uploaded_file($tmp, $tempDest);
        $parserPath = __DIR__ . '/parse_students_file.py';
        if (is_file($parserPath)) {
            $output = shell_exec('python ' . escapeshellarg($parserPath) . ' ' . escapeshellarg($tempDest));
            @unlink($tempDest);
            $jsonRes = json_decode((string)$output, true);
            foreach ((is_array($jsonRes) ? ($jsonRes['students'] ?? []) : []) as $row) {
                $rawLines[] = trim(($row['full_name'] ?? '') . ', ' . ($row['email'] ?? ''), ', ');
            }
        } else {
            @unlink($tempDest);
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Excel support requires the parser helper; please use a CSV file.']);
            exit;
        }
    }
}
// ─── 2. Pasted text path ───────────────────────────────────────────────────────
elseif (isset($_POST['emails_text']) && trim($_POST['emails_text']) !== '') {
    if (strlen($_POST['emails_text']) > 200000) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Pasted text too large.']);
        exit;
    }
    $rawLines = preg_split('/[\r\n;]+/', $_POST['emails_text']) ?: [];
}

$rawLines = array_values(array_filter(array_map('trim', $rawLines), fn($l) => $l !== ''));

if (empty($rawLines)) {
    echo json_encode(['success' => false, 'message' => 'No teacher entries found. Paste official emails or upload a CSV.']);
    exit;
}

// ─── 3. Parse + validate each line ─────────────────────────────────────────────
$records = [];
$rejected = 0;

foreach (array_slice($rawLines, 0, MAX_IMPORT_ROWS) as $line) {
    // Split into possible name/email parts
    $parts = array_map('trim', explode(',', $line));
    $email = '';
    $name = '';

    foreach ($parts as $p) {
        if (filter_var(strtolower($p), FILTER_VALIDATE_EMAIL) && stripos($p, OFFICIAL_EMAIL_DOMAIN) !== false) {
            $email = strtolower($p);
        } elseif ($p !== '' && $name === '') {
            $name = strip_tags($p);
        }
    }

    // Bare email line → derive a display name from the prefix
    if ($email === '' && count($parts) === 1 && filter_var(strtolower(trim($parts[0])), FILTER_VALIDATE_EMAIL)) {
        $email = strtolower(trim($parts[0]));
        $name = '';
    }
    if ($email === '' && preg_match('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', $line, $m)) {
        $candidate = strtolower($m[0]);
        if (str_ends_with($candidate, OFFICIAL_EMAIL_DOMAIN)) $email = $candidate;
    }

    if (!$email || !str_ends_with($email, OFFICIAL_EMAIL_DOMAIN)) { $rejected++; continue; }

    if ($name === '') {
        $prefix = explode('@', $email)[0];
        $name = ucwords(preg_replace('/[\d._\-]+/', ' ', $prefix));
    }
    $name = trim(mb_substr($name, 0, 120));
    if ($name === '') { $rejected++; continue; }

    $records[] = [
        'email' => $email,
        'full_name' => $name,
        'role' => 'teacher',
        'program' => 'Faculty',
        'password_hash' => 'oauth'
    ];
}

if (empty($records)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No valid teacher emails found. All addresses must belong to the ' . OFFICIAL_EMAIL_DOMAIN . ' domain.']);
    exit;
}

// ─── 4. Upsert via service key ─────────────────────────────────────────────────
$result = supabaseServiceQuery("/rest/v1/users", 'POST', $records, ["Prefer: resolution=merge-duplicates"]);

if ($result['status'] >= 200 && $result['status'] < 300) {
    logSecurityEvent("TEACHERS_IMPORTED: " . count($records) . " faculty accounts by {$_SESSION['email']}", $_SESSION['email'], 'High');
    echo json_encode([
        'success' => true,
        'imported' => count($records),
        'rejected_invalid' => $rejected,
        'teachers' => array_column($records, 'email'),
        'message' => count($records) . ' teacher accounts imported!' . ($rejected > 0 ? " ($rejected skipped.)" : '')
    ]);
} else {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Database rejected the import.', 'detail' => $result['data']]);
}
