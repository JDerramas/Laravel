<?php
/**
 * student/campus_map.php — Student Campus Architectural CAD Blueprint
 * Navotas Polytechnic College LMS
 */
require_once __DIR__ . '/../includes/auth.php';
require_student_area();

$NPC_PORTAL = 'student';
$PAGE_TITLE = 'NPC Map · Student Portal';
include __DIR__ . '/../campus_map.php';
