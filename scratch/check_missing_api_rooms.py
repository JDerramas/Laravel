import json
import re

# Load NPC_FLOORS_DATA
with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8', errors='ignore') as f:
    js_content = f.read()

data_part = js_content.split('window.NPC_FLOORS_DATA = ', 1)[1].strip()
idx = data_part.rfind('};')
if idx != -1:
    data_part = data_part[:idx+1]
floors_data = json.loads(data_part)

# Load api/campus_map.php existing room IDs
with open('api/campus_map.php', 'r', encoding='utf-8') as f:
    api_content = f.read()

api_room_ids = set(re.findall(r"'id'\s*=>\s*'([^']+)'", api_content))

missing_rooms = []
for floor, fdata in floors_data.items():
    for r in fdata.get('rooms', []):
        rid = r.get('id')
        if rid not in api_room_ids:
            missing_rooms.append((floor, r))

print(f"Total missing rooms in API: {len(missing_rooms)}")
for f, r in missing_rooms[:10]:
    print(f"  Floor {f}: {r.get('id')} - {r.get('name')}")
