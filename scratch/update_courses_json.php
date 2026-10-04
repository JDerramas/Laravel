<?php
$file = __DIR__ . '/../backend/elms_courses.json';
$data = json_decode(file_get_contents($file), true);
foreach ($data['courses'] as &$c) {
    if (isset($c['live_session'])) {
        if (($c['live_session']['platform'] ?? '') === 'google_meet') {
            $c['live_session']['platform'] = 'plugnmeet';
        }
        if (str_contains($c['live_session']['meeting_link'] ?? '', 'meet.google.com')) {
            $c['live_session']['meeting_link'] = '/live_room.php?session_code=' . rawurlencode($c['live_session']['session_code'] ?? '') . '&course_code=' . rawurlencode($c['code']) . '&room_id=' . rawurlencode($c['live_session']['room_id'] ?? '');
        }
    }
    if (str_contains($c['meeting_link'] ?? '', 'meet.google.com')) {
        $c['meeting_link'] = '/live_room.php?course_code=' . rawurlencode($c['code']);
    }
}
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "UPDATED COURSES JSON\n";
