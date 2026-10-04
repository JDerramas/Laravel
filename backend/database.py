import sqlite3
import os
import json
import uuid
from datetime import datetime

root_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
db_path = os.path.join(root_dir, 'app.db') if os.path.exists(os.path.join(root_dir, 'app.db')) else os.path.join(os.path.dirname(os.path.abspath(__file__)), 'app.db')

def dict_factory(cursor, row):
    d = {}
    for idx, col in enumerate(cursor.description):
        d[col[0]] = row[idx]
    return d

def get_db():
    conn = sqlite3.connect(db_path)
    conn.row_factory = dict_factory
    return conn

def init_db():
    conn = get_db()
    c = conn.cursor()
    
    # 1. Announcements
    c.execute("""
    CREATE TABLE IF NOT EXISTS announcements (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        body TEXT NOT NULL,
        category TEXT NOT NULL DEFAULT 'news',
        audience TEXT DEFAULT '["students","faculty"]',
        department TEXT DEFAULT 'all',
        status TEXT DEFAULT 'published',
        scheduled_at TEXT,
        created_at TEXT,
        updated_at TEXT
    )
    """)
    
    # 2. Chat Conversations
    c.execute("""
    CREATE TABLE IF NOT EXISTS chat_conversations (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL,
        title TEXT DEFAULT 'New Conversation',
        created_at TEXT,
        updated_at TEXT
    )
    """)
    
    # 3. Chat Messages
    c.execute("""
    CREATE TABLE IF NOT EXISTS chat_messages (
        id TEXT PRIMARY KEY,
        conversation_id TEXT REFERENCES chat_conversations(id) ON DELETE CASCADE,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        sources TEXT,
        created_at TEXT
    )
    """)
    
    # 4. Attendance Records
    c.execute("""
    CREATE TABLE IF NOT EXISTS attendance_records (
        id TEXT PRIMARY KEY,
        student_id TEXT NOT NULL,
        student_name TEXT,
        check_in_at TEXT,
        method TEXT DEFAULT 'qr_code'
    )
    """)
    
    # 5. Classes
    c.execute("""
    CREATE TABLE IF NOT EXISTS classes (
        id TEXT PRIMARY KEY,
        course_code TEXT NOT NULL,
        course_name TEXT NOT NULL,
        instructor TEXT,
        room TEXT,
        schedule_time TEXT,
        schedule_days TEXT,
        created_at TEXT
    )
    """)
    
    # 6. Student Classes
    c.execute("""
    CREATE TABLE IF NOT EXISTS student_classes (
        id TEXT PRIMARY KEY,
        student_id TEXT NOT NULL,
        class_id TEXT REFERENCES classes(id) ON DELETE CASCADE,
        created_at TEXT,
        UNIQUE(student_id, class_id)
    )
    """)
    
    # Insert sample data if empty
    c.execute("SELECT COUNT(*) FROM announcements")
    if c.fetchone()['COUNT(*)'] == 0:
        now = datetime.now().isoformat()
        samples = [
            (str(uuid.uuid4()), 'Midterm Exam Schedule Released', 'Official schedule for midterm examinations is now posted.', 'emergency', '["students","faculty"]', 'all', 'published', None, now, now),
            (str(uuid.uuid4()), 'Library Maintenance Notice', 'Digital archives remain online 24/7 during library system maintenance this week.', 'academic', '["students","faculty"]', 'all', 'published', None, now, now),
            (str(uuid.uuid4()), 'Tech Symposium 2026', 'Join the annual student technology symposium featuring industry keynotes.', 'news', '["students","faculty","staff"]', 'all', 'published', None, now, now)
        ]
        c.executemany("INSERT INTO announcements VALUES (?,?,?,?,?,?,?,?,?,?)", samples)
        
    c.execute("SELECT COUNT(*) FROM classes")
    if c.fetchone()['COUNT(*)'] == 0:
        now = datetime.now().isoformat()
        c1 = str(uuid.uuid4())
        c2 = str(uuid.uuid4())
        c3 = str(uuid.uuid4())
        classes_samples = [
            (c1, 'CS101', 'Introduction to Programming', 'Prof. Smith', 'Room 301', '09:00 AM - 10:30 AM', 'MWF', now),
            (c2, 'MATH201', 'Advanced Calculus', 'Dr. Jones', 'Room 205', '11:00 AM - 12:30 PM', 'TTh', now),
            (c3, 'ENG102', 'Technical Writing', 'Ms. Davis', 'Room 102', '01:00 PM - 02:30 PM', 'MWF', now)
        ]
        c.executemany("INSERT INTO classes VALUES (?,?,?,?,?,?,?,?)", classes_samples)
        
        # Enroll default student
        enrollments = [
            (str(uuid.uuid4()), '251505', c1, now),
            (str(uuid.uuid4()), '251505', c2, now),
            (str(uuid.uuid4()), '251505', c3, now)
        ]
        c.executemany("INSERT INTO student_classes VALUES (?,?,?,?)", enrollments)
        
    conn.commit()
    conn.close()
    print("Database initialized successfully at app.db")

if __name__ == "__main__":
    init_db()
