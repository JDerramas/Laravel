-- ============================================
-- NPC Connect — 005: Account deactivation + audit performance
-- Run this in: Supabase Dashboard → SQL Editor
-- ============================================

-- ─── Step 1: users.is_active ─────────────────────────────────────────────────
-- Deactivated accounts are blocked at login (set_session.php checks this).
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;

-- Backfill safety: any NULL becomes active
UPDATE users SET is_active = true WHERE is_active IS NULL;

-- ─── Step 2: security_logs indexes for server-side audit filtering ──────────
CREATE INDEX IF NOT EXISTS idx_seclog_created ON security_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_seclog_user ON security_logs(user_email);
CREATE INDEX IF NOT EXISTS idx_seclog_severity ON security_logs(severity);
