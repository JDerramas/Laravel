-- ============================================================================
-- NPC Connect — 012: Live Online Class Presence, Duration Timers & Reconnect Tracking
-- Forward-only, non-destructive migration.
-- Run in: Supabase Dashboard → SQL Editor (or via Migration runner)
-- ============================================================================

DO $$ BEGIN
    -- duration_seconds: Total accumulated active seconds inside the virtual class
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='duration_seconds') THEN
        ALTER TABLE attendance_records ADD COLUMN duration_seconds INT DEFAULT 0;
    END IF;

    -- leave_count: Number of times the student disconnected or left and rejoined
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='leave_count') THEN
        ALTER TABLE attendance_records ADD COLUMN leave_count INT DEFAULT 0;
    END IF;

    -- is_online: Current real-time presence state
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='is_online') THEN
        ALTER TABLE attendance_records ADD COLUMN is_online BOOLEAN DEFAULT false;
    END IF;

    -- last_heartbeat: Timestamp of latest keepalive ping
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='last_heartbeat') THEN
        ALTER TABLE attendance_records ADD COLUMN last_heartbeat TIMESTAMPTZ;
    END IF;

    -- last_joined_at: Timestamp when current active session streak started
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='last_joined_at') THEN
        ALTER TABLE attendance_records ADD COLUMN last_joined_at TIMESTAMPTZ;
    END IF;

    -- last_left_at: Timestamp of most recent disconnect or leave
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='attendance_records' AND column_name='last_left_at') THEN
        ALTER TABLE attendance_records ADD COLUMN last_left_at TIMESTAMPTZ;
    END IF;
END $$;
