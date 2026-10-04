<?php
$f = __DIR__ . '/../backend/elms_presence.json';
$data = json_decode(file_get_contents($f), true);
var_dump($data['NPC-AIS201-2026-09-18'] ?? 'NOT FOUND');
