# NPC Connect — Pre-Launch Security & Ops Checklist

> Status as of Aug 26, 2026 — verified by automated audit + live smoke test.
>
> ## ✅ Verified Working
>
> ### Features (all 5 "school system" items DONE)
> | Feature | Where |
> |---|---|
> | Notification center (merged bell feed w/ click-through) | npc.js + api_student/faculty |
> | Document request processing (approve/reject/ready/release) | admin_settings.php + api_admin.php |
> | Attendance history per subject (cards, ring chart, warnings) | academic.php |
> | Grade breakdown + published date (P/M/F + components) | academic.php |
> | Reports + CSV export (7 report types) | admin_reports.php + api_admin get_report |

### Guards (live smoke tested)
| Path type | Result |
|---|---|
| admin*.php unauthenticated | 302 → login ✓ |
| teacher.php unauthenticated | 302 → login ✓ |
| student pages unauthenticated | 302 → login ✓ |
| All api_*.php unauthenticated | 302 → login ✓ |
| download_material.php unauthentated | 302 → login ✓ |
| backup.php without token | 403 Forbidden ✓ |

### Upload security
- Randomized filenames (`mat_` + sha256 prefix) ✓
- MIME sniffing via finfo (not extension trust) ✓
- documents/ dir is OUTSIDE web root ✓
- .htaccess deny-all in documents/ (defense in depth) ✓
- Auth-checked streaming via download_material.php ✓

### New ops tooling
- **backup.php** — full DB→JSON (23 tables) + documents copy → timestamped zip,
  keeps last 14. CLI: `php backup.php`. Browser: `backup.php?token=...`
  (requires `BACKUP_TOKEN=...` line in `.env`)
- app/.htaccess blocks direct access to `.env`, `.sql`, backups, logs ✓

---

## 🔧 TODO (needs your Supabase/hosting access)

### 1. Stable hosting (ngrok is demo-only)
Options ranked for NPC deployment:
1. **InfinityFree / 000webhost** — free PHP hosting; works with Supabase REST from PHP.
   - Con: outbound cURL allowed? Test `supabase_helper.php` connectivity first.
2. **Railway / Render** — free tier PHP via Docker or native; stable URL.
3. **School-hosted cPanel** — ask MIS/IT office; best long-term (PHP 8.1+).
4. **VPS (Contabo/DigitalOcean ~$5/mo)** — full control; needs someone to maintain.

After deploy:
- Set real domain HTTPS (Let's Encrypt auto on most hosts).
- Update `.env` if base path changes.

### 2. Google/Supabase redirect URLs
Login uses `origin + '/auth_callback.php'` dynamically — good for any host.
Still verify in **Supabase Dashboard → Authentication → URL Configuration**:
- [ ] Site URL = your production URL (e.g., `https://npc-connect.school.edu.ph`)
- [ ] Redirect URLs list includes BOTH:
  - `https://YOURDOMAIN/auth_callback.php`
  - `http://localhost:PORT/auth_callback.php` (dev only)
- [ ] Google Cloud Console → OAuth client → Authorized redirect URIs includes:
  - `https://<project>.supabase.co/auth/v1/callback`
- [ ] After deploy, do one real login per role to confirm session creation.

### 3. Protect uploaded files — DONE (verified above)

### 4. Role permission testing matrix (manual, after migration 005 runs)
| Test | Steps | Expected |
|---|---|---|
| Student → admin page | Login as student, go /admin.php | Bounce w/ ?denied=admin |
| Student → other grades | DevTools: call api_student.php?action=get_grades with other's email | Only own data returned (server ignores param) |
| Teacher A edits Teacher B class | Login A, POST save_gradebook with B's class_id | 403 ownership check |
| Admin approves grades | Login admin → Grades → approve submission | status → Approved/Published |
| Deactivated user login | Run migration 005 first; deactivate a test user | Login rejected: "account deactivated" |

### 5. Backups
- [x] backup.php created & tested live (119 rows, 23 tables, docs copied)
- [ ] Add `BACKUP_TOKEN=<random-string>` to `.env` (for browser-triggered runs)
- [ ] Schedule daily run:
  - Windows: Task Scheduler → `php C:\...\app\backup.php` daily 2AM
  - Linux/cPanel cron: `0 2 * * * php /path/to/app/backup.php`
- [ ] Copy zips OFF the server weekly (Google Drive/USB). Backup sa same disk ≠ backup.

---

## 🎬 Demo Checklist (run before showing anyone)

**Setup:** start server, have 3 accounts ready (student/teacher/admin), phone w/ camera for QR.

**Student flow**
- [ ] Open login page → clean render, night mode toggle works
- [ ] Login as student (NPC Gmail SSO)
- [ ] Dashboard: next-class ticker shows LIVE/NEXT chip correctly
- [ ] Schedule page renders all enrolled subjects
- [ ] QR scan attendance: success receipt shows subject/time/status
- [ ] Academic → grades tab: P/M/F columns + expand breakdown
- [ ] Academic → materials tab: download a file
- [ ] Request document → success toast
- [ ] Bell icon: merged feed shows announcements + personal notifs
- [ ] Logout

**Teacher flow**
- [ ] Login as teacher
- [ ] Teaching brief counters show real numbers
- [ ] Start attendance session for today's class → QR appears
- [ ] Live roster updates (use phone to scan as student)
- [ ] End session
- [ ] Gradebook: edit a cell → save draft → export xlsx
- [ ] Post announcement to your section
- [ ] Upload material (PDF) → appears in student materials tab
- [ ] Consultations: Confirm + Reschedule buttons work
- [ ] Logout

**Admin flow**
- [ ] Login as admin
- [ ] Dashboard metrics render
- [ ] Users: bulk import CSV preview works (cancel without importing)
- [ ] Users: deactivate/reactivate button state changes
- items marked [x] are verified by code audit + smoke test.
