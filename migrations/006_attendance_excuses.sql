-- ============================================
-- NPC Connect — 006: Attendance Excuse Letters
-- Run in: Supabase Dashboard → SQL Editor
-- Students submit excuse letters for missed classes;
-- the class professor approves or rejects them.
-- ============================================

CREATE TABLE IF NOT EXISTS attendance_excuses (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    student_number TEXT NOT NULL,
    student_name TEXT NOT NULL,
    student_email TEXT NOT NULL,
    faculty_email TEXT NOT NULL,          -- professor who handles the class
    class_code TEXT NOT NULL DEFAULT '',
    session_code TEXT,                    -- nullable: may cover a whole day absence
    absence_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Pending', -- 'Pending' | 'Approved' | 'Rejected'
    reviewed_by TEXT,
    reviewed_at TIMESTAMPTZ,
    remarks TEXT,
    created_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_excuses_student ON attendance_excuses(student_number);
CREATE INDEX IF NOT EXISTS idx_excuses_faculty ON attendance_excuses(faculty_email);
CREATE INDEX IF NOT EXISTS idx_excuses_status ON attendance_excuses(status);

ALTER TABLE attendance_excuses ENABLE ROW LEVEL SECURITY;

-- Any authenticated user can view (faculty lists their own; students list their own —
-- actual scoping is enforced server-side via service-key queries)
CREATE POLICY "excuse_select_auth"
    ON attendance_excuses FOR SELECT USING (auth.uid() IS NOT NULL);

-- Only students insert their own excuses (server uses service key anyway)
CREATE POLICY "excuse_insert_student"
    ON attendance_excuses FOR INSERT WITH CHECK (auth.uid() IS NOT NULL);

-- Faculty/admin updates via service key; direct updates blocked for students
CREATE POLICY "excuse_update_staff"
    ON attendance_excuses FOR UPDATE
    USING (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin','teacher','faculty')))
    WITH CHECK (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin','teacher','faculty')));

-- Seed nothing; excuses start empty.
