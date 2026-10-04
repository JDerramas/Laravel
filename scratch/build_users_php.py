import os, json, re

p = r'C:\Users\Lovi\.gemini\antigravity-ide\brain\e658c01b-c4a9-4181-8ce6-345f32a01e89\.system_generated\logs\transcript_full.jsonl'
users_lines = {}
with open(p, 'r', encoding='utf-8', errors='ignore') as fp:
    for line in fp:
        if 'VIEW_FILE' in line and 'users.php' in line:
            d = json.loads(line)
            cnt = d.get('content', '')
            if 'admin/security/users.php' in cnt:
                for row in cnt.splitlines():
                    m = re.match(r'^(\d+): (.*)$', row)
                    if m:
                        users_lines[int(m.group(1))] = m.group(2)

print('Lines in users_lines:', len(users_lines))

# CSV Import code between 685 and 720:
csv_block = """        document.getElementById('import-csv-file')?.addEventListener('change', function () {
            const file = this.files[0];
            if (!file || typeof Papa === 'undefined') return;
            Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                complete: function (res) {
                    const prev = document.getElementById('import-preview');
                    const run = document.getElementById('btn-import-run');
                    const fields = res.meta.fields || [];
                    const req = ['full_name','email_prefix'];
                    const missing = req.filter(f => !fields.includes(f));
                    if (missing.length) {
                        prev.classList.remove('hidden');
                        prev.innerHTML = '<span class="text-error font-bold">Missing columns: ' + missing.join(', ') + '</span>';
                        run.disabled = true;
                        return;
                    }
                    pendingRows = res.data
                        .map(r => ({
                            full_name: String(r.full_name || '').trim(),
                            email: String(r.email_prefix || '').trim().toLowerCase() + '@navotaspolytechniccollege.edu.ph',
                            number: String(r.number || '').trim().toUpperCase(),
                            role: ['student','teacher','admin'].includes(String(r.role||'').trim().toLowerCase()) ? String(r.role).trim().toLowerCase() : 'student',
                            program: String(r.program || '').trim(),
                            section: String(r.section || '').trim()
                        }))
                        .filter(r => r.full_name && /^[a-z0-9._-]+@[a-z]/i.test(r.email));
                    prev.classList.remove('hidden');
                    prev.innerHTML ="""

lines_to_add = csv_block.splitlines()
start_line = 686
for idx, l in enumerate(lines_to_add):
    users_lines[start_line + idx] = l

max_l = max(users_lines.keys())
for i in range(1, max_l + 1):
    if i not in users_lines:
        users_lines[i] = ""

full_text = '\n'.join(users_lines[i] for i in range(1, max_l + 1)) + '\n'

with open(r'd:\xampp\htdocs\llama-b10483-bin-win-cpu-x64\LocalAI\app\admin\security\users.php', 'w', encoding='utf-8') as out:
    out.write(full_text)

print('Wrote users.php successfully! Total lines:', max_l)
