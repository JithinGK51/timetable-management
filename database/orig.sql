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
    institution_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    event_type ENUM('holiday','exam','special_schedule','other') DEFAULT 'other',
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
