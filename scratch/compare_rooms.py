import re
import json

# Parse api/campus_map.php
with open('api/campus_map.php', 'r', encoding='utf-8') as f:
    api_content = f.read()

# Match rooms in api/campus_map.php
api_rooms_matches = re.findall(r"'id'\s*=>\s*'([^']+)'", api_content)
# Filter only room IDs (not '1F', '2F', etc. from floors)
api_room_ids = [r for r in api_rooms_matches if r not in ['1F', '2F', '3F', '4F', 'RD']]

# Parse npc-floorplans-data.js
with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8', errors='ignore') as f:
    js_content = f.read()

# Match room IDs in js
js_room_ids = re.findall(r'"id":\s*"([^"]+)"', js_content)
# Filter only room IDs (exclude grids A-J, 1-9)
js_room_ids = [r for r in js_room_ids if not (len(r) == 1 and (r in 'ABCDEFGHIJ' or r in '123456789'))]

print(f"API Room IDs count: {len(api_room_ids)}")
print(f"JS Room IDs count: {len(js_room_ids)}")

api_set = set(api_room_ids)
js_set = set(js_room_ids)

common = api_set.intersection(js_set)
only_in_api = api_set - js_set
only_in_js = js_set - api_set

print(f"Common room IDs: {len(common)}")
print(f"Only in API: {len(only_in_api)}")
print(f"Only in JS: {len(only_in_js)}")

print("\nSample only in API (first 20):", sorted(list(only_in_api))[:20])
print("\nSample only in JS (first 20):", sorted(list(only_in_js))[:20])
