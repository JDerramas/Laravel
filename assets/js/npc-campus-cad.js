/**
 * npc-campus-cad.js — Professional Architectural & Civil Engineering CAD Blueprint Engine
 * Navotas Polytechnic College (NPC) Multi-Purpose Academic Building
 * 
 * Reconstructed directly from DPWH Architectural Reference Blueprints
 * Building Dimensions: 56.000m (Grids A-J) x 60.500m (Grids 1-9)
 * 
 * Features:
 *  - 100% Floor Independence: 1F, 2F, 3F, 4F, RD have completely decoupled geometries
 *  - High-Precision Vector CAD: Walls, structural column grids, doors with swing arcs, stairs, voids
 *  - Developer "Reference Overlay" Mode: Real-time semi-transparent DPWH blueprint raster overlay with 0-100% opacity slider
 *  - Interactive Room Selection synced with Room Inspector Card & live attendance metrics
 *  - AutoCAD Viewport: Infinite pan, zoom-to-cursor, zoom extents, real-time crosshair coordinate tracking
 *  - Interactive Tape Measure Tool (DIST)
 *  - Layer Visibility Manager (A-WALL, A-DOOR, A-COLS, S-GRID, A-ROOM, A-DIMS, REF-OVERLAY)
 */

(function (window) {
    'use strict';

    class NpcCadEngine {
        constructor(containerEl, options = {}) {
            this.container = typeof containerEl === 'string' ? document.querySelector(containerEl) : containerEl;
            if (!this.container) throw new Error('CAD container element not found.');

            this.options = Object.assign({
                theme: 'autocad-dark', // 'autocad-dark' | 'blueprint' | 'white'
                currentFloor: '2F',
                onRoomSelect: null,
                onRoomHover: null,
                onCoordinateChange: null,
                onMeasureComplete: null
            }, options);

            this.currentFloor = this.options.currentFloor;
            this.theme = this.options.theme;
            this.svgScale = 20.0; // 20 SVG units per real-world meter
            this.buildingSpecs = window.NPC_BUILDING_SPECS || {
                width: 56.0,
                depth: 60.5,
                gridX: [],
                gridY: []
            };
            this.floorsData = window.NPC_FLOORS_DATA || {};

            // Layer states
            this.layers = {
                'REF-OVERLAY': { name: 'Reference Blueprint Overlay', visible: false, color: '#f59e0b' },
                'A-WALL': { name: 'Walls & Partitions', visible: true, color: '#00e5ff' },
                'A-DOOR': { name: 'Doors & Swings', visible: false, color: '#facc15' },
                'A-COLS': { name: 'Structural Columns (A–J, 1–9)', visible: true, color: '#ffffff' },
                'S-GRID': { name: 'Grid Lines & Dimension Strings', visible: true, color: '#f43f5e' },
                'A-ROOM': { name: 'Room Tags & Area (m²)', visible: true, color: '#f8fafc' },
                'A-STAIR': { name: 'Stairs & Fire Escapes', visible: true, color: '#a855f7' },
                'A-COURT': { name: 'Sports Courts & Voids', visible: true, color: '#fb923c' }
            };

            // Overlay opacity state (0.0 to 1.0)
            this.overlayOpacity = 0.5;

            // Viewport State (Pan & Zoom)
            this.viewState = {
                panX: 60,
                panY: 40,
                scale: 0.65,
                isPanning: false,
                startX: 0,
                startY: 0
            };

            // Tool state
            this.activeTool = 'select'; // 'select' | 'measure'
            this.measureState = {
                step: 0,
                p1: null,
                p2: null
            };

            this.selectedRoomId = null;
            this.hoveredRoomId = null;
            this.roomStatuses = this.options.roomStatuses || {};

            this.initDOM();
            this.bindEvents();
            this.loadFloor(this.currentFloor);
        }

        // ────────────────── INITIALIZE DOM & SVG STRUCTURE ──────────────────
        initDOM() {
            this.container.innerHTML = '';
            this.container.className = `cad-viewport-root cad-theme-${this.theme} relative w-full h-full overflow-hidden select-none`;
            this.container.style.touchAction = 'none';

            // SVG Canvas Container
            this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            this.svg.setAttribute('class', 'cad-svg-canvas w-full h-full block');
            this.svg.setAttribute('id', 'cad-master-svg');
            this.svg.style.touchAction = 'none';

            // SVG Defs
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            defs.innerHTML = `
                <!-- Grid background pattern -->
                <pattern id="cad-grid-pattern" width="20" height="20" patternUnits="userSpaceOnUse">
                    <line x1="0" y1="0" x2="20" y2="0" class="cad-grid-minor" stroke-width="0.5" />
                    <line x1="0" y1="0" x2="0" y2="20" class="cad-grid-minor" stroke-width="0.5" />
                </pattern>
                <pattern id="cad-grid-major-pattern" width="100" height="100" patternUnits="userSpaceOnUse">
                    <rect width="100" height="100" fill="url(#cad-grid-pattern)" />
                    <line x1="0" y1="0" x2="100" y2="0" class="cad-grid-major" stroke-width="1" />
                    <line x1="0" y1="0" x2="0" y2="100" class="cad-grid-major" stroke-width="1" />
                </pattern>

                <!-- Wood flooring pattern for gym -->
                <pattern id="cad-wood-court" width="12" height="6" patternUnits="userSpaceOnUse">
                    <rect width="12" height="6" fill="#78350f" fill-opacity="0.25" />
                    <line x1="0" y1="3" x2="12" y2="3" stroke="#b45309" stroke-width="0.5" stroke-opacity="0.4"/>
                    <line x1="6" y1="0" x2="6" y2="6" stroke="#b45309" stroke-width="0.5" stroke-opacity="0.3"/>
                </pattern>

                <!-- Trellis/Pergola striped hatch -->
                <pattern id="cad-trellis-hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                    <line x1="0" y1="0" x2="0" y2="8" stroke="#10b981" stroke-width="1" stroke-opacity="0.6"/>
                </pattern>

                <!-- Void Diagonal Crosshatch -->
                <pattern id="cad-void-hatch" width="16" height="16" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                    <line x1="0" y1="0" x2="16" y2="0" stroke="#64748b" stroke-width="0.6" stroke-dasharray="2,2" stroke-opacity="0.3"/>
                </pattern>
            `;
            this.svg.appendChild(defs);

            // Background Grid Rect
            this.bgGrid = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            this.bgGrid.setAttribute('x', '-10000');
            this.bgGrid.setAttribute('y', '-10000');
            this.bgGrid.setAttribute('width', '20000');
            this.bgGrid.setAttribute('height', '20000');
            this.bgGrid.setAttribute('fill', 'url(#cad-grid-major-pattern)');
            this.svg.appendChild(this.bgGrid);

            // Master Zoom/Pan Group
            this.masterGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            this.masterGroup.setAttribute('id', 'cad-master-group');

            // Layer Groups
            this.layerGroups = {
                'REF-OVERLAY': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-COURT': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-ROOM': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-WALL': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-DOOR': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-STAIR': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-COLS': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'S-GRID': document.createElementNS('http://www.w3.org/2000/svg', 'g'),
                'A-MEASURE': document.createElementNS('http://www.w3.org/2000/svg', 'g')
            };

            for (const key in this.layerGroups) {
                if (this.layers[key] && this.layers[key].visible === false) {
                    this.layerGroups[key].style.display = 'none';
                }
                this.masterGroup.appendChild(this.layerGroups[key]);
            }
            this.svg.appendChild(this.masterGroup);

            // Interactive Dynamic Crosshair (Screen Space)
            this.crosshairGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            this.crosshairGroup.setAttribute('id', 'cad-crosshair-group');
            this.crosshairGroup.innerHTML = `
                <line id="cad-ch-x" x1="0" y1="0" x2="0" y2="0" stroke="#38bdf8" stroke-width="0.75" stroke-dasharray="3,3" opacity="0.4" pointer-events="none" />
                <line id="cad-ch-y" x1="0" y1="0" x2="0" y2="0" stroke="#38bdf8" stroke-width="0.75" stroke-dasharray="3,3" opacity="0.4" pointer-events="none" />
                <circle id="cad-ch-target" cx="0" cy="0" r="6" fill="none" stroke="#38bdf8" stroke-width="1" opacity="0.6" pointer-events="none" />
            `;
            this.svg.appendChild(this.crosshairGroup);

            this.container.appendChild(this.svg);
            this.updateTransform();
        }

        // ────────────────── BIND EVENTS ──────────────────
        bindEvents() {
            let mouseDownPos = { x: 0, y: 0 };
            let mouseDownTime = 0;

            // Mouse Pan & Drag
            this.svg.addEventListener('mousedown', (e) => {
                mouseDownPos = { x: e.clientX, y: e.clientY };
                mouseDownTime = Date.now();
                if (e.button === 1 || e.button === 0) { // Middle or left click
                    if (this.activeTool === 'select' && e.target.closest('.cad-room-interactive')) {
                        // Let room click handler take over
                    } else if (this.activeTool === 'measure') {
                        this.handleMeasureClick(e);
                    } else {
                        this.viewState.isPanning = true;
                        this.viewState.startX = e.clientX - this.viewState.panX;
                        this.viewState.startY = e.clientY - this.viewState.panY;
                        this.container.style.cursor = 'grabbing';
                    }
                }
            });

            window.addEventListener('mousemove', (e) => {
                if (this.viewState.isPanning) {
                    this.viewState.panX = e.clientX - this.viewState.startX;
                    this.viewState.panY = e.clientY - this.viewState.startY;
                    this.updateTransform();
                }

                // Update crosshair & world coordinates
                const rect = this.svg.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;

                const chX = document.getElementById('cad-ch-x');
                const chY = document.getElementById('cad-ch-y');
                const chT = document.getElementById('cad-ch-target');
                if (chX && chY && chT) {
                    chX.setAttribute('x1', '0');
                    chX.setAttribute('x2', rect.width);
                    chX.setAttribute('y1', mouseY);
                    chX.setAttribute('y2', mouseY);

                    chY.setAttribute('x1', mouseX);
                    chY.setAttribute('x2', mouseX);
                    chY.setAttribute('y1', '0');
                    chY.setAttribute('y2', rect.height);

                    chT.setAttribute('cx', mouseX);
                    chT.setAttribute('cy', mouseY);
                }

                // World coordinate conversion:
                // svgX = (mouseX - panX) / scale
                // svgY = (mouseY - panY) / scale
                // worldX_m = svgX / svgScale
                // worldY_m = 60.5 - (svgY / svgScale)
                const svgX = (mouseX - this.viewState.panX) / this.viewState.scale;
                const svgY = (mouseY - this.viewState.panY) / this.viewState.scale;
                const worldX_m = svgX / this.svgScale;
                const worldY_m = this.buildingSpecs.depth - (svgY / this.svgScale);

                if (typeof this.options.onCoordinateChange === 'function') {
                    this.options.onCoordinateChange({
                        worldX_m: Math.max(0, Math.min(this.buildingSpecs.width, worldX_m)),
                        worldY_m: Math.max(0, Math.min(this.buildingSpecs.depth, worldY_m)),
                        worldX_mm: Math.round(worldX_m * 1000),
                        worldY_mm: Math.round(worldY_m * 1000)
                    });
                }
            });

            window.addEventListener('mouseup', (e) => {
                const dist = Math.hypot(e.clientX - mouseDownPos.x, e.clientY - mouseDownPos.y);
                const duration = Date.now() - mouseDownTime;
                if (this.viewState.isPanning) {
                    this.viewState.isPanning = false;
                    this.container.style.cursor = this.activeTool === 'measure' ? 'crosshair' : 'default';
                }
                // If it was a quick click on empty canvas, clear selection
                if (dist < 6 && duration < 300 && this.activeTool === 'select') {
                    if (!e.target || !e.target.closest || !e.target.closest('.cad-room-interactive')) {
                        this.clearSelection();
                        if (typeof this.options.onClearSelect === 'function') {
                            this.options.onClearSelect();
                        }
                    }
                }
            });

            // Mouse Wheel Zoom to Cursor
            this.svg.addEventListener('wheel', (e) => {
                e.preventDefault();
                const rect = this.svg.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;

                const zoomFactor = e.deltaY < 0 ? 1.15 : 0.87;
                const newScale = Math.max(0.15, Math.min(6.0, this.viewState.scale * zoomFactor));

                // Zoom centered around mouse position
                this.viewState.panX = mouseX - (mouseX - this.viewState.panX) * (newScale / this.viewState.scale);
                this.viewState.panY = mouseY - (mouseY - this.viewState.panY) * (newScale / this.viewState.scale);
                this.viewState.scale = newScale;

                this.updateTransform();
            }, { passive: false });

            // ── Mobile Touch Gestures: 1-finger pan, 2-finger pinch zoom, tap selection ──
            let touchStartDist = 0;
            let touchStartScale = 1;
            let touchStartTime = 0;
            let touchStartPos = { x: 0, y: 0 };
            let isTouching = false;
            let isPinching = false;

            this.svg.addEventListener('touchstart', (e) => {
                touchStartTime = Date.now();
                if (e.touches.length === 1) {
                    isTouching = true;
                    isPinching = false;
                    touchStartPos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                    this.viewState.isPanning = true;
                    this.viewState.startX = e.touches[0].clientX - this.viewState.panX;
                    this.viewState.startY = e.touches[0].clientY - this.viewState.panY;
                } else if (e.touches.length === 2) {
                    isTouching = false;
                    isPinching = true;
                    this.viewState.isPanning = false;
                    const t1 = e.touches[0];
                    const t2 = e.touches[1];
                    touchStartDist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
                    touchStartScale = this.viewState.scale;
                }
            }, { passive: false });

            this.svg.addEventListener('touchmove', (e) => {
                e.preventDefault();
                if (isPinching && e.touches.length === 2) {
                    const t1 = e.touches[0];
                    const t2 = e.touches[1];
                    const currentDist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
                    if (touchStartDist > 0) {
                        const factor = currentDist / touchStartDist;
                        const newScale = Math.max(0.15, Math.min(6.0, touchStartScale * factor));
                        
                        const rect = this.svg.getBoundingClientRect();
                        const midX = ((t1.clientX + t2.clientX) / 2) - rect.left;
                        const midY = ((t1.clientY + t2.clientY) / 2) - rect.top;

                        this.viewState.panX = midX - (midX - this.viewState.panX) * (newScale / this.viewState.scale);
                        this.viewState.panY = midY - (midY - this.viewState.panY) * (newScale / this.viewState.scale);
                        this.viewState.scale = newScale;
                        this.updateTransform();
                    }
                } else if (isTouching && e.touches.length === 1 && this.viewState.isPanning) {
                    this.viewState.panX = e.touches[0].clientX - this.viewState.startX;
                    this.viewState.panY = e.touches[0].clientY - this.viewState.startY;
                    this.updateTransform();
                }
            }, { passive: false });

            this.svg.addEventListener('touchend', (e) => {
                const duration = Date.now() - touchStartTime;
                if (isTouching && e.changedTouches.length > 0) {
                    const endTouch = e.changedTouches[0];
                    const dist = Math.hypot(endTouch.clientX - touchStartPos.x, endTouch.clientY - touchStartPos.y);
                    if (dist < 14 && duration < 350) {
                        const elem = document.elementFromPoint(endTouch.clientX, endTouch.clientY);
                        const roomEl = elem?.closest('.cad-room-interactive');
                        if (roomEl) {
                            const roomId = roomEl.getAttribute('data-room-id');
                            this.selectRoom(roomId);
                            if (typeof this.options.onRoomSelect === 'function') {
                                const floorData = this.floorsData[this.currentFloor];
                                const room = floorData?.rooms?.find(r => r.id === roomId);
                                this.options.onRoomSelect(room || { id: roomId });
                            }
                        } else if (!elem?.closest('.cad-floor-btn') && !elem?.closest('#topbar')) {
                            this.clearSelection();
                            if (typeof this.options.onClearSelect === 'function') {
                                this.options.onClearSelect();
                            }
                        }
                    }
                }
                if (e.touches.length === 0) {
                    this.viewState.isPanning = false;
                    isTouching = false;
                    isPinching = false;
                }
            });

            // Window Resize
            window.addEventListener('resize', () => {
                this.updateTransform();
            });
        }

        updateTransform() {
            if (this.masterGroup) {
                this.masterGroup.setAttribute(
                    'transform',
                    `translate(${this.viewState.panX}, ${this.viewState.panY}) scale(${this.viewState.scale})`
                );
            }
        }

        // ────────────────── LOAD & RENDER FLOOR GEOMETRY ──────────────────
        loadFloor(floorKey) {
            this.currentFloor = floorKey;
            const floorData = this.floorsData[floorKey];
            if (!floorData) {
                console.warn(`Floor ${floorKey} not found in NPC_FLOORS_DATA`);
                return;
            }

            // Clear all floor-specific layer groups
            for (const key in this.layerGroups) {
                this.layerGroups[key].innerHTML = '';
            }

            // 1. Render Developer Reference Overlay
            this.renderReferenceOverlay(floorKey, floorData);

            // 2. Render Column Grids & Structural Dimensions
            this.renderGridsAndDimensions(floorData);

            // 3. Render Rooms & Courtyard Voids
            this.renderRooms(floorData);

            // 4. Render Architectural Double Walls
            this.renderWalls(floorData);

            // 5. Render Doors with Swings
            this.renderDoors(floorData);

            // 6. Render Stairs
            this.renderStairs(floorData);

            // 7. Render Structural Columns
            this.renderColumns(floorData);

            // Reset selection
            this.selectedRoomId = null;
        }

        // ────────────────── 1. DEVELOPER REFERENCE OVERLAY ──────────────────
        renderReferenceOverlay(floorKey, floorData) {
            const group = this.layerGroups['REF-OVERLAY'];
            const cal = floorData.calibration;
            if (!cal || !cal.image) return;

            // Math Calibration:
            // Raster image size: 2977 x 2105
            // 1 meter = 20 SVG units
            const imgW = 2977 * (this.svgScale / cal.scale_x);
            const imgH = 2105 * (this.svgScale / cal.scale_y);
            const imgX = -cal.ax * (this.svgScale / cal.scale_x);
            // Grid 9 (top) is at Y9 = y1 - (60.5 * scale_y)
            const y9 = cal.y1 - (this.buildingSpecs.depth * cal.scale_y);
            const imgY = -y9 * (this.svgScale / cal.scale_y);

            const imgEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
            imgEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', cal.image);
            imgEl.setAttribute('x', imgX);
            imgEl.setAttribute('y', imgY);
            imgEl.setAttribute('width', imgW);
            imgEl.setAttribute('height', imgH);
            imgEl.setAttribute('id', 'cad-reference-overlay-img');
            imgEl.setAttribute('opacity', this.layers['REF-OVERLAY'].visible ? this.overlayOpacity : 0);
            imgEl.setAttribute('style', 'transition: opacity 0.2s ease; pointer-events: none;');

            group.appendChild(imgEl);
        }

        setOverlayOpacity(opacity) {
            this.overlayOpacity = Math.max(0, Math.min(1, parseFloat(opacity)));
            const imgEl = document.getElementById('cad-reference-overlay-img');
            if (imgEl && this.layers['REF-OVERLAY'].visible) {
                imgEl.setAttribute('opacity', this.overlayOpacity);
            }
        }

        toggleOverlay(visible) {
            this.layers['REF-OVERLAY'].visible = visible !== undefined ? visible : !this.layers['REF-OVERLAY'].visible;
            const imgEl = document.getElementById('cad-reference-overlay-img');
            if (imgEl) {
                imgEl.setAttribute('opacity', this.layers['REF-OVERLAY'].visible ? this.overlayOpacity : 0);
            }
            return this.layers['REF-OVERLAY'].visible;
        }

        // ────────────────── 2. GRIDS & DIMENSION STRINGS ──────────────────
        renderGridsAndDimensions(floorData) {
            const group = this.layerGroups['S-GRID'];
            const w = this.buildingSpecs.width * this.svgScale;
            const d = this.buildingSpecs.depth * this.svgScale;
            const ext = 70; // Extension for dimension lines

            // Vertical Grids (A to J)
            this.buildingSpecs.gridX.forEach((gx, idx) => {
                const svgX = gx.x * this.svgScale;
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', svgX);
                line.setAttribute('y1', -ext);
                line.setAttribute('x2', svgX);
                line.setAttribute('y2', d + ext);
                line.setAttribute('class', 'cad-grid-centerline');
                line.setAttribute('stroke-dasharray', '8,3,2,3');
                line.setAttribute('stroke-width', '0.75');
                group.appendChild(line);

                // Top Grid Bubble
                this.createGridBubble(group, svgX, -ext - 16, gx.id);
                // Bottom Grid Bubble
                this.createGridBubble(group, svgX, d + ext + 16, gx.id);

                // Dimension string between consecutive grids (Top)
                if (idx < this.buildingSpecs.gridX.length - 1) {
                    const nextGx = this.buildingSpecs.gridX[idx + 1];
                    const nextSvgX = nextGx.x * this.svgScale;
                    const deltaM = (nextGx.x - gx.x).toFixed(3);

                    const dimLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    dimLine.setAttribute('x1', svgX);
                    dimLine.setAttribute('y1', -ext - 4);
                    dimLine.setAttribute('x2', nextSvgX);
                    dimLine.setAttribute('y2', -ext - 4);
                    dimLine.setAttribute('class', 'cad-dim-line');
                    dimLine.setAttribute('stroke-width', '0.8');
                    group.appendChild(dimLine);

                    const dimText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    dimText.setAttribute('x', (svgX + nextSvgX) / 2);
                    dimText.setAttribute('y', -ext - 8);
                    dimText.setAttribute('class', 'cad-dim-text font-mono text-[9px] font-bold text-center');
                    dimText.setAttribute('text-anchor', 'middle');
                    dimText.textContent = `${deltaM}m`;
                    group.appendChild(dimText);
                }
            });

            // Horizontal Grids (1 to 9)
            this.buildingSpecs.gridY.forEach((gy, idx) => {
                // svgY = (depth - gy.y) * svgScale (Grid 9 is North = top = 0)
                const svgY = (this.buildingSpecs.depth - gy.y) * this.svgScale;

                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', -ext);
                line.setAttribute('y1', svgY);
                line.setAttribute('x2', w + ext);
                line.setAttribute('y2', svgY);
                line.setAttribute('class', 'cad-grid-centerline');
                line.setAttribute('stroke-dasharray', '8,3,2,3');
                line.setAttribute('stroke-width', '0.75');
                group.appendChild(line);

                // Left Grid Bubble
                this.createGridBubble(group, -ext - 16, svgY, gy.id);
                // Right Grid Bubble
                this.createGridBubble(group, w + ext + 16, svgY, gy.id);
            });
        }

        createGridBubble(group, cx, cy, label) {
            const bubble = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            bubble.setAttribute('class', 'cad-grid-bubble cursor-pointer');

            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', cx);
            circle.setAttribute('cy', cy);
            circle.setAttribute('r', '12');
            circle.setAttribute('class', 'cad-grid-bubble-circle');

            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', cx);
            text.setAttribute('y', cy + 4);
            text.setAttribute('class', 'cad-grid-bubble-text font-mono font-bold text-[11px]');
            text.setAttribute('text-anchor', 'middle');
            text.textContent = label;

            bubble.appendChild(circle);
            bubble.appendChild(text);
            group.appendChild(bubble);
        }

        // ────────────────── 3. ROOMS & COURTS ──────────────────
        renderRooms(floorData) {
            const roomGroup = this.layerGroups['A-ROOM'];
            const courtGroup = this.layerGroups['A-COURT'];

            (floorData.rooms || []).forEach(room => {
                const svgX = room.x * this.svgScale;
                const svgY = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                const svgW = room.w * this.svgScale;
                const svgH = room.d * this.svgScale;

                // Dedicated Sports Arena or Court
                if (room.type === 'sports' || room.court) {
                    this.renderSportsCourt(courtGroup, room, svgX, svgY, svgW, svgH);
                }

                // Dedicated Void ("OPEN ABOVE" / "OPEN TO BELOW")
                if (room.type === 'void') {
                    this.renderVoidArea(courtGroup, room, svgX, svgY, svgW, svgH);
                    return;
                }

                const roomStatus = this.roomStatuses?.[room.id] || this.roomStatuses?.[room.code] || room.status;
                const isOccupied = (roomStatus === 'occupied');

                const roomEl = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                roomEl.setAttribute('class', 'cad-room-interactive cursor-pointer transition-all' + (isOccupied ? ' is-occupied' : ''));
                roomEl.setAttribute('data-room-id', room.id);
                roomEl.setAttribute('data-room-code', room.code || room.id);
                roomEl.setAttribute('data-status', roomStatus || 'available');
                roomEl.setAttribute('id', `cad-room-${room.id}`);

                // Floor surface fill
                if (room.polygon) {
                    const pointsStr = room.polygon.map(pt => {
                        const px = pt[0] * this.svgScale;
                        const py = (this.buildingSpecs.depth - pt[1]) * this.svgScale;
                        return `${px},${py}`;
                    }).join(' ');
                    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    poly.setAttribute('points', pointsStr);
                    poly.setAttribute('class', `cad-room-floor-surface fill-${room.type}`);
                    poly.setAttribute('fill', this.getRoomTypeFill(room.type));
                    roomEl.appendChild(poly);

                    const borderPoly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    borderPoly.setAttribute('points', pointsStr);
                    borderPoly.setAttribute('class', 'cad-room-interactive-border');
                    borderPoly.setAttribute('fill', 'none');
                    roomEl.appendChild(borderPoly);
                } else {
                    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rect.setAttribute('x', svgX);
                    rect.setAttribute('y', svgY);
                    rect.setAttribute('width', svgW);
                    rect.setAttribute('height', svgH);
                    rect.setAttribute('class', `cad-room-floor-surface fill-${room.type}`);
                    rect.setAttribute('fill', this.getRoomTypeFill(room.type));
                    roomEl.appendChild(rect);

                    const borderRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    borderRect.setAttribute('x', svgX);
                    borderRect.setAttribute('y', svgY);
                    borderRect.setAttribute('width', svgW);
                    borderRect.setAttribute('height', svgH);
                    borderRect.setAttribute('class', 'cad-room-interactive-border');
                    borderRect.setAttribute('fill', 'none');
                    roomEl.appendChild(borderRect);
                }

                // Room Labels (Centered)
                const labelGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                labelGroup.setAttribute('class', 'cad-room-tag pointer-events-none');

                const centerX = room.labelX !== undefined ? (room.labelX * this.svgScale) : (svgX + svgW / 2);
                const centerY = room.labelY !== undefined ? ((this.buildingSpecs.depth - room.labelY) * this.svgScale) : (svgY + svgH / 2);
                labelGroup.setAttribute('data-center-x', centerX);
                labelGroup.setAttribute('data-center-y', centerY);
                labelGroup.setAttribute('data-code-len', (room.code || room.id).length);

                // Occupied Indicator Dot
                if (isOccupied) {
                    const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    dot.setAttribute('cx', centerX - ((room.code || room.id).length * 3.4 + 7));
                    dot.setAttribute('cy', centerY - 13);
                    dot.setAttribute('r', '3.5');
                    dot.setAttribute('class', 'cad-occupied-dot');
                    dot.setAttribute('fill', '#ef4444');
                    labelGroup.appendChild(dot);
                }

                // Code Pill
                const textCode = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                textCode.setAttribute('x', centerX);
                textCode.setAttribute('y', centerY - 10);
                textCode.setAttribute('class', 'cad-tag-code font-mono font-bold text-[11px]');
                textCode.setAttribute('text-anchor', 'middle');
                textCode.textContent = room.code || room.id;
                labelGroup.appendChild(textCode);

                // Room Name
                const textName = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                textName.setAttribute('x', centerX);
                textName.setAttribute('y', centerY + 4);
                textName.setAttribute('class', 'cad-tag-name font-sans font-semibold text-[10px]');
                textName.setAttribute('text-anchor', 'middle');
                textName.textContent = this.truncateRoomName(room.name, svgW);
                labelGroup.appendChild(textName);

                // Area (m²)
                if (room.area_sqm) {
                    const textArea = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    textArea.setAttribute('x', centerX);
                    textArea.setAttribute('y', centerY + 18);
                    textArea.setAttribute('class', 'cad-tag-area font-mono text-[9px]');
                    textArea.setAttribute('text-anchor', 'middle');
                    textArea.textContent = `A: ${room.area_sqm.toFixed(1)} m²`;
                    labelGroup.appendChild(textArea);
                }

                roomEl.appendChild(labelGroup);

                // Interaction Handlers
                roomEl.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectRoom(room.id);
                    if (typeof this.options.onRoomSelect === 'function') {
                        this.options.onRoomSelect(room);
                    }
                });

                roomEl.addEventListener('mouseenter', (e) => {
                    this.hoveredRoomId = room.id;
                    if (typeof this.options.onRoomHover === 'function') {
                        this.options.onRoomHover(room, e);
                    }
                });

                roomEl.addEventListener('mouseleave', () => {
                    this.hoveredRoomId = null;
                    if (typeof this.options.onRoomHover === 'function') {
                        this.options.onRoomHover(null);
                    }
                });

                roomGroup.appendChild(roomEl);
            });
        }

        truncateRoomName(name, width) {
            if (width < 80 && name.length > 10) return name.substring(0, 8) + '…';
            if (width < 140 && name.length > 20) return name.substring(0, 18) + '…';
            return name;
        }

        getRoomTypeFill(type) {
            switch (type) {
                case 'lab': return 'rgba(6, 182, 212, 0.16)'; // Cyan
                case 'classroom': return 'rgba(59, 130, 246, 0.14)'; // Blue
                case 'office': return 'rgba(168, 85, 247, 0.16)'; // Purple
                case 'amenity': return 'rgba(16, 185, 129, 0.16)'; // Emerald
                case 'sports': return 'url(#cad-wood-court)'; // Hardwood Court
                case 'restroom': return 'rgba(14, 165, 233, 0.18)'; // Sky Blue
                case 'stairs': return 'rgba(244, 63, 94, 0.15)'; // Rose
                case 'utility': return 'rgba(100, 116, 139, 0.2)'; // Slate
                default: return 'rgba(255, 255, 255, 0.05)';
            }
        }

        // ────────────────── SPORTS ARENA & COURTS ──────────────────
        renderSportsCourt(group, room, svgX, svgY, svgW, svgH) {
            // Hardwood background
            const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            bgRect.setAttribute('x', svgX);
            bgRect.setAttribute('y', svgY);
            bgRect.setAttribute('width', svgW);
            bgRect.setAttribute('height', svgH);
            bgRect.setAttribute('fill', 'url(#cad-wood-court)');
            bgRect.setAttribute('stroke', '#d97706');
            bgRect.setAttribute('stroke-width', '2');
            group.appendChild(bgRect);

            if (room.court && room.court.type === 'fiba_basketball') {
                // Official FIBA regulation court linework (28m x 15m) centered inside arena
                const courtW = 28.0 * this.svgScale;
                const courtH = 15.0 * this.svgScale;
                const courtX = svgX + (svgW - courtW) / 2;
                const courtY = svgY + (svgH - courtH) / 2;

                const courtRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                courtRect.setAttribute('x', courtX);
                courtRect.setAttribute('y', courtY);
                courtRect.setAttribute('width', courtW);
                courtRect.setAttribute('height', courtH);
                courtRect.setAttribute('class', 'cad-court-perimeter');
                courtRect.setAttribute('fill', 'rgba(217, 119, 6, 0.15)');
                courtRect.setAttribute('stroke', '#f97316');
                courtRect.setAttribute('stroke-width', '2');
                group.appendChild(courtRect);

                // Half-court line
                const midLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                midLine.setAttribute('x1', courtX + courtW / 2);
                midLine.setAttribute('y1', courtY);
                midLine.setAttribute('x2', courtX + courtW / 2);
                midLine.setAttribute('y2', courtY + courtH);
                midLine.setAttribute('class', 'cad-court-centerline');
                midLine.setAttribute('stroke', '#f97316');
                midLine.setAttribute('stroke-width', '2');
                group.appendChild(midLine);

                // Center Circle (r = 1.8m)
                const centerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                centerCircle.setAttribute('cx', courtX + courtW / 2);
                centerCircle.setAttribute('cy', courtY + courtH / 2);
                centerCircle.setAttribute('r', 1.8 * this.svgScale);
                centerCircle.setAttribute('class', 'cad-court-circle');
                centerCircle.setAttribute('fill', 'none');
                centerCircle.setAttribute('stroke', '#f97316');
                centerCircle.setAttribute('stroke-width', '2');
                group.appendChild(centerCircle);

                // Left & Right Keys & 3-Point Arcs
                this.renderBasketballKey(group, courtX, courtY, courtH, 1);
                this.renderBasketballKey(group, courtX + courtW, courtY, courtH, -1);
            }
        }

        renderBasketballKey(group, baseX, courtY, courtH, dir) {
            const keyW = 5.8 * this.svgScale;
            const keyH = 4.9 * this.svgScale;
            const keyY = courtY + (courtH - keyH) / 2;

            // Free throw lane
            const lane = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            lane.setAttribute('x', dir === 1 ? baseX : baseX - keyW);
            lane.setAttribute('y', keyY);
            lane.setAttribute('width', keyW);
            lane.setAttribute('height', keyH);
            lane.setAttribute('fill', 'rgba(234, 88, 12, 0.2)');
            lane.setAttribute('stroke', '#f97316');
            lane.setAttribute('stroke-width', '1.5');
            group.appendChild(lane);

            // Free throw circle
            const ftCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            ftCircle.setAttribute('cx', dir === 1 ? baseX + keyW : baseX - keyW);
            ftCircle.setAttribute('cy', courtY + courtH / 2);
            ftCircle.setAttribute('r', 1.8 * this.svgScale);
            ftCircle.setAttribute('fill', 'none');
            ftCircle.setAttribute('stroke', '#f97316');
            ftCircle.setAttribute('stroke-width', '1.5');
            group.appendChild(ftCircle);

            // 3-Point Arc
            const arc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const arcR = 6.75 * this.svgScale;
            const cy = courtY + courtH / 2;
            const sweep = dir === 1 ? 1 : 0;
            const targetX = dir === 1 ? baseX + arcR : baseX - arcR;
            arc.setAttribute('d', `M ${baseX} ${cy - arcR} A ${arcR} ${arcR} 0 0 ${sweep} ${targetX} ${cy} A ${arcR} ${arcR} 0 0 ${sweep} ${baseX} ${cy + arcR}`);
            arc.setAttribute('fill', 'none');
            arc.setAttribute('stroke', '#f97316');
            arc.setAttribute('stroke-width', '1.5');
            group.appendChild(arc);
        }

        // ────────────────── CENTRAL VOID & ATRIUM ──────────────────
        renderVoidArea(group, room, svgX, svgY, svgW, svgH) {
            // Void bounding box
            const voidRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            voidRect.setAttribute('x', svgX);
            voidRect.setAttribute('y', svgY);
            voidRect.setAttribute('width', svgW);
            voidRect.setAttribute('height', svgH);
            voidRect.setAttribute('fill', 'url(#cad-void-hatch)');
            voidRect.setAttribute('stroke', '#94a3b8');
            voidRect.setAttribute('stroke-dasharray', '4,4');
            voidRect.setAttribute('stroke-width', '1.5');
            group.appendChild(voidRect);

            // Architectural Diagonal Cross
            const line1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line1.setAttribute('x1', svgX);
            line1.setAttribute('y1', svgY);
            line1.setAttribute('x2', svgX + svgW);
            line1.setAttribute('y2', svgY + svgH);
            line1.setAttribute('stroke', '#94a3b8');
            line1.setAttribute('stroke-dasharray', '6,6');
            line1.setAttribute('stroke-width', '1');
            group.appendChild(line1);

            const line2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line2.setAttribute('x1', svgX + svgW);
            line2.setAttribute('y1', svgY);
            line2.setAttribute('x2', svgX);
            line2.setAttribute('y2', svgY + svgH);
            line2.setAttribute('stroke', '#94a3b8');
            line2.setAttribute('stroke-dasharray', '6,6');
            line2.setAttribute('stroke-width', '1');
            group.appendChild(line2);

            // Void Label
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', svgX + svgW / 2);
            text.setAttribute('y', svgY + svgH / 2 + 5);
            text.setAttribute('class', 'cad-tag-code font-mono font-bold text-sm tracking-widest text-slate-400');
            text.setAttribute('text-anchor', 'middle');
            text.textContent = room.code || 'OPEN ABOVE';
            group.appendChild(text);
        }

        // ────────────────── 4. ARCHITECTURAL DOUBLE WALLS & PARTITIONS ──────────────────
        renderWalls(floorData) {
            const wallGroup = this.layerGroups['A-WALL'];
            const thick = 6; // 0.3m wall thickness in SVG units (20 SVG units/m * 0.3m = 6)
            const floorKey = this.currentFloor;

            // 1. Exterior Building Perimeter Shell (Double-line architectural walls)
            if (floorKey === '4F') {
                // 4F perimeter with South Executive Terrace setback (Grids 1-9, A-J)
                const outerPts = [
                    [0.0, 0.0], [24.667, 0.0], [38.001, 5.8], [50.501, 5.8],
                    [50.501, 14.5], [56.001, 14.5], [56.001, 60.5], [0.0, 60.5]
                ];
                const outerStr = outerPts.map(pt => `${pt[0] * this.svgScale},${(this.buildingSpecs.depth - pt[1]) * this.svgScale}`).join(' ');
                const outerPoly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                outerPoly.setAttribute('points', outerStr);
                outerPoly.setAttribute('class', 'cad-exterior-wall-outer');
                wallGroup.appendChild(outerPoly);

                // South & East terrace balustrade / parapet railing along perimeter (Grid 1 to Grid J, up to y=14.5)
                const terrPts = [[24.667, 0.0], [56.001, 0.0], [56.001, 14.5]];
                const terrStr = terrPts.map(pt => `${pt[0] * this.svgScale},${(this.buildingSpecs.depth - pt[1]) * this.svgScale}`).join(' ');
                const terrPoly = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
                terrPoly.setAttribute('points', terrStr);
                terrPoly.setAttribute('class', 'cad-balustrade');
                wallGroup.appendChild(terrPoly);

                // Architectural Glass Curtain Wall (salamin) along diagonal facade and terrace hallway
                const glassPts = [[24.667, 0.0], [38.001, 5.8], [50.501, 5.8], [50.501, 14.5]];
                const glassStr = glassPts.map(pt => `${pt[0] * this.svgScale},${(this.buildingSpecs.depth - pt[1]) * this.svgScale}`).join(' ');
                const glassPoly = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
                glassPoly.setAttribute('points', glassStr);
                glassPoly.setAttribute('class', 'cad-glass-wall');
                wallGroup.appendChild(glassPoly);
            } else if (floorKey === 'RD') {
                // RD outer perimeter parapet
                const minSvgY = (this.buildingSpecs.depth - 60.5) * this.svgScale;
                const maxSvgY = (this.buildingSpecs.depth - 1.5) * this.svgScale;
                const svgW = this.buildingSpecs.width * this.svgScale;
                const svgH = maxSvgY - minSvgY;

                const outerShell = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                outerShell.setAttribute('x', '0');
                outerShell.setAttribute('y', minSvgY);
                outerShell.setAttribute('width', svgW);
                outerShell.setAttribute('height', svgH);
                outerShell.setAttribute('class', 'cad-exterior-wall-outer');
                wallGroup.appendChild(outerShell);
            } else if (floorKey === '1F') {
                // 1F perimeter with Grand Entrance Plaza cutout
                const outerPts = [
                    [0.0, 1.5], [18.0, 1.5], [18.0, 0.0], [38.001, 0.0],
                    [38.001, 1.5], [56.001, 1.5], [56.001, 60.5], [0.0, 60.5]
                ];
                const outerStr = outerPts.map(pt => `${pt[0] * this.svgScale},${(this.buildingSpecs.depth - pt[1]) * this.svgScale}`).join(' ');
                const outerPoly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                outerPoly.setAttribute('points', outerStr);
                outerPoly.setAttribute('class', 'cad-exterior-wall-outer');
                wallGroup.appendChild(outerPoly);
            } else {
                // 2F and 3F standard rectangular perimeter (Y from 1.5 to 60.5, X from 0.0 to 56.001)
                const minSvgY = (this.buildingSpecs.depth - 60.5) * this.svgScale;
                const maxSvgY = (this.buildingSpecs.depth - 1.5) * this.svgScale;
                const svgW = this.buildingSpecs.width * this.svgScale;
                const svgH = maxSvgY - minSvgY;

                const outerShell = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                outerShell.setAttribute('x', '0');
                outerShell.setAttribute('y', minSvgY);
                outerShell.setAttribute('width', svgW);
                outerShell.setAttribute('height', svgH);
                outerShell.setAttribute('class', 'cad-exterior-wall-outer');
                wallGroup.appendChild(outerShell);

                const innerShell = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                innerShell.setAttribute('x', thick);
                innerShell.setAttribute('y', minSvgY + thick);
                innerShell.setAttribute('width', svgW - thick * 2);
                innerShell.setAttribute('height', svgH - thick * 2);
                innerShell.setAttribute('class', 'cad-exterior-wall-inner');
                wallGroup.appendChild(innerShell);
            }

            // 2. Bold Architectural Partition Walls for Enclosed Rooms
            (floorData.rooms || []).forEach(room => {
                // Do not draw partition walls enclosing open hallways, plazas, open courts, or waiting areas
                const isOpenCirculation = 
                    room.type === 'void' ||
                    (room.code && room.code.includes('OPEN')) ||
                    room.id.endsWith('-H01') || room.id.endsWith('-H02') || room.id.endsWith('-H03') ||
                    room.id.endsWith('-WAIT') || room.id.endsWith('-LOBBY') || room.id.endsWith('-EVENT') ||
                    room.id.endsWith('-ENTR') || room.id.endsWith('-COURT') ||
                    (room.name && (
                        room.name.toLowerCase().includes('hallway') ||
                        room.name.toLowerCase().includes('open space') ||
                        room.name.toLowerCase().includes('waiting area') ||
                        room.name.toLowerCase().includes('garden') ||
                        room.name.toLowerCase().includes('pergola') ||
                        room.name.toLowerCase().includes('courtyard') ||
                        room.name.toLowerCase().includes('plaza')
                    ));
                if (isOpenCirculation) return;

                const rx = room.x * this.svgScale;
                const ry = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                const rw = room.w * this.svgScale;
                const rh = room.d * this.svgScale;

                if (room.polygon) {
                    const pointsStr = room.polygon.map(pt => {
                        const px = pt[0] * this.svgScale;
                        const py = (this.buildingSpecs.depth - pt[1]) * this.svgScale;
                        return `${px},${py}`;
                    }).join(' ');
                    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    poly.setAttribute('points', pointsStr);
                    poly.setAttribute('class', 'cad-partition-wall');
                    poly.setAttribute('data-wall-room-id', room.id);
                    wallGroup.appendChild(poly);
                } else {
                    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rect.setAttribute('x', rx);
                    rect.setAttribute('y', ry);
                    rect.setAttribute('width', rw);
                    rect.setAttribute('height', rh);
                    rect.setAttribute('class', 'cad-partition-wall');
                    rect.setAttribute('data-wall-room-id', room.id);
                    wallGroup.appendChild(rect);
                }

                // 3. Restroom Stall Dividers / Cubicle Partitions
                if (room.type === 'restroom') {
                    this.renderToiletCubicles(wallGroup, room, rx, ry, rw, rh);
                }
            });

            // 4. Balustrades / Railings around Central Open Atriums & Voids
            (floorData.rooms || []).forEach(room => {
                if (room.type === 'void') {
                    const rx = room.x * this.svgScale;
                    const ry = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                    const rw = room.w * this.svgScale;
                    const rh = room.d * this.svgScale;

                    const rail = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rail.setAttribute('x', rx);
                    rail.setAttribute('y', ry);
                    rail.setAttribute('width', rw);
                    rail.setAttribute('height', rh);
                    rail.setAttribute('class', 'cad-balustrade');
                    wallGroup.appendChild(rail);
                }
            });
        }

        renderToiletCubicles(group, room, rx, ry, rw, rh) {
            // Internal cubicle partitions
            const numStalls = Math.min(5, Math.max(2, Math.floor(rw / (1.1 * this.svgScale))));
            const stallW = rw / numStalls;
            const stallD = Math.min(rh * 0.45, 1.4 * this.svgScale);

            for (let i = 1; i < numStalls; i++) {
                const stallLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                stallLine.setAttribute('x1', rx + (i * stallW));
                stallLine.setAttribute('y1', ry);
                stallLine.setAttribute('x2', rx + (i * stallW));
                stallLine.setAttribute('y2', ry + stallD);
                stallLine.setAttribute('class', 'cad-toilet-cubicle');
                group.appendChild(stallLine);
            }
        }

        // ────────────────── 5. DOORS & SWINGS ──────────────────
        renderDoors(floorData) {
            const doorGroup = this.layerGroups['A-DOOR'];
            doorGroup.innerHTML = '';
            // Temporarily disabled per door layer visibility configuration
            if (!this.layers['A-DOOR']?.visible) return;

            (floorData.rooms || []).forEach(room => {
                if (!room.doors || room.doors.length === 0) return;

                const rx = room.x * this.svgScale;
                const ry = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                const rw = room.w * this.svgScale;
                const rh = room.d * this.svgScale;

                room.doors.forEach(door => {
                    const dw = (door.w || 1.0) * this.svgScale;
                    let dx = rx, dy = ry;

                    if (door.wall === 'south') {
                        dx = rx + (door.offset * this.svgScale);
                        dy = ry + rh;
                    } else if (door.wall === 'north') {
                        dx = rx + (door.offset * this.svgScale);
                        dy = ry;
                    } else if (door.wall === 'west') {
                        dx = rx;
                        dy = ry + (door.offset * this.svgScale);
                    } else if (door.wall === 'east') {
                        dx = rx + rw;
                        dy = ry + (door.offset * this.svgScale);
                    }

                    // Door leaf line (90 degree swing)
                    const leaf = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    leaf.setAttribute('x1', dx);
                    leaf.setAttribute('y1', dy);
                    leaf.setAttribute('x2', dx + (door.wall === 'south' ? dw : 0));
                    leaf.setAttribute('y2', dy + (door.wall === 'west' ? dw : 0));
                    leaf.setAttribute('class', 'cad-door-leaf');
                    leaf.setAttribute('stroke-width', '1.5');
                    doorGroup.appendChild(leaf);

                    // Door swing circular arc
                    const arc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    arc.setAttribute('d', `M ${dx + dw} ${dy} A ${dw} ${dw} 0 0 1 ${dx} ${dy + dw}`);
                    arc.setAttribute('class', 'cad-door-swing-arc');
                    arc.setAttribute('fill', 'none');
                    arc.setAttribute('stroke-dasharray', '2,2');
                    arc.setAttribute('stroke-width', '1');
                    doorGroup.appendChild(arc);
                });
            });

            // 4F Terrace Double Doors (from South Hallway and East Hallway into Terrace)
            if (this.currentFloor === '4F') {
                const addDoubleDoor = (x, y, isVertical, width) => {
                    const dw = (width / 2) * this.svgScale;
                    const sx = x * this.svgScale;
                    const sy = (this.buildingSpecs.depth - y) * this.svgScale;
                    if (isVertical) {
                        // Vertical wall at x, door centered around y
                        const topY = sy - dw;
                        const botY = sy + dw;
                        // Top leaf & arc swinging east (+x)
                        const l1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        l1.setAttribute('x1', sx); l1.setAttribute('y1', topY);
                        l1.setAttribute('x2', sx + dw); l1.setAttribute('y2', topY);
                        l1.setAttribute('class', 'cad-door-leaf'); l1.setAttribute('stroke-width', '1.5');
                        doorGroup.appendChild(l1);
                        const a1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        a1.setAttribute('d', `M ${sx + dw} ${topY} A ${dw} ${dw} 0 0 1 ${sx} ${sy}`);
                        a1.setAttribute('class', 'cad-door-swing-arc'); a1.setAttribute('fill', 'none');
                        a1.setAttribute('stroke-dasharray', '2,2'); a1.setAttribute('stroke-width', '1');
                        doorGroup.appendChild(a1);

                        // Bottom leaf & arc swinging east (+x)
                        const l2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        l2.setAttribute('x1', sx); l2.setAttribute('y1', botY);
                        l2.setAttribute('x2', sx + dw); l2.setAttribute('y2', botY);
                        l2.setAttribute('class', 'cad-door-leaf'); l2.setAttribute('stroke-width', '1.5');
                        doorGroup.appendChild(l2);
                        const a2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        a2.setAttribute('d', `M ${sx + dw} ${botY} A ${dw} ${dw} 0 0 0 ${sx} ${sy}`);
                        a2.setAttribute('class', 'cad-door-swing-arc'); a2.setAttribute('fill', 'none');
                        a2.setAttribute('stroke-dasharray', '2,2'); a2.setAttribute('stroke-width', '1');
                        doorGroup.appendChild(a2);
                    } else {
                        // Horizontal wall at y, door centered around x
                        const leftX = sx - dw;
                        const rightX = sx + dw;
                        // Left leaf & arc swinging south (+sy in SVG)
                        const l1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        l1.setAttribute('x1', leftX); l1.setAttribute('y1', sy);
                        l1.setAttribute('x2', leftX); l1.setAttribute('y2', sy + dw);
                        l1.setAttribute('class', 'cad-door-leaf'); l1.setAttribute('stroke-width', '1.5');
                        doorGroup.appendChild(l1);
                        const a1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        a1.setAttribute('d', `M ${leftX} ${sy + dw} A ${dw} ${dw} 0 0 0 ${sx} ${sy}`);
                        a1.setAttribute('class', 'cad-door-swing-arc'); a1.setAttribute('fill', 'none');
                        a1.setAttribute('stroke-dasharray', '2,2'); a1.setAttribute('stroke-width', '1');
                        doorGroup.appendChild(a1);

                        // Right leaf & arc swinging south (+sy in SVG)
                        const l2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                        l2.setAttribute('x1', rightX); l2.setAttribute('y1', sy);
                        l2.setAttribute('x2', rightX); l2.setAttribute('y2', sy + dw);
                        l2.setAttribute('class', 'cad-door-leaf'); l2.setAttribute('stroke-width', '1.5');
                        doorGroup.appendChild(l2);
                        const a2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        a2.setAttribute('d', `M ${rightX} ${sy + dw} A ${dw} ${dw} 0 0 1 ${sx} ${sy}`);
                        a2.setAttribute('class', 'cad-door-swing-arc'); a2.setAttribute('fill', 'none');
                        a2.setAttribute('stroke-dasharray', '2,2'); a2.setAttribute('stroke-width', '1');
                        doorGroup.appendChild(a2);
                    }
                };
                // East terrace bay entrance from Hallway-1 along Grid I (matching blueprint & user screenshot)
                addDoubleDoor(50.501, 11.4, true, 2.0);
                // South executive terrace entrance from South Hallway
                addDoubleDoor(44.0, 5.8, false, 2.0);
            }
        }

        // ────────────────── 6. STAIRS ──────────────────
        renderStairs(floorData) {
            const stairGroup = this.layerGroups['A-STAIR'];

            (floorData.rooms || []).forEach(room => {
                if (room.type !== 'stairs') return;

                const sx = room.x * this.svgScale;
                const sy = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                const sw = room.w * this.svgScale;
                const sh = room.d * this.svgScale;

                // Treads (Horizontal or Vertical steps)
                const steps = 10;
                const stepH = sh / steps;

                for (let i = 1; i < steps; i++) {
                    const stepLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    stepLine.setAttribute('x1', sx);
                    stepLine.setAttribute('y1', sy + (i * stepH));
                    stepLine.setAttribute('x2', sx + sw);
                    stepLine.setAttribute('y2', sy + (i * stepH));
                    stepLine.setAttribute('stroke', '#a855f7');
                    stepLine.setAttribute('stroke-width', '0.75');
                    stairGroup.appendChild(stepLine);
                }

                // Mid-rail & UP/DN arrow
                const midRail = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                midRail.setAttribute('x1', sx + sw / 2);
                midRail.setAttribute('y1', sy);
                midRail.setAttribute('x2', sx + sw / 2);
                midRail.setAttribute('y2', sy + sh);
                midRail.setAttribute('stroke', '#a855f7');
                midRail.setAttribute('stroke-width', '1.5');
                stairGroup.appendChild(midRail);
            });
        }

        // ────────────────── 7. STRUCTURAL COLUMNS ──────────────────
        renderColumns(floorData) {
            const colGroup = this.layerGroups['A-COLS'];
            const colSize = 0.60 * this.svgScale; // 600mm x 600mm reinforced concrete columns

            (floorData.columns || []).forEach(col => {
                const cx = col.x * this.svgScale;
                const cy = (this.buildingSpecs.depth - col.y) * this.svgScale;

                const colRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                colRect.setAttribute('x', cx - colSize / 2);
                colRect.setAttribute('y', cy - colSize / 2);
                colRect.setAttribute('width', colSize);
                colRect.setAttribute('height', colSize);
                colRect.setAttribute('class', 'cad-column-pillar');
                colRect.setAttribute('fill', '#ffffff');
                colRect.setAttribute('stroke', '#00e5ff');
                colRect.setAttribute('stroke-width', '1.2');

                // Diagonal cross inside column
                const c1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                c1.setAttribute('x1', cx - colSize / 2);
                c1.setAttribute('y1', cy - colSize / 2);
                c1.setAttribute('x2', cx + colSize / 2);
                c1.setAttribute('y2', cy + colSize / 2);
                c1.setAttribute('class', 'cad-col-cross');

                const c2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                c2.setAttribute('x1', cx + colSize / 2);
                c2.setAttribute('y1', cy - colSize / 2);
                c2.setAttribute('x2', cx - colSize / 2);
                c2.setAttribute('y2', cy + colSize / 2);
                c2.setAttribute('class', 'cad-col-cross');

                colGroup.appendChild(colRect);
                colGroup.appendChild(c1);
                colGroup.appendChild(c2);
            });
        }

        // ────────────────── SELECTION & TOOLS ──────────────────
        selectRoom(roomId, zoomTo = false) {
            if (!roomId) {
                this.clearSelection();
                return;
            }

            this.selectedRoomId = roomId;

            this.container.querySelectorAll('.cad-room-interactive').forEach(el => {
                if (el.getAttribute('data-room-id') === roomId) {
                    el.classList.add('is-selected');
                } else {
                    el.classList.remove('is-selected');
                }
            });

            if (zoomTo && roomId) {
                const floorData = this.floorsData[this.currentFloor];
                const room = floorData?.rooms?.find(r => r.id === roomId);
                if (room) {
                    const rx = room.x * this.svgScale;
                    const ry = (this.buildingSpecs.depth - (room.y + room.d)) * this.svgScale;
                    const rw = room.w * this.svgScale;
                    const rh = room.d * this.svgScale;

                    const rect = this.svg.getBoundingClientRect();
                    this.viewState.scale = 1.6;
                    this.viewState.panX = rect.width / 2 - (rx + rw / 2) * this.viewState.scale;
                    this.viewState.panY = rect.height / 2 - (ry + rh / 2) * this.viewState.scale;
                    this.updateTransform();
                }
            }
        }

        clearSelection() {
            this.selectedRoomId = null;
            this.container.querySelectorAll('.cad-room-interactive.is-selected').forEach(el => {
                el.classList.remove('is-selected');
            });
        }

        zoomToRoom(roomId) {
            this.selectRoom(roomId, true);
        }

        updateRoomStatuses(statusMap) {
            this.roomStatuses = statusMap || {};
            if (!this.container) return;
            this.container.querySelectorAll('.cad-room-interactive').forEach(el => {
                const rid = el.getAttribute('data-room-id');
                const rcode = el.getAttribute('data-room-code');
                const status = this.roomStatuses[rid] || (rcode && this.roomStatuses[rcode]) || el.getAttribute('data-status') || 'available';
                const isOcc = (status === 'occupied');
                el.classList.toggle('is-occupied', isOcc);
                el.setAttribute('data-status', status);

                const labelGroup = el.querySelector('.cad-room-tag');
                let dot = labelGroup ? labelGroup.querySelector('.cad-occupied-dot') : null;
                if (isOcc && !dot && labelGroup) {
                    const cx = parseFloat(labelGroup.getAttribute('data-center-x') || 0);
                    const cy = parseFloat(labelGroup.getAttribute('data-center-y') || 0);
                    const codeLen = parseInt(labelGroup.getAttribute('data-code-len') || '5', 10);
                    dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    dot.setAttribute('cx', cx - (codeLen * 3.4 + 7));
                    dot.setAttribute('cy', cy - 13);
                    dot.setAttribute('r', '3.5');
                    dot.setAttribute('class', 'cad-occupied-dot');
                    dot.setAttribute('fill', '#ef4444');
                    labelGroup.appendChild(dot);
                } else if (!isOcc && dot) {
                    dot.remove();
                }
            });
        }

        setTool(toolName) {
            this.activeTool = toolName;
            this.container.style.cursor = toolName === 'measure' ? 'crosshair' : 'default';
            if (toolName !== 'measure') {
                this.measureState.step = 0;
                this.layerGroups['A-MEASURE'].innerHTML = '';
            }
        }

        handleMeasureClick(e) {
            const rect = this.svg.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const svgX = (mouseX - this.viewState.panX) / this.viewState.scale;
            const svgY = (mouseY - this.viewState.panY) / this.viewState.scale;

            const worldX_m = svgX / this.svgScale;
            const worldY_m = this.buildingSpecs.depth - (svgY / this.svgScale);

            if (this.measureState.step === 0) {
                this.measureState.step = 1;
                this.measureState.p1 = { x: worldX_m, y: worldY_m, svgX, svgY };
                this.layerGroups['A-MEASURE'].innerHTML = `
                    <circle cx="${svgX}" cy="${svgY}" r="4" fill="#f59e0b" stroke="#ffffff" stroke-width="1.5" />
                `;
            } else {
                this.measureState.step = 0;
                this.measureState.p2 = { x: worldX_m, y: worldY_m, svgX, svgY };
                const p1 = this.measureState.p1;
                const p2 = this.measureState.p2;

                const dx = p2.x - p1.x;
                const dy = p2.y - p1.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                this.layerGroups['A-MEASURE'].innerHTML = `
                    <line x1="${p1.svgX}" y1="${p1.svgY}" x2="${p2.svgX}" y2="${p2.svgY}" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4,4" />
                    <circle cx="${p1.svgX}" cy="${p1.svgY}" r="4" fill="#f59e0b" stroke="#ffffff" stroke-width="1.5" />
                    <circle cx="${p2.svgX}" cy="${p2.svgY}" r="4" fill="#f59e0b" stroke="#ffffff" stroke-width="1.5" />
                    <rect x="${(p1.svgX + p2.svgX) / 2 - 40}" y="${(p1.svgY + p2.svgY) / 2 - 14}" width="80" height="20" rx="4" fill="#0f172a" stroke="#f59e0b" stroke-width="1"/>
                    <text x="${(p1.svgX + p2.svgX) / 2}" y="${(p1.svgY + p2.svgY) / 2}" fill="#f59e0b" font-family="monospace" font-size="11" font-weight="bold" text-anchor="middle" dominant-baseline="middle">
                        ${dist.toFixed(2)}m
                    </text>
                `;

                if (typeof this.options.onMeasureComplete === 'function') {
                    this.options.onMeasureComplete({
                        distance_m: dist.toFixed(3),
                        distance_mm: Math.round(dist * 1000),
                        deltaX_m: Math.abs(dx).toFixed(3),
                        deltaY_m: Math.abs(dy).toFixed(3)
                    });
                }
            }
        }

        zoomIn() {
            this.viewState.scale = Math.min(6.0, this.viewState.scale * 1.25);
            this.updateTransform();
        }

        zoomOut() {
            this.viewState.scale = Math.max(0.15, this.viewState.scale * 0.8);
            this.updateTransform();
        }

        resetViewport() {
            const rect = this.svg.getBoundingClientRect();
            const w = this.buildingSpecs.width * this.svgScale;
            const d = this.buildingSpecs.depth * this.svgScale;

            const scaleX = (rect.width - 120) / w;
            const scaleY = (rect.height - 120) / d;
            this.viewState.scale = Math.min(scaleX, scaleY, 0.75);

            this.viewState.panX = (rect.width - w * this.viewState.scale) / 2;
            this.viewState.panY = (rect.height - d * this.viewState.scale) / 2;
            this.updateTransform();
        }

        setTheme(themeName) {
            this.theme = themeName;
            this.container.className = `cad-viewport-root cad-theme-${themeName} relative w-full h-full overflow-hidden select-none`;
        }

        setLayerVisible(layerId, isVisible) {
            if (this.layers[layerId]) {
                this.layers[layerId].visible = isVisible;
                if (this.layerGroups[layerId]) {
                    this.layerGroups[layerId].style.display = isVisible ? 'block' : 'none';
                    if (layerId === 'A-DOOR' && isVisible && this.layerGroups['A-DOOR'].children.length === 0) {
                        this.renderDoors(this.floorsData[this.currentFloor]);
                    }
                }
            }
        }
    }

    window.NpcCadEngine = NpcCadEngine;
    console.log('📐 NpcCadEngine loaded with authentic DPWH Blueprint reconstruction & Reference Overlay.');

})(window);
