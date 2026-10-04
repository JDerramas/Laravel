import json

with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8') as f:
    content = f.read()

specs_str = content.split('window.NPC_BUILDING_SPECS = ')[1].split(';\n\nwindow.NPC_FLOORS_DATA = ')[0]
floors_str = content.split('window.NPC_FLOORS_DATA = ')[1].split(';\n\nconsole.log')[0]

specs = json.loads(specs_str)
floors = json.loads(floors_str)

print('=== BUILDING SPECS GRID X ===')
for g in specs['gridX']:
    print(f"Grid {g['id']}: x = {g['x']:.3f}")

print('\n=== BUILDING SPECS GRID Y ===')
for g in specs['gridY']:
    print(f"Grid {g['id']}: y = {g['y']:.3f}")

print('\n=== FLOOR EXTENTS ===')
for f_key in ['1F', '2F', '3F', '4F', 'RD']:
    f = floors[f_key]
    min_x = min(r.get('x', 0) for r in f['rooms'])
    max_x = max(r.get('x', 0) + r.get('w', 0) for r in f['rooms'])
    min_y = min(r.get('y', 0) for r in f['rooms'])
    max_y = max(r.get('y', 0) + r.get('d', 0) for r in f['rooms'])
    print(f"{f_key}: x in [{min_x:.3f}, {max_x:.3f}], y in [{min_y:.3f}, {max_y:.3f}], rooms={len(f['rooms'])}")
