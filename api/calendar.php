<?php
/**
 * api/calendar.php — Academic Calendar & Milestone Management API
 * 
 * Supports:
 *  - GET  ?action=get_events   : Public/student/faculty retrieval of calendar milestones
 *  - POST ?action=save_event   : Admin-only CRUD endpoint to create or edit academic calendar dates
 *  - POST ?action=delete_event : Admin-only endpoint to remove an academic milestone
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/supabase_helper.php';

header('Content-Type: application/json; charset=utf-8');

$calendarFile = __DIR__ . '/../backend/academic_calendar.json';

function loadCalendarData($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $raw = @file_get_contents($filePath);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveCalendarData($filePath, array $data) {
    // Sort chronologically by date keys
    ksort($data);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($filePath, $json, LOCK_EX) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Fallback to parse JSON body if needed
$input = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $parsed = json_decode($rawInput, true);
    if (is_array($parsed)) {
        $input = $parsed;
        if (empty($action) && isset($input['action'])) {
            $action = $input['action'];
        }
    }
}

// ─── 1. GET: Fetch Academic Calendar Events ──────────────────────────────────
if ($method === 'GET' || $action === 'get_events') {
    $events = loadCalendarData($calendarFile);
    echo json_encode([
        'success' => true,
        'events'  => $events,
        'total_dates' => count($events)
    ]);
    exit;
}

// ─── For modifying actions: enforce Admin Authorization & CSRF Check ───────────
$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'guest';
if (!in_array($userRole, ['admin', 'registrar'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Access denied: Only Administrators and Registrars can modify Academic Calendar dates.'
    ]);
    exit;
}

// Check CSRF
$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? $input['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'CSRF validation failed. Please refresh the page and try again.'
    ]);
    exit;
}

// ─── 2. POST: Save / Edit Academic Milestone ──────────────────────────────────
if ($action === 'save_event') {
    $date = trim($_POST['date'] ?? $input['date'] ?? '');
    $title = trim($_POST['title'] ?? $input['title'] ?? '');
    $type = trim($_POST['type'] ?? $input['type'] ?? 'academic');
    $desc = trim($_POST['desc'] ?? $input['desc'] ?? '');
    $eventId = trim($_POST['id'] ?? $input['id'] ?? '');

    // Validate date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date format. Expected YYYY-MM-DD.']);
        exit;
    }

    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Event title cannot be empty.']);
        exit;
    }

    $validTypes = ['academic', 'exam', 'holiday', 'deadline', 'event'];
    if (!in_array($type, $validTypes)) {
        $type = 'academic';
    }

    $events = loadCalendarData($calendarFile);

    if (empty($eventId)) {
        $eventId = 'ev-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    $newItem = [
        'id'    => $eventId,
        'title' => $title,
        'type'  => $type,
        'desc'  => $desc,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['email'] ?? 'admin'
    ];

    // Check if this date already has events; replace existing with same ID or update/add
    if (!isset($events[$date])) {
        $events[$date] = [$newItem];
    } else {
        $found = false;
        foreach ($events[$date] as $idx => $ev) {
            if (isset($ev['id']) && $ev['id'] === $eventId) {
                $events[$date][$idx] = $newItem;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $events[$date][] = $newItem;
        }
    }

    if (!saveCalendarData($calendarFile, $events)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write calendar file to disk.']);
        exit;
    }

    // Try logging to database security_logs if table exists
    try {
        supabaseServiceQuery("/rest/v1/security_logs", "POST", [
            'user_id' => $_SESSION['user_id'] ?? 'admin',
            'action' => 'ACADEMIC_CALENDAR_UPDATE',
            'details' => "Updated milestone on {$date}: {$title} ({$type})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at' => date('c')
        ]);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'message' => "Successfully saved academic milestone for {$date}.",
        'event'   => $newItem,
        'events'  => $events
    ]);
    exit;
}

// ─── 3. POST: Delete Academic Milestone ───────────────────────────────────────
if ($action === 'delete_event') {
    $date = trim($_POST['date'] ?? $input['date'] ?? '');
    $eventId = trim($_POST['id'] ?? $input['id'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date format.']);
        exit;
    }

    $events = loadCalendarData($calendarFile);

    if (isset($events[$date])) {
        if (!empty($eventId)) {
            $events[$date] = array_values(array_filter($events[$date], function ($ev) use ($eventId) {
                return !isset($ev['id']) || $ev['id'] !== $eventId;
            }));
            if (empty($events[$date])) {
                unset($events[$date]);
            }
        } else {
            // Delete entire date entry
            unset($events[$date]);
        }

        saveCalendarData($calendarFile, $events);

        try {
            supabaseServiceQuery("/rest/v1/security_logs", "POST", [
                'user_id' => $_SESSION['user_id'] ?? 'admin',
                'action' => 'ACADEMIC_CALENDAR_DELETE',
                'details' => "Deleted milestone on {$date}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'created_at' => date('c')
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'message' => "Milestone on {$date} has been deleted.",
        'events'  => $events
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid or unknown action requested.']);
