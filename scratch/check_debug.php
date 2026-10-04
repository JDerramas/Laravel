<?php
$d = json_decode(file_get_contents(__DIR__ . '/../backend/elms_presence.json'), true);
echo "PRESENCE SESSIONS:\n";
print_r(array_keys($d));

if (isset($d['NPC-AIS201-2026-09-18'])) {
    echo "PRESENCE IN NPC-AIS201-2026-09-18:\n";
    print_r($d['NPC-AIS201-2026-09-18']);
}
