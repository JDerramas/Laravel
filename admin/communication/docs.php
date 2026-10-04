<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Document Hub - NPC Connect Admin</title>
    <script>
        /* Pre-paint theme: apply saved night-mode before first paint (no flash) */
        (function () {
            try {
                var t = localStorage.getItem('npc-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/npc.js"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "rgb(var(--primary-rgb) / <alpha-value>)",
                        "primary-container": "rgb(var(--primary-container-rgb) / <alpha-value>)",
                        "on-primary": "rgb(var(--on-primary-rgb) / <alpha-value>)",
                        "on-primary-container": "rgb(var(--on-primary-container-rgb) / <alpha-value>)",
                        "primary-fixed": "rgb(var(--primary-fixed-rgb) / <alpha-value>)",
                        "primary-fixed-dim": "rgb(var(--primary-fixed-dim-rgb) / <alpha-value>)",
                        "on-primary-fixed": "rgb(var(--on-primary-fixed-rgb) / <alpha-value>)",
                        "on-primary-fixed-variant": "rgb(var(--on-primary-fixed-variant-rgb) / <alpha-value>)",
                        "secondary": "rgb(var(--secondary-rgb) / <alpha-value>)",
                        "secondary-container": "rgb(var(--secondary-container-rgb) / <alpha-value>)",
                        "on-secondary": "rgb(var(--on-secondary-rgb) / <alpha-value>)",
                        "on-secondary-container": "rgb(var(--on-secondary-container-rgb) / <alpha-value>)",
                        "secondary-fixed": "rgb(var(--secondary-fixed-rgb) / <alpha-value>)",
                        "secondary-fixed-dim": "rgb(var(--secondary-fixed-dim-rgb) / <alpha-value>)",
                        "on-secondary-fixed": "rgb(var(--on-secondary-fixed-rgb) / <alpha-value>)",
                        "on-secondary-fixed-variant": "rgb(var(--on-secondary-fixed-variant-rgb) / <alpha-value>)",
                        "npc-gold-muted": "rgb(var(--npc-gold-muted-rgb) / <alpha-value>)",
                        "tertiary": "rgb(var(--tertiary-rgb) / <alpha-value>)",
                        "on-tertiary": "rgb(var(--on-tertiary-rgb) / <alpha-value>)",
                        "tertiary-container": "rgb(var(--tertiary-container-rgb) / <alpha-value>)",
                        "tertiary-fixed": "rgb(var(--tertiary-fixed-rgb) / <alpha-value>)",
                        "tertiary-fixed-dim": "rgb(var(--tertiary-fixed-dim-rgb) / <alpha-value>)",
                        "on-tertiary-fixed": "rgb(var(--on-tertiary-fixed-rgb) / <alpha-value>)",
                        "on-tertiary-fixed-variant": "rgb(var(--on-tertiary-fixed-variant-rgb) / <alpha-value>)",
                        "background": "rgb(var(--background-rgb) / <alpha-value>)",
                        "surface": "rgb(var(--surface-rgb) / <alpha-value>)",
                        "surface-subtle": "rgb(var(--surface-subtle-rgb) / <alpha-value>)",
                        "surface-bright": "rgb(var(--surface-bright-rgb) / <alpha-value>)",
                        "surface-dim": "rgb(var(--surface-dim-rgb) / <alpha-value>)",
                        "surface-tint": "rgb(var(--surface-tint-rgb) / <alpha-value>)",
                        "surface-variant": "rgb(var(--surface-variant-rgb) / <alpha-value>)",
                        "surface-container-lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
                        "surface-container-low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
                        "surface-container": "rgb(var(--surface-container-rgb) / <alpha-value>)",
                        "surface-container-high": "rgb(var(--surface-container-high-rgb) / <alpha-value>)",
                        "surface-container-highest": "rgb(var(--surface-container-highest-rgb) / <alpha-value>)",
                        "on-surface": "rgb(var(--on-surface-rgb) / <alpha-value>)",
                        "on-surface-variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
                        "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
                        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
                        "status-info": "rgb(var(--status-info-rgb) / <alpha-value>)",
                        "status-success": "rgb(var(--status-success-rgb) / <alpha-value>)",
                        "status-warning": "rgb(var(--status-warning-rgb) / <alpha-value>)",
                        "error": "rgb(var(--error-rgb) / <alpha-value>)",
                        "on-error": "rgb(var(--on-error-rgb) / <alpha-value>)",
                        "error-container": "rgb(var(--error-container-rgb) / <alpha-value>)",
                        "on-error-container": "rgb(var(--on-error-container-rgb) / <alpha-value>)",
                        "inverse-surface": "rgb(var(--inverse-surface-rgb) / <alpha-value>)",
                        "inverse-on-surface": "rgb(var(--inverse-on-surface-rgb) / <alpha-value>)",
                        "inverse-primary": "rgb(var(--inverse-primary-rgb) / <alpha-value>)",
                        "excel-green": "rgb(var(--excel-green-rgb) / <alpha-value>)",
                        "excel-dark": "rgb(var(--excel-dark-rgb) / <alpha-value>)",
                        "excel-light": "rgb(var(--excel-light-rgb) / <alpha-value>)",
                        "excel-border": "rgb(var(--excel-border-rgb) / <alpha-value>)"
                    },
                    fontFamily: {
                        "sans": ["Geist", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
        <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Document Repository</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-sm shadow-sm npc-navy-card">
                        <?php echo htmlspecialchars($admin_initial); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-6 md:p-10 max-w-7xl w-full mx-auto space-y-8 flex-1">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-outline-variant/60 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-primary tracking-tight">Institutional Knowledge Base</h1>
                    <p class="text-sm text-on-surface-variant mt-1">Uploaded files are automatically saved directly into the AI chatbot's knowledge folder (<code class="font-mono bg-surface-container px-1.5 py-0.5 rounded text-xs">/documents</code>).</p>
                </div>
            </div>

            <!-- Upload Area Section -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant p-8 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">cloud_upload</span>
                        Upload Knowledge File
                    </h2>
                    <span class="text-xs font-mono text-on-surface-variant bg-surface-container px-2.5 py-1 rounded-lg">Supported: PDF, DOCX, TXT, XLSX</span>
                </div>

                <form id="upload-form" class="space-y-4">
                    <div id="drop-zone" class="border-2 border-dashed border-outline-variant hover:border-primary rounded-2xl p-8 text-center cursor-pointer transition-colors bg-surface-subtle flex flex-col items-center justify-center min-h-[160px]" onclick="document.getElementById('file-input').click()">
                        <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary flex items-center justify-center mb-3 shadow-sm npc-navy-card">
                            <span class="material-symbols-outlined text-[24px]">upload_file</span>
                        </div>
                        <p class="text-sm font-bold text-primary mb-0.5" id="file-name-display">Click to browse or drop file here</p>
                        <p class="text-xs text-on-surface-variant">Files are processed instantly for AI assistant grounding</p>
                        <input type="file" id="file-input" class="hidden" accept=".pdf,.docx,.txt,.xlsx,.csv" onchange="handleFileSelected(event)">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Document Title (Optional)</label>
                            <input type="text" id="doc-title-input" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary" placeholder="e.g. NPC Student Handbook 2026">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Category</label>
                            <select id="doc-category-input" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary">
                                <option value="Handbook">Student Handbook / Policies</option>
                                <option value="Academic">Academic Guidelines & Syllabus</option>
                                <option value="Forms">Administrative Forms</option>
                                <option value="FAQ">Campus FAQs & Directory</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" id="submit-upload-btn" class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity flex items-center gap-2 shadow-sm disabled:opacity-50 npc-navy-card">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                            Save & Sync to AI Chatbot
                        </button>
                    </div>
                </form>
            </div>

            <!-- Documents Table Card -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-subtle">
                    <div>
                        <h3 class="font-bold text-primary text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">folder_open</span>
                            Indexed Documents
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Files currently available to students and the NPC AI chatbot</p>
                    </div>
                    <button onclick="loadDocs()" class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-primary rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">refresh</span> Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface font-mono text-xs uppercase tracking-wider">
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Document Title & File</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Category</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">File Size</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant">Status</th>
                                <th class="py-3 px-6 font-semibold border-b border-outline-variant text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="docs-tbody" class="divide-y divide-outline-variant/30 text-sm">
                            <tr>
                                <td colspan="5" class="p-8 text-center text-on-surface-variant"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
        const csrfToken = <?= json_encode($csrf_token) ?>;

        let selectedFile = null;

        function handleFileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                selectedFile = file;
                document.getElementById('file-name-display').innerText = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                if (!document.getElementById('doc-title-input').value) {
                    document.getElementById('doc-title-input').value = file.name.replace(/\.[^/.]+$/, "");
                }
            }
        }

        // Drag & Drop handlers
        const dropZone = document.getElementById('drop-zone');
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary', 'bg-surface-container-low');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-primary', 'bg-surface-container-low');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-surface-container-low');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                selectedFile = file;
                document.getElementById('file-name-display').innerText = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                if (!document.getElementById('doc-title-input').value) {
                    document.getElementById('doc-title-input').value = file.name.replace(/\.[^/.]+$/, "");
                }
            }
        });

        document.getElementById('upload-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!selectedFile) {
                return alert('Please select a file to upload.');
            }

            const submitBtn = document.getElementById('submit-upload-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span> Uploading & Indexing...';

            try {
                const title = document.getElementById('doc-title-input').value.trim() || selectedFile.name;
                const category = document.getElementById('doc-category-input').value;

                // Single secured upload endpoint: saves file AND indexes it in the DB server-side
                const formData = new FormData();
                formData.append('file', selectedFile);
                formData.append('title', title);
                formData.append('category', category);
                formData.append('csrf_token', csrfToken);

                const res = await fetch('/api/upload_document.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });
                const result = await res.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to upload document');
                }

                alert('Document successfully uploaded to AI chatbot folder and database!');
                selectedFile = null;
                document.getElementById('file-input').value = '';
                document.getElementById('file-name-display').innerText = 'Click to browse or drop file here';
                document.getElementById('doc-title-input').value = '';
                loadDocs();

            } catch (err) {
                alert('Upload failed: ' + err.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">add_circle</span> Save & Sync to AI Chatbot';
            }
        });

        async function loadDocs() {
            const tbody = document.getElementById('docs-tbody');
            try {
                const { data, error } = await supabaseClient.from('documents').select('*').order('created_at', { ascending: false });
                if (error) throw error;

                if (!data || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="p-8 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[36px] text-outline-variant mb-2 block">folder_open</span>
                                <p class="font-semibold">No documents uploaded yet</p>
                                <p class="text-xs text-on-surface-variant mt-1">Upload PDF, DOCX, or text files above to ground the AI Chatbot.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = data.map(d => `
                    <tr class="hover:bg-surface-subtle transition-colors">
                        <td class="py-4 px-6 font-semibold text-primary flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-surface-container text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">description</span>
                            </div>
                            <div>
                                <p class="font-bold text-sm text-primary">${d.title}</p>
                                <p class="text-xs font-mono text-on-surface-variant">${d.file_url || 'file'}</p>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-on-surface-variant text-xs font-medium">${d.category || 'General'}</td>
                        <td class="py-4 px-6 font-mono text-xs text-on-surface-variant">${d.file_size || '1.0 MB'}</td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-status-success/15 text-status-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-status-success"></span> AI Indexed
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <button onclick="deleteDoc('${d.id}', '${d.file_url}')" class="text-error hover:underline text-xs flex items-center gap-1 font-semibold ml-auto">
                                <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                            </button>
                        </td>
                    </tr>
                `).join('');

            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-error">Failed to load documents from database.</td></tr>';
            }
        }

        async function deleteDoc(id, filename) {
            if (!await npcConfirm({
                title: 'Remove Document',
                message: 'Are you sure you want to remove this document from the AI Knowledge Base?',
                type: 'danger',
                confirmText: 'Remove Document'
            })) return;

            try {
                // Single secured endpoint: removes the file AND the DB record server-side
                const res = await fetch('/api/delete_document.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id: id, filename: filename, csrf_token: csrfToken })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Delete failed');
            } catch (err) {
                alert('Error: ' + err.message);
            }
            loadDocs();
        }

        loadDocs();
    </script>
</body>
</html>
