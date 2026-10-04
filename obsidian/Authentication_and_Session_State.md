---
title: Authentication & Session State
type: Architecture Note
status: active
tags:
  - auth
  - session-management
  - security
  - dev-login
  - elms
updated: 2026-09-10
---

# 🔑 Authentication & Institutional Session Management

User identity, role access control, and cryptographic session validation in NPC ELMS are centralized in [includes/auth.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/includes/auth.php) and [login.php](file:///c:/Users/Lovi/Downloads/llama-b10483-bin-win-cpu-x64/LocalAI/app/login.php).

Connected Nodes:
- Central Brain: [[NPC_ELMS_Brain_Index]]
- Section Isolation: [[Section_Gated_Access_Control]]
- Audit Trails: [[Security_and_Audit_Logging]]
- Remote Access: [[Mobile_and_Remote_Tunneling]]

---

## 🔒 1. Session Hardening & Security Standards
Every authenticated PHP session enforces:
- `session.cookie_httponly = 1` (Protects against XSS cookie theft).
- `session.cookie_samesite = 'Strict'` (Prevents CSRF attacks).
- `session.use_strict_mode = 1` (Rejects uninitialized session IDs).
- Automatic session regeneration (`session_regenerate_id(true)`) upon elevation or role switching.

---

## 🎭 2. Rapid Dev Login Helper (`dev_login.php`)
For streamlined development, multi-user pairing, and mobile testing:

```
/dev_login.php?role=student   -> Logs in as Lovi Student (Section 2A)
/dev_login.php?role=teacher   -> Logs in as Prof. Dev Faculty
/dev_login.php?role=admin     -> Logs in as Administrator Dev
```
- Safely guarded to permit local access (`127.0.0.1`, `localhost`) and authenticated ngrok tunnels (`twice-careless-occultist.ngrok-free.dev`).

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Section_Gated_Access_Control]] | [[Security_and_Audit_Logging]]*
