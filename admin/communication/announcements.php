<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$admin_name = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$admin_email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
$jsConfig = getJsConfig();
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Campus Bulletin & Document Studio - NPC Admin</title>
    <!-- Tailwind CSS CDN -->
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <style>
        /* Google Docs Canvas Styling */
        .docs-page-canvas {
            min-height: 620px;
            box-shadow: 0 4px 20px -2px rgba(0, 23, 54, 0.08), 0 2px 6px -1px rgba(0, 23, 54, 0.04);
            border: 1px solid #e2e8f0;
            outline: none;
            line-height: 1.7;
            font-size: 14.5px;
            color: #0b1c30;
        }
        .docs-page-canvas:focus {
            box-shadow: 0 0 0 2px #001736, 0 8px 30px rgba(0, 23, 54, 0.12);
        }
        .docs-page-canvas p {
            margin-bottom: 0.75rem;
        }
        .docs-page-canvas h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #001736;
            margin-top: 1.25rem;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        .docs-page-canvas h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #001736;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .docs-page-canvas h3 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #002b5c;
            margin-top: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .docs-page-canvas ul {
            list-style-type: disc;
            margin-left: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .docs-page-canvas ol {
            list-style-type: decimal;
            margin-left: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .docs-page-canvas blockquote {
            border-left: 4px solid #775a19;
            padding-left: 1rem;
            font-style: italic;
            color: #43474f;
            background: #eff4ff;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-radius: 0 8px 8px 0;
            margin: 0.75rem 0;
        }
        .docs-toolbar-btn {
            padding: 4px 6px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #43474f;
            transition: all 0.12s ease;
        }
        .docs-toolbar-btn:hover {
            background-color: #e5eeff;
            color: #001736;
        }
        .docs-toolbar-btn.active {
            background-color: #002b5c;
            color: #ffffff;
        }
        .docs-separator {
            width: 1px;
            height: 18px;
            background-color: #cbd5e1;
            margin: 0 4px;
        }
    </style>    <script id="npc-role-meta" type="application/json"><?= json_encode(['role' => $_SESSION['role'] ?? 'student', 'email' => $_SESSION['email'] ?? '', 'name' => $_SESSION['name'] ?? '']) ?></script>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <?php include __DIR__ . '/../../includes/_denied_banner.php'; ?>
    <!-- SideNavBar Desktop -->
    <?php $NPC_PORTAL = 'admin'; include __DIR__ . '/../../includes/_sidebar.php'; ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 lg:ml-64 bg-surface min-h-screen flex flex-col">
        <!-- Top Header -->
        <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-6 md:px-10 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold text-primary lg:hidden">NPC Admin</span>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[24px]">description</span>
                    <h2 class="text-lg font-bold text-primary hidden lg:block">Campus Bulletin & Document Studio</h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openHistoryModal()" class="bg-surface-container hover:bg-surface-container-high text-primary px-3.5 py-2 rounded-xl flex items-center gap-1.5 text-xs font-bold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">history</span>
                    Broadcast History
                </button>
                <button onclick="saveDocumentAnnouncement('published')" class="px-5 py-2 bg-primary text-on-primary font-bold text-xs rounded-xl hover:opacity-90 transition-opacity shadow-sm flex items-center gap-1.5 npc-navy-card">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    Publish Announcement
                </button>
            </div>
        </header>

        <!-- Main Workspace -->
        <div class="p-6 md:p-8 max-w-7xl w-full mx-auto space-y-6 flex-1 flex flex-col">
            
            <!-- Quick Document Preset Templates -->
            <div class="bg-surface-container-lowest p-4 px-6 rounded-2xl border border-outline-variant shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[20px]">auto_stories</span>
                    <div>
                        <span class="text-xs font-bold text-primary block">Official Document Presets</span>
                        <span class="text-[11px] text-on-surface-variant">Click to populate official NPC memo templates</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="applyTemplate('advisory')" class="px-3 py-1.5 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-primary text-xs font-semibold transition-all">
                        🏛️ Official Advisory
                    </button>
                    <button onclick="applyTemplate('exam')" class="px-3 py-1.5 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-primary text-xs font-semibold transition-all">
                        📝 Exam Schedule
                    </button>
                    <button onclick="applyTemplate('enrollment')" class="px-3 py-1.5 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-primary text-xs font-semibold transition-all">
                        🎓 Enrollment Steps
                    </button>
                    <button onclick="applyTemplate('suspension')" class="px-3 py-1.5 rounded-lg bg-error/10 hover:bg-error hover:text-white text-error text-xs font-semibold transition-all">
                        🚨 Urgent Suspension
                    </button>
                </div>
            </div>

            <!-- Content Grid Layout: Left Document Sheet (Span 8) & Right Settings Panel (Span 4) -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start flex-1">
                
                <!-- 📄 LEFT: GOOGLE DOCS CANVAS (SPAN 8) -->
                <div class="xl:col-span-8 space-y-4 flex flex-col">
                    
                    <!-- Document Title Input -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 px-6 shadow-sm">
                        <label class="block text-xs font-bold text-primary uppercase tracking-wider mb-1">Announcement Title / Subject Line</label>
                        <input id="doc-title" class="w-full bg-transparent border-0 border-b-2 border-outline-variant/60 focus:border-primary focus:ring-0 text-lg font-bold text-primary px-0 py-1 transition-all placeholder:text-gray-400" placeholder="e.g. CAMPUS ADVISORY: Midterm Examination Guidelines for AY 2026-2027">
                    </div>

                    <!-- 🧰 GOOGLE DOCS RIBBON TOOLBAR -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-2 px-3 shadow-sm sticky top-20 z-20 flex flex-wrap items-center gap-1">
                        <!-- Undo / Redo -->
                        <button type="button" onclick="formatDoc('undo')" class="docs-toolbar-btn" title="Undo (Ctrl+Z)"><span class="material-symbols-outlined text-[18px]">undo</span></button>
                        <button type="button" onclick="formatDoc('redo')" class="docs-toolbar-btn" title="Redo (Ctrl+Y)"><span class="material-symbols-outlined text-[18px]">redo</span></button>

                        <div class="docs-separator"></div>

                        <!-- Paragraph Style -->
                        <select onchange="formatDoc('formatBlock', this.value); this.selectedIndex=0;" class="bg-transparent border-0 text-xs font-semibold text-primary focus:ring-0 py-1 pl-2 pr-6 cursor-pointer">
                            <option value="">Normal Text</option>
                            <option value="h1">Title (H1)</option>
                            <option value="h2">Heading 1 (H2)</option>
                            <option value="h3">Heading 2 (H3)</option>
                            <option value="p">Paragraph</option>
                            <option value="blockquote">Quote Block</option>
                        </select>

                        <div class="docs-separator"></div>

                        <!-- Font Family -->
                        <select onchange="formatDoc('fontName', this.value); this.selectedIndex=0;" class="bg-transparent border-0 text-xs font-semibold text-primary focus:ring-0 py-1 pl-2 pr-6 cursor-pointer">
                            <option value="Geist">Geist (Modern)</option>
                            <option value="Merriweather">Merriweather (Serif)</option>
                            <option value="JetBrains Mono">JetBrains (Mono)</option>
                            <option value="Arial">Arial</option>
                        </select>

                        <div class="docs-separator"></div>

                        <!-- Basic Formatting -->
                        <button type="button" onclick="formatDoc('bold')" class="docs-toolbar-btn" title="Bold (Ctrl+B)"><span class="material-symbols-outlined text-[18px] font-bold">format_bold</span></button>
                        <button type="button" onclick="formatDoc('italic')" class="docs-toolbar-btn" title="Italic (Ctrl+I)"><span class="material-symbols-outlined text-[18px]">format_italic</span></button>
                        <button type="button" onclick="formatDoc('underline')" class="docs-toolbar-btn" title="Underline (Ctrl+U)"><span class="material-symbols-outlined text-[18px]">format_underlined</span></button>
                        <button type="button" onclick="formatDoc('strikeThrough')" class="docs-toolbar-btn" title="Strikethrough"><span class="material-symbols-outlined text-[18px]">strikethrough_s</span></button>

                        <div class="docs-separator"></div>

                        <!-- Alignment -->
                        <button type="button" onclick="formatDoc('justifyLeft')" class="docs-toolbar-btn" title="Align Left"><span class="material-symbols-outlined text-[18px]">format_align_left</span></button>
                        <button type="button" onclick="formatDoc('justifyCenter')" class="docs-toolbar-btn" title="Align Center"><span class="material-symbols-outlined text-[18px]">format_align_center</span></button>
                        <button type="button" onclick="formatDoc('justifyRight')" class="docs-toolbar-btn" title="Align Right"><span class="material-symbols-outlined text-[18px]">format_align_right</span></button>
                        <button type="button" onclick="formatDoc('justifyFull')" class="docs-toolbar-btn" title="Justify"><span class="material-symbols-outlined text-[18px]">format_align_justify</span></button>

                        <div class="docs-separator"></div>

                        <!-- Lists -->
                        <button type="button" onclick="formatDoc('insertUnorderedList')" class="docs-toolbar-btn" title="Bulleted List"><span class="material-symbols-outlined text-[18px]">format_list_bulleted</span></button>
                        <button type="button" onclick="formatDoc('insertOrderedList')" class="docs-toolbar-btn" title="Numbered List"><span class="material-symbols-outlined text-[18px]">format_list_numbered</span></button>
                        <button type="button" onclick="formatDoc('indent')" class="docs-toolbar-btn" title="Increase Indent"><span class="material-symbols-outlined text-[18px]">format_indent_increase</span></button>
                        <button type="button" onclick="formatDoc('outdent')" class="docs-toolbar-btn" title="Decrease Indent"><span class="material-symbols-outlined text-[18px]">format_indent_decrease</span></button>

                        <div class="docs-separator"></div>

                        <!-- Inserts -->
                        <button type="button" onclick="insertCalloutBox()" class="docs-toolbar-btn" title="Insert Highlight Box"><span class="material-symbols-outlined text-[18px] text-secondary">ad_units</span></button>
                        <button type="button" onclick="insertDivider()" class="docs-toolbar-btn" title="Insert Horizontal Line"><span class="material-symbols-outlined text-[18px]">horizontal_rule</span></button>
                        <button type="button" onclick="insertLinkPrompt()" class="docs-toolbar-btn" title="Insert Hyperlink"><span class="material-symbols-outlined text-[18px]">link</span></button>
                        <button type="button" onclick="formatDoc('removeFormat')" class="docs-toolbar-btn text-error" title="Clear Formatting"><span class="material-symbols-outlined text-[18px]">format_clear</span></button>
                    </div>

                    <!-- 📄 THE GOOGLE DOCS PAGE SHEET -->
                    <div class="bg-surface-subtle p-6 rounded-2xl border border-outline-variant/50 flex justify-center">
                        <div id="docs-editor" contenteditable="true" class="docs-page-canvas bg-white max-w-3xl w-full p-10 md:p-14 rounded-xl" oninput="onEditorInput()">
                            <p>Type your official announcement here or click any template above...</p>
                        </div>
                    </div>

                    <!-- Document Footer Status Bar -->
                    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-6 py-2.5 flex justify-between items-center text-xs font-mono text-on-surface-variant">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5 text-status-success font-bold">
                                <span class="w-2 h-2 rounded-full bg-status-success"></span> Rich Editor Ready
                            </span>
                            <span>•</span>
                            <span id="doc-words-count">0 words</span>
                            <span>•</span>
                            <span id="doc-chars-count">0 characters</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-on-surface-variant/70">Google Docs Engine v2.0</span>
                        </div>
                    </div>
                </div>

                <!-- ⚙️ RIGHT: BROADCAST SETTINGS & TARGETING (SPAN 4) -->
                <div class="xl:col-span-4 space-y-6">
                    
                    <!-- 1. Category Classification -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">category</span>
                            Classification Type
                        </h2>
                        <div class="space-y-2.5">
                            <label class="flex items-center justify-between p-3 border border-outline-variant/60 rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors group has-[:checked]:border-secondary has-[:checked]:bg-secondary-container/20">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">school</span>
                                    <div>
                                        <span class="text-xs font-bold text-on-surface block">Academic Alert</span>
                                        <span class="text-[11px] text-on-surface-variant">Curriculum, grades & exams</span>
                                    </div>
                                </div>
                                <input checked name="category" type="radio" value="academic" class="text-secondary focus:ring-secondary">
                            </label>

                            <label class="flex items-center justify-between p-3 border border-outline-variant/60 rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors group has-[:checked]:border-error has-[:checked]:bg-error/10">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-error" style="font-variation-settings: 'FILL' 1;">warning</span>
                                    <div>
                                        <span class="text-xs font-bold text-on-surface block">Emergency Advisory</span>
                                        <span class="text-[11px] text-on-surface-variant">Suspensions & urgent alerts</span>
                                    </div>
                                </div>
                                <input name="category" type="radio" value="emergency" class="text-error focus:ring-error">
                            </label>

                            <label class="flex items-center justify-between p-3 border border-outline-variant/60 rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors group has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">article</span>
                                    <div>
                                        <span class="text-xs font-bold text-on-surface block">Campus News</span>
                                        <span class="text-[11px] text-on-surface-variant">Events & general updates</span>
                                    </div>
                                </div>
                                <input name="category" type="radio" value="news" class="text-primary focus:ring-primary">
                            </label>
                        </div>
                    </div>

                    <!-- 2. Target Audience -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">groups</span>
                            Target Audience
                        </h2>
                        
                        <div class="space-y-2 text-xs">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input checked id="target-students" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary" type="checkbox">
                                <span class="font-semibold text-on-surface">All Enrolled Students</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input checked id="target-faculty" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary" type="checkbox">
                                <span class="font-semibold text-on-surface">Faculty & Instructors</span>
                            </label>
                        </div>

                        <div class="pt-2 border-t border-outline-variant/40">
                            <label class="block text-xs font-semibold text-on-surface mb-1">Target Section Filter</label>
                            <select id="target-section" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-semibold text-primary focus:outline-none focus:border-primary">
                                <option value="all">Entire Student Body (All Sections)</option>
                                <option value="AIS 2A">AIS 2A</option>
                                <option value="BSIS 3A">BSIS 3A</option>
                                <option value="BSBA - HR 1A">BSBA - HR 1A</option>
                                <option value="BSBA - FM 2A">BSBA - FM 2A</option>
                                <option value="BSEd 2A">BSEd 2A</option>
                                <option value="BEEd 2A">BEEd 2A</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Publish & Actions -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm space-y-3">
                        <button onclick="saveDocumentAnnouncement('published')" class="w-full py-3 bg-primary text-on-primary font-bold text-sm rounded-xl hover:opacity-90 transition-opacity shadow-md flex items-center justify-center gap-2 npc-navy-card">
                            <span class="material-symbols-outlined text-[18px]">campaign</span>
                            Broadcast Bulletin Now
                        </button>
                        <button onclick="saveDocumentAnnouncement('draft')" class="w-full py-2.5 bg-surface-container text-primary font-semibold text-xs rounded-xl hover:bg-surface-container-high transition-colors flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">save</span>
                            Save as Internal Draft
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <!-- HISTORY & DRAFTS MODAL -->
        <div id="history-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-3xl mx-4 overflow-hidden border border-outline-variant/20 flex flex-col max-h-[85vh]">
                <div class="p-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-subtle">
                    <div>
                        <h3 class="text-xl font-bold text-primary">Published Bulletin History</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Manage, delete, or view previous campus announcements</p>
                    </div>
                    <button onclick="document.getElementById('history-modal').classList.add('hidden')" class="text-on-surface-variant hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 space-y-3" id="history-list-container"><div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div></div>
            </div>
        </div>
    </main>

    <script>
        const supabaseUrl = <?= json_encode($jsConfig['url']) ?>;
        const supabaseKey = <?= json_encode($jsConfig['key']) ?>;
        const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
        const csrfToken = <?= json_encode($csrf_token) ?>;

        // Rich Text Command Engine
        function formatDoc(cmd, value = null) {
            document.execCommand(cmd, false, value);
            document.getElementById('docs-editor').focus();
            onEditorInput();
        }

        function insertCalloutBox() {
            const html = `
                <div style="background-color: #eff4ff; border-left: 4px solid #001736; padding: 12px 16px; border-radius: 6px; margin: 12px 0;">
                    <strong>📌 IMPORTANT NOTICE:</strong> Enter detailed instruction or highlight text here...
                </div>
                <p><br></p>
            `;
            document.execCommand('insertHTML', false, html);
            onEditorInput();
        }

        function insertDivider() {
            document.execCommand('insertHorizontalRule', false, null);
            onEditorInput();
        }

        function insertLinkPrompt() {
            const url = prompt('Enter Hyperlink URL (e.g. https://navotaspolytechniccollege.edu.ph):');
            if (url) {
                formatDoc('createLink', url);
            }
        }

        function onEditorInput() {
            const editor = document.getElementById('docs-editor');
            const text = editor.innerText.trim();
            const words = text ? text.split(/\s+/).length : 0;
            const chars = text.length;

            document.getElementById('doc-words-count').innerText = `${words} words`;
            document.getElementById('doc-chars-count').innerText = `${chars} characters`;
        }

        // Preset Templates (Official Document Studio)
        function applyTemplate(type) {
            const editor = document.getElementById('docs-editor');
            const titleInput = document.getElementById('doc-title');

            if (type === 'advisory') {
                titleInput.value = 'OFFICIAL CAMPUS ADVISORY: Midterm Academic Guidelines';
                editor.innerHTML = `
                    <div style="text-align: center; border-bottom: 2px solid #001736; padding-bottom: 12px; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #001736; font-size: 18px; font-weight: bold;">NAVOTAS POLYTECHNIC COLLEGE</h2>
                        <p style="margin: 2px 0; font-size: 12px; color: #64748b;">Office of Academic Affairs • Campus Bulletin</p>
                    </div>
                    <h2>ACADEMIC MEMORANDUM</h2>
                    <p><strong>TO:</strong> All Bonafide Students and Faculty Members</p>
                    <p><strong>DATE:</strong> ${new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
                    <p><strong>SUBJECT:</strong> Official Guidelines and Policies for Midterm Academic Cycle</p>
                    <hr style="border: 0; border-top: 1px solid #cbd5e1; margin: 16px 0;">
                    <p>Please be advised of the following key schedules and compliance requirements:</p>
                    <ul>
                        <li><strong>Attendance & Participation:</strong> Students must maintain official verified attendance via NPC Connect QR code.</li>
                        <li><strong>Encoding of Grades:</strong> Faculty professors will encode Prelim and Midterm grade reports via the faculty portal.</li>
                        <li><strong>Student Consultations:</strong> Academic advisers are available during scheduled consultation hours.</li>
                    </ul>
                    <div style="background-color: #eff4ff; border-left: 4px solid #001736; padding: 12px 16px; border-radius: 6px; margin: 16px 0;">
                        <strong>📌 REMINDER:</strong> For inquiries, please reach out to your respective college department coordinator.
                    </div>
                    <p><br></p>
                    <p>Approved by:<br><strong>Office of Academic Affairs</strong><br>Navotas Polytechnic College</p>
                `;
            } else if (type === 'exam') {
                titleInput.value = 'EXAMINATION SCHEDULE: 1st Semester Midterm Examinations';
                editor.innerHTML = `
                    <h2>MIDTERM EXAMINATION NOTICE</h2>
                    <p>This is to inform all students across all college programs regarding the upcoming <strong>Midterm Examinations</strong>.</p>
                    <div style="background-color: #fef3c7; border-left: 4px solid #d97706; padding: 12px 16px; border-radius: 6px; margin: 16px 0;">
                        <strong>⚠️ EXAMINATION PROTOCOL:</strong> Please bring your official NPC Student ID and ensure your enrollment clearance is verified.
                    </div>
                    <h3>Important Examination Guidelines:</h3>
                    <ol>
                        <li>Strict adherence to scheduled time slots is enforced. Late students exceeding 15 minutes will not be admitted.</li>
                        <li>No electronic gadgets or unauthorized study materials are permitted during test periods.</li>
                        <li>Check your section timetable for room and proctor assignments.</li>
                    </ol>
                    <p>We wish all students the best in their examinations!</p>
                `;
            } else if (type === 'enrollment') {
                titleInput.value = 'ENROLLMENT ADVISORY: Step-by-Step Registration Process';
                editor.innerHTML = `
                    <h2>COLLEGE ENROLLMENT GUIDELINES</h2>
                    <p>Follow the streamlined enrollment procedure for the upcoming semester:</p>
                    <ul>
                        <li><strong>Step 1:</strong> Access your NPC Connect student portal and review your degree evaluation.</li>
                        <li><strong>Step 2:</strong> Verify your block section assignment and registered subjects.</li>
                        <li><strong>Step 3:</strong> Download and print your official certified Certificate of Registration (COR).</li>
                    </ul>
                    <p>For section transfer requests, please visit the Registrar's Office during office hours (8:00 AM - 5:00 PM).</p>
                `;
            } else if (type === 'suspension') {
                titleInput.value = 'URGENT ADVISORY: Class and Office Work Suspension';
                document.querySelector('input[name="category"][value="emergency"]').checked = true;
                editor.innerHTML = `
                    <div style="background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                        <h2 style="color: #991b1b; margin: 0 0 8px 0;">🚨 URGENT CAMPUS ADVISORY: CLASS SUSPENSION</h2>
                        <p style="color: #7f1d1d; margin: 0; font-weight: 600;">In light of inclement weather advisories from PAGASA, classes and school activities are SUSPENDED.</p>
                    </div>
                    <p>Due to severe weather conditions, <strong>all in-person classes and campus activities are suspended</strong> effective immediately.</p>
                    <ul>
                        <li>Students and faculty are advised to stay safe at home.</li>
                        <li>Synchronous and asynchronous learning activities will resume upon further notice.</li>
                        <li>Keep monitoring the official NPC Connect bulletin for updates.</li>
                    </ul>
                `;
            }
            onEditorInput();
        }

        async function saveDocumentAnnouncement(status) {
            const title = document.getElementById('doc-title').value.trim();
            const bodyHtml = document.getElementById('docs-editor').innerHTML.trim();
            const category = document.querySelector('input[name="category"]:checked')?.value || 'academic';
            const targetSection = document.getElementById('target-section').value;

            if (!title) return alert('Please provide an announcement title.');
            if (!bodyHtml || bodyHtml === '<p><br></p>') return alert('Please enter announcement body content.');

            let ok = false;
            try {
                const res = await fetch('/api/admin.php?action=save_announcement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        title: title,
                        body: bodyHtml,
                        category: category,
                        status: status,
                        department: targetSection,
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Server rejected the announcement.');
                ok = true;
            } catch (err) {
                alert('Error publishing announcement: ' + err.message);
            }

            if (ok) {
                alert(`✅ Successfully ${status === 'published' ? 'published' : 'saved'} announcement! It is now live in the student feed.`);
                document.getElementById('doc-title').value = '';
                document.getElementById('docs-editor').innerHTML = '<p>Type your official announcement here or click any template above...</p>';
                onEditorInput();
            }
        }

        async function openHistoryModal() {
            document.getElementById('history-modal').classList.remove('hidden');
            const container = document.getElementById('history-list-container');
            container.innerHTML = '<div class="npc-skeleton-group py-2" aria-busy="true"><div class="sk-table-row"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-3/4" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div><div class="sk-table-row" style="padding-bottom:0"><div class="skeleton sk-line" style="margin-bottom:0"></div><div class="skeleton sk-line w-2/3" style="margin-bottom:0"></div><div class="skeleton sk-chip"></div><div class="skeleton sk-chip" style="width:2.5rem;margin-left:auto"></div></div></div>';

            const { data, error } = await supabaseClient
                .from('announcements')
                .select('*')
                .order('created_at', { ascending: false });

            if (error || !data || data.length === 0) {
                container.innerHTML = '<p class="text-center text-on-surface-variant py-8">No previous announcements found.</p>';
                return;
            }

            container.innerHTML = data.map(a => `
                <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/50 flex justify-between items-start gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase ${a.category === 'emergency' ? 'bg-error/20 text-error' : 'bg-primary/10 text-primary'}">${a.category}</span>
                            <span class="text-xs text-on-surface-variant font-mono">${new Date(a.created_at).toLocaleDateString()}</span>
                        </div>
                        <h4 class="font-bold text-sm text-primary break-words" style="overflow-wrap:anywhere;">${a.title}</h4>
                        <div class="text-xs text-on-surface-variant line-clamp-2 max-w-xl break-words" style="overflow-wrap:anywhere;">${a.body}</div>
                    </div>
                    <button onclick="deleteAnnouncement('${a.id}')" class="text-error hover:bg-error/10 p-2 rounded-lg transition-colors" title="Delete">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            `).join('');
        }

        async function deleteAnnouncement(id) {
            if (!await npcConfirm({
                title: 'Delete Announcement',
                message: 'Are you sure you want to delete this announcement?',
                type: 'danger',
                confirmText: 'Yes, Delete'
            })) return;
            let ok = false;
            let msg = '';
            try {
                const res = await fetch('/api/admin.php?action=delete_announcement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ id: id, csrf_token: csrfToken })
                });
                const data = await res.json();
                ok = !!data.success;
                msg = data.message || '';
            } catch (err) {
                msg = err.message;
            }
            if (!ok) alert('Error: ' + (msg || 'Delete failed.'));
            openHistoryModal();
        }

        onEditorInput();
    </script>
</body>
</html>
