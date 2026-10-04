<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <?php $PAGE_TITLE = 'NPC Connect - Admin Executive AI Assistant';
    include __DIR__ . '/../../includes/_head.php'; ?>
    <script id="npc-role-meta" type="application/json">
        <?= json_encode(['role' => $_SESSION['role'] ?? 'admin', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? 'Administrator']) ?>
    </script>
</head>

<body class="bg-background text-on-background font-body-md min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin';
    include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen">
        <!-- TopNavBar for Desktop Context -->
        <header class="hidden md:flex fixed top-0 left-0 lg:left-64 right-0 h-16 bg-surface dark:bg-inverse-surface border-b border-outline-variant dark:border-outline z-30 px-margin-desktop items-center justify-between">
            <div class="flex items-center gap-4 flex-1">
                <div class="relative w-96">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-surface-subtle border border-outline-variant rounded-full font-label-md text-label-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Search institutional records, courses..." type="text">
                </div>
            </div>
            <div class="flex items-center gap-6">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-container text-on-primary-container rounded-full text-xs font-semibold">
                    <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span>
                    Admin Console
                </span>
            </div>
        </header>

        <!-- Top Nav Mobile Replacement padding -->
        <div class="md:h-16"></div>

        <div class="p-margin-mobile md:p-margin-desktop max-w-container-max mx-auto h-full">
            <!-- NPC ADMIN AI ASSISTANT CHAT VIEW -->
            <div id="chatbot-view" class="view active">
                <div class="h-[calc(100vh-8rem)] flex overflow-hidden bg-surface border border-outline-variant rounded-2xl shadow-sm">
                    <!-- Conversation History Sidebar -->
                    <aside class="hidden md:flex flex-col w-72 bg-surface-subtle border-r border-outline-variant">
                        <div class="p-4 border-b border-outline-variant">
                            <button onclick="window.location.reload()" class="w-full flex items-center justify-center gap-2 bg-primary text-on-primary py-2.5 px-4 rounded-xl hover:bg-primary/90 transition-colors shadow-sm cursor-pointer">
                                <span class="material-symbols-outlined text-sm">add</span>
                                <span class="font-mono text-sm font-semibold">New Session</span>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-3 space-y-2">
                            <div class="px-2 py-1 text-on-surface-variant font-mono text-xs font-semibold opacity-70">Admin Quick Prompts</div>
                            <button onclick="setQuestionPrompt('What are the official guidelines for uploading documents into the knowledge base?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-primary text-[18px]">folder_open</span>
                                <span class="truncate font-medium">Knowledge Documents</span>
                            </button>
                            <button onclick="setQuestionPrompt('Explain the NPC grading scale and policies on incomplete grades.')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-secondary text-[18px]">grade</span>
                                <span class="truncate font-medium">Grading Policies</span>
                            </button>
                            <button onclick="setQuestionPrompt('How does the dynamic QR attendance verification work for classes?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-status-info text-[18px]">qr_code_scanner</span>
                                <span class="truncate font-medium">QR Attendance Audit</span>
                            </button>
                            <button onclick="setQuestionPrompt('What academic degree programs are currently offered by NPC?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-tertiary text-[18px]">school</span>
                                <span class="truncate font-medium">Degree Offerings</span>
                            </button>
                        </div>
                    </aside>

                    <!-- Active Chat Canvas -->
                    <section class="flex-1 flex flex-col bg-surface relative">
                        <!-- Chat Header -->
                        <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-lowest flex justify-between items-center shadow-sm z-10">
                            <div class="flex items-center gap-3">
                                <div id="admin-ai-core" class="npc-ai-core-wrapper w-12 h-12 shrink-0"></div>
                                <div>
                                    <h2 class="text-xl font-bold text-on-surface flex items-center gap-2">
                                        NPC Executive AI Assistant
                                        <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-primary/10 text-primary font-bold">Admin Mode</span>
                                    </h2>
                                    <p class="font-mono text-xs text-on-surface-variant font-medium">Institutional policy advisor and system management assistant</p>
                                </div>
                            </div>
                            <!-- Safety UI Indicator -->
                            <div class="flex items-center gap-2 bg-surface-container-high px-3 py-1.5 rounded-full border border-outline-variant">
                                <span class="material-symbols-outlined text-status-success" style="font-size: 18px;">verified_user</span>
                                <span class="font-mono text-xs font-semibold text-on-surface-variant">Admin Authorized</span>
                            </div>
                        </div>

                        <!-- Chat Messages Area -->
                        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-background">
                            <!-- Intro Message -->
                            <div class="flex justify-center my-2">
                                <div class="bg-surface-container-low px-4 py-1.5 rounded-full border border-outline-variant text-on-surface-variant font-mono text-xs font-semibold">
                                    Executive Session Active
                                </div>
                            </div>

                            <!-- Initial AI Message -->
                            <div class="flex flex-col items-start w-full">
                                <div class="flex gap-3 max-w-[90%] md:max-w-[80%]">
                                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-sm">
                                        <span class="material-symbols-outlined text-on-primary" style="font-size: 18px;">smart_toy</span>
                                    </div>
                                    <div class="bg-surface-container-lowest border border-outline-variant p-4 rounded-xl rounded-tl-none shadow-sm flex flex-col gap-3">
                                        <div class="text-base text-on-surface space-y-2 leading-relaxed">
                                            <p>Greetings, Administrator. I am your <strong>NPC Executive AI Assistant</strong>.</p>
                                            <p class="text-sm text-on-surface-variant">I can assist you with institutional document indexing, faculty course assignments, schedule auditing, grading policy interpretation, and campus guidelines.</p>
                                        </div>
                                    </div>
                                </div>
                                <span class="font-mono text-xs font-medium text-on-surface-variant mt-1 ml-11">Now</span>
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="p-4 md:p-6 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                            <div class="max-w-4xl mx-auto flex items-end gap-2 bg-surface-subtle border border-outline-variant rounded-2xl focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden pr-2 pl-4 py-2 shadow-inner transition-shadow">
                                <textarea id="chat-input" class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 resize-none text-base text-on-surface py-2 max-h-32 overflow-y-auto" placeholder="Ask about institutional policies, faculty schedules, or document indexing..." rows="1" style="min-height: 40px;"></textarea>
                                <div class="flex items-center gap-1 pb-1">
                                    <button id="chat-send-btn" aria-label="Send message" onclick="sendMessage()" class="text-white bg-primary p-2 hover:bg-primary/90 rounded-full transition-colors flex items-center justify-center shadow-md cursor-pointer">
                                        <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                    </button>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <p class="font-mono text-[11px] text-on-surface-variant/70">Institutional data is processed in accordance with NPC academic and data privacy regulations.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../../assets/js/app.js') ? filemtime(__DIR__ . '/../../assets/js/app.js') : (file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : 1) ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            try {
                if (window.npcThree && typeof window.npcThree.initAiCore === 'function') {
                    window.aiCore3D = window.npcThree.initAiCore('admin-ai-core', { size: 48 });
                }
            } catch (e) {
                console.error("Admin 3D AI Core init error:", e);
            }
        });
    </script>
</body>

</html>