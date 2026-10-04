<?php

/**
 * campus_map.php — NPC Architectural CAD Blueprint Workstation
 * Navotas Polytechnic College (NPC) Multi-Purpose Academic Building
 * 
 * Features:
 *  - 2D AutoCAD Vector Blueprint: Vector floor plans, DPWH column grids (A–J, 1–10), double walls, room tags & dimensions
 *  - Interactive CAD Tools: Precision crosshair, tape measure (DIST), layer visibility manager, zoom extents
 *  - Live Room Availability & Hotel-Style Room Inspector Card (Section, Course, Prof, Live Attendance)
 */

require_once __DIR__ . '/includes/auth.php';

// Direct browser access redirection to the user's dedicated portal page (only if requested at /campus_map.php root)
$reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($reqUri === '/campus_map.php' || $reqUri === '/campus_map') {
    require_login();
    $activeRole = $_SESSION['role'] ?? 'student';
    $baseRole = $_SESSION['base_role'] ?? $activeRole;

    if (!in_array($baseRole, ['admin', 'registrar'])) {
        if (in_array($baseRole, ['teacher', 'faculty'])) {
            header("Location: /teacher/campus_map.php");
            exit();
        } else {
            header("Location: /student/campus_map.php");
            exit();
        }
    }

    if ($activeRole === 'admin' || $activeRole === 'registrar') {
        header("Location: /admin/campus_map.php");
        exit();
    } elseif ($activeRole === 'teacher' || $activeRole === 'faculty') {
        header("Location: /teacher/campus_map.php");
        exit();
    } else {
        header("Location: /student/campus_map.php");
        exit();
    }
}

$userRole = strtolower($_SESSION['role'] ?? $_SESSION['base_role'] ?? 'guest');
$userName = $_SESSION['name'] ?? 'NPC Member';
$userEmail = strtolower(trim($_SESSION['email'] ?? ''));
$isFacultyOrAdmin = in_array($userRole, ['teacher', 'faculty', 'admin', 'registrar']);
$NPC_PORTAL = isset($NPC_PORTAL) ? ($NPC_PORTAL === 'teacher' ? 'faculty' : $NPC_PORTAL) : (in_array($userRole, ['admin', 'faculty', 'teacher']) ? ($userRole === 'admin' ? 'admin' : 'faculty') : 'student');

$PAGE_TITLE = isset($PAGE_TITLE) ? $PAGE_TITLE : 'NPC Map · NPC LMS';
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <?php include __DIR__ . '/includes/_head.php'; ?>
    <style>
        /* CAD Theme & Viewport Core Styles */
        .cad-glass {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .light .cad-glass {
            background: rgba(255, 255, 255, 0.94);
        }

        /* Suppress global search button and portal switch in campus map */
        #npc-search-hint,
        #npc-portal-switch {
            display: none !important;
        }

        /* Fullscreen Theater / Lakihaan Canvas Mode */
        body.campus-fullscreen #npc-sidebar {
            display: none !important;
        }

        body.campus-fullscreen #topbar {
            display: none !important;
        }

        body.campus-fullscreen #main-wrapper {
            padding-left: 0 !important;
            height: 100vh !important;
            width: 100vw !important;
            position: fixed !important;
            inset: 0 !important;
            z-index: 100 !important;
        }

        body.campus-fullscreen .cad-room-tag text {
            font-size: 130% !important;
        }

        /* 1. AutoCAD Classic Dark */
        .cad-theme-autocad-dark {
            background-color: #0b0f17;
            color: #e6edf3;
        }

        .cad-theme-autocad-dark .cad-grid-minor {
            stroke: rgba(255, 255, 255, 0.035);
        }

        .cad-theme-autocad-dark .cad-grid-major {
            stroke: rgba(255, 255, 255, 0.08);
        }

        .cad-theme-autocad-dark .cad-grid-centerline {
            stroke: #f43f5e;
            stroke-opacity: 0.5;
        }

        .cad-theme-autocad-dark .cad-grid-bubble-circle {
            fill: #161b22;
            stroke: #f43f5e;
            stroke-width: 1.5;
        }

        .cad-theme-autocad-dark .cad-grid-bubble-text {
            fill: #ffffff;
        }

        .cad-theme-autocad-dark .cad-exterior-wall-outer {
            stroke: #00e5ff;
            stroke-width: 4.2px;
            fill: rgba(0, 229, 255, 0.04);
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-exterior-wall-inner {
            stroke: #00e5ff;
            stroke-width: 2.2px;
            fill: none;
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-partition-wall {
            stroke: #00e5ff;
            stroke-width: 3.2px;
            stroke-linecap: square;
            stroke-linejoin: miter;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-toilet-cubicle {
            stroke: #38bdf8;
            stroke-width: 1.4px;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-balustrade {
            stroke: #fb923c;
            stroke-width: 2.0px;
            stroke-dasharray: 6, 3;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-glass-wall {
            stroke: #38bdf8;
            stroke-width: 4.2px;
            stroke-linecap: round;
            fill: none;
            filter: drop-shadow(0 0 6px rgba(56, 189, 248, 0.9));
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-room-floor-surface {
            fill: rgba(17, 24, 39, 0.7);
            stroke: none;
            transition: fill 0.2s ease;
        }

        .cad-theme-autocad-dark .cad-room-interactive-border {
            stroke: transparent;
            stroke-width: 3.5px;
            fill: none;
            transition: stroke 0.15s ease, stroke-width 0.15s ease;
            pointer-events: none;
        }

        .cad-theme-autocad-dark .cad-room-interactive:hover .cad-room-floor-surface {
            fill: rgba(14, 165, 233, 0.22);
        }

        .cad-theme-autocad-dark .cad-room-interactive:hover .cad-room-interactive-border {
            stroke: #38bdf8 !important;
            stroke-width: 3.5px !important;
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-selected .cad-room-floor-surface {
            fill: rgba(251, 191, 36, 0.26);
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-selected .cad-room-interactive-border {
            stroke: #f59e0b !important;
            stroke-width: 4.5px !important;
            filter: drop-shadow(0 0 6px rgba(245, 158, 11, 0.85));
        }

        /* Occupied / In-Use Room Styling (Classic Dark) */
        .cad-theme-autocad-dark .cad-room-interactive.is-occupied .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.28) !important;
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied .cad-room-interactive-border {
            stroke: #ef4444 !important;
            stroke-width: 3.5px !important;
            filter: drop-shadow(0 0 6px rgba(239, 68, 68, 0.7));
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied .cad-tag-code {
            fill: #fca5a5 !important;
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied:hover .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.44) !important;
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied:hover .cad-room-interactive-border {
            stroke: #f87171 !important;
            stroke-width: 4.5px !important;
            filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.95));
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied.is-selected .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.48) !important;
        }

        .cad-theme-autocad-dark .cad-room-interactive.is-occupied.is-selected .cad-room-interactive-border {
            stroke: #f59e0b !important;
            stroke-width: 4.5px !important;
            filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.95)) drop-shadow(0 0 4px rgba(239, 68, 68, 0.8));
        }

        .cad-theme-autocad-dark .cad-door-leaf {
            stroke: #facc15;
        }

        .cad-theme-autocad-dark .cad-door-swing-arc {
            stroke: #facc15;
        }

        .cad-theme-autocad-dark .cad-glazing-frame {
            stroke: #38bdf8;
            fill: rgba(56, 189, 248, 0.15);
        }

        .cad-theme-autocad-dark .cad-glazing-pane {
            stroke: #bae6fd;
        }

        .cad-theme-autocad-dark .cad-column-pillar {
            fill: #ffffff;
            stroke: #00e5ff;
        }

        .cad-theme-autocad-dark .cad-col-cross {
            stroke: #00e5ff;
            stroke-width: 0.8;
        }

        .cad-theme-autocad-dark .cad-dim-line,
        .cad-theme-autocad-dark .cad-dim-extension {
            stroke: #4ade80;
        }

        .cad-theme-autocad-dark .cad-dim-text {
            fill: #4ade80;
        }

        .cad-theme-autocad-dark .cad-tag-code {
            fill: #ffffff;
        }

        .cad-theme-autocad-dark .cad-tag-name {
            fill: #94a3b8;
        }

        .cad-theme-autocad-dark .cad-tag-area {
            fill: #38bdf8;
        }

        .cad-theme-autocad-dark .cad-furniture-bench,
        .cad-theme-autocad-dark .cad-furniture-desk,
        .cad-theme-autocad-dark .cad-furniture-shelf {
            stroke: #64748b;
            fill: none;
        }

        .cad-theme-autocad-dark .cad-court-perimeter,
        .cad-theme-autocad-dark .cad-court-centerline,
        .cad-theme-autocad-dark .cad-court-circle {
            stroke: #f97316;
        }

        /* 2. DPWH Blueprint Navy */
        .cad-theme-blueprint {
            background-color: #081a36;
            color: #f0f9ff;
        }

        .cad-theme-blueprint .cad-grid-minor {
            stroke: rgba(56, 189, 248, 0.07);
        }

        .cad-theme-blueprint .cad-grid-major {
            stroke: rgba(56, 189, 248, 0.16);
        }

        .cad-theme-blueprint .cad-grid-centerline {
            stroke: rgba(255, 255, 255, 0.4);
        }

        .cad-theme-blueprint .cad-grid-bubble-circle {
            fill: #0b254d;
            stroke: #38bdf8;
            stroke-width: 1.5;
        }

        .cad-theme-blueprint .cad-grid-bubble-text {
            fill: #ffffff;
        }

        .cad-theme-blueprint .cad-exterior-wall-outer {
            stroke: #ffffff;
            stroke-width: 4.2px;
            fill: rgba(56, 189, 248, 0.08);
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-exterior-wall-inner {
            stroke: #38bdf8;
            stroke-width: 2.2px;
            fill: none;
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-partition-wall {
            stroke: #ffffff;
            stroke-width: 3.2px;
            stroke-linecap: square;
            stroke-linejoin: miter;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-toilet-cubicle {
            stroke: #7dd3fc;
            stroke-width: 1.4px;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-balustrade {
            stroke: #facc15;
            stroke-width: 2.0px;
            stroke-dasharray: 6, 3;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-glass-wall {
            stroke: #7dd3fc;
            stroke-width: 4.2px;
            stroke-linecap: round;
            fill: none;
            filter: drop-shadow(0 0 6px rgba(125, 211, 252, 0.9));
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-room-floor-surface {
            fill: rgba(11, 37, 77, 0.5);
            stroke: none;
            transition: fill 0.2s ease;
        }

        .cad-theme-blueprint .cad-room-interactive-border {
            stroke: transparent;
            stroke-width: 3.5px;
            fill: none;
            transition: stroke 0.15s ease, stroke-width 0.15s ease;
            pointer-events: none;
        }

        .cad-theme-blueprint .cad-room-interactive:hover .cad-room-floor-surface {
            fill: rgba(56, 189, 248, 0.25);
        }

        .cad-theme-blueprint .cad-room-interactive:hover .cad-room-interactive-border {
            stroke: #bae6fd !important;
            stroke-width: 3.5px !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-selected .cad-room-floor-surface {
            fill: rgba(245, 158, 11, 0.35);
        }

        .cad-theme-blueprint .cad-room-interactive.is-selected .cad-room-interactive-border {
            stroke: #f59e0b !important;
            stroke-width: 4.5px !important;
            filter: drop-shadow(0 0 6px rgba(245, 158, 11, 0.85));
        }

        /* Occupied / In-Use Room Styling (Blueprint) */
        .cad-theme-blueprint .cad-room-interactive.is-occupied .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.3) !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied .cad-room-interactive-border {
            stroke: #ef4444 !important;
            stroke-width: 3.5px !important;
            filter: drop-shadow(0 0 6px rgba(239, 68, 68, 0.7));
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied .cad-tag-code {
            fill: #fca5a5 !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied:hover .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.45) !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied:hover .cad-room-interactive-border {
            stroke: #f87171 !important;
            stroke-width: 4.5px !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied.is-selected .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.5) !important;
        }

        .cad-theme-blueprint .cad-room-interactive.is-occupied.is-selected .cad-room-interactive-border {
            stroke: #f59e0b !important;
            stroke-width: 4.5px !important;
        }

        .cad-theme-blueprint .cad-door-leaf {
            stroke: #38bdf8;
        }

        .cad-theme-blueprint .cad-door-swing-arc {
            stroke: #38bdf8;
        }

        .cad-theme-blueprint .cad-glazing-frame {
            stroke: #7dd3fc;
            fill: rgba(125, 211, 252, 0.2);
        }

        .cad-theme-blueprint .cad-glazing-pane {
            stroke: #ffffff;
        }

        .cad-theme-blueprint .cad-column-pillar {
            fill: #ffffff;
            stroke: #0284c7;
        }

        .cad-theme-blueprint .cad-col-cross {
            stroke: #0284c7;
            stroke-width: 0.8;
        }

        .cad-theme-blueprint .cad-dim-line,
        .cad-theme-blueprint .cad-dim-extension {
            stroke: #bae6fd;
        }

        .cad-theme-blueprint .cad-dim-text {
            fill: #ffffff;
        }

        .cad-theme-blueprint .cad-tag-code {
            fill: #ffffff;
        }

        .cad-theme-blueprint .cad-tag-name {
            fill: #bae6fd;
        }

        .cad-theme-blueprint .cad-tag-area {
            fill: #7dd3fc;
        }

        .cad-theme-blueprint .cad-furniture-bench,
        .cad-theme-blueprint .cad-furniture-desk,
        .cad-theme-blueprint .cad-furniture-shelf {
            stroke: #38bdf8;
            fill: none;
        }

        .cad-theme-blueprint .cad-court-perimeter,
        .cad-theme-blueprint .cad-court-centerline,
        .cad-theme-blueprint .cad-court-circle {
            stroke: #facc15;
        }

        /* 3. Architectural Drafting White */
        .cad-theme-white {
            background-color: #f8fafc;
            color: #0f172a;
        }

        .cad-theme-white .cad-grid-minor {
            stroke: rgba(15, 23, 42, 0.05);
        }

        .cad-theme-white .cad-grid-major {
            stroke: rgba(15, 23, 42, 0.1);
        }

        .cad-theme-white .cad-grid-centerline {
            stroke: #dc2626;
            stroke-opacity: 0.55;
        }

        .cad-theme-white .cad-grid-bubble-circle {
            fill: #ffffff;
            stroke: #dc2626;
            stroke-width: 1.5;
        }

        .cad-theme-white .cad-grid-bubble-text {
            fill: #0f172a;
        }

        .cad-theme-white .cad-exterior-wall-outer {
            stroke: #0f172a;
            stroke-width: 4.2px;
            fill: rgba(15, 23, 42, 0.04);
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-white .cad-exterior-wall-inner {
            stroke: #0f172a;
            stroke-width: 2.2px;
            fill: none;
            stroke-linecap: square;
            stroke-linejoin: miter;
            pointer-events: none;
        }

        .cad-theme-white .cad-partition-wall {
            stroke: #0f172a;
            stroke-width: 3.2px;
            stroke-linecap: square;
            stroke-linejoin: miter;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-white .cad-toilet-cubicle {
            stroke: #475569;
            stroke-width: 1.4px;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-white .cad-balustrade {
            stroke: #ea580c;
            stroke-width: 2.0px;
            stroke-dasharray: 6, 3;
            fill: none;
            pointer-events: none;
        }

        .cad-theme-white .cad-glass-wall {
            stroke: #0284c7;
            stroke-width: 4.2px;
            stroke-linecap: round;
            fill: none;
            filter: drop-shadow(0 0 4px rgba(2, 132, 199, 0.5));
            pointer-events: none;
        }

        .cad-theme-white .cad-room-floor-surface {
            fill: rgba(241, 245, 249, 0.85);
            stroke: none;
            transition: fill 0.2s ease;
        }

        .cad-theme-white .cad-room-interactive-border {
            stroke: transparent;
            stroke-width: 3.5px;
            fill: none;
            transition: stroke 0.15s ease, stroke-width 0.15s ease;
            pointer-events: none;
        }

        .cad-theme-white .cad-room-interactive:hover .cad-room-floor-surface {
            fill: rgba(2, 132, 199, 0.14);
        }

        .cad-theme-white .cad-room-interactive:hover .cad-room-interactive-border {
            stroke: #0284c7 !important;
            stroke-width: 3.5px !important;
        }

        .cad-theme-white .cad-room-interactive.is-selected .cad-room-floor-surface {
            fill: rgba(245, 158, 11, 0.22);
        }

        .cad-theme-white .cad-room-interactive.is-selected .cad-room-interactive-border {
            stroke: #d97706 !important;
            stroke-width: 4.5px !important;
            filter: drop-shadow(0 0 6px rgba(217, 119, 6, 0.85));
        }

        /* Occupied / In-Use Room Styling (Drafting White) */
        .cad-theme-white .cad-room-interactive.is-occupied .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.2) !important;
        }

        .cad-theme-white .cad-room-interactive.is-occupied .cad-room-interactive-border {
            stroke: #dc2626 !important;
            stroke-width: 3.5px !important;
            filter: drop-shadow(0 0 4px rgba(220, 38, 38, 0.45));
        }

        .cad-theme-white .cad-room-interactive.is-occupied .cad-tag-code {
            fill: #b91c1c !important;
        }

        .cad-theme-white .cad-room-interactive.is-occupied:hover .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.32) !important;
        }

        .cad-theme-white .cad-room-interactive.is-occupied:hover .cad-room-interactive-border {
            stroke: #b91c1c !important;
            stroke-width: 4.5px !important;
        }

        .cad-theme-white .cad-room-interactive.is-occupied.is-selected .cad-room-floor-surface {
            fill: rgba(239, 68, 68, 0.36) !important;
        }

        .cad-theme-white .cad-room-interactive.is-occupied.is-selected .cad-room-interactive-border {
            stroke: #d97706 !important;
            stroke-width: 4.5px !important;
        }

        .cad-theme-white .cad-door-leaf {
            stroke: #2563eb;
        }

        .cad-theme-white .cad-door-swing-arc {
            stroke: #2563eb;
        }

        .cad-theme-white .cad-glazing-frame {
            stroke: #0284c7;
            fill: rgba(2, 132, 199, 0.1);
        }

        .cad-theme-white .cad-glazing-pane {
            stroke: #0284c7;
        }

        .cad-theme-white .cad-column-pillar {
            fill: #0f172a;
            stroke: #0f172a;
        }

        .cad-theme-white .cad-col-cross {
            stroke: #ffffff;
            stroke-width: 0.8;
        }

        .cad-theme-white .cad-dim-line,
        .cad-theme-white .cad-dim-extension {
            stroke: #0f172a;
        }

        .cad-theme-white .cad-dim-text {
            fill: #0f172a;
        }

        .cad-theme-white .cad-tag-code {
            fill: #0f172a;
        }

        .cad-theme-white .cad-tag-name {
            fill: #475569;
        }

        .cad-theme-white .cad-tag-area {
            fill: #0284c7;
        }

        .cad-theme-white .cad-furniture-bench,
        .cad-theme-white .cad-furniture-desk,
        .cad-theme-white .cad-furniture-shelf {
            stroke: #94a3b8;
            fill: none;
        }

        .cad-theme-white .cad-court-perimeter,
        .cad-theme-white .cad-court-centerline,
        .cad-theme-white .cad-court-circle {
            stroke: #ea580c;
        }

        /* Viewport & Cursor classes */
        .cad-viewport-root {
            cursor: crosshair;
            touch-action: none;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }

        .cad-viewport-root.is-panning {
            cursor: grabbing !important;
        }

        .cad-viewport-root[data-cad-tool="pan"] {
            cursor: grab;
        }

        /* Pulsing indicator animations */
        .cad-occupied-dot {
            animation: pulse-ring-red 2s infinite;
        }

        @keyframes pulse-ring-red {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }

            100% {
                opacity: 1;
            }
        }

        /* Hotel-style room inspector drawer */
        .room-inspector {
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease, visibility 0.35s ease;
        }

        .room-inspector.closed {
            transform: translateX(calc(100% + 80px)) !important;
            opacity: 0 !important;
            pointer-events: none !important;
            visibility: hidden !important;
            display: none !important;
        }

        @media (max-width: 768px) {
            .room-inspector {
                top: auto !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-height: 84vh !important;
                border-radius: 28px 28px 0 0 !important;
                border-bottom: none !important;
                box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.45) !important;
            }

            .room-inspector.closed {
                transform: translateY(calc(100% + 80px)) !important;
            }
        }

        .pulse-badge {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased selection:bg-primary/20">
    <?php include __DIR__ . '/includes/_denied_banner.php'; ?>

    <div class="flex min-h-screen w-full" id="app-root">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/includes/_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 bg-surface lg:pl-64" id="main-wrapper">
            <!-- Topbar Navigation Header -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant px-3 sm:px-4 md:px-6 flex items-center justify-between sticky top-0 z-30 shadow-sm gap-2 sm:gap-4" id="topbar">
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <button type="button" class="lg:hidden p-2 text-on-surface-variant hover:text-primary rounded-lg shrink-0 cursor-pointer" onclick="toggleNpcSidebar()" aria-label="Open Navigation">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="flex items-center gap-2.5">
                        <img src="/assets/img/npc-logo.png" alt="NPC Seal" class="w-8 h-8 rounded-full object-contain bg-white p-0.5 border border-emerald-500/30 shrink-0 shadow-sm">
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xs md:text-sm font-bold text-on-surface leading-tight">NPC Map</h1>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 uppercase hidden sm:inline-block">Official NPC Campus</span>
                            </div>
                            <p class="text-[10px] text-on-surface-variant font-mono hidden md:block">DPWH Column Grids A–J, 1–9 · 56.0m × 60.5m</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary Counters & Find Prof Action -->
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    <div class="flex items-center gap-1.5 sm:gap-2 text-[11px] sm:text-xs font-mono">
                        <div class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium border border-emerald-500/20" title="Available / Vacant Rooms">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-badge"></span>
                            <span id="stat-available-count">-- Available</span>
                        </div>
                        <div class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-700 dark:text-rose-400 font-medium border border-rose-500/20" title="In-Use / Occupied Rooms">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span id="stat-occupied-count">-- In-Use</span>
                        </div>
                    </div>

                    <button id="btn-open-faculty" class="px-2.5 sm:px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1 sm:gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-sm">person_search</span>
                        <span class="hidden sm:inline">Find Prof</span>
                    </button>
                </div>
            </header>

            <!-- Main Workstation Area -->
            <main class="flex-1 flex flex-col min-w-0 relative overflow-hidden bg-slate-950">
                <!-- Floating Top Toolbar: Floor Switcher and Search Bar -->
                <div class="absolute top-3 left-3 right-3 z-20 flex flex-wrap items-center justify-between gap-3 pointer-events-none">

                    <!-- Left: Floor Level Switcher Pills (2F, 3F, 4F, RD) -->
                    <div class="cad-glass p-1 rounded-xl border border-outline-variant shadow-xl pointer-events-auto flex items-center gap-1 overflow-x-auto max-w-full">
                        <button class="cad-floor-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white shadow-sm transition-all flex items-center gap-1.5 cursor-pointer shrink-0" data-floor="2F">
                            <span class="font-mono font-bold text-white">2F</span>
                            <span class="hidden sm:inline text-[11px]">Academic & Labs</span>
                        </button>
                        <button class="cad-floor-btn px-3 py-1.5 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-all flex items-center gap-1.5 cursor-pointer shrink-0" data-floor="3F">
                            <span class="font-mono font-bold text-emerald-400">3F</span>
                            <span class="hidden sm:inline text-[11px]">Library & Sciences</span>
                        </button>
                        <button class="cad-floor-btn px-3 py-1.5 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-all flex items-center gap-1.5 cursor-pointer shrink-0" data-floor="4F">
                            <span class="font-mono font-bold text-emerald-400">4F</span>
                            <span class="hidden sm:inline text-[11px]">Gymnasium Arena</span>
                        </button>
                        <button class="cad-floor-btn px-3 py-1.5 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-all flex items-center gap-1.5 cursor-pointer shrink-0" data-floor="RD">
                            <span class="font-mono font-bold text-emerald-400">RD</span>
                            <span class="hidden sm:inline text-[11px]">Roof Deck</span>
                        </button>
                    </div>

                    <!-- Right: Search Bar with Live Auto-Suggest & Mobile Find Prof -->
                    <div class="relative w-full sm:w-80 md:w-96 pointer-events-auto">
                        <div class="flex items-center gap-2 w-full">
                            <div class="cad-glass rounded-xl border border-outline-variant shadow-xl flex items-center px-3 py-1.5 gap-2 focus-within:ring-2 focus-within:ring-emerald-500 bg-slate-900/80 backdrop-blur-md flex-1 min-w-0">
                                <span class="material-symbols-outlined text-base text-emerald-400 shrink-0">search</span>
                                <input type="text" id="map-search-input" placeholder="Search for room (e.g. 201, Comp Lab)..." class="bg-transparent text-xs w-full outline-none text-on-surface placeholder:text-on-surface-variant/60 font-sans" autocomplete="off">
                                <button id="btn-clear-search" class="hidden text-on-surface-variant hover:text-on-surface shrink-0">
                                    <span class="material-symbols-outlined text-xs">close</span>
                                </button>
                            </div>
                            <!-- Mobile 'Find Prof' Button (Right beside Search Bar on Mobile) -->
                            <button id="btn-open-faculty-mobile" type="button" class="sm:hidden px-3 py-2 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary/90 flex items-center gap-1 shadow-lg shrink-0 cursor-pointer" title="Find Professor Room">
                                <span class="material-symbols-outlined text-sm">person_search</span>
                                <span class="whitespace-nowrap">Find Prof</span>
                            </button>
                        </div>

                        <div id="search-results-dropdown" class="hidden absolute left-0 right-0 top-full mt-1.5 cad-glass rounded-xl border border-outline-variant shadow-2xl p-2 max-h-72 overflow-y-auto z-40 space-y-1 bg-slate-900/95 backdrop-blur-md">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- ══════════════════ 2D AUTOCAD BLUEPRINT VIEWPORT CONTAINER (Full Screen Workstation) ══════════════════ -->
                <div id="cad-viewport-container" class="w-full absolute top-0 left-0 right-0 bottom-0"></div>

                <!-- ══════════════════ 3D BIM VIEWPORT CONTAINER (Hidden) ══════════════════ -->
                <div id="bim-3d-viewport-container" class="w-full absolute top-0 left-0 right-0 bottom-0 hidden"></div>

                <!-- ══════════════════ OFFICIAL DPWH ARCHITECTURAL TITLE BLOCK (Hidden per student focus) ══════════════════ -->
                <div class="hidden" id="cad-title-block">
                    <span id="tb-sheet-title">2F ARCHITECTURAL NPC MAP (CAD)</span>
                </div>

                <!-- ══════════════════ LAYER MANAGER POPUP ══════════════════ -->
                <div id="cad-layer-manager" class="hidden absolute top-16 right-4 z-30 cad-glass p-4 rounded-2xl border border-outline-variant shadow-2xl w-72 space-y-3 font-mono text-xs">
                    <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                        <div class="flex items-center gap-1.5 font-bold text-on-surface">
                            <span class="material-symbols-outlined text-sm text-cyan-400">layers</span>
                            <span>AutoCAD Layers (LAYER)</span>
                        </div>
                        <button id="btn-close-layers" class="text-on-surface-variant hover:text-on-surface">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>

                </div>

                <!-- Hover Room Tooltip -->
                <div id="room-hover-tooltip" class="hidden absolute pointer-events-none z-30 cad-glass px-3 py-2 rounded-xl border border-outline-variant shadow-xl text-xs transition-opacity duration-150">
                    <div class="font-bold text-on-surface flex items-center gap-1.5" id="tooltip-title">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Room 201</span>
                    </div>
                    <div class="text-[11px] text-on-surface-variant mt-0.5 font-mono" id="tooltip-subtitle">Available / Vacant</div>
                </div>

                <!-- ══════════════════ HOTEL-STYLE ROOM INSPECTOR CARD ══════════════════ -->
                <aside id="room-inspector" class="room-inspector closed absolute top-3 bottom-11 right-3 w-full md:w-[420px] cad-glass rounded-3xl border border-outline-variant shadow-2xl z-40 flex flex-col overflow-hidden" style="display: none;">
                    <!-- Mobile drag handle indicator -->
                    <div class="w-12 h-1 rounded-full bg-slate-500/40 mx-auto mt-2.5 mb-0.5 md:hidden"></div>

                    <!-- Card Header -->
                    <div class="p-4 border-b border-outline-variant flex items-center justify-between bg-surface-container-lowest/80">
                        <div class="flex items-center gap-2">
                            <span id="inspect-floor-badge" class="px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-400 font-mono font-bold text-xs">2F</span>
                            <span id="inspect-code-badge" class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface font-mono text-xs">COMPLAB-2</span>
                        </div>
                        <button id="btn-close-inspector" type="button" class="w-9 h-9 rounded-full bg-surface-container/70 hover:bg-rose-500/25 text-on-surface hover:text-rose-400 flex items-center justify-center transition-all cursor-pointer z-50 pointer-events-auto" title="Close Panel (Esc)">
                            <span class="material-symbols-outlined text-xl pointer-events-none">close</span>
                        </button>
                    </div>

                    <!-- Scrollable Content Body -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-5">

                        <!-- Room Architectural Banner -->
                        <div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-slate-900 via-emerald-950/80 to-slate-950 text-white p-5 shadow-lg border border-emerald-500/25">
                            <div class="absolute -right-8 -bottom-8 opacity-10 text-white pointer-events-none">
                                <span class="material-symbols-outlined" style="font-size: 140px;">architecture</span>
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-2">
                                    <span id="inspect-type-tag" class="text-[10px] font-mono uppercase tracking-wider px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-sm text-white font-bold">
                                        Computer Laboratory
                                    </span>
                                    <span id="inspect-status-pill" class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500 text-white shadow-sm pulse-badge">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        <span id="inspect-status-text">Available Now</span>
                                    </span>
                                </div>
                                <h3 id="inspect-room-name" class="text-xl font-bold leading-snug">Computer Laboratory 2</h3>
                                <p id="inspect-wing-info" class="text-xs text-white/80 mt-0.5 font-mono">West Wing · DPWH Sheet A-102</p>

                                <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-white/15 text-xs">
                                    <div>
                                        <span class="text-white/60 block text-[10px] uppercase font-mono">Floor Area</span>
                                        <span id="inspect-area" class="font-bold text-sm text-emerald-300 font-mono">92.5 m²</span>
                                    </div>
                                    <div>
                                        <span class="text-white/60 block text-[10px] uppercase font-mono">Capacity</span>
                                        <span id="inspect-capacity" class="font-bold text-sm text-emerald-300 font-mono">45 Seats</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Room Activity & Announcement Notice (Shown when in-use or has announcement) -->
                        <div id="inspect-announcement-box" class="hidden p-4 rounded-2xl bg-amber-500/10 border border-amber-500/35 space-y-2.5 text-on-surface shadow-md animate-fade-in">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[17px] text-amber-400 animate-pulse">campaign</span>
                                    <span id="inspect-activity-type-label">Room Activity</span>
                                </span>
                                <span id="inspect-faculty-only-badge" class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                    Faculty &amp; Admin Only
                                </span>
                            </div>
                            <p id="inspect-announcement-text" class="text-xs font-semibold text-white leading-relaxed whitespace-pre-wrap">
                                <!-- Activity description or announcement -->
                            </p>
                            <div class="flex items-center justify-between text-[10px] font-mono text-on-surface-variant pt-2 border-t border-amber-500/20">
                                <span id="inspect-occupied-by-label" class="flex items-center gap-1 font-semibold text-amber-300/90">
                                    <span class="material-symbols-outlined text-xs text-amber-400">person</span>
                                    Occupied by Faculty
                                </span>
                                <span id="inspect-occupied-since-label" class="text-slate-400">Active</span>
                            </div>
                        </div>

                        <!-- Class Section & Course Highlight Banner -->
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-cyan-950/80 via-slate-900 to-blue-950/80 border border-cyan-500/40 space-y-2.5 shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-cyan-400 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm">groups</span>
                                    Class Section
                                </span>
                                <span id="inspect-section-badge" class="px-3 py-1 rounded-xl text-xs font-black bg-cyan-500/25 text-cyan-300 border border-cyan-400/50 font-mono shadow-sm">BSIT 2-A</span>
                            </div>
                            <div class="pt-1.5 border-t border-cyan-500/20">
                                <span class="text-[10px] font-mono uppercase text-slate-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs text-cyan-400">menu_book</span>
                                    Course & Subject Title
                                </span>
                                <h4 id="inspect-course-name" class="text-sm font-bold text-white leading-snug mt-0.5">IT102 — Computer Programming 1</h4>
                            </div>
                        </div>

                        <!-- Assigned Professor Profile Card -->
                        <div id="inspect-professor-section" class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant font-mono flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-cyan-400">person</span>
                                    Assigned Faculty
                                </span>
                                <span id="inspect-prof-status" class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 font-semibold font-mono">
                                    Assigned Instructor
                                </span>
                            </div>

                            <div class="flex items-center gap-3.5 pt-1">
                                <img id="inspect-prof-avatar" src="https://ui-avatars.com/api/?name=Prof&background=0284c7&color=fff" alt="Faculty" class="w-12 h-12 rounded-full border-2 border-cyan-500/30 shadow-sm object-cover">
                                <div class="min-w-0 flex-1">
                                    <h4 id="inspect-prof-name" class="font-bold text-sm text-on-surface truncate">Prof. Edsan Moreno</h4>
                                    <p id="inspect-prof-role" class="text-xs text-on-surface-variant truncate">College of Information Sciences</p>
                                    <p id="inspect-prof-email" class="text-[11px] font-mono text-cyan-400 truncate mt-0.5">edsan.moreno@navotaspolytechniccollege.edu.ph</p>
                                    <div class="mt-1">
                                        <span id="inspect-prof-schedule" class="text-[10px] px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400 font-mono">08:00 AM – 10:00 AM (Ongoing)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Live Student Attendance Dashboard Box -->
                        <div id="inspect-attendance-section" class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant space-y-3.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant font-mono flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-emerald-400">how_to_reg</span>
                                    Live Attendance Headcount
                                </span>
                                <span id="inspect-att-status-badge" class="flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold font-mono border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span id="inspect-att-status-text">High Attendance</span>
                                </span>
                            </div>

                            <!-- Attendance Counter Numbers & Progress Bar -->
                            <div class="space-y-2">
                                <div class="flex items-baseline justify-between">
                                    <div class="font-mono">
                                        <span id="inspect-att-present-count" class="text-3xl font-black text-emerald-400 leading-none">38</span>
                                        <span class="text-xs text-on-surface-variant font-medium ml-1" id="inspect-att-enrolled-count">/ 45 Students</span>
                                    </div>
                                    <span id="inspect-att-percentage" class="text-xs font-black text-cyan-300 font-mono px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/30">84.4% Rate</span>
                                </div>

                                <!-- Animated Progress Bar -->
                                <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden border border-outline-variant/40">
                                    <div id="inspect-att-progress-bar" class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 rounded-full transition-all duration-500 shadow-sm" style="width: 84.4%;"></div>
                                </div>

                                <!-- Attendance Breakdown Pills -->
                                <div class="grid grid-cols-3 gap-1.5 pt-1 text-[11px] font-mono text-center">
                                    <div class="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-400">
                                        <span class="block text-[9px] text-slate-400 uppercase font-sans">Present</span>
                                        <span id="inspect-att-present-pill" class="font-black text-sm">38</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-400">
                                        <span class="block text-[9px] text-slate-400 uppercase font-sans">Late</span>
                                        <span id="inspect-att-late-pill" class="font-black text-sm">2</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-rose-500/10 border border-rose-500/25 text-rose-400">
                                        <span class="block text-[9px] text-slate-400 uppercase font-sans">Absent</span>
                                        <span id="inspect-att-absent-pill" class="font-black text-sm">5</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Student Sign-In Roll Log -->
                            <div class="pt-2 border-t border-outline-variant/50 space-y-2">
                                <div class="flex items-center justify-between text-[10px] font-mono text-on-surface-variant uppercase">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs text-cyan-400">schedule</span>
                                        Recent Student Sign-Ins
                                    </span>
                                    <span class="text-cyan-400 font-semibold">RFID Swipes</span>
                                </div>
                                <div id="inspect-attendance-logs" class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Daily Schedule Timetable -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant font-mono flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-cyan-400">event_upcoming</span>
                                    Daily Room Timetable
                                </h4>
                                <span class="text-[10px] text-on-surface-variant font-mono"><?= date('l, M j, Y') ?></span>
                            </div>

                            <div id="inspect-timetable-list" class="space-y-2">
                                <!-- Populated dynamically -->
                            </div>
                        </div>

                        <!-- Amenities & Architectural Specs -->
                        <div class="space-y-2.5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant font-mono flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-cyan-400">tune</span>
                                Architectural Specs & ACU
                            </h4>

                            <div id="inspect-amenities-list" class="grid grid-cols-2 gap-2 text-xs">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="p-4 border-t border-outline-variant bg-surface-container-lowest/80 flex flex-col gap-2">
                        <!-- Navigation Row -->
                        <div class="flex items-center gap-2">
                            <button id="btn-focus-cad-room" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer" title="Focus into room in NPC Map">
                                <span class="material-symbols-outlined text-sm">center_focus_strong</span>
                                <span>Focus Map</span>
                            </button>
                            <button id="btn-copy-room-link" class="px-3.5 py-2.5 rounded-xl border border-outline-variant text-on-surface-variant hover:text-emerald-400 hover:bg-surface-container transition-all flex items-center gap-1 text-xs font-semibold cursor-pointer" title="Copy Room Direct Link">
                                <span class="material-symbols-outlined text-sm">share</span>
                                <span>Share</span>
                            </button>
                        </div>

                        <!-- Faculty & Admin "Use This Room" Action Buttons -->
                        <?php if ($isFacultyOrAdmin && $NPC_PORTAL !== 'student'): ?>
                            <div id="inspector-faculty-actions" class="pt-1 flex flex-col gap-2">
                                <!-- Button when room is available: Claim Room -->
                                <button type="button" id="btn-claim-room" onclick="openUseRoomModal()" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-95 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]">meeting_room</span>
                                    <span>Use This Room (Faculty &amp; Admin)</span>
                                </button>

                                <!-- Buttons when room is occupied by current user or admin: Release Room & Edit Announcement -->
                                <div id="inspector-occupied-actions" class="hidden flex items-center gap-2">
                                    <button type="button" id="btn-edit-announcement" onclick="openEditAnnouncementModal()" class="flex-1 py-2.5 px-3 rounded-xl border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-bold transition-all flex items-center justify-center gap-1 cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                        <span>Edit Notice</span>
                                    </button>
                                    <button type="button" id="btn-release-room" onclick="handleReleaseCurrentRoom()" class="flex-1 py-2.5 px-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-sm cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">logout</span>
                                        <span>Release Room</span>
                                    </button>
                                </div>

                                <!-- Button to Join Hybrid Virtual Room if enabled -->
                                <a id="btn-inspector-virtual-room" href="#" target="_blank" class="hidden w-full py-2.5 px-4 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm text-center cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]">videocam</span>
                                    <span>Enter Hybrid Virtual Room ↗</span>
                                </a>

                                <!-- Reset All Occupied Rooms (Admin & Faculty) -->
                                <button type="button" id="btn-reset-all-rooms" onclick="handleClearAllRooms()" class="w-full py-2 px-3 rounded-xl border border-slate-700 bg-slate-800/60 hover:bg-rose-950/40 hover:border-rose-500/50 text-slate-300 hover:text-rose-300 text-[11px] font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer mt-1" title="Reset all campus rooms to available">
                                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                                    <span>Vacate / Reset All In-Use Rooms</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Notice for students: Clean View Only without admin buttons -->
                            <div class="p-2.5 rounded-xl bg-surface-container/60 border border-outline-variant/40 text-[11px] font-mono text-center text-on-surface-variant mt-1">
                                <span>🏛️ Official NPC Campus Map · View Only</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- ══════════════════ MODAL: USE THIS ROOM (FACULTY & ADMIN ONLY) ══════════════════ -->
                <div id="use-room-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-3xl p-6 max-w-lg w-full shadow-2xl relative">
                        <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-primary text-on-primary flex items-center justify-center shadow-sm">
                                    <span class="material-symbols-outlined text-[22px]">meeting_room</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-primary">Use Room (Faculty &amp; Admin)</h3>
                                    <p class="text-[11px] text-on-surface-variant font-mono">Official Campus Room Occupancy System</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeUseRoomModal()" class="p-1 rounded-xl hover:bg-surface-container text-on-surface-variant cursor-pointer">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form id="use-room-form" class="space-y-4" onsubmit="handleUseRoomSubmit(event)">
                            <input type="hidden" id="use-room-id">
                            <input type="hidden" id="use-room-code">
                            <input type="hidden" id="use-room-name">
                            <input type="hidden" id="use-room-floor">

                            <!-- Target Room Details Badge -->
                            <div class="p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-cyan-400" id="use-modal-code-badge">RM-201</span>
                                        <span class="px-2 py-0.5 rounded-md bg-cyan-500/15 text-cyan-300 font-mono text-[10px] font-bold" id="use-modal-floor-badge">2nd Floor</span>
                                    </div>
                                    <p class="text-xs font-semibold text-on-surface mt-1" id="use-modal-room-name">Classroom 1 (Room 201)</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Available Now
                                    </span>
                                </div>
                            </div>

                            <!-- Occupying User Identity -->
                            <div class="p-3 rounded-2xl bg-surface-container/60 border border-outline-variant/40 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-cyan-400">badge</span>
                                    <span class="text-on-surface-variant">Occupying Host:</span>
                                    <span class="font-bold text-on-surface" id="use-modal-host-name"><?= htmlspecialchars($userName) ?></span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary font-mono text-[10px] font-bold uppercase">
                                    <?= htmlspecialchars($userRole) ?>
                                </span>
                            </div>

                            <!-- Activity / Session Type -->
                            <div>
                                <label class="block text-xs font-bold text-primary mb-1">Session / Activity Type <span class="text-error">*</span></label>
                                <select id="use-activity-type" class="w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface font-semibold focus:ring-2 focus:ring-primary focus:outline-none">
                                    <option value="Faculty Class / Lecture">Faculty Class / Lecture Session</option>
                                    <option value="Department Consultation">Department Consultation &amp; Advising</option>
                                    <option value="Academic Council / Faculty Meeting">Academic Council / Faculty Meeting</option>
                                    <option value="Examination / Project Defense">Examination / Capstone Project Defense</option>
                                    <option value="Campus Organization / Rehearsal">Campus Organization / Rehearsal</option>
                                    <option value="General Administrative Work">General Administrative Work</option>
                                </select>
                            </div>

                            <!-- Announcement / Activity Description (Optional) -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-bold text-primary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs text-amber-400">campaign</span>
                                        Announcement or Description <span class="text-on-surface-variant font-normal font-mono text-[10px]">(Optional)</span>
                                    </label>
                                    <span class="text-[10px] text-on-surface-variant font-mono">Visible to all campus users</span>
                                </div>
                                <textarea id="use-announcement" rows="3" class="w-full px-3 py-2.5 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Describe the room activity (e.g. BSIT 2-A Consultation / Faculty Meeting)... visible to all students and staff."></textarea>
                            </div>

                            <!-- Restriction Notice -->
                            <div class="p-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-400 text-lg shrink-0 mt-0.5">lock_clock</span>
                                <div class="text-[11px] text-on-surface-variant leading-relaxed">
                                    <span class="font-bold text-amber-300 block">Faculty and Admin Only Room Policy</span>
                                    Occupying this room will be recorded in the campus audit log. The room status will turn red (In-Use) across the 2D NPC Map and 3D BIM.
                                </div>
                            </div>

                            <!-- Optional PlugNmeet Virtual Hybrid Meeting -->
                            <label class="flex items-center gap-2.5 p-3 rounded-2xl hover:bg-surface-container cursor-pointer transition-colors border border-outline-variant/50">
                                <input type="checkbox" id="use-enable-virtual" class="w-4 h-4 rounded text-cyan-500 focus:ring-cyan-500 border-outline-variant cursor-pointer">
                                <span class="text-xs text-on-surface">
                                    <span class="font-bold">Enable PlugNmeet Hybrid Meeting</span>
                                    <span class="text-[11px] text-on-surface-variant block">Create an official video conference link for remote attendees</span>
                                </span>
                            </label>

                            <!-- Modal Actions -->
                            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/60">
                                <button type="button" onclick="closeUseRoomModal()" class="px-4 py-2 rounded-xl border border-outline-variant hover:bg-surface text-on-surface text-xs font-semibold cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit" id="btn-submit-use-room" class="px-5 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    <span>Confirm &amp; Use Room</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ══════════════════ FACULTY DIRECTORY MODAL ══════════════════ -->
                <div id="faculty-drawer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
                    <div class="cad-glass rounded-3xl border border-outline-variant shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden">
                        <div class="p-4 border-b border-outline-variant flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-cyan-400">badge</span>
                                <h3 class="font-bold text-sm text-on-surface">NPC Faculty Room Directory</h3>
                            </div>
                            <button id="btn-close-faculty" class="text-on-surface-variant hover:text-on-surface">
                                <span class="material-symbols-outlined text-lg">close</span>
                            </button>
                        </div>

                        <div class="p-3 border-b border-outline-variant">
                            <input type="text" id="faculty-search-input" placeholder="Search professor name or department..." class="w-full px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant text-xs outline-none focus:border-cyan-500">
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-2" id="faculty-list-container">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Three.js Core & OrbitControls for 3D BIM Extrusion -->
    <script src="/assets/js/three.min.js"></script>
    <script src="/assets/js/OrbitControls.js"></script>
    <script src="/assets/js/GLTFLoader.js"></script>

    <!-- Master 2D Architectural Floorplans Data (DPWH Calibration) -->
    <script src="/assets/js/npc-floorplans-data.js?v=<?= time() ?>"></script>

    <!-- 2D Master CAD Map Engine & Reference Overlay -->
    <script src="/assets/js/npc-campus-cad.js?v=<?= time() ?>"></script>

    <!-- 3D Procedural BIM Extrusion Engine (Generated from 2D Master) -->
    <script src="/assets/js/npc-campus-3d.js?v=<?= time() ?>"></script>
    <!-- Full Blender-authored building model: room-aware, floor-sliceable, and walkthrough-ready. -->
    <script src="/assets/js/npc-blender-3d.js"></script>

    <!-- Page Controller Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isInitialDark = document.documentElement.classList.contains('dark') ||
                localStorage.getItem('npc-theme') === 'dark' ||
                (!localStorage.getItem('npc-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);

            // NPC Authenticated User Context for Role-Based Controls
            window.NPC_USER_CONTEXT = {
                role: <?= json_encode($userRole) ?>,
                name: <?= json_encode($userName) ?>,
                email: <?= json_encode($userEmail) ?>,
                isFacultyOrAdmin: <?= $isFacultyOrAdmin ? 'true' : 'false' ?>
            };

            const state = {
                currentFloor: '2F',
                viewMode: '2d', // '2d' | '3d'
                campusData: null,
                roomStatuses: {},
                occupancies: {}, // Map of roomId/roomCode -> live database occupancy
                selectedRoom: null,
                cadEngine: null,
                campus3D: null,
                currentTheme: isInitialDark ? 'autocad-dark' : 'white',
                activeTool: 'select'
            };

            // DOM References
            const cadContainer = document.getElementById('cad-viewport-container');
            const bim3dContainer = document.getElementById('bim-3d-viewport-container');
            const btnMode2d = document.getElementById('btn-mode-2d');
            const btnMode3d = document.getElementById('btn-mode-3d');
            const btnToggleOverlay = document.getElementById('btn-toggle-overlay');
            const refStatusBadge = document.getElementById('ref-status-badge');
            const sliderRefOpacity = document.getElementById('slider-ref-opacity');
            const refOpacityVal = document.getElementById('ref-opacity-val');
            const tb2dTools = document.getElementById('toolbar-2d-tools');
            const tbRefOverlay = document.getElementById('toolbar-ref-overlay');
            const inspector = document.getElementById('room-inspector');
            const hoverTooltip = document.getElementById('room-hover-tooltip');
            const searchInput = document.getElementById('map-search-input');
            const searchDropdown = document.getElementById('search-results-dropdown');
            const btnClearSearch = document.getElementById('btn-clear-search');
            const tbSheetTitle = document.getElementById('tb-sheet-title');
            const facultyDrawer = document.getElementById('faculty-drawer');
            const footerNavHint = document.getElementById('footer-nav-hint');
            const footerCoords = document.getElementById('footer-cad-coords');
            const layerManager = document.getElementById('cad-layer-manager');
            const layerItemsList = document.getElementById('cad-layer-items-list');

            window._campusState = state;

            // ══════════════════ MODAL & OCCUPANCY ACTIONS (FACULTY & ADMIN ONLY) ══════════════════
            window.openUseRoomModal = function() {
                const room = state.selectedRoom;
                if (!room) {
                    if (typeof window.notify === 'function') {
                        window.notify('Please select a room from the blueprint before occupying.', 'warning');
                    } else {
                        alert('Please select a room from the blueprint before occupying.');
                    }
                    return;
                }

                if (!window.NPC_USER_CONTEXT?.isFacultyOrAdmin) {
                    if (typeof window.notify === 'function') {
                        window.notify('Room occupancy is restricted to Faculty and Administrators.', 'error');
                    } else {
                        alert('Room occupancy is restricted to Faculty and Administrators.');
                    }
                    return;
                }

                document.getElementById('use-room-id').value = room.id;
                document.getElementById('use-room-code').value = room.code || room.id;
                document.getElementById('use-room-name').value = room.name || room.code || room.id;
                document.getElementById('use-room-floor').value = room.floor || state.currentFloor || '2F';

                document.getElementById('use-modal-code-badge').textContent = room.code || room.id;
                document.getElementById('use-modal-floor-badge').textContent = (room.floor || state.currentFloor) + ' Floor';
                document.getElementById('use-modal-room-name').textContent = room.name || room.code || room.id;
                document.getElementById('use-modal-host-name').textContent = window.NPC_USER_CONTEXT.name || 'Faculty Member';

                document.getElementById('use-announcement').value = '';
                const vCb = document.getElementById('use-enable-virtual');
                if (vCb) vCb.checked = false;

                const modal = document.getElementById('use-room-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            };

            window.closeUseRoomModal = function() {
                const modal = document.getElementById('use-room-modal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            };

            window.handleUseRoomSubmit = async function(e) {
                if (e) e.preventDefault();
                const btn = document.getElementById('btn-submit-use-room');
                const origHtml = btn ? btn.innerHTML : '';

                try {
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Confirming...';
                    }

                    const payload = {
                        action: 'use_room',
                        room_id: document.getElementById('use-room-id').value,
                        room_code: document.getElementById('use-room-code').value,
                        room_name: document.getElementById('use-room-name').value,
                        floor: document.getElementById('use-room-floor').value,
                        activity_type: document.getElementById('use-activity-type').value,
                        announcement: document.getElementById('use-announcement').value.trim(),
                        enable_virtual: document.getElementById('use-enable-virtual')?.checked || false
                    };

                    const resp = await fetch('/api/campus_map.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const res = await resp.json();

                    if (res.success) {
                        if (typeof window.notify === 'function') {
                            window.notify(res.message || 'Room is now in-use!', 'success', 4000);
                        }
                        window.closeUseRoomModal();
                        if (typeof window.pollRealtimeOccupancy === 'function') {
                            await window.pollRealtimeOccupancy(true);
                        }
                    } else {
                        if (typeof window.notify === 'function') {
                            window.notify(res.error || 'Failed to use room.', 'error', 5000);
                        } else {
                            alert(res.error || 'Failed to use room.');
                        }
                    }
                } catch (err) {
                    console.error('Use room submit error:', err);
                    if (typeof window.notify === 'function') {
                        window.notify('Connection error while updating room.', 'error');
                    }
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    }
                }
            };

            window.handleReleaseCurrentRoom = async function() {
                const room = state.selectedRoom;
                if (!room) return;

                const roomLabel = room.code || room.name || room.id;
                if (!confirm(`Are you sure you want to release ${roomLabel}? The room will immediately become available for campus use.`)) {
                    return;
                }

                try {
                    const resp = await fetch('/api/campus_map.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'release_room',
                            room_id: room.id,
                            room_code: room.code
                        })
                    });
                    const res = await resp.json();

                    if (res.success) {
                        if (typeof window.notify === 'function') {
                            window.notify(res.message || `Room ${roomLabel} is now released.`, 'success', 4000);
                        }
                        if (state.roomStatuses) {
                            delete state.roomStatuses[room.id];
                            if (room.code) delete state.roomStatuses[room.code];
                        }
                        if (state.occupancies) {
                            delete state.occupancies[room.id];
                            if (room.code) delete state.occupancies[room.code];
                        }
                        room.status = 'available';
                        room.occupancy = null;
                        if (typeof window.updateInspectorLiveComponents === 'function') {
                            window.updateInspectorLiveComponents(room);
                        }
                        if (typeof window.pollRealtimeOccupancy === 'function') {
                            await window.pollRealtimeOccupancy(true);
                        }
                    } else {
                        if (typeof window.notify === 'function') {
                            window.notify(res.error || 'Failed to release room.', 'error');
                        } else {
                            alert(res.error || 'Failed to release room.');
                        }
                    }
                } catch (err) {
                    console.error('Release room error:', err);
                }
            };

            window.handleClearAllRooms = async function() {
                if (!confirm('Are you sure you want to release and reset ALL campus rooms? All rooms will immediately be set to Available.')) {
                    return;
                }
                try {
                    const resp = await fetch('/api/campus_map.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'clear_all_rooms' })
                    });
                    const res = await resp.json();
                    if (res.success) {
                        if (typeof window.notify === 'function') {
                            window.notify(res.message || 'All rooms have been released and are now available!', 'success', 4000);
                        }
                        state.roomStatuses = {};
                        state.occupancies = {};
                        if (state.campusData && Array.isArray(state.campusData)) {
                            state.campusData.forEach(r => {
                                if (r.status === 'occupied') r.status = 'available';
                                r.occupancy = null;
                            });
                        }
                        if (state.selectedRoom) {
                            state.selectedRoom.status = 'available';
                            state.selectedRoom.occupancy = null;
                            if (typeof window.updateInspectorLiveComponents === 'function') {
                                window.updateInspectorLiveComponents(state.selectedRoom);
                            }
                        }
                        if (typeof window.pollRealtimeOccupancy === 'function') {
                            await window.pollRealtimeOccupancy(true);
                        }
                    } else {
                        alert(res.error || 'Failed to reset rooms.');
                    }
                } catch (err) {
                    console.error('Clear all rooms error:', err);
                }
            };

            window.openEditAnnouncementModal = async function() {
                const room = state.selectedRoom;
                if (!room) return;

                const occ = state.occupancies?.[room.id] || (room.code && state.occupancies?.[room.code]);
                const currentAnn = occ?.announcement || '';
                const newAnn = prompt(`Update Optional Announcement / Description for ${room.code || room.name}:\n(Visible to all campus students & faculty)`, currentAnn);
                if (newAnn === null) return;

                try {
                    const resp = await fetch('/api/campus_map.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_announcement',
                            room_id: room.id,
                            announcement: newAnn.trim(),
                            activity_type: occ?.activity_type || ''
                        })
                    });
                    const res = await resp.json();
                    if (res.success) {
                        if (typeof window.notify === 'function') {
                            window.notify('Room notice updated successfully!', 'success');
                        }
                        if (typeof window.pollRealtimeOccupancy === 'function') {
                            await window.pollRealtimeOccupancy(true);
                        }
                    } else {
                        alert(res.error || 'Failed to update announcement.');
                    }
                } catch (err) {
                    console.error('Update notice error:', err);
                }
            };

            // Dynamic Room Inspector Live Components Renderer
            window.updateInspectorLiveComponents = function(fullRoom) {
                if (!fullRoom) return;

                const occ = state.occupancies?.[fullRoom.id] || (fullRoom.code && state.occupancies?.[fullRoom.code]) || fullRoom.occupancy;
                const isOccupied = (state.roomStatuses?.[fullRoom.id] === 'occupied' ||
                    (fullRoom.code && state.roomStatuses?.[fullRoom.code] === 'occupied') ||
                    !!occ ||
                    fullRoom.status === 'occupied');

                // 1. Status Pill & Text
                const statusPill = document.getElementById('inspect-status-pill');
                const statusText = document.getElementById('inspect-status-text');
                if (statusText) {
                    statusText.textContent = isOccupied ? (occ?.activity_type || 'In-Use / Occupied') : 'Available Now';
                }
                if (statusPill) {
                    statusPill.className = isOccupied ?
                        'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500 text-white shadow-sm' :
                        'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500 text-white shadow-sm pulse-badge';
                }

                // 2. Announcement Box
                const annBox = document.getElementById('inspect-announcement-box');
                const annText = document.getElementById('inspect-announcement-text');
                const actTypeLabel = document.getElementById('inspect-activity-type-label');
                const occByLabel = document.getElementById('inspect-occupied-by-label');
                const occSinceLabel = document.getElementById('inspect-occupied-since-label');

                if (annBox) {
                    if (isOccupied) {
                        annBox.classList.remove('hidden');
                        if (actTypeLabel) actTypeLabel.textContent = occ?.activity_type || fullRoom.course || 'Room Activity';
                        if (annText) {
                            if (occ?.announcement && occ.announcement.trim()) {
                                annText.textContent = occ.announcement;
                                annText.className = "text-xs font-semibold text-white leading-relaxed whitespace-pre-wrap bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20";
                            } else {
                                annText.textContent = `No additional description provided. Room is currently in use for ${occ?.activity_type || fullRoom.course || 'Faculty Activity'}.`;
                                annText.className = "text-xs text-on-surface-variant italic leading-relaxed";
                            }
                        }
                        if (occByLabel) {
                            const hostName = occ?.occupied_by_name || fullRoom.current_professor?.name || 'Faculty Member';
                            const hostRole = occ?.occupied_by_role ? ` (${occ.occupied_by_role})` : '';
                            occByLabel.innerHTML = `
                                <span class="material-symbols-outlined text-xs text-amber-400">person</span>
                                <span>${hostName}${hostRole}</span>
                            `;
                        }
                        if (occSinceLabel) {
                            occSinceLabel.textContent = occ?.started_at ? `Started: ${occ.started_at.substring(11, 16)}` : 'Active';
                        }
                    } else {
                        annBox.classList.add('hidden');
                    }
                }

                // 3. Faculty / Admin Action Buttons
                const btnClaim = document.getElementById('btn-claim-room');
                const occupiedActions = document.getElementById('inspector-occupied-actions');
                const btnVirtualRoom = document.getElementById('btn-inspector-virtual-room');
                const studentNotice = document.getElementById('inspector-student-notice');

                const isUserFacultyOrAdmin = window.NPC_USER_CONTEXT?.isFacultyOrAdmin;
                const userEmail = (window.NPC_USER_CONTEXT?.email || '').toLowerCase();
                const userRole = (window.NPC_USER_CONTEXT?.role || '').toLowerCase();

                // Hybrid video conference link
                const virtualUrl = occ?.virtual_meeting_url;
                if (btnVirtualRoom) {
                    if (isOccupied && virtualUrl) {
                        btnVirtualRoom.href = virtualUrl;
                        btnVirtualRoom.classList.remove('hidden');
                    } else {
                        btnVirtualRoom.classList.add('hidden');
                    }
                }

                if (!isUserFacultyOrAdmin) {
                    // Student / Guest: View-only
                    if (btnClaim) btnClaim.classList.add('hidden');
                    if (occupiedActions) occupiedActions.classList.add('hidden');
                    if (studentNotice) studentNotice.classList.remove('hidden');
                } else {
                    // Faculty or Administrator
                    if (studentNotice) studentNotice.classList.add('hidden');

                    if (!isOccupied) {
                        if (btnClaim) btnClaim.classList.remove('hidden');
                        if (occupiedActions) occupiedActions.classList.add('hidden');
                    } else {
                        if (btnClaim) btnClaim.classList.add('hidden');

                        // Authorized Faculty and Administrators can release occupied rooms
                        const isHost = occ && occ.occupied_by_email && (occ.occupied_by_email.toLowerCase().trim() === userEmail.trim());
                        const canRelease = isHost || isUserFacultyOrAdmin;

                        if (canRelease) {
                            if (occupiedActions) {
                                occupiedActions.classList.remove('hidden');
                                occupiedActions.classList.add('flex');
                            }
                            if (studentNotice) studentNotice.classList.add('hidden');
                        } else {
                            if (occupiedActions) occupiedActions.classList.add('hidden');
                            if (studentNotice) {
                                const host = occ?.occupied_by_name || 'Faculty / In-Use';
                                studentNotice.innerHTML = `🔒 Room is currently in use by <strong class="text-amber-300">${host}</strong>.`;
                                studentNotice.classList.remove('hidden');
                            }
                        }
                    }
                }
            };

            // Global Room Inspector Control
            window.closeRoomInspector = function() {
                const insp = document.getElementById('room-inspector');
                if (insp) {
                    insp.classList.add('closed');
                    insp.style.display = 'none';
                }
                state.cadEngine?.selectRoom(null);
                state.selectedRoom = null;
            };

            window.openRoomInspector = function(room) {
                if (!room) return;

                // Match with full live room data if available
                const fullRoom = state.campusData?.rooms?.find(rm =>
                    rm.id === room.id || (room.code && rm.code === room.code) || rm.code === room.id
                ) || room;
                state.selectedRoom = fullRoom;

                // 1. Room Basic Data
                document.getElementById('inspect-floor-badge').textContent = fullRoom.floor || state.currentFloor;
                document.getElementById('inspect-code-badge').textContent = fullRoom.code || fullRoom.id;
                document.getElementById('inspect-type-tag').textContent = (fullRoom.type || 'Room').toUpperCase();
                document.getElementById('inspect-room-name').textContent = fullRoom.name;
                document.getElementById('inspect-wing-info').textContent = `${fullRoom.wing || 'Academic Wing'} · DPWH Spec`;
                document.getElementById('inspect-area').textContent = `${fullRoom.area_sqm || 70} m²`;
                document.getElementById('inspect-capacity').textContent = `${fullRoom.capacity || 40} Seats`;

                const statusPill = document.getElementById('inspect-status-pill');
                const statusText = document.getElementById('inspect-status-text');
                const isAvail = (fullRoom.live_status === 'available' || fullRoom.status === 'available');
                statusText.textContent = isAvail ? 'Available Now' : (fullRoom.status === 'occupied' ? 'Ongoing Class' : 'In-Use');
                statusPill.className = isAvail ?
                    'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500 text-white shadow-sm pulse-badge' :
                    'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500 text-white shadow-sm';

                // 2. Class Section & Course Banner
                document.getElementById('inspect-section-badge').textContent = fullRoom.section || 'General';
                document.getElementById('inspect-course-name').textContent = fullRoom.course || 'Independent Academic Session';

                // 3. Assigned Professor
                const profSec = document.getElementById('inspect-professor-section');
                if (fullRoom.current_professor) {
                    profSec.classList.remove('hidden');
                    document.getElementById('inspect-prof-name').textContent = fullRoom.current_professor.name;
                    document.getElementById('inspect-prof-role').textContent = fullRoom.current_professor.role || 'Assigned Faculty Member';
                    document.getElementById('inspect-prof-email').textContent = fullRoom.current_professor.email || 'faculty@navotaspolytechniccollege.edu.ph';
                    document.getElementById('inspect-prof-avatar').src = fullRoom.current_professor.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(fullRoom.current_professor.name)}&background=0284c7&color=fff`;
                    const schedEl = document.getElementById('inspect-prof-schedule');
                    if (schedEl) schedEl.textContent = fullRoom.current_professor.time_window || '08:00 AM – 10:00 AM (Ongoing)';
                } else {
                    document.getElementById('inspect-prof-name').textContent = 'Prof. Edsan Moreno';
                    document.getElementById('inspect-prof-role').textContent = 'College Faculty & Staff';
                    document.getElementById('inspect-prof-email').textContent = 'edsan.moreno@navotaspolytechniccollege.edu.ph';
                    document.getElementById('inspect-prof-avatar').src = 'https://ui-avatars.com/api/?name=Edsan+Moreno&background=0284c7&color=fff';
                }

                // 4. Live Student Attendance Dashboard
                const att = fullRoom.attendance || {
                    enrolled: fullRoom.capacity || 45,
                    present: 38,
                    absent: 5,
                    late: 2,
                    rate: 84.4,
                    status: 'High Attendance (Ongoing)',
                    recent_logs: [{
                            name: 'Juan Dela Cruz',
                            student_no: '2024-00102',
                            time: '08:02 AM',
                            status: 'Present'
                        },
                        {
                            name: 'Maria Clara Santos',
                            student_no: '2024-00145',
                            time: '08:05 AM',
                            status: 'Present'
                        },
                        {
                            name: 'Angelo Reyes',
                            student_no: '2024-00188',
                            time: '08:08 AM',
                            status: 'Present'
                        },
                        {
                            name: 'Kristine Joy Ramos',
                            student_no: '2024-00215',
                            time: '08:15 AM',
                            status: 'Late'
                        }
                    ]
                };

                document.getElementById('inspect-att-present-count').textContent = att.present;
                document.getElementById('inspect-att-enrolled-count').textContent = `/ ${att.enrolled} Students`;
                document.getElementById('inspect-att-percentage').textContent = `${att.rate}% Rate`;
                document.getElementById('inspect-att-progress-bar').style.width = `${Math.min(att.rate, 100)}%`;
                document.getElementById('inspect-att-present-pill').textContent = att.present;
                document.getElementById('inspect-att-late-pill').textContent = att.late;
                document.getElementById('inspect-att-absent-pill').textContent = att.absent;
                document.getElementById('inspect-att-status-text').textContent = att.status;

                // Render Recent Student Sign-Ins
                const logContainer = document.getElementById('inspect-attendance-logs');
                if (logContainer) {
                    logContainer.innerHTML = '';
                    const logs = att.recent_logs || [];
                    logs.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between p-2 rounded-xl bg-surface-container-lowest/70 border border-outline-variant/40 text-xs font-mono';
                        const dotColor = (item.status === 'Present') ? 'bg-emerald-400' : (item.status === 'Late' ? 'bg-amber-400' : 'bg-rose-400');
                        const badgeColor = (item.status === 'Present') ?
                            'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' :
                            (item.status === 'Late' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30');

                        row.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full ${dotColor}"></span>
                                <div>
                                    <span class="font-bold text-on-surface">${item.name}</span>
                                    <span class="text-[10px] text-slate-400 block">${item.student_no}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${badgeColor}">${item.status}</span>
                                <span class="text-[9px] text-slate-400 block mt-0.5">${item.time}</span>
                            </div>
                        `;
                        logContainer.appendChild(row);
                    });
                }

                // 5. Daily Timetable
                const ttList = document.getElementById('inspect-timetable-list');
                ttList.innerHTML = '';
                const timetable = fullRoom.timetable || [];
                timetable.forEach(t => {
                    const row = document.createElement('div');
                    row.className = 'p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs flex items-center justify-between font-mono';
                    row.innerHTML = `
                        <div>
                            <div class="font-bold text-on-surface">${t.course}</div>
                            <div class="text-[11px] text-on-surface-variant mt-0.5">${t.day} · ${t.time}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md bg-surface-container text-cyan-300 border border-cyan-500/30 text-[11px] font-semibold">${t.section}</span>
                    `;
                    ttList.appendChild(row);
                });

                // 6. Amenities
                const amenList = document.getElementById('inspect-amenities-list');
                amenList.innerHTML = '';
                const amenities = fullRoom.amenities || ['Ceiling Cassette ACU 6HP', 'Fiber Wi-Fi', 'Interactive Display'];
                amenities.forEach(a => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center gap-1.5 p-2 rounded-xl bg-surface-container-low border border-outline-variant/60';
                    item.innerHTML = `
                        <span class="material-symbols-outlined text-xs text-cyan-400">check_circle</span>
                        <span class="truncate">${a}</span>
                    `;
                    amenList.appendChild(item);
                });

                // 7. Update Live Occupancy & Notice Card
                window.updateInspectorLiveComponents(fullRoom);

                const insp = document.getElementById('room-inspector');
                if (insp) {
                    insp.style.display = 'flex';
                    insp.classList.remove('closed');
                }
            };

            // 1. Initialize 2D CAD Blueprint Engine
            function initCadEngine() {
                try {
                    const isDark = document.documentElement.classList.contains('dark') ||
                        localStorage.getItem('npc-theme') === 'dark' ||
                        (!localStorage.getItem('npc-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    state.currentTheme = isDark ? 'autocad-dark' : 'white';

                    state.cadEngine = new NpcCadEngine(cadContainer, {
                        theme: state.currentTheme,
                        currentFloor: state.currentFloor,
                        roomStatuses: state.roomStatuses || {},
                        onRoomSelect: (room) => selectRoomById(room.id, {
                            zoomCamera: false,
                            source: '2d'
                        }),
                        onClearSelect: () => clearSelection(),
                        onRoomHover: (room, e) => {
                            if (!room) {
                                hoverTooltip.classList.add('hidden');
                            } else {
                                hoverTooltip.classList.remove('hidden');
                                const roomData = state.campusData?.rooms?.find(rm => rm.id === room.id || (room.code && rm.code === room.code)) || room;
                                const occ = state.occupancies?.[room.id] || (room.code && state.occupancies?.[room.code]);
                                const isOccupied = !!occ || (state.roomStatuses?.[room.id] === 'occupied') || (room.code && state.roomStatuses?.[room.code] === 'occupied') || (roomData.status === 'occupied');
                                const statusColor = isOccupied ? '#ef4444' : '#10b981';

                                document.getElementById('tooltip-title').innerHTML = `
                                    <span class="w-2 h-2 rounded-full inline-block shadow-sm" style="background:${statusColor}"></span>
                                    <span>${room.name || room.code || room.id}</span>
                                `;
                                let subtitle = `<span class="font-mono text-cyan-400 font-bold">${room.code || room.id}</span>`;
                                if (isOccupied) {
                                    const act = occ?.activity_type || roomData.course || 'Occupied / In-Use';
                                    const host = occ?.occupied_by_name || roomData.current_professor?.name;
                                    const ann = (occ?.announcement && occ.announcement.trim()) ? ` · "${occ.announcement.trim()}"` : '';
                                    subtitle += ` · <span class="text-rose-400 font-bold">${act}</span>` + (host ? ` (${host})` : '') + ann;
                                } else {
                                    subtitle += ` · <span class="text-emerald-400 font-bold">Available Now</span> · ${room.area_sqm || 70}m²`;
                                }
                                document.getElementById('tooltip-subtitle').innerHTML = subtitle;
                                if (e) {
                                    hoverTooltip.style.left = `${e.clientX + 14}px`;
                                    hoverTooltip.style.top = `${e.clientY + 14}px`;
                                }
                            }
                        },
                        onCoordinateChange: (coords) => {
                            if (footerCoords) {
                                const signX = coords.worldX_m >= 0 ? '+' : '';
                                const signY = coords.worldY_m >= 0 ? '+' : '';
                                footerCoords.textContent = `X: ${signX}${coords.worldX_m.toFixed(2)}m | Y: ${signY}${coords.worldY_m.toFixed(2)}m (${coords.worldX_mm}mm, ${coords.worldY_mm}mm)`;
                            }
                        },
                        onMeasureComplete: (m) => {
                            const msg = `Tape Measure: ${m.distance_m} m (${m.distance_mm} mm) | ΔX: ${m.deltaX_m}m, ΔY: ${m.deltaY_m}m`;
                            if (typeof window.notify === 'function') {
                                window.notify(msg, 'info', 4000);
                            }
                            if (footerNavHint) {
                                footerNavHint.textContent = msg;
                            }
                        }
                    });
                } catch (err) {
                    console.error('Failed to init CAD engine:', err);
                }
            }

            // 1B. Initialize 3D Procedural BIM Engine (Directly Generated from NPC_FLOORS_DATA)
            function init3DEngine() {
                try {
                    if (state.campus3D) return;
                    state.campus3D = new NpcCampus3D(bim3dContainer, {
                        currentFloor: state.currentFloor,
                        onRoomSelect: (room) => selectRoomById(room.id, {
                            zoomCamera: false,
                            source: '3d'
                        }),
                        onClearSelect: () => clearSelection(),
                        onRoomHover: (room) => {
                            if (!room) {
                                hoverTooltip.classList.add('hidden');
                            } else {
                                hoverTooltip.classList.remove('hidden');
                                const roomData = state.campusData?.rooms?.find(rm => rm.id === room.id) || room;
                                const isAvail = (roomData.live_status === 'available' || roomData.status === 'available');
                                const statusColor = isAvail ? '#10b981' : '#f43f5e';
                                document.getElementById('tooltip-title').innerHTML = `
                                    <span class="w-2 h-2 rounded-full" style="background:${statusColor}"></span>
                                    <span>${room.name}</span>
                                `;
                                document.getElementById('tooltip-subtitle').textContent =
                                    `${room.code || room.id} · Section: ${roomData.section || 'General'} · ${roomData.current_professor?.name || (isAvail ? 'Available' : 'Occupied')}`;
                            }
                        }
                    });
                } catch (err) {
                    console.error('Failed to init 3D engine:', err);
                }
            }

            // 1C. View Mode (2D Vector Blueprint)
            function setViewMode(mode) {
                state.viewMode = '2d';
                if (cadContainer) cadContainer.classList.remove('hidden');
                if (bim3dContainer) bim3dContainer.classList.add('hidden');
                if (btnMode2d) btnMode2d.className = 'px-2.5 py-1 rounded-lg font-bold bg-cyan-600 text-white shadow-sm transition-all flex items-center gap-1';
                if (btnMode3d) btnMode3d.className = 'px-2.5 py-1 rounded-lg font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-all flex items-center gap-1';
                setTimeout(() => {
                    state.cadEngine?.resetViewport();
                    if (state.selectedRoom) {
                        state.cadEngine?.selectRoom(state.selectedRoom.id, false);
                    }
                }, 50);
            }

            btnMode2d?.addEventListener('click', () => setViewMode('2d'));
            btnMode3d?.addEventListener('click', () => setViewMode('3d'));

            // 1D. Developer Reference Overlay Controls
            btnToggleOverlay?.addEventListener('click', () => {
                const isVis = state.cadEngine?.toggleOverlay();
                if (refStatusBadge) {
                    refStatusBadge.textContent = isVis ? 'ON' : 'OFF';
                    refStatusBadge.className = isVis ? 'text-amber-400 font-bold' : 'text-slate-400 font-bold';
                }
                if (btnToggleOverlay) {
                    if (isVis) {
                        btnToggleOverlay.classList.add('border-amber-500/50', 'bg-amber-500/10', 'text-amber-300');
                    } else {
                        btnToggleOverlay.classList.remove('border-amber-500/50', 'bg-amber-500/10', 'text-amber-300');
                    }
                }
            });

            sliderRefOpacity?.addEventListener('input', (e) => {
                const val = parseInt(e.target.value, 10);
                if (refOpacityVal) refOpacityVal.textContent = `${val}%`;
                state.cadEngine?.setOverlayOpacity(val / 100);
            });

            // 2. Floor Level Switcher (Source of Truth for 1F, 2F, 3F, 4F, RD)
            function updateFloorPills(floorId) {
                state.currentFloor = floorId;
                document.querySelectorAll('.cad-floor-btn').forEach(btn => {
                    const f = btn.getAttribute('data-floor');
                    btn.disabled = false;
                    btn.style.pointerEvents = 'auto';
                    if (f === floorId) {
                        btn.className = 'cad-floor-btn px-2.5 py-1 rounded-lg text-xs font-semibold bg-cyan-600 text-white shadow-sm transition-all flex items-center gap-1 cursor-pointer';
                    } else {
                        btn.className = 'cad-floor-btn px-2.5 py-1 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-all flex items-center gap-1 cursor-pointer';
                    }
                });
            }

            function setFloor(floorId) {
                if (!floorId) return;
                updateFloorPills(floorId);
                state.cadEngine?.loadFloor(floorId);
                if (state.roomStatuses) {
                    state.cadEngine?.updateRoomStatuses(state.roomStatuses);
                }
                state.campus3D?.setFloor(floorId);
                if (tbSheetTitle) {
                    tbSheetTitle.textContent = `${floorId} ARCHITECTURAL BLUEPRINT (CAD)`;
                }
                // If currently selected room is on a different floor, clear selection
                if (state.selectedRoom && state.selectedRoom.floor !== floorId) {
                    clearSelection();
                } else if (state.selectedRoom && state.selectedRoom.id) {
                    state.cadEngine?.selectRoom(state.selectedRoom.id, false);
                    state.campus3D?.selectRoom(state.selectedRoom.id, false);
                }
            }

            document.querySelectorAll('.cad-floor-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    setFloor(btn.getAttribute('data-floor'));
                });
            });

            // 3. Unified Central Room Selection & Clean State Control
            function selectRoomById(roomId, options = {}) {
                if (!roomId) {
                    clearSelection();
                    return;
                }

                const zoomCamera = typeof options === 'boolean' ? options : (options.zoomCamera !== false);
                const source = (typeof options === 'object' && options.source) ? options.source : 'programmatic';

                // 1. Find room metadata by stable room ID (Search API data first, then master NPC_FLOORS_DATA)
                let room = state.campusData?.rooms?.find(r => r.id === roomId || r.code === roomId);
                if (!room && window.NPC_FLOORS_DATA) {
                    for (const fKey of ['1F', '2F', '3F', '4F', 'RD']) {
                        const fRooms = window.NPC_FLOORS_DATA[fKey]?.rooms || [];
                        const found = fRooms.find(r => r.id === roomId || r.code === roomId);
                        if (found) {
                            room = Object.assign({}, found, {
                                floor: fKey
                            });
                            break;
                        }
                    }
                }

                // 2. Validate that room exists; fail gracefully if no metadata
                if (!room) {
                    console.warn(`Room ID "${roomId}" not found in dataset.`);
                    return;
                }

                state.selectedRoom = room;

                // 3. Change to room's floor if necessary
                if (room.floor && room.floor !== state.currentFloor && room.floor !== 'ALL') {
                    setFloor(room.floor);
                }

                // 4. Highlight the same room in BOTH 2D and 3D
                state.cadEngine?.selectRoom(room.id, state.viewMode === '2d' && zoomCamera);
                state.campus3D?.selectRoom(room.id, state.viewMode === '3d' && zoomCamera);

                // 5. Update room inspector with the same room data
                window.openRoomInspector(room);
            }

            function clearSelection() {
                state.selectedRoom = null;
                state.cadEngine?.clearSelection();
                state.campus3D?.clearSelection();
                const insp = document.getElementById('room-inspector');
                if (insp) {
                    insp.classList.add('closed');
                    insp.style.display = 'none';
                }
            }

            // 4. CAD Tools Controller (Select, Measure, Zoom In, Zoom Out, Fit)
            const btnSelect = document.getElementById('btn-cad-select');
            const btnMeasure = document.getElementById('btn-cad-measure');
            const btnZoomIn = document.getElementById('btn-2d-zoom-in');
            const btnZoomOut = document.getElementById('btn-2d-zoom-out');
            const btnReset = document.getElementById('btn-2d-reset');
            const btnToggleLayers = document.getElementById('btn-toggle-layers');
            const btnCloseLayers = document.getElementById('btn-close-layers');

            btnSelect?.addEventListener('click', () => {
                state.activeTool = 'select';
                state.cadEngine?.setTool('select');
                btnSelect.className = 'cad-tool-btn px-2.5 py-1 rounded-lg text-xs font-semibold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center gap-1 transition-all';
                btnMeasure.className = 'cad-tool-btn px-2.5 py-1 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container flex items-center gap-1 transition-all';
                if (footerNavHint) {
                    footerNavHint.textContent = 'Left Drag: Pan Map | Wheel: Zoom | Click Room: Inspect Details';
                }
            });

            btnMeasure?.addEventListener('click', () => {
                state.activeTool = 'measure';
                state.cadEngine?.setTool('measure');
                btnMeasure.className = 'cad-tool-btn px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center gap-1 transition-all';
                btnSelect.className = 'cad-tool-btn px-2.5 py-1 rounded-lg text-xs font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container flex items-center gap-1 transition-all';
                if (footerNavHint) {
                    footerNavHint.textContent = 'Measure (DIST): Click first point, then click second point to measure distance';
                }
            });

            btnZoomIn?.addEventListener('click', () => {
                state.cadEngine?.zoomIn();
            });

            btnZoomOut?.addEventListener('click', () => {
                state.cadEngine?.zoomOut();
            });

            btnReset?.addEventListener('click', () => {
                state.cadEngine?.resetViewport();
            });

            // 5. AutoCAD Layers Manager
            function populateLayerManager() {
                if (!layerItemsList || !state.cadEngine) return;
                layerItemsList.innerHTML = Object.keys(state.cadEngine.layers).map(layerId => {
                    const layer = state.cadEngine.layers[layerId];
                    return `
                        <label class="flex items-center justify-between p-2 rounded-xl hover:bg-surface-container cursor-pointer transition-colors border border-transparent hover:border-outline-variant/40">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background:${layer.color}"></span>
                                <span class="text-xs text-on-surface font-mono font-bold">${layerId}</span>
                                <span class="text-[11px] text-on-surface-variant font-sans">${layer.name}</span>
                            </span>
                            <input type="checkbox" class="layer-toggle-cb rounded border-outline-variant text-cyan-500 focus:ring-cyan-500 cursor-pointer w-4 h-4" data-layer="${layerId}" ${layer.visible ? 'checked' : ''}>
                        </label>
                    `;
                }).join('');

                layerItemsList.querySelectorAll('.layer-toggle-cb').forEach(cb => {
                    cb.addEventListener('change', () => {
                        const lid = cb.getAttribute('data-layer');
                        state.cadEngine?.setLayerVisible(lid, cb.checked);
                    });
                });
            }

            btnToggleLayers?.addEventListener('click', () => {
                const isHidden = layerManager.classList.toggle('hidden');
                if (!isHidden) {
                    populateLayerManager();
                }
            });

            btnCloseLayers?.addEventListener('click', () => {
                layerManager.classList.add('hidden');
            });

            // 6. Automatic Theme Synchronization with Global Dark Mode Toggle
            function syncCADTheme() {
                const isDark = document.documentElement.classList.contains('dark') ||
                    localStorage.getItem('npc-theme') === 'dark' ||
                    (!localStorage.getItem('npc-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                const themeName = isDark ? 'autocad-dark' : 'white';
                state.currentTheme = themeName;
                state.cadEngine?.setTheme(themeName);
            }

            // Sync immediately on load
            syncCADTheme();

            // Observe global dark mode changes on <html>
            const themeObserver = new MutationObserver((mutations) => {
                for (const m of mutations) {
                    if (m.attributeName === 'class') {
                        syncCADTheme();
                        break;
                    }
                }
            });
            themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            // Also listen to cross-tab storage changes
            window.addEventListener('storage', (e) => {
                if (e.key === 'npc-theme') syncCADTheme();
            });

            // 7. Fullscreen / Lakihaan Workstation Mode
            const btnFullscreen = document.getElementById('btn-toggle-fullscreen');
            const fsIcon = document.getElementById('fullscreen-icon');
            const fsText = document.getElementById('fullscreen-text');

            function toggleFullscreenWorkstation() {
                const isFs = document.body.classList.toggle('campus-fullscreen');
                if (isFs) {
                    if (fsIcon) fsIcon.textContent = 'fullscreen_exit';
                    if (fsText) fsText.textContent = 'Normal (Esc)';
                    if (btnFullscreen) btnFullscreen.classList.add('border-amber-400', 'text-amber-400');
                    if (typeof window.notify === 'function') window.notify('Lakihaan View activated! Press Esc or F to toggle.', 'info', 3000);
                } else {
                    if (fsIcon) fsIcon.textContent = 'fullscreen';
                    if (fsText) fsText.textContent = 'Lakihaan (F)';
                    if (btnFullscreen) btnFullscreen.classList.remove('border-amber-400', 'text-amber-400');
                }
                setTimeout(() => state.cadEngine?.resetViewport(), 100);
            }

            btnFullscreen?.addEventListener('click', toggleFullscreenWorkstation);

            window.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                if (e.code === 'KeyF') {
                    toggleFullscreenWorkstation();
                } else if (e.code === 'Escape') {
                    if (document.body.classList.contains('campus-fullscreen')) {
                        toggleFullscreenWorkstation();
                    } else {
                        window.closeRoomInspector();
                        layerManager.classList.add('hidden');
                    }
                }
            });

            // 8. Fetch Campus Data from API
            async function fetchCampusData() {
                try {
                    const resp = await fetch('/api/campus_map.php?action=get_all');
                    const data = await resp.json();
                    state.campusData = data;
                    updateSummaryCounters(data.summary);
                    renderFacultyDirectory(data.faculty || []);

                    // Extract room statuses (occupied, available, etc.) and push to CAD engine
                    if (data.rooms) {
                        const statusMap = {};
                        data.rooms.forEach(r => {
                            statusMap[r.id] = r.status;
                            if (r.code) statusMap[r.code] = r.status;
                        });
                        state.roomStatuses = statusMap;
                        state.cadEngine?.updateRoomStatuses(statusMap);
                    }

                    const params = new URLSearchParams(window.location.search);
                    const targetRoom = params.get('room');
                    if (targetRoom) {
                        selectRoomById(targetRoom, true);
                    }
                } catch (e) {
                    console.warn('Could not fetch campus summary data:', e);
                }
            }

            function updateSummaryCounters(summary) {
                if (!summary) return;
                const availEl = document.getElementById('stat-available-count');
                const occEl = document.getElementById('stat-occupied-count');
                if (availEl) availEl.textContent = `${summary.available} Available`;
                if (occEl) occEl.textContent = `${summary.occupied} In-Use`;
            }

            // 8B. Real-Time Room Occupancy Poller (< 5ms response, lightweight)
            let pollTimer = null;
            async function pollRealtimeOccupancy(force = false) {
                try {
                    const resp = await fetch('/api/campus_map.php?action=get_occupancy');
                    if (!resp.ok) return;
                    const data = await resp.json();

                    if (data && data.success) {
                        state.roomStatuses = Object.assign({}, state.roomStatuses, data.statuses || {});
                        state.occupancies = data.occupancies || {};

                        // Push dynamic statuses to CAD vector engine
                        state.cadEngine?.updateRoomStatuses(state.roomStatuses);

                        // If room inspector is open, update its occupancy details live
                        if (state.selectedRoom) {
                            window.updateInspectorLiveComponents(state.selectedRoom);
                        }

                        // Update summary counters
                        const uniqueOccupied = new Set(Object.values(data.occupancies || {}).map(o => o.room_id));
                        const occupiedTotal = uniqueOccupied.size;
                        const occEl = document.getElementById('stat-occupied-count');
                        const availEl = document.getElementById('stat-available-count');
                        if (occEl) occEl.textContent = `${occupiedTotal} In-Use`;
                        if (availEl && state.campusData?.summary?.total_rooms) {
                            availEl.textContent = `${Math.max(0, state.campusData.summary.total_rooms - occupiedTotal)} Available`;
                        }
                    }
                } catch (err) {
                    console.debug('Realtime occupancy poll tick:', err);
                } finally {
                    if (!force) {
                        clearTimeout(pollTimer);
                        pollTimer = setTimeout(() => pollRealtimeOccupancy(false), 3500);
                    }
                }
            }
            window.pollRealtimeOccupancy = pollRealtimeOccupancy;

            // Room Inspector Action Listeners
            const btnCloseInsp = document.getElementById('btn-close-inspector');
            if (btnCloseInsp) {
                btnCloseInsp.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    window.closeRoomInspector();
                });
            }

            document.getElementById('btn-focus-cad-room')?.addEventListener('click', () => {
                if (state.selectedRoom) {
                    state.cadEngine?.zoomToRoom(state.selectedRoom.id);
                }
            });

            document.getElementById('btn-copy-room-link')?.addEventListener('click', () => {
                if (state.selectedRoom) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('room', state.selectedRoom.id);
                    navigator.clipboard.writeText(url.toString()).then(() => {
                        if (typeof window.notify === 'function') {
                            window.notify('Room share link copied!', 'success');
                        } else {
                            alert('Room share link copied!');
                        }
                    });
                }
            });

            // 9. Quick Room Explorer Buttons
            document.querySelectorAll('.quick-room-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const roomId = btn.getAttribute('data-room-id');
                    if (roomId) selectRoomById(roomId, true);
                });
            });

            // 10. Search Bar Autocomplete
            searchInput?.addEventListener('input', (e) => {
                const q = e.target.value.trim().toLowerCase();
                if (!q) {
                    searchDropdown.classList.add('hidden');
                    btnClearSearch.classList.add('hidden');
                    return;
                }
                btnClearSearch.classList.remove('hidden');

                if (!state.campusData || !state.campusData.rooms) return;

                const matches = state.campusData.rooms.filter(r => {
                    const inName = r.name?.toLowerCase().includes(q);
                    const inCode = r.code?.toLowerCase().includes(q);
                    const inSec = r.section?.toLowerCase().includes(q);
                    const inProf = r.current_professor?.name?.toLowerCase().includes(q);
                    return inName || inCode || inSec || inProf;
                }).slice(0, 8);

                if (matches.length === 0) {
                    searchDropdown.innerHTML = '<div class="p-3 text-xs text-on-surface-variant text-center font-mono">No matching rooms found.</div>';
                    searchDropdown.classList.remove('hidden');
                    return;
                }

                searchDropdown.innerHTML = matches.map(r => `
                    <div class="search-result-item p-2 rounded-lg hover:bg-surface-container cursor-pointer flex items-center justify-between text-xs transition-colors" data-room-id="${r.id}">
                        <div>
                            <div class="font-bold text-on-surface flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" style="background:${r.status === 'available' ? '#10b981' : '#f43f5e'}"></span>
                                <span>${r.name}</span>
                                <span class="font-mono text-[10px] text-cyan-400 px-1.5 py-0.5 rounded bg-surface-container-high">${r.code}</span>
                            </div>
                            <div class="text-[11px] text-on-surface-variant mt-0.5 font-mono">
                                ${r.floor} · Section: ${r.section || 'General'} · ${r.current_professor?.name || ''}
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-sm text-cyan-400">arrow_forward</span>
                    </div>
                `).join('');

                searchDropdown.classList.remove('hidden');

                searchDropdown.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const roomId = item.getAttribute('data-room-id');
                        selectRoomById(roomId, true);
                        searchDropdown.classList.add('hidden');
                        searchInput.value = '';
                        btnClearSearch.classList.add('hidden');
                    });
                });
            });

            btnClearSearch?.addEventListener('click', () => {
                searchInput.value = '';
                searchDropdown.classList.add('hidden');
                btnClearSearch.classList.add('hidden');
            });

            // 11. Faculty Directory Modal
            const btnOpenFaculty = document.getElementById('btn-open-faculty');
            const btnOpenFacultyMobile = document.getElementById('btn-open-faculty-mobile');
            const btnCloseFaculty = document.getElementById('btn-close-faculty');
            const facultyList = document.getElementById('faculty-list-container');
            const facultySearch = document.getElementById('faculty-search-input');

            btnOpenFaculty?.addEventListener('click', () => facultyDrawer.classList.remove('hidden'));
            btnOpenFacultyMobile?.addEventListener('click', () => facultyDrawer.classList.remove('hidden'));
            btnCloseFaculty?.addEventListener('click', () => facultyDrawer.classList.add('hidden'));
            facultyDrawer?.addEventListener('click', (e) => {
                if (e.target === facultyDrawer) facultyDrawer.classList.add('hidden');
            });

            function renderFacultyDirectory(faculty) {
                if (!facultyList) return;
                if (!faculty || faculty.length === 0) {
                    facultyList.innerHTML = '<div class="text-xs text-on-surface-variant p-4 text-center font-mono">No faculty records.</div>';
                    return;
                }

                facultyList.innerHTML = faculty.map(f => `
                    <div class="faculty-card p-3 rounded-2xl bg-surface-container-low border border-outline-variant hover:border-cyan-500/50 transition-all flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <img src="${f.avatar}" alt="${f.name}" class="w-10 h-10 rounded-full border border-outline-variant shadow-sm object-cover">
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs text-on-surface truncate">${f.name}</h4>
                                <p class="text-[11px] text-on-surface-variant font-mono truncate">${f.current_room_name} (${f.current_room_code})</p>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-500/10 text-cyan-400 font-semibold">${f.floor} Floor</span>
                            </div>
                        </div>
                        <button class="btn-locate-prof px-3 py-1.5 rounded-xl bg-cyan-600 text-white text-xs font-semibold hover:bg-cyan-500 transition-all flex items-center gap-1 shadow-sm shrink-0" data-room-id="${f.current_room_id}">
                            <span class="material-symbols-outlined text-xs">location_searching</span>
                            <span>Locate</span>
                        </button>
                    </div>
                `).join('');

                facultyList.querySelectorAll('.btn-locate-prof').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const roomId = btn.getAttribute('data-room-id');
                        facultyDrawer.classList.add('hidden');
                        selectRoomById(roomId, true);
                    });
                });
            }

            // Start up in 2D Vector CAD Blueprint mode immediately
            initCadEngine();
            fetchCampusData().then(() => {
                pollRealtimeOccupancy();
            });
        });
    </script>
</body>

</html>