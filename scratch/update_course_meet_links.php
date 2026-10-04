<?php
$f = __DIR__ . '/../backend/elms_courses.json';
$d = json_decode(file_get_contents($f), true);
$links = [
    'crs-ais-201' => 'https://meet.google.com/vpm-zskq-yuj',
    'crs-is-204'  => 'https://meet.google.com/kmt-dfgh-xzb',
    'crs-hci-102' => 'https://meet.google.com/wty-qmnb-pqr',
    'crs-qm-101'  => 'https://meet.google.com/jxc-uvrw-mkn'
];

foreach ($d['courses'] as &$c) {
    $id = $c['id'];
    $c['meeting_link'] = $links[$id] ?? 'https://meet.google.com/vpm-zskq-yuj';
    if (isset($c['live_session'])) {
        $c['live_session']['meeting_link'] = $c['meeting_link'];
        $c['live_session']['is_active'] = false; // ensure clean state
    }
}
unset($c);

file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "SUCCESS_COURSES_UPDATED\n";
