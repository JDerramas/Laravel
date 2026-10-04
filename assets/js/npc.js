/* ============================================================
   npc.js — NPC Connect UX Engine v2
   Global micro-interaction + data-viz layer. Auto-loads on
   every page via _head.php (and legacy pages that include it).
   Everything is defensive: missing DOM = no-op.
   ============================================================ */
(function () {
    'use strict';

    /* --------------------------------------------------------
       0. AUTONOMOUS LOCAL MYSQL SUPABASE CLIENT
          Redirects all client-side queries to local MySQL REST gateway.
          Zero dependency on external Supabase servers.
       -------------------------------------------------------- */
    function initLocalSupabase() {
        function createLocalClient() {
            return {
                from: function(table) {
                    var _table = table;
                    var _select = '*';
                    var _order = null;
                    var _limit = null;
                    var _filters = [];
                    var _single = false;

                    function executeQuery() {
                        var qs = 'table=' + encodeURIComponent(_table) + '&select=' + encodeURIComponent(_select);
                        if (_order) qs += '&order=' + encodeURIComponent(_order);
                        if (_limit) qs += '&limit=' + encodeURIComponent(_limit);
                        if (_filters.length) qs += '&' + _filters.join('&');

                        return fetch('/api/rest.php?' + qs)
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                var data = res;
                                if (_single) {
                                    data = Array.isArray(res) ? (res[0] || null) : res;
                                } else if (!Array.isArray(res)) {
                                    data = res ? [res] : [];
                                }
                                return { data: data, error: null };
                            })
                            .catch(function(err) {
                                console.warn('[LocalDB] Query error for ' + _table + ':', err);
                                return { data: null, error: err };
                            });
                    }

                    var queryObj = {
                        select: function(fields) { _select = fields || '*'; return queryObj; },
                        eq: function(col, val) { _filters.push(encodeURIComponent(col) + '=eq.' + encodeURIComponent(val)); return queryObj; },
                        neq: function(col, val) { _filters.push(encodeURIComponent(col) + '=neq.' + encodeURIComponent(val)); return queryObj; },
                        order: function(col, opts) { _order = col + (opts && opts.ascending === false ? '.desc' : '.asc'); return queryObj; },
                        limit: function(n) { _limit = n; return queryObj; },
                        single: function() { _single = true; return executeQuery(); },
                        then: function(onFulfilled, onRejected) { return executeQuery().then(onFulfilled, onRejected); },
                        insert: function(rows) {
                            var body = Array.isArray(rows) ? rows : [rows];
                            return fetch('/api/rest.php?table=' + encodeURIComponent(_table), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(body)
                            }).then(function(r) { return r.json(); })
                              .then(function(data) { return { data: data, error: null }; })
                              .catch(function(err) { return { data: null, error: err }; });
                        },
                        update: function(vals) {
                            return {
                                eq: function(col, val) {
                                    return fetch('/api/rest.php?table=' + encodeURIComponent(_table) + '&' + encodeURIComponent(col) + '=eq.' + encodeURIComponent(val), {
                                        method: 'PATCH',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(vals)
                                    }).then(function(r) { return r.json(); })
                                      .then(function(data) { return { data: data, error: null }; })
                                      .catch(function(err) { return { data: null, error: err }; });
                                }
                            };
                        },
                        delete: function() {
                            return {
                                eq: function(col, val) {
                                    return fetch('/api/rest.php?table=' + encodeURIComponent(_table) + '&' + encodeURIComponent(col) + '=eq.' + encodeURIComponent(val), {
                                        method: 'DELETE'
                                    }).then(function(r) { return r.json(); })
                                      .then(function(data) { return { data: data, error: null }; })
                                      .catch(function(err) { return { data: null, error: err }; });
                                }
                            };
                        }
                    };
                    return queryObj;
                },
                channel: function(name) {
                    var listeners = [];
                    var pollInterval = null;
                    var lastCheck = new Date().toISOString();
                    var channelObj = {
                        on: function(event, config, callback) {
                            listeners.push({ event: event, config: config, callback: callback });
                            return channelObj;
                        },
                        subscribe: function() {
                            if (!pollInterval) {
                                pollInterval = setInterval(function() {
                                    listeners.forEach(function(l) {
                                        var tbl = (l.config && l.config.table) || 'attendance_records';
                                        fetch('/api/realtime.php?table=' + encodeURIComponent(tbl) + '&since=' + encodeURIComponent(lastCheck))
                                            .then(function(r) { return r.json(); })
                                            .then(function(res) {
                                                if (res && res.data && res.data.length > 0) {
                                                    lastCheck = res.timestamp || new Date().toISOString();
                                                    res.data.forEach(function(row) {
                                                        try { l.callback({ eventType: 'INSERT', new: row }); } catch(e) {}
                                                    });
                                                }
                                            }).catch(function() {});
                                    });
                                }, 1500);
                            }
                            return channelObj;
                        },
                        unsubscribe: function() { if (pollInterval) clearInterval(pollInterval); }
                    };
                    return channelObj;
                },
                auth: {
                    getUser: function() { return Promise.resolve({ data: { user: { id: 'usr-local', email: 'user@navotaspolytechniccollege.edu.ph' } }, error: null }); },
                    getSession: function() { return Promise.resolve({ data: { session: { user: { id: 'usr-local' } } }, error: null }); },
                    signOut: function() { window.location.href = '/logout.php'; return Promise.resolve({ error: null }); },
                    onAuthStateChange: function(cb) { return { data: { subscription: { unsubscribe: function() {} } } }; }
                }
            };
        }

        window.createLocalSupabaseClient = createLocalClient;
        window.supabase = {
            createClient: function() { return createLocalClient(); }
        };
    }
    initLocalSupabase();

    var REDUCED = false;
    try { REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

    /* --------------------------------------------------------
       1. COUNT-UP — animate any [data-countup] to its value.
          Usage: <span data-countup="1234">0</span>
                 <span data-countup="92.4" data-decimals="1" data-suffix="%">
       -------------------------------------------------------- */
    function animateCountUp(el) {
        if (el.__npcCounted) return;
        el.__npcCounted = true;
        var target = parseFloat(el.getAttribute('data-countup'));
        if (isNaN(target)) return;
        var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var suffix = el.getAttribute('data-suffix') || '';
        var prefix = el.getAttribute('data-prefix') || '';
        var dur = parseInt(el.getAttribute('data-duration') || '1100', 10);

        if (REDUCED) { el.textContent = prefix + target.toFixed(decimals) + suffix; return; }

        var t0 = null;
        function frame(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3); /* easeOutCubic */
            el.textContent = prefix + (target * eased).toFixed(decimals) + suffix;
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
    window.npcCountUp = animateCountUp;

    /* --------------------------------------------------------
       2. RING CHART — animate SVG circles with data-ring-pct.
          Usage: <circle data-ring-pct="78" r="15.9155" … />
       -------------------------------------------------------- */
    var RING_CIRCUM = 100; /* pathLength=100 convention used by NPC donuts */

    function animateRing(circle) {
        if (circle.__npcRingDone) return;
        circle.__npcRingDone = true;
        var pct = Math.max(0, Math.min(100, parseFloat(circle.getAttribute('data-ring-pct')) || 0));
        circle.setAttribute('pathLength', '100');
        circle.setAttribute('stroke-dasharray', '100');
        if (REDUCED) {
            circle.setAttribute('stroke-dashoffset', String(100 - pct));
            return;
        }
        circle.setAttribute('stroke-dashoffset', '100');
        var t0 = null;
        var dur = 1200;
        function frame(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            circle.setAttribute('stroke-dashoffset', String(100 - pct * eased));
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    /* Run all pending animations inside a root element */
    function runAnimations(root) {
        root = root || document;
        root.querySelectorAll('[data-countup]').forEach(animateCountUp);
        root.querySelectorAll('circle[data-ring-pct], path[data-ring-pct]').forEach(function (el) {
            /* wait a tick so layout settles before measuring/animating */
            setTimeout(function () { animateRing(el); }, 60);
        });
        root.querySelectorAll('.reveal:not(.revealed)').forEach(function (el) { revealObserver.observe(el); });
        root.querySelectorAll('.tilt, .npc-tile, .npc-card').forEach(bindTilt);
    }
    window.npcRunAnimations = runAnimations;

    /* --------------------------------------------------------
       3. SCROLL REVEAL
       -------------------------------------------------------- */
    var revealObserver = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('revealed');
                    revealObserver.unobserve(en.target);
                }
            });
        }, { threshold: 0.08 })
        : null;

    /* --------------------------------------------------------
       4. RIPPLE — attach to .ripple elements via event delegation
       -------------------------------------------------------- */
    document.addEventListener('pointerdown', function (e) {
        var host = e.target.closest ? e.target.closest('.ripple') : null;
        if (!host || REDUCED) return;
        var rect = host.getBoundingClientRect();
        var ink = document.createElement('span');
        ink.className = 'npc-ink';
        var size = Math.max(rect.width, rect.height);
        ink.style.width = ink.style.height = size + 'px';
        ink.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ink.style.top = (e.clientY - rect.top - size / 2) + 'px';
        host.appendChild(ink);
        setTimeout(function () { ink.remove(); }, 600);
    }, { passive: true });

    /* --------------------------------------------------------
       5. 3D TILT & ELEVATED ICON DEPTH — .tilt, .npc-tile, .npc-card
       -------------------------------------------------------- */
    function bindTilt(el) {
        if (el.__npcTilt || REDUCED) return;
        el.__npcTilt = true;
        el.style.transformStyle = 'preserve-3d';

        var childIcon = el.querySelector('.npc-tile-icon, .material-symbols-outlined, img, .icon-3d');

        el.addEventListener('pointermove', function (e) {
            var r = el.getBoundingClientRect();
            var px = (e.clientX - r.left) / r.width;
            var py = (e.clientY - r.top) / r.height;
            var x = px - 0.5;
            var y = py - 0.5;
            el.style.setProperty('--spec-x', (px * 100).toFixed(1) + '%');
            el.style.setProperty('--spec-y', (py * 100).toFixed(1) + '%');
            el.style.transform = 'perspective(900px) rotateX(' + (-y * 10).toFixed(2) + 'deg) rotateY(' + (x * 12).toFixed(2) + 'deg) translateZ(8px)';
            
            if (childIcon) {
                childIcon.style.transform = 'perspective(600px) translateZ(24px) rotateX(' + (-y * 14).toFixed(2) + 'deg) rotateY(' + (x * 16).toFixed(2) + 'deg) scale(1.15)';
                childIcon.style.transition = 'transform 0.08s ease-out';
            }
        });

        el.addEventListener('pointerleave', function () {
            el.style.transform = '';
            if (childIcon) {
                childIcon.style.transform = '';
                childIcon.style.transition = 'transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1)';
            }
        });
    }

    /* --------------------------------------------------------
       6. CONFETTI & CELEBRATION — 3D Three.js with 2D Canvas Fallback
          window.npcConfetti.burst()
       -------------------------------------------------------- */
    window.npcConfetti = {
        _burst2D: function (count) {
            if (REDUCED) return;
            count = count || 120;
            var canvas = document.createElement('canvas');
            canvas.style.cssText = 'position:fixed;inset:0;width:100vw;height:100vh;pointer-events:none;z-index:9999;';
            document.body.appendChild(canvas);
            var ctx = canvas.getContext('2d');
            var dpr = window.devicePixelRatio || 1;
            canvas.width = innerWidth * dpr; canvas.height = innerHeight * dpr;
            ctx.scale(dpr, dpr);
            var colors = ['#fed488', '#f7b955', '#aac7ff', '#0a4d8c', '#38bdf8', '#4ade80'];
            var parts = [];
            for (var i = 0; i < count; i++) {
                parts.push({
                    x: innerWidth / 2 + (Math.random() - 0.5) * innerWidth * 0.4,
                    y: innerHeight * 0.35,
                    vx: (Math.random() - 0.5) * 11,
                    vy: -Math.random() * 10 - 4,
                    w: 5 + Math.random() * 6,
                    h: 3 + Math.random() * 5,
                    rot: Math.random() * Math.PI,
                    vr: (Math.random() - 0.5) * 0.28,
                    color: colors[(Math.random() * colors.length) | 0],
                    life: 1
                });
            }
            var start = null;
            function step(ts) {
                if (!start) start = ts;
                var dt = Math.min((ts - start) / 16.7, 3); start = ts;
                ctx.clearRect(0, 0, innerWidth, innerHeight);
                var alive = 0;
                parts.forEach(function (p) {
                    if (p.life <= 0) return;
                    alive++;
                    p.vy += 0.32 * dt; p.x += p.vx * dt; p.y += p.vy * dt; p.rot += p.vr * dt;
                    if (p.y > innerHeight + 40) p.life = 0;
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rot);
                    ctx.globalAlpha = Math.max(p.life, 0);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                });
                if (alive > 0) requestAnimationFrame(step);
                else canvas.remove();
            }
            requestAnimationFrame(step);
        },
        burst: function (count) {
            if (REDUCED) return;
            // Upgrade to 3D Three.js celebration if available
            if (window.npcThree && typeof window.npcThree.celebrate3D === 'function' && window.npcThree.hasWebGL) {
                window.npcThree.celebrate3D({ count: count || 140 });
                return;
            }
            window.npcConfetti._burst2D(count);
        }
    };

    /* --------------------------------------------------------
       7. COMMAND PALETTE — Ctrl/Cmd + K quick navigation.
          Pages can extend with window.NPC_COMMANDS = [...]
       -------------------------------------------------------- */
    function buildCommands() {
        var cmds = [
            { label: 'My Dashboard', icon: 'dashboard', href: '/student/index.php' },
            { label: 'Courses & ELMS Tasks', icon: 'menu_book', href: '/student/courses.php' },
            { label: 'My Schedule', icon: 'calendar_month', href: '/student/schedule.php' },
            { label: 'Academic Performance & Grades', icon: 'trending_up', href: '/student/academic.php' },
            { label: 'Scan Attendance QR', icon: 'qr_code_scanner', href: '/student/qrcode.php' },
            { label: 'AI Study Tutor', icon: 'smart_toy', href: '/student/ai_assistant.php' },
            { label: 'Settings', icon: 'settings', href: '/student/settings.php' },
            { label: 'Sign Out', icon: 'logout', href: '/logout.php' }
        ];
        var body = document.body.textContent || '';
        if (/Faculty Portal|Grade Encoding|Assigned Classes|teacher/.test(window.location.pathname) || /Faculty Portal|Grade Encoding|Assigned Classes/.test(body)) {
            cmds.unshift(
                { label: 'Faculty Dashboard', icon: 'dashboard', href: '/teacher/index.php' },
                { label: 'ELMS Courses & Live Classes', icon: 'menu_book', href: '/teacher/courses.php' },
                { label: 'My Assigned Classes', icon: 'school', href: '/teacher/classes.php' },
                { label: 'Live Attendance QR', icon: 'qr_code_scanner', href: '/teacher/attendance.php' },
                { label: 'Grade Encoding', icon: 'grade', href: '/teacher/grades.php' },
                { label: 'Teaching Assistant AI', icon: 'support_agent', href: '/teacher/ai_assistant.php' }
            );
        }
        if (/Admin Portal|Administrative Master Panel|System Security|admin/.test(window.location.pathname) || /Admin Portal|Administrative Master Panel|System Security & Activity Logs/.test(body)) {
            cmds.unshift(
                { label: 'Admin Dashboard', icon: 'dashboard', href: '/admin/index.php' },
                { label: 'Master ELMS Hub', icon: 'menu_book', href: '/admin/academic/elms.php' },
                { label: 'Grade Approvals', icon: 'verified', href: '/admin/academic/grades.php' },
                { label: 'Classes & Rosters', icon: 'school', href: '/admin/academic/classes.php' },
                { label: 'Student Schedules', icon: 'calendar_month', href: '/admin/academic/schedules.php' },
                { label: 'Live Attendance QR', icon: 'qr_code_scanner', href: '/admin/academic/attendance.php' },
                { label: 'Student Directory', icon: 'group', href: '/admin/academic/students.php' },
                { label: 'User Management', icon: 'manage_accounts', href: '/admin/security/users.php' },
                { label: 'Security & Audit Logs', icon: 'security', href: '/admin/security/audit.php' },
                { label: 'Announcements', icon: 'campaign', href: '/admin/communication/announcements.php' },
                { label: 'Documents & AI Knowledge', icon: 'description', href: '/admin/communication/docs.php' },
                { label: 'System Settings', icon: 'settings', href: '/admin/system/settings.php' },
                { label: 'Reports & Analytics', icon: 'analytics', href: '/admin/system/reports.php' }
            );
        }
        /* de-dupe by href+label, page-provided extras first */
        var extra = Array.isArray(window.NPC_COMMANDS) ? window.NPC_COMMANDS : [];
        var seen = {};
        return extra.concat(cmds).filter(function (c) {
            var k = c.href + '|' + c.label;
            if (seen[k]) return false;
            seen[k] = true;
            return true;
        });
    }

    function openPalette() {
        var existing = document.getElementById('npc-cmdk');
        if (existing) { closePalette(); return; }
        var commands = buildCommands();
        var wrap = document.createElement('div');
        wrap.id = 'npc-cmdk';
        wrap.innerHTML =
            '<div class="npc-cmdk-panel">' +
            '  <div style="display:flex;align-items:center;gap:.6rem;padding:0 1rem;">' +
            '    <span class="material-symbols-outlined" style="color:rgb(var(--on-surface-variant-rgb));font-size:20px;">search</span>' +
            '    <input id="npc-cmdk-input" class="npc-cmdk-input" placeholder="Jump to… (type to search)" autocomplete="off" spellcheck="false">' +
            '    <span class="kbd">ESC</span>' +
            '  </div>' +
            '  <div id="npc-cmdk-list" class="npc-cmdk-list"></div>' +
            '</div>';
        document.body.appendChild(wrap);

        var list = wrap.querySelector('#npc-cmdk-list');
        var input = wrap.querySelector('#npc-cmdk-input');
        var active = 0;

        function render(filterText) {
            var q = (filterText || '').toLowerCase().trim();
            var items = commands.filter(function (c) {
                return !q || c.label.toLowerCase().indexOf(q) !== -1;
            });
            active = 0;
            if (!items.length) {
                list.innerHTML = '<div style="padding:1.2rem;text-align:center;color:rgb(var(--on-surface-variant-rgb));font-size:.85rem;">No matches found</div>';
                return;
            }
            list.innerHTML = items.map(function (c, i) {
                return '<button type="button" class="npc-cmdk-item' + (i === 0 ? ' active' : '') + '" data-href="' + c.href + '">' +
                    '<span class="material-symbols-outlined">' + c.icon + '</span><span>' + c.label + '</span>' +
                    '<span style="margin-left:auto;font-family:\'JetBrains Mono\',monospace;font-size:10px;color:rgb(var(--outline-rgb));">' + c.href.replace('.php', '') + '</span>' +
                    '</button>';
            }).join('');
            Array.prototype.forEach.call(list.children, function (btn, i) {
                btn.addEventListener('click', function () { location.href = btn.getAttribute('data-href'); });
                btn.addEventListener('pointerenter', function () { setActive(i); });
            });
        }

        function setActive(i) {
            active = i;
            Array.prototype.forEach.call(list.querySelectorAll('.npc-cmdk-item'), function (b, bi) {
                b.classList.toggle('active', bi === i);
            });
        }

        input.addEventListener('input', function () { render(input.value); });
        input.addEventListener('keydown', function (e) {
            var items = list.querySelectorAll('.npc-cmdk-item');
            if (e.key === 'ArrowDown') { e.preventDefault(); if (items.length) setActive(Math.min(active + 1, items.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); if (items.length) setActive(Math.max(active - 1, 0)); }
            else if (e.key === 'Enter') { e.preventDefault(); var it = items[active]; if (it) location.href = it.getAttribute('data-href'); }
            else if (e.key === 'Escape') { closePalette(); }
        });
        wrap.addEventListener('click', function (e) { if (e.target === wrap) closePalette(); });

        render('');
        setTimeout(function () { input.focus(); }, 30);
    }

    function closePalette() {
        var el = document.getElementById('npc-cmdk');
        if (el) el.remove();
    }
    window.npcClosePalette = closePalette;

    function isMainDashboard() {
        var p = (window.location.pathname || '').toLowerCase();
        var clean = p.replace(/\/+$/, '');
        return clean.endsWith('/student') || clean.endsWith('/student/index.php') ||
               clean.endsWith('/teacher') || clean.endsWith('/teacher/index.php') ||
               clean.endsWith('/admin')   || clean.endsWith('/admin/index.php');
    }

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            if (!isMainDashboard()) return; // Search palette only active on main dashboards
            e.preventDefault();
            openPalette();
        } else if (e.key === 'Escape') {
            closePalette();
        }
    });

    /* Add a subtle "Ctrl+K" hint chip into #topbar when present (only on main dashboards) */
    function mountSearchHint() {
        if (!isMainDashboard()) return;
        var bar = document.getElementById('topbar') || document.querySelector('header');
        if (!bar || document.getElementById('npc-search-hint')) return;
        var clusters = bar.querySelectorAll(':scope > div');
        var target = clusters.length ? clusters[clusters.length - 1] : bar;
        var hint = document.createElement('button');
        hint.id = 'npc-search-hint';
        hint.type = 'button';
        hint.className = 'hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-outline-variant bg-surface-container-low text-on-surface-variant text-xs font-semibold hover:bg-surface-container transition-colors cursor-pointer';
        hint.innerHTML = '<span class="material-symbols-outlined" style="font-size:15px;">search</span><span>Search</span><span class="kbd">Ctrl K</span>';
        hint.addEventListener('click', openPalette);
        target.insertBefore(hint, target.firstChild);
    }

    /* --------------------------------------------------------
       7b. SHARED CHROME — theme toggle, role badge, denied banner.
           Works on every portal page without touching markup.
       -------------------------------------------------------- */
    var ROLE_META = {
        student: { label: 'Student', icon: 'school', cls: 'bg-surface-container text-primary border-outline-variant' },
        teacher: { label: 'Faculty', icon: 'cast_for_education', cls: 'bg-secondary-container text-on-secondary-container border-secondary-container' },
        admin:   { label: 'Admin', icon: 'shield_person', cls: 'bg-error/10 text-error border-error/30' }
    };

    function mountThemeToggle() {
        if (document.getElementById('npc-theme-toggle') || document.getElementById('npc-theme-toggle-m')) return;

        var isDark = function () { return document.documentElement.classList.contains('dark'); };
        var btn = document.createElement('button');
        btn.id = 'npc-theme-toggle-m';
        btn.type = 'button';
        btn.setAttribute('data-tip', 'Toggle night mode');
        btn.setAttribute('data-tip-pos', 'down');
        btn.setAttribute('aria-label', 'Toggle night mode');
        btn.className = 'press ripple';
        btn.innerHTML =
            '<span class="npc-theme-orb" style="width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;' +
            'border-radius:9999px;border:1px solid rgb(var(--outline-variant-rgb));' +
            'background:rgb(var(--surface-container-low-rgb));color:rgb(var(--on-surface-variant-rgb));cursor:pointer;' +
            'transition:transform .25s cubic-bezier(.34,1.56,.64,1);">' +
            '<span class="material-symbols-outlined" style="font-size:19px;">' + (isDark() ? 'light_mode' : 'dark_mode') + '</span></span>';

        function paint() {
            btn.querySelector('.material-symbols-outlined').textContent = isDark() ? 'light_mode' : 'dark_mode';
        }

        btn.addEventListener('click', function () {
            var html = document.documentElement;
            html.classList.add('theme-anim');
            html.classList.toggle('dark');
            try { localStorage.setItem('npc-theme', isDark() ? 'dark' : 'light'); } catch (e) {}
            paint();
            setTimeout(function () { html.classList.remove('theme-anim'); }, 500);
        });

        /* Strategy 1 — topbar cluster (student pages & some headers) */
        var bar = document.getElementById('topbar');
        var placed = false;
        if (bar) {
            var clusters = bar.querySelectorAll(':scope > div');
            var target = clusters.length ? clusters[clusters.length - 1] : bar;
            target.insertBefore(btn, target.firstChild);
            placed = true;
        }
        /* Strategy 2 — generic header with right-side flex group */
        if (!placed) {
            var hdrs = document.querySelectorAll('header');
            for (var i = 0; i < hdrs.length; i++) {
                var flexes = hdrs[i].querySelectorAll(':scope > div:last-child, :scope > div.flex.items-center.gap-3, :scope > div.flex.items-center.gap-4');
                if (flexes.length) {
                    flexes[flexes.length - 1].insertBefore(btn, flexes[flexes.length - 1].firstChild);
                    placed = true;
                    break;
                }
                if (hdrs[i].classList.contains('flex') && hdrs[i].className.indexOf('justify-between') !== -1) {
                    hdrs[i].appendChild(btn);
                    placed = true;
                    break;
                }
                /* bare header (no inner divs) */
                if (!hdrs[i].querySelector(':scope > div')) {
                    hdrs[i].appendChild(btn);
                    placed = true;
                    break;
                }
            }
        }
        if (!placed) return; /* nothing suitable on this page */
    }

    function mountRoleBadge() {
        var bar = document.getElementById('topbar');
        var metaEl = document.getElementById('npc-role-meta');
        if (!bar || !metaEl || bar.querySelector('.npc-role-chip')) return;
        var meta = {};
        try { meta = JSON.parse(metaEl.textContent || '{}'); } catch (e) {}
        var role = meta.role === 'teacher' ? 'teacher' : (meta.role === 'admin' ? 'admin' : 'student');
        var m = ROLE_META[role];
        var chip = document.createElement('span');
        chip.className = 'npc-role-chip hidden sm:inline-flex border ' + m.cls;
        chip.title = (meta.email || '') + ' — signed in via NPC Gmail SSO';
        chip.innerHTML = '<span class="material-symbols-outlined" style="font-size:13px;">' + m.icon + '</span>' + m.label;
        var clusters = bar.querySelectorAll(':scope > div');
        var target = clusters.length ? clusters[clusters.length - 1] : bar;
        target.insertBefore(chip, target.firstChild);
    }

    function mountDeniedBanner() {
        if (!document.getElementById('npc-denied-banner') || document.getElementById('npc-denied-banner-host')) return;
        var host = document.createElement('div');
        host.id = 'npc-denied-banner-host';
        host.style.cssText = 'position:relative;z-index:70;';
        host.style.width = '100%';
        fetch('_denied_banner.php').then(function (r) { return r.text(); }).then(function (html) {
            host.innerHTML = html;
            var main = document.querySelector('main') || document.body;
            main.insertBefore(host, main.firstChild);
        }).catch(function () {});
    }

    window.npcChrome = {
        refresh: function () { mountThemeToggle(); mountRoleBadge(); }
    };

    /* --------------------------------------------------------
       8. BOOTSTRAP on DOM ready
       -------------------------------------------------------- */
    /* --------------------------------------------------------
       7c. UNIVERSAL ENTRANCE CHOREOGRAPHY
           Staggers cards/sections/rows on every page load and on
           dynamic content injection. Skips chat bubbles & toasts.
       -------------------------------------------------------- */
    var __choreoBusy = false;
    function choreograph(root) {
        root = root || document;
        if (REDUCED || __choreoBusy) return;
        __choreoBusy = true;
        setTimeout(function () { __choreoBusy = false; }, 200);
                var sel = [
            'main section:not(.npc-choreo)', 'main > div > section:not(.npc-choreo)',
            '.grid > .npc-card:not(.npc-choreo)', 'section.grid > div:not(.npc-choreo)',
            'table tbody tr:not(.npc-choreo)', '.stagger > *:not(.npc-choreo)'
        ].join(',');
        /* skip delicate zones: excel grid, chat streams, toasts, modals */
        sel = sel.split(',').map(function (s) { return s + ':not(.no-choreo)'; }).join(',');
        var nodes = root.querySelectorAll(sel);
        var delay = 0;
        for (var i = 0; i < nodes.length && i < 24; i++) {
            (function (el, d) {
                el.classList.add('npc-choreo');
                el.style.opacity = '0';
                el.style.transform = 'translateY(14px)';
                setTimeout(function () {
                    el.style.transition = 'opacity .5s cubic-bezier(.22,.68,.32,1), transform .5s cubic-bezier(.22,.68,.32,1)';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 40 + d);
            })(nodes[i], delay);
            delay += 55;
        }
    }
    window.npcChoreograph = choreograph;

    /* --------------------------------------------------------
       7d. PORTAL SWITCHER — removed globally per user request.
       -------------------------------------------------------- */
    function mountPortalSwitcher() {
        var existing = document.getElementById('npc-portal-switch');
        if (existing) existing.remove();
    }

    /* --------------------------------------------------------
       7e. MOBILE DRAWER — shared sidebar works on phones too.
           Injects a hamburger button into the page header if not present.
        -------------------------------------------------------- */
    function mountMobileDrawer() {
        var sidebar = document.getElementById('npc-sidebar');
        var overlay = document.getElementById('npc-drawer-overlay');
        if (!sidebar) return;

        /* Make sidebar drawer-capable on small screens */
        sidebar.classList.add('npc-drawer');
        if (!document.getElementById('npc-drawer-style')) {
            var st = document.createElement('style');
            st.id = 'npc-drawer-style';
            st.textContent =
                '@media(max-width:1023px){' +
                '  #npc-sidebar.npc-drawer{display:flex !important;transform:translateX(-100%) !important;transition:transform .3s cubic-bezier(.22,.68,.32,1) !important;z-index:50 !important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.7) !important;}' +
                '  #npc-sidebar.npc-drawer.open{transform:translateX(0) !important;}' +
                '}' +
                '@media(min-width:1024px){#npc-sidebar.npc-drawer{transform:none !important;}}';
            document.head.appendChild(st);
        }

        /* Phone UX: close the drawer after choosing a destination */
        Array.prototype.forEach.call(sidebar.querySelectorAll('a'), function (a) {
            a.addEventListener('click', function () {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.add('hidden');
            });
        });
        if (overlay) overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { sidebar.classList.remove('open'); if (overlay) overlay.classList.add('hidden'); }
        });

        /* Guard: If a mobile hamburger toggle button already exists, do NOT inject a duplicate! */
        var existingBtn = document.getElementById('npc-mobile-menu-btn') || 
                          document.querySelector('button[onclick*="toggleNpcSidebar"]') ||
                          document.querySelector('button[aria-label="Open Navigation"]') ||
                          document.querySelector('header button.lg\\:hidden');
        if (existingBtn) {
            if (!existingBtn.id) existingBtn.id = 'npc-mobile-menu-btn';
            return;
        }

        /* Hamburger — place in first header found if no button exists */
        var hdr = document.querySelector('header');
        if (!hdr) return;
        var btn = document.createElement('button');
        btn.id = 'npc-mobile-menu-btn';
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Open Navigation');
        btn.className = 'lg:hidden p-2 rounded-xl text-primary hover:bg-surface-container transition-colors cursor-pointer';
        btn.innerHTML = '<span class="material-symbols-outlined text-[24px]">menu</span>';
        btn.addEventListener('click', function () {
            if (typeof window.toggleNpcSidebar === 'function') {
                window.toggleNpcSidebar();
            } else {
                sidebar.classList.add('open');
                if (overlay) overlay.classList.remove('hidden');
            }
        });
        hdr.insertBefore(btn, hdr.firstChild);
    }

    /* --------------------------------------------------------
       7f. UNIVERSAL NOTIFICATION CENTER
           Bell on every portal page. Feed = published announcements
           (api_notifications.php). Read/unread tracked per user
           in localStorage. Falls back gracefully if API offline.
       -------------------------------------------------------- */
    function mountNotificationCenter() {
        if (document.getElementById('npc-notif-center')) return;
        var hdr = document.getElementById('topbar') || document.querySelector('header');
        if (!hdr) return;

        var meta = {};
        var metaEl = document.getElementById('npc-role-meta');
        try { meta = JSON.parse((metaEl && metaEl.textContent) || '{}'); } catch (e) {}
        var userKey = 'npc-notif-read-' + (meta.email || 'anon');

        var wrap = document.createElement('div');
        wrap.id = 'npc-notif-center';
        wrap.className = 'relative';
        wrap.innerHTML =
            '<button type="button" id="npc-notif-btn" data-tip="Notifications" data-tip-pos="down" aria-label="Notifications"' +
            ' class="relative p-2 rounded-full border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container transition-colors cursor-pointer press" style="overflow:visible;">' +
            '<span class="material-symbols-outlined" style="font-size:19px;">notifications</span>' +
            '<span id="npc-notif-badge" class="npc-badge-pill hidden">0</span>' +
            '</button>' +
            '<div id="npc-notif-panel" class="npc-popover hidden" style="min-width:340px; z-index:100;">' +
            '<div class="npc-popover-arrow"></div>' +
            '<div class="px-4 py-3 border-b border-outline-variant/60 flex items-center justify-between bg-surface-subtle">' +
            '<p class="text-sm font-bold text-primary">Notifications</p>' +
            '<button id="npc-notif-markall" class="text-[11px] font-mono font-bold text-on-surface-variant hover:text-primary transition-colors cursor-pointer">MARK ALL READ</button>' +
            '</div>' +
            '<div id="npc-notif-list" class="max-h-96 overflow-y-auto divide-y divide-outline-variant/40 custom-scroll">' +
            '<div class="p-5 text-center text-xs text-on-surface-variant animate-pulse">Loading…</div>' +
            '</div>' +
            '<div class="px-4 py-2 text-center bg-surface-subtle border-t border-outline-variant/60">' +
            '<span class="text-[10px] font-mono text-outline">Campus announcements &amp; personal updates</span>' +
            '</div></div>';

        /* place before the theme toggle cluster start */
        var clusters = hdr.querySelectorAll(':scope > div');
        var target = clusters.length ? clusters[clusters.length - 1] : hdr;
        target.insertBefore(wrap, target.firstChild);

        var btn = wrap.querySelector('#npc-notif-btn');
        var panel = wrap.querySelector('#npc-notif-panel');
        var badge = wrap.querySelector('#npc-notif-badge');
        var list = wrap.querySelector('#npc-notif-list');
        var ITEMS = [];
        var readMap = {};
        try { readMap = JSON.parse(localStorage.getItem(userKey) || '{}'); } catch (e) { readMap = {}; }

        function saveRead() {
            try { localStorage.setItem(userKey, JSON.stringify(readMap)); } catch (e) {}
        }
        function unreadCount() {
            var n = 0;
            ITEMS.forEach(function (it) { if (!readMap[it.id]) n++; });
            return n;
        }
        function paintBadge() {
            var n = unreadCount();
            if (n > 0) { 
                badge.textContent = n > 9 ? '9+' : String(n); 
                badge.classList.remove('hidden'); 
            } else {
                badge.classList.add('hidden');
            }
        }
        function timeAgo(iso) {
            var s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
            if (isNaN(s)) return '';
            if (s < 60) return 'just now';
            if (s < 3600) return Math.floor(s / 60) + 'm ago';
            if (s < 86400) return Math.floor(s / 3600) + 'h ago';
            return Math.floor(s / 86400) + 'd ago';
        }
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

        function renderList() {
            if (!ITEMS.length) {
                list.innerHTML = '<div class="p-6 text-center flex flex-col items-center gap-2">' +
                    '<span class="material-symbols-outlined text-[30px] text-outline-variant">notifications_off</span>' +
                    '<p class="text-xs text-on-surface-variant">No notifications yet.</p></div>';
                return;
            }
            list.innerHTML = ITEMS.map(function (a) {
                var unread = !readMap[a.id];
                /* Icon per source/category: docs, grades, alerts, academics */
                var icon = a.category === 'emergency' ? 'warning'
                    : a.category === 'academic' ? 'school'
                    : a.category === 'document' ? 'description'
                    : a.category === 'grade' ? 'military_tech'
                    : a.kind === 'personal' ? 'info'
                    : 'campaign';
                var color = a.category === 'emergency' ? 'text-error'
                    : a.category === 'document' ? 'text-status-info'
                    : a.category === 'grade' ? 'text-secondary'
                    : a.category === 'academic' ? 'text-secondary'
                    : a.kind === 'personal' ? 'text-primary'
                    : 'text-status-info';
                return '<div class="npc-notif-item flex items-start gap-3 px-4 py-3 hover:bg-surface-container-low/60 transition-colors cursor-pointer' + (unread ? ' bg-primary/5' : '') + '" data-id="' + esc(a.id) + '"' + (a.link ? ' data-link="' + esc(a.link) + '"' : '') + '>' +
                    '<span class="material-symbols-outlined ' + color + ' text-[19px] mt-0.5 shrink-0">' + icon + '</span>' +
                    '<div class="min-w-0 flex-1 overflow-hidden">' +
                    '<p class="text-xs font-bold text-on-surface break-words leading-tight">' + (unread ? '<span class="inline-block w-1.5 h-1.5 rounded-full bg-error mr-1.5 align-middle"></span>' : '') + esc(a.title) + '</p>' +
                    '<p class="text-[11px] text-on-surface-variant break-words leading-snug mt-1" style="overflow-wrap:anywhere;">' + esc(a.excerpt) + '</p>' +
                    '<p class="text-[10px] font-mono text-outline mt-1">' + timeAgo(a.created_at) + (a.link ? ' · <span class="text-primary">open &rarr;</span>' : '') + '</p>' +
                    '</div></div>';
            }).join('');
            Array.prototype.forEach.call(list.querySelectorAll('.npc-notif-item'), function (el) {
                el.addEventListener('click', function () {
                    readMap[el.getAttribute('data-id')] = 1;
                    saveRead();
                    paintBadge();
                    el.classList.remove('bg-primary/5');
                    var dot = el.querySelector('.bg-error.rounded-full');
                    if (dot) dot.remove();
                    /* Personal notifications deep-link to their portal page */
                    var link = el.getAttribute('data-link');
                    if (link) { panel.classList.add('hidden'); window.location.href = link; }
                });
            });
        }

        function load() {
            /* Two sources merged into ONE feed: campus announcements
               (api_notifications.php) + personal notifications
               (api_student.php — grades published, document status,
               attendance, system). Personal entries carry a link_url so
               clicking jumps to the relevant portal page. */
            var annPromise = fetch('/api/notifications.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .catch(function () { return null; });
            var perPromise = fetch('/api/student.php?action=get_notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .catch(function () { return null; });

            Promise.all([annPromise, perPromise]).then(function (results) {
                var ann = (results[0] && results[0].notifications) || [];
                var per = (results[1] && results[1].notifications) || [];
                ITEMS = ann.map(function (a) {
                    return {
                        id: String(a.id),
                        title: a.title,
                        excerpt: a.excerpt,
                        created_at: a.created_at,
                        category: a.category,
                        kind: 'announcement',
                        link: ''
                    };
                }).concat(per.map(function (n) {
                    return {
                        id: 'ntf-' + n.id,
                        title: n.title,
                        excerpt: n.message,
                        created_at: n.created_at,
                        category: n.type,
                        kind: 'personal',
                        link: n.link_url || ''
                    };
                }));
                ITEMS.sort(function (x, y) { return new Date(y.created_at || 0) - new Date(x.created_at || 0); });
                renderList();
                paintBadge();
            });
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isHidden = panel.classList.toggle('hidden');
            wrap.classList.toggle('is-open', !isHidden);
            if (!isHidden) {
                // Ensure popover does not clip outside left edge of small viewports
                var rect = panel.getBoundingClientRect();
                if (rect.left < 10) {
                    panel.style.right = 'auto';
                    panel.style.left = '0';
                    var arrow = panel.querySelector('.npc-popover-arrow');
                    if (arrow) { arrow.style.right = 'auto'; arrow.style.left = '16px'; }
                }
                load();
            }
        });
        wrap.querySelector('#npc-notif-markall').addEventListener('click', function () {
            ITEMS.forEach(function (it) { readMap[it.id] = 1; });
            saveRead();
            paintBadge();
            renderList();
        });
        document.addEventListener('click', function (e) {
            if (!panel.classList.contains('hidden') && !wrap.contains(e.target)) {
                panel.classList.add('hidden');
                wrap.classList.remove('is-open');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                panel.classList.add('hidden');
                wrap.classList.remove('is-open');
            }
        });
        /* preload quietly & poll for live notifications */
        setTimeout(load, 800);
        setInterval(load, 10000);
    }

    function mountGlobalLiveClassWatcher() {
        var path = window.location.pathname.toLowerCase();
        // Only run for student portal pages
        if (!path.includes('/student/')) return;

        var LAST_GLOBAL_LIVE_SESSION_KEY = '';
        var floatingBar = null;

        function playLiveClassChime() {
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                osc.start();
                osc.stop(ctx.currentTime + 0.42);
            } catch(e) {}
        }

        async function pollGlobalLiveClass() {
            try {
                var res = await fetch('/api/elms.php?action=get_live_sessions');
                var data = await res.json();
                if (data.success && data.live_sessions && data.live_sessions.length > 0) {
                    var s = data.live_sessions[0];
                    var sessionKey = s.course_code + '_' + (s.session_code || '') + '_' + (s.is_attendance_locked ? '1' : '0');
                    var isNew = (LAST_GLOBAL_LIVE_SESSION_KEY !== sessionKey);
                    LAST_GLOBAL_LIVE_SESSION_KEY = sessionKey;

                    // If user is already on student/courses.php, let the dedicated page handler manage the UI
                    if (path.includes('/student/courses.php')) {
                        if (floatingBar) {
                            floatingBar.remove();
                            floatingBar = null;
                        }
                        return;
                    }

                    if (!floatingBar) {
                        floatingBar = document.createElement('div');
                        floatingBar.id = 'npc-global-live-bar';
                        floatingBar.className = 'fixed top-20 right-4 sm:right-6 z-50 max-w-md rounded-2xl bg-slate-950/95 border-2 border-red-500 text-white shadow-2xl p-4 backdrop-blur-md animate-fade-in flex items-center justify-between gap-4 ring-4 ring-red-500/20';
                        document.body.appendChild(floatingBar);
                    }

                    floatingBar.innerHTML = '<div class="flex items-center gap-3 min-w-0">' +
                        '<div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center shrink-0 animate-pulse">' +
                        '<span class="material-symbols-outlined text-[20px]">sensors</span>' +
                        '</div>' +
                        '<div class="min-w-0">' +
                        '<div class="flex items-center gap-1.5">' +
                        '<span class="px-2 py-0.2 rounded-full font-mono text-[9px] font-bold uppercase bg-red-600 text-white animate-pulse">LIVE CLASS</span>' +
                        '<span class="font-mono text-[11px] font-bold text-red-400">' + escA(s.course_code) + '</span>' +
                        '</div>' +
                        '<p class="text-xs font-semibold text-slate-100 truncate mt-0.5">' + escA(s.course_title) + '</p>' +
                        '<p class="text-[10px] text-slate-400 truncate">Sec ' + escA(s.section || '2A') + ' · ' + escA(s.instructor || 'Faculty') + '</p>' +
                        '</div>' +
                        '</div>' +
                        '<div class="flex items-center gap-2 shrink-0">' +
                        '<a href="/student/courses.php?join_course=' + encodeURIComponent(s.course_code) + '" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all shadow-sm inline-flex items-center gap-1 cursor-pointer">' +
                        '<span class="material-symbols-outlined text-[15px]">videocam</span> Enter' +
                        '</a>' +
                        '<button type="button" onclick="this.closest(\'#npc-global-live-bar\').classList.add(\'hidden\')" class="p-1 text-slate-400 hover:text-white rounded-lg cursor-pointer" title="Dismiss">' +
                        '<span class="material-symbols-outlined text-[16px]">close</span>' +
                        '</button>' +
                        '</div>';

                    floatingBar.classList.remove('hidden');

                    if (isNew) {
                        playLiveClassChime();
                        if (window.notify) {
                            window.notify('🔴 LIVE ONLINE CLASS: ' + s.course_code + ' started. Click to join!', 'success');
                        }
                    }
                } else {
                    if (LAST_GLOBAL_LIVE_SESSION_KEY !== '') {
                        if (window.notify) {
                            window.notify('📢 The live online class session has ended.', 'info');
                        }
                    }
                    LAST_GLOBAL_LIVE_SESSION_KEY = '';
                    if (floatingBar) {
                        floatingBar.remove();
                        floatingBar = null;
                    }
                }
            } catch (err) {}
        }

        setTimeout(pollGlobalLiveClass, 1200);
        setInterval(pollGlobalLiveClass, 3500);
    }

    function ensureThree(cb) {
        if (window.npcThree) {
            if (cb) cb();
            return;
        }
        function loadScript(src, next) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = next;
            s.onerror = function () {};
            document.head.appendChild(s);
        }
        if (typeof THREE === 'undefined') {
            loadScript('/assets/js/three.min.js', function () {
                loadScript('/assets/js/npc-three.js', function () {
                    if (cb) cb();
                });
            });
        } else {
            loadScript('/assets/js/npc-three.js', function () {
                if (cb) cb();
            });
        }
    }

    function mountLiveDateAndClock() {
        function tick() {
            var now = new Date();
            var dateEls = document.querySelectorAll('#current-date, .npc-current-date');
            var dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            dateEls.forEach(function (el) {
                if (el && el.textContent !== dateStr) el.textContent = dateStr;
            });

            var clockEls = document.querySelectorAll('#npc-live-clock, .npc-live-clock');
            var timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit' });
            clockEls.forEach(function (el) {
                if (el) el.textContent = timeStr;
            });
        }
        tick();
        setInterval(tick, 1000);
    }

    function boot() {
        runAnimations(document);
        document.querySelectorAll('.tilt, .npc-tile, .npc-card').forEach(bindTilt);
        mountSearchHint();
        try { mountLiveDateAndClock(); } catch (e) {}
        try { mountThemeToggle(); } catch (e) {}
        try { mountRoleBadge(); } catch (e) {}
        try { mountPortalSwitcher(); } catch (e) {}
        try { mountMobileDrawer(); } catch (e) {}
        try { mountNotificationCenter(); } catch (e) {}
        try { mountGlobalLiveClassWatcher(); } catch (e) {}
        try {
            if (window.npcThree && typeof window.npcThree.initBackground === 'function') {
                // Only mount ambient background on dashboard/content pages (not on login page which has its own hero scene)
                if (!document.getElementById('npc-login-card')) {
                    window.npcThree.initBackground();
                }
                if (typeof window.npcThree.initAuto3DIcons === 'function') {
                    window.npcThree.initAuto3DIcons();
                }
            } else {
                ensureThree(function () {
                    if (window.npcThree && typeof window.npcThree.initBackground === 'function' && !document.getElementById('npc-login-card')) {
                        window.npcThree.initBackground();
                    }
                    if (window.npcThree && typeof window.npcThree.initAuto3DIcons === 'function') {
                        window.npcThree.initAuto3DIcons();
                    }
                });
            }
        } catch (e) {}
        setTimeout(function () { choreograph(document); }, 30);
        /* NOTE: no MutationObserver here on purpose — grids/chats rebuild DOM
           constantly and re-animating causes flicker. Pages that render
           dynamic sections can call window.npcChoreograph() explicitly. */
    }

/* ============================================================
   SKELETON BUILDER — window.npcSkeleton(kind, count)
   Reusable loading placeholders. kinds:
     'rows'    → list rows with avatar+lines (feeds, rosters)
     'cards'   → stat/feature cards grid
     'table'   → table-like rows (grades, users)
     'lines'   → simple text lines (announcements, details)
   ============================================================ */
window.npcSkeleton = function (kind, count) {
    kind = kind || 'rows';
    count = Math.max(1, Math.min(12, parseInt(count) || 3));
    var sk = function (cls, style) {
        return '<div class="skeleton ' + cls + '"' + (style ? ' style="' + style + '"' : '') + '></div>';
    };
    var out = '';
    if (kind === 'rows') {
        for (var i = 0; i < count; i++) {
            out += '<div class="sk-row">' + sk('sk-avatar')
                + '<div class="flex-1 min-w-0">' + sk('sk-line w-2/3') + sk('sk-line w-1/2', 'margin-bottom:0') + '</div></div>';
        }
    } else if (kind === 'cards') {
        out += '<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
        for (var j = 0; j < count; j++) {
            out += '<div class="sk-card rounded-2xl">' + sk('sk-chip', 'margin-bottom:.6rem') + sk('sk-title', 'width:70%;margin-bottom:0') + '</div>';
        }
        out += '</div>';
    } else if (kind === 'table') {
        for (var k = 0; k < count; k++) {
            out += '<div class="sk-table-row">' + sk('sk-line', 'margin-bottom:0') + sk('sk-line w-3/4', 'margin-bottom:0')
                + sk('sk-chip') + sk('sk-chip', 'width:2.5rem') + '</div>';
        }
    } else { /* lines */
        for (var n = 0; n < count; n++) {
            out += sk('sk-line ' + (n % 2 ? 'w-1/2' : 'w-3/4'));
        }
        out += sk('sk-line w-1/2', 'margin-bottom:0');
    }
    return '<div class="npc-skeleton-group" aria-busy="true" aria-live="polite">' + out + '</div>';
};

/* Auto-swap any element marked data-skeleton="kind:count" on load */
(function () {
    var apply = function () {
        document.querySelectorAll('[data-skeleton]:not([data-skeleton-done])').forEach(function (el) {
            el.setAttribute('data-skeleton-done', '1');
            var parts = (el.getAttribute('data-skeleton') || 'rows:3').split(':');
            el.innerHTML = window.npcSkeleton(parts[0], parseInt(parts[1]));
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply);
    else apply();
})();

/* ============================================================
   SKELETON BUILDER END
   ============================================================ */

/* Global HTML attribute & content sanitization helper */
window.escA = function (s) {
    if (s === null || s === undefined) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
};
window.escapeHtml = window.escA;

/* ============================================================
   NPC TOP PROGRESS BAR & NAVIGATION HELPERS
   Provides snappy visual feedback on screen changes while
   ensuring full multi-page PHP script compatibility.
   ============================================================ */
var npcInstant = (function () {
    var topLoader = null;

    function getTopLoader() {
        if (!topLoader && document.body) {
            topLoader = document.getElementById('npc-top-loader');
            if (!topLoader) {
                topLoader = document.createElement('div');
                topLoader.id = 'npc-top-loader';
                topLoader.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg,#f59e0b,#fed488,#38bdf8);z-index:99999;transition:width 0.22s cubic-bezier(0.1,0.9,0.2,1),opacity 0.25s ease;pointer-events:none;box-shadow:0 0 10px rgba(254,212,136,0.7);';
                document.body.appendChild(topLoader);
            }
        }
        return topLoader;
    }

    function startProgress() {
        var l = getTopLoader();
        if (!l) return;
        l.style.opacity = '1';
        l.style.width = '35%';
        setTimeout(function () { if (l && l.style.opacity === '1') l.style.width = '80%'; }, 100);
    }

    function finishProgress() {
        var l = getTopLoader();
        if (!l) return;
        l.style.width = '100%';
        setTimeout(function () {
            l.style.opacity = '0';
            setTimeout(function () { if (l) l.style.width = '0%'; }, 250);
        }, 120);
    }

    function isEligibleUrl(urlStr) {
        try {
            var loc = window.location;
            var u = new URL(urlStr, loc.href);
            if (u.origin !== loc.origin) return false;
            if (u.pathname === loc.pathname && u.search === loc.search) return false;
            if (u.hash && u.pathname === loc.pathname) return false;
            var p = u.pathname.toLowerCase();
            if (p.includes('/logout.php') || p.includes('/switch_portal.php') || p.includes('/dev_login.php')) return false;
            if (/\.(pdf|zip|xlsx|csv|jpg|jpeg|png|gif|svg|mp4|webm)$/i.test(p)) return false;
            return true;
        } catch (e) {
            return false;
        }
    }

    function navigate(urlStr) {
        startProgress();
        window.location.href = urlStr;
    }

    function prefetch() {
        // Safe no-op
    }

    function init() {
        finishProgress();

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented) return;
            if (e.button !== 0) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var a = e.target.closest('a[href]');
            if (!a) return;

            if (a.hasAttribute('download') || a.getAttribute('target') === '_blank') return;

            var href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            if (isEligibleUrl(a.href)) {
                startProgress();
            }
        });
    }

    return {
        init: init,
        prefetch: prefetch,
        navigate: navigate
    };
})();
/* ============================================================
   15. NPC COLLEGIATE MODAL DIALOGS (Universal Confirm & Alert)
   Replaces browser native "localhost:8000 says" with styled UI.
   ============================================================ */
var npcModal = (function() {
    var activeResolve = null;
    var overlay = null;

    function createOverlay() {
        if (overlay && document.body.contains(overlay)) return overlay;
        overlay = document.createElement('div');
        overlay.id = 'npc-modal-dialog-overlay';
        overlay.className = 'fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-200';
        overlay.innerHTML = 
            '<div id="npc-modal-dialog-card" class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl max-w-md w-full p-6 text-left transform scale-95 transition-all duration-200 relative overflow-hidden" style="max-height: 90vh; display: flex; flex-direction: column;">' +
            '    <div class="flex items-start gap-4">' +
            '        <div id="npc-modal-icon-wrap" class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0">' +
            '            <span id="npc-modal-icon" class="material-symbols-outlined text-[26px]">help</span>' +
            '        </div>' +
            '        <div class="flex-1 min-w-0">' +
            '            <h3 id="npc-modal-title" class="text-base font-bold text-on-surface"></h3>' +
            '            <div id="npc-modal-message" class="text-xs text-on-surface-variant mt-2 leading-relaxed whitespace-pre-wrap break-words"></div>' +
            '        </div>' +
            '    </div>' +
            '    <div id="npc-modal-actions" class="flex items-center justify-end gap-2.5 mt-6 pt-4 border-t border-outline-variant/60">' +
            '        <button type="button" id="npc-modal-btn-cancel" class="px-4 py-2.5 rounded-xl text-xs font-semibold border border-outline-variant text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer">Cancel</button>' +
            '        <button type="button" id="npc-modal-btn-confirm" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-sm transition-all cursor-pointer flex items-center gap-1.5"></button>' +
            '    </div>' +
            '</div>';
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                close(false);
            }
        });

        window.addEventListener('keydown', function(e) {
            if (!overlay || overlay.classList.contains('pointer-events-none')) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                close(false);
            } else if (e.key === 'Enter' && !e.shiftKey) {
                var confirmBtn = document.getElementById('npc-modal-btn-confirm');
                if (confirmBtn && document.activeElement !== document.getElementById('npc-modal-btn-cancel')) {
                    e.preventDefault();
                    close(true);
                }
            }
        });

        return overlay;
    }

    function close(result) {
        if (!overlay) return;
        overlay.classList.add('opacity-0', 'pointer-events-none');
        var card = document.getElementById('npc-modal-dialog-card');
        if (card) card.classList.add('scale-95');
        if (activeResolve) {
            var r = activeResolve;
            activeResolve = null;
            r(result);
        }
    }

    function confirm(optionsOrMsg) {
        return new Promise(function(resolve) {
            activeResolve = resolve;
            var opts = typeof optionsOrMsg === 'string' ? { message: optionsOrMsg } : (optionsOrMsg || {});
            var msg = opts.message || opts.text || '';
            var title = opts.title || (opts.type === 'danger' || /delete|remove|clear|terminate|end/i.test(msg) ? 'Confirm Action' : 'Are You Sure?');
            var type = opts.type;
            if (!type) {
                if (/delete|remove|clear|cancel|drop|terminate|end live/i.test(msg + ' ' + title)) {
                    type = 'danger';
                } else if (/warning|caution|conflict|strike|suspend/i.test(msg + ' ' + title)) {
                    type = 'warning';
                } else if (/approve|publish|submit|save|create/i.test(msg + ' ' + title)) {
                    type = 'success';
                } else {
                    type = 'info';
                }
            }

            var confirmText = opts.confirmText || (type === 'danger' ? 'Yes, Delete' : (type === 'success' ? 'Confirm' : 'Proceed'));
            var cancelText = opts.cancelText || 'Cancel';

            createOverlay();

            var iconWrap = document.getElementById('npc-modal-icon-wrap');
            var icon = document.getElementById('npc-modal-icon');
            var titleEl = document.getElementById('npc-modal-title');
            var msgEl = document.getElementById('npc-modal-message');
            var cancelBtn = document.getElementById('npc-modal-btn-cancel');
            var confirmBtn = document.getElementById('npc-modal-btn-confirm');

            cancelBtn.style.display = '';
            titleEl.textContent = title;
            msgEl.textContent = msg;
            cancelBtn.textContent = cancelText;
            confirmBtn.textContent = confirmText;

            if (type === 'danger') {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-red-500/10 text-red-500';
                icon.textContent = 'warning';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer flex items-center gap-1.5';
            } else if (type === 'warning') {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-amber-500/15 text-amber-500';
                icon.textContent = 'report_problem';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-sm transition-all cursor-pointer flex items-center gap-1.5';
            } else if (type === 'success') {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-emerald-500/15 text-emerald-500';
                icon.textContent = 'verified';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all cursor-pointer flex items-center gap-1.5';
            } else {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-primary/10 text-primary';
                icon.textContent = 'help';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-on-primary bg-primary hover:opacity-90 shadow-sm transition-all cursor-pointer flex items-center gap-1.5';
            }

            cancelBtn.onclick = function() { close(false); };
            confirmBtn.onclick = function() { close(true); };

            overlay.classList.remove('opacity-0', 'pointer-events-none');
            var card = document.getElementById('npc-modal-dialog-card');
            if (card) card.classList.remove('scale-95');

            setTimeout(function() {
                if (type === 'danger') {
                    cancelBtn.focus();
                } else {
                    confirmBtn.focus();
                }
            }, 60);
        });
    }

    function alert(optionsOrMsg) {
        return new Promise(function(resolve) {
            activeResolve = resolve;
            var opts = typeof optionsOrMsg === 'string' ? { message: optionsOrMsg } : (optionsOrMsg || {});
            var msg = opts.message || opts.text || '';
            var title = opts.title || (/error|failed|invalid|mandatory/i.test(msg) ? 'Notice' : 'Information');
            var type = opts.type || (/error|failed|invalid/i.test(msg) ? 'danger' : (/success|complete|done|✅/i.test(msg) ? 'success' : 'info'));
            var btnText = opts.btnText || 'Understood';

            createOverlay();

            var iconWrap = document.getElementById('npc-modal-icon-wrap');
            var icon = document.getElementById('npc-modal-icon');
            var titleEl = document.getElementById('npc-modal-title');
            var msgEl = document.getElementById('npc-modal-message');
            var cancelBtn = document.getElementById('npc-modal-btn-cancel');
            var confirmBtn = document.getElementById('npc-modal-btn-confirm');

            cancelBtn.style.display = 'none';
            titleEl.textContent = title;
            msgEl.textContent = msg;
            confirmBtn.textContent = btnText;

            if (type === 'danger') {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-red-500/10 text-red-500';
                icon.textContent = 'error';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer';
            } else if (type === 'success') {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-emerald-500/15 text-emerald-500';
                icon.textContent = 'check_circle';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all cursor-pointer';
            } else {
                iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-primary/10 text-primary';
                icon.textContent = 'info';
                confirmBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-on-primary bg-primary hover:opacity-90 shadow-sm transition-all cursor-pointer';
            }

            confirmBtn.onclick = function() {
                cancelBtn.style.display = '';
                close(true);
            };

            overlay.classList.remove('opacity-0', 'pointer-events-none');
            var card = document.getElementById('npc-modal-dialog-card');
            if (card) card.classList.remove('scale-95');
            setTimeout(function() { confirmBtn.focus(); }, 60);
        });
    }

    return {
        confirm: confirm,
        alert: alert
    };
})();

window.npcModal = npcModal;
window.npcConfirm = npcModal.confirm;
window.npcAlert = npcModal.alert;

// Gracefully intercept native alert() to eliminate "localhost:8000 says"
var _nativeAlert = window.alert;
window.alert = function(msg) {
    try {
        if (msg !== undefined && msg !== null) {
            npcModal.alert(msg);
        }
    } catch (e) {
        if (_nativeAlert) _nativeAlert(msg);
    }
};

/* Initialize Instant Navigation on DOM ready */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        boot();
        npcInstant.init();
    });
} else {
    boot();
    npcInstant.init();
}
})();
