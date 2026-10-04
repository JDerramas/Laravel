<?php
$res = file_get_contents('http://127.0.0.1:8000/api/campus_map.php?action=get_all');
$data = json_decode($res, true);
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "Total rooms: " . count($data['rooms'] ?? []) . "\n";

$occupied = [];
foreach ($data['rooms'] as $r) {
    if (($r['status'] ?? '') === 'occupied') {
        $occupied[] = ($r['code'] ?? $r['id']) . " (" . ($r['status_label'] ?? '') . ")";
    }
}
echo "Occupied rooms count: " . count($occupied) . "\n";
if (!empty($occupied)) {
    echo "Occupied list:\n" . implode("\n", $occupied) . "\n";
} else {
    echo "ALL DEMO / SIMULATED ROOMS HAVE BEEN REMOVED! Rooms are available!\n";
}
