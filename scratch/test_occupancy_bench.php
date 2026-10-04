<?php
$t0 = microtime(true);
$res1 = @file_get_contents('http://127.0.0.1:8000/api/campus_map.php?action=get_occupancy');
$t1 = microtime(true);
echo "127.0.0.1 time: " . round(($t1 - $t0) * 1000, 2) . " ms\n";

$t2 = microtime(true);
$res2 = @file_get_contents('http://localhost:8000/api/campus_map.php?action=get_occupancy');
$t3 = microtime(true);
echo "localhost time: " . round(($t3 - $t2) * 1000, 2) . " ms\n";
