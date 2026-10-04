<?php
/**
 * api/notifications.php — Unified Communication & Notification Feed
 * 
 * Provides:
 * - Scoped campus announcements (Urgent / Pinned first, audience-filtered)
 * - Personal notification items (grades, attendance, document releases)
 * - Read/unread state synchronization
 * - User notification preferences
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = getCurrentUser();
$currentUserEmail = strtolower($user['email'] ?? '');
$userRole = strtolower($user['role'] ?? 'student');
session_write_close();

// ─── POST ACTIONS ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $postAction = $input['action'] ?? $action;

    // 1. Mark all personal notifications as read
    if ($postAction === 'mark_all_read') {
        requireCsrf();
        supabaseServiceQuery(
            "/rest/v1/notifications?user_email=eq." . rawurlencode($currentUserEmail),
            'PATCH',
            ['is_read' => true]
        );
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read.']);
        exit;
    }

    // 2. Mark single notification as read
    if ($postAction === 'mark_read') {
        requireCsrf();
        $notifId = trim($input['id'] ?? '');
        if ($notifId) {
            supabaseServiceQuery(
                "/rest/v1/notifications?id=eq." . rawurlencode($notifId) . "&user_email=eq." . rawurlencode($currentUserEmail),
                'PATCH',
                ['is_read' => true]
            );
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // 3. Save notification preferences
    if ($postAction === 'update_preferences') {
        requireCsrf();
        $prefs = [
            'user_email'            => $currentUserEmail,
            'in_app_announcements'  => !empty($input['in_app_announcements']),
            'in_app_live_classes'   => !empty($input['in_app_live_classes']),
            'in_app_grades'         => !empty($input['in_app_grades']),
            'in_app_attendance'     => !empty($input['in_app_attendance']),
            'email_notifications'   => !empty($input['email_notifications']),
            'updated_at'            => date('c')
        ];

        supabaseServiceQuery(
            "/rest/v1/user_notification_preferences",
            'POST',
            [$prefs],
            ["Prefer: resolution=merge-duplicates"]
        );

        echo json_encode(['success' => true, 'preferences' => $prefs, 'message' => 'Notification preferences saved.']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ─── GET: Fetch User Notification Preferences ─────────────────────────────────
if ($action === 'get_preferences') {
    $pQuery = supabaseServiceQuery(
        "/rest/v1/user_notification_preferences?user_email=eq." . rawurlencode($currentUserEmail) . "&limit=1"
    );
    $prefs = ($pQuery['status'] === 200 && !empty($pQuery['data'])) ? $pQuery['data'][0] : [
        'in_app_announcements' => true,
        'in_app_live_classes'  => true,
        'in_app_grades'        => true,
        'in_app_attendance'    => true,
        'email_notifications'  => false
    ];

    echo json_encode(['success' => true, 'preferences' => $prefs]);
    exit;
}

// ─── GET: Main Feed (Announcements + Personal Notifications) ──────────────────
// 1. Fetch announcements
$annQuery = supabaseServiceQuery(
    "/rest/v1/announcements?status=eq.published&order=created_at.desc&limit=30"
);

$announcements = ($annQuery['status'] === 200 && is_array($annQuery['data'])) ? $annQuery['data'] : [];

// Resolve student section / program for audience matching if student
$studentSection = $_SESSION['section'] ?? '';
$studentProgram = $_SESSION['program'] ?? '';

$nowTs = time();
$filteredAnnouncements = [];

foreach ($announcements as $a) {
    // Schedule check: hide future scheduled announcements
    if (!empty($a['scheduled_at']) && strtotime($a['scheduled_at']) > $nowTs) {
        continue;
    }
    // Expiration check: hide expired announcements
    if (!empty($a['expires_at']) && strtotime($a['expires_at']) < $nowTs) {
        continue;
    }

    // Audience filtering
    $aud = strtolower(trim($a['target_audience'] ?? 'all'));
    if ($aud === 'students' && $userRole !== 'student' && $userRole !== 'admin') {
        continue;
    }
    if ($aud === 'faculty' && $userRole !== 'teacher' && $userRole !== 'faculty' && $userRole !== 'admin') {
        continue;
    }
    if ($aud === 'program' && !empty($a['target_program']) && $userRole === 'student') {
        if (strcasecmp($a['target_program'], $studentProgram) !== 0) {
            continue;
        }
    }
    if ($aud === 'section' && !empty($a['target_section']) && $userRole === 'student') {
        if (strpos(strtoupper($studentSection), strtoupper($a['target_section'])) === false) {
            continue;
        }
    }

    $body = trim(strip_tags((string)($a['body'] ?? '')));
    $body = preg_replace('/\*\*/', '', $body);

    $filteredAnnouncements[] = [
        'id'              => (string)($a['id'] ?? ''),
        'title'           => (string)($a['title'] ?? 'Announcement'),
        'excerpt'         => mb_substr($body, 0, 140),
        'category'        => (string)($a['category'] ?? 'news'),
        'priority'        => (string)($a['priority'] ?? 'Normal'),
        'is_pinned'       => !empty($a['is_pinned']),
        'target_audience' => (string)($a['target_audience'] ?? 'all'),
        'created_at'      => (string)($a['created_at'] ?? ''),
        'kind'            => 'announcement',
        'link'            => ''
    ];
}

// Sort announcements: Pinned first, then Urgent priority, then created_at desc
usort($filteredAnnouncements, function ($x, $y) {
    if ($x['is_pinned'] !== $y['is_pinned']) {
        return $y['is_pinned'] ? 1 : -1;
    }
    $prioMap = ['Urgent' => 3, 'Important' => 2, 'Normal' => 1];
    $pX = $prioMap[$x['priority'] ?? 'Normal'] ?? 1;
    $pY = $prioMap[$y['priority'] ?? 'Normal'] ?? 1;
    if ($pX !== $pY) {
        return $pY - $pX;
    }
    return strtotime($y['created_at'] ?? '0') - strtotime($x['created_at'] ?? '0');
});

// 2. Fetch personal notifications for the current user
$notifQuery = supabaseServiceQuery(
    "/rest/v1/notifications?user_email=eq." . rawurlencode($currentUserEmail) . "&order=created_at.desc&limit=25"
);
$personalRaw = ($notifQuery['status'] === 200 && is_array($notifQuery['data'])) ? $notifQuery['data'] : [];

$personalItems = [];
$unreadCount = 0;

foreach ($personalRaw as $n) {
    $isRead = !empty($n['is_read']);
    if (!$isRead) $unreadCount++;

    $personalItems[] = [
        'id'         => 'ntf-' . ($n['id'] ?? ''),
        'title'      => (string)($n['title'] ?? 'Notification'),
        'excerpt'    => (string)($n['message'] ?? ''),
        'category'   => (string)($n['type'] ?? 'info'),
        'priority'   => 'Normal',
        'is_pinned'  => false,
        'is_read'    => $isRead,
        'link'       => (string)($n['link_url'] ?? ''),
        'created_at' => (string)($n['created_at'] ?? ''),
        'kind'       => 'personal'
    ];
}

// 3. Merged feed for the bell popover (npc.js compatibility)
$allFeed = array_merge($filteredAnnouncements, $personalItems);
usort($allFeed, function ($a, $b) {
    if (!empty($a['is_pinned']) && empty($b['is_pinned'])) return -1;
    if (empty($a['is_pinned']) && !empty($b['is_pinned'])) return 1;
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

echo json_encode([
    'success'       => true,
    'notifications' => $allFeed,
    'announcements' => $filteredAnnouncements,
    'personal'      => $personalItems,
    'unread_count'  => $unreadCount
]);
