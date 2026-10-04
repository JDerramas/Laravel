-- =======================================================
-- Navotas Polytechnic College (NPC) ELMS
-- Complete MySQL / MariaDB Database Schema & Seed Data
-- =======================================================

CREATE DATABASE IF NOT EXISTS `npc_elms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `npc_elms`;

-- 1. Users (Authentication & Directory)
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(64) PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `full_name` VARCHAR(191) NOT NULL,
    `student_number` VARCHAR(64) DEFAULT NULL,
    `password_hash` VARCHAR(255) DEFAULT 'oauth',
    `role` ENUM('student', 'faculty', 'teacher', 'admin', 'registrar') DEFAULT 'student',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Students Profile
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

-- 3. Classes / Academic Schedule
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
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Attendance Records
CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(64) DEFAULT NULL,
    `student_name` VARCHAR(191) DEFAULT NULL,
    `student_number` VARCHAR(64) DEFAULT NULL,
    `session_code` VARCHAR(64) DEFAULT 'CS301-A',
    `subject_code` VARCHAR(64) DEFAULT NULL,
    `status` ENUM('Present', 'Late', 'Absent', 'Excused') DEFAULT 'Present',
    `check_in_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `method` VARCHAR(32) DEFAULT 'qr_code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Grades & Academic Records
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

-- 6. Announcements
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

-- 7. Live Online Sessions (GMeet & Virtual Rooms)
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

-- 8. Personal Notifications
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

-- 9. Notification Preferences
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
    `user_email` VARCHAR(191) PRIMARY KEY,
    `in_app_announcements` TINYINT(1) DEFAULT 1,
    `in_app_live_classes` TINYINT(1) DEFAULT 1,
    `in_app_grades` TINYINT(1) DEFAULT 1,
    `in_app_attendance` TINYINT(1) DEFAULT 1,
    `email_notifications` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Documents & Circulars
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(191) NOT NULL,
    `category` VARCHAR(64) DEFAULT 'Academic',
    `file_url` TEXT DEFAULT NULL,
    `file_size` VARCHAR(32) DEFAULT '1.2 MB',
    `uploaded_by` VARCHAR(191) DEFAULT 'Admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. AI Tool Usage & Moderation Audit Logs
CREATE TABLE IF NOT EXISTS `ai_tool_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_email` VARCHAR(191) NOT NULL,
    `role` VARCHAR(32) DEFAULT 'student',
    `tool_name` VARCHAR(64) NOT NULL,
    `query_text` TEXT DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT 'ok',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- SEED DEFAULT ACCOUNTS & DATA
-- =======================================================

INSERT INTO `users` (`id`, `email`, `full_name`, `student_number`, `role`, `is_active`) VALUES
('usr-admin-01', 'admin@navotaspolytechniccollege.edu.ph', 'Administrator', 'ADMIN-001', 'admin', 1),
('usr-faculty-01', 'jderramas251505@navotaspolytechniccollege.edu.ph', 'JILO DERRAMAS', 'FAC-001', 'teacher', 1),
('usr-student-01', 'student2024001@navotaspolytechniccollege.edu.ph', 'Lovi Student', '2024-00192', 'student', 1)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `role` = VALUES(`role`);

INSERT INTO `students` (`user_id`, `student_number`, `full_name`, `email`, `program`, `section`, `year_level`, `status`) VALUES
('usr-student-01', '2024-00192', 'Lovi Student', 'student2024001@navotaspolytechniccollege.edu.ph', 'AIS', '2A', 2, 'Enrolled')
ON DUPLICATE KEY UPDATE `program` = VALUES(`program`), `section` = VALUES(`section`);

INSERT INTO `announcements` (`title`, `body`, `category`, `target_audience`, `priority`, `is_pinned`, `status`) VALUES
('Welcome to 1st Semester A.Y. 2026-2027', 'Classes officially begin on September 14, 2026. Please check your assigned room and schedule in the Student Portal.', 'academic', 'all', 'Urgent', 1, 'published'),
('Dynamic QR Attendance Active', 'Please make sure to scan the dynamic QR code within the first 5 minutes of each class to be marked Present.', 'news', 'students', 'Normal', 0, 'published');

INSERT INTO `grades` (`student_id`, `student_number`, `subject_code`, `description`, `units`, `grade`, `status`, `semester`, `is_published`) VALUES
('usr-student-01', '2024-00192', 'IT101', 'Introduction to Computing', 3.0, 1.25, 'Passed', '1st Semester, 2026-2027', 1),
('usr-student-01', '2024-00192', 'IS201', 'Database Management Systems', 3.0, 1.50, 'Passed', '1st Semester, 2026-2027', 1),
('usr-student-01', '2024-00192', 'GE104', 'Mathematics in the Modern World', 3.0, 1.75, 'Passed', '1st Semester, 2026-2027', 1);

INSERT INTO `classes` (`id`, `code`, `title`, `section`, `instructor`, `instructor_email`, `room`, `schedule_day`, `start_time`, `end_time`, `units`, `max_students`) VALUES
('cls-01', 'IS201', 'Database Management Systems', 'AIS-2A', 'JILO DERRAMAS', 'jderramas251505@navotaspolytechniccollege.edu.ph', 'Room 304', 'Monday', '08:00', '10:00', 3.0, 40),
('cls-02', 'IT102', 'Computer Programming 1', 'AIS-2A', 'JILO DERRAMAS', 'jderramas251505@navotaspolytechniccollege.edu.ph', 'Lab 2', 'Wednesday', '10:00', '12:00', 3.0, 40),
('cls-03', 'GE104', 'Mathematics in the Modern World', 'AIS-2A', 'Prof. Santos', 'santos@navotaspolytechniccollege.edu.ph', 'Room 201', 'Friday', '13:00', '15:00', 3.0, 40);
