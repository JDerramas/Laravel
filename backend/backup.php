<?php
/**
 * backup.php — NPC Connect backup tool (CLI or browser w/ admin session)
 *
 * Creates timestamped backups:
 *   backups/backup_YYYY-MM-DD_HHMMSS/
 *     ├── database/  (one JSON per table, from Supabase REST)
 *     ├── documents/ (copy of ../documents uploads)
 *     └── MANIFEST.json
 *
 * Usage:
 *   CLI:      php backup.php            (keeps last N=14 by default)
 *   Browser:  backup.php?token=...      (requires BACKUP_TOKEN in .env)
 *
 * Restore note: JSON files are restorable via PostgREST POST per table,
 * or import into Supabase Table Editor. Manifest lists row counts.
 */

require_once __DIR__ . '/db_helper.php';

$isCli = PHP_SAPI === 'cli';

// ─── Access control ─────────────────────────────────────────────────────────
if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/auth.php';
    }
    $env = loadEnv();
    $token = $_GET['token'] ?? '';
    $expected = $env['BACKUP_TOKEN'] ?? '';
    // Token path only (admin session alone is NOT enough — backups leave the building)
    if (empty($expected) || empty($token) || !hash_equals($expected, $token)) {
        http_response_code(403);
        exit('Forbidden. Set BACKUP_TOKEN in .env and pass ?token=...');
    }
}

// ─── Config ──────────────────────────────────────────────────────────────────
$env = loadEnv();
$base = getenv('SUPABASE_URL') ?: ($env['SUPABASE_URL'] ?? '');
$key  = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($env['SUPABASE_SERVICE_ROLE_KEY'] ?? '');
if (!$base || !$key) { fwrite(STDERR, "Missing SUPABASE_URL / SERVICE_ROLE_KEY in .env\n"); exit(1); }

$retention = 14;
$stamp = date('Y-m-d_His');
$root  = __DIR__ . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup_' . $stamp;
$dbDir = $root . '/database';
$docSrc = dirname(__DIR__) . '/documents';
$docDst = $root . '/documents';
foreach ([$dbDir, $docDst] as $d) { if (!is_dir($d)) mkdir($d, 0755, true); }

function fetchAll(string $url, string $key, string $table): array {
    $all = []; $range = 1000; $offset = 0;
    while (true) {
        $ch = curl_init("$url/rest/v1/$table?select=*");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "apikey: $key",
                "Authorization: Bearer $key",
                "Range: $offset-" . ($offset + $range - 1),
                'Prefer: count=exact'
            ],
            CURLOPT_TIMEOUT => 60
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code !== 200) return [$table => []]; // skip on error
        $rows = json_decode($body, true) ?: [];
        $all = array_merge($all, $rows);
        if (count($rows) < $range) break;
        $offset += $range;
    }
    return $all;
}

$tables = [
    'users', 'classes', 'enrollments', 'user_class_enrollments',
    'attendance_records', 'attendance_sessions', 'grades', 'grade_submissions', 'grade_change_requests',
    'document_requests', 'consultation_appointments', 'faculty_materials',
    'announcements', 'class_announcements', 'notifications', 'profile_update_requests',
    'school_years', 'semesters', 'programs', 'sections', 'subjects', 'app_settings',
    'security_logs'
];

// ─── Dump DB ────────────────────────────────────────────────────────────────
$manifest = ['created_at' => date('c'), 'tables' => [], 'files_backed_up' => 0];
$totalRows = 0;
foreach ($tables as $t) {
    $rows = fetchAll($base, $key, $t);
    file_put_contents("$dbDir/$t.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $manifest['tables'][$t] = count($rows);
    $totalRows += count($rows);
    echo (($isCli ? '' : '<br>')) . "  [db] $t: " . count($rows) . " rows\n";
}

// ─── Copy documents ─────────────────────────────────────────────────────────
if (is_dir($docSrc)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docSrc, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (!$f->isFile()) continue;
        $rel = substr($f->getPathname(), strlen($docSrc) + 1);
        $dst = $docDst . '/' . $rel;
        @mkdir(dirname($dst), 0755, true);
        copy($f->getPathname(), $dst);
        $manifest['files_backed_up']++;
    }
}
file_put_contents($root . '/MANIFEST.json', json_encode($manifest, JSON_PRETTY_PRINT));

// ─── Zip it ─────────────────────────────────────────────────────────────────
$zipPath = $root . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    foreach (['database', 'documents'] as $sub) {
        $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$sub", FilesystemIterator::SKIP_DOTS));
        foreach ($dir as $f) {
            if (!$f->isFile()) continue;
            $zip->addFile($f->getPathname(), "$sub/" . substr($f->getPathname(), strlen("$root/") ));
        }
    }
    $zip->addFile($root . '/MANIFEST.json', 'MANIFEST.json');
    $zip->close();
}
// Remove the loose dir after zipping to save space
recurseRm($root);

// ─── Retention: keep newest N ───────────────────────────────────────────────
$baks = glob(__DIR__ . '/backups/backup_*.zip');
sort($baks);
while (count($baks) > $retention) {
    $old = array_shift($baks);
    @unlink($old);
}

$size = filesize($zipPath);
$msg = "Backup complete → $zipPath (" . round($size / 1024 / 1024, 2) . " MB, $totalRows rows, {$manifest['files_backed_up']} files)";
echo (($isCli ? '' : '<p>')) . $msg . (($isCli ? "\n" : '</p>'));

function recurseRm(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}
