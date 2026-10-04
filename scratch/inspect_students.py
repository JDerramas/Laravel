import re

with open(r'd:\xampp\htdocs\llama-b10483-bin-win-cpu-x64\LocalAI\app\admin\academic\students.php', encoding='utf-8', errors='ignore') as f:
    text = f.read()

print('Length:', len(text))
funcs = re.findall(r'function\s+([a-zA-Z0-9_]+)', text)
print('Functions:', funcs)

for line_no, line in enumerate(text.splitlines(), 1):
    if any(k in line.lower() for k in ['edit', 'modal', 'filter', 'sort', 'program', 'section', 'action']):
        if line_no < 350 or line_no > 500:
            print(f'{line_no}: {line.strip()[:100]}')
