/**
 * npc-three.js — NPC ELMS 3D Animation & Visual FX Engine
 * Navotas Polytechnic College Electronic Learning Management System
 * Built with Three.js (r128+)
 * 
 * Modular 3D Experience System:
 *  1. 3DExperienceManager — Global lifecycle, tier detection, reduced-motion, and tab visibility manager
 *  2. Ambient 3D Constellation / Particle Field (Universal Dashboard & Workspace)
 *  3. Cinematic 3D Academic Knowledge Hero (Sign-in Portal)
 *  4. Holographic 3D Viewfinder & Laser HUD (Attendance Scanner)
 *  5. Physics-based 3D Metallic Celebration (Attendance & Achievement moments)
 *  6. Reactive 3D Holographic AI Core (Campus AI Assistants — idle, thinking, responding, offline)
 *  7. 3D Verification Seal (Digital Receipt & Modal Achievement)
 *  8. Campus Live Lobby (Optional isometric live class & campus status hub with list view fallback)
 * 
 * Defensive & Accessible:
 *  - Automatically detects WebGL support. If missing, renders elegant CSS fallback.
 *  - Honors prefers-reduced-motion: reduce.
 *  - Automatically pauses rendering loops when tab is hidden (visibilitychange).
 *  - Lowers particle counts and polygon density on mobile and low-power devices.
 *  - Disposes geometries, materials, textures, and requestAnimationFrame handles on unmount.
 *  - Canvas overlays use pointer-events: none to avoid interfering with user clicks.
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        var exp = factory();
        root.npcThree = exp;
        root.NPC3DManager = exp.ExperienceManager;
    }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    // ── System & Feature Detection ───────────────────────────────────────────
    var REDUCED_MOTION = false;
    try {
        REDUCED_MOTION = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {}

    function hasWebGL() {
        try {
            var canvas = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    var WEBGL_AVAILABLE = hasWebGL();

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    // Color palettes mapped to NPC Branding (Deep Midnight Navy, Refined Gold, Cyan/Azure, Emerald)
    var PALETTE = {
        navy: 0x001736,
        navyMid: 0x0a2e5c,
        navyLight: 0x1e3a8a,
        gold: 0xfed488,
        goldDeep: 0xf59e0b,
        azure: 0x38bdf8,
        emerald: 0x10b981,
        slate: 0x64748b,
        white: 0xffffff,
        darkBg: 0x090f19,
        lightBg: 0xf8f9ff
    };

    // Helper: Create high performance canvas overlay
    function createOverlayCanvas(id, className, styleText) {
        var existing = document.getElementById(id);
        if (existing) return existing;
        var canvas = document.createElement('canvas');
        canvas.id = id;
        if (className) canvas.className = className;
        canvas.style.cssText = styleText || 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:1;';
        return canvas;
    }

    // ── 1. 3D EXPERIENCE MANAGER (LIFECYCLE & QUALITY CONTROLLER) ───────────
    var ExperienceManager = {
        activeInstances: [],
        capabilities: {
            webgl: WEBGL_AVAILABLE,
            reducedMotion: REDUCED_MOTION,
            mobile: false,
            lowPower: false,
            tier: 'high' // 'high' | 'medium' | 'low' | 'off'
        },

        init: function () {
            var self = this;
            var w = typeof window !== 'undefined' ? window.innerWidth : 1024;
            var cores = (typeof navigator !== 'undefined' && navigator.hardwareConcurrency) ? navigator.hardwareConcurrency : 4;
            var isMobile = w < 768 || (typeof navigator !== 'undefined' && /Mobi|Android|iPhone/i.test(navigator.userAgent));
            var isLowPower = isMobile || cores <= 4;

            this.capabilities.mobile = isMobile;
            this.capabilities.lowPower = isLowPower;

            if (!WEBGL_AVAILABLE || REDUCED_MOTION) {
                this.capabilities.tier = 'off';
            } else if (isMobile || cores <= 2) {
                this.capabilities.tier = 'low';
            } else if (isLowPower || cores <= 4) {
                this.capabilities.tier = 'medium';
            } else {
                this.capabilities.tier = 'high';
            }

            // Global visibilitychange listener to suspend WebGL frame loops
            if (typeof document !== 'undefined') {
                document.addEventListener('visibilitychange', function () {
                    var hidden = document.hidden;
                    self.activeInstances.forEach(function (inst) {
                        if (inst && typeof inst.onVisibilityChange === 'function') {
                            inst.onVisibilityChange(!hidden);
                        }
                    });
                });
            }
        },

        register: function (inst) {
            if (!inst) return;
            this.activeInstances.push(inst);
            return inst;
        },

        unregister: function (inst) {
            var idx = this.activeInstances.indexOf(inst);
            if (idx !== -1) {
                this.activeInstances.splice(idx, 1);
            }
        },

        destroyAll: function () {
            while (this.activeInstances.length > 0) {
                var inst = this.activeInstances.pop();
                if (inst && typeof inst.destroy === 'function') {
                    try { inst.destroy(); } catch (e) { console.warn('NPC 3D teardown notice:', e); }
                }
            }
        },

        createCSSFallback: function (container, type) {
            if (!container) return;
            var fallback = document.createElement('div');
            fallback.className = 'npc-3d-css-fallback absolute inset-0 pointer-events-none overflow-hidden';
            if (type === 'hero') {
                fallback.innerHTML = '<div class="absolute inset-0 bg-radial-at-c from-primary/10 via-surface/40 to-transparent opacity-60"></div>' +
                                     '<div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-secondary/10 blur-3xl"></div>' +
                                     '<div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-primary/15 blur-3xl"></div>';
            } else if (type === 'scanner') {
                fallback.innerHTML = '<div class="absolute inset-4 rounded-2xl border-2 border-dashed border-secondary/40 animate-pulse"></div>';
            } else if (type === 'core') {
                fallback.innerHTML = '<div class="w-16 h-16 rounded-full border-2 border-secondary/60 animate-spin mx-auto flex items-center justify-center">' +
                                     '<div class="w-8 h-8 rounded-full bg-primary/20"></div></div>';
            }
            container.appendChild(fallback);
            return fallback;
        }
    };

    ExperienceManager.init();

    // ── 2. AMBIENT 3D BACKGROUND CONSTELLATION ──────────────────────────────
    // A subtle, luxurious 3D node field that floats behind dashboard main areas
    var ambientInstance = null;

    function initBackground(opts) {
        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') return null;
        if (ambientInstance) return ambientInstance;

        opts = opts || {};
        var parent = opts.container || document.getElementById('canvas-container') || document.getElementById('main-wrapper') || document.body;
        if (!parent) return null;

        var canvas = createOverlayCanvas('npc-three-bg-canvas', 'npc-three-bg', 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:0;opacity:0.65;transition:opacity 0.6s ease;');
        if (parent === document.body || parent.id === 'main-wrapper') {
            document.body.insertBefore(canvas, document.body.firstChild);
        } else {
            parent.style.position = 'relative';
            parent.insertBefore(canvas, parent.firstChild);
        }

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
        camera.position.z = 240;

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: ExperienceManager.capabilities.tier !== 'low',
            powerPreference: 'high-performance'
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, ExperienceManager.capabilities.tier === 'high' ? 2 : 1.25));
        renderer.setSize(window.innerWidth, window.innerHeight);

        // Particle field based on capability tier
        var particleCount = ExperienceManager.capabilities.tier === 'low' ? 35 : (ExperienceManager.capabilities.tier === 'medium' ? 65 : 110);
        var geometry = new THREE.BufferGeometry();
        var positions = new Float32Array(particleCount * 3);
        var velocities = [];
        var colors = new Float32Array(particleCount * 3);

        for (var i = 0; i < particleCount; i++) {
            var i3 = i * 3;
            positions[i3] = (Math.random() - 0.5) * 520;
            positions[i3 + 1] = (Math.random() - 0.5) * 360;
            positions[i3 + 2] = (Math.random() - 0.5) * 200;

            velocities.push({
                x: (Math.random() - 0.5) * 0.16,
                y: (Math.random() - 0.5) * 0.16,
                z: (Math.random() - 0.5) * 0.10
            });
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        // Create Dark & Light Mode Particle Sprites
        var darkSpriteCanvas = document.createElement('canvas');
        darkSpriteCanvas.width = 32;
        darkSpriteCanvas.height = 32;
        var dsctx = darkSpriteCanvas.getContext('2d');
        var dgrad = dsctx.createRadialGradient(16, 16, 0, 16, 16, 16);
        dgrad.addColorStop(0, 'rgba(255,255,255,1)');
        dgrad.addColorStop(0.35, 'rgba(254,212,136,0.9)');
        dgrad.addColorStop(0.8, 'rgba(56,189,248,0.4)');
        dgrad.addColorStop(1, 'rgba(0,0,0,0)');
        dsctx.fillStyle = dgrad;
        dsctx.fillRect(0, 0, 32, 32);
        var darkSprite = new THREE.CanvasTexture(darkSpriteCanvas);

        var lightSpriteCanvas = document.createElement('canvas');
        lightSpriteCanvas.width = 32;
        lightSpriteCanvas.height = 32;
        var lsctx = lightSpriteCanvas.getContext('2d');
        var lgrad = lsctx.createRadialGradient(16, 16, 0, 16, 16, 16);
        lgrad.addColorStop(0, 'rgba(255,255,255,1)');
        lgrad.addColorStop(0.35, 'rgba(255,255,255,0.95)');
        lgrad.addColorStop(0.7, 'rgba(255,255,255,0.5)');
        lgrad.addColorStop(1, 'rgba(255,255,255,0)');
        lsctx.fillStyle = lgrad;
        lsctx.fillRect(0, 0, 32, 32);
        var lightSprite = new THREE.CanvasTexture(lightSpriteCanvas);

        var darkPal = [
            new THREE.Color(PALETTE.azure),
            new THREE.Color(PALETTE.gold),
            new THREE.Color(0xa5f3fc)
        ];
        var lightPal = [
            new THREE.Color(0x0284c7), // Sapphire Blue
            new THREE.Color(0xd97706), // Rich Golden Amber
            new THREE.Color(0x0f766e), // Emerald Teal
            new THREE.Color(0x1d4ed8)  // Academic Royal Blue
        ];

        var initiallyDark = isDark();

        var pMaterial = new THREE.PointsMaterial({
            size: initiallyDark ? 6.0 : 7.5,
            map: initiallyDark ? darkSprite : lightSprite,
            transparent: true,
            opacity: initiallyDark ? 0.70 : 0.88,
            vertexColors: true,
            blending: initiallyDark ? THREE.AdditiveBlending : THREE.NormalBlending,
            depthWrite: false
        });

        var pointCloud = new THREE.Points(geometry, pMaterial);
        scene.add(pointCloud);

        // Dynamic Connecting Lines between nearby nodes (Skip if low power)
        var enableLines = ExperienceManager.capabilities.tier !== 'low';
        var maxLines = enableLines ? particleCount * 2 : 0;
        var linePositions = new Float32Array(Math.max(1, maxLines) * 6);
        var lineColors = new Float32Array(Math.max(1, maxLines) * 6);
        var lineGeo = new THREE.BufferGeometry();
        lineGeo.setAttribute('position', new THREE.BufferAttribute(linePositions, 3).setUsage(THREE.DynamicDrawUsage));
        lineGeo.setAttribute('color', new THREE.BufferAttribute(lineColors, 3).setUsage(THREE.DynamicDrawUsage));

        var lineMat = new THREE.LineBasicMaterial({
            vertexColors: true,
            transparent: true,
            opacity: initiallyDark ? 0.30 : 0.40,
            blending: initiallyDark ? THREE.AdditiveBlending : THREE.NormalBlending,
            depthWrite: false
        });

        var linesMesh = enableLines ? new THREE.LineSegments(lineGeo, lineMat) : null;
        if (linesMesh) scene.add(linesMesh);

        function updateThemeColors(dark) {
            var pal = dark ? darkPal : lightPal;
            var colAttr = geometry.attributes.color.array;
            for (var k = 0; k < particleCount; k++) {
                var c = pal[k % pal.length];
                var k3 = k * 3;
                colAttr[k3] = c.r;
                colAttr[k3 + 1] = c.g;
                colAttr[k3 + 2] = c.b;
            }
            geometry.attributes.color.needsUpdate = true;

            pMaterial.map = dark ? darkSprite : lightSprite;
            pMaterial.blending = dark ? THREE.AdditiveBlending : THREE.NormalBlending;
            pMaterial.opacity = dark ? 0.70 : 0.88;
            pMaterial.size = dark ? 6.0 : 7.5;
            pMaterial.needsUpdate = true;

            if (lineMat) {
                lineMat.blending = dark ? THREE.AdditiveBlending : THREE.NormalBlending;
                lineMat.opacity = dark ? 0.30 : 0.40;
                lineMat.needsUpdate = true;
            }

            canvas.style.opacity = dark ? '0.65' : '0.85';
        }

        updateThemeColors(initiallyDark);

        // Mouse Parallax (Dampened on mobile)
        var mouseX = 0, mouseY = 0, targetX = 0, targetY = 0;
        function onPointerMove(e) {
            if (ExperienceManager.capabilities.mobile) return;
            mouseX = (e.clientX - window.innerWidth / 2) * 0.04;
            mouseY = (e.clientY - window.innerHeight / 2) * 0.04;
        }
        window.addEventListener('pointermove', onPointerMove, { passive: true });

        function onResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }
        window.addEventListener('resize', onResize);

        // Theme sync
        var themeObserver = new MutationObserver(function () {
            updateThemeColors(isDark());
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        var animId = null;
        var isVisible = true;

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible) return;

            targetX += (mouseX - targetX) * 0.04;
            targetY += (mouseY - targetY) * 0.04;

            camera.position.x = targetX;
            camera.position.y = -targetY;
            camera.lookAt(scene.position);

            var pos = geometry.attributes.position.array;
            var lineIdx = 0;
            var linePos = enableLines ? lineGeo.attributes.position.array : null;
            var lineCol = enableLines ? lineGeo.attributes.color.array : null;
            var dark = isDark();

            for (var i = 0; i < particleCount; i++) {
                var i3 = i * 3;
                pos[i3] += velocities[i].x;
                pos[i3 + 1] += velocities[i].y;
                pos[i3 + 2] += velocities[i].z;

                if (pos[i3] < -250 || pos[i3] > 250) velocities[i].x *= -1;
                if (pos[i3 + 1] < -180 || pos[i3 + 1] > 180) velocities[i].y *= -1;
                if (pos[i3 + 2] < -120 || pos[i3 + 2] > 120) velocities[i].z *= -1;

                // Connect with adjacent nodes (desktop / medium-tier)
                if (enableLines) {
                    for (var j = i + 1; j < particleCount && lineIdx < maxLines; j++) {
                        var j3 = j * 3;
                        var dx = pos[i3] - pos[j3];
                        var dy = pos[i3 + 1] - pos[j3 + 1];
                        var dz = pos[i3 + 2] - pos[j3 + 2];
                        var distSq = dx * dx + dy * dy + dz * dz;

                        if (distSq < 5625) { // dist < 75
                            var rawAlpha = 1.0 - (Math.sqrt(distSq) / 75.0);
                            var alpha = Math.max(0, Math.min(1, rawAlpha));
                            var pBase = lineIdx * 6;

                            linePos[pBase] = pos[i3];
                            linePos[pBase + 1] = pos[i3 + 1];
                            linePos[pBase + 2] = pos[i3 + 2];
                            linePos[pBase + 3] = pos[j3];
                            linePos[pBase + 4] = pos[j3 + 1];
                            linePos[pBase + 5] = pos[j3 + 2];

                            if (dark) {
                                var r = 0.99, g = 0.83, b = 0.53;
                                lineCol[pBase]     = r * alpha;
                                lineCol[pBase + 1] = g * alpha;
                                lineCol[pBase + 2] = b * alpha;
                                lineCol[pBase + 3] = r * alpha;
                                lineCol[pBase + 4] = g * alpha;
                                lineCol[pBase + 5] = b * alpha;
                            } else {
                                var baseR = (i % 3 === 0) ? 0.85 : 0.01;
                                var baseG = (i % 3 === 0) ? 0.47 : 0.48;
                                var baseB = (i % 3 === 0) ? 0.02 : 0.82;
                                var lr = baseR * alpha + (1.0 - alpha);
                                var lg = baseG * alpha + (1.0 - alpha);
                                var lb = baseB * alpha + (1.0 - alpha);
                                lineCol[pBase]     = lr;
                                lineCol[pBase + 1] = lg;
                                lineCol[pBase + 2] = lb;
                                lineCol[pBase + 3] = lr;
                                lineCol[pBase + 4] = lg;
                                lineCol[pBase + 5] = lb;
                            }

                            lineIdx++;
                        }
                    }
                }
            }

            geometry.attributes.position.needsUpdate = true;
            if (enableLines) {
                lineGeo.setDrawRange(0, lineIdx * 2);
                lineGeo.attributes.position.needsUpdate = true;
                lineGeo.attributes.color.needsUpdate = true;
                linesMesh.rotation.y += 0.0005;
            }

            pointCloud.rotation.y += 0.0005;

            renderer.render(scene, camera);
        }

        animate();

        ambientInstance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                window.removeEventListener('pointermove', onPointerMove);
                window.removeEventListener('resize', onResize);
                themeObserver.disconnect();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                geometry.dispose();
                pMaterial.dispose();
                darkSprite.dispose();
                lightSprite.dispose();
                if (lineGeo) lineGeo.dispose();
                if (lineMat) lineMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(ambientInstance);
                ambientInstance = null;
            }
        };

        ExperienceManager.register(ambientInstance);
        return ambientInstance;
    }

    // ── 3. CINEMATIC 3D ACADEMIC LOGIN HERO ──────────────────────────────────
    // Floating academic knowledge constellation + calm concentric golden rings
    function initLoginHero(containerId) {
        var container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!container) return null;

        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') {
            ExperienceManager.createCSSFallback(container, 'hero');
            return null;
        }

        var canvas = createOverlayCanvas('npc-login-3d-canvas', 'absolute inset-0 w-full h-full pointer-events-none z-0');
        container.insertBefore(canvas, container.firstChild);

        var rect = container.getBoundingClientRect();
        var width = rect.width || window.innerWidth;
        var height = rect.height || window.innerHeight;

        var scene = new THREE.Scene();
        scene.fog = new THREE.FogExp2(0x001736, 0.0016);

        var camera = new THREE.PerspectiveCamera(52, width / height, 1, 2000);
        camera.position.set(0, 60, 260);

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: ExperienceManager.capabilities.tier !== 'low'
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, ExperienceManager.capabilities.tier === 'high' ? 2 : 1.25));
        renderer.setSize(width, height);

        // Academic Knowledge Grid Ground
        var cols = ExperienceManager.capabilities.tier === 'low' ? 20 : 44;
        var rows = cols;
        var waveGeo = new THREE.PlaneGeometry(720, 720, cols, rows);
        waveGeo.rotateX(-Math.PI / 2);

        var waveMat = new THREE.PointsMaterial({
            color: 0x38bdf8,
            size: 2.5,
            transparent: true,
            opacity: 0.50,
            blending: THREE.AdditiveBlending
        });
        var waveMesh = new THREE.Points(waveGeo, waveMat);
        waveMesh.position.y = -60;
        scene.add(waveMesh);

        var wireMat = new THREE.MeshBasicMaterial({
            color: 0x0a4d8c,
            wireframe: true,
            transparent: true,
            opacity: 0.14
        });
        var wireMesh = new THREE.Mesh(waveGeo, wireMat);
        wireMesh.position.y = -60;
        scene.add(wireMesh);

        // Floating Concentric Golden Rings (Academic Excellence)
        var ringGroup = new THREE.Group();
        ringGroup.position.set(-130, 40, -40);

        var ringMat1 = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            wireframe: true,
            transparent: true,
            opacity: 0.60
        });
        var ring1 = new THREE.Mesh(new THREE.TorusGeometry(36, 0.6, 10, 52), ringMat1);
        ringGroup.add(ring1);

        var ringMat2 = new THREE.MeshBasicMaterial({
            color: 0x38bdf8,
            wireframe: true,
            transparent: true,
            opacity: 0.40
        });
        var ring2 = new THREE.Mesh(new THREE.TorusGeometry(44, 0.5, 8, 48), ringMat2);
        ring2.rotation.x = Math.PI / 4;
        ringGroup.add(ring2);

        // Core Polyhedron
        var coreGeo = new THREE.IcosahedronGeometry(11, 1);
        var coreMat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            wireframe: true,
            transparent: true,
            opacity: 0.75
        });
        var coreMesh = new THREE.Mesh(coreGeo, coreMat);
        ringGroup.add(coreMesh);
        scene.add(ringGroup);

        // Floating Academic Nodes
        var starCount = ExperienceManager.capabilities.tier === 'low' ? 40 : 100;
        var starGeo = new THREE.BufferGeometry();
        var starPos = new Float32Array(starCount * 3);
        for (var s = 0; s < starCount; s++) {
            var s3 = s * 3;
            starPos[s3] = (Math.random() - 0.5) * 550;
            starPos[s3 + 1] = Math.random() * 240 - 40;
            starPos[s3 + 2] = (Math.random() - 0.5) * 400;
        }
        starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
        var starMat = new THREE.PointsMaterial({
            color: 0xfed488,
            size: 3.0,
            transparent: true,
            opacity: 0.70,
            blending: THREE.AdditiveBlending
        });
        var stars = new THREE.Points(starGeo, starMat);
        scene.add(stars);

        function updateLoginHeroTheme(dark) {
            scene.fog.color.setHex(dark ? 0x001736 : 0xf8fafd);
            scene.fog.density = dark ? 0.0016 : 0.0011;

            waveMat.color.setHex(dark ? 0x38bdf8 : 0x0284c7);
            waveMat.blending = dark ? THREE.AdditiveBlending : THREE.NormalBlending;
            waveMat.opacity = dark ? 0.50 : 0.80;
            waveMat.size = dark ? 2.5 : 3.2;
            waveMat.needsUpdate = true;

            wireMat.color.setHex(dark ? 0x0a4d8c : 0xbfdbfe);
            wireMat.opacity = dark ? 0.14 : 0.28;
            wireMat.needsUpdate = true;

            ringMat1.color.setHex(dark ? 0xfed488 : 0xd97706);
            ringMat2.color.setHex(dark ? 0x38bdf8 : 0x0284c7);
            coreMat.color.setHex(dark ? 0xfed488 : 0xd97706);
            starMat.color.setHex(dark ? 0xfed488 : 0xd97706);
            starMat.blending = dark ? THREE.AdditiveBlending : THREE.NormalBlending;
            starMat.needsUpdate = true;
        }

        var heroThemeObserver = new MutationObserver(function () {
            updateLoginHeroTheme(isDark());
        });
        heroThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        updateLoginHeroTheme(isDark());

        var mouseX = 0, mouseY = 0, targetX = 0, targetY = 0;
        function onPointer(e) {
            if (ExperienceManager.capabilities.mobile) return;
            mouseX = (e.clientX - width / 2) * 0.05;
            mouseY = (e.clientY - height / 2) * 0.05;
        }
        window.addEventListener('pointermove', onPointer, { passive: true });

        function onResize() {
            var r = container.getBoundingClientRect();
            width = r.width || window.innerWidth;
            height = r.height || window.innerHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        }
        window.addEventListener('resize', onResize);

        var clock = new THREE.Clock();
        var animId = null;
        var isVisible = true;

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible) return;

            var time = clock.getElapsedTime();

            targetX += (mouseX - targetX) * 0.04;
            targetY += (mouseY - targetY) * 0.04;
            camera.position.x = targetX;
            camera.position.y = 60 - targetY;
            camera.lookAt(0, 10, 0);

            // Gentle Wave Motion
            var pos = waveGeo.attributes.position;
            var stride = ExperienceManager.capabilities.tier === 'low' ? 2 : 1;
            for (var i = 0; i < pos.count; i += stride) {
                var u = pos.getX(i);
                var v = pos.getZ(i);
                var z = Math.sin(u * 0.015 + time * 0.75) * 6 + Math.cos(v * 0.015 + time * 0.6) * 5;
                pos.setY(i, z);
            }
            pos.needsUpdate = true;

            // Rotating Rings
            ring1.rotation.x += 0.005;
            ring1.rotation.y += 0.007;
            ring2.rotation.y -= 0.006;
            coreMesh.rotation.y += 0.009;
            coreMesh.rotation.x += 0.004;

            ringGroup.position.y = 40 + Math.sin(time * 0.9) * 5;

            // Gentle Starfield Drift
            stars.rotation.y = time * 0.015;

            renderer.render(scene, camera);
        }

        animate();

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                window.removeEventListener('pointermove', onPointer);
                window.removeEventListener('resize', onResize);
                heroThemeObserver.disconnect();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                waveGeo.dispose();
                waveMat.dispose();
                wireMat.dispose();
                ring1.geometry.dispose();
                ringMat1.dispose();
                ring2.geometry.dispose();
                ringMat2.dispose();
                coreGeo.dispose();
                coreMat.dispose();
                starGeo.dispose();
                starMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

        ExperienceManager.register(instance);
        return instance;
    }

    // ── 4. HOLOGRAPHIC 3D SCANNER HUD (QR SCANNER OVERLAY) ───────────────────
    // A high-clarity, non-blocking volumetric HUD designed to float above camera feeds
    function initScannerHUD(containerId) {
        var container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!container) return null;

        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') {
            ExperienceManager.createCSSFallback(container, 'scanner');
            return {
                setScanState: function () {},
                triggerVerificationPulse: function () {},
                destroy: function () {}
            };
        }

        var canvas = createOverlayCanvas('npc-scanner-3d-canvas', 'absolute inset-0 w-full h-full pointer-events-none z-10');
        container.appendChild(canvas);

        var rect = container.getBoundingClientRect();
        var width = rect.width || 420;
        var height = rect.height || 320;

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(45, width / height, 1, 1000);
        camera.position.z = 180;

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(width, height);

        var hudGroup = new THREE.Group();
        scene.add(hudGroup);

        // Concentric Holographic Rings with Gold & Azure Accents
        var outerRingGeo = new THREE.RingGeometry(58, 59.5, 48);
        var outerRingMat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.75
        });
        var outerRing = new THREE.Mesh(outerRingGeo, outerRingMat);
        hudGroup.add(outerRing);

        var dashedRingGeo = new THREE.RingGeometry(51, 52, 32, 1, 0, Math.PI * 1.6);
        var dashedRingMat = new THREE.MeshBasicMaterial({
            color: 0x38bdf8,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.65
        });
        var dashedRing = new THREE.Mesh(dashedRingGeo, dashedRingMat);
        hudGroup.add(dashedRing);

        var innerTargetGeo = new THREE.RingGeometry(22, 23, 32);
        var innerTargetMat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.5
        });
        var innerTarget = new THREE.Mesh(innerTargetGeo, innerTargetMat);
        hudGroup.add(innerTarget);

        // Measurement Ticks
        var tickGroup = new THREE.Group();
        for (var t = 0; t < 8; t++) {
            var angle = (t / 8) * Math.PI * 2;
            var tickGeo = new THREE.BufferGeometry().setFromPoints([
                new THREE.Vector3(Math.cos(angle) * 62, Math.sin(angle) * 62, 0),
                new THREE.Vector3(Math.cos(angle) * 68, Math.sin(angle) * 68, 0)
            ]);
            var tickLine = new THREE.Line(tickGeo, new THREE.LineBasicMaterial({ color: 0xfed488, transparent: true, opacity: 0.6 }));
            tickGroup.add(tickLine);
        }
        hudGroup.add(tickGroup);

        // Sweeping Laser Beam
        var laserPlaneGeo = new THREE.PlaneGeometry(130, 7);
        var laserPlaneMat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            transparent: true,
            opacity: 0.55,
            blending: THREE.AdditiveBlending,
            side: THREE.DoubleSide
        });
        var laserPlane = new THREE.Mesh(laserPlaneGeo, laserPlaneMat);
        laserPlane.rotation.x = Math.PI / 6;
        hudGroup.add(laserPlane);

        // Corner Brackets
        var bracketSize = 72;
        var bracketPts = [
            [-bracketSize, bracketSize],
            [bracketSize, bracketSize],
            [bracketSize, -bracketSize],
            [-bracketSize, -bracketSize]
        ];
        var cornerGroup = new THREE.Group();
        bracketPts.forEach(function (pt) {
            var sx = Math.sign(pt[0]);
            var sy = Math.sign(pt[1]);
            var pts = [
                new THREE.Vector3(pt[0] - sx * 16, pt[1], 4),
                new THREE.Vector3(pt[0], pt[1], 4),
                new THREE.Vector3(pt[0], pt[1] - sy * 16, 4)
            ];
            var cornerGeo = new THREE.BufferGeometry().setFromPoints(pts);
            var cornerLine = new THREE.Line(cornerGeo, new THREE.LineBasicMaterial({
                color: 0xfed488,
                transparent: true,
                opacity: 0.80
            }));
            cornerGroup.add(cornerLine);
        });
        hudGroup.add(cornerGroup);

        function resize() {
            var r = container.getBoundingClientRect();
            var w = r.width || 420;
            var h = r.height || 320;
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
            renderer.setSize(w, h);
        }
        window.addEventListener('resize', resize);

        var laserY = 0;
        var laserDir = 1;
        var state = 'scanning'; // 'scanning' | 'success' | 'idle'
        var animId = null;
        var isVisible = true;

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible) return;

            outerRing.rotation.z += 0.007;
            dashedRing.rotation.z -= 0.010;
            tickGroup.rotation.z += 0.003;

            if (state === 'scanning') {
                laserY += laserDir * 1.30;
                if (laserY > 52) { laserY = 52; laserDir = -1; }
                if (laserY < -52) { laserY = -52; laserDir = 1; }
                laserPlane.position.y = laserY;
                laserPlane.visible = true;

                var s = 1 + Math.sin(Date.now() * 0.004) * 0.03;
                innerTarget.scale.set(s, s, 1);
            } else if (state === 'success') {
                laserPlane.visible = false;
                outerRingMat.color.setHex(PALETTE.emerald);
                dashedRingMat.color.setHex(PALETTE.gold);
                var pulse = 1 + Math.sin(Date.now() * 0.01) * 0.12;
                hudGroup.scale.set(pulse, pulse, pulse);
            }

            renderer.render(scene, camera);
        }

        function updateScannerTheme(dark) {
            outerRingMat.color.setHex(dark ? 0xfed488 : 0xd97706);
            dashedRingMat.color.setHex(dark ? 0x38bdf8 : 0x0284c7);
            innerTargetMat.color.setHex(dark ? 0xfed488 : 0xd97706);
            laserPlaneMat.color.setHex(dark ? 0xfed488 : 0xd97706);
            laserPlaneMat.blending = dark ? THREE.AdditiveBlending : THREE.NormalBlending;
            laserPlaneMat.needsUpdate = true;
        }

        var scannerObserver = new MutationObserver(function () {
            updateScannerTheme(isDark());
        });
        scannerObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        updateScannerTheme(isDark());

        animate();

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            setScanState: function (newState) {
                state = newState;
                if (state === 'success') {
                    outerRingMat.color.setHex(PALETTE.emerald);
                    setTimeout(function () {
                        outerRingMat.color.setHex(isDark() ? PALETTE.gold : 0xd97706);
                        state = 'scanning';
                        hudGroup.scale.set(1, 1, 1);
                    }, 2600);
                }
            },
            triggerVerificationPulse: function () {
                this.setScanState('success');
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                window.removeEventListener('resize', resize);
                scannerObserver.disconnect();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                outerRingGeo.dispose();
                outerRingMat.dispose();
                dashedRingGeo.dispose();
                dashedRingMat.dispose();
                innerTargetGeo.dispose();
                innerTargetMat.dispose();
                laserPlaneGeo.dispose();
                laserPlaneMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

        ExperienceManager.register(instance);
        return instance;
    }

    // ── 5. PHYSICS-BASED 3D METALLIC CELEBRATION ────────────────────────────
    // Short, graceful 3D celebration burst for attendance recorded & grade published
    function celebrate3D(options) {
        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') {
            if (window.npcConfetti && typeof window.npcConfetti._burst2D === 'function') {
                window.npcConfetti._burst2D(60);
            }
            return;
        }

        options = options || {};
        var count = ExperienceManager.capabilities.tier === 'low' ? 45 : (options.count || 100);

        var canvas = document.createElement('canvas');
        canvas.id = 'npc-celebration-3d';
        canvas.style.cssText = 'position:fixed;inset:0;width:100vw;height:100vh;pointer-events:none;z-index:99999;';
        document.body.appendChild(canvas);

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 1, 1000);
        camera.position.z = 200;

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: ExperienceManager.capabilities.tier !== 'low'
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(window.innerWidth, window.innerHeight);

        var ambLight = new THREE.AmbientLight(0xffffff, 0.7);
        scene.add(ambLight);
        var dirLight = new THREE.DirectionalLight(0xfffaed, 0.9);
        dirLight.position.set(50, 100, 80);
        scene.add(dirLight);

        var ribbonGeo = new THREE.PlaneGeometry(3.2, 6.8);
        var cubeGeo = new THREE.BoxGeometry(3.2, 3.2, 3.2);
        var octaGeo = new THREE.OctahedronGeometry(3.4, 0);

        var colors = [
            PALETTE.gold,
            PALETTE.goldDeep,
            PALETTE.azure,
            PALETTE.navyMid,
            PALETTE.emerald,
            0xffffff
        ];

        var pieces = [];
        var geometries = [ribbonGeo, cubeGeo, octaGeo];

        for (var i = 0; i < count; i++) {
            var geo = geometries[i % geometries.length];
            var col = colors[i % colors.length];
            var mat = new THREE.MeshStandardMaterial({
                color: col,
                metalness: 0.85,
                roughness: 0.25,
                side: THREE.DoubleSide
            });
            var mesh = new THREE.Mesh(geo, mat);

            var startX = (Math.random() - 0.5) * 80;
            var startY = (Math.random() - 0.5) * 30 + 10;
            mesh.position.set(startX, startY, (Math.random() - 0.5) * 40);

            var angle = Math.random() * Math.PI * 2;
            var speed = 2.5 + Math.random() * 5.0;

            pieces.push({
                mesh: mesh,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed + 3.8,
                vz: (Math.random() - 0.5) * 3.5,
                rotX: (Math.random() - 0.5) * 0.18,
                rotY: (Math.random() - 0.5) * 0.18,
                rotZ: (Math.random() - 0.5) * 0.18,
                gravity: -0.18,
                drag: 0.97,
                life: 1.0,
                decay: 0.015 + Math.random() * 0.012
            });

            scene.add(mesh);
        }

        var animId = null;

        function step() {
            var alive = 0;

            for (var p = 0; p < pieces.length; p++) {
                var it = pieces[p];
                if (it.life <= 0) continue;

                alive++;
                it.vx *= it.drag;
                it.vy += it.gravity;
                it.vz *= it.drag;

                it.mesh.position.x += it.vx;
                it.mesh.position.y += it.vy;
                it.mesh.position.z += it.vz;

                it.mesh.rotation.x += it.rotX;
                it.mesh.rotation.y += it.rotY;
                it.mesh.rotation.z += it.rotZ;

                it.life -= it.decay;
                it.mesh.material.opacity = Math.max(0, it.life);
                it.mesh.material.transparent = true;

                if (it.mesh.position.y < -140) {
                    it.life = 0;
                }
            }

            renderer.render(scene, camera);

            if (alive > 0) {
                animId = requestAnimationFrame(step);
            } else {
                cancelAnimationFrame(animId);
                pieces.forEach(function (pt) {
                    pt.mesh.material.dispose();
                });
                ribbonGeo.dispose();
                cubeGeo.dispose();
                octaGeo.dispose();
                renderer.dispose();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
            }
        }

        step();
    }

    // ── 6. REFINED 3D HOLOGRAPHIC AI CORE ───────────────────────────────────
    // Status visual object: idle (calm orbit), thinking (pulse/rotating ring),
    // responding (mild waveform), offline (muted static state)
    function initAiCore(containerId, options) {
        var container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!container) return null;

        options = options || {};
        var size = options.size || 88;

        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') {
            ExperienceManager.createCSSFallback(container, 'core');
            return {
                setMode: function () {},
                destroy: function () {}
            };
        }

        var canvas = document.createElement('canvas');
        canvas.className = 'npc-ai-core-canvas';
        canvas.style.cssText = 'width:' + size + 'px;height:' + size + 'px;display:block;margin:0 auto;pointer-events:none;';
        container.innerHTML = '';
        container.appendChild(canvas);

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(50, 1, 1, 500);
        camera.position.z = 85;

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(size, size);

        var coreGroup = new THREE.Group();
        scene.add(coreGroup);

        // Inner glowing core sphere
        var innerGeo = new THREE.SphereGeometry(14, 20, 20);
        var innerMat = new THREE.MeshBasicMaterial({
            color: 0x38bdf8,
            wireframe: true,
            transparent: true,
            opacity: 0.65
        });
        var innerBall = new THREE.Mesh(innerGeo, innerMat);
        coreGroup.add(innerBall);

        // Geometric Icosahedron Cage
        var cageGeo = new THREE.IcosahedronGeometry(22, 1);
        var cageMat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            wireframe: true,
            transparent: true,
            opacity: 0.80
        });
        var cage = new THREE.Mesh(cageGeo, cageMat);
        coreGroup.add(cage);

        // Orbiting Ring 1
        var orbit1Geo = new THREE.TorusGeometry(30, 0.6, 8, 44);
        var orbit1Mat = new THREE.MeshBasicMaterial({
            color: 0x38bdf8,
            transparent: true,
            opacity: 0.60
        });
        var orbit1 = new THREE.Mesh(orbit1Geo, orbit1Mat);
        orbit1.rotation.x = Math.PI / 3;
        coreGroup.add(orbit1);

        // Orbiting Ring 2
        var orbit2Geo = new THREE.TorusGeometry(32, 0.6, 8, 44);
        var orbit2Mat = new THREE.MeshBasicMaterial({
            color: 0xfed488,
            transparent: true,
            opacity: 0.70
        });
        var orbit2 = new THREE.Mesh(orbit2Geo, orbit2Mat);
        orbit2.rotation.y = Math.PI / 4;
        coreGroup.add(orbit2);

        // Swarm of tiny AI synapses
        var pCount = ExperienceManager.capabilities.tier === 'low' ? 16 : 32;
        var pGeo = new THREE.BufferGeometry();
        var pPos = new Float32Array(pCount * 3);
        for (var p = 0; p < pCount; p++) {
            var rad = 25 + Math.random() * 12;
            var theta = Math.random() * Math.PI * 2;
            var phi = (Math.random() - 0.5) * Math.PI;
            pPos[p * 3] = rad * Math.cos(phi) * Math.cos(theta);
            pPos[p * 3 + 1] = rad * Math.cos(phi) * Math.sin(theta);
            pPos[p * 3 + 2] = rad * Math.sin(phi);
        }
        pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
        var pMat = new THREE.PointsMaterial({
            color: 0xfed488,
            size: 2.2,
            transparent: true,
            opacity: 0.75
        });
        var synapses = new THREE.Points(pGeo, pMat);
        coreGroup.add(synapses);

        var mode = 'idle'; // 'idle' | 'thinking' | 'responding' | 'speaking' | 'offline'
        var clock = new THREE.Clock();
        var animId = null;
        var isVisible = true;

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible) return;

            var time = clock.getElapsedTime();

            if (mode === 'offline') {
                // Static muted state
                cage.rotation.set(0.3, 0.2, 0);
                innerBall.rotation.set(0, 0, 0);
                orbit1.rotation.set(Math.PI / 3, 0, 0);
                orbit2.rotation.set(0, Math.PI / 4, 0);
                innerBall.scale.set(0.85, 0.85, 0.85);
            } else {
                var speedMul = (mode === 'thinking') ? 2.8 : ((mode === 'responding' || mode === 'speaking') ? 1.8 : 0.85);

                cage.rotation.x += 0.008 * speedMul;
                cage.rotation.y += 0.012 * speedMul;
                innerBall.rotation.y -= 0.014 * speedMul;
                orbit1.rotation.z += 0.016 * speedMul;
                orbit2.rotation.x -= 0.020 * speedMul;
                synapses.rotation.y += 0.010 * speedMul;

                var breathFreq = (mode === 'thinking') ? 5.0 : ((mode === 'responding' || mode === 'speaking') ? 3.5 : 1.8);
                var breathAmp = (mode === 'thinking') ? 0.10 : ((mode === 'responding' || mode === 'speaking') ? 0.08 : 0.04);
                var breath = 1 + Math.sin(time * breathFreq) * breathAmp;
                innerBall.scale.set(breath, breath, breath);
            }

            renderer.render(scene, camera);
        }

        function updateAiCoreTheme(dark) {
            if (mode === 'offline') {
                cageMat.color.setHex(PALETTE.slate);
                innerMat.color.setHex(PALETTE.slate);
                orbit1Mat.color.setHex(PALETTE.slate);
                orbit2Mat.color.setHex(PALETTE.slate);
                pMat.color.setHex(PALETTE.slate);
                cageMat.opacity = 0.35;
                innerMat.opacity = 0.25;
                orbit1Mat.opacity = 0.25;
                orbit2Mat.opacity = 0.25;
                pMat.opacity = 0.30;
            } else if (mode === 'thinking') {
                cageMat.color.setHex(dark ? PALETTE.azure : 0x0284c7);
                innerMat.color.setHex(dark ? PALETTE.gold : 0xd97706);
                orbit1Mat.color.setHex(dark ? 0x38bdf8 : 0x0284c7);
                orbit2Mat.color.setHex(dark ? 0xfed488 : 0xd97706);
                pMat.color.setHex(dark ? 0xfed488 : 0xd97706);
                cageMat.opacity = 0.85;
                innerMat.opacity = 0.70;
            } else if (mode === 'responding' || mode === 'speaking') {
                cageMat.color.setHex(PALETTE.emerald);
                innerMat.color.setHex(dark ? PALETTE.gold : 0xd97706);
                orbit1Mat.color.setHex(PALETTE.emerald);
                orbit2Mat.color.setHex(dark ? 0xfed488 : 0xd97706);
                pMat.color.setHex(PALETTE.emerald);
                cageMat.opacity = 0.85;
                innerMat.opacity = 0.75;
            } else {
                // Calm Idle
                cageMat.color.setHex(dark ? PALETTE.gold : 0xd97706);
                innerMat.color.setHex(dark ? PALETTE.azure : 0x0284c7);
                orbit1Mat.color.setHex(dark ? 0x38bdf8 : 0x0284c7);
                orbit2Mat.color.setHex(dark ? 0xfed488 : 0xd97706);
                pMat.color.setHex(dark ? 0xfed488 : 0xd97706);
                cageMat.opacity = dark ? 0.75 : 0.90;
                innerMat.opacity = dark ? 0.60 : 0.80;
            }
        }

        var aiObserver = new MutationObserver(function () {
            updateAiCoreTheme(isDark());
        });
        aiObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        updateAiCoreTheme(isDark());

        animate();

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            setMode: function (newMode) {
                mode = newMode;
                updateAiCoreTheme(isDark());
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                aiObserver.disconnect();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                innerGeo.dispose();
                innerMat.dispose();
                cageGeo.dispose();
                cageMat.dispose();
                orbit1Geo.dispose();
                orbit1Mat.dispose();
                orbit2Geo.dispose();
                orbit2Mat.dispose();
                pGeo.dispose();
                pMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

        ExperienceManager.register(instance);
        return instance;
    }

    // ── 7. 3D VERIFICATION SEAL ──────────────────────────────────────────────
    // Modal achievement seal for verified attendance receipts & official transcripts
    function renderVerificationSeal(containerId) {
        var container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!container) return null;

        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined') {
            container.innerHTML = '<div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-600 flex items-center justify-center mx-auto">' +
                                  '<span class="material-symbols-outlined text-[32px]">verified</span></div>';
            return { destroy: function () {} };
        }

        var canvas = document.createElement('canvas');
        canvas.style.cssText = 'width:80px;height:80px;display:block;margin:0 auto;pointer-events:none;';
        container.innerHTML = '';
        container.appendChild(canvas);

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(45, 1, 1, 500);
        camera.position.z = 70;

        var renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(80, 80);

        var amb = new THREE.AmbientLight(0xffffff, 0.85);
        scene.add(amb);
        var dir = new THREE.DirectionalLight(0xfffaed, 0.9);
        dir.position.set(30, 50, 40);
        scene.add(dir);

        var sealGroup = new THREE.Group();
        scene.add(sealGroup);

        var rimGeo = new THREE.CylinderGeometry(24, 24, 2.5, 32);
        var rimMat = new THREE.MeshStandardMaterial({
            color: 0x10b981,
            metalness: 0.75,
            roughness: 0.28
        });
        var rimMesh = new THREE.Mesh(rimGeo, rimMat);
        rimMesh.rotation.x = Math.PI / 2;
        sealGroup.add(rimMesh);

        var bezelGeo = new THREE.TorusGeometry(22, 1.8, 12, 36);
        var bezelMat = new THREE.MeshStandardMaterial({
            color: 0xfed488,
            metalness: 0.9,
            roughness: 0.2
        });
        var bezelMesh = new THREE.Mesh(bezelGeo, bezelMat);
        sealGroup.add(bezelMesh);

        var clock = new THREE.Clock();
        var animId = null;
        var isVisible = true;

        function anim() {
            animId = requestAnimationFrame(anim);
            if (!isVisible) return;
            var t = clock.getElapsedTime();
            sealGroup.rotation.y = Math.sin(t * 1.6) * 0.35;
            sealGroup.rotation.x = Math.cos(t * 1.3) * 0.15;
            renderer.render(scene, camera);
        }
        anim();

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                rimGeo.dispose();
                rimMat.dispose();
                bezelGeo.dispose();
                bezelMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

        ExperienceManager.register(instance);
        return instance;
    }

    // ── 8. CAMPUS LIVE LOBBY (ISOMETRIC VIRTUAL CAMPUS HUB) ───────────────────
    // Optional, ultra-lightweight 3D campus hub with live class beacons & list view fallback
    function initCampusLobby(containerId, options) {
        var container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!container) return null;

        options = options || {};
        var liveSessions = options.liveSessions || [];

        // Build container DOM with 3D/List toggle
        container.innerHTML = 
            '<div class="relative w-full rounded-2xl border border-outline-variant bg-surface-container-lowest overflow-hidden shadow-sm">' +
                '<div class="px-5 py-3.5 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between">' +
                    '<div class="flex items-center gap-2">' +
                        '<span class="material-symbols-outlined text-primary text-[18px]">domain</span>' +
                        '<h4 class="text-xs font-bold text-primary">Navotas Polytechnic College · Campus Live Hub</h4>' +
                    '</div>' +
                    '<div class="flex items-center gap-1.5 no-print">' +
                        '<button id="campus-toggle-3d" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary text-on-primary">3D View</button>' +
                        '<button id="campus-toggle-list" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-outline-variant text-on-surface hover:bg-surface-container">List View</button>' +
                    '</div>' +
                '</div>' +
                '<div id="campus-3d-stage" class="relative w-full h-56 sm:h-64 bg-slate-950/90 overflow-hidden"></div>' +
                '<div id="campus-list-stage" class="hidden p-4 divide-y divide-outline-variant/40 max-h-64 overflow-y-auto"></div>' +
            '</div>';

        var stage3D = container.querySelector('#campus-3d-stage');
        var stageList = container.querySelector('#campus-list-stage');
        var btn3D = container.querySelector('#campus-toggle-3d');
        var btnList = container.querySelector('#campus-toggle-list');

        // Populate fallback list
        if (liveSessions.length === 0) {
            stageList.innerHTML = '<p class="text-xs text-on-surface-variant text-center py-6">No active synchronous classes at this time.</p>';
        } else {
            stageList.innerHTML = liveSessions.map(function (s) {
                return '<div class="py-2.5 flex items-center justify-between gap-3">' +
                       '<div><span class="font-mono text-xs font-bold text-primary">' + (s.course_code || 'CLASS') + '</span> ' +
                       '<span class="text-xs font-semibold text-on-surface">(' + (s.section || '2A') + ')</span>' +
                       '<p class="text-[11px] text-on-surface-variant">' + (s.instructor || 'Faculty') + ' · ' + (s.topic || 'Synchronous Lecture') + '</p></div>' +
                       '<span class="px-2 py-0.5 rounded-full bg-red-600/20 text-red-600 font-mono text-[10px] font-bold animate-pulse">LIVE</span>' +
                       '</div>';
            }).join('');
        }

        // Toggle Handlers
        btn3D.addEventListener('click', function () {
            stage3D.classList.remove('hidden');
            stageList.classList.add('hidden');
            btn3D.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary text-on-primary';
            btnList.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-outline-variant text-on-surface hover:bg-surface-container';
        });
        btnList.addEventListener('click', function () {
            stage3D.classList.add('hidden');
            stageList.classList.remove('hidden');
            btnList.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary text-on-primary';
            btn3D.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-outline-variant text-on-surface hover:bg-surface-container';
        });

        if (!WEBGL_AVAILABLE || REDUCED_MOTION || typeof THREE === 'undefined' || ExperienceManager.capabilities.tier === 'low') {
            btnList.click(); // Default to list on low power or reduced motion
            return {
                destroy: function () {}
            };
        }

        // Lightweight Isometric 3D Scene
        var canvas = document.createElement('canvas');
        canvas.className = 'w-full h-full block';
        stage3D.appendChild(canvas);

        var rect = stage3D.getBoundingClientRect();
        var width = rect.width || 500;
        var height = rect.height || 240;

        var scene = new THREE.Scene();
        scene.background = new THREE.Color(0x060b14);

        var aspect = width / height;
        var d = 60;
        var camera = new THREE.OrthographicCamera(-d * aspect, d * aspect, d, -d, 1, 1000);
        camera.position.set(100, 100, 100);
        camera.lookAt(0, 0, 0);

        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
        renderer.setSize(width, height);

        var ambLight = new THREE.AmbientLight(0xffffff, 0.75);
        scene.add(ambLight);
        var dirLight = new THREE.DirectionalLight(0xfffaed, 0.85);
        dirLight.position.set(60, 100, 40);
        scene.add(dirLight);

        // Ground Platform
        var baseGeo = new THREE.BoxGeometry(90, 4, 90);
        var baseMat = new THREE.MeshStandardMaterial({ color: 0x0a1e36, roughness: 0.8 });
        var base = new THREE.Mesh(baseGeo, baseMat);
        base.position.y = -2;
        scene.add(base);

        // 4 Campus Buildings (Virtual Class Hall, Admin Center, Library Hub, Lecture Wing)
        var buildingGroup = new THREE.Group();
        scene.add(buildingGroup);

        var bMat = new THREE.MeshStandardMaterial({ color: 0x1e3a8a, metalness: 0.3, roughness: 0.4 });
        var bMatGold = new THREE.MeshStandardMaterial({ color: 0xfed488, metalness: 0.5, roughness: 0.3 });

        var buildings = [
            { x: -22, z: -22, w: 24, h: 22, d: 24, name: 'Virtual Class Hall', live: liveSessions.length > 0 },
            { x: 22, z: -22, w: 20, h: 16, d: 20, name: 'Academic Admin Center', live: false },
            { x: -22, z: 22, w: 22, h: 18, d: 20, name: 'Campus AI & Library', live: false },
            { x: 22, z: 22, w: 26, h: 14, d: 24, name: 'Lecture & Lab Wing', live: false }
        ];

        var beaconGeo = new THREE.ConeGeometry(3, 8, 16);
        beaconGeo.rotateX(Math.PI);
        var beaconMat = new THREE.MeshBasicMaterial({ color: 0xef4444 });
        var beacons = [];

        buildings.forEach(function (b) {
            var geo = new THREE.BoxGeometry(b.w, b.h, b.d);
            var m = new THREE.Mesh(geo, b.live ? bMatGold : bMat);
            m.position.set(b.x, b.h / 2, b.z);
            buildingGroup.add(m);

            if (b.live) {
                var beacon = new THREE.Mesh(beaconGeo, beaconMat);
                beacon.position.set(b.x, b.h + 8, b.z);
                buildingGroup.add(beacon);
                beacons.push(beacon);
            }
        });

        var animId = null;
        var isVisible = true;
        var clock = new THREE.Clock();

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible) return;

            var t = clock.getElapsedTime();
            buildingGroup.rotation.y = t * 0.08;

            beacons.forEach(function (b) {
                b.position.y = 30 + Math.sin(t * 3) * 2;
                b.rotation.y += 0.04;
            });

            renderer.render(scene, camera);
        }

        animate();

        function onResize() {
            var r = stage3D.getBoundingClientRect();
            var w = r.width || 500;
            var h = r.height || 240;
            var asp = w / h;
            camera.left = -d * asp;
            camera.right = d * asp;
            camera.top = d;
            camera.bottom = -d;
            camera.updateProjectionMatrix();
            renderer.setSize(w, h);
        }
        window.addEventListener('resize', onResize);

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                window.removeEventListener('resize', onResize);
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                baseGeo.dispose();
                baseMat.dispose();
                bMat.dispose();
                bMatGold.dispose();
                beaconGeo.dispose();
                beaconMat.dispose();
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

            ExperienceManager.register(instance);
        return instance;
    }

    // ── 9. MICRO 3D INTERACTIVE ICON & BADGE SYSTEM ─────────────────────────
    /**
     * create3DIcon: Injects a micro WebGL 3D element into any icon wrapper or container
     * @param {string|HTMLElement} target - Element or CSS selector
     * @param {'ai'|'live'|'qr'|'medal'|'seal'|'academic'} type - Icon aesthetic type
     * @param {object} [opts] - Optional overrides (size, color, speed)
     */
    function create3DIcon(target, type, opts) {
        if (!WEBGL_AVAILABLE || REDUCED_MOTION) return null;

        var el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return null;

        opts = opts || {};
        var rect = el.getBoundingClientRect();
        var width = opts.size || rect.width || 44;
        var height = opts.size || rect.height || 44;
        if (width <= 0) width = 44;
        if (height <= 0) height = 44;

        var canvas = document.createElement('canvas');
        canvas.className = 'npc-3d-micro-icon';
        canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:2;';

        var cs = window.getComputedStyle(el);
        if (cs.position === 'static') {
            el.style.position = 'relative';
        }

        el.appendChild(canvas);

        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({
                canvas: canvas,
                alpha: true,
                antialias: true,
                powerPreference: 'low-power'
            });
            renderer.setSize(width, height, false);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        } catch (e) {
            if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
            return null;
        }

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
        camera.position.z = 3.8;

        var amb = new THREE.AmbientLight(0xffffff, 0.8);
        scene.add(amb);
        var dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
        dirLight.position.set(2, 3, 4);
        scene.add(dirLight);

        var group = new THREE.Group();
        scene.add(group);

        var disposables = [];
        type = type || 'ai';

        if (type === 'ai') {
            var coreGeo = new THREE.IcosahedronGeometry(0.85, 0);
            var coreMat = new THREE.MeshStandardMaterial({
                color: PALETTE.azure,
                emissive: 0x0284c7,
                emissiveIntensity: 0.4,
                metalness: 0.8,
                roughness: 0.2,
                wireframe: true
            });
            var core = new THREE.Mesh(coreGeo, coreMat);
            group.add(core);

            var ringGeo = new THREE.TorusGeometry(1.25, 0.05, 8, 32);
            var ringMat = new THREE.MeshStandardMaterial({
                color: PALETTE.gold,
                metalness: 0.9,
                roughness: 0.15
            });
            var ring = new THREE.Mesh(ringGeo, ringMat);
            ring.rotation.x = Math.PI / 4;
            group.add(ring);

            disposables.push(coreGeo, coreMat, ringGeo, ringMat);
        } else if (type === 'live') {
            var sphereGeo = new THREE.SphereGeometry(0.48, 16, 16);
            var sphereMat = new THREE.MeshStandardMaterial({
                color: 0xef4444,
                emissive: 0xdc2626,
                emissiveIntensity: 0.85,
                metalness: 0.4,
                roughness: 0.2
            });
            var sphere = new THREE.Mesh(sphereGeo, sphereMat);
            group.add(sphere);

            var wRingGeo1 = new THREE.RingGeometry(0.7, 0.85, 32);
            var wRingMat1 = new THREE.MeshBasicMaterial({
                color: 0xef4444,
                side: THREE.DoubleSide,
                transparent: true,
                opacity: 0.85
            });
            var ring1 = new THREE.Mesh(wRingGeo1, wRingMat1);
            group.add(ring1);

            var wRingGeo2 = new THREE.RingGeometry(1.05, 1.2, 32);
            var wRingMat2 = new THREE.MeshBasicMaterial({
                color: 0xf59e0b,
                side: THREE.DoubleSide,
                transparent: true,
                opacity: 0.55
            });
            var ring2 = new THREE.Mesh(wRingGeo2, wRingMat2);
            group.add(ring2);

            disposables.push(sphereGeo, sphereMat, wRingGeo1, wRingMat1, wRingGeo2, wRingMat2);
        } else if (type === 'qr') {
            var boxGeo = new THREE.BoxGeometry(1.6, 1.6, 0.05);
            var edgeGeo = new THREE.EdgesGeometry(boxGeo);
            var edgeMat = new THREE.LineBasicMaterial({ color: PALETTE.azure });
            var wireBox = new THREE.LineSegments(edgeGeo, edgeMat);
            group.add(wireBox);

            var laserGeo = new THREE.BoxGeometry(1.5, 0.05, 0.05);
            var laserMat = new THREE.MeshBasicMaterial({ color: 0x10b981 });
            var laser = new THREE.Mesh(laserGeo, laserMat);
            group.add(laser);

            disposables.push(boxGeo, edgeGeo, edgeMat, laserGeo, laserMat);
        } else if (type === 'medal' || type === 'seal') {
            var cylGeo = new THREE.CylinderGeometry(1.1, 1.1, 0.15, 32);
            var goldMat = new THREE.MeshStandardMaterial({
                color: 0xf59e0b,
                metalness: 0.95,
                roughness: 0.15,
                emissive: 0x78350f,
                emissiveIntensity: 0.2
            });
            var coin = new THREE.Mesh(cylGeo, goldMat);
            coin.rotation.x = Math.PI / 2;
            group.add(coin);

            var starGeo = new THREE.OctahedronGeometry(0.45, 0);
            var starMat = new THREE.MeshStandardMaterial({
                color: 0xfffbeb,
                metalness: 0.8,
                roughness: 0.2
            });
            var star = new THREE.Mesh(starGeo, starMat);
            star.position.z = 0.1;
            group.add(star);

            disposables.push(cylGeo, goldMat, starGeo, starMat);
        } else {
            var dodecaGeo = new THREE.DodecahedronGeometry(1.0, 0);
            var dodecaMat = new THREE.MeshStandardMaterial({
                color: PALETTE.navyLight,
                metalness: 0.7,
                roughness: 0.3,
                wireframe: true
            });
            var dodeca = new THREE.Mesh(dodecaGeo, dodecaMat);
            group.add(dodeca);
            disposables.push(dodecaGeo, dodecaMat);
        }

        var animId;
        var clock = new THREE.Clock();
        var isVisible = true;
        var isIntersecting = true;

        var observer = null;
        if (typeof IntersectionObserver !== 'undefined') {
            observer = new IntersectionObserver(function (entries) {
                if (entries && entries[0]) {
                    isIntersecting = entries[0].isIntersecting;
                }
            }, { threshold: 0.05 });
            observer.observe(el);
        }

        function onMouseMove(e) {
            var b = el.getBoundingClientRect();
            var mx = ((e.clientX - b.left) / b.width - 0.5) * 2;
            var my = ((e.clientY - b.top) / b.height - 0.5) * 2;
            group.rotation.y = mx * 0.5;
            group.rotation.x = -my * 0.5;
        }
        function onMouseLeave() {
            group.rotation.x = 0;
            group.rotation.y = 0;
        }

        el.addEventListener('mousemove', onMouseMove);
        el.addEventListener('mouseleave', onMouseLeave);

        function animate() {
            animId = requestAnimationFrame(animate);
            if (!isVisible || !isIntersecting) return;

            var t = clock.getElapsedTime();
            if (type === 'ai') {
                core.rotation.y = t * 0.9;
                core.rotation.x = t * 0.45;
                ring.rotation.z = t * 1.3;
            } else if (type === 'live') {
                var s1 = 1 + (Math.sin(t * 4) * 0.22);
                ring1.scale.set(s1, s1, 1);
                ring1.material.opacity = 0.3 + (Math.cos(t * 4) * 0.4);

                var s2 = 1 + (Math.cos(t * 3) * 0.25);
                ring2.scale.set(s2, s2, 1);
                ring2.material.opacity = 0.2 + (Math.sin(t * 3) * 0.3);

                sphere.scale.setScalar(0.92 + Math.sin(t * 6) * 0.12);
            } else if (type === 'qr') {
                wireBox.rotation.z = Math.sin(t * 0.8) * 0.08;
                laser.position.y = Math.sin(t * 3) * 0.65;
            } else if (type === 'medal' || type === 'seal') {
                group.rotation.y = t * 1.2;
                group.position.y = Math.sin(t * 2.5) * 0.06;
            } else {
                group.rotation.y = t * 0.6;
                group.rotation.x = t * 0.3;
            }

            renderer.render(scene, camera);
        }

        animate();

        var instance = {
            onVisibilityChange: function (vis) {
                isVisible = vis;
            },
            destroy: function () {
                cancelAnimationFrame(animId);
                el.removeEventListener('mousemove', onMouseMove);
                el.removeEventListener('mouseleave', onMouseLeave);
                if (observer) observer.disconnect();
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                disposables.forEach(function (d) { if (d && d.dispose) d.dispose(); });
                renderer.dispose();
                ExperienceManager.unregister(instance);
            }
        };

        ExperienceManager.register(instance);
        return instance;
    }

    // Auto-discover and attach micro 3D icons across the DOM
    function initAuto3DIcons() {
        if (!WEBGL_AVAILABLE || REDUCED_MOTION) return;
        var targets = document.querySelectorAll('[data-npc-3d-icon]');
        targets.forEach(function (el) {
            if (el.dataset.npc3dMounted) return;
            el.dataset.npc3dMounted = 'true';
            var iconType = el.getAttribute('data-npc-3d-icon') || 'ai';
            create3DIcon(el, iconType);
        });
    }

    // Public Module API
    return {
        hasWebGL: WEBGL_AVAILABLE,
        isReducedMotion: REDUCED_MOTION,
        palette: PALETTE,
        ExperienceManager: ExperienceManager,
        initBackground: initBackground,
        initLoginHero: initLoginHero,
        initScannerHUD: initScannerHUD,
        celebrate3D: celebrate3D,
        initAiCore: initAiCore,
        renderVerificationSeal: renderVerificationSeal,
        initCampusLobby: initCampusLobby,
        create3DIcon: create3DIcon,
        initAuto3DIcons: initAuto3DIcons
    };
});
