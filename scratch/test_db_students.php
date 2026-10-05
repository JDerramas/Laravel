<?php
require_once __DIR__ . '/../includes/db_helper.php';
$q = supabaseServiceQuery('/rest/v1/users?role=eq.student&select=full_name,student_number,program,section');
print_r($q);

$c = supabaseServiceQuery('/rest/v1/students?select=*&limit=10');
print_r($c);
