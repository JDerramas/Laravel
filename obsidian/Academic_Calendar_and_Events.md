---
title: Academic Calendar & Events Engine
type: Feature Note
status: active
tags:
  - calendar
  - events
  - timeline
  - admin-editor
  - elms
updated: 2026-09-10
---

# 📅 Academic Calendar & Real-Time Events Engine

The **Academic Calendar Engine** provides institutional scheduling, synchronized dates across all dashboards, and an interactive administrative event editor.

Connected Nodes:
- Central Brain: [[NPC_ELMS_Brain_Index]]
- Admin Oversight: [[Admin_Master_Hub]]
- Student Hub: [[Student_Portal_Architecture]]
- UI Components: [[UI_Design_System_and_Tailwind]]

---

## 🕒 1. Live Dynamic Date & Clock Widget
- Present in the top bar of every portal page:
  - **Live Institutional Date**: `Wednesday, September 9, 2026`
  - **Pulsating Live Clock**: `8:45:12 PM` (auto-ticking every 1000ms via `mountLiveDateAndClock()` in `npc.js`).

---

## 📝 2. Admin Calendar Event Management
Located on the Admin Dashboard ([admin/index.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/admin/index.php)):
- **Add / Edit Academic Events Modal**:
  - Event Title (e.g. *"Midterm Examination Week"*, *"Faculty Academic Assembly"*).
  - Date & Time range.
  - Event Category (Exam, Holiday, Submission Deadline, Consultation).
  - Target Audience (Campus-wide, Faculty Only, Students Only).
- **Instant Persistence**:
  - Saved to `backend/academic_events.json` and immediately synced to student and faculty calendars.

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Admin_Master_Hub]] | [[Student_Portal_Architecture]]*
