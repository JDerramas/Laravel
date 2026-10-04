---
title: Faculty Portal Architecture
type: Architecture Note
status: active
tags:
  - faculty-portal
  - teacher
  - grading
  - authoring
  - elms
updated: 2026-09-10
---

# 👩‍🏫 Faculty Portal Architecture (`/teacher/`)

The **Faculty Portal** empowers college professors and instructors to manage instructional content, evaluate student submissions, and conduct live virtual classrooms.

Connected Nodes:
- Central Brain: [[NPC_ELMS_Brain_Index]]
- Live Meeting Switch: [[Teacher_Session_Controller]]
- Evaluation System: [[Coursework_and_Grading_Engine]]
- Student Nexus: [[Student_Portal_Architecture]]

---

## 📂 1. Faculty Workspaces

| Path | File | Key Functions |
| :--- | :--- | :--- |
| `/teacher/index.php` | [teacher/index.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/teacher/index.php) | Faculty Dashboard, Class Schedules, Attendance Summaries |
| `/teacher/courses.php` | [teacher/courses.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/teacher/courses.php) | **ELMS Course Hub**: Handout Uploads, Assignment Builder, Live Submissions Table, Virtual Classroom Controls |
| `/teacher/attendance.php` | [teacher_attendance.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/teacher_attendance.php) | Class Attendance Rosters & QR Generator |
| `/teacher/grades.php` | [teacher_grades.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/teacher_grades.php) | Prelim/Midterm/Final Grade Encoding & Transmutation |

---

## 🛠️ 2. Core Instructional Capabilities
1. **Module & Lecture Handout Publishing**:
   - Professors upload slide decks, laboratory exercises, and reading materials organized by Week.
2. **Assignment & Capstone Creation**:
   - Set titles, instructions, deadlines, maximum points (e.g. 100), and rubrics.
3. **Live Coursework Evaluation Modal**:
   - Inspects submitted URLs (GitHub / Google Drive) and qualitative student notes.
   - Encodes numerical score and qualitative constructive remarks.
4. **Virtual Classroom Control**:
   - One-click Start/End switch for online classes: see [[Teacher_Session_Controller]].

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Teacher_Session_Controller]] | [[Coursework_and_Grading_Engine]]*
