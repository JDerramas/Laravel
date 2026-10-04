import json

with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8', errors='ignore') as f:
    js_content = f.read()

data_part = js_content.split('window.NPC_FLOORS_DATA = ', 1)[1].strip()
idx = data_part.rfind('};')
if idx != -1:
    data_part = data_part[:idx+1]
data = json.loads(data_part)

for floor, fdata in data.items():
    rooms = fdata.get('rooms', [])
    print(f"=== Floor {floor}: {len(rooms)} rooms ===")
    for r in rooms[:10]:
        rid = r.get("id", "")
        code = r.get("code", "")
        name = r.get("name", "")
        rtype = r.get("type", "")
        print(f"  ID: {rid:<16} Code: {code:<12} Name: {name:<32} Type: {rtype}")
    if len(rooms) > 10:
        print(f"  ... and {len(rooms) - 10} more rooms")
