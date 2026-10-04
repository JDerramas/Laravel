-- =======================================================
-- Navotas Polytechnic College (NPC) ELMS
-- Comprehensive MySQL / MariaDB Schema (All 35 Tables)
-- =======================================================

CREATE DATABASE IF NOT EXISTS `npc_elms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `npc_elms`;

-- 1. Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(64) PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `full_name` VARCHAR(191) NOT NULL,
    `student_number` VARCHAR(64) DEFAULT NULL,
    `password_hash` VARCHAR(255) DEFAULT 'oauth',
    `role` VARCHAR(32) DEFAULT 'student',
    `program` VARCHAR(64) DEFAULT 'AIS',
    `section` VARCHAR(32) DEFAULT '2A',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Students
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(64) DEFAULT NULL,
    `student_number` VARCHAR(64) NOT NULL UNIQUE,
    `full_name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `program` VARCHAR(64) DEFAULT 'AIS',
    `section` VARCHAR(32) DEFAULT '2A',
    `year_level` INT DEFAULT 2,
    `status` VARCHAR(32) DEFAULT 'Enrolled',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Programs, Sections, Subjects
CREATE TABLE IF NOT EXISTS `programs` (
    `id` VARCHAR(64) PRIMARY KEY,
    `code` VARCHAR(32) NOT NULL UNIQUE,
    `name` VARCHAR(191) NOT NULL,
    `department` VARCHAR(191) DEFAULT 'College of Computer Studies',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sections` (
    `id` VARCHAR(64) PRIMARY KEY,
    `program_id` VARCHAR(64) DEFAULT NULL,
    `section_name` VARCHAR(32) NOT NULL,
    `year_level` INT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subjects` (
    `id` VARCHAR(64) PRIMARY KEY,
    `code` VARCHAR(64) NOT NULL UNIQUE,
    `title` VARCHAR(191) NOT NULL,
    `units` DECIMAL(3,1) DEFAULT 3.0,
    `lecture_hours` INT DEFAULT 3,
    `lab_hours` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Classes
CREATE TABLE IF NOT EXISTS `classes` (
    `id` VARCHAR(64) PRIMARY KEY,
    `code` VARCHAR(64) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `section` VARCHAR(32) DEFAULT '01',
    `instructor` VARCHAR(191) DEFAULT NULL,
    `instructor_email` VARCHAR(191) DEFAULT NULL,
    `room` VARCHAR(64) DEFAULT NULL,
    `schedule_day` VARCHAR(32) DEFAULT 'Monday',
    `start_time` VARCHAR(16) DEFAULT '08:00',
    `end_time` VARCHAR(16) DEFAULT '10:00',
    `units` DECIMAL(3,1) DEFAULT 3.0,
    `max_students` INT DEFAULT 40,
    `school_year` VARCHAR(32) DEFAULT '2026-2027',
    `semester` VARCHAR(64) DEFAULT '1st Semester, 2026-2027',
    `created_by_email` VARCHAR(191) DEFAULT NULL,
    `created_by_name` VARCHAR(191) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Enrollments & Faculty Assignments
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` VARCHAR(64) PRIMARY KEY,
    `student_id` VARCHAR(64) DEFAULT NULL,
    `student_number` VARCHAR(64) NOT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `class_id` VARCHAR(64) NOT NULL,
    `semester` VARCHAR(64) DEFAULT '1st Semester, 2026-2027',
    `school_year` VARCHAR(32) DEFAULT '2026-2027',
    `status` VARCHAR(32) DEFAULT 'Enrolled',
    `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `faculty_assignments` (
    `id` VARCHAR(64) PRIMARY KEY,
    `faculty_email` VARCHAR(191) NOT NULL,
    `faculty_name` VARCHAR(191) NOT NULL,
    `class_id` VARCHAR(64) NOT NULL,
    `academic_year` VARCHAR(32) DEFAULT '2026-2027',
    `semester` VARCHAR(32) DEFAULT '1st Semester',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Grades & Gradebook
CREATE TABLE IF NOT EXISTS `grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(64) DEFAULT NULL,
    `student_number` VARCHAR(64) NOT NULL,
    `subject_code` VARCHAR(64) NOT NULL,
    `description` VARCHAR(191) NOT NULL,
    `units` DECIMAL(3,1) DEFAULT 3.0,
    `grade` DECIMAL(4,2) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Ongoing',
    `semester` VARCHAR(64) DEFAULT '1st Semester, 2026-2027',
    `is_published` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_grades` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `student_number` VARCHAR(64) NOT NULL,
    `prelim_grade` DECIMAL(4,2) DEFAULT NULL,
    `midterm_grade` DECIMAL(4,2) DEFAULT NULL,
    `finals_grade` DECIMAL(4,2) DEFAULT NULL,
    `final_rating` DECIMAL(4,2) DEFAULT NULL,
    `numerical_rating` VARCHAR(16) DEFAULT NULL,
    `remarks` VARCHAR(64) DEFAULT 'Passed',
    `is_published` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `grade_components` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) NOT NULL,
    `component_name` VARCHAR(64) NOT NULL,
    `weight` DECIMAL(5,2) DEFAULT 0.0,
    `max_points` INT DEFAULT 100,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `grade_submissions` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) NOT NULL,
    `faculty_email` VARCHAR(191) NOT NULL,
    `status` VARCHAR(32) DEFAULT 'Pending',
    `submission_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `remarks` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `grading_schemes` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `scheme_name` VARCHAR(64) DEFAULT 'Standard NPC',
    `rules_json` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Attendance System
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `teacher_id` VARCHAR(191) NOT NULL,
    `session_code` VARCHAR(64) NOT NULL UNIQUE,
    `is_active` TINYINT(1) DEFAULT 1,
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(64) DEFAULT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `student_number` VARCHAR(64) DEFAULT NULL,
    `session_code` VARCHAR(64) DEFAULT 'CS301-A',
    `subject_code` VARCHAR(64) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Present',
    `reference_id` VARCHAR(64) DEFAULT NULL,
    `verified_via` VARCHAR(32) DEFAULT 'qr_code',
    `check_in_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `method` VARCHAR(32) DEFAULT 'qr_code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_excuses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(64) NOT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `faculty_email` VARCHAR(191) NOT NULL,
    `subject_code` VARCHAR(64) DEFAULT NULL,
    `reason` TEXT NOT NULL,
    `document_url` TEXT DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Announcements
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `category` VARCHAR(64) DEFAULT 'news',
    `target_audience` VARCHAR(64) DEFAULT 'all',
    `target_program` VARCHAR(64) DEFAULT NULL,
    `target_section` VARCHAR(32) DEFAULT NULL,
    `priority` VARCHAR(32) DEFAULT 'Normal',
    `is_pinned` TINYINT(1) DEFAULT 0,
    `status` VARCHAR(32) DEFAULT 'published',
    `scheduled_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `class_announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `author_name` VARCHAR(191) DEFAULT NULL,
    `author_email` VARCHAR(191) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'published',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Live Sessions & Online Presence
CREATE TABLE IF NOT EXISTS `live_sessions` (
    `id` VARCHAR(64) PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `subject_code` VARCHAR(64) DEFAULT NULL,
    `section` VARCHAR(32) DEFAULT NULL,
    `teacher_email` VARCHAR(191) NOT NULL,
    `teacher_name` VARCHAR(191) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `meet_link` TEXT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `active_students_count` INT DEFAULT 0,
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `live_online_presence` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_email` VARCHAR(191) NOT NULL UNIQUE,
    `user_name` VARCHAR(191) NOT NULL,
    `role` VARCHAR(32) DEFAULT 'student',
    `last_seen` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Materials & Documents
CREATE TABLE IF NOT EXISTS `faculty_materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` VARCHAR(64) DEFAULT NULL,
    `title` VARCHAR(191) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_url` TEXT DEFAULT NULL,
    `file_name` VARCHAR(191) DEFAULT NULL,
    `file_size` VARCHAR(32) DEFAULT NULL,
    `uploaded_by_email` VARCHAR(191) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(191) NOT NULL,
    `category` VARCHAR(64) DEFAULT 'Academic',
    `file_url` TEXT DEFAULT NULL,
    `file_size` VARCHAR(32) DEFAULT '1.2 MB',
    `uploaded_by` VARCHAR(191) DEFAULT 'Admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Student Requests
CREATE TABLE IF NOT EXISTS `document_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(64) NOT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `document_type` VARCHAR(64) NOT NULL,
    `purpose` TEXT DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Pending',
    `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profile_update_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(64) NOT NULL,
    `student_email` VARCHAR(191) NOT NULL,
    `requested_changes` JSON DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `consultation_appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(64) NOT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `faculty_email` VARCHAR(191) NOT NULL,
    `topic` VARCHAR(255) NOT NULL,
    `scheduled_date` DATE DEFAULT NULL,
    `scheduled_time` VARCHAR(32) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'Requested',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_email` VARCHAR(191) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(64) DEFAULT 'info',
    `link` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
    `user_email` VARCHAR(191) PRIMARY KEY,
    `in_app_announcements` TINYINT(1) DEFAULT 1,
    `in_app_live_classes` TINYINT(1) DEFAULT 1,
    `in_app_grades` TINYINT(1) DEFAULT 1,
    `in_app_attendance` TINYINT(1) DEFAULT 1,
    `email_notifications` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. System Settings & Logs
CREATE TABLE IF NOT EXISTS `app_settings` (
    `setting_key` VARCHAR(128) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(500) NOT NULL,
    `user_email` VARCHAR(191) DEFAULT '',
    `ip_address` VARCHAR(64) DEFAULT '127.0.0.1',
    `severity` VARCHAR(32) DEFAULT 'Low',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(64) NOT NULL,
    `table_name` VARCHAR(64) NOT NULL,
    `record_id` VARCHAR(64) DEFAULT NULL,
    `old_data` JSON DEFAULT NULL,
    `new_data` JSON DEFAULT NULL,
    `performed_by` VARCHAR(191) NOT NULL,
    `performed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ai_tool_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_email` VARCHAR(191) NOT NULL,
    `user_role` VARCHAR(32) DEFAULT 'student',
    `tool_name` VARCHAR(64) NOT NULL,
    `input_summary` TEXT DEFAULT NULL,
    `output_summary` TEXT DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'ok',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_conversations` (
    `id` VARCHAR(64) PRIMARY KEY,
    `user_id` VARCHAR(64) NOT NULL,
    `title` VARCHAR(191) DEFAULT 'New Conversation',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` VARCHAR(64) NOT NULL,
    `role` VARCHAR(32) NOT NULL,
    `content` TEXT NOT NULL,
    `sources` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
