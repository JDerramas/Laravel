---
title: Mobile & Remote Tunneling Architecture
type: Infrastructure Note
status: active
tags:
  - mobile
  - tunneling
  - ngrok
  - webrtc-ssl
  - phone-testing
updated: 2026-09-10
---

# 📱 Mobile & Remote Tunneling Architecture

To enable live smartphone and tablet testing from anywhere (on Wi-Fi, 4G, or 5G), NPC ELMS incorporates an automated tunneling layer.

Connected Nodes:
- Central Brain: [[NPC_ELMS_Brain_Index]]
- Virtual Classroom: [[Virtual_Classroom_WebRTC]]
- UI Design: [[UI_Design_System_and_Tailwind]]
- Auth State: [[Authentication_and_Session_State]]

---

## 🔒 1. Why HTTPS is Mandatory for Mobile WebRTC
Modern mobile browsers (Google Chrome on Android, Apple Safari on iOS) strictly enforce WebRTC media constraints:
- `navigator.mediaDevices.getUserMedia()` is **permanently disabled** on plain HTTP connections across local networks (e.g. `http://192.168.1.62:8000`).
- By running a secure **ngrok HTTPS tunnel** with trusted TLS certificates:
  1. Camera and microphone permissions prompt cleanly on mobile.
  2. The WebRTC video classroom functions flawlessly without browser warnings.

---

## 🌐 2. Live Public Endpoints
- **Official Permanent Tunnel**:
  👉 **`https://twice-careless-occultist.ngrok-free.dev`**
- **Fallback Cloudflare Tunnel**:
  `cloudflared tunnel --url http://127.0.0.1:8000`

---
*Backlinks: [[NPC_ELMS_Brain_Index]] | [[Virtual_Classroom_WebRTC]] | [[UI_Design_System_and_Tailwind]]*
