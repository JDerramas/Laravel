<?php
// scratch/test_build_rooms.php

function getFullRoomsFromDataset(): array {
    $jsPath = __DIR__ . '/../assets/js/npc-floorplans-data.js';
    $jsContent = file_get_contents($jsPath);
    $pos = strpos($jsContent, 'window.NPC_FLOORS_DATA = ');
    if ($pos === false) return [];
    
    $jsonSub = substr($jsContent, $pos + strlen('window.NPC_FLOORS_DATA = '));
    $endPos = strrpos($jsonSub, '};');
    if ($endPos === false) return [];
    
    $floorsData = json_decode(substr($jsonSub, 0, $endPos + 1), true);
    if (!is_array($floorsData)) return [];

    $results = [];
    foreach ($floorsData as $floorKey => $floor) {
        $rooms = $floor['rooms'] ?? [];
        foreach ($rooms as $r) {
            $type = $r['type'] ?? 'classroom';
            
            // Map type colors
            $color = match($type) {
                'sports' => '#f59e0b',
                'lab' => '#06b6d4',
                'office', 'faculty_office' => '#8b5cf6',
                'amenity', 'canteen', 'garden' => '#10b981',
                'restroom' => '#38bdf8',
                'stairs' => '#f43f5e',
                'utility' => '#64748b',
                'void' => '#334155',
                default => '#3b82f6'
            };

            // Calculate realistic capacity
            $area = (float)($r['area_sqm'] ?? 45.0);
            $capacity = match($type) {
                'sports' => 750,
                'amenity' => ($area > 200 ? 150 : 60),
                'canteen' => 120,
                'lab' => 45,
                'classroom' => 50,
                'office' => 15,
                'restroom' => 10,
                'stairs', 'utility', 'void' => 5,
                default => 40
            };

            // Amenities list based on type
            $amenities = match($type) {
                'sports' => ['FIBA Regulation Hardwood Court', 'LED Scoreboard', 'Bleacher Seating', 'High-Bay Stadium Lights', 'PA Sound System'],
                'lab' => ['45 High-Spec Workstations', 'Dual Fiber Wi-Fi 6', 'Ceiling Cassette ACU 6HP', 'Interactive Smart Projector', 'UPS Backup'],
                'office' => ['Administrative Workstations', 'Executive Consultation Table', 'High-Speed Wi-Fi', 'Filing Storage', 'Inverter Split-Type ACU'],
                'amenity' => ['Open Student Lounge', 'Reading Tables & Benches', 'Campus Wi-Fi Zone', 'Natural Daylighting'],
                'canteen' => ['Food Service Stalls', 'Handwash Sinks', 'Spacious Dining Tables', 'Trash Segregation Station'],
                'restroom' => ['Individual Stalls with Bidet', 'Ceramic Sinks', 'Ventilation Fan', 'Hand Dryer', 'PWD Grab Rails'],
                'stairs' => ['Non-Slip Granite Treads', 'Continuous Steel Handrails', 'Emergency Illumination', 'Exit Signage'],
                default => ['Ceiling Cassette ACU 6HP', 'Magnetic Whiteboard', 'Fiber Wi-Fi', 'Audio-Visual Ready', 'Ergonomic Armchairs']
            };

            $wing = match(true) {
                ($r['x'] < 18.0) => 'West Academic Wing',
                ($r['x'] > 38.0) => 'East Faculty Wing',
                default => 'Central Core'
            };

            $results[] = [
                'id' => $r['id'],
                'code' => $r['code'] ?? $r['id'],
                'name' => $r['name'],
                'floor' => $floorKey,
                'type' => $type,
                'wing' => $wing,
                'area_sqm' => $area,
                'capacity' => $capacity,
                'x' => (float)($r['x'] ?? 0),
                'z' => (float)($r['y'] ?? 0),
                'w' => (float)($r['w'] ?? 8),
                'l' => (float)($r['d'] ?? 8),
                'color' => $color,
                'amenities' => $amenities
            ];
        }
    }
    return $results;
}

$rooms = getFullRoomsFromDataset();
echo "Generated total rooms: " . count($rooms) . "\n";
echo "Sample room: " . json_encode($rooms[0], JSON_PRETTY_PRINT) . "\n";
