<?php
/**
 * Core Functions
 * Teacher Timetable Management System
 */

require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function getCurrentAdmin() {
    if (!isLoggedIn()) return null;
    
    return dbFetch(
        "SELECT a.*, r.name as role_name 
         FROM admins a 
         LEFT JOIN roles r ON a.role_id = r.id 
         WHERE a.id = ?",
        [$_SESSION['admin_id']]
    );
}

function isSuperAdmin() {
    $admin = getCurrentAdmin();
    return $admin && $admin['is_super_admin'];
}

function hasPermission($module, $action = 'view') {
    if (isSuperAdmin()) return true;
    
    $admin = getCurrentAdmin();
    if (!$admin || !$admin['role_id']) return false;
    
    $permissionMap = [
        'view' => 'can_view',
        'create' => 'can_create',
        'edit' => 'can_edit',
        'delete' => 'can_delete',
        'export' => 'can_export'
    ];
    
    $column = $permissionMap[$action] ?? 'can_view';
    
    $permission = dbFetch(
        "SELECT {$column} FROM role_permissions WHERE role_id = ? AND module = ?",
        [$admin['role_id'], $module]
    );
    
    return $permission && $permission[$column];
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /ttc/modules/auth/login.php');
        exit;
    }
}

function requirePermission($module, $action = 'view') {
    requireAuth();
    if (!hasPermission($module, $action)) {
        // Store alert in session and redirect to dashboard
        $_SESSION['alert'] = [
            'message' => 'You do not have permission to access this feature.',
            'type' => 'error'
        ];
        if (!headers_sent()) {
            header('Location: /ttc/modules/dashboard/index.php');
        } else {
            echo '<script>window.location.href = "/ttc/modules/dashboard/index.php";</script>';
        }
        exit;
    }
}

function login($username, $password, $remember = false) {
    $admin = dbFetch(
        "SELECT * FROM admins WHERE username = ? AND status = 'active'",
        [$username]
    );
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['is_super_admin'] = $admin['is_super_admin'];
        $_SESSION['role_id'] = $admin['role_id'];
        $_SESSION['institution_id'] = $admin['institution_id'];
        
        // Update last login
        dbQuery("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
        
        // Remember me functionality
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            // Store token in database (simplified - in production use separate table)
        }
        
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: /ttc/modules/auth/login.php');
    exit;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        // Fallback if headers already sent
        echo "<script>window.location.href='$url';</script>";
        echo "<meta http-equiv='refresh' content='0;url=$url'>";
        exit;
    }
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function formatDate($date, $format = null) {
    if (!$format) {
        $setting = dbFetch("SELECT setting_value FROM settings WHERE setting_key = 'date_format' AND institution_id IS NULL");
        $format = $setting ? $setting['setting_value'] : 'd-m-Y';
    }
    return date($format, strtotime($date));
}

function formatTime($time, $format = null) {
    if (!$format) {
        $setting = dbFetch("SELECT setting_value FROM settings WHERE setting_key = 'time_format' AND institution_id IS NULL");
        $format = $setting ? $setting['setting_value'] : 'H:i';
    }
    return date($format, strtotime($time));
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

function getDayName($day) {
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday'
    ];
    return $days[$day] ?? $day;
}

function getDaysOfWeek() {
    return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
}

// ============================================
// DASHBOARD DATA FUNCTIONS
// ============================================

function getDashboardStats() {
    // Check if user is sub-admin (has institution_id but not super admin)
    $institutionFilter = '';
    $params = [];
    
    if (isset($_SESSION['institution_id']) && $_SESSION['institution_id'] && !isSuperAdmin()) {
        $institutionFilter = " AND institution_id = ?";
        $params = [$_SESSION['institution_id']];
    }
    
    return [
        'total_teachers' => dbFetch("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'" . $institutionFilter, $params)['count'] ?? 0,
        'active_teachers' => dbFetch("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'" . $institutionFilter, $params)['count'] ?? 0,
        'total_classes' => dbFetch("SELECT COUNT(*) as count FROM classes WHERE status = 'active'" . $institutionFilter, $params)['count'] ?? 0,
        'total_sections' => dbFetch("SELECT COUNT(*) as count FROM sections s JOIN classes c ON s.class_id = c.id WHERE s.status = 'active'" . ($institutionFilter ? " AND c.institution_id = ?" : ""), $params)['count'] ?? 0,
        'total_subjects' => dbFetch("SELECT COUNT(*) as count FROM subjects WHERE status = 'active'" . $institutionFilter, $params)['count'] ?? 0,
        'timetables_generated' => dbFetch("SELECT COUNT(*) as count FROM timetables WHERE 1=1" . $institutionFilter, $params)['count'] ?? 0,
        'timetables_published' => dbFetch("SELECT COUNT(*) as count FROM timetables WHERE status = 'published'" . $institutionFilter, $params)['count'] ?? 0
    ];
}

function getRecentTimetables($limit = 5) {
    return dbFetchAll(
        "SELECT t.*, i.name as institution_name, c.name as class_name, s.name as section_name
         FROM timetables t
         JOIN institutions i ON t.institution_id = i.id
         JOIN classes c ON t.class_id = c.id
         JOIN sections s ON t.section_id = s.id
         ORDER BY t.updated_at DESC
         LIMIT ?",
        [$limit]
    );
}

function getTodaysSchedule() {
    $today = strtolower(date('l'));
    return dbFetchAll(
        "SELECT te.*, ts.start_time, ts.end_time, sub.name as subject_name, 
                teach.name as teacher_name, c.name as class_name, sec.name as section_name
         FROM timetable_entries te
         JOIN timetables t ON te.timetable_id = t.id
         JOIN time_slots ts ON te.time_slot_id = ts.id
         JOIN subjects sub ON te.subject_id = sub.id
         JOIN teachers teach ON te.teacher_id = teach.id
         JOIN classes c ON t.class_id = c.id
         JOIN sections sec ON t.section_id = sec.id
         WHERE te.day_of_week = ? AND t.status = 'published'
         ORDER BY ts.start_time",
        [$today]
    );
}

// ============================================
// INSTITUTION FUNCTIONS
// ============================================

function getInstitutions($status = null) {
    $sql = "SELECT * FROM institutions";
    $params = [];
    
    if ($status) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY name";
    return dbFetchAll($sql, $params);
}

function getInstitutionById($id) {
    return dbFetch("SELECT * FROM institutions WHERE id = ?", [$id]);
}

// ============================================
// CLASS & SECTION FUNCTIONS
// ============================================

function getClassesByInstitution($institutionId, $status = 'active') {
    return dbFetchAll(
        "SELECT c.*, d.name as department_name 
         FROM classes c 
         LEFT JOIN departments d ON c.department_id = d.id 
         WHERE c.institution_id = ? AND c.status = ?
         ORDER BY c.name",
        [$institutionId, $status]
    );
}

function getSectionsByClass($classId, $status = 'active') {
    return dbFetchAll(
        "SELECT s.*, t.name as class_teacher_name 
         FROM sections s 
         LEFT JOIN teachers t ON s.class_teacher_id = t.id 
         WHERE s.class_id = ? AND s.status = ?
         ORDER BY s.name",
        [$classId, $status]
    );
}

// ============================================
// SUBJECT FUNCTIONS
// ============================================

function getSubjectsByClass($classId, $status = 'active') {
    return dbFetchAll(
        "SELECT * FROM subjects WHERE class_id = ? AND status = ? ORDER BY name",
        [$classId, $status]
    );
}

// ============================================
// TEACHER FUNCTIONS
// ============================================

function getTeachersByInstitution($institutionId, $status = 'active') {
    return dbFetchAll(
        "SELECT t.*, 
                (SELECT COUNT(*) FROM teacher_subjects ts WHERE ts.teacher_id = t.id) as subject_count,
                (SELECT COUNT(*) FROM timetable_entries te WHERE te.teacher_id = t.id) as assignment_count
         FROM teachers t 
         WHERE t.institution_id = ? AND t.status = ?
         ORDER BY t.name",
        [$institutionId, $status]
    );
}

function getTeacherSubjects($teacherId) {
    return dbFetchAll(
        "SELECT s.* FROM subjects s
         JOIN teacher_subjects ts ON s.id = ts.subject_id
         WHERE ts.teacher_id = ?",
        [$teacherId]
    );
}

function getTeacherAvailability($teacherId) {
    return dbFetchAll(
        "SELECT * FROM teacher_availability WHERE teacher_id = ? ORDER BY FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')",
        [$teacherId]
    );
}

// ============================================
// TIME SLOT FUNCTIONS
// ============================================

function getTimeSlotsByInstitution($institutionId) {
    return dbFetchAll(
        "SELECT * FROM time_slots WHERE institution_id = ? ORDER BY period_number",
        [$institutionId]
    );
}

function getWorkingDaysByInstitution($institutionId) {
    return dbFetchAll(
        "SELECT * FROM working_days WHERE institution_id = ? 
         ORDER BY FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')",
        [$institutionId]
    );
}

// ============================================
// TIMETABLE FUNCTIONS
// ============================================

function getTimetableById($id) {
    return dbFetch(
        "SELECT t.*, i.name as institution_name, c.name as class_name, s.name as section_name
         FROM timetables t
         JOIN institutions i ON t.institution_id = i.id
         JOIN classes c ON t.class_id = c.id
         JOIN sections s ON t.section_id = s.id
         WHERE t.id = ?",
        [$id]
    );
}

function getTimetableEntries($timetableId) {
    return dbFetchAll(
        "SELECT te.*, ts.period_number, ts.start_time, ts.end_time, ts.is_break, ts.is_lunch,
                sub.name as subject_name, sub.code as subject_code, sub.type as subject_type,
                teach.name as teacher_name, teach.employee_id
         FROM timetable_entries te
         JOIN time_slots ts ON te.time_slot_id = ts.id
         LEFT JOIN subjects sub ON te.subject_id = sub.id
         LEFT JOIN teachers teach ON te.teacher_id = teach.id
         WHERE te.timetable_id = ?
         ORDER BY FIELD(te.day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday'), 
                  ts.period_number",
        [$timetableId]
    );
}

function checkTeacherConflict($teacherId, $dayOfWeek, $timeSlotId, $excludeTimetableId = null) {
    $sql = "SELECT COUNT(*) as count FROM timetable_entries te
            JOIN timetables t ON te.timetable_id = t.id
            WHERE te.teacher_id = ? AND te.day_of_week = ? AND te.time_slot_id = ? AND t.status != 'archived'";
    $params = [$teacherId, $dayOfWeek, $timeSlotId];
    
    if ($excludeTimetableId) {
        $sql .= " AND te.timetable_id != ?";
        $params[] = $excludeTimetableId;
    }
    
    $result = dbFetch($sql, $params);
    return $result['count'] > 0;
}

function getTeacherDailyLoad($teacherId, $dayOfWeek, $excludeTimetableId = null) {
    $sql = "SELECT COUNT(*) as count FROM timetable_entries te
            JOIN timetables t ON te.timetable_id = t.id
            WHERE te.teacher_id = ? AND te.day_of_week = ? AND t.status != 'archived'";
    $params = [$teacherId, $dayOfWeek];
    
    if ($excludeTimetableId) {
        $sql .= " AND te.timetable_id != ?";
        $params[] = $excludeTimetableId;
    }
    
    $result = dbFetch($sql, $params);
    return $result['count'];
}

// ============================================
// SETTINGS FUNCTIONS
// ============================================

function getSetting($key, $institutionId = null) {
    $setting = dbFetch(
        "SELECT setting_value FROM settings WHERE setting_key = ? AND (institution_id = ? OR institution_id IS NULL) 
         ORDER BY institution_id DESC LIMIT 1",
        [$key, $institutionId]
    );
    return $setting ? $setting['setting_value'] : null;
}

function setSetting($key, $value, $institutionId = null, $group = 'general') {
    $existing = dbFetch(
        "SELECT id FROM settings WHERE setting_key = ? AND institution_id ".($institutionId ? "= ?" : "IS NULL"),
        $institutionId ? [$key, $institutionId] : [$key]
    );
    
    if ($existing) {
        dbQuery(
            "UPDATE settings SET setting_value = ? WHERE id = ?",
            [$value, $existing['id']]
        );
    } else {
        dbQuery(
            "INSERT INTO settings (institution_id, setting_key, setting_value, setting_group) VALUES (?, ?, ?, ?)",
            [$institutionId, $key, $value, $group]
        );
    }
}
