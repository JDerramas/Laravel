---
title: UI Design System & Tailwind Palette
type: Design Note
status: active
tags:
  - design-system
  - tailwind
  - css
  - dark-mode
  - aesthetics
updated: 2026-09-10
---

# 🎨 UI Design System & Semantic Color Architecture

The visual identity of Navotas Polytechnic College is built upon an institutional design system defined in [styles.css](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/styles.css) and [includes/_head.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/includes/_head.php).

Connected Nodes:
- 3D Visuals: [[ThreeJS_3D_Graphics_Engine]]
- Student Hub: [[Student_Portal_Architecture]]
- Mobile Ready: [[Mobile_and_Remote_Tunneling]]
- Brain Index: [[NPC_ELMS_Brain_Index]]

---

## 🏛️ 1. Color Palette Tokens

```css
:root {
  /* NPC Academic Navy */
  --primary: #001736;
  --primary-rgb: 0, 23, 54;

  /* NPC Radiant Gold */
  --secondary: #fed488;
  --secondary-rgb: 254, 212, 136;

  /* Light Theme Surfaces */
  --surface: #f8f9fc;
  --surface-container: #ffffff;
  --on-surface: #0f172a;
  --outline-variant: #e2e8f0;
}

html.dark {
  /* Dark Mode Oled / Obsidian */
  --surface: #0b1329;
  --surface-container: #0f1c3f;
  --on-surface: #f8fafc;
  --outline-variant: rgba(255, 255, 255, 0.08);
}
```

---

## 🪄 2. Modern Design Elements
1. **Glassmorphism**: Translucent backdrop blur overlays (`backdrop-blur-md bg-white/70 dark:bg-slate-900/70`).
2. **Micro-Animations**: Hover card elevations, glowing red pulsars for active classes (`ring-4 ring-rose-500/30 animate-pulse`), and subtle 3D card tilts.
3. **Typography**: Google Fonts pairing: **Geist** for crisp modern headings and UI text, and **JetBrains Mono** for student numbers, timestamps, and course codes.

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[ThreeJS_3D_Graphics_Engine]]*
