<?php
$t0 = microtime(true);
ob_start();
include __DIR__ . '/../api/campus_map.php';
$out = ob_get_clean();
$t1 = microtime(true);
echo "Direct include time: " . round(($t1 - $t0) * 1000, 2) . " ms, size: " . strlen($out) . " bytes\n";
