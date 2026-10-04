<?php
require_once __DIR__ . '/../includes/auth.php';
require_student_area();
$is_logged_in = isset($_SESSION['user_id']);
$raw_name = (isset($_SESSION['name']) && $_SESSION['name'] !== null) ? (string)$_SESSION['name'] : 'Guest User';
$user_name = $is_logged_in ? explode(' ', trim($raw_name))[0] : 'Guest';
$full_name = $is_logged_in ? (string)$_SESSION['name'] : 'Guest User';
$user_id_display = $is_logged_in && isset($_SESSION['student_number']) ? (string)$_SESSION['student_number'] : 'GUEST';
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <?php $PAGE_TITLE = 'Student AI Assistant - NPC Connect'; include __DIR__ . '/../includes/_head.php'; ?>
    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../includes/_denied_banner.php'; ?>

    <!-- App Container -->
    <div class="flex min-h-screen w-full" id="app-root">

        <!-- SideNavBar (Sticky Desktop navigation) -->
        <?php $NPC_PORTAL = 'student'; include __DIR__ . '/../includes/_sidebar.php'; ?>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64 overflow-x-hidden" id="main-wrapper">

            <!-- TopNavBar Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3.5 sm:px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm" id="topbar">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button" onclick="toggleNpcSidebar()" class="lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer shrink-0" title="Open navigation menu" aria-label="Open Navigation">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <span class="text-lg sm:text-xl font-bold text-primary lg:hidden truncate">NPC Connect</span>
                    <h2 class="text-xl font-bold text-primary hidden lg:block" id="page-title">Campus AI Assistant</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="/student/campus_map.php" class="ripple press inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-primary font-semibold text-xs hover:bg-primary/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <span class="hidden sm:inline">NPC Map</span>
                    </a>
                    <span class="font-mono text-[11px] sm:text-xs font-semibold bg-surface-container px-2 sm:px-3 py-1.5 rounded-md border border-outline-variant text-primary hidden sm:inline shrink-0" id="user-id-chip">ID: <?php echo htmlspecialchars($user_id_display); ?></span>
                    <?php 
                    $userAvatar = $_SESSION['picture'] ?? $_SESSION['avatar'] ?? null;
                    if (!empty($userAvatar)): ?>
                        <img alt="<?php echo htmlspecialchars($full_name); ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border border-outline-variant shadow-sm shrink-0"
                            src="<?php echo htmlspecialchars($userAvatar); ?>" referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary font-bold text-xs sm:text-sm items-center justify-center shadow-sm shrink-0 hidden">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm shrink-0">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-primary hidden md:inline" id="user-name-display"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
            </header>

            <!-- Page Canvas -->
            <main class="flex-1 p-3.5 sm:p-6 md:p-10 max-w-7xl w-full mx-auto" id="canvas-container">
            
                <!-- NPC AI ASSISTANT CHAT VIEW -->
                <div id="chatbot-view" class="view active">
                    <div class="h-[calc(100vh-8rem)] flex overflow-hidden bg-surface border border-outline-variant rounded-2xl shadow-sm">
                        <!-- Conversation History / Prompt Sidebar -->
                        <aside class="hidden md:flex flex-col w-72 bg-surface-subtle border-r border-outline-variant">
                            <div class="p-4 border-b border-outline-variant">
                                <button onclick="window.location.reload()" class="w-full flex items-center justify-center gap-2 bg-primary text-on-primary py-2.5 px-4 rounded-xl hover:bg-primary/90 transition-colors shadow-sm cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">add</span>
                                    <span class="font-mono text-sm font-semibold">New Conversation</span>
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                                <div class="px-2 py-1 text-on-surface-variant font-mono text-xs font-semibold opacity-70">Popular Student Topics</div>
                                <button onclick="setQuestionPrompt('Where can I see my class schedule and room assignments?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-primary text-[18px]">calendar_today</span>
                                    <span class="truncate font-medium">My Schedule</span>
                                </button>
                                <button onclick="setQuestionPrompt('How does the dynamic QR attendance work and what counts as late?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-status-info text-[18px]">qr_code_scanner</span>
                                    <span class="truncate font-medium">QR Attendance Rules</span>
                                </button>
                                <button onclick="setQuestionPrompt('How is the GPA and grading system calculated at NPC?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-secondary text-[18px]">grade</span>
                                    <span class="truncate font-medium">Grading System</span>
                                </button>
                                <button onclick="setQuestionPrompt('What academic degree programs and majors are available at NPC?')" class="w-full text-left px-3 py-2.5 rounded-lg bg-surface text-on-surface hover:bg-surface-container flex items-center gap-2.5 border border-outline-variant/60 text-xs transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-tertiary text-[18px]">school</span>
                                    <span class="truncate font-medium">Courses & Programs</span>
                                </button>
                            </div>
                        </aside>

                        <!-- Active Chat Canvas -->
                        <section class="flex-1 flex flex-col bg-surface relative">
                            <!-- Chat Header -->
                            <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-lowest flex justify-between items-center shadow-sm z-10">
                                <div class="flex items-center gap-3">
                                    <div id="ai-core-avatar" class="npc-ai-core-wrapper w-12 h-12 shrink-0"></div>
                                    <div>
                                        <h2 class="text-xl font-bold text-on-surface">NPC Campus AI Assistant</h2>
                                        <p class="font-mono text-xs text-on-surface-variant font-medium">Your official student academic guide · <span class="text-status-success font-semibold">3D Neural Core Active</span></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 bg-surface-container-high px-3 py-1.5 rounded-full border border-outline-variant">
                                    <span class="material-symbols-outlined text-status-info" style="font-size: 18px;">shield</span>
                                    <span class="font-mono text-xs font-semibold text-on-surface-variant">Student Verified</span>
                                </div>
                            </div>

                            <!-- Chat Messages Area -->
                            <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-background">
                                <!-- Intro Message -->
                                <div class="flex justify-center my-2">
                                    <div class="bg-surface-container-low px-4 py-1.5 rounded-full border border-outline-variant text-on-surface-variant font-mono text-xs font-semibold">
                                        Session Active
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
                                                <p>Hello <?php echo htmlspecialchars($user_name); ?>! I am your <strong>NPC Campus AI Assistant</strong>.</p>
                                                <p class="text-sm text-on-surface-variant">Ask me anything about your class schedules, dynamic QR attendance, grades, degree programs, or college guidelines.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="font-mono text-xs font-medium text-on-surface-variant mt-1 ml-11">Now</span>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <div class="p-4 md:p-6 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <div class="max-w-4xl mx-auto flex items-end gap-2 bg-surface-subtle border border-outline-variant rounded-2xl focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden pr-2 pl-4 py-2 shadow-inner transition-shadow">
                                    <textarea id="chat-input" class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 resize-none text-base text-on-surface py-2 max-h-32 overflow-y-auto" placeholder="Ask about schedules, QR attendance, grades, or subjects..." rows="1" style="min-height: 40px;"></textarea>
                                    <div class="flex items-center gap-1 pb-1">
                                        <button id="chat-send-btn" aria-label="Send message" onclick="sendMessage()" class="text-white bg-primary p-2 hover:bg-primary/90 rounded-full transition-colors flex items-center justify-center shadow-md cursor-pointer">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <p class="font-mono text-[11px] text-on-surface-variant/70">AI Assistant may produce inaccurate information. Always verify against official documents.</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../../assets/js/app.js') ? filemtime(__DIR__ . '/../../assets/js/app.js') : (file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : 1) ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            try {
                if (window.npcThree && typeof window.npcThree.initAiCore === 'function') {
                    window.aiCore3D = window.npcThree.initAiCore('ai-core-avatar', { size: 48 });
                }
            } catch (e) {
                console.error("AI Core 3D init error:", e);
            }
        });
    </script>
</body>
</html>
