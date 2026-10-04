import sys, os
sys.path.insert(0, os.path.abspath('.'))
import generate_floorplans_data as gfd

for floor in ['2F', '3F', '4F', 'RD']:
    data = gfd.floors_data[floor]
    print('=== ' + floor + ' ===')
    rooms_by_id = {r['id']: r for r in data['rooms']}
    for s_id in [floor+'-S01', floor+'-S02', floor+'-S04', floor+'-FE01', floor+'-FE02', floor+'-FE04']:
        if s_id in rooms_by_id:
            r = rooms_by_id[s_id]
            print(f'  {s_id}: x={r.get("x")}, y={r.get("y")}, w={r.get("w")}, d={r.get("d")}')
    for t_id in [floor+'-T01', floor+'-T02', floor+'-T03', floor+'-T04', floor+'-T05']:
        if t_id in rooms_by_id:
            r = rooms_by_id[t_id]
            print(f'  {t_id}: x={r.get("x")}, y={r.get("y")}, w={r.get("w")}, d={r.get("d")}, poly={bool(r.get("polygon"))}')
