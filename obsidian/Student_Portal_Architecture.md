---
title: Student Portal Architecture
type: Architecture Note
status: active
tags:
  - student-portal
  - student-experience
  - coursework
  - elms
updated: 2026-09-10
---

# 👨‍🎓 Student Portal Architecture (`/student/`)

The **Student Portal** serves as the primary academic learning environment for Navotas Polytechnic College learners.

Connected Nodes:
- Central Brain: [[NPC_ELMS_Brain_Index]]
- Virtual Classroom: [[Virtual_Classroom_WebRTC]]
- Section Privacy: [[Section_Gated_Access_Control]]
- Grading Feedback: [[Coursework_and_Grading_Engine]]
- 3D Atmosphere: [[ThreeJS_3D_Graphics_Engine]]

---

## 📂 1. Primary Student Views & Routes

| Path | File | Function |
| :--- | :--- | :--- |
| `/student/index.php` | [student/index.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/student/index.php) | Main Dashboard, Live Broadcast Banner, Schedule, Quick Stats |
| `/student/courses.php` | [student/courses.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/student/courses.php) | ELMS Courses, Handouts, Capstone Tasks, Live Virtual Class |
| `/student/academic.php` | [student/academic.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/student/academic.php) | Grades, Curriculum Checklist, Evaluation Records |
| `/student/schedule.php` | [student/schedule.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/student/schedule.php) | Weekly Timetable & Room Schedules |
| `/student/qrcode.php` | [student/qrcode.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/student/qrcode.php) | 3D HUD Scanner for Attendance Verification |

---

## 🌟 2. Interactive Highlights
1. **Real-time Broadcast Banner (`#live-class-alert-banner`)**:
   - Pulses red at the top of the screen when a professor starts an online lecture for the student's section.
2. **Dynamic Course Cards**:
   - Shows module file downloads (`.pdf`, `.pptx`, `.zip`), active assignment deadlines, and submission forms.
3. **Instant Grade & Feedback Card**:
   - As soon as a professor grades an assignment, a green card reveals:
     `Graded · 98/100` alongside personalized professor remarks.

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Coursework_and_Grading_Engine]] | [[Virtual_Classroom_WebRTC]]*
