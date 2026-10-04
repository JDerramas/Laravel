import json
import os

# Complete master dataset for NPC Campus CAD / 3D BIM reconstruction
# Calibrated directly against DPWH Blueprint Reference Sheets (2F, 3F, 4F, RD)
# 1F Ground Floor is temporarily hidden per user direction.

building_specs = {
    "name": "Navotas Polytechnic College - Main Academic & Gymnasium Building",
    "width": 56.000,
    "depth": 60.500,
    "defaultFloor": "2F",
    "gridX": [
        {"id": "A", "x": 0.000},
        {"id": "B", "x": 5.500},
        {"id": "C", "x": 12.500},
        {"id": "D", "x": 18.000},
        {"id": "E", "x": 24.667},
        {"id": "F", "x": 31.334},
        {"id": "G", "x": 38.001},
        {"id": "H", "x": 43.501},
        {"id": "I", "x": 50.501},
        {"id": "J", "x": 56.001}
    ],
    "gridY": [
        {"id": "1", "y": 0.000},
        {"id": "2", "y": 1.500},
        {"id": "3", "y": 8.300},
        {"id": "4", "y": 15.500},
        {"id": "5", "y": 24.500},
        {"id": "6", "y": 33.500},
        {"id": "7", "y": 42.500},
        {"id": "8", "y": 51.500},
        {"id": "9", "y": 60.500}
    ]
}

# Calibration metadata for the developer Reference Overlay on each floor
# (Ax, Y1) origin in image pixels, scale in px/meter (24.82 px/m)
calibration = {
    "1F": {"image": "assets/img/reference/2F_reference.png", "ax": 922.0, "y1": 1854.0, "scale_x": 24.821, "scale_y": 24.826},
    "2F": {"image": "assets/img/reference/2F_reference.png", "ax": 922.0, "y1": 1854.0, "scale_x": 24.821, "scale_y": 24.826},
    "3F": {"image": "assets/img/reference/3F_reference.png", "ax": 897.0, "y1": 1848.0, "scale_x": 24.821, "scale_y": 24.826},
    "4F": {"image": "assets/img/reference/4F_reference.png", "ax": 884.0, "y1": 1883.0, "scale_x": 24.821, "scale_y": 24.826},
    "RD": {"image": "assets/img/reference/RD_reference.png", "ax": 980.0, "y1": 1856.0, "scale_x": 24.821, "scale_y": 24.826}
}

# --- 1F (GROUND FLOOR) DATA ---
floors_1f = {
    "name": "First Floor — Main Entrance, Lobby, Services & Campus Canteen",
    "elevation": 0.000,
    "rooms": [
        # Main Campus Canteen & Cafeteria (Ground Floor)
        {"id": "1F-13", "code": "1F-13", "name": "NPC Campus Canteen & Cafeteria", "type": "amenity", "x": 18.0, "y": 33.5, "w": 20.001, "d": 9.0, "area_sqm": 161.611, "doors": [{"wall": "south", "offset": 6.0, "w": 2.0, "swing": "out"}, {"wall": "south", "offset": 12.0, "w": 2.0, "swing": "out"}]},
        {"id": "1F-S05", "code": "1F-S05", "name": "Stair-5 (to 2F)", "type": "stairs", "x": 34.0, "y": 39.0, "w": 4.0, "d": 3.5, "area_sqm": 17.524, "direction": "UP"},

        # Entrance Plaza & Grand Information Lobby
        {"id": "1F-ENTR", "code": "1F-ENTR", "name": "Campus Main Entrance Plaza", "type": "amenity", "x": 18.0, "y": 1.5, "w": 20.001, "d": 6.8, "area_sqm": 136.000, "doors": [{"wall": "south", "offset": 10.0, "w": 3.0, "swing": "out"}]},
        {"id": "1F-LOBBY", "code": "1F-LOBBY", "name": "Grand Campus Information Lobby", "type": "amenity", "x": 18.0, "y": 15.5, "w": 20.001, "d": 18.0, "area_sqm": 360.000},
        {"id": "1F-ELEV", "code": "ELEV-CORE", "name": "Elevator Core (E | E)", "type": "utility", "x": 24.667, "y": 13.5, "w": 4.5, "d": 2.0},

        # Front Administration & Student Service Counters
        {"id": "1F-REG", "code": "1F-REG", "name": "Registrar Front Service Counters", "type": "office", "x": 7.677, "y": 1.5, "w": 10.323, "d": 6.8, "area_sqm": 70.196, "doors": [{"wall": "north", "offset": 4.0, "w": 1.6, "swing": "in"}]},
        {"id": "1F-CLINIC", "code": "1F-CLINIC", "name": "Campus Health Clinic & Infirmary", "type": "office", "x": 38.001, "y": 1.5, "w": 10.0, "d": 6.8, "area_sqm": 68.000, "doors": [{"wall": "north", "offset": 2.5, "w": 1.6, "swing": "in"}]},
        {"id": "1F-SEC", "code": "1F-SEC", "name": "Campus Security & Safety Office", "type": "office", "x": 48.001, "y": 1.5, "w": 8.0, "d": 6.8, "area_sqm": 54.400, "doors": [{"wall": "north", "offset": 2.0, "w": 1.2, "swing": "in"}]},

        # Ground Restrooms & Vertical Circulation
        {"id": "1F-T01", "code": "1F-T01", "name": "Toilet-1/F", "type": "restroom", "x": 0.0, "y": 1.5, "w": 7.677, "d": 3.3, "area_sqm": 30.274},
        {"id": "1F-T02", "code": "1F-T02", "name": "Toilet-1/M", "type": "restroom", "x": 0.0, "y": 4.8, "w": 5.5, "d": 7.2, "area_sqm": 34.089, "polygon": [[0.0, 4.8], [5.5, 4.8], [5.5, 8.3], [2.5, 8.3], [2.5, 12.0], [0.0, 12.0]]},
        {"id": "1F-S02", "code": "1F-S02", "name": "Stair-2", "type": "stairs", "x": 12.500, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "UP"},
        {"id": "1F-S01", "code": "1F-S01", "name": "Stair-1", "type": "stairs", "x": 39.281, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "UP"},
        {"id": "1F-S04", "code": "1F-S04", "name": "Stair-4", "type": "stairs", "x": 12.5, "y": 42.5, "w": 3.3, "d": 6.0, "area_sqm": 15.063, "direction": "UP"},
        {"id": "1F-S03", "code": "1F-S03", "name": "Stair-3", "type": "stairs", "x": 40.0, "y": 42.5, "w": 3.5, "d": 6.0, "area_sqm": 19.971, "direction": "UP"}
    ]
}

# --- 2F DATA ---
floors_2f = {
    "name": "Second Floor — Academic & Student Services",
    "elevation": 4.185,
    "rooms": [
        # North Wing (Grids 7-9)
        {"id": "2F-FE02", "code": "2F-FE02", "name": "Fire Escape Stairs 2", "type": "stairs", "x": 0.0, "y": 55.5, "w": 5.5, "d": 5.0, "area_sqm": 24.102},
        {"id": "2F-U01", "code": "2F-U01", "name": "Elec. Rm.-2", "type": "utility", "x": 5.5, "y": 53.7, "w": 2.28, "d": 1.8, "area_sqm": 5.437, "doors": [{"wall": "east", "offset": 0.8, "w": 0.9, "swing": "in"}]},
        {"id": "2F-10", "code": "2F-10", "name": "Sto.-4", "type": "utility", "x": 5.5, "y": 51.5, "w": 2.28, "d": 2.2, "area_sqm": 5.654, "doors": [{"wall": "east", "offset": 1.0, "w": 0.9, "swing": "in"}]},
        {"id": "2F-09", "code": "2F-09", "name": "Computer Laboratory 1", "type": "lab", "x": 0.0, "y": 42.5, "w": 7.78, "d": 13.0, "area_sqm": 101.896, "labelX": 3.89, "labelY": 47.0, "polygon": [[0.0, 42.5], [7.78, 42.5], [7.78, 51.5], [5.5, 51.5], [5.5, 55.5], [0.0, 55.5]], "doors": [{"wall": "east", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "east", "offset": 7.5, "w": 1.2, "swing": "out"}]},
        {"id": "2F-T04", "code": "2F-T04", "name": "Toilet-3/M", "type": "restroom", "x": 10.75, "y": 56.0, "w": 7.25, "d": 4.5, "area_sqm": 30.085, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-T05", "code": "2F-T05", "name": "Toilet-3/F", "type": "restroom", "x": 10.75, "y": 51.5, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-11", "code": "2F-11", "name": "Computer Laboratory 2", "type": "lab", "x": 18.0, "y": 51.5, "w": 10.0, "d": 9.0, "area_sqm": 92.509, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 8.5, "w": 1.2, "swing": "out"}]},
        {"id": "2F-15", "code": "2F-15", "name": "Computer Laboratory 3", "type": "lab", "x": 28.0, "y": 51.5, "w": 10.001, "d": 9.0, "area_sqm": 92.509, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 8.5, "w": 1.2, "swing": "out"}]},
        {"id": "2F-17", "code": "2F-17", "name": "Computer Laboratory 4", "type": "lab", "x": 38.001, "y": 51.5, "w": 8.0, "d": 9.0, "area_sqm": 72.002, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 6.5, "w": 1.2, "swing": "out"}]},
        {"id": "2F-19-STO", "code": "2F-19", "name": "Computer Sto.", "type": "utility", "x": 48.501, "y": 51.5, "w": 7.500, "d": 5.800, "area_sqm": 44.514, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-FE03", "code": "2F-FE03", "name": "Fire Escape Stairs 3", "type": "stairs", "x": 48.501, "y": 57.300, "w": 7.500, "d": 3.200, "area_sqm": 24.102, "doors": [{"wall": "west", "offset": 0.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-T06", "code": "2F-T06", "name": "Toilet-4/M", "type": "restroom", "x": 48.751, "y": 47.0, "w": 7.250, "d": 4.5, "area_sqm": 29.967, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-T07", "code": "2F-T07", "name": "Toilet-4/F", "type": "restroom", "x": 48.751, "y": 42.5, "w": 7.250, "d": 4.5, "area_sqm": 30.184, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-S04", "code": "2F-S04", "name": "Stair-4", "type": "stairs", "x": 12.5, "y": 42.5, "w": 3.3, "d": 6.0, "area_sqm": 15.063, "direction": "UP/DN"},
        {"id": "2F-12", "code": "2F-12", "name": "Academic Affairs / Coordinator Office", "type": "office", "x": 15.8, "y": 42.5, "w": 11.0, "d": 6.0, "area_sqm": 66.561, "doors": [{"wall": "north", "offset": 1.2, "w": 1.2, "swing": "in"}, {"wall": "north", "offset": 8.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-14", "code": "2F-14", "name": "MIS Office", "type": "office", "x": 28.8, "y": 42.5, "w": 7.0, "d": 6.0, "area_sqm": 41.664, "doors": [{"wall": "north", "offset": 5.2, "w": 1.2, "swing": "in"}]},
        {"id": "2F-16", "code": "2F-16", "name": "NSTP Office", "type": "office", "x": 35.8, "y": 42.5, "w": 4.2, "d": 6.0, "area_sqm": 24.870, "doors": [{"wall": "north", "offset": 0.8, "w": 1.2, "swing": "in"}]},
        {"id": "2F-S03", "code": "2F-S03", "name": "Stair-3", "type": "stairs", "x": 40.0, "y": 42.5, "w": 3.5, "d": 6.0, "area_sqm": 19.971, "direction": "UP/DN"},

        # West Wing Classrooms (Grids 4-7, A-D)
        {"id": "2F-07", "code": "2F-07", "name": "Classroom-5", "type": "classroom", "x": 0.0, "y": 33.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-05", "code": "2F-05", "name": "Classroom-3", "type": "classroom", "x": 0.0, "y": 24.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-03", "code": "2F-03", "name": "Classroom-1", "type": "classroom", "x": 0.0, "y": 15.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-08", "code": "2F-08", "name": "Classroom-6", "type": "classroom", "x": 10.28, "y": 33.5, "w": 7.72, "d": 9.0, "area_sqm": 69.516, "doors": [{"wall": "west", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "west", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-06", "code": "2F-06", "name": "Classroom-4", "type": "classroom", "x": 10.28, "y": 24.5, "w": 7.72, "d": 9.0, "area_sqm": 69.518, "doors": [{"wall": "west", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "west", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-04", "code": "2F-04", "name": "Classroom-2", "type": "classroom", "x": 10.28, "y": 15.5, "w": 7.72, "d": 9.0, "area_sqm": 69.520, "doors": [{"wall": "west", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "west", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-FE01", "code": "2F-FE01", "name": "Fire Escape Stairs 1", "type": "stairs", "x": 0.0, "y": 12.0, "w": 5.5, "d": 3.5, "area_sqm": 24.102},
        {"id": "2F-T03", "code": "2F-T03", "name": "PWD Toilet-2", "type": "restroom", "x": 2.5, "y": 8.3, "w": 3.0, "d": 3.7, "area_sqm": 5.965},
        {"id": "2F-T02", "code": "2F-T02", "name": "Toilet-2/M", "type": "restroom", "x": 0.0, "y": 4.8, "w": 5.5, "d": 7.2, "area_sqm": 34.089, "polygon": [[0.0, 4.8], [5.5, 4.8], [5.5, 8.3], [2.5, 8.3], [2.5, 12.0], [0.0, 12.0]]},
        {"id": "2F-S02", "code": "2F-S02", "name": "Stair-2", "type": "stairs", "x": 12.500, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "UP/DN"},

        # Center Wing & Atrium (Grids 4-7, D-G)
        {"id": "2F-13", "code": "2F-13", "name": "Student Multi-Purpose Hall & Activity Center", "type": "amenity", "x": 18.0, "y": 33.5, "w": 20.001, "d": 9.0, "area_sqm": 161.611, "doors": [{"wall": "south", "offset": 6.0, "w": 2.0, "swing": "out"}, {"wall": "south", "offset": 12.0, "w": 2.0, "swing": "out"}]},
        {"id": "2F-S05", "code": "2F-S05", "name": "Stair-5", "type": "stairs", "x": 34.0, "y": 39.0, "w": 4.0, "d": 3.5, "area_sqm": 17.524, "direction": "UP/DN"},
        {"id": "2F-G01", "code": "2F-G01", "name": "Roof Garden", "type": "amenity", "x": 21.334, "y": 24.5, "w": 13.333, "d": 9.0, "area_sqm": 107.877},
        {"id": "2F-A01L", "code": "2F-A01", "name": "Garden (West)", "type": "amenity", "x": 18.0, "y": 24.5, "w": 3.334, "d": 9.0, "area_sqm": 34.710},
        {"id": "2F-A01R", "code": "2F-A01", "name": "Garden (East)", "type": "amenity", "x": 34.667, "y": 24.5, "w": 3.334, "d": 9.0, "area_sqm": 34.710},
        {"id": "2F-VOID", "code": "OPEN ABOVE", "name": "Central Open Void / Atrium", "type": "void", "x": 21.334, "y": 15.5, "w": 13.333, "d": 9.0},
        {"id": "2F-PERGL", "code": "TRELLIS-W", "name": "Trellis Pergola West", "type": "amenity", "x": 18.0, "y": 15.5, "w": 3.334, "d": 9.0},
        {"id": "2F-PERGR", "code": "TRELLIS-E", "name": "Trellis Pergola East", "type": "amenity", "x": 34.667, "y": 15.5, "w": 3.334, "d": 9.0},
        {"id": "2F-ELEV", "code": "ELEV-CORE", "name": "Elevator Core (E | E)", "type": "utility", "x": 24.667, "y": 13.5, "w": 4.5, "d": 2.0},

        # East Wing Classrooms (Grids 4-7, G-J)
        {"id": "2F-19", "code": "2F-19", "name": "Classroom-7", "type": "classroom", "x": 38.001, "y": 33.5, "w": 7.72, "d": 9.0, "area_sqm": 69.516, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-21", "code": "2F-21", "name": "Classroom-9", "type": "classroom", "x": 38.001, "y": 24.5, "w": 7.72, "d": 9.0, "area_sqm": 69.518, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-23", "code": "2F-23", "name": "Classroom-11", "type": "classroom", "x": 38.001, "y": 15.5, "w": 7.72, "d": 9.0, "area_sqm": 69.520, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "east", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-20", "code": "2F-20", "name": "Classroom-8", "type": "classroom", "x": 48.221, "y": 33.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989, "doors": [{"wall": "west", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "west", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-22", "code": "2F-22", "name": "Classroom-10", "type": "classroom", "x": 48.221, "y": 24.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989, "doors": [{"wall": "west", "offset": 1.5, "w": 1.0, "swing": "in"}, {"wall": "west", "offset": 7.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-24", "code": "2F-24", "name": "Guidance Office", "type": "office", "x": 48.221, "y": 18.5, "w": 7.780, "d": 6.0, "area_sqm": 44.037, "doors": [{"wall": "west", "offset": 2.0, "w": 1.2, "swing": "in"}]},
        {"id": "2F-FE04", "code": "2F-FE04", "name": "Fire Escape Stairs 4", "type": "stairs", "x": 48.221, "y": 15.3, "w": 7.780, "d": 3.2, "area_sqm": 24.102},
        {"id": "2F-25", "code": "2F-25", "name": "SAO Office (Student Affairs)", "type": "office", "x": 48.221, "y": 10.6, "w": 7.780, "d": 4.7, "area_sqm": 36.492, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "2F-S01", "code": "2F-S01", "name": "Stair-1", "type": "stairs", "x": 39.281, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "UP/DN"},

        # South Wing Administrative (Grids 1-3, A-J)
        {"id": "2F-T01", "code": "2F-T01", "name": "Toilet-2/F", "type": "restroom", "x": 0.0, "y": 1.5, "w": 7.677, "d": 3.3, "area_sqm": 30.274},
        {"id": "2F-02", "code": "2F-02", "name": "Registrar's Office-2", "type": "office", "x": 7.677, "y": 1.5, "w": 16.990, "d": 6.8, "area_sqm": 138.783, "doors": [{"wall": "north", "offset": 6.0, "w": 1.6, "swing": "in"}]},
        {"id": "2F-01", "code": "2F-01", "name": "Cashier's Office", "type": "office", "x": 24.667, "y": 1.5, "w": 13.334, "d": 6.8, "area_sqm": 97.215, "doors": [{"wall": "north", "offset": 3.0, "w": 1.6, "swing": "in"}]},
        {"id": "2F-28", "code": "2F-28", "name": "AVR (Audio-Visual Room)", "type": "amenity", "x": 38.001, "y": 1.5, "w": 10.0, "d": 6.8, "area_sqm": 74.631, "doors": [{"wall": "north", "offset": 2.5, "w": 1.6, "swing": "in"}]},
        {"id": "2F-27", "code": "2F-27", "name": "Student Org. Office 1", "type": "office", "x": 48.001, "y": 1.5, "w": 4.0, "d": 6.8, "area_sqm": 27.554, "doors": [{"wall": "north", "offset": 1.5, "w": 1.0, "swing": "in"}]},
        {"id": "2F-26", "code": "2F-26", "name": "Student Org. Office 2", "type": "office", "x": 52.000, "y": 1.5, "w": 4.001, "d": 6.8, "area_sqm": 36.735, "doors": [{"wall": "north", "offset": 2.0, "w": 1.2, "swing": "in"}]}
    ]
}

# --- 3F DATA ---
floors_3f = {
    "name": "Third Floor — Library & Science Laboratories",
    "elevation": 7.785,
    "rooms": [
        # South Wing: The Magnificent Central Library
        {"id": "3F-T01", "code": "3F-T01", "name": "Toilet-5/F", "type": "restroom", "x": 0.0, "y": 1.5, "w": 7.677, "d": 3.3, "area_sqm": 30.274},
        {"id": "3F-T02", "code": "3F-T02", "name": "Toilet-5/M", "type": "restroom", "x": 0.0, "y": 4.8, "w": 5.5, "d": 7.2, "area_sqm": 34.089, "polygon": [[0.0, 4.8], [5.5, 4.8], [5.5, 8.3], [2.5, 8.3], [2.5, 12.0], [0.0, 12.0]]},
        {"id": "3F-T03", "code": "3F-T03", "name": "PWD Toilet-3", "type": "restroom", "x": 2.5, "y": 8.3, "w": 3.0, "d": 3.7, "area_sqm": 5.965},
        {"id": "3F-01", "code": "3F-01", "name": "NPC Central Library", "type": "amenity", "x": 7.677, "y": 1.5, "w": 41.324, "d": 6.8, "area_sqm": 401.591, "doors": [{"wall": "north", "offset": 6.0, "w": 2.4, "swing": "in"}, {"wall": "north", "offset": 18.0, "w": 2.4, "swing": "in"}]},
        {"id": "3F-01A", "code": "3F-01A", "name": "Librarian Office", "type": "office", "x": 49.001, "y": 1.5, "w": 7.000, "d": 6.8, "area_sqm": 35.251, "doors": [{"wall": "west", "offset": 2.0, "w": 1.2, "swing": "in"}]},

        # Center Wing: Study Area & Open Court
        {"id": "3F-12", "code": "3F-12", "name": "Study Area", "type": "amenity", "x": 18.0, "y": 33.5, "w": 20.001, "d": 9.0, "area_sqm": 161.966, "doors": [{"wall": "south", "offset": 8.0, "w": 2.0, "swing": "out"}]},
        {"id": "3F-S05", "code": "3F-S05", "name": "Stair-5", "type": "stairs", "x": 34.0, "y": 39.0, "w": 4.0, "d": 3.5, "area_sqm": 17.524, "direction": "UP/DN"},
        {"id": "3F-VOID", "code": "OPEN", "name": "Central Open Void / Courtyard", "type": "void", "x": 18.0, "y": 15.5, "w": 20.001, "d": 18.0},
        {"id": "3F-ELEV", "code": "ELEV-CORE", "name": "Elevator Core (E | E)", "type": "utility", "x": 24.667, "y": 13.5, "w": 4.5, "d": 2.0},

        # North Wing: Science Laboratories & Faculty
        {"id": "3F-FE02", "code": "3F-FE02", "name": "Fire Escape Stairs 2", "type": "stairs", "x": 0.0, "y": 55.5, "w": 5.5, "d": 5.0, "area_sqm": 24.102},
        {"id": "3F-09", "code": "3F-09", "name": "Faculty-1", "type": "office", "x": 0.0, "y": 51.5, "w": 5.5, "d": 4.0, "area_sqm": 37.559, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}]},
        {"id": "3F-U01", "code": "3F-U01", "name": "Elec. Rm.-3", "type": "utility", "x": 5.5, "y": 51.5, "w": 2.28, "d": 4.0, "area_sqm": 5.437, "doors": [{"wall": "east", "offset": 1.5, "w": 0.9, "swing": "in"}]},
        {"id": "3F-08", "code": "3F-08", "name": "Speech Laboratory 1", "type": "lab", "x": 0.0, "y": 42.5, "w": 7.78, "d": 9.0, "area_sqm": 69.983, "doors": [{"wall": "east", "offset": 2.0, "w": 1.2, "swing": "out"}]},
        {"id": "3F-T04", "code": "3F-T04", "name": "Toilet-6/M", "type": "restroom", "x": 10.75, "y": 56.0, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "3F-T05", "code": "3F-T05", "name": "Toilet-6/F", "type": "restroom", "x": 10.75, "y": 51.5, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "3F-10", "code": "3F-10", "name": "Science Laboratory 1", "type": "lab", "x": 18.0, "y": 51.5, "w": 10.0, "d": 9.0, "area_sqm": 93.009, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 8.5, "w": 1.2, "swing": "out"}]},
        {"id": "3F-13", "code": "3F-13", "name": "Science Laboratory 2", "type": "lab", "x": 28.0, "y": 51.5, "w": 10.001, "d": 9.0, "area_sqm": 93.007, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 8.5, "w": 1.2, "swing": "out"}]},
        {"id": "3F-16", "code": "3F-16", "name": "Science Laboratory 3", "type": "lab", "x": 38.001, "y": 51.5, "w": 8.0, "d": 9.0, "area_sqm": 72.388, "doors": [{"wall": "south", "offset": 1.5, "w": 1.2, "swing": "out"}, {"wall": "south", "offset": 6.5, "w": 1.2, "swing": "out"}]},
        {"id": "3F-17", "code": "3F-17", "name": "Science Lab Dressing Rm.", "type": "utility", "x": 48.501, "y": 51.5, "w": 7.500, "d": 5.800, "area_sqm": 44.891, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "3F-FE03", "code": "3F-FE03", "name": "Fire Escape Stairs 3", "type": "stairs", "x": 48.501, "y": 57.300, "w": 7.500, "d": 3.200, "area_sqm": 24.102, "doors": [{"wall": "west", "offset": 0.5, "w": 1.2, "swing": "in"}]},
        {"id": "3F-T06", "code": "3F-T06", "name": "Toilet-7/M", "type": "restroom", "x": 48.751, "y": 47.0, "w": 7.250, "d": 4.5, "area_sqm": 29.967},
        {"id": "3F-T07", "code": "3F-T07", "name": "Toilet-7/F", "type": "restroom", "x": 48.751, "y": 42.5, "w": 7.250, "d": 4.5, "area_sqm": 30.026},
        {"id": "3F-S04", "code": "3F-S04", "name": "Stair-4", "type": "stairs", "x": 12.5, "y": 42.5, "w": 3.3, "d": 6.0, "direction": "UP/DN"},
        {"id": "3F-11", "code": "3F-11", "name": "Multi-Purpose Room / Prayer Rm.", "type": "amenity", "x": 15.8, "y": 42.5, "w": 11.0, "d": 6.0, "area_sqm": 67.182, "doors": [{"wall": "north", "offset": 1.2, "w": 1.2, "swing": "in"}, {"wall": "north", "offset": 8.5, "w": 1.2, "swing": "in"}]},
        {"id": "3F-14", "code": "3F-14", "name": "Science Faculty Rm.", "type": "office", "x": 28.8, "y": 42.5, "w": 7.0, "d": 6.0, "area_sqm": 41.664, "doors": [{"wall": "north", "offset": 5.2, "w": 1.2, "swing": "in"}]},
        {"id": "3F-15", "code": "3F-15", "name": "Science Sto. Rm.", "type": "utility", "x": 35.8, "y": 42.5, "w": 4.2, "d": 6.0, "area_sqm": 25.515, "doors": [{"wall": "north", "offset": 0.8, "w": 1.0, "swing": "in"}]},
        {"id": "3F-S03", "code": "3F-S03", "name": "Stair-3", "type": "stairs", "x": 40.0, "y": 42.5, "w": 3.5, "d": 6.0, "direction": "UP/DN"},

        # West Wing Classrooms
        {"id": "3F-07", "code": "3F-07", "name": "Classroom-16", "type": "classroom", "x": 0.0, "y": 33.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "3F-05", "code": "3F-05", "name": "Classroom-14", "type": "classroom", "x": 0.0, "y": 24.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "3F-03", "code": "3F-03", "name": "Classroom-12", "type": "classroom", "x": 0.0, "y": 15.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "3F-08B", "code": "3F-08B", "name": "Classroom-17", "type": "classroom", "x": 10.28, "y": 33.5, "w": 7.72, "d": 9.0, "area_sqm": 69.516},
        {"id": "3F-06", "code": "3F-06", "name": "Classroom-15", "type": "classroom", "x": 10.28, "y": 24.5, "w": 7.72, "d": 9.0, "area_sqm": 69.518},
        {"id": "3F-04", "code": "3F-04", "name": "Classroom-13", "type": "classroom", "x": 10.28, "y": 15.5, "w": 7.72, "d": 9.0, "area_sqm": 69.520},
        {"id": "3F-FE01", "code": "3F-FE01", "name": "Fire Escape Stairs 1", "type": "stairs", "x": 0.0, "y": 12.0, "w": 5.5, "d": 3.5},
        {"id": "3F-S02", "code": "3F-S02", "name": "Stair-2", "type": "stairs", "x": 12.500, "y": 11.5, "w": 4.22, "d": 4.0, "direction": "UP/DN"},

        # East Wing Classrooms
        {"id": "3F-18", "code": "3F-18", "name": "Classroom-18", "type": "classroom", "x": 38.001, "y": 33.5, "w": 7.72, "d": 9.0, "area_sqm": 69.516},
        {"id": "3F-20", "code": "3F-20", "name": "Classroom-20", "type": "classroom", "x": 38.001, "y": 24.5, "w": 7.72, "d": 9.0, "area_sqm": 69.518},
        {"id": "3F-22", "code": "3F-22", "name": "Classroom-22", "type": "classroom", "x": 38.001, "y": 15.5, "w": 7.72, "d": 9.0, "area_sqm": 69.520},
        {"id": "3F-19", "code": "3F-19", "name": "Classroom-19", "type": "classroom", "x": 48.221, "y": 33.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "3F-21", "code": "3F-21", "name": "Classroom-21", "type": "classroom", "x": 48.221, "y": 24.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "3F-23", "code": "3F-23", "name": "Faculty Room-2", "type": "office", "x": 48.221, "y": 18.5, "w": 7.780, "d": 6.0, "area_sqm": 45.886},
        {"id": "3F-FE04", "code": "3F-FE04", "name": "Fire Escape Stairs 4", "type": "stairs", "x": 48.221, "y": 15.3, "w": 7.780, "d": 3.2, "area_sqm": 24.102},
        {"id": "3F-01B", "code": "3F-01B", "name": "Library Storage", "type": "utility", "x": 48.221, "y": 10.6, "w": 7.780, "d": 4.7, "area_sqm": 37.708},
        {"id": "3F-S01", "code": "3F-S01", "name": "Stair-1", "type": "stairs", "x": 39.281, "y": 11.5, "w": 4.22, "d": 4.0, "direction": "UP/DN"}
    ]
}

# --- 4F DATA ---
floors_4f = {
    "name": "Fourth Floor — Gymnasium Arena & Executive Admin",
    "elevation": 11.385,
    "rooms": [
        # Full FIBA Basketball Arena (Grids D-J, 6-9)
        {"id": "4F-GYM", "code": "4F-GYM", "name": "Gymnasium & FIBA Basketball Arena", "type": "sports", "x": 18.0, "y": 33.5, "w": 38.001, "d": 27.0, "area_sqm": 1026.0, "court": {"type": "fiba_basketball", "length": 28.0, "width": 15.0}},
        {"id": "4F-FE03", "code": "4F-FE03", "name": "Fire Escape Stairs 3", "type": "stairs", "x": 48.501, "y": 57.300, "w": 7.500, "d": 3.200, "area_sqm": 24.102},

        # PE, Sports & Research Facilities (East of Atrium)
        {"id": "4F-11", "code": "4F-11", "name": "Sports Sto. Room", "type": "utility", "x": 38.001, "y": 28.500, "w": 5.500, "d": 5.000, "area_sqm": 25.500, "doors": [{"wall": "east", "offset": 1.0, "w": 1.0, "swing": "out"}]},
        {"id": "4F-12", "code": "4F-12", "name": "Research & Publication Rm.", "type": "office", "x": 38.001, "y": 18.300, "w": 5.500, "d": 10.200, "area_sqm": 59.239, "doors": [{"wall": "east", "offset": 1.2, "w": 1.2, "swing": "out"}]},
        {"id": "4F-12A", "code": "4F-12A", "name": "File Sto.", "type": "utility", "x": 38.001, "y": 15.100, "w": 5.500, "d": 3.200, "area_sqm": 18.073, "doors": [{"wall": "north", "offset": 2.5, "w": 1.0, "swing": "in"}]},
        {"id": "4F-T07", "code": "4F-T07", "name": "Female Locker/Shower Rm.-1", "type": "restroom", "x": 48.001, "y": 28.5, "w": 8.0, "d": 5.0, "area_sqm": 31.176, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "4F-T06", "code": "4F-T06", "name": "Male Locker/Shower Rm.-1", "type": "restroom", "x": 48.001, "y": 23.5, "w": 8.0, "d": 5.0, "area_sqm": 31.176, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "4F-13", "code": "4F-13", "name": "Sports Faculty Room", "type": "office", "x": 48.001, "y": 17.7, "w": 8.0, "d": 5.8, "area_sqm": 64.523, "doors": [{"wall": "west", "offset": 2.0, "w": 1.2, "swing": "in"}]},
        {"id": "4F-FE04", "code": "4F-FE04", "name": "Fire Escape Stairs 4", "type": "stairs", "x": 48.001, "y": 14.5, "w": 8.0, "d": 3.2, "area_sqm": 24.102},
        {"id": "4F-S01", "code": "4F-S01", "name": "Stair-1", "type": "stairs", "x": 39.281, "y": 11.5, "w": 4.22, "d": 4.0, "direction": "UP/DN"},

        # West Wing Classrooms & Labs
        {"id": "4F-FE02", "code": "4F-FE02", "name": "Fire Escape Stairs 2", "type": "stairs", "x": 0.0, "y": 55.5, "w": 5.5, "d": 5.0},
        {"id": "4F-09", "code": "4F-09", "name": "Faculty-3", "type": "office", "x": 0.0, "y": 51.5, "w": 5.5, "d": 4.0, "area_sqm": 37.823, "doors": [{"wall": "east", "offset": 1.5, "w": 1.0, "swing": "in"}]},
        {"id": "4F-U01", "code": "4F-U01", "name": "Elec. Rm.-4", "type": "utility", "x": 5.5, "y": 51.5, "w": 2.28, "d": 4.0, "area_sqm": 5.437, "doors": [{"wall": "east", "offset": 1.5, "w": 0.9, "swing": "in"}]},
        {"id": "4F-08", "code": "4F-08", "name": "Computer Laboratory 5 (AI Lab)", "type": "lab", "x": 0.0, "y": 42.5, "w": 7.78, "d": 9.0, "area_sqm": 69.983, "doors": [{"wall": "east", "offset": 2.0, "w": 1.2, "swing": "out"}]},
        {"id": "4F-T04", "code": "4F-T04", "name": "Toilet-9/M", "type": "restroom", "x": 10.75, "y": 56.0, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "4F-T05", "code": "4F-T05", "name": "Toilet-9/F", "type": "restroom", "x": 10.75, "y": 51.5, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "4F-06", "code": "4F-06", "name": "Classroom-27", "type": "classroom", "x": 0.0, "y": 33.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "4F-04", "code": "4F-04", "name": "Classroom-25", "type": "classroom", "x": 0.0, "y": 24.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "4F-02", "code": "4F-02", "name": "Classroom-23", "type": "classroom", "x": 0.0, "y": 15.5, "w": 7.78, "d": 9.0, "area_sqm": 69.989},
        {"id": "4F-07", "code": "4F-07", "name": "Classroom-28", "type": "classroom", "x": 10.28, "y": 33.5, "w": 7.72, "d": 9.0, "area_sqm": 69.516},
        {"id": "4F-05", "code": "4F-05", "name": "Classroom-26", "type": "classroom", "x": 10.28, "y": 24.5, "w": 7.72, "d": 9.0, "area_sqm": 69.518},
        {"id": "4F-03", "code": "4F-03", "name": "Classroom-24", "type": "classroom", "x": 10.28, "y": 15.5, "w": 7.72, "d": 9.0, "area_sqm": 69.520},
        {"id": "4F-FE01", "code": "4F-FE01", "name": "Fire Escape Stairs 1", "type": "stairs", "x": 0.0, "y": 12.0, "w": 5.5, "d": 3.5},
        {"id": "4F-S02", "code": "4F-S02", "name": "Stair-2", "type": "stairs", "x": 12.500, "y": 11.5, "w": 4.22, "d": 4.0, "direction": "UP/DN"},
        {"id": "4F-S04", "code": "4F-S04", "name": "Stair-4", "type": "stairs", "x": 12.5, "y": 42.5, "w": 3.5, "d": 6.0, "direction": "UP/DN"},

        # South Executive Administration Wing
        {"id": "4F-T01", "code": "4F-T01", "name": "Toilet-8/F", "type": "restroom", "x": 0.0, "y": 0.0, "w": 7.677, "d": 4.8, "area_sqm": 30.274, "doors": [{"wall": "north", "offset": 6.0, "w": 1.2, "swing": "in"}]},
        {"id": "4F-T02", "code": "4F-T02", "name": "Toilet-8/M", "type": "restroom", "x": 0.0, "y": 4.8, "w": 5.5, "d": 7.2, "area_sqm": 34.089, "polygon": [[0.0, 4.8], [5.5, 4.8], [5.5, 8.3], [2.5, 8.3], [2.5, 12.0], [0.0, 12.0]]},
        {"id": "4F-T03", "code": "4F-T03", "name": "PWD Toilet-4", "type": "restroom", "x": 2.5, "y": 8.3, "w": 3.0, "d": 3.7, "area_sqm": 5.965},
        {"id": "4F-01B", "code": "4F-01B", "name": "Private Toilet", "type": "restroom", "x": 7.677, "y": 0.0, "w": 2.123, "d": 2.7, "area_sqm": 5.040, "doors": [{"wall": "north", "offset": 0.8, "w": 0.9, "swing": "in"}]},
        {"id": "4F-01A", "code": "4F-01A", "name": "Office (Dean / Board)", "type": "office", "x": 7.677, "y": 0.0, "w": 4.823, "d": 8.3, "area_sqm": 42.022, "labelX": 10.5, "labelY": 5.2, "polygon": [[7.677, 2.7], [9.8, 2.7], [9.8, 0.0], [12.5, 0.0], [12.5, 8.3], [7.677, 8.3]], "doors": [{"wall": "east", "offset": 3.5, "w": 1.2, "swing": "in"}]},
        {"id": "4F-01", "code": "4F-01", "name": "Administration Office Suite", "type": "office", "x": 12.5, "y": 0.0, "w": 25.501, "d": 8.3, "area_sqm": 180.474, "labelX": 23.0, "labelY": 5.0, "polygon": [[12.5, 0.0], [24.667, 0.0], [38.001, 5.8], [38.001, 8.3], [12.5, 8.3]], "doors": [{"wall": "north", "offset": 7.5, "w": 2.0, "swing": "in"}]},
        {"id": "4F-TERR", "code": "4F-TERR", "name": "Terrace", "type": "amenity", "x": 24.667, "y": 0.0, "w": 31.334, "d": 14.5, "labelX": 45.0, "labelY": 4.0, "polygon": [[24.667, 0.0], [56.001, 0.0], [56.001, 14.5], [50.501, 14.5], [50.501, 5.8], [38.001, 5.8]], "area_sqm": 190.919, "features": ["glass_wall", "open_terrace", "balustrade"]}
    ]
}

# --- RD (ROOF DECK) DATA ---
floors_rd = {
    "name": "Roof Deck — Sports Arena, ACU Mechanical & Event Plaza",
    "elevation": 15.500,
    "rooms": [
        {"id": "RD-COURT", "code": "RD-COURT", "name": "Volleyball / Badminton Court", "type": "sports", "x": 0.0, "y": 24.5, "w": 18.0, "d": 18.0, "court": {"type": "volleyball", "length": 18.0, "width": 9.0}, "area_sqm": 324.0},
        {"id": "RD-T01", "code": "RD-T01", "name": "Toilet-10/M", "type": "restroom", "x": 10.75, "y": 56.0, "w": 7.25, "d": 4.5, "area_sqm": 30.085, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "RD-T02", "code": "RD-T02", "name": "Toilet-10/F", "type": "restroom", "x": 10.75, "y": 51.5, "w": 7.25, "d": 4.5, "area_sqm": 30.307, "doors": [{"wall": "west", "offset": 1.5, "w": 1.2, "swing": "in"}]},
        {"id": "RD-S04", "code": "RD-S04", "name": "Stair-4", "type": "stairs", "x": 12.5, "y": 42.5, "w": 3.5, "d": 6.0, "area_sqm": 15.063, "direction": "DN"},
        {"id": "RD-STO7", "code": "RD-STO7", "name": "Sto. 7", "type": "utility", "x": 16.0, "y": 42.5, "w": 2.0, "d": 6.0, "area_sqm": 12.0},
        {"id": "RD-LOCKF", "code": "RD-LOCKF", "name": "Female Locker/Sho. Rm.-2", "type": "restroom", "x": 0.0, "y": 51.5, "w": 5.5, "d": 4.0, "area_sqm": 24.0},
        {"id": "RD-LOCKM", "code": "RD-LOCKM", "name": "Male Locker/Sho. Rm.-2", "type": "restroom", "x": 5.5, "y": 51.5, "w": 2.28, "d": 2.0, "area_sqm": 10.0},
        {"id": "RD-U01", "code": "RD-U01", "name": "Elec. Rm.-5", "type": "utility", "x": 5.5, "y": 53.5, "w": 2.28, "d": 2.0, "area_sqm": 5.437},
        {"id": "RD-GYMVOID", "code": "OPEN TO BELOW", "name": "Gymnasium Arena High-Ceiling Void", "type": "void", "x": 18.0, "y": 33.5, "w": 38.001, "d": 27.0},
        {"id": "RD-ACUN", "code": "RD-ACUN", "name": "ACU Mechanical Bank North (7.5TR & 6.0HP)", "type": "utility", "x": 18.0, "y": 56.5, "w": 32.501, "d": 4.0, "area_sqm": 114.0},
        {"id": "RD-ACUW", "code": "RD-ACUW", "name": "ACU Mechanical Bank West (7.5TR)", "type": "utility", "x": 18.0, "y": 33.5, "w": 6.667, "d": 23.0, "area_sqm": 72.0},
        {"id": "RD-EVENT", "code": "RD-EVENT", "name": "Open Space for Special Events & Activities", "type": "amenity", "x": 18.0, "y": 15.5, "w": 38.001, "d": 18.0, "area_sqm": 684.0},
        {"id": "RD-LOBBY", "code": "RD-LOBBY", "name": "Deck Elevator Lobby", "type": "amenity", "x": 24.667, "y": 8.3, "w": 6.667, "d": 7.2, "area_sqm": 48.0},
        {"id": "RD-MECH", "code": "RD-MECH", "name": "Mechanical Area & Chiller Room", "type": "utility", "x": 48.001, "y": 24.5, "w": 8.0, "d": 9.0, "area_sqm": 72.0},
        {"id": "RD-STO5", "code": "RD-STO5", "name": "Storage-5", "type": "utility", "x": 48.001, "y": 18.5, "w": 8.0, "d": 6.0, "area_sqm": 48.0},
        {"id": "RD-STO6", "code": "RD-STO6", "name": "Storage-6", "type": "utility", "x": 0.0, "y": 8.3, "w": 5.5, "d": 3.7, "area_sqm": 20.35},
        {"id": "RD-FE01", "code": "RD-FE01", "name": "Fire Escape Stairs 1", "type": "stairs", "x": 0.0, "y": 12.0, "w": 5.5, "d": 3.5},
        {"id": "RD-S02", "code": "RD-S02", "name": "Stair-2", "type": "stairs", "x": 12.500, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "DN"},
        {"id": "RD-FE02", "code": "RD-FE02", "name": "Fire Escape Stairs 2", "type": "stairs", "x": 0.0, "y": 55.5, "w": 5.5, "d": 5.0},
        {"id": "RD-FE03", "code": "RD-FE03", "name": "Fire Escape Stairs 3", "type": "stairs", "x": 48.501, "y": 57.300, "w": 7.500, "d": 3.200},
        {"id": "RD-S01", "code": "RD-S01", "name": "Stair-1", "type": "stairs", "x": 39.281, "y": 11.5, "w": 4.22, "d": 4.0, "area_sqm": 18.913, "direction": "DN"},
        {"id": "RD-FE04", "code": "RD-FE04", "name": "Fire Escape Stairs 4", "type": "stairs", "x": 48.221, "y": 15.3, "w": 7.780, "d": 3.2}
    ]
}

# Structural columns for vertical alignment across all floors
columns = []
for gx in building_specs["gridX"]:
    for gy in building_specs["gridY"]:
        columns.append({
            "gridX": gx["id"],
            "gridY": gy["id"],
            "x": gx["x"],
            "y": gy["y"],
            "size": 0.60
        })

floors_data = {
    "1F": floors_1f,
    "2F": floors_2f,
    "3F": floors_3f,
    "4F": floors_4f,
    "RD": floors_rd
}

# Add columns & calibration to each floor
for f_key in floors_data:
    floors_data[f_key]["columns"] = columns
    floors_data[f_key]["calibration"] = calibration.get(f_key, calibration["2F"])

js_content = f"""/**
 * NPC Campus Floorplans Master Geometry Dataset
 * Reconstructed directly from DPWH Architectural Reference Blueprints
 * Building Dimensions: 56.000m wide (Grids A-J) x 60.500m deep (Grids 1-9)
 */

window.NPC_BUILDING_SPECS = {json.dumps(building_specs, indent=2)};

window.NPC_FLOORS_DATA = {json.dumps(floors_data, indent=2)};

console.log('🏛️ NPC Floorplans Master Geometry loaded. Total floors:', Object.keys(window.NPC_FLOORS_DATA).length);
"""

out_path = os.path.join("assets", "js", "npc-floorplans-data.js")
with open(out_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print(f"Generated {out_path} with {len(floors_data)} floors.")
