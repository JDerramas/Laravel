-- =========================================================================
-- NPC Connect — 012: Faculty Assessment Studio Tables & Schema
-- Run in: Supabase Dashboard → SQL Editor or via apply_migration
-- Creates essential tables for Faculty Assessment Studio:
--   1. grade_components (quizzes, activities, exams per class/period)
--   2. student_grades (stores raw, weighted, transmuted, and component_scores)
--   3. grading_schemes (per-class period and component weights)
--   4. grade_submissions (official lifecycle: Draft, Submitted, Approved, Published)
--   5. grade_change_requests (formal post-approval grade revisions)
-- =========================================================================

-- 1. Grade Components
CREATE TABLE IF NOT EXISTS public.grade_components (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE,
    component_name TEXT NOT NULL,
    percentage_weight NUMERIC NOT NULL,
    max_score NUMERIC DEFAULT 100,
    grading_period TEXT DEFAULT 'Prelim', -- 'Prelim', 'Midterm', 'Final', 'All'
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_gc_class_period ON public.grade_components(class_id, grading_period);

-- 2. Student Grades
CREATE TABLE IF NOT EXISTS public.student_grades (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE,
    student_id TEXT,
    student_number TEXT NOT NULL,
    student_name TEXT,
    prelim NUMERIC DEFAULT 0,
    midterm NUMERIC DEFAULT 0,
    prefinal NUMERIC DEFAULT 0,
    final NUMERIC DEFAULT 0,
    raw_grade NUMERIC DEFAULT 0,
    weighted_grade NUMERIC DEFAULT 0,
    equivalent_grade NUMERIC DEFAULT 0, -- NPC Transmuted Scale (1.00 - 5.00)
    final_rating NUMERIC DEFAULT 0,
    remarks TEXT DEFAULT 'Ongoing', -- 'Passed', 'Failed', 'INC', 'Dropped', 'Ongoing'
    component_scores JSONB DEFAULT '{}'::jsonb,
    is_locked BOOLEAN DEFAULT false,
    is_published BOOLEAN DEFAULT false,
    submitted_at TIMESTAMPTZ,
    approved_at TIMESTAMPTZ,
    published_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(class_id, student_number)
);

CREATE INDEX IF NOT EXISTS idx_sg_class_id ON public.student_grades(class_id);
CREATE INDEX IF NOT EXISTS idx_sg_student_num ON public.student_grades(student_number);

-- 3. Grading Schemes (Per-class formulas)
CREATE TABLE IF NOT EXISTS public.grading_schemes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE,
    faculty_email TEXT NOT NULL,
    period_weights JSONB NOT NULL DEFAULT '{"prelim":30,"midterm":30,"final":40}'::jsonb,
    component_weights JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_locked BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(class_id)
);

CREATE INDEX IF NOT EXISTS idx_gs_class_id ON public.grading_schemes(class_id);

-- 4. Grade Submissions (Official Registrar Lifecycle)
CREATE TABLE IF NOT EXISTS public.grade_submissions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE,
    class_code TEXT NOT NULL,
    section TEXT NOT NULL,
    faculty_email TEXT NOT NULL,
    faculty_name TEXT NOT NULL,
    grading_period TEXT DEFAULT 'Final',
    status TEXT DEFAULT 'Draft', -- 'Draft', 'Submitted', 'Approved', 'Published', 'Returned'
    lock_state BOOLEAN DEFAULT false,
    submitted_at TIMESTAMPTZ,
    reviewed_by TEXT,
    reviewed_at TIMESTAMPTZ,
    approved_at TIMESTAMPTZ,
    published_at TIMESTAMPTZ,
    remarks TEXT,
    created_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(class_id, grading_period)
);

CREATE INDEX IF NOT EXISTS idx_sub_class_period ON public.grade_submissions(class_id, grading_period);

-- 5. Grade Change Requests
CREATE TABLE IF NOT EXISTS public.grade_change_requests (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE,
    class_code TEXT NOT NULL,
    student_number TEXT NOT NULL,
    student_name TEXT NOT NULL,
    faculty_email TEXT NOT NULL,
    faculty_name TEXT NOT NULL,
    original_grade NUMERIC NOT NULL,
    proposed_grade NUMERIC NOT NULL,
    reason TEXT NOT NULL,
    status TEXT DEFAULT 'Pending', -- 'Pending', 'Approved', 'Rejected'
    reviewed_by TEXT,
    reviewed_at TIMESTAMPTZ,
    admin_remarks TEXT,
    created_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_gcr_class_id ON public.grade_change_requests(class_id);
CREATE INDEX IF NOT EXISTS idx_gcr_status ON public.grade_change_requests(status);

-- Enable RLS
ALTER TABLE public.grade_components ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.student_grades ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grading_schemes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grade_submissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grade_change_requests ENABLE ROW LEVEL SECURITY;

-- Allow service role and authenticated users full access
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grade_components' AND policyname = 'gc_auth_read') THEN
        CREATE POLICY "gc_auth_read" ON public.grade_components FOR SELECT USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grade_components' AND policyname = 'gc_auth_modify') THEN
        CREATE POLICY "gc_auth_modify" ON public.grade_components FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'student_grades' AND policyname = 'sg_auth_all') THEN
        CREATE POLICY "sg_auth_all" ON public.student_grades FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grading_schemes' AND policyname = 'gs_auth_all') THEN
        CREATE POLICY "gs_auth_all" ON public.grading_schemes FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grade_submissions' AND policyname = 'sub_auth_all') THEN
        CREATE POLICY "sub_auth_all" ON public.grade_submissions FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grade_change_requests' AND policyname = 'gcr_auth_all') THEN
        CREATE POLICY "gcr_auth_all" ON public.grade_change_requests FOR ALL USING (true);
    END IF;
END $$;
