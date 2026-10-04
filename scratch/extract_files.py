import os, json, re

brains = r'C:\Users\Lovi\.gemini\antigravity-ide\brain'

users_views = []
admin_views = []

for root, dirs, files in os.walk(brains):
    for f in files:
        if f == 'transcript_full.jsonl':
            p = os.path.join(root, f)
            with open(p, 'r', encoding='utf-8', errors='ignore') as fp:
                for line in fp:
                    if 'VIEW_FILE' in line:
                        try:
                            d = json.loads(line)
                            cnt = d.get('content', '')
                            if 'users.php' in cnt and 'Showing lines' in cnt:
                                users_views.append((p, cnt))
                            if 'api/admin.php' in cnt and 'Showing lines' in cnt:
                                admin_views.append((p, cnt))
                        except Exception as e:
                            pass

print(f"Found {len(users_views)} users.php views, {len(admin_views)} admin.php views")
for p, cnt in users_views:
    m = re.search(r'Showing lines (\d+) to (\d+)', cnt)
    if m:
        print("users.php view:", m.group(1), "to", m.group(2), "in", os.path.basename(os.path.dirname(os.path.dirname(p))))

for p, cnt in admin_views:
    m = re.search(r'Showing lines (\d+) to (\d+)', cnt)
    if m:
        print("admin.php view:", m.group(1), "to", m.group(2), "in", os.path.basename(os.path.dirname(os.path.dirname(p))))
