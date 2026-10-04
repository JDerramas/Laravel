<?php
require_once __DIR__ . '/../includes/supabase_helper.php';
$r = supabaseServiceQuery("/rest/v1/users?select=id,full_name,student_number,email,role,section,program");
echo json_encode($r['data'], JSON_PRETTY_PRINT);
