<?php
/**
 * admin/campus_map.php — Admin Campus Architectural CAD Blueprint
 * Navotas Polytechnic College LMS
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$NPC_PORTAL = 'admin';
$PAGE_TITLE = 'NPC Map · LMS Admin Center';
include __DIR__ . '/../campus_map.php';
