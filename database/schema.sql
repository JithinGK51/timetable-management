-- Teacher Timetable Management System - Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.2+

-- Create database
CREATE DATABASE IF NOT EXISTS ttc_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE ttc_system;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS timetable_versions;
DROP TABLE IF EXISTS timetable_entries;
DROP TABLE IF EXISTS timetables;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS teacher_availability;
DROP TABLE IF EXISTS teacher_subjects;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS sections;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS working_days;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS institutions;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- CORE AUTHENTICATION & ROLES
-- ============================================

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    permissions JSON,
    is_system BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    can_view BOOLEAN DEFAULT FALSE,
    can_create BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    can_export BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_module (role_id, module)
);

CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role_id INT,
    is_super_admin BOOLEAN DEFAULT FALSE,
    status ENUM('active','inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- ============================================
-- INSTITUTION MANAGEMENT
-- ============================================

CREATE TABLE institutions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    type ENUM('school','college','university') NOT NULL,
    address TEXT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    logo VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE
);

-- ============================================
-- CLASS & SECTION MANAGEMENT
-- ============================================

CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    department_id INT NULL,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    class_teacher_id INT NULL,
    capacity INT DEFAULT 30,
    room_number VARCHAR(20),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- ============================================
-- SUBJECT MANAGEMENT
-- ============================================

CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    weekly_hours INT NOT NULL DEFAULT 1,
    type ENUM('theory','lab','both') DEFAULT 'theory',
    description TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- ============================================
-- TEACHER MANAGEMENT
-- ============================================

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    qualification TEXT,
    max_periods_per_day INT DEFAULT 6,
    max_periods_per_week INT DEFAULT 30,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE
);

CREATE TABLE teacher_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);

CREATE TABLE teacher_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    start_time TIME,
    end_time TIME,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_day (teacher_id, day_of_week)
);

-- ============================================
-- TIME SLOT CONFIGURATION
-- ============================================

CREATE TABLE working_days (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    is_working BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_institution_day (institution_id, day_of_week)
);

CREATE TABLE time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    period_number INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_break BOOLEAN DEFAULT FALSE,
    is_lunch BOOLEAN DEFAULT FALSE,
    slot_type VARCHAR(20) DEFAULT 'class',
    display_name VARCHAR(50),
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_institution_period (institution_id, period_number)
);

-- ============================================
-- TIMETABLE MANAGEMENT
-- ============================================

CREATE TABLE timetables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    version INT DEFAULT 1,
    status ENUM('draft','published','archived') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_timetable (institution_id, class_id, section_id, academic_year, version)
);

CREATE TABLE timetable_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timetable_id INT NOT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    time_slot_id INT NOT NULL,
    subject_id INT,
    teacher_id INT,
    is_override BOOLEAN DEFAULT FALSE,
    override_reason TEXT,
    notes TEXT,
    FOREIGN KEY (timetable_id) REFERENCES timetables(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_timetable_slot (timetable_id, day_of_week, time_slot_id)
);

CREATE TABLE timetable_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timetable_id INT NOT NULL,
    version_number INT NOT NULL,
    data JSON NOT NULL,
    modified_by INT NOT NULL,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_id) REFERENCES timetables(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- EVENT & HOLIDAY MANAGEMENT
-- ============================================

CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT,
    title VARCHAR(200) NOT NULL,
    type ENUM('holiday','exam','event','other','sports') DEFAULT 'event',
    start_date DATE NOT NULL,
    end_date DATE,
    description TEXT,
    affects_timetable BOOLEAN DEFAULT FALSE,
    timetable_override JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- SYSTEM SETTINGS
-- ============================================

CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (institution_id, setting_key)
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default super admin role
INSERT INTO roles (name, description, is_system, status) VALUES 
('Super Admin', 'Full system access with all permissions', TRUE, 'active'),
('Timetable Manager', 'Can manage timetables, teachers, and schedules', FALSE, 'active'),
('Teacher Manager', 'Can manage teachers and their assignments', FALSE, 'active'),
('Viewer', 'Read-only access to view timetables and reports', FALSE, 'active');

-- Insert default permissions for Super Admin
INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'dashboard', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'institution', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'class', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'section', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'subject', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'teacher', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'timeslot', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'timetable', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'event', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'export', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'role', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'subadmin', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
SELECT id, 'settings', 1, 1, 1, 1, 1 FROM roles WHERE name = 'Super Admin';

-- Insert default super admin user (password: admin123)
-- Change this password after first login!
INSERT INTO admins (username, password, name, email, role_id, is_super_admin, status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@ttc.com', 1, TRUE, 'active');

-- Insert default global settings
INSERT INTO settings (institution_id, setting_key, setting_value, setting_group) VALUES
(NULL, 'academic_year', '2025-2026', 'general'),
(NULL, 'system_name', 'Teacher Timetable Management System', 'general'),
(NULL, 'timezone', 'Asia/Kolkata', 'general'),
(NULL, 'date_format', 'd-m-Y', 'general'),
(NULL, 'time_format', 'H:i', 'general');

-- ============================================
-- SAMPLE DATA FOR TESTING
-- ============================================

-- Sample Institutions
INSERT INTO institutions (name, type, address, contact_email, contact_phone, status) VALUES
('Springfield High School', 'school', '123 Education Street, Springfield', 'info@springfield.edu', '+1-555-0101', 'active'),
('Riverside College', 'college', '456 University Avenue, Riverside', 'admin@riverside.edu', '+1-555-0202', 'active'),
('Metro University', 'university', '789 Campus Road, Metro City', 'contact@metrouni.edu', '+1-555-0303', 'active');

-- Sample Departments (for colleges/universities)
INSERT INTO departments (institution_id, name, code, description, status) VALUES
(2, 'Computer Science', 'CS', 'Department of Computer Science and Engineering', 'active'),
(2, 'Mathematics', 'MATH', 'Department of Mathematics', 'active'),
(3, 'Engineering', 'ENG', 'School of Engineering', 'active'),
(3, 'Business Administration', 'MBA', 'School of Business', 'active');

-- Sample Classes
INSERT INTO classes (institution_id, department_id, name, code, description, status) VALUES
(1, NULL, 'Grade 10', 'G10', 'Tenth Grade - High School', 'active'),
(1, NULL, 'Grade 11', 'G11', 'Eleventh Grade - High School', 'active'),
(1, NULL, 'Grade 12', 'G12', 'Twelfth Grade - High School', 'active'),
(2, 1, 'First Year CS', 'CS-1', 'First Year Computer Science', 'active'),
(2, 1, 'Second Year CS', 'CS-2', 'Second Year Computer Science', 'active'),
(2, 2, 'First Year Math', 'MATH-1', 'First Year Mathematics', 'active'),
(3, 3, 'BTech Year 1', 'BTECH-1', 'Bachelor of Technology First Year', 'active'),
(3, 4, 'MBA Year 1', 'MBA-1', 'MBA First Year', 'active');

-- Sample Sections
INSERT INTO sections (class_id, name, capacity, room_number, status) VALUES
(1, 'Section A', 30, '101', 'active'),
(1, 'Section B', 30, '102', 'active'),
(2, 'Section A', 25, '201', 'active'),
(2, 'Section B', 25, '202', 'active'),
(3, 'Section A', 20, '301', 'active'),
(4, 'Section A', 40, 'CS-LAB-1', 'active'),
(4, 'Section B', 40, 'CS-LAB-2', 'active'),
(5, 'Section A', 35, 'CS-ROOM-1', 'active'),
(6, 'Section A', 30, 'MATH-ROOM-1', 'active'),
(7, 'Section A', 50, 'ENG-HALL-1', 'active'),
(8, 'Section A', 45, 'MBA-HALL-1', 'active');

-- Sample Subjects
INSERT INTO subjects (class_id, name, code, weekly_hours, type, description, status) VALUES
(1, 'Mathematics', 'MATH10', 6, 'theory', 'Advanced Mathematics', 'active'),
(1, 'Physics', 'PHY10', 5, 'theory', 'Physics Fundamentals', 'active'),
(1, 'Chemistry', 'CHEM10', 5, 'theory', 'Chemistry Basics', 'active'),
(1, 'English', 'ENG10', 4, 'theory', 'English Literature', 'active'),
(2, 'Mathematics', 'MATH11', 6, 'theory', 'Advanced Calculus', 'active'),
(2, 'Physics', 'PHY11', 5, 'theory', 'Advanced Physics', 'active'),
(2, 'Computer Science', 'CS11', 4, 'both', 'Introduction to Programming', 'active'),
(4, 'Programming Fundamentals', 'CS101', 6, 'both', 'Introduction to Programming', 'active'),
(4, 'Data Structures', 'CS102', 5, 'theory', 'Data Structures and Algorithms', 'active'),
(4, 'Database Systems', 'CS103', 4, 'both', 'Database Management Systems', 'active'),
(5, 'Object Oriented Programming', 'CS201', 6, 'both', 'OOP Concepts', 'active'),
(5, 'Web Development', 'CS202', 4, 'lab', 'Web Technologies', 'active'),
(7, 'Engineering Mathematics', 'ENG101', 6, 'theory', 'Mathematics for Engineers', 'active'),
(7, 'Mechanics', 'ENG102', 5, 'theory', 'Engineering Mechanics', 'active'),
(8, 'Management Principles', 'MBA101', 5, 'theory', 'Principles of Management', 'active'),
(8, 'Marketing', 'MBA102', 4, 'theory', 'Marketing Management', 'active');

-- Sample Teachers
INSERT INTO teachers (institution_id, employee_id, name, email, phone, qualification, max_periods_per_day, max_periods_per_week, status) VALUES
(1, 'T001', 'John Smith', 'john.smith@springfield.edu', '+1-555-1001', 'M.Ed, Mathematics', 6, 30, 'active'),
(1, 'T002', 'Sarah Johnson', 'sarah.j@springfield.edu', '+1-555-1002', 'M.Sc, Physics', 6, 30, 'active'),
(1, 'T003', 'Michael Brown', 'michael.b@springfield.edu', '+1-555-1003', 'Ph.D, Chemistry', 5, 25, 'active'),
(1, 'T004', 'Emily Davis', 'emily.d@springfield.edu', '+1-555-1004', 'M.A, English Literature', 5, 25, 'active'),
(2, 'T101', 'Dr. Robert Wilson', 'r.wilson@riverside.edu', '+1-555-2001', 'Ph.D, Computer Science', 6, 30, 'active'),
(2, 'T102', 'Prof. Lisa Anderson', 'l.anderson@riverside.edu', '+1-555-2002', 'M.Tech, Software Engineering', 6, 30, 'active'),
(2, 'T103', 'Dr. James Miller', 'j.miller@riverside.edu', '+1-555-2003', 'Ph.D, Mathematics', 5, 25, 'active'),
(3, 'T201', 'Prof. David Garcia', 'd.garcia@metrouni.edu', '+1-555-3001', 'Ph.D, Mechanical Engineering', 6, 30, 'active'),
(3, 'T202', 'Dr. Jennifer Lee', 'j.lee@metrouni.edu', '+1-555-3002', 'Ph.D, Business Administration', 5, 25, 'active'),
(3, 'T203', 'Prof. William Taylor', 'w.taylor@metrouni.edu', '+1-555-3003', 'MBA, Marketing', 5, 25, 'active');

-- Sample Teacher-Subject Assignments
INSERT INTO teacher_subjects (teacher_id, subject_id, is_primary) VALUES
(1, 1, TRUE), (1, 5, FALSE),
(2, 2, TRUE), (2, 6, FALSE),
(3, 3, TRUE),
(4, 4, TRUE),
(5, 8, TRUE), (5, 9, FALSE),
(6, 10, TRUE), (6, 11, FALSE),
(7, 12, TRUE),
(8, 13, TRUE), (8, 14, FALSE),
(9, 15, TRUE),
(10, 16, TRUE);

-- Sample Teacher Availability (all available by default)
INSERT INTO teacher_availability (teacher_id, day_of_week, is_available) VALUES
(1, 'monday', TRUE), (1, 'tuesday', TRUE), (1, 'wednesday', TRUE), (1, 'thursday', TRUE), (1, 'friday', TRUE),
(2, 'monday', TRUE), (2, 'tuesday', TRUE), (2, 'wednesday', TRUE), (2, 'thursday', TRUE), (2, 'friday', TRUE),
(3, 'monday', TRUE), (3, 'tuesday', TRUE), (3, 'wednesday', TRUE), (3, 'thursday', TRUE), (3, 'friday', TRUE),
(4, 'monday', TRUE), (4, 'tuesday', TRUE), (4, 'wednesday', TRUE), (4, 'thursday', TRUE), (4, 'friday', TRUE),
(5, 'monday', TRUE), (5, 'tuesday', TRUE), (5, 'wednesday', TRUE), (5, 'thursday', TRUE), (5, 'friday', TRUE),
(6, 'monday', TRUE), (6, 'tuesday', TRUE), (6, 'wednesday', TRUE), (6, 'thursday', TRUE), (6, 'friday', TRUE),
(7, 'monday', TRUE), (7, 'tuesday', TRUE), (7, 'wednesday', TRUE), (7, 'thursday', TRUE), (7, 'friday', TRUE),
(8, 'monday', TRUE), (8, 'tuesday', TRUE), (8, 'wednesday', TRUE), (8, 'thursday', TRUE), (8, 'friday', TRUE),
(9, 'monday', TRUE), (9, 'tuesday', TRUE), (9, 'wednesday', TRUE), (9, 'thursday', TRUE), (9, 'friday', TRUE),
(10, 'monday', TRUE), (10, 'tuesday', TRUE), (10, 'wednesday', TRUE), (10, 'thursday', TRUE), (10, 'friday', TRUE);

-- Sample Working Days for Institutions
INSERT INTO working_days (institution_id, day_of_week, is_working) VALUES
(1, 'monday', TRUE), (1, 'tuesday', TRUE), (1, 'wednesday', TRUE), (1, 'thursday', TRUE), (1, 'friday', TRUE), (1, 'saturday', FALSE),
(2, 'monday', TRUE), (2, 'tuesday', TRUE), (2, 'wednesday', TRUE), (2, 'thursday', TRUE), (2, 'friday', TRUE), (2, 'saturday', TRUE),
(3, 'monday', TRUE), (3, 'tuesday', TRUE), (3, 'wednesday', TRUE), (3, 'thursday', TRUE), (3, 'friday', TRUE), (3, 'saturday', FALSE);

-- Sample Time Slots for Institution 1 (School)
INSERT INTO time_slots (institution_id, period_number, start_time, end_time, is_break, is_lunch, slot_type, display_name) VALUES
(1, 1, '08:00:00', '08:45:00', FALSE, FALSE, 'class', 'Period 1'),
(1, 2, '08:45:00', '09:30:00', FALSE, FALSE, 'class', 'Period 2'),
(1, 3, '09:30:00', '10:15:00', FALSE, FALSE, 'class', 'Period 3'),
(1, 4, '10:15:00', '10:30:00', TRUE, FALSE, 'break', 'Short Break'),
(1, 5, '10:30:00', '11:15:00', FALSE, FALSE, 'class', 'Period 4'),
(1, 6, '11:15:00', '12:00:00', FALSE, FALSE, 'class', 'Period 5'),
(1, 7, '12:00:00', '12:45:00', FALSE, TRUE, 'lunch', 'Lunch Break'),
(1, 8, '12:45:00', '13:30:00', FALSE, FALSE, 'class', 'Period 6'),
(1, 9, '13:30:00', '14:15:00', FALSE, FALSE, 'class', 'Period 7'),
(1, 10, '14:15:00', '15:00:00', FALSE, FALSE, 'class', 'Period 8');

-- Sample Time Slots for Institution 2 (College)
INSERT INTO time_slots (institution_id, period_number, start_time, end_time, is_break, is_lunch, slot_type, display_name) VALUES
(2, 1, '09:00:00', '10:00:00', FALSE, FALSE, 'class', 'Period 1'),
(2, 2, '10:00:00', '11:00:00', FALSE, FALSE, 'class', 'Period 2'),
(2, 3, '11:00:00', '12:00:00', FALSE, FALSE, 'class', 'Period 3'),
(2, 4, '12:00:00', '13:00:00', FALSE, TRUE, 'lunch', 'Lunch Break'),
(2, 5, '13:00:00', '14:00:00', FALSE, FALSE, 'class', 'Period 4'),
(2, 6, '14:00:00', '15:00:00', FALSE, FALSE, 'class', 'Period 5'),
(2, 7, '15:00:00', '16:00:00', FALSE, FALSE, 'class', 'Period 6');

-- Sample Events/Holidays
INSERT INTO events (institution_id, title, type, start_date, end_date, description, affects_timetable, created_by) VALUES
(1, 'Summer Vacation', 'holiday', '2025-06-01', '2025-08-15', 'Summer break for all students', TRUE, 1),
(1, 'Annual Day', 'event', '2025-04-15', '2025-04-15', 'School Annual Day Celebration', FALSE, 1),
(2, 'Mid-Term Exams', 'exam', '2025-03-15', '2025-03-25', 'Mid-term examination period', TRUE, 1),
(2, 'Spring Break', 'holiday', '2025-04-10', '2025-04-14', 'Spring break', TRUE, 1),
(3, 'Convocation', 'event', '2025-05-20', '2025-05-20', 'Graduation ceremony', FALSE, 1),
(NULL, 'National Holiday', 'holiday', '2025-01-26', '2025-01-26', 'Republic Day', TRUE, 1);

-- Sample Sub-Admin Users
INSERT INTO admins (username, password, name, email, role_id, is_super_admin, status) VALUES
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Timetable Manager', 'manager1@ttc.com', 2, FALSE, 'active'),
('teacher_mgr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Teacher Coordinator', 'teachermgr@ttc.com', 3, FALSE, 'active'),
('viewer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Report Viewer', 'viewer1@ttc.com', 4, FALSE, 'active');

-- Sample Permissions for other roles
INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export)
VALUES 
(2, 'timetable', 1, 1, 1, 0, 1),
(2, 'teacher', 1, 1, 1, 0, 0),
(2, 'timeslot', 1, 1, 1, 0, 0),
(2, 'export', 1, 0, 0, 0, 1),
(3, 'teacher', 1, 1, 1, 0, 0),
(3, 'subject', 1, 0, 0, 0, 0),
(4, 'timetable', 1, 0, 0, 0, 1),
(4, 'export', 1, 0, 0, 0, 1);
