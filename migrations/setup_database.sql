-- ============================================
-- NPC Connect — Complete Supabase Database Schema
-- Run this in: Supabase Dashboard → SQL Editor
-- ============================================

-- 1. Users (Login & Directory System)
CREATE TABLE IF NOT EXISTS users (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    student_number TEXT,
    password_hash TEXT DEFAULT 'oauth',
    role TEXT DEFAULT 'student', -- 'student', 'faculty', 'admin'
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 2. Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'news', -- 'news', 'academic', 'emergency'
    audience TEXT[] DEFAULT ARRAY['students','faculty'],
    department TEXT DEFAULT 'all',
    status TEXT DEFAULT 'published', -- 'draft', 'published', 'archived'
    scheduled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- 3. Classes / Academic Schedule
CREATE TABLE IF NOT EXISTS classes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    code TEXT NOT NULL,
    title TEXT NOT NULL,
    section TEXT DEFAULT '01',
    instructor TEXT,
    room TEXT,
    schedule_day TEXT, -- 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
    start_time TEXT, -- '08:00', '10:00', etc.
    end_time TEXT,
    units NUMERIC DEFAULT 3.0,
    max_students INT DEFAULT 40,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 4. Attendance Records
CREATE TABLE IF NOT EXISTS attendance_records (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    student_id TEXT NOT NULL,
    student_name TEXT,
    student_number TEXT,
    session_code TEXT DEFAULT 'CS301-A',
    check_in_at TIMESTAMPTZ DEFAULT now(),
    method TEXT DEFAULT 'qr_code' -- 'qr_code', 'manual'
);

-- 5. Academic Records / Grades
CREATE TABLE IF NOT EXISTS grades (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    student_id TEXT NOT NULL,
    student_number TEXT,
    subject_code TEXT NOT NULL,
    description TEXT NOT NULL,
    units NUMERIC DEFAULT 3.0,
    grade NUMERIC, -- e.g. 1.25, 1.50
    status TEXT DEFAULT 'Ongoing', -- 'Passed', 'Failed', 'Ongoing', 'Incomplete'
    semester TEXT DEFAULT '1st Semester, 2026-2027',
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 6. Documents & Guidelines
CREATE TABLE IF NOT EXISTS documents (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'Academic', -- 'Academic', 'Handbook', 'Financial', 'Form'
    file_url TEXT,
    file_size TEXT DEFAULT '1.2 MB',
    uploaded_by TEXT,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 7. Chat Conversations
CREATE TABLE IF NOT EXISTS chat_conversations (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id TEXT NOT NULL,
    title TEXT DEFAULT 'New Conversation',
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- 8. Chat Messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    conversation_id UUID REFERENCES chat_conversations(id) ON DELETE CASCADE,
    role TEXT NOT NULL, -- 'user', 'assistant'
    content TEXT NOT NULL,
    sources JSONB,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 9. Security & System Activity Logs
CREATE TABLE IF NOT EXISTS security_logs (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    event TEXT NOT NULL,
    user_email TEXT,
    ip_address TEXT DEFAULT '127.0.0.1',
    severity TEXT DEFAULT 'Low', -- 'Low', 'Medium', 'High'
    created_at TIMESTAMPTZ DEFAULT now()
);



-- Enable RLS for all tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE announcements ENABLE ROW LEVEL SECURITY;
ALTER TABLE classes ENABLE ROW LEVEL SECURITY;
ALTER TABLE attendance_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE grades ENABLE ROW LEVEL SECURITY;
ALTER TABLE documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE chat_conversations ENABLE ROW LEVEL SECURITY;
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE security_logs ENABLE ROW LEVEL SECURITY;

-- Allow all operations for anon role (Development / Demo Mode)
CREATE POLICY "Allow all on users" ON users FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on announcements" ON announcements FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on classes" ON classes FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on attendance_records" ON attendance_records FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on grades" ON grades FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on documents" ON documents FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on chat_conversations" ON chat_conversations FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on chat_messages" ON chat_messages FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on security_logs" ON security_logs FOR ALL USING (true) WITH CHECK (true);

-- Insert Default Administrator
INSERT INTO users (email, full_name, student_number, role, password_hash) VALUES
('admin@navotaspolytechniccollege.edu.ph', 'NPC Administrator', '1', 'admin', 'oauth'),
('jderramas251505@navotaspolytechniccollege.edu.ph', 'Administrator (J. Derramas)', '251505', 'admin', 'oauth')
ON CONFLICT (email) DO UPDATE SET role = 'admin';

