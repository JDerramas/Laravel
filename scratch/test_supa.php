<?php
require_once __DIR__ . '/../includes/supabase_helper.php';
$t0 = microtime(true);
$res = supabaseServiceQuery('/rest/v1/users?limit=1');
$elapsed = microtime(true) - $t0;
echo "Time: " . round($elapsed, 3) . "s\n";
echo "Status: " . $res['status'] . "\n";
echo "Error: " . ($res['error'] ?? 'none') . "\n";
