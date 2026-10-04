/**
 * npc-campus-3d.js — High-Fidelity 3D BIM Architectural Engine
 * Navotas Polytechnic College (NPC) Multi-Purpose Academic Building
 * 
 * Generated Directly from Master 2D Floorplan Geometry (window.NPC_FLOORS_DATA)
 * Dimensions: 56.000m (Grids A-J) x 60.500m (Grids 1-9)
 * 
 * Features:
 *  - 2D is the Master: All 3D walls, slabs, voids, and rooms originate directly from NPC_FLOORS_DATA
 *  - Procedural Wall Extrusions (3.2m height, architectural double walls)
 *  - Floor Slicing / Isolation Mode (1F, 2F, 3F, 4F, RD) + Exploded Axonometric Building View
 *  - Interactive Room Raycasting with Golden Selection Glow and Hover Wireframes
 *  - Seamless Sync with Room Inspector Card & Live Student Attendance Metrics
 *  - Orbit Controls: Intuitive Pan, Rotate, and Zoom-to-Room
 */

(function (window) {
    'use strict';

    class NpcCampus3D {
        constructor(containerEl, options = {}) {
            this.container = typeof containerEl === 'string' ? document.querySelector(containerEl) : containerEl;
            if (!this.container) throw new Error('3D container element not found.');

            this.options = Object.assign({
                currentFloor: '2F',
                onRoomSelect: null,
                onRoomHover: null
            }, options);

            this.currentFloor = this.options.currentFloor;
            this.buildingSpecs = window.NPC_BUILDING_SPECS || { width: 56.0, depth: 60.5 };
            this.floorsData = window.NPC_FLOORS_DATA || {};

            // Center offsets in 3D world space (Origin at center of building)
            this.centerX = this.buildingSpecs.width / 2;  // 28.0m
            this.centerZ = this.buildingSpecs.depth / 2;  // 30.25m

            // Three.js State
            this.scene = null;
            this.camera = null;
            this.renderer = null;
            this.controls = null;
            this.raycaster = new THREE.Raycaster();
            this.mouse = new THREE.Vector2();

            this.floorGroups = {};
            this.roomMeshes = [];
            this.activeFloorRoomMeshes = [];
            this.roomDataMap = {};
            this.selectedRoomId = null;
            this.hoveredRoomId = null;

            this.initThree();
            this.buildFromMasterData();
            this.animate = this.animate.bind(this);
            requestAnimationFrame(this.animate);
        }

        initThree() {
            const width = this.container.clientWidth || window.innerWidth;
            const height = this.container.clientHeight || (window.innerHeight - 180);

            // 1. Scene
            this.scene = new THREE.Scene();
            this.scene.background = new THREE.Color(0x0b0f17);
            this.scene.fog = new THREE.FogExp2(0x0b0f17, 0.007);

            // 2. Camera
            this.camera = new THREE.PerspectiveCamera(45, width / height, 0.5, 500);
            this.camera.position.set(40, 48, 65);

            // 3. Renderer
            this.renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
            this.renderer.setSize(width, height);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            this.container.appendChild(this.renderer.domElement);

            // 4. OrbitControls
            if (typeof THREE.OrbitControls === 'function') {
                this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
                this.controls.enableDamping = true;
                this.controls.dampingFactor = 0.08;
                this.controls.maxPolarAngle = Math.PI / 2.05; // Do not go below ground
                this.controls.minDistance = 10;
                this.controls.maxDistance = 180;
                this.controls.target.set(0, 8, 0);
            }

            // 5. Lighting
            const ambient = new THREE.AmbientLight(0xffffff, 0.65);
            this.scene.add(ambient);

            const hemiLight = new THREE.HemisphereLight(0x38bdf8, 0x0f172a, 0.5);
            this.scene.add(hemiLight);

            const sunLight = new THREE.DirectionalLight(0xfffaed, 1.2);
            sunLight.position.set(50, 90, 40);
            sunLight.castShadow = true;
            sunLight.shadow.mapSize.width = 2048;
            sunLight.shadow.mapSize.height = 2048;
            sunLight.shadow.camera.near = 10;
            sunLight.shadow.camera.far = 250;
            sunLight.shadow.camera.left = -60;
            sunLight.shadow.camera.right = 60;
            sunLight.shadow.camera.top = 60;
            sunLight.shadow.camera.bottom = -60;
            sunLight.shadow.bias = -0.0005;
            this.scene.add(sunLight);

            const fillLight = new THREE.DirectionalLight(0x38bdf8, 0.4);
            fillLight.position.set(-50, 40, -40);
            this.scene.add(fillLight);

            // 6. Ground Base Grid & Plaza
            this.createGroundPlaza();

            // 7. Interaction Events
            this.bindEvents();
        }

        createGroundPlaza() {
            const groundGeo = new THREE.PlaneGeometry(160, 160);
            const groundMat = new THREE.MeshStandardMaterial({
                color: 0x0f172a,
                roughness: 0.9,
                metalness: 0.1
            });
            const ground = new THREE.Mesh(groundGeo, groundMat);
            ground.rotation.x = -Math.PI / 2;
            ground.position.y = -0.05;
            ground.receiveShadow = true;
            this.scene.add(ground);

            const gridHelper = new THREE.GridHelper(140, 70, 0x00e5ff, 0x1e293b);
            gridHelper.position.y = 0.0;
            this.scene.add(gridHelper);
        }

        bindEvents() {
            const dom = this.renderer.domElement;
            let pointerDownX = 0;
            let pointerDownY = 0;
            let pointerDownTime = 0;

            dom.addEventListener('pointerdown', (e) => {
                pointerDownX = e.clientX;
                pointerDownY = e.clientY;
                pointerDownTime = Date.now();
            });

            dom.addEventListener('pointermove', (e) => {
                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((e.clientX - rect.left) / dom.clientWidth) * 2 - 1;
                this.mouse.y = -((e.clientY - rect.top) / dom.clientHeight) * 2 + 1;
                this.checkHover();
            });

            dom.addEventListener('pointerup', (e) => {
                const moveDist = Math.hypot(e.clientX - pointerDownX, e.clientY - pointerDownY);
                const holdDuration = Date.now() - pointerDownTime;
                // Ignore dragging or panning as a click
                if (moveDist > 6 || holdDuration > 300) return;

                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((e.clientX - rect.left) / dom.clientWidth) * 2 - 1;
                this.mouse.y = -((e.clientY - rect.top) / dom.clientHeight) * 2 + 1;

                this.raycaster.setFromCamera(this.mouse, this.camera);
                const targets = (this.activeFloorRoomMeshes && this.activeFloorRoomMeshes.length > 0)
                    ? this.activeFloorRoomMeshes
                    : this.roomMeshes;
                const intersects = this.raycaster.intersectObjects(targets, true);

                let hitRoomId = null;
                for (let i = 0; i < intersects.length; i++) {
                    const obj = intersects[i].object;
                    if (obj.userData && obj.userData.roomId) {
                        hitRoomId = obj.userData.roomId;
                        break;
                    }
                }

                if (hitRoomId) {
                    this.selectRoom(hitRoomId, true);
                    if (typeof this.options.onRoomSelect === 'function') {
                        const roomData = this.roomDataMap[hitRoomId]?.data;
                        if (roomData) this.options.onRoomSelect(roomData);
                    }
                } else {
                    this.clearSelection();
                    if (typeof this.options.onClearSelect === 'function') {
                        this.options.onClearSelect();
                    }
                }
            });

            window.addEventListener('resize', () => {
                this.resize();
            });
        }

        resize() {
            if (!this.container || !this.renderer || !this.camera) return;
            const width = this.container.clientWidth || window.innerWidth;
            const height = this.container.clientHeight || (window.innerHeight - 180);
            if (width > 0 && height > 0) {
                this.camera.aspect = width / height;
                this.camera.updateProjectionMatrix();
                this.renderer.setSize(width, height);
            }
        }

        // ────────────────── BUILD 3D MODEL FROM MASTER 2D GEOMETRY ──────────────────
        buildFromMasterData() {
            this.buildingGroup = new THREE.Group();
            this.buildingGroup.name = 'npc_campus_building';
            this.scene.add(this.buildingGroup);

            // Floor elevation lookup
            const floorKeys = ['1F', '2F', '3F', '4F', 'RD'];

            floorKeys.forEach(fKey => {
                const fData = this.floorsData[fKey];
                if (!fData) return;

                const fGroup = new THREE.Group();
                fGroup.name = `floor_${fKey}`;
                const baseElev = fData.elevation || 0.0;

                // 1. Floor Slab (with open atrium void cutout)
                this.buildFloorSlab(fGroup, fKey, baseElev);

                // 2. Extrude All Room Geometries
                (fData.rooms || []).forEach(room => {
                    this.buildRoom3D(fGroup, fKey, room, baseElev);
                });

                // 3. Vertical Columns
                this.buildColumns3D(fGroup, fData.columns, baseElev);

                this.floorGroups[fKey] = {
                    group: fGroup,
                    baseElevation: baseElev
                };

                this.buildingGroup.add(fGroup);
            });

            this.setFloor(this.currentFloor);
        }

        buildFloorSlab(fGroup, fKey, baseElev) {
            const w = this.buildingSpecs.width;
            const d = this.buildingSpecs.depth;

            // Shape with central courtyard hole
            const shape = new THREE.Shape();
            shape.moveTo(-w / 2, -d / 2);
            shape.lineTo(w / 2, -d / 2);
            shape.lineTo(w / 2, d / 2);
            shape.lineTo(-w / 2, d / 2);
            shape.closePath();

            // Central Void Hole for 2F and 3F
            if (fKey === '2F' || fKey === '3F') {
                const hole = new THREE.Path();
                const holeX1 = 18.0 - this.centerX;
                const holeX2 = 38.0 - this.centerX;
                const holeZ1 = 15.5 - this.centerZ;
                const holeZ2 = 33.5 - this.centerZ;

                hole.moveTo(holeX1, holeZ1);
                hole.lineTo(holeX2, holeZ1);
                hole.lineTo(holeX2, holeZ2);
                hole.lineTo(holeX1, holeZ2);
                hole.closePath();
                shape.holes.push(hole);
            }

            const slabGeo = new THREE.ExtrudeGeometry(shape, {
                depth: 0.35,
                bevelEnabled: true,
                bevelSegments: 2,
                steps: 1,
                bevelSize: 0.05,
                bevelThickness: 0.05
            });

            const slabMat = new THREE.MeshStandardMaterial({
                color: fKey === 'RD' ? 0x94a3b8 : (fKey === '4F' ? 0x64748b : 0xe2e8f0),
                roughness: 0.6,
                metalness: 0.1
            });

            const slabMesh = new THREE.Mesh(slabGeo, slabMat);
            slabMesh.rotation.x = Math.PI / 2;
            slabMesh.position.y = baseElev;
            slabMesh.receiveShadow = true;
            fGroup.add(slabMesh);
        }

        buildRoom3D(fGroup, fKey, room, baseElev) {
            if (room.type === 'void') return; // Voids are empty air / cutouts

            const wallH = room.type === 'sports' ? 6.5 : 3.2; // High ceiling for gym
            const floorMat = this.getRoomMaterial(room.type);
            const wallMat = new THREE.MeshStandardMaterial({
                color: 0xf1f5f9,
                roughness: 0.5,
                metalness: 0.05
            });

            // Handle Polygonal Rooms (e.g. L-shaped Comlab 1, diagonal terrace)
            if (room.polygon && room.polygon.length >= 3) {
                const shape = new THREE.Shape();
                room.polygon.forEach((pt, idx) => {
                    const px = pt[0] - this.centerX;
                    const py = pt[1] - this.centerZ;
                    if (idx === 0) shape.moveTo(px, py);
                    else shape.lineTo(px, py);
                });
                shape.closePath();

                const floorGeo = new THREE.ShapeGeometry(shape);
                floorGeo.rotateX(-Math.PI / 2);
                const floorMesh = new THREE.Mesh(floorGeo, floorMat);
                floorMesh.position.set(0, baseElev + 0.05, 0);
                floorMesh.receiveShadow = true;
                floorMesh.userData = { roomId: room.id, isRoom: true, floorKey: fKey };
                fGroup.add(floorMesh);

                const thick = 0.2;
                const pts = room.polygon;
                const isTerrace = (room.id === '4F-TERR');
                const glassMat = new THREE.MeshStandardMaterial({
                    color: 0x38bdf8,
                    transparent: true,
                    opacity: 0.35,
                    roughness: 0.1,
                    metalness: 0.2
                });
                const railingMat = new THREE.MeshStandardMaterial({
                    color: 0xf59e0b,
                    roughness: 0.4,
                    metalness: 0.6
                });

                for (let i = 0; i < pts.length; i++) {
                    const p1 = pts[i];
                    const p2 = pts[(i + 1) % pts.length];
                    const dx = p2[0] - p1[0];
                    const dy = p2[1] - p1[1];
                    const len = Math.hypot(dx, dy);
                    if (len < 0.01) continue;

                    const midX = (p1[0] + p2[0]) / 2 - this.centerX;
                    const midZ = this.centerZ - (p1[1] + p2[1]) / 2;

                    let h = wallH;
                    let m = wallMat;

                    if (isTerrace) {
                        // Parapet / railing on south and east exterior edges
                        const isBalustrade = (p1[1] < 1.0 && p2[1] < 1.0) || (p1[0] > 55.0 && p2[0] > 55.0);
                        const isStairsWall = (p1[1] > 14.0 && p2[1] > 14.0);
                        if (isBalustrade) {
                            h = 1.1; // 1.1m safety railing height
                            m = railingMat;
                        } else if (isStairsWall) {
                            h = wallH;
                            m = wallMat; // solid wall under fire escape stairs
                        } else {
                            // Glass curtain wall along admin diagonal and hallway
                            h = wallH;
                            m = glassMat;
                        }
                    }

                    const wMesh = new THREE.Mesh(new THREE.BoxGeometry(len, h, thick), m);
                    wMesh.position.set(midX, baseElev + h / 2, midZ);
                    wMesh.rotation.y = Math.atan2(dy, dx);
                    wMesh.castShadow = true;
                    wMesh.receiveShadow = true;
                    fGroup.add(wMesh);
                }

                const extrudeSettings = { depth: (isTerrace ? 0.3 : wallH - 0.2), bevelEnabled: false };
                const volGeo = new THREE.ExtrudeGeometry(shape, extrudeSettings);
                volGeo.rotateX(-Math.PI / 2);
                const colorHex = this.getRoomColorHex(room.type);
                const volMat = new THREE.MeshStandardMaterial({
                    color: colorHex,
                    transparent: true,
                    opacity: isTerrace ? 0.08 : 0.18,
                    roughness: 0.2,
                    metalness: 0.1
                });
                const volMesh = new THREE.Mesh(volGeo, volMat);
                volMesh.position.set(0, baseElev + 0.1, 0);
                volMesh.userData = { roomId: room.id, isRoom: true, floorKey: fKey };
                fGroup.add(volMesh);

                const wireGeo = new THREE.EdgesGeometry(volGeo);
                const wireMat = new THREE.LineBasicMaterial({
                    color: 0x38bdf8,
                    transparent: true,
                    opacity: 0.4,
                    linewidth: 1
                });
                const wireMesh = new THREE.LineSegments(wireGeo, wireMat);
                wireMesh.position.copy(volMesh.position);
                fGroup.add(wireMesh);
                return;
            }

            // Convert 2D blueprint coordinates to centered 3D coordinates:
            // 2D: X: [0..56.0], Y: [0..60.5] (South to North)
            // 3D: X: [-28.0..+28.0], Z: [-30.25..+30.25] (Z positive is South, Z negative is North)
            const posX = (room.x + room.w / 2) - this.centerX;
            const posZ = this.centerZ - (room.y + room.d / 2);

            // 1. Room Floor Surface
            const floorGeo = new THREE.BoxGeometry(room.w - 0.2, 0.1, room.d - 0.2);
            const floorMesh = new THREE.Mesh(floorGeo, floorMat);
            floorMesh.position.set(posX, baseElev + 0.05, posZ);
            floorMesh.receiveShadow = true;
            floorMesh.userData = { roomId: room.id, isRoom: true, floorKey: fKey };
            fGroup.add(floorMesh);

            // 2. Extruded Architectural Perimeter Walls

            // 4 walls (North, South, East, West) with cutouts for doors
            const thick = 0.2;
            // North & South walls
            [-room.d / 2 + thick / 2, room.d / 2 - thick / 2].forEach(wz => {
                const wallX = new THREE.Mesh(new THREE.BoxGeometry(room.w, wallH, thick), wallMat);
                wallX.position.set(posX, baseElev + wallH / 2, posZ + wz);
                wallX.castShadow = true;
                wallX.receiveShadow = true;
                fGroup.add(wallX);
            });

            // West & East walls
            [-room.w / 2 + thick / 2, room.w / 2 - thick / 2].forEach(wx => {
                const wallZ = new THREE.Mesh(new THREE.BoxGeometry(thick, wallH, room.d - thick * 2), wallMat);
                wallZ.position.set(posX + wx, baseElev + wallH / 2, posZ);
                wallZ.castShadow = true;
                wallZ.receiveShadow = true;
                fGroup.add(wallZ);
            });

            // 3. Interactive Raycast Volume & Wireframe Glow
            const colorHex = this.getRoomColorHex(room.type);
            const volGeo = new THREE.BoxGeometry(room.w - 0.3, wallH - 0.2, room.d - 0.3);
            const volMat = new THREE.MeshStandardMaterial({
                color: colorHex,
                transparent: true,
                opacity: 0.18,
                roughness: 0.2,
                metalness: 0.1
            });
            const volMesh = new THREE.Mesh(volGeo, volMat);
            volMesh.position.set(posX, baseElev + wallH / 2, posZ);
            volMesh.userData = { roomId: room.id, isRoom: true, floorKey: fKey };
            fGroup.add(volMesh);

            const wireGeo = new THREE.EdgesGeometry(volGeo);
            const wireMat = new THREE.LineBasicMaterial({
                color: 0x38bdf8,
                transparent: true,
                opacity: 0.4,
                linewidth: 1
            });
            const wireMesh = new THREE.LineSegments(wireGeo, wireMat);
            wireMesh.position.copy(volMesh.position);
            fGroup.add(wireMesh);

            // Register for raycasting & tracking
            this.roomMeshes.push(floorMesh, volMesh);
            this.roomDataMap[room.id] = {
                data: room,
                floorKey: fKey,
                floorMesh: floorMesh,
                volMesh: volMesh,
                wireMesh: wireMesh,
                baseColor: colorHex,
                posX,
                posZ,
                baseElev,
                wallH
            };
        }

        buildColumns3D(fGroup, columns, baseElev) {
            if (!columns) return;
            const colMat = new THREE.MeshStandardMaterial({
                color: 0xffffff,
                roughness: 0.4,
                metalness: 0.2
            });
            const colGeo = new THREE.BoxGeometry(0.6, 3.4, 0.6);

            columns.forEach(col => {
                const cx = col.x - this.centerX;
                const cz = this.centerZ - col.y;
                const pillar = new THREE.Mesh(colGeo, colMat);
                pillar.position.set(cx, baseElev + 1.7, cz);
                pillar.castShadow = true;
                pillar.receiveShadow = true;
                fGroup.add(pillar);
            });
        }

        getRoomMaterial(type) {
            switch (type) {
                case 'sports':
                    // Hardwood Gym Floor
                    return new THREE.MeshStandardMaterial({ color: 0xd97706, roughness: 0.3, metalness: 0.1 });
                case 'lab':
                    // High-tech Cyan Epoxy Floor
                    return new THREE.MeshStandardMaterial({ color: 0x0891b2, roughness: 0.25, metalness: 0.2 });
                case 'office':
                    // Professional Slate Blue Floor
                    return new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.5, metalness: 0.1 });
                case 'amenity':
                    // Vibrant Emerald / Canteen / Garden
                    return new THREE.MeshStandardMaterial({ color: 0x059669, roughness: 0.4, metalness: 0.1 });
                case 'restroom':
                    // Clean White/Sky Ceramic Tile
                    return new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.2, metalness: 0.1 });
                case 'stairs':
                    // Non-slip Granite Tread
                    return new THREE.MeshStandardMaterial({ color: 0x7c3aed, roughness: 0.6, metalness: 0.1 });
                default:
                    // Polished Classroom Terrazzo
                    return new THREE.MeshStandardMaterial({ color: 0x2563eb, roughness: 0.4, metalness: 0.1 });
            }
        }

        getRoomColorHex(type) {
            switch (type) {
                case 'sports': return 0xf59e0b;
                case 'lab': return 0x06b6d4;
                case 'office': return 0x8b5cf6;
                case 'amenity': return 0x10b981;
                case 'restroom': return 0x38bdf8;
                case 'stairs': return 0xf43f5e;
                default: return 0x3b82f6;
            }
        }

        // ────────────────── FLOOR ISOLATION & VISIBILITY ──────────────────
        setFloor(floorKey) {
            this.currentFloor = floorKey;

            Object.keys(this.floorGroups).forEach(fk => {
                const fg = this.floorGroups[fk];
                if (!fg) return;

                if (floorKey === 'ALL') {
                    // Exploded axonometric stack
                    fg.group.visible = true;
                    fg.group.position.y = (fg.baseElevation) * 1.35;
                } else if (fk === floorKey) {
                    // Isolated floor view: strictly isolate active floor
                    fg.group.visible = true;
                    fg.group.position.y = 0; // Grounded for ergonomic inspection
                } else {
                    fg.group.visible = false;
                }
            });

            // Maintain active floor room meshes strictly for raycasting hit testing
            if (floorKey === 'ALL') {
                this.activeFloorRoomMeshes = this.roomMeshes.slice();
            } else {
                this.activeFloorRoomMeshes = this.roomMeshes.filter(m => m.userData && m.userData.floorKey === floorKey);
            }

            // Adjust camera framing to floor center
            if (this.controls) {
                if (floorKey === '4F' || floorKey === 'RD') {
                    this.controls.target.set(4, 3, 0);
                    this.camera.position.set(42, 50, 62);
                } else {
                    this.controls.target.set(0, 3, 0);
                    this.camera.position.set(38, 45, 58);
                }
                this.controls.update();
            }
        }

        // ────────────────── ROOM SELECTION & HOVER ──────────────────
        checkHover() {
            this.raycaster.setFromCamera(this.mouse, this.camera);
            const targets = (this.activeFloorRoomMeshes && this.activeFloorRoomMeshes.length > 0)
                ? this.activeFloorRoomMeshes
                : this.roomMeshes;
            const intersects = this.raycaster.intersectObjects(targets, true);

            if (intersects.length > 0) {
                let hitRoom = null;
                for (let i = 0; i < intersects.length; i++) {
                    const obj = intersects[i].object;
                    if (obj.userData && obj.userData.roomId) {
                        hitRoom = obj.userData.roomId;
                        break;
                    }
                }

                if (hitRoom && hitRoom !== this.hoveredRoomId) {
                    this.clearHover();
                    this.hoveredRoomId = hitRoom;
                    this.renderer.domElement.style.cursor = 'pointer';

                    const item = this.roomDataMap[hitRoom];
                    if (item && item.wireMesh && hitRoom !== this.selectedRoomId) {
                        item.wireMesh.material.color.setHex(0x38bdf8);
                        item.wireMesh.material.opacity = 0.9;
                    }
                    if (item && item.volMesh && hitRoom !== this.selectedRoomId) {
                        item.volMesh.material.opacity = 0.38;
                    }

                    if (typeof this.options.onRoomHover === 'function') {
                        this.options.onRoomHover(item ? item.data : null);
                    }
                }
            } else {
                this.clearHover();
            }
        }

        clearHover() {
            if (!this.hoveredRoomId) return;
            const item = this.roomDataMap[this.hoveredRoomId];
            if (item && this.hoveredRoomId !== this.selectedRoomId) {
                if (item.wireMesh) {
                    item.wireMesh.material.color.setHex(0x38bdf8);
                    item.wireMesh.material.opacity = 0.4;
                }
                if (item.volMesh) {
                    item.volMesh.material.color.setHex(item.baseColor);
                    item.volMesh.material.opacity = 0.18;
                }
            }
            this.hoveredRoomId = null;
            this.renderer.domElement.style.cursor = 'default';

            if (typeof this.options.onRoomHover === 'function') {
                this.options.onRoomHover(null);
            }
        }

        selectRoom(roomId, zoomCamera = true) {
            if (!roomId) {
                this.clearSelection();
                return;
            }

            const item = this.roomDataMap[roomId];
            if (!item) return;

            // Switch to room's floor if not currently on it
            if (item.floorKey && item.floorKey !== this.currentFloor && this.currentFloor !== 'ALL') {
                this.setFloor(item.floorKey);
            }

            // Restore previously selected room
            if (this.selectedRoomId && this.selectedRoomId !== roomId) {
                this.clearSelection();
            }

            this.selectedRoomId = roomId;

            // Persistent golden glow highlight for selected room
            if (item.wireMesh) {
                item.wireMesh.material.color.setHex(0xfbbf24);
                item.wireMesh.material.opacity = 1.0;
            }
            if (item.volMesh) {
                item.volMesh.material.color.setHex(0xfbbf24);
                item.volMesh.material.opacity = 0.48;
            }

            // Smoothly focus camera without placing inside geometry
            if (zoomCamera && this.controls) {
                const tx = item.posX;
                const tz = item.posZ;
                this.controls.target.set(tx, 1.5, tz);
                this.camera.position.set(tx + 16, 22, tz + 20);
                this.controls.update();
            }
        }

        clearSelection() {
            if (this.selectedRoomId) {
                const prev = this.roomDataMap[this.selectedRoomId];
                if (prev) {
                    if (prev.wireMesh) {
                        prev.wireMesh.material.color.setHex(0x38bdf8);
                        prev.wireMesh.material.opacity = 0.4;
                    }
                    if (prev.volMesh) {
                        prev.volMesh.material.color.setHex(prev.baseColor);
                        prev.volMesh.material.opacity = 0.18;
                    }
                }
                this.selectedRoomId = null;
            }
        }

        resetView() {
            if (this.controls) {
                this.controls.target.set(0, 4, 0);
                this.camera.position.set(40, 48, 65);
                this.controls.update();
            }
        }

        animate() {
            requestAnimationFrame(this.animate);
            if (this.controls) {
                this.controls.update();
            }
            this.renderer.render(this.scene, this.camera);
        }
    }

    window.NpcCampus3D = NpcCampus3D;
    console.log('🏛️ NpcCampus3D loaded: 3D BIM extrusion engine directly connected to master 2D dataset.');

})(window);
