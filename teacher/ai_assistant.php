<?php
require_once __DIR__ . '/../includes/auth.php';
require_teacher();
$teacher_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Faculty Professor';
$teacher_initial = strtoupper(substr($teacher_name, 0, 1));
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <?php $PAGE_TITLE = 'Faculty AI Assistant - NPC Connect'; include __DIR__ . '/../includes/_head.php'; ?>
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'teacher', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? 'Faculty Professor']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'faculty'; include __DIR__ . '/../includes/_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col overflow-x-hidden">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-2 sm:gap-4">
                <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC Faculty</span>
                <h2 class="text-xl font-bold text-primary hidden lg:block">Faculty AI Assistant</h2>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="/teacher/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px]">map</span>
                    <span class="hidden sm:inline">NPC Map</span>
                </a>
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0">
                    <?php echo htmlspecialchars($teacher_initial); ?>
                </div>
                <span class="text-sm font-semibold text-primary hidden md:inline"><?php echo htmlspecialchars($teacher_name); ?></span>
            </div>
        </header>

        <!-- Canvas -->
        <div class="p-3.5 sm:p-6 md:p-10 max-w-5xl w-full mx-auto space-y-6 flex-1 flex flex-col">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
                <div class="flex items-center gap-4">
                    <div id="faculty-ai-core" class="npc-ai-core-wrapper w-14 h-14 shrink-0"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-primary tracking-tight">AI Teaching & Course Assistant</h1>
                        <p class="text-xs text-on-surface-variant mt-0.5">Generate quiz questions, outline lesson plans, or check institutional academic guidelines.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-xs font-semibold">
                        <span class="material-symbols-outlined text-[14px]">psychology</span>
                        Faculty AI Mode
                    </span>
                </div>
            </div>

            <!-- Suggested Quick Chips -->
            <div class="flex flex-wrap gap-2">
                <button onclick="setPrompt('Generate a 5-item multiple choice quiz for Database Management with answer key.')" class="bg-surface-container-low hover:bg-surface-container text-primary text-xs font-semibold px-3.5 py-2 rounded-xl border border-outline-variant/60 transition-colors shadow-sm cursor-pointer">
                    📝 Generate 5-Item Quiz
                </button>
                <button onclick="setPrompt('Create a 1-hour lesson outline for Web Systems and Technologies.')" class="bg-surface-container-low hover:bg-surface-container text-primary text-xs font-semibold px-3.5 py-2 rounded-xl border border-outline-variant/60 transition-colors shadow-sm cursor-pointer">
                    💡 1-Hour Lesson Plan
                </button>
                <button onclick="setPrompt('What is the standard NPC grading scale (1.00 to 5.00) and computation rules?')" class="bg-surface-container-low hover:bg-surface-container text-primary text-xs font-semibold px-3.5 py-2 rounded-xl border border-outline-variant/60 transition-colors shadow-sm cursor-pointer">
                    📊 Grading Scale Breakdown
                </button>
                <button onclick="setPrompt('What are the official attendance policies for dynamic QR codes and manual overrides?')" class="bg-surface-container-low hover:bg-surface-container text-primary text-xs font-semibold px-3.5 py-2 rounded-xl border border-outline-variant/60 transition-colors shadow-sm cursor-pointer">
                    📋 QR Attendance Rules
                </button>
            </div>

            <!-- Chat Window -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex-1 flex flex-col overflow-hidden min-h-[500px]">
                <div id="teacher-chat-box" class="flex-1 p-6 overflow-y-auto space-y-4 bg-background">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                        </div>
                        <div class="bg-surface-container-low p-4 rounded-2xl max-w-xl text-xs leading-relaxed text-on-surface border border-outline-variant/40 shadow-sm">
                            Hello Professor <?php echo htmlspecialchars(explode(' ', trim($teacher_name))[0]); ?>! How can I assist you with your course syllabus, lesson plans, quiz creation, or grading computations today?
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-outline-variant/40 bg-surface-container-lowest flex gap-3">
                    <input type="text" id="teacher-chat-input" onkeypress="if(event.key==='Enter') sendTeacherMessage()" placeholder="Ask AI to create quiz questions, draft lesson outlines, or explain grading rules..." class="flex-1 px-4 py-2.5 bg-surface-subtle border border-outline-variant rounded-xl text-xs focus:outline-none focus:border-primary">
                    <button onclick="sendTeacherMessage()" id="teacher-send-btn" class="px-5 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 flex items-center gap-1 shadow-sm transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">send</span> Send
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function formatAiMessage(text) {
            if (!text) return '';
            let escaped = escapeHtml(text);
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
            escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-surface-subtle px-1.5 py-0.5 rounded font-mono text-[11px]">$1</code>');
            escaped = escaped.replace(/\n/g, '<br>');
            return escaped;
        }

        function setPrompt(text) {
            document.getElementById('teacher-chat-input').value = text;
            sendTeacherMessage();
        }

        async function sendTeacherMessage() {
            const input = document.getElementById('teacher-chat-input');
            const chatBox = document.getElementById('teacher-chat-box');
            const msg = input.value.trim();
            if (!msg) return;

            // Append User Message
            chatBox.innerHTML += `
                <div class="flex items-start justify-end gap-3">
                    <div class="bg-primary text-on-primary p-3.5 rounded-2xl max-w-lg text-xs leading-relaxed shadow-sm">
                        ${escapeHtml(msg)}
                    </div>
                </div>
            `;
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;
            saveTeacherChat();

            // Append Typing Indicator
            const loadingId = 'loading-' + Date.now();
            chatBox.innerHTML += `
                <div id="${loadingId}" class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                        <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                    </div>
                    <div class="bg-surface-container-low p-3.5 rounded-2xl text-xs text-on-surface-variant flex items-center gap-1.5 border border-outline-variant/30">
                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                    </div>
                </div>
            `;
            chatBox.scrollTop = chatBox.scrollHeight;

            // 3D AI Core Thinking Mode
            if (window.aiCore3D && typeof window.aiCore3D.setMode === 'function') {
                window.aiCore3D.setMode('thinking');
            }

            try {
                const headers = { 'Content-Type': 'application/json' };
                if (window.__CSRF__) headers['X-CSRF-Token'] = window.__CSRF__;

                const payload = { question: msg, role: 'faculty' };
                if (window.__CSRF__) payload.csrf_token = window.__CSRF__;

                const fetchFn = typeof window.api === 'function' ? window.api : fetch;
                const res = await fetchFn('/api/ask.php', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (!res.ok) throw new Error(data.message || data.detail || 'Error getting response');
                
                const reply = data.answer || '';
                let sourcesHtml = '';
                if (data.sources && data.sources.length > 0) {
                    sourcesHtml = `
                        <div class="mt-2 pt-2 border-t border-outline-variant/30 flex items-start gap-1.5 text-[10px] text-on-surface-variant font-mono">
                            <span class="material-symbols-outlined text-[13px] text-status-info shrink-0 mt-0.5">menu_book</span>
                            <span>Sources: ${data.sources.map(s => escapeHtml(s.file)).join(', ')}</span>
                        </div>
                    `;
                }

                // 3D AI Core Speaking Mode
                if (window.aiCore3D && typeof window.aiCore3D.setMode === 'function') {
                    window.aiCore3D.setMode('speaking');
                    setTimeout(() => {
                        if (window.aiCore3D && typeof window.aiCore3D.setMode === 'function') {
                            window.aiCore3D.setMode('idle');
                        }
                    }, 3500);
                }

                const loadingEl = document.getElementById(loadingId);
                if (loadingEl) {
                    if (data.warning || data.banned) {
                        const isBan = !!data.banned;
                        loadingEl.innerHTML = `
                            <div class="w-8 h-8 rounded-full ${isBan ? 'bg-error text-on-error' : 'bg-status-warning text-on-warning'} flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">${isBan ? 'gpp_bad' : 'warning'}</span>
                            </div>
                            <div class="${isBan ? 'bg-error-container border-error/40 text-error' : 'bg-status-warning/10 border-status-warning/40 text-on-surface'} p-4 rounded-2xl max-w-xl text-xs leading-relaxed shadow-sm border">
                                <div class="font-mono text-[10px] uppercase tracking-wider font-bold mb-1.5 flex items-center gap-1.5 ${isBan ? 'text-error' : 'text-status-warning'}">
                                    <span class="material-symbols-outlined text-[14px]">${isBan ? 'block' : 'security'}</span>
                                    ${isBan ? 'AI ACCESS SUSPENDED · 5-DAY LOCKOUT' : `POLICY WARNING · STRIKE ${data.strike || 1} OF 3`}
                                </div>
                                <div class="leading-relaxed whitespace-pre-line">${formatAiMessage(reply)}</div>
                            </div>
                        `;
                        if (isBan) {
                            input.disabled = true;
                            input.placeholder = "AI Assistant access suspended for 5 days.";
                        }
                    } else {
                        loadingEl.innerHTML = `
                            <div class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                            </div>
                            <div class="bg-surface-container-low p-4 rounded-2xl max-w-xl text-xs leading-relaxed text-on-surface shadow-sm border border-outline-variant/30">
                                <div>${formatAiMessage(reply)}</div>
                                ${sourcesHtml}
                            </div>
                        `;
                    }
                    saveTeacherChat();
                }
            } catch (err) {
                if (window.aiCore3D && typeof window.aiCore3D.setMode === 'function') {
                    window.aiCore3D.setMode('idle');
                }
                const loadingEl = document.getElementById(loadingId);
                if (loadingEl) {
                    loadingEl.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-error text-on-error flex items-center justify-center font-bold text-xs shrink-0">
                            <span class="material-symbols-outlined text-[16px]">error</span>
                        </div>
                        <div class="bg-error-container text-error p-3.5 rounded-2xl max-w-lg text-xs leading-relaxed border border-error/30 font-medium">
                            ${escapeHtml(err.message || 'Error communicating with AI service.')}
                        </div>
                    `;
                    saveTeacherChat();
                }
            }
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        const TEACHER_STORAGE_KEY = 'npc_teacher_chat_history';

        function saveTeacherChat() {
            try {
                const box = document.getElementById('teacher-chat-box');
                if (box) localStorage.setItem(TEACHER_STORAGE_KEY, box.innerHTML);
            } catch (e) {}
        }

        function restoreTeacherChat() {
            try {
                const saved = localStorage.getItem(TEACHER_STORAGE_KEY);
                const box = document.getElementById('teacher-chat-box');
                if (saved && saved.trim() && box) {
                    box.innerHTML = saved;
                    box.scrollTop = box.scrollHeight;
                }
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            restoreTeacherChat();
            try {
                if (window.npcThree && typeof window.npcThree.initAiCore === 'function') {
                    window.aiCore3D = window.npcThree.initAiCore('faculty-ai-core', { size: 52 });
                }
            } catch (e) {
                console.error("3D AI Core init error:", e);
            }
        });
    </script>
</body>
</html>
