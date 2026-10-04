<?php
/**
 * api/realtime.php — Ultra-Fast Autonomous Real-Time Sync Engine
 * Provides instant live deltas for attendance records, notifications, and live classes.
 * Completely replaces Supabase Realtime WebSockets with lightweight local polling (<5ms).
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? 'attendance_records');
$since = $_GET['since'] ?? '';
$sessionCode = $_GET['session_code'] ?? '';
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

try {
    $db = getDB();

    if ($table === 'attendance_records') {
        $where = [];
        $params = [];

        if (!empty($sessionCode)) {
            $where[] = "session_code = ?";
            $params[] = $sessionCode;
        }

        if (!empty($since)) {
            $where[] = "scanned_at > ?";
            $params[] = $since;
        }

        $sql = "SELECT * FROM attendance_records";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY scanned_at DESC LIMIT $limit";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'ok',
            'table' => $table,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $records,
            'count' => count($records)
        ]);
        exit;
    }

    if ($table === 'live_session') {
        $courseId = $_GET['course_id'] ?? '';
        $courseJsonPath = __DIR__ . '/../backend/elms_courses.json';
        $live = null;
        if (file_exists($courseJsonPath)) {
            $data = json_decode(file_get_contents($courseJsonPath), true);
            foreach (($data['courses'] ?? []) as $c) {
                if ($c['id'] === $courseId || $c['code'] === $courseId) {
                    $live = $c['live_session'] ?? null;
                    break;
                }
            }
        }
        echo json_encode([
            'status' => 'ok',
            'table' => 'live_session',
            'live_session' => $live,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Generic table fallback
    $stmt = $db->prepare("SELECT * FROM `$table` ORDER BY id DESC LIMIT $limit");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'table' => $table,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ]);
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
