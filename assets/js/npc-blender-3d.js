/**
 * npc-blender-3d.js — State-of-the-Art Architectural BIM & Walkthrough Engine
 * Navotas Polytechnic College (NPC) Multi-Purpose Academic Building
 * 
 * Features:
 *  - Screen-filling massive scale (1.4x physical geometry)
 *  - Full First-Person Architectural Walkthrough Mode (WASD, Mouse Look 360°, Touch D-Pad)
 *  - Real-time room underfoot detector while walking
 *  - Photorealistic PBR architectural materials (polished terrazzo, frosted glass, chrome, cedar louvers)
 *  - Interior warm LED troffer illumination glowing from classrooms
 *  - Architectural CAD drafting pedestal with elevation benchmarks (+0.00m to +21.4m), compass rose & axis bubbles
 *  - Floor Slicing / Dollhouse Mode (1F, 2F, 3F, 4F, RD)
 *  - Unlocked deep zoom capability down to 0.05m
 */

(function (window) {
    'use strict';

    class NpcBlender3D {
        constructor(containerId, options = {}) {
            this.container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
            if (!this.container) throw new Error('3D container element not found.');

            this.options = Object.assign({
                modelUrl: '/assets/models/npc_campus_blender.glb',
                onRoomClick: null,
                onRoomHover: null,
                onLoaded: null,
                onFloorChange: null,
                onWalkModeChange: null,
                onWalkRoomChange: null
            }, options);

            this.scene = null;
            this.camera = null;
            this.renderer = null;
            this.controls = null;
            this.model = null;
            this.modelScale = 1.45; // Super massive scale tailored for 76m x 76m building to ensure complete fit
            this.roomMeshes = new Map(); // roomId -> mesh[]
            this.roomLabels = [];
            this.floorMarkers = [];
            this.elevationMarkers = [];
            this.selectedRoomId = null;
            this.hoveredRoomId = null;
            this.currentFloor = '2F';
            this.pointerDownPos = { x: 0, y: 0 };
            this.pointerDownTime = 0;
            this.roomHighlightBox = null;

            // Floor elevation map (scaled to 1.45x)
            this.floorHeights = {
                '1F': 0.0 * this.modelScale,
                '2F': 4.20 * this.modelScale,  // 6.09m
                '3F': 7.90 * this.modelScale,  // 11.45m
                '4F': 11.60 * this.modelScale, // 16.82m
                'RD': 15.30 * this.modelScale  // 22.18m
            };

            // Walk Mode State
            this.isWalkMode = false;
            this.walkSpeed = 6.5; // m/s
            this.walkVelocity = new THREE.Vector3();
            this.walkEuler = new THREE.Euler(0, 0, 0, 'YXZ');
            this.walkKeys = {
                forward: false,
                backward: false,
                left: false,
                right: false,
                turnLeft: false,
                turnRight: false,
                sprint: false
            };
            this.walkFloorElevation = this.floorHeights['2F'];
            this.isPointerDown = false;
            this.lastPointerX = 0;
            this.lastPointerY = 0;
            this.lastWalkTime = performance.now();
            this.currentWalkRoom = null;
            this.savedOrbitPos = new THREE.Vector3();
            this.savedOrbitTarget = new THREE.Vector3();

            this.raycaster = new THREE.Raycaster();
            this.mouse = new THREE.Vector2(-999, -999);

            this.init();
        }

        init() {
            const width = this.container.clientWidth || window.innerWidth;
            const height = this.container.clientHeight || window.innerHeight;

            // 1. Scene — Photorealistic Architectural Studio White Canvas
            this.scene = new THREE.Scene();
            this.scene.background = new THREE.Color(0xf6f8fa);
            this.scene.fog = new THREE.FogExp2(0xf6f8fa, 0.0006);

            // 2. Camera — Calibrated 38° FOV for deep, screen-filling architectural presence
            this.camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 2000);
            const initY = (this.floorHeights['2F'] || 8.19) + 1.2;
            this.camera.position.set(38, initY + 44, 48);

            // 3. Renderer with PBR ACES Filmic Tone Mapping & High-End Soft Shadows
            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
            this.renderer.setSize(width, height);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
            this.renderer.toneMappingExposure = 1.02;
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            this.container.appendChild(this.renderer.domElement);

            // 4. OrbitControls — Unlocked smooth navigation & deep zoom
            if (typeof THREE.OrbitControls === 'function') {
                this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
                this.controls.enableDamping = true;
                this.controls.dampingFactor = 0.08;
                this.controls.screenSpacePanning = true;
                this.controls.minDistance = 0.05; // Deep zoom into desks & glassboards
                this.controls.maxDistance = 500;
                this.controls.maxPolarAngle = Math.PI / 2 - 0.01;
                this.controls.target.set(0, initY, 0);
            }

            // 5. High-End Studio Architectural Lighting (Matching V-Ray Reference Photos)
            this.setupLighting();

            // 6. Architectural Studio Pedestal with Ground Shadow Plane & Compass
            this.setupPedestal();

            // 7. Load Blender GLB Model
            this.loadBlenderModel();

            // 8. Event Listeners (Orbit & Walk)
            this.bindEvents();

            // 9. Animation Loop
            this.animate = this.animate.bind(this);
            requestAnimationFrame(this.animate);
        }

        setupLighting() {
            // Ambient architectural daylight - calibrated for rich color saturation & depth
            const ambient = new THREE.AmbientLight(0xffffff, 0.78);
            this.scene.add(ambient);

            // Hemisphere Sky & Ground Bounce
            const hemi = new THREE.HemisphereLight(0xffffff, 0xd0d7de, 0.50);
            hemi.position.set(0, 100, 0);
            this.scene.add(hemi);

            // Directional Sun Light (crisp architectural sun casting soft natural shadows)
            const sun = new THREE.DirectionalLight(0xfffaed, 1.55);
            sun.position.set(60, 110, 50);
            sun.castShadow = true;
            sun.shadow.mapSize.width = 4096;
            sun.shadow.mapSize.height = 4096;
            sun.shadow.camera.near = 10;
            sun.shadow.camera.far = 300;
            const d = 75;
            sun.shadow.camera.left = -d;
            sun.shadow.camera.right = d;
            sun.shadow.camera.top = d;
            sun.shadow.camera.bottom = -d;
            sun.shadow.bias = -0.00015;
            sun.shadow.radius = 2.0;
            this.scene.add(sun);

            // Soft Fill Light for clear visibility of opposite walls
            const fill = new THREE.DirectionalLight(0xecf0f5, 0.38);
            fill.position.set(-60, 50, -50);
            this.scene.add(fill);

            // Interior Warm LED Classroom Lights
            const interiorLight1 = new THREE.PointLight(0xfff7ed, 1.4, 35);
            interiorLight1.position.set(-20 * this.modelScale, this.floorHeights['2F'] + 3.2, -6 * this.modelScale);
            this.scene.add(interiorLight1);

            const interiorLight2 = new THREE.PointLight(0xfff7ed, 1.4, 35);
            interiorLight2.position.set(-7 * this.modelScale, this.floorHeights['2F'] + 3.2, 18 * this.modelScale);
            this.scene.add(interiorLight2);

            const interiorLight3 = new THREE.PointLight(0xfff7ed, 1.4, 35);
            interiorLight3.position.set(15 * this.modelScale, this.floorHeights['2F'] + 3.2, 6 * this.modelScale);
            this.scene.add(interiorLight3);
        }

        setupPedestal() {
            // 1. Studio Ground Soft Shadow Receiver Plane
            const shadowGeo = new THREE.PlaneGeometry(350, 350);
            const shadowMat = new THREE.ShadowMaterial({ opacity: 0.18 });
            const shadowMesh = new THREE.Mesh(shadowGeo, shadowMat);
            shadowMesh.rotation.x = -Math.PI / 2;
            shadowMesh.position.y = -0.28;
            shadowMesh.receiveShadow = true;
            this.scene.add(shadowMesh);

            // 2. Crisp subtle architectural drafting grid
            const gridHelper = new THREE.GridHelper(150, 75, 0x94a3b8, 0xe2e8f0);
            gridHelper.position.y = -0.27;
            this.scene.add(gridHelper);

            // 3. Compass Rose / North Arrow on ground
            this.createCompassRose();

            // 4. Architectural CAD Grid Axis Markers (A, B, C... 1, 2, 3...)
            this.createCadAxisMarkers();

            // 5. Elevation Benchmark Markers (+0.00m, +4.20m, +7.90m, +11.60m, +15.30m)
            this.createElevationBenchmarks();
        }


        createCompassRose() {
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 256;
            const ctx = canvas.getContext('2d');

            ctx.strokeStyle = '#00e5ff';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.arc(128, 128, 110, 0, Math.PI * 2);
            ctx.stroke();

            // North Pointer
            ctx.fillStyle = '#00e5ff';
            ctx.beginPath();
            ctx.moveTo(128, 20);
            ctx.lineTo(145, 128);
            ctx.lineTo(128, 115);
            ctx.lineTo(111, 128);
            ctx.closePath();
            ctx.fill();

            // Text N
            ctx.font = 'bold 36px monospace';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('N', 128, 55);

            const tex = new THREE.CanvasTexture(canvas);
            const geo = new THREE.PlaneGeometry(16, 16);
            const mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: 0.65 });
            const mesh = new THREE.Mesh(geo, mat);
            mesh.rotation.x = -Math.PI / 2;
            mesh.position.set(-42, -0.25, -42);
            this.scene.add(mesh);
        }

        createCadAxisMarkers() {
            const axisLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            const gridX = [-36.0, -28.0, -20.0, -12.0, -4.0, 4.0, 12.0, 20.0, 28.0, 36.0];
            axisLetters.forEach((letter, i) => {
                const sprite = this.createAxisBubbleSprite(letter);
                sprite.position.set(gridX[i] * this.modelScale, 0.4, 42 * this.modelScale);
                this.scene.add(sprite);
            });

            const axisNumbers = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
            const gridZ = [-36.0, -28.0, -20.0, -12.0, -4.0, 4.0, 12.0, 20.0, 28.0, 36.0];
            axisNumbers.forEach((num, i) => {
                const sprite = this.createAxisBubbleSprite(num);
                sprite.position.set(-42 * this.modelScale, 0.4, gridZ[i] * this.modelScale);
                this.scene.add(sprite);
            });
        }

        createAxisBubbleSprite(text) {
            const canvas = document.createElement('canvas');
            canvas.width = 128;
            canvas.height = 128;
            const ctx = canvas.getContext('2d');

            ctx.fillStyle = 'rgba(11, 17, 30, 0.92)';
            ctx.strokeStyle = '#00e5ff';
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.arc(64, 64, 52, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 54px monospace';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, 64, 66);

            const tex = new THREE.CanvasTexture(canvas);
            const mat = new THREE.SpriteMaterial({ map: tex, transparent: true });
            const sprite = new THREE.Sprite(mat);
            sprite.scale.set(3.2, 3.2, 1.0);
            return sprite;
        }

        createElevationBenchmarks() {
            const benchmarks = [
                { text: '▼ EL. +21.4m · ROOF DECK & SOLAR', y: this.floorHeights['RD'], color: '#00e5ff' },
                { text: '▼ EL. +16.2m · 4F GYMNASIUM ARENA', y: this.floorHeights['4F'], color: '#eab308' },
                { text: '▼ EL. +11.1m · 3F CENTRAL LIBRARY', y: this.floorHeights['3F'], color: '#a855f7' },
                { text: '▼ EL. +5.88m · 2F ACADEMIC ROOMS', y: this.floorHeights['2F'], color: '#10b981' },
                { text: '▼ EL. ±0.00m · 1F GROUND LOBBY', y: this.floorHeights['1F'], color: '#38bdf8' }
            ];

            benchmarks.forEach(bm => {
                const canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 72;
                const ctx = canvas.getContext('2d');

                ctx.fillStyle = 'rgba(7, 12, 22, 0.88)';
                ctx.strokeStyle = bm.color;
                ctx.lineWidth = 3;
                ctx.fillRect(4, 4, canvas.width - 8, canvas.height - 8);
                ctx.strokeRect(4, 4, canvas.width - 8, canvas.height - 8);

                ctx.fillStyle = bm.color;
                ctx.font = 'bold 24px monospace';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(bm.text, 18, canvas.height / 2);

                const tex = new THREE.CanvasTexture(canvas);
                const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
                const sprite = new THREE.Sprite(mat);
                sprite.scale.set(11.0, 1.6, 1.0);
                sprite.position.set(42 * this.modelScale, bm.y + 0.6, -38 * this.modelScale);
                this.scene.add(sprite);
                this.elevationMarkers.push(sprite);
            });
        }

        loadBlenderModel() {
            const loader = new THREE.GLTFLoader();
            loader.load(
                this.options.modelUrl,
                (gltf) => {
                    this.model = gltf.scene;

                    // Physical 1.4x scaling for commanding size ("SUBRA LAKI")
                    this.model.scale.set(this.modelScale, this.modelScale, this.modelScale);

                    // Traverse, classify objects and enhance materials
                    this.model.traverse((child) => {
                        if (child.isMesh) {
                            child.castShadow = true;
                            child.receiveShadow = true;
                            child.userData.originalMaterial = child.material;

                            const name = child.name;

                            // Architectural Material Enhancements ("kulay tulad sa picture", "kulay ng room is white tas brown yung pinto")
                            if (child.material) {
                                child.material = child.material.clone();
                                child.userData.originalMaterial = child.material;

                                const lower = name.toLowerCase();

                                const matName = (child.material.name || '').toLowerCase();

                                if (lower.includes('wall') && !lower.includes('cap') && !lower.includes('glass')) {
                                    // Vibrant architectural wall coloring
                                    if (matName.includes('accent_navy') || lower.includes('navy') || lower.includes('signage')) {
                                        // Deep Academic Navy Accent Wall
                                        child.material.color = new THREE.Color(0x0a1e3f);
                                        child.material.roughness = 0.28;
                                    } else if (matName.includes('accent_gold') || lower.includes('trim') || lower.includes('gold') || lower.includes('seal')) {
                                        // NPC Heritage Gold Accent
                                        child.material.color = new THREE.Color(0xf59e0b);
                                        child.material.roughness = 0.22;
                                        child.material.metalness = 0.35;
                                    } else if (matName.includes('tech_blue')) {
                                        // Tech Cyan/Blue Feature Wall
                                        child.material.color = new THREE.Color(0x0284c7);
                                        child.material.roughness = 0.26;
                                    } else if (matName.includes('tech_slate') || lower.includes('complab')) {
                                        // Modern Tech Slate Wall
                                        child.material.color = new THREE.Color(0x1e293b);
                                        child.material.roughness = 0.32;
                                    } else if (matName.includes('teal') || lower.includes('restroom') || lower.includes('partition')) {
                                        // Glazed Restroom Aqua Teal Wall
                                        child.material.color = new THREE.Color(0x0d9488);
                                        child.material.roughness = 0.22;
                                    } else if (matName.includes('gym_oak') || lower.includes('gym_wall_oak')) {
                                        // Sports Arena Warm Oak Wainscoting
                                        child.material.color = new THREE.Color(0xb45309);
                                        child.material.roughness = 0.28;
                                    } else if (matName.includes('warm_cream') || lower.includes('classroom')) {
                                        // Soft Warm Architectural Cream Wall
                                        child.material.color = new THREE.Color(0xf3eee5);
                                        child.material.roughness = 0.36;
                                    } else if (matName.includes('corridor')) {
                                        // Clean Pearl Gray Wall
                                        child.material.color = new THREE.Color(0xe2e8f0);
                                        child.material.roughness = 0.32;
                                    } else {
                                        child.material.color = new THREE.Color(0xf1ede4);
                                        child.material.roughness = 0.35;
                                    }
                                } else if (lower.includes('column') || matName.includes('col_navy')) {
                                    // Institutional Deep Navy Structural Columns Grid
                                    child.material.color = new THREE.Color(0x001736);
                                    child.material.roughness = 0.25;
                                } else if (lower.startsWith('door_') || lower.includes('door_leaf')) {
                                    // WARM RICH BROWN TIMBER DOORS ("brown yung pinto")
                                    child.material.color = new THREE.Color(0x522b11);
                                    child.material.roughness = 0.32;
                                    child.material.metalness = 0.04;
                                } else if (lower.includes('doorframe') || lower.includes('door_frame')) {
                                    // DARK ESPRESSO TIMBER CASING
                                    child.material.color = new THREE.Color(0x1b120a);
                                    child.material.roughness = 0.38;
                                } else if (lower.includes('wallcap') || lower.includes('wall_cap')) {
                                    // ARCHITECTURAL DARK OUTLINE RIM FOR SHARP BLUEPRINT EDGES
                                    child.material.color = new THREE.Color(0x11161d);
                                    child.material.roughness = 0.25;
                                } else if (lower.includes('gym') || lower.includes('court')) {
                                    if (lower.includes('line') || lower.includes('circle') || lower.includes('bound')) {
                                        // WHITE COURT MARKINGS
                                        child.material.color = new THREE.Color(0xffffff);
                                        child.material.roughness = 0.15;
                                    } else if (lower.includes('rim')) {
                                        // VIBRANT ORANGE-RED RIM
                                        child.material.color = new THREE.Color(0xef4444);
                                    } else if (lower.includes('hoop') || lower.includes('post') || lower.includes('stanchion')) {
                                        // ROYAL BLUE BASKETBALL STANCHION (Picture 1 Match)
                                        child.material.color = new THREE.Color(0x1d4ed8);
                                        child.material.roughness = 0.25;
                                    } else if (lower.includes('key')) {
                                        // CONTRASTING WARM WOOD KEY
                                        child.material.color = new THREE.Color(0xbd6f1e);
                                    } else if (lower.includes('backboard')) {
                                        // GLASS BACKBOARD
                                        child.material.transparent = true;
                                        child.material.opacity = 0.85;
                                        child.material.roughness = 0.05;
                                    } else {
                                        // GOLDEN POLISHED MAPLE HARDWOOD BASKETBALL COURT (Picture 1 Match)
                                        child.material.color = new THREE.Color(0xdf8d32);
                                        child.material.roughness = 0.18;
                                        child.material.metalness = 0.02;
                                    }
                                } else if (lower.includes('cr_floor') || lower.includes('restroom_floor')) {
                                    // SLATE BLUE-GRAY RESTROOM TILE (Picture 1 & 2 Match)
                                    child.material.color = new THREE.Color(0x546577);
                                    child.material.roughness = 0.28;
                                } else if (lower.includes('partition')) {
                                    // VIBRANT TEAL/CYAN RESTROOM PARTITIONS (Picture 1 & 2 Match)
                                    child.material.color = new THREE.Color(0x197d8c);
                                    child.material.roughness = 0.28;
                                } else if (lower.includes('toilet') || lower.includes('bowl') || lower.includes('urinal')) {
                                    // WHITE PORCELAIN TOILETS
                                    child.material.color = new THREE.Color(0xfcfcfc);
                                    child.material.roughness = 0.10;
                                } else if (lower.includes('condenser') || lower.includes('chiller')) {
                                    if (lower.includes('fan')) {
                                        child.material.color = new THREE.Color(0x111111);
                                    } else {
                                        // POPPING FIRE-ENGINE RED ROOFTOP CHILLERS (Picture 0 & 1 Match)
                                        child.material.color = new THREE.Color(0xdc2626);
                                        child.material.roughness = 0.28;
                                    }
                                } else if (lower.includes('fire_alarm') || lower.includes('strobe')) {
                                    // POPPING FIRE-ALARM RED CORRIDOR BOX
                                    child.material.color = new THREE.Color(0xdc2626);
                                } else if (lower.includes('solar')) {
                                    if (lower.includes('frame')) {
                                        child.material.color = new THREE.Color(0x94a3b8);
                                    } else {
                                        // DEEP NAVY BLUE SOLAR PHOTOVOLTAIC CELLS
                                        child.material.color = new THREE.Color(0x1e3a8a);
                                    }
                                } else if (lower.includes('lab_bench') || lower.includes('workbench')) {
                                    // DARK CHARCOAL SLATE COMPUTER LAB BENCHES
                                    child.material.color = new THREE.Color(0x1e293b);
                                    child.material.roughness = 0.30;
                                } else if (lower.includes('monitor') || lower.includes('screen_pc')) {
                                    // BLACK LCD MONITORS
                                    child.material.color = new THREE.Color(0x0f172a);
                                    child.material.roughness = 0.20;
                                } else if (lower.includes('chair') || lower.includes('seat')) {
                                    // DARK CHARCOAL STUDENT CHAIRS
                                    child.material.color = new THREE.Color(0x334155);
                                    child.material.roughness = 0.38;
                                } else if (lower.includes('desk') || lower.includes('podium') || lower.includes('bleacher')) {
                                    // WARM GOLDEN BIRCH CLASSROOM DESKS (Picture 2 & 3 Match)
                                    child.material.color = new THREE.Color(0xbe8242);
                                    child.material.roughness = 0.35;
                                } else if (lower.includes('glassboard')) {
                                    // HIGH-GLOSS MAGNETIC WHITE GLASSBOARD
                                    child.material.color = new THREE.Color(0xf8fafc);
                                    child.material.roughness = 0.08;
                                } else if (lower.includes('screen') || lower.includes('projector')) {
                                    // MATTE WHITE PROJECTOR SCREEN
                                    child.material.color = new THREE.Color(0xfcfcfc);
                                    child.material.roughness = 0.95;
                                } else if (lower.includes('glass') || lower.includes('clerestory') || lower.includes('rail')) {
                                    child.material.transparent = true;
                                    child.material.opacity = 0.82;
                                    child.material.roughness = 0.08;
                                } else if (lower.includes('louver') || lower.includes('timber')) {
                                    child.material.color = new THREE.Color(0xa56024);
                                    child.material.roughness = 0.40;
                                } else if (lower.startsWith('room_floor_') || lower.startsWith('floor_') || lower.includes('corridor')) {
                                    // WARM ARCHITECTURAL CREAM/SAND TERRAZZO (Warm contrast with crisp white walls)
                                    child.material.color = new THREE.Color(0xeae5d9);
                                    child.material.roughness = 0.26;
                                    child.material.metalness = 0.02;
                                }
                            }

                            if (name.startsWith('ROOM_FLOOR_') || name.startsWith('ROOM_WALLS_') || name.startsWith('DOOR_')) {
                                const parts = name.split('_');
                                const roomId = parts.slice(parts[0] === 'DOOR' ? 1 : 2).join('_');
                                child.userData.roomId = roomId;

                                if (!this.roomMeshes.has(roomId)) {
                                    this.roomMeshes.set(roomId, []);
                                }
                                this.roomMeshes.get(roomId).push(child);
                            }

                            if (name.includes('1F') || name.includes('Level_1')) child.userData.floor = '1F';
                            else if (name.includes('2F') || name.includes('Level_2')) child.userData.floor = '2F';
                            else if (name.includes('3F') || name.includes('Level_3')) child.userData.floor = '3F';
                            else if (name.includes('4F') || name.includes('Level_4')) child.userData.floor = '4F';
                            else if (name.includes('RD') || name.includes('Roof') || name.includes('Solar')) child.userData.floor = 'RD';
                        }
                    });

                    // Add Prominent 3D Room Badges
                    this.create3DRoomLabels();

                    this.scene.add(this.model);

                    // Initialize to 2F Academic dollhouse mode by default
                    this.setFloor('2F');

                    if (this.options.onLoaded) {
                        this.options.onLoaded(this);
                    }
                },
                undefined,
                (err) => {
                    console.error('Error loading Blender GLB model:', err);
                }
            );
        }

        create3DRoomLabels() {
            const roomMeta = {
                // 2F Academic Classrooms & Labs
                '2F-03': { text: 'ROOM 201 (Classroom 1)', color: '#10b981', floor: '2F' },
                '2F-04': { text: 'ROOM 202 (Classroom 2)', color: '#10b981', floor: '2F' },
                '2F-05': { text: 'ROOM 203 (Classroom 3)', color: '#10b981', floor: '2F' },
                '2F-06': { text: 'ROOM 204 (Classroom 4)', color: '#10b981', floor: '2F' },
                '2F-07': { text: 'ROOM 205 (Classroom 5)', color: '#10b981', floor: '2F' },
                '2F-08': { text: 'ROOM 206 (Classroom 6)', color: '#10b981', floor: '2F' },
                '2F-19': { text: 'ROOM 207 (Classroom 7)', color: '#10b981', floor: '2F' },
                '2F-20': { text: 'ROOM 208 (Classroom 8)', color: '#10b981', floor: '2F' },
                '2F-09': { text: 'COMP LAB 1 (50 PCs)', color: '#06b6d4', floor: '2F' },
                '2F-11': { text: 'COMP LAB 2 (JILO DERRAMAS)', color: '#f59e0b', floor: '2F' },
                '2F-15': { text: 'COMP LAB 3 (45 PCs)', color: '#06b6d4', floor: '2F' },
                '2F-17': { text: 'COMP LAB 4 (40 PCs)', color: '#06b6d4', floor: '2F' },
                '2F-01': { text: "CASHIER'S OFFICE", color: '#3b82f6', floor: '2F' },
                '2F-02': { text: "REGISTRAR'S OFFICE", color: '#3b82f6', floor: '2F' },
                '2F-13': { text: 'CANTEEN & CAFETERIA', color: '#f97316', floor: '2F' },
                '2F-28': { text: 'AVR (Audio-Visual Hall)', color: '#8b5cf6', floor: '2F' },

                // 3F Library & Science Labs
                '3F-01': { text: 'NPC CENTRAL LIBRARY (400 m²)', color: '#a855f7', floor: '3F' },
                '3F-09': { text: 'FACULTY ROOM 1 (Gen Ed)', color: '#3b82f6', floor: '3F' },
                '3F-23': { text: 'FACULTY ROOM 2 (Info Sciences)', color: '#3b82f6', floor: '3F' },
                '3F-10': { text: 'SCIENCE LAB 1 (Chemistry)', color: '#06b6d4', floor: '3F' },
                '3F-13': { text: 'SCIENCE LAB 2 (Physics)', color: '#06b6d4', floor: '3F' },
                '3F-15': { text: 'SCIENCE LAB 3 (Biology)', color: '#06b6d4', floor: '3F' },

                // 4F Gym & Administration
                '4F-GYM': { text: 'NPC GYMNASIUM & ARENA (750 Seats)', color: '#eab308', floor: '4F' },
                '4F-01': { text: "DEAN'S OFFICE & SUITE", color: '#3b82f6', floor: '4F' },

                // RD Roof Deck
                'RD-SOLAR': { text: 'SOLAR PANEL ARRAY (176 Panels)', color: '#38bdf8', floor: 'RD' },
                'RD-COURT': { text: 'OPEN SPORTS COURT', color: '#f59e0b', floor: 'RD' },
                'RD-EVENTS': { text: 'SPECIAL EVENTS DECK', color: '#10b981', floor: 'RD' },

                // 1F Ground Entrance
                '1F-LOBBY': { text: 'MAIN GRAND LOBBY', color: '#00e5ff', floor: '1F' }
            };

            Object.keys(roomMeta).forEach(id => {
                const info = roomMeta[id];
                const meshes = this.roomMeshes.get(id);
                let x = 0, y = 5.0, z = 0;

                if (meshes && meshes.length > 0) {
                    const box = new THREE.Box3();
                    meshes.forEach(m => box.expandByObject(m));
                    const center = new THREE.Vector3();
                    box.getCenter(center);
                    x = center.x;
                    y = box.max.y + 1.4;
                    z = center.z;
                } else {
                    return;
                }

                const sprite = this.createCanvasTextSprite(info.text, info.color);
                sprite.position.set(x, y, z);
                sprite.userData = { roomId: id, floor: info.floor, isRoomBadge: true };
                sprite.visible = (this.currentFloor === info.floor);
                this.scene.add(sprite);
                this.roomLabels.push(sprite);
            });

            // Floor Level Gates in All Levels Mode
            this.createFloorLevelGates();
        }

        createFloorLevelGates() {
            const gates = [
                { floor: '4F', text: 'LEVEL 4 · GYMNASIUM ARENA & DEAN SUITE', y: this.floorHeights['4F'] + 3.0, z: 0, color: '#eab308' },
                { floor: '3F', text: 'LEVEL 3 · CENTRAL LIBRARY & SCIENCE LABS', y: this.floorHeights['3F'] + 3.0, z: 0, color: '#a855f7' },
                { floor: '2F', text: 'LEVEL 2 · ACADEMIC CLASSROOMS (201-208) & COMP LABS', y: this.floorHeights['2F'] + 3.0, z: 0, color: '#10b981' },
                { floor: '1F', text: 'LEVEL 1 · MAIN ENTRANCE LOBBY & ADMISSIONS', y: 3.5, z: -18 * this.modelScale, color: '#00e5ff' }
            ];

            gates.forEach(g => {
                const sprite = this.createFloorGateSprite(g.text, g.color);
                sprite.position.set(0, g.y, g.z);
                sprite.userData = { targetFloor: g.floor, isFloorGate: true };
                sprite.visible = (this.currentFloor === 'all');
                this.scene.add(sprite);
                this.floorMarkers.push(sprite);
            });
        }

        createFloorGateSprite(text, accentColor = '#10b981') {
            const canvas = document.createElement('canvas');
            canvas.width = 680;
            canvas.height = 110;
            const ctx = canvas.getContext('2d');

            ctx.fillStyle = 'rgba(7, 11, 20, 0.94)';
            ctx.strokeStyle = accentColor;
            ctx.lineWidth = 5;

            const r = 20;
            ctx.beginPath();
            ctx.moveTo(r, 4);
            ctx.lineTo(canvas.width - r, 4);
            ctx.quadraticCurveTo(canvas.width - 4, 4, canvas.width - 4, r);
            ctx.lineTo(canvas.width - 4, canvas.height - r);
            ctx.quadraticCurveTo(canvas.width - 4, canvas.height - 4, canvas.width - r, canvas.height - 4);
            ctx.lineTo(r, canvas.height - 4);
            ctx.quadraticCurveTo(4, canvas.height - 4, 4, canvas.height - r);
            ctx.lineTo(4, r);
            ctx.quadraticCurveTo(4, 4, r, 4);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            ctx.fillStyle = accentColor;
            ctx.fillRect(14, 16, 12, canvas.height - 32);

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 28px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, canvas.width / 2 + 8, canvas.height / 2);

            const tex = new THREE.CanvasTexture(canvas);
            const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
            const sprite = new THREE.Sprite(mat);
            sprite.scale.set(16.0, 2.5, 1.0);
            return sprite;
        }

        createCanvasTextSprite(text, accentColor = '#0284c7') {
            const canvas = document.createElement('canvas');
            canvas.width = 540;
            canvas.height = 108;
            const ctx = canvas.getContext('2d');

            // High-Contrast Floating Architectural HUD Badge for maximum visibility
            ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
            ctx.shadowBlur = 10;
            ctx.shadowOffsetY = 5;

            ctx.fillStyle = 'rgba(11, 17, 32, 0.94)';
            ctx.strokeStyle = accentColor;
            ctx.lineWidth = 4;

            const r = 18;
            ctx.beginPath();
            ctx.moveTo(r, 6);
            ctx.lineTo(canvas.width - r, 6);
            ctx.quadraticCurveTo(canvas.width - 6, 6, canvas.width - 6, r);
            ctx.lineTo(canvas.width - 6, canvas.height - r);
            ctx.quadraticCurveTo(canvas.width - 6, canvas.height - 6, canvas.width - r, canvas.height - 6);
            ctx.lineTo(r, canvas.height - 6);
            ctx.quadraticCurveTo(6, canvas.height - 6, 6, canvas.height - r);
            ctx.lineTo(6, r);
            ctx.quadraticCurveTo(6, 6, r, 6);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            // Reset shadow
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetY = 0;

            // Accent Pill on Left
            ctx.fillStyle = accentColor;
            ctx.fillRect(14, 16, 8, canvas.height - 32);

            // Large Bold High-Contrast Typography
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 28px "JetBrains Mono", monospace, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, canvas.width / 2 + 8, canvas.height / 2);

            const tex = new THREE.CanvasTexture(canvas);
            const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
            const sprite = new THREE.Sprite(mat);
            sprite.scale.set(8.8, 1.76, 1.0); // Doubled physical scale so badges are huge and readable!
            return sprite;
        }

        // ────────────────── FLOOR ISOLATION / DOLLHOUSE SLICER ──────────────────
        setFloor(floorId) {
            this.currentFloor = floorId;
            if (!this.model) return;

            const floorOrder = ['1F', '2F', '3F', '4F', 'RD'];
            const targetIdx = floorOrder.indexOf(floorId);

            this.model.traverse((child) => {
                if (child.isMesh) {
                    const name = child.name;
                    if (floorId === 'all') {
                        child.visible = true;
                    } else {
                        // Sliced mode: hide full-height tall pillars, roof, and clerestory
                        if (name.startsWith('Column_') || name.includes('Clerestory') || name.includes('Roof') || name.includes('Parapet') || name.includes('Solar')) {
                            child.visible = false;
                        } else if (child.userData.floor) {
                            const meshFloorIdx = floorOrder.indexOf(child.userData.floor);
                            child.visible = (meshFloorIdx !== -1 && meshFloorIdx <= targetIdx);
                        } else {
                            const box = new THREE.Box3().setFromObject(child);
                            const targetMaxY = (this.floorHeights[floorId] || 6.72) + 6.0;
                            child.visible = (box.min.y < targetMaxY);
                        }
                    }
                }
            });

            // Update label visibilities
            this.floorMarkers.forEach(gate => {
                gate.visible = (floorId === 'all');
            });

            this.roomLabels.forEach(lbl => {
                if (floorId === 'all') {
                    lbl.visible = false;
                } else {
                    lbl.visible = (lbl.userData.floor === floorId);
                }
            });

            // Re-target camera smoothly for screen-filling optimal framing
            if (!this.isWalkMode) {
                const targetY = this.floorHeights[floorId] !== undefined ? this.floorHeights[floorId] + 1.2 : 8.19;
                const camDist = floorId === 'all' ? 88 : 64;
                this.flyToTarget(new THREE.Vector3(0, targetY, 0), camDist);
            } else {
                this.walkFloorElevation = this.floorHeights[floorId] !== undefined ? this.floorHeights[floorId] : 8.19;
            }

            if (this.options.onFloorChange) {
                this.options.onFloorChange(floorId);
            }
        }

        // ────────────────── ROOM ZOOM & SELECTION ──────────────────
        flyToRoom(roomId) {
            const meshes = this.roomMeshes.get(roomId);
            if (!meshes || meshes.length === 0) return;

            const box = new THREE.Box3();
            meshes.forEach(m => box.expandByObject(m));
            const center = new THREE.Vector3();
            box.getCenter(center);

            const firstMesh = meshes[0];
            if (firstMesh.userData.floor && this.currentFloor !== firstMesh.userData.floor) {
                this.setFloor(firstMesh.userData.floor);
            }

            if (this.isWalkMode) {
                // If in walk mode, teleport walking position directly inside!
                this.startWalkMode(roomId);
                return;
            }

            // Overhead architectural cutaway angle overlooking desks and glassboard
            const roomWidth = box.max.x - box.min.x;
            const roomDepth = box.max.z - box.min.z;
            const camPos = new THREE.Vector3(center.x + roomWidth * 0.35, box.max.y + 6.0, center.z + roomDepth * 0.70);
            this.animateCamera(camPos, center, 600);
            this.highlightRoom(roomId);
        }

        // ────────────────── FIRST-PERSON ARCHITECTURAL WALK MODE ──────────────────
        startWalkMode(initialRoomId = null) {
            this.isWalkMode = true;
            if (this.controls) this.controls.enabled = false;

            // Save orbit camera state to restore later
            this.savedOrbitPos.copy(this.camera.position);
            if (this.controls) this.savedOrbitTarget.copy(this.controls.target);

            // Determine floor elevation
            const activeFloor = this.currentFloor === 'all' ? '2F' : this.currentFloor;
            if (this.currentFloor === 'all') this.setFloor('2F');
            this.walkFloorElevation = this.floorHeights[activeFloor] || 5.88;

            let startPos = new THREE.Vector3(0, this.walkFloorElevation + 1.65, 10);
            let lookTarget = new THREE.Vector3(0, this.walkFloorElevation + 1.65, 0);

            if (initialRoomId) {
                const meshes = this.roomMeshes.get(initialRoomId);
                if (meshes && meshes.length > 0) {
                    const box = new THREE.Box3();
                    meshes.forEach(m => box.expandByObject(m));
                    const center = new THREE.Vector3();
                    box.getCenter(center);
                    const depth = box.max.z - box.min.z;

                    // Stand in the student seating area facing the front board
                    startPos.set(center.x, box.min.y + 1.65, center.z - depth * 0.20);
                    lookTarget.set(center.x, box.min.y + 1.65, center.z + depth * 0.40);
                    this.highlightRoom(initialRoomId);
                }
            }

            this.camera.position.copy(startPos);
            this.camera.lookAt(lookTarget);

            // Initialize Euler from camera look
            this.walkEuler.setFromQuaternion(this.camera.quaternion);

            this.lastWalkTime = performance.now();

            if (this.options.onWalkModeChange) {
                this.options.onWalkModeChange(true);
            }

            this.detectCurrentWalkRoom();
        }

        exitWalkMode() {
            this.isWalkMode = false;
            if (this.controls) {
                this.controls.enabled = true;
                const lookAt = new THREE.Vector3(0, this.walkFloorElevation + 1.5, 0);
                const camPos = new THREE.Vector3(20, 14, 22);
                this.animateCamera(camPos, lookAt, 500);
            }

            if (this.options.onWalkModeChange) {
                this.options.onWalkModeChange(false);
            }
        }

        teleportInsideRoom(roomId) {
            this.startWalkMode(roomId);
        }

        highlightRoom(roomId) {
            this.selectedRoomId = roomId;

            // Remove existing highlight bounding box
            if (this.roomHighlightBox) {
                this.scene.remove(this.roomHighlightBox);
                this.roomHighlightBox.geometry?.dispose();
                this.roomHighlightBox.material?.dispose();
                this.roomHighlightBox = null;
            }

            // Restore all meshes to their original materials
            this.roomMeshes.forEach((meshes) => {
                meshes.forEach(m => {
                    if (m.userData.originalMaterial) {
                        m.material = m.userData.originalMaterial;
                    }
                });
            });

            if (!roomId) return;

            const targetMeshes = this.roomMeshes.get(roomId);
            if (targetMeshes && targetMeshes.length > 0) {
                const box = new THREE.Box3();
                targetMeshes.forEach(m => {
                    box.expandByObject(m);
                    const lower = m.name.toLowerCase();
                    // Gently tint floor mesh with glowing accent without ruining walls or furniture
                    if (lower.includes('floor')) {
                        const floorMat = m.userData.originalMaterial ? m.userData.originalMaterial.clone() : m.material.clone();
                        floorMat.emissive = new THREE.Color(0x0284c7);
                        floorMat.emissiveIntensity = 0.35;
                        m.material = floorMat;
                    }
                });

                // Add crisp glowing cyan bounding perimeter outline
                const size = new THREE.Vector3();
                box.getSize(size);
                const center = new THREE.Vector3();
                box.getCenter(center);

                const boxGeo = new THREE.BoxGeometry(size.x + 0.25, size.y + 0.1, size.z + 0.25);
                const edges = new THREE.EdgesGeometry(boxGeo);
                const lineMat = new THREE.LineBasicMaterial({ color: 0x00e5ff, linewidth: 2, depthTest: true });
                this.roomHighlightBox = new THREE.LineSegments(edges, lineMat);
                this.roomHighlightBox.position.copy(center);
                this.scene.add(this.roomHighlightBox);
            }
        }

        flyToTarget(targetVec, distance = 64) {
            if (!this.controls) return;
            const currentDir = new THREE.Vector3().subVectors(this.camera.position, this.controls.target).normalize();
            if (currentDir.y < 0.35) {
                currentDir.y = 0.55;
                currentDir.normalize();
            }
            const newCamPos = new THREE.Vector3().addVectors(targetVec, currentDir.multiplyScalar(distance));
            this.animateCamera(newCamPos, targetVec, 450);
        }

        animateCamera(targetPos, targetLookAt, duration = 500) {
            if (!this.controls) return;
            const startPos = this.camera.position.clone();
            const startTarget = this.controls.target.clone();
            const startTime = performance.now();

            const update = (now) => {
                const progress = Math.min((now - startTime) / duration, 1.0);
                const t = 1 - Math.pow(1 - progress, 3);

                this.camera.position.lerpVectors(startPos, targetPos, t);
                this.controls.target.lerpVectors(startTarget, targetLookAt, t);

                if (progress < 1.0) {
                    requestAnimationFrame(update);
                } else {
                    this.camera.position.copy(targetPos);
                    this.controls.target.copy(targetLookAt);
                }
            };

            requestAnimationFrame(update);
        }

        detectCurrentWalkRoom() {
            const camPos = this.camera.position;
            let insideRoomId = null;

            for (let [roomId, meshes] of this.roomMeshes.entries()) {
                const box = new THREE.Box3();
                meshes.forEach(m => box.expandByObject(m));
                if (camPos.x >= box.min.x && camPos.x <= box.max.x &&
                    camPos.z >= box.min.z && camPos.z <= box.max.z) {
                    insideRoomId = roomId;
                    break;
                }
            }

            if (insideRoomId !== this.currentWalkRoom) {
                this.currentWalkRoom = insideRoomId;
                if (this.options.onWalkRoomChange) {
                    this.options.onWalkRoomChange(insideRoomId);
                }
            }
        }

        bindEvents() {
            const dom = this.renderer.domElement;

            // Mouse Move & Hover
            dom.addEventListener('mousemove', (e) => {
                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
                this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

                if (this.isWalkMode && this.isPointerDown) {
                    const dx = e.clientX - this.lastPointerX;
                    const dy = e.clientY - this.lastPointerY;
                    this.lastPointerX = e.clientX;
                    this.lastPointerY = e.clientY;

                    // Mouse Look 360°
                    this.walkEuler.y -= dx * 0.0035;
                    this.walkEuler.x -= dy * 0.003;
                    this.walkEuler.x = Math.max(-Math.PI / 2.3, Math.min(Math.PI / 2.3, this.walkEuler.x));
                    this.camera.quaternion.setFromEuler(this.walkEuler);
                    return;
                }

                if (!this.isWalkMode) this.checkHover();
            });

            // Track pointer down coordinates & time to distinguish orbit/pan drag from click
            dom.addEventListener('pointerdown', (e) => {
                this.pointerDownPos = { x: e.clientX, y: e.clientY };
                this.pointerDownTime = Date.now();

                if (this.isWalkMode && e.button === 0) {
                    this.isPointerDown = true;
                    this.lastPointerX = e.clientX;
                    this.lastPointerY = e.clientY;
                }
            });

            window.addEventListener('pointerup', () => {
                this.isPointerDown = false;
            });

            dom.addEventListener('mousedown', (e) => {
                if (this.isWalkMode && e.button === 0) {
                    this.isPointerDown = true;
                    this.lastPointerX = e.clientX;
                    this.lastPointerY = e.clientY;
                }
            });

            window.addEventListener('mouseup', () => {
                this.isPointerDown = false;
            });

            // Touch Drag Support for Walk Mode (Mobile / Tablet 360° Look)
            dom.addEventListener('touchstart', (e) => {
                if (this.isWalkMode && e.touches.length > 0) {
                    this.isPointerDown = true;
                    this.lastPointerX = e.touches[0].clientX;
                    this.lastPointerY = e.touches[0].clientY;
                }
            }, { passive: true });

            dom.addEventListener('touchmove', (e) => {
                if (this.isWalkMode && this.isPointerDown && e.touches.length > 0) {
                    const dx = e.touches[0].clientX - this.lastPointerX;
                    const dy = e.touches[0].clientY - this.lastPointerY;
                    this.lastPointerX = e.touches[0].clientX;
                    this.lastPointerY = e.touches[0].clientY;

                    this.walkEuler.y -= dx * 0.0035;
                    this.walkEuler.x -= dy * 0.003;
                    this.walkEuler.x = Math.max(-Math.PI / 2.3, Math.min(Math.PI / 2.3, this.walkEuler.x));
                    this.camera.quaternion.setFromEuler(this.walkEuler);
                }
            }, { passive: true });

            window.addEventListener('touchend', () => {
                this.isPointerDown = false;
            });

            // Single Click: Select and Highlight ONLY (Blocks drag/orbit misclicks)
            dom.addEventListener('click', (e) => {
                if (this.isWalkMode) return;

                // Drag gesture detection: if mouse moved > 5px or hold time > 260ms, user was ORBITING!
                const dx = e.clientX - this.pointerDownPos.x;
                const dy = e.clientY - this.pointerDownPos.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const duration = Date.now() - this.pointerDownTime;

                if (dist > 5 || duration > 260) {
                    return; // Ignore completely — user was rotating/orbiting the camera!
                }

                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
                this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
                this.handleClick(false); // false = select/highlight only, do NOT fly camera!
            });

            // Double Click: Explicit intentional zoom into room
            dom.addEventListener('dblclick', (e) => {
                if (this.isWalkMode) return;
                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
                this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
                this.handleClick(true); // true = deliberate double-click zoom
            });

            // Keyboard Walk Controls (WASD / Arrows)
            window.addEventListener('keydown', (e) => {
                if (!this.isWalkMode) return;
                switch (e.code) {
                    case 'KeyW':
                    case 'ArrowUp':
                        this.walkKeys.forward = true;
                        break;
                    case 'KeyS':
                    case 'ArrowDown':
                        this.walkKeys.backward = true;
                        break;
                    case 'KeyA':
                    case 'ArrowLeft':
                        this.walkKeys.left = true;
                        break;
                    case 'KeyD':
                    case 'ArrowRight':
                        this.walkKeys.right = true;
                        break;
                    case 'KeyQ':
                        this.walkKeys.turnLeft = true;
                        break;
                    case 'KeyE':
                        this.walkKeys.turnRight = true;
                        break;
                    case 'ShiftLeft':
                    case 'ShiftRight':
                        this.walkKeys.sprint = true;
                        break;
                    case 'Escape':
                        this.exitWalkMode();
                        break;
                }
            });

            window.addEventListener('keyup', (e) => {
                if (!this.isWalkMode) return;
                switch (e.code) {
                    case 'KeyW':
                    case 'ArrowUp':
                        this.walkKeys.forward = false;
                        break;
                    case 'KeyS':
                    case 'ArrowDown':
                        this.walkKeys.backward = false;
                        break;
                    case 'KeyA':
                    case 'ArrowLeft':
                        this.walkKeys.left = false;
                        break;
                    case 'KeyD':
                    case 'ArrowRight':
                        this.walkKeys.right = false;
                        break;
                    case 'KeyQ':
                        this.walkKeys.turnLeft = false;
                        break;
                    case 'KeyE':
                        this.walkKeys.turnRight = false;
                        break;
                    case 'ShiftLeft':
                    case 'ShiftRight':
                        this.walkKeys.sprint = false;
                        break;
                }
            });

            window.addEventListener('resize', () => this.onResize());
        }

        checkHover() {
            if (!this.model) return;
            this.raycaster.setFromCamera(this.mouse, this.camera);

            if (this.currentFloor === 'all') {
                const gateIntersects = this.raycaster.intersectObjects(this.floorMarkers, true);
                if (gateIntersects.length > 0) {
                    this.renderer.domElement.style.cursor = 'pointer';
                    return;
                }
            }

            const labelIntersects = this.raycaster.intersectObjects(this.roomLabels.filter(l => l.visible), true);
            if (labelIntersects.length > 0) {
                this.renderer.domElement.style.cursor = 'pointer';
                const hitRoomId = labelIntersects[0].object.userData?.roomId;
                if (hitRoomId && hitRoomId !== this.hoveredRoomId) {
                    this.hoveredRoomId = hitRoomId;
                    if (this.options.onRoomHover) this.options.onRoomHover(hitRoomId);
                }
                return;
            }

            const intersects = this.raycaster.intersectObjects(this.model.children, true);
            let hitRoomId = null;
            for (let hit of intersects) {
                if (hit.object.userData && hit.object.userData.roomId) {
                    hitRoomId = hit.object.userData.roomId;
                    break;
                }
            }

            if (hitRoomId !== this.hoveredRoomId) {
                this.hoveredRoomId = hitRoomId;
                this.renderer.domElement.style.cursor = hitRoomId ? 'pointer' : 'default';

                if (this.options.onRoomHover) {
                    this.options.onRoomHover(hitRoomId);
                }
            }
        }

        handleClick(zoomCamera = false) {
            if (!this.model) return;
            this.raycaster.setFromCamera(this.mouse, this.camera);

            if (this.currentFloor === 'all') {
                const gateHits = this.raycaster.intersectObjects(this.floorMarkers, true);
                if (gateHits.length > 0) {
                    const targetFloor = gateHits[0].object.userData?.targetFloor;
                    if (targetFloor) {
                        this.setFloor(targetFloor);
                        return;
                    }
                }
            }

            const labelHits = this.raycaster.intersectObjects(this.roomLabels.filter(l => l.visible), true);
            if (labelHits.length > 0) {
                const roomId = labelHits[0].object.userData?.roomId;
                if (roomId) {
                    if (zoomCamera) {
                        this.flyToRoom(roomId);
                    } else {
                        this.highlightRoom(roomId);
                    }
                    if (this.options.onRoomClick) {
                        this.options.onRoomClick(roomId, zoomCamera);
                    }
                    return;
                }
            }

            const intersects = this.raycaster.intersectObjects(this.model.children, true);
            for (let hit of intersects) {
                const roomId = hit.object.userData?.roomId;
                if (roomId) {
                    if (zoomCamera) {
                        this.flyToRoom(roomId);
                    } else {
                        this.highlightRoom(roomId);
                    }
                    if (this.options.onRoomClick) {
                        this.options.onRoomClick(roomId, zoomCamera);
                    }
                    break;
                }
            }
        }

        onResize() {
            if (!this.container || !this.renderer || !this.camera) return;
            const width = this.container.clientWidth;
            const height = this.container.clientHeight;
            this.camera.aspect = width / height;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(width, height);
        }

        resetView() {
            if (this.isWalkMode) this.exitWalkMode();
            this.setFloor('2F');
            const targetY = (this.floorHeights['2F'] || 6.09) + 1.2;
            const targetPos = new THREE.Vector3(45, targetY + 48, 54);
            const lookAt = new THREE.Vector3(0, targetY, 0);
            this.animateCamera(targetPos, lookAt, 500);
        }

        topPlanView() {
            if (this.isWalkMode) this.exitWalkMode();
            const curF = this.currentFloor === 'all' ? '2F' : this.currentFloor;
            const targetY = (this.floorHeights[curF] || 6.09);
            const targetPos = new THREE.Vector3(0, targetY + 95, 0.001);
            const lookAt = new THREE.Vector3(0, targetY, 0);
            this.animateCamera(targetPos, lookAt, 500);
        }

        animate() {
            requestAnimationFrame(this.animate);

            if (this.isWalkMode) {
                const now = performance.now();
                const delta = Math.min((now - this.lastWalkTime) / 1000, 0.1);
                this.lastWalkTime = now;

                if (this.walkKeys.turnLeft) this.walkEuler.y += 2.2 * delta;
                if (this.walkKeys.turnRight) this.walkEuler.y -= 2.2 * delta;

                const forward = new THREE.Vector3(0, 0, -1).applyAxisAngle(new THREE.Vector3(0, 1, 0), this.walkEuler.y);
                const right = new THREE.Vector3(1, 0, 0).applyAxisAngle(new THREE.Vector3(0, 1, 0), this.walkEuler.y);

                const move = new THREE.Vector3();
                if (this.walkKeys.forward) move.add(forward);
                if (this.walkKeys.backward) move.sub(forward);
                if (this.walkKeys.right) move.add(right);
                if (this.walkKeys.left) move.sub(right);

                if (move.lengthSq() > 0) {
                    move.normalize();
                    const speed = this.walkSpeed * (this.walkKeys.sprint ? 1.7 : 1.0);
                    this.camera.position.addScaledVector(move, speed * delta);

                    // Clamp within 76m campus footprint bounds
                    this.camera.position.x = Math.max(-48 * this.modelScale, Math.min(48 * this.modelScale, this.camera.position.x));
                    this.camera.position.z = Math.max(-48 * this.modelScale, Math.min(48 * this.modelScale, this.camera.position.z));
                }

                // Lock standing eye level
                this.camera.position.y = this.walkFloorElevation + 1.65;
                this.camera.quaternion.setFromEuler(this.walkEuler);

                this.detectCurrentWalkRoom();
            } else {
                if (this.controls) this.controls.update();
            }

            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }
        }
    }

    window.NpcBlender3D = NpcBlender3D;
})(window);
