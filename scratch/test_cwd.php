<?php
header('Content-Type: application/json');
echo json_encode([
    'cwd' => getcwd(),
    'dir' => __DIR__,
    'presence_file' => realpath(__DIR__ . '/../backend/elms_presence.json')
]);
