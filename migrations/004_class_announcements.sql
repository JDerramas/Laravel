-- ============================================
-- NPC Connect — 004: Class Announcements + Materials hardening
-- Run this in: Supabase Dashboard → SQL Editor
-- Teachers post announcements scoped to ONE class/section;
-- enrolled students receive them as notifications.
-- ============================================

-- ─── Step 1: Class Announcements Table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS class_announcements (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES classes(id) ON DELETE CASCADE,
    class_code TEXT NOT NULL DEFAULT '',
    section TEXT NOT NULL DEFAULT '',
    faculty_email TEXT NOT NULL,
    faculty_name TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL,
    body TEXT NOT NULL DEFAULT '',
    category TEXT NOT NULL DEFAULT 'general', -- 'general' | 'assignment' | 'exam' | 'reminder'
    status TEXT NOT NULL DEFAULT 'published', -- 'published' | 'archived'
    created_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_class_ann_class ON class_announcements(class_id);
CREATE INDEX IF NOT EXISTS idx_class_ann_created ON class_announcements(created_at DESC);

ALTER TABLE class_announcements ENABLE ROW LEVEL SECURITY;

CREATE POLICY "classann_select_auth" ON class_announcements FOR SELECT USING (auth.uid() IS NOT NULL);
CREATE POLICY "classann_insert_staff" ON class_announcements FOR INSERT WITH CHECK (
    EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin', 'teacher', 'faculty'))
);
CREATE POLICY "classann_update_owner" ON class_announcements FOR UPDATE USING (
    EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND (u.role = 'admin' OR lower(u.email) = lower(faculty_email)))
);
CREATE POLICY "classann_delete_owner" ON class_announcements FOR DELETE USING (
    EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND (u.role = 'admin' OR lower(u.email) = lower(faculty_email)))
);

-- ─── Step 2: consultation_appointments — allow Rescheduled status ───────────
-- Existing app used 'Pending' | 'Confirmed' | 'Declined' | 'Completed'.
-- Add 'Rescheduled' if a CHECK constraint blocks it (no-op otherwise).
DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'consultation_appointments'::regclass
          AND contype = 'c'
          AND pg_get_constraintdef(oid) ILIKE '%status%'
    ) THEN
        -- Constraint exists; try widening it safely
        BEGIN
            ALTER TABLE consultation_appointments DROP CONSTRAINT IF EXISTS consultation_appointments_status_check;
            ALTER TABLE consultation_appointments ADD CONSTRAINT consultation_appointments_status_check
                CHECK (status IN ('Pending', 'Confirmed', 'Declined', 'Completed', 'Rescheduled'));
        EXCEPTION WHEN OTHERS THEN NULL; -- keep original constraint if rename differs
        END;
    END IF;
END $$;

-- ─── Step 3: requested_date/time must accept updates (no-op guard) ──────────
-- Reschedule writes requested_date / requested_time directly via service key.
