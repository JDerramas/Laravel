<?php
/**
 * teacher_grades.php — Root route compatibility wrapper for /teacher/grades.php
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '';
header("Location: /teacher/grades.php" . $qs, true, 301);
exit;
