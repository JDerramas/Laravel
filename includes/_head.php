<?php
/**
 * _head.php — Single source of truth for <head> across all NPC Connect pages.
 *
 * Usage (inside a page):
 *   <?php $PAGE_TITLE = 'Dashboard'; include '_head.php'; ?>
 *
 * Provides:
 *   - Pre-paint dark-mode bootstrap (no flash of wrong theme)
 *   - ONE centralized Tailwind config mapped to CSS variables,
 *     so every semantic color (and its /opacity modifiers) flips
 *     automatically between light & dark themes.
 *   - window.__CSRF__ + window.api() fetch helper
 *   - Theme toggle auto-mounted into #topbar
 *   - Staggered entrance animation for direct children of <main>
 *   - window.notify() toast system
 *
 * The including page keeps its own <head> wrapper and may append
 * page-specific <style>/<script> tags AFTER this include.
 */
$__pageTitle = isset($PAGE_TITLE) ? htmlspecialchars($PAGE_TITLE, ENT_QUOTES) : 'NPC LMS — Learning Management System';
$__csrfToken = function_exists('getCsrfToken') ? getCsrfToken() : '';
$__userRole = $_SESSION['role'] ?? 'student';
$__userBaseRole = $_SESSION['base_role'] ?? $__userRole;
$__userEmail = $_SESSION['email'] ?? '';
$__userName = $_SESSION['name'] ?? '';
?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $__pageTitle ?></title>
    <script id="npc-role-meta" type="application/json"><?= json_encode([
        'role' => $__userRole,
        'base_role' => $__userBaseRole,
        'email' => $__userEmail,
        'name' => $__userName
    ]) ?></script>

    <!-- Theme bootstrap: runs before first paint to prevent flashing -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('npc-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

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

    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= file_exists(dirname(__DIR__) . '/assets/css/styles.css') ? filemtime(dirname(__DIR__) . '/assets/css/styles.css') : '1' ?>">

    <!-- Three.js 3D Engine & NPC 3D Visuals -->
    <script src="/assets/js/three.min.js?v=<?= file_exists(dirname(__DIR__) . '/assets/js/three.min.js') ? filemtime(dirname(__DIR__) . '/assets/js/three.min.js') : 'r128' ?>"></script>
    <script>if (typeof THREE === 'undefined') { const s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js'; document.head.appendChild(s); }</script>
    <script src="/assets/js/npc-three.js?v=<?= file_exists(dirname(__DIR__) . '/assets/js/npc-three.js') ? filemtime(dirname(__DIR__) . '/assets/js/npc-three.js') : '1' ?>"></script>

    <!-- NPC UX Engine v2: count-up, ring charts, reveals, ripples, confetti, Ctrl+K palette -->
    <script src="/assets/js/npc.js?v=<?= file_exists(dirname(__DIR__) . '/assets/js/npc.js') ? filemtime(dirname(__DIR__) . '/assets/js/npc.js') : '1' ?>"></script>

    <!-- Local MySQL Supabase Drop-In Client (Zero Cloud Dependency) -->
    <script src="/assets/js/local-supabase.js?v=<?= file_exists(dirname(__DIR__) . '/assets/js/local-supabase.js') ? filemtime(dirname(__DIR__) . '/assets/js/local-supabase.js') : '1' ?>"></script>

    <script>
        // ── CSRF token for API calls ────────────────────────────────────────────
        window.__CSRF__ = <?= json_encode($__csrfToken) ?>;

        // ── Fetch helper: auto JSON headers + CSRF ─────────────────────────────
        window.api = function (url, opts) {
            opts = opts || {};
            opts.headers = Object.assign({
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }, opts.headers || {});
            if (window.__CSRF__) opts.headers['X-CSRF-Token'] = window.__CSRF__;
            return fetch(url, opts);
        };

        // ── Toast notifications ────────────────────────────────────────────────
        window.notify = function (message, type, timeout) {
            type = type || 'info';
            timeout = timeout || 4000;
            var root = document.getElementById('npc-toast-root');
            if (!root) {
                root = document.createElement('div');
                root.id = 'npc-toast-root';
                document.body.appendChild(root);
            }
            var icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
            var toast = document.createElement('div');
            toast.className = 'npc-toast ' + type;
            toast.innerHTML =
                '<span class="material-symbols-outlined">' + (icons[type] || icons.info) + '</span>' +
                '<span></span>';
            toast.lastChild.textContent = message;
            root.appendChild(toast);
            setTimeout(function () {
                toast.classList.add('leaving');
                setTimeout(function () { toast.remove(); }, 320);
            }, timeout);
        };

        // ── Entrance choreography: stagger direct children of <main> ──────────
        document.addEventListener('DOMContentLoaded', function () {
            var main = document.querySelector('main');
            if (!main) return;
            Array.prototype.slice.call(main.children).slice(0, 10).forEach(function (el, i) {
                el.classList.add('rise-in');
                el.style.animationDelay = Math.min(i * 70, 420) + 'ms';
            });
        });
    </script>
