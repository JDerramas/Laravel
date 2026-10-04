import json

with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8') as f:
    content = f.read()

floors_str = content.split('window.NPC_FLOORS_DATA = ')[1].split(';\n\nconsole.log')[0]
floors = json.loads(floors_str)

for f_key in ['2F', '3F', '4F', 'RD']:
    print(f"\n=================== FLOOR {f_key} ===================")
    rooms = floors[f_key]['rooms']
    for r in rooms:
        poly_str = " (POLYGON)" if "polygon" in r else ""
        print(f"  {r['id']:<12} | {r['name']:<35} | type={r['type']:<9} | x={r.get('x', 0):>6.2f}, y={r.get('y', 0):>6.2f}, w={r.get('w', 0):>5.2f}, d={r.get('d', 0):>5.2f}{poly_str}")
