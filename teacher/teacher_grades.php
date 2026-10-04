<?php
/**
 * teacher/teacher_grades.php — Route compatibility wrapper for /teacher/grades.php
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '';
header("Location: /teacher/grades.php" . $qs, true, 301);
exit;
