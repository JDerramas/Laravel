<?php
$presenceFile = __DIR__ . '/../backend/elms_presence.json';
$presence = json_decode(file_get_contents($presenceFile), true);
echo json_encode($presence, JSON_PRETTY_PRINT);
