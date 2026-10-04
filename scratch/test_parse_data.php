<?php
$jsPath = __DIR__ . '/../assets/js/npc-floorplans-data.js';
$jsContent = file_get_contents($jsPath);

$pos = strpos($jsContent, 'window.NPC_FLOORS_DATA = ');
if ($pos !== false) {
    $jsonSub = substr($jsContent, $pos + strlen('window.NPC_FLOORS_DATA = '));
    $endPos = strrpos($jsonSub, '};');
    if ($endPos !== false) {
        $jsonStr = substr($jsonSub, 0, $endPos + 1);
        $floorsData = json_decode($jsonStr, true);
        echo "Successfully parsed NPC_FLOORS_DATA! Floors: " . count($floorsData) . "\n";
        $totalRooms = 0;
        foreach ($floorsData as $fk => $fd) {
            $rCount = count($fd['rooms'] ?? []);
            $totalRooms += $rCount;
            echo "  Floor $fk: $rCount rooms\n";
        }
        echo "Total rooms: $totalRooms\n";
    } else {
        echo "End delimiter }; not found\n";
    }
} else {
    echo "window.NPC_FLOORS_DATA not found\n";
}
