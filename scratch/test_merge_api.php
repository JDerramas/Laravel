<?php
// scratch/test_merge_api.php

function getRoomDefinitions(): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $jsPath = __DIR__ . '/../assets/js/npc-floorplans-data.js';
    $jsContent = file_exists($jsPath) ? file_get_contents($jsPath) : '';
    
    $floorsData = [];
    $pos = strpos($jsContent, 'window.NPC_FLOORS_DATA = ');
    if ($pos !== false) {
        $jsonSub = substr($jsContent, $pos + strlen('window.NPC_FLOORS_DATA = '));
        $endPos = strrpos($jsonSub, '};');
        if ($endPos !== false) {
            $floorsData = json_decode(substr($jsonSub, 0, $endPos + 1), true) ?: [];
        }
    }

    // Curated presets for key rooms
    $presets = [
        '2F-11' => [
            'code' => 'COMPLAB-2',
            'name' => 'Computer Laboratory 2',
            'capacity' => 45,
            'amenities' => ['45 High-Spec PC Workstations', 'Dual Gigabit Fiber Uplinks', 'Ceiling Cassette ACU 6HP', 'Interactive Smart Projector', 'UPS Power Station']
        ],
        '2F-03' => [
            'code' => 'RM-201',
            'name' => 'Classroom 1 (Room 201)',
            'capacity' => 45,
            'amenities' => ['45 Ergonomic Tablet Armchairs', 'Magnetic Glass Whiteboard', 'Ceiling Cassette ACU 6HP', 'High-Speed Campus Wi-Fi 6', 'Audio-Visual Ready']
        ],
        '2F-04' => [
            'code' => 'RM-202',
            'name' => 'Classroom 2 (Room 202)',
            'capacity' => 45,
            'amenities' => ['45 Ergonomic Tablet Armchairs', 'Magnetic Glass Whiteboard', 'Ceiling Cassette ACU 6HP', 'High-Speed Campus Wi-Fi 6', 'Audio-Visual Ready']
        ],
        '2F-09' => [
            'code' => 'COMPLAB-1',
            'name' => 'Computer Laboratory 1',
            'capacity' => 50,
            'amenities' => ['50 PC Terminals', 'Networking Rack', 'Ceiling Cassette ACU 6HP', 'Interactive Smart Projector', 'Dual Wi-Fi 6']
        ],
        '2F-13' => [
            'code' => 'CANTEEN-201',
            'name' => 'NPC Campus Canteen & Cafeteria',
            'capacity' => 120,
            'amenities' => ['Food Service Stalls', 'Sanitary Wash Area', 'Spacious Dining Seating', 'Overlooking Central Garden']
        ],
        '2F-01' => [
            'code' => 'CASHIER-201',
            'name' => "Cashier's Office",
            'capacity' => 25,
            'amenities' => ['4 Service Windows', 'Queue Management System', 'Air-Conditioned Waiting Lounge', 'Direct CCTV Monitoring']
        ],
        '2F-02' => [
            'code' => 'REGISTRAR-202',
            'name' => "Registrar's Office & Student Records",
            'capacity' => 40,
            'amenities' => ['Document Verification Windows', 'Archival Record Vault', 'Fast-Track Inquiries Counter', 'Air-Conditioned Waiting Hall']
        ],
        '2F-28' => [
            'code' => 'AVR-201',
            'name' => 'Audio-Visual Room (AVR)',
            'capacity' => 65,
            'amenities' => ['Tiered Seating', 'HD Laser Projector', 'Surround Sound System', 'Acoustic Wall Panels', 'Dual 6HP ACU']
        ],
        '3F-01' => [
            'code' => 'LIB-301',
            'name' => 'NPC Central Library',
            'capacity' => 180,
            'amenities' => ['Over 10,000 Volume Reference Collection', 'OPAC Terminals', 'Quiet Reading Carrels', 'E-Library High-Speed Wi-Fi', 'Full ACU Climate Control']
        ],
        '3F-10' => [
            'code' => 'SCILAB-1',
            'name' => 'Science Laboratory 1 (Chemistry)',
            'capacity' => 40,
            'amenities' => ['Chemical Fume Hoods', 'Acid-Resistant Ceramic Benches', 'Eye Wash Safety Station', 'Gas & Vacuum Taps', 'Centrifuges & Glassware']
        ],
        '3F-12' => [
            'code' => 'STUDY-301',
            'name' => 'Student Study & Collaborative Area',
            'capacity' => 80,
            'amenities' => ['Modular Collaborative Tables', 'Power Charging Ports', 'Glassboards for Ideation', 'Campus Wi-Fi 6', 'Natural Daylighting']
        ],
        '3F-13' => [
            'code' => 'SCILAB-2',
            'name' => 'Science Laboratory 2 (Physics)',
            'capacity' => 40,
            'amenities' => ['Optics Bench Kits', 'Precision Electronic Balances', 'Circuit & Mechanics Stations', 'Safety Showers', 'Ceiling Cassette ACU']
        ],
        '3F-15' => [
            'code' => 'SCILAB-3',
            'name' => 'Science Laboratory 3 (Biology)',
            'capacity' => 40,
            'amenities' => ['Binocular Compound Microscopes', 'Autoclave & Incubator Units', 'Dissection Stations', 'Specimen Refrigeration', 'Safety Goggles Station']
        ],
        '4F-GYM' => [
            'code' => 'GYM-401',
            'name' => 'NPC Multi-Purpose Gymnasium & Arena',
            'capacity' => 750,
            'amenities' => ['FIBA Regulation Maple Hardwood Court', 'Electronic LED Scoreboard', '750-Seat Spectator Bleachers', 'High-Bay Stadium Lighting', 'PA Sound System']
        ],
        '4F-01' => [
            'code' => 'ADMIN-401',
            'name' => 'Executive Administration Suite',
            'capacity' => 30,
            'amenities' => ['Conference Boardroom', 'President & Dean Executive Offices', 'Reception Lounge', 'Private Restroom', 'Secure Filing Archives']
        ],
        'RD-COURT' => [
            'code' => 'RD-VB01',
            'name' => 'Open Volleyball & Badminton Court',
            'capacity' => 100,
            'amenities' => ['Anti-Slip Rubberized Sports Coating', 'High Perimeter Safety Netting', 'Night LED Stadium Floodlights', 'Direct Stairwell Access']
        ],
        'RD-EVENT' => [
            'code' => 'RD-EV01',
            'name' => 'Special Events & Activities Deck',
            'capacity' => 300,
            'amenities' => ['Ceramic Tiled Open Gathering Area', 'Overlooking Navotas Cityscape', 'Acoustic Sound Tower Hookups', 'Waterproof Electrical Outlets']
        ],
        'RD-SOLAR' => [
            'code' => 'RD-SOLAR',
            'name' => 'Clean Energy Solar Panel Array (176 Panels)',
            'capacity' => 5,
            'amenities' => ['112 New + 64 Existing Photovoltaic Panels', 'Direct Grid-Tie Inverters', 'Real-Time Energy Metering', 'Clean Energy Generation for NPC']
        ]
    ];

    $results = [];
    foreach ($floorsData as $floorKey => $floor) {
        $rooms = $floor['rooms'] ?? [];
        foreach ($rooms as $r) {
            $rid = $r['id'];
            $preset = $presets[$rid] ?? [];
            $type = $r['type'] ?? 'classroom';

            $color = match($type) {
                'sports' => '#f59e0b',
                'lab' => '#06b6d4',
                'office', 'faculty_office' => '#8b5cf6',
                'amenity', 'canteen', 'garden', 'deck' => '#10b981',
                'restroom' => '#38bdf8',
                'stairs' => '#f43f5e',
                'utility' => '#64748b',
                'solar' => '#1e293b',
                'void' => '#334155',
                default => '#3b82f6'
            };

            $area = (float)($r['area_sqm'] ?? 45.0);
            $capacity = $preset['capacity'] ?? match($type) {
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

            $amenities = $preset['amenities'] ?? match($type) {
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
                'id' => $rid,
                'code' => $preset['code'] ?? ($r['code'] ?? $rid),
                'name' => $preset['name'] ?? $r['name'],
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

    $cached = $results;
    return $results;
}

$rooms = getRoomDefinitions();
echo "Total parsed rooms: " . count($rooms) . "\n";
$byFloor = [];
foreach ($rooms as $r) {
    $byFloor[$r['floor']] = ($byFloor[$r['floor']] ?? 0) + 1;
}
print_r($byFloor);
