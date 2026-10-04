-- ============================================
-- NPC Connect — 009: Per-Class Grading Schemes
-- Run in: Supabase Dashboard → SQL Editor
-- Each teacher can define their own grading formula per class:
--   - Period weights (Prelim/Midterm/Final %)
--   - Component weights per period (Quiz/Activity/Attendance/Exam/Project %)
-- ============================================

CREATE TABLE IF NOT EXISTS grading_schemes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES classes(id) ON DELETE CASCADE,
    faculty_email TEXT NOT NULL,

    -- Period weights: {"prelim":30,"midterm":30,"final":40}
    period_weights JSONB NOT NULL DEFAULT '{"prelim":30,"midterm":30,"final":40}',

    -- Component weights per period:
    -- {"prelim":{"quiz":20,"activity":20,"attendance":10,"exam":50},
    --  "midterm":{...},"final":{"quiz":20,"project":20,"attendance":10,"exam":50}}
    component_weights JSONB NOT NULL DEFAULT '{}',

    is_locked BOOLEAN DEFAULT false,      -- lock after registrar approval
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now(),

    UNIQUE(class_id)
);

CREATE INDEX IF NOT EXISTS idx_gs_class ON grading_schemes(class_id);

ALTER TABLE grading_schemes ENABLE ROW LEVEL SECURITY;

CREATE POLICY "gs_select_auth"
    ON grading_schemes FOR SELECT USING (auth.uid() IS NOT NULL);
