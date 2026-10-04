import os

root_dir = r"d:\xampp\htdocs\llama-b10483-bin-win-cpu-x64\LocalAI\app"
skip_dirs = {'node_modules', '.git', '.obsidian', 'scratch', 'backups', '__pycache__', '.agents'}

tree = {}
for root, dirs, files in os.walk(root_dir):
    dirs[:] = [d for d in dirs if d not in skip_dirs]
    rel_path = os.path.relpath(root, root_dir)
    tree[rel_path] = files

for folder, files in sorted(tree.items()):
    prefix = "" if folder == "." else f"[{folder}]"
    print(prefix)
    for f in sorted(files):
        print(f"  - {f}")
