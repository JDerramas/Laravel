<?php
$t0 = microtime(true);
$res = @file_get_contents('http://127.0.0.1:8000/api/campus_map.php?action=get_all');
$t1 = microtime(true);
echo "API response size: " . strlen($res) . " bytes\n";
echo "API time: " . round(($t1 - $t0) * 1000, 2) . " ms\n";
