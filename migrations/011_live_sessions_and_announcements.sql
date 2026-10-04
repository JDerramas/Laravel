-- ============================================================================
-- NPC Connect — 011: Live Classroom (Google Meet), Verified Attendance & Communication
-- Forward-only, non-destructive migration.
-- Run in: Supabase Dashboard → SQL Editor (or via Migration runner)
-- ============================================================================

-- ─── 1. ATTENDANCE SESSIONS ENHANCEMENT (Live Classroom & Controls) ──────────
DO $$ BEGIN
    -- meeting_provider: 'google_meet' | 'jitsi' | 'webrtc' | 'custom'
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='meeting_provider') THEN
        ALTER TABLE attendance_sessions ADD COLUMN meeting_provider TEXT DEFAULT 'google_meet';
    END IF;

    -- meeting_link: e.g. 'https://meet.google.com/abc-defg-hij'
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='meeting_link') THEN
        ALTER TABLE attendance_sessions ADD COLUMN meeting_link TEXT;
    END IF;

    -- topic: specific subject / lecture focus
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='topic') THEN
        ALTER TABLE attendance_sessions ADD COLUMN topic TEXT;
    END IF;

    -- agenda: bulleted or narrative meeting agenda
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='agenda') THEN
        ALTER TABLE attendance_sessions ADD COLUMN agenda TEXT;
    END IF;

    -- session_status: 'scheduled' | 'starting_soon' | 'active' | 'ended' | 'cancelled'
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='session_status') THEN
        ALTER TABLE attendance_sessions ADD COLUMN session_status TEXT DEFAULT 'active';
    END IF;

    -- grace_period_minutes: default 15 minutes
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='grace_period_minutes') THEN
        ALTER TABLE attendance_sessions ADD COLUMN grace_period_minutes INT DEFAULT 15;
    END IF;

    -- is_attendance_locked: teacher can immediately freeze check-ins
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_sessions' AND column_name='is_attendance_locked') THEN
        ALTER TABLE attendance_sessions ADD COLUMN is_attendance_locked BOOLEAN DEFAULT false;
    END IF;
END $$;

-- ─── 2. ATTENDANCE RECORDS ENHANCEMENT (Verified Receipt & Audit) ─────────────
DO $$ BEGIN
    -- reference_id: Unique audit receipt e.g. 'REF-20260910-A1B2C'
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='reference_id') THEN
        ALTER TABLE attendance_records ADD COLUMN reference_id TEXT;
    END IF;

    -- verified_via: 'live_portal_checkin' | 'qr_code' | 'manual_code' | 'teacher_manual' | 'google_meet_verified'
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='verified_via') THEN
        ALTER TABLE attendance_records ADD COLUMN verified_via TEXT DEFAULT 'qr_code';
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_attendance_records_ref ON attendance_records(reference_id);

-- ─── 3. ANNOUNCEMENTS ENHANCEMENT (Audience Scoping, Priority, Scheduling) ─────
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name='announcements') THEN
        -- priority: 'Normal' | 'Important' | 'Urgent'
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='priority') THEN
            ALTER TABLE announcements ADD COLUMN priority TEXT DEFAULT 'Normal';
        END IF;

        -- target_audience: 'all' | 'students' | 'faculty' | 'program' | 'section'
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='target_audience') THEN
            ALTER TABLE announcements ADD COLUMN target_audience TEXT DEFAULT 'all';
        END IF;

        -- target_program: e.g. 'BSIS', 'BSAIS', 'BSBA'
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='target_program') THEN
            ALTER TABLE announcements ADD COLUMN target_program TEXT;
        END IF;

        -- target_section: e.g. '2A', '3B'
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='target_section') THEN
            ALTER TABLE announcements ADD COLUMN target_section TEXT;
        END IF;

        -- is_pinned: Pinned announcements show at the top of the feed
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='is_pinned') THEN
            ALTER TABLE announcements ADD COLUMN is_pinned BOOLEAN DEFAULT false;
        END IF;

        -- scheduled_at: future publishing support
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='scheduled_at') THEN
            ALTER TABLE announcements ADD COLUMN scheduled_at TIMESTAMPTZ;
        END IF;

        -- expires_at: date when announcement unpins or archives
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='expires_at') THEN
            ALTER TABLE announcements ADD COLUMN expires_at TIMESTAMPTZ;
        END IF;

        -- attachments: JSON array of [{ name, url, type, size }]
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='announcements' AND column_name='attachments') THEN
            ALTER TABLE announcements ADD COLUMN attachments JSONB DEFAULT '[]'::jsonb;
        END IF;
    END IF;
END $$;

-- ─── 4. NOTIFICATION PREFERENCES TABLE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_notification_preferences (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_email TEXT NOT NULL UNIQUE,
    in_app_announcements BOOLEAN DEFAULT true,
    in_app_live_classes BOOLEAN DEFAULT true,
    in_app_grades BOOLEAN DEFAULT true,
    in_app_attendance BOOLEAN DEFAULT true,
    email_notifications BOOLEAN DEFAULT false,
    updated_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_notif_pref_email ON user_notification_preferences(user_email);

ALTER TABLE user_notification_preferences ENABLE ROW LEVEL SECURITY;

CREATE POLICY "notif_pref_select_auth"
    ON user_notification_preferences FOR SELECT
    USING (auth.uid() IS NOT NULL);

CREATE POLICY "notif_pref_upsert_auth"
    ON user_notification_preferences FOR ALL
    USING (auth.uid() IS NOT NULL);
