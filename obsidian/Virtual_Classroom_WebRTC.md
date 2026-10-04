---
title: Virtual Classroom WebRTC Engine
type: Architecture Note
status: active
tags:
  - virtual-classroom
  - webrtc
  - jitsi
  - live-broadcast
  - elms
updated: 2026-09-10
---

# 📹 Virtual Classroom WebRTC Engine (Unlimited Video Meetings)

The **Virtual Classroom WebRTC Engine** delivers free, high-performance, real-time video conferencing for Navotas Polytechnic College lectures, laboratory classes, and student consultations without commercial licensing fees.

Connected Nodes:
- Central Hub: [[NPC_ELMS_Brain_Index]]
- Security Barrier: [[Section_Gated_Access_Control]]
- Teacher Lifecycle: [[Teacher_Session_Controller]]
- Campus Oversight: [[Admin_Live_Audit_Center]]
- Mobile SSL Requirements: [[Mobile_and_Remote_Tunneling]]

---

## 🌟 1. Core Architectural Highlights
1. **Zero Time Restrictions (Unli)**:
   - Free tiers of Zoom or Google Meet impose hard 40-minute or 60-minute cutoffs.
   - The NPC ELMS WebRTC integration (`meet.jit.si`) runs indefinitely without time limits.
2. **Zero Attendee Caps**:
   - Accommodates full lecture halls, blended classes, or entire collegiate sections simultaneously.
3. **Comprehensive Academic Toolkit**:
   - **HD Camera & Audio Stream**: Native WebRTC microphone and video capture.
   - **Screen Sharing**: Seamless presentation of slide decks, PDF handouts, and programming code.
   - **In-Room Chat**: Real-time Q&A sidebar.
   - **Raise Hand & Reactions**: Non-verbal student feedback during professor discussions.
   - **Speaker & Tile Grid Views**: Dynamic layout adaptation for both lecture and seminar formats.

---

## ⚙️ 2. WebRTC Room Initialization Parameters
Rooms are launched using collision-proof identifiers:
```
NPC-ELMS-<COURSE_CODE>-SEC<SECTION>-<HASH>
Example: NPC-ELMS-AIS201-SEC2A-025359
```

When joining, the system constructs a seamless auto-join URL:
```javascript
const roomUrl = `https://meet.jit.si/${room_id}#userInfo.displayName="${encodeURIComponent(displayName)}"&config.prejoinPageEnabled=false`;
```
- **`config.prejoinPageEnabled=false`**: Bypasses the generic lobby screen and drops the user straight into class.
- **`userInfo.displayName`**: Injects the student or faculty member's institutional identity (e.g. `Lovi Student (2024-00192)` or `Prof. Maria Santos`).

---

## 🛡️ 3. Security & Gating Dependencies
- Students cannot access the room unless their enrolled section matches the class: see [[Section_Gated_Access_Control]].
- The session cannot exist until the professor switches it ON: see [[Teacher_Session_Controller]].
- Administrators retain campus surveillance and emergency termination rights: see [[Admin_Live_Audit_Center]].

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Student_Portal_Architecture]] | [[Faculty_Portal_Architecture]]*
