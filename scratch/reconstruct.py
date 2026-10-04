import os, json, re

brains = r'C:\Users\Lovi\.gemini\antigravity-ide\brain'

users_lines = {}
admin_lines = {}

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
                            is_users = 'users.php`' in cnt
                            is_admin = 'api/admin.php`' in cnt
                            if not is_users and not is_admin:
                                continue
                            
                            # parse lines format: "123: content"
                            for row in cnt.splitlines():
                                m = re.match(r'^(\d+): (.*)$', row)
                                if m:
                                    line_no = int(m.group(1))
                                    line_text = m.group(2)
                                    if is_users:
                                        users_lines[line_no] = line_text
                                    if is_admin:
                                        admin_lines[line_no] = line_text
                        except Exception as e:
                            pass

print(f"Users lines found: {len(users_lines)}")
if users_lines:
    missing_u = [i for i in range(1, max(users_lines.keys()) + 1) if i not in users_lines]
    print(f"Users max line: {max(users_lines.keys())}, missing: {len(missing_u)} -> {missing_u[:20]}")

print(f"Admin lines found: {len(admin_lines)}")
if admin_lines:
    missing_a = [i for i in range(1, max(admin_lines.keys()) + 1) if i not in admin_lines]
    print(f"Admin max line: {max(admin_lines.keys())}, missing: {len(missing_a)} -> {missing_a[:20]}")
