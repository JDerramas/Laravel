-- ============================================
-- NPC Connect — 007: Materials module weeks + Announcement attachments & scheduling
-- Run in: Supabase Dashboard → SQL Editor
-- ============================================

-- ─── 1. faculty_materials: module/week label ────────────────────────────────
ALTER TABLE faculty_materials ADD COLUMN IF NOT EXISTS module_week TEXT DEFAULT '';

-- ─── 2. class_announcements: attachment + schedule ──────────────────────────
ALTER TABLE class_announcements ADD COLUMN IF NOT EXISTS attachment_url TEXT DEFAULT '';
ALTER TABLE class_announcements ADD COLUMN IF NOT EXISTS attachment_name TEXT DEFAULT '';
ALTER TABLE class_announcements ADD COLUMN IF NOT EXISTS scheduled_at TIMESTAMPTZ;

-- Announcements with a future scheduled_at are hidden from students until then.
-- (Display filter is enforced server-side in api_student.php / api_faculty.php.)
CREATE INDEX IF NOT EXISTS idx_class_ann_sched ON class_announcements(scheduled_at);
