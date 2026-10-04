---
title: Teacher Session Controller
type: Architecture Note
status: active
tags:
  - teacher-control
  - faculty
  - session-lifecycle
  - elms
updated: 2026-09-10
---

# 👨‍🏫 Teacher Session Controller (Exclusive On/Off Authority)

Under institutional policy:
> *"teacher lng mismo maka on or off"*

Students have **Join privileges only**; only the faculty instructor assigned to the course has administrative authority to initiate, administer, and terminate a virtual class.

Connected Nodes:
- Virtual Meeting Engine: [[Virtual_Classroom_WebRTC]]
- Access Barrier: [[Section_Gated_Access_Control]]
- Faculty Portal: [[Faculty_Portal_Architecture]]
- Brain Index: [[NPC_ELMS_Brain_Index]]

---

## 🎛️ 1. Faculty Course Card Controls
In [teacher/courses.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/teacher/courses.php):

| State | Visual Indicator | Action Available to Teacher |
| :--- | :--- | :--- |
| **Class Offline** | Standard navy card | **"Start Live Virtual Class (Sec 2A)"** button |
| **Class Online (Active)** | Pulsating red glowing ring, `🔴 LIVE CLASS ACTIVE` badge | **"Enter Room"** & **"End Session"** buttons |

---

## ⚡ 2. Session Lifecycle Workflow

```mermaid
sequenceDiagram
    autonumber
    actor Teacher as Faculty (Prof. Santos)
    participant UI as teacher/courses.php
    participant API as api/elms.php
    participant DB as backend/elms_courses.json

    Teacher->>UI: Clicks "Start Live Virtual Class"
    UI->>Teacher: Opens Topic/Agenda Modal
    Teacher->>UI: Enters "Financial Fraud Audit Discussion" & Submits
    UI->>API: POST ?action=toggle_live_class (status: active)
    API->>API: Validates role == 'teacher' || 'admin'
    API->>API: Generates Room ID: NPC-ELMS-AIS201-SEC2A-025359
    API->>DB: Saves live_session state (is_active: true, started_at: ISO timestamp)
    API-->>UI: 200 OK + Launches Teacher WebRTC Room

    Note over Teacher, DB: Lecture in session...

    Teacher->>UI: Clicks "End Session"
    UI->>API: POST ?action=toggle_live_class (status: inactive)
    API->>DB: Sets live_session.is_active = false
    API-->>UI: Session closed; course card returns to offline state
```

---

## 🔒 3. Server-Side Guard
`api/elms.php?action=toggle_live_class` enforces strict authorization:
```php
$userRole = $_SESSION['role'] ?? 'student';
if ($userRole !== 'teacher' && $userRole !== 'faculty' && $userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Only faculty instructors can control live class sessions.']);
    exit();
}
```

---
*Backlinks: [[Virtual_Classroom_WebRTC]] | [[Faculty_Portal_Architecture]] | [[Admin_Live_Audit_Center]]*
