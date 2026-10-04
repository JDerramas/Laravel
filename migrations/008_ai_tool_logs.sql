-- ============================================
-- NPC Connect — 008: AI Assistant Audit Logging
-- Run in: Supabase Dashboard → SQL Editor
-- Every AI tool call is logged: who asked, what tool,
-- what data was touched, whether it succeeded.
-- ============================================

CREATE TABLE IF NOT EXISTS ai_tool_logs (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_email TEXT NOT NULL,
    user_role TEXT NOT NULL,
    tool_name TEXT NOT NULL,              -- e.g. 'get_my_grades' | 'llm_answer'
    input_summary TEXT,                   -- trimmed question / params (no secrets)
    output_summary TEXT,                  -- short result digest
    status TEXT NOT NULL DEFAULT 'ok',    -- 'ok' | 'denied' | 'error'
    created_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_ail_user ON ai_tool_logs(user_email);
CREATE INDEX IF NOT EXISTS idx_ail_created ON ai_tool_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ail_tool ON ai_tool_logs(tool_name);

ALTER TABLE ai_tool_logs ENABLE ROW LEVEL SECURITY;

-- Admins can review; inserts happen via service key from ask.php
CREATE POLICY "ail_select_admin"
    ON ai_tool_logs FOR SELECT
    USING (EXISTS (SELECT 1 FROM users u WHERE u.id::text = auth.uid()::text AND u.role IN ('admin')));
