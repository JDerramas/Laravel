<?php
/**
 * teacher/campus_map.php — Faculty Campus Architectural CAD Blueprint
 * Navotas Polytechnic College LMS
 */
require_once __DIR__ . '/../includes/auth.php';
require_teacher();

$NPC_PORTAL = 'faculty';
$PAGE_TITLE = 'NPC Map · Faculty Portal';
include __DIR__ . '/../campus_map.php';
