-- ============================================
-- NPC Connect — 010: Component score storage
-- Run in: Supabase Dashboard → SQL Editor
-- Stores raw component scores per student so the
-- dynamic grading sheets survive page refreshes.
-- ============================================

ALTER TABLE student_grades
    ADD COLUMN IF NOT EXISTS component_scores JSONB DEFAULT '{}';

-- Shape:
-- { "prelim": { "<component_id>": 88, ... },
--   "midterm": {...}, "final": {...} }
