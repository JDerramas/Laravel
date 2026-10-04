-- ============================================
-- NPC Connect — 005: Soft-Delete Support + Academic Settings
-- Run in: Supabase Dashboard → SQL Editor
-- Enables: user deactivation (is_active), academic calendar (settings table)
-- ============================================

-- ─── Step 1: Soft-delete for users ─────────────────────────────────────────
-- Add is_active column (default true) and an index for the admin user list filter.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_users_role_active ON users(role, is_active);

-- Convenience view for the admin user grid (only active accounts by default)
CREATE OR REPLACE VIEW active_users AS
SELECT * FROM users WHERE is_active = true;

-- ─── Step 2: Global app settings (attendance grace, etc.) ───────────────────
-- Simple key/value table for tunable institution-wide parameters.
-- Migration 003 already created school_years + semesters; this complements them.
CREATE TABLE IF NOT EXISTS app_settings (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    data_type TEXT DEFAULT 'text', -- 'text' | 'integer' | 'boolean' | 'json'
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

ALTER TABLE app_settings ENABLE ROW LEVEL SECURITY;
CREATE POLICY "appset_select_auth" ON app_settings FOR SELECT USING (auth.uid() IS NOT NULL);
CREATE POLICY "appset_mutation_admin" ON app_settings FOR ALL
    USING (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role = 'admin'))
    WITH CHECK (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role = 'admin'));

-- Seed sensible defaults
INSERT INTO app_settings (setting_key, setting_value, data_type, description)
VALUES
    ('attendance_grace_minutes', '5', 'integer', 'Minutes before a class is marked Late instead of On Time'),
    ('attendance_present_window_minutes', '10', 'integer', 'QR session: minutes students can check in as Present'),
    ('attendance_late_window_minutes', '5', 'integer', 'QR session: extra minutes after Present window counted as Late'),
    ('upload_max_size_mb', '10', 'integer', 'Maximum upload size in MB for documents/materials'),
    ('allow_self_enrollment', 'false', 'boolean', 'Whether students can self-enroll in classes')
ON CONFLICT (setting_key) DO NOTHING;

-- ─── Step 3: Enrollments table (section + class subject assignment) ─────────
-- Bridges students ↔ sections, and students ↔ specific class/subject offerings.
CREATE TABLE IF NOT EXISTS user_class_enrollments (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    student_id TEXT NOT NULL,             -- users.id or users.student_number
    class_id UUID REFERENCES classes(id) ON DELETE CASCADE,
    enrollment_status TEXT DEFAULT 'Enrolled', -- 'Enrolled' | 'Dropped' | 'Completed' | 'Irregular'
    enrolled_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(student_id, class_id)
);
CREATE INDEX IF NOT EXISTS idx_uce_student ON user_class_enrollments(student_id);
CREATE INDEX IF NOT EXISTS idx_uce_class ON user_class_enrollments(class_id);

-- ─── Step 4: RLS policies ───────────────────────────────────────────────────
ALTER TABLE user_class_enrollments ENABLE ROW LEVEL SECURITY;
CREATE POLICY "uce_select_auth"
    ON user_class_enrollments FOR SELECT USING (auth.uid() IS NOT NULL);
CREATE POLICY "uce_mutation_staff"
    ON user_class_enrollments FOR ALL
    USING (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin', 'teacher')))
    WITH CHECK (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin', 'teacher')));

-- ─── Step 5: Soft-delete helper view for users ──────────────────────────────
CREATE OR REPLACE VIEW deactivated_users AS
SELECT id, full_name, email, role, student_number, program, section, created_at
FROM users WHERE is_active = false;
