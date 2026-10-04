-- 6. Classes
CREATE TABLE IF NOT EXISTS classes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    course_code TEXT NOT NULL,
    course_name TEXT NOT NULL,
    instructor TEXT,
    room TEXT,
    schedule_time TEXT, -- e.g. '09:00 AM - 10:30 AM'
    schedule_days TEXT, -- e.g. 'MWF'
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 7. Student Classes (Enrollment)
CREATE TABLE IF NOT EXISTS student_classes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    student_id TEXT NOT NULL,
    class_id UUID REFERENCES classes(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(student_id, class_id)
);

ALTER TABLE classes ENABLE ROW LEVEL SECURITY;
ALTER TABLE student_classes ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all on classes" ON classes FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on student_classes" ON student_classes FOR ALL USING (true) WITH CHECK (true);

-- Insert sample classes
INSERT INTO classes (id, course_code, course_name, instructor, room, schedule_time, schedule_days) VALUES
('b1111111-1111-1111-1111-111111111111', 'CS101', 'Introduction to Programming', 'Prof. Smith', 'Room 301', '09:00 AM - 10:30 AM', 'MWF'),
('b2222222-2222-2222-2222-222222222222', 'MATH201', 'Advanced Calculus', 'Dr. Jones', 'Room 205', '11:00 AM - 12:30 PM', 'TTh'),
('b3333333-3333-3333-3333-333333333333', 'ENG102', 'Technical Writing', 'Ms. Davis', 'Room 102', '01:00 PM - 02:30 PM', 'MWF')
ON CONFLICT DO NOTHING;

-- Enroll Juan Dela Cruz (251505) in these classes
INSERT INTO student_classes (student_id, class_id) VALUES
('251505', 'b1111111-1111-1111-1111-111111111111'),
('251505', 'b2222222-2222-2222-2222-222222222222'),
('251505', 'b3333333-3333-3333-3333-333333333333')
ON CONFLICT DO NOTHING;

-- Also update setup_database.sql to include these so it is complete for future use.
