<?php
$presenceFile = __DIR__ . '/../backend/elms_presence.json';
$presence = json_decode(file_get_contents($presenceFile), true);
$p = $presence['NPC-AIS201-2026-09-18']['2024-00192'] ?? null;
var_dump($p);
