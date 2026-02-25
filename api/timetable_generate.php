<?php
/**
 * Timetable Generation Engine
 * Core algorithm for generating conflict-free timetables
 */

require_once __DIR__ . '/../includes/functions.php';

/**
 * Generate a timetable for a class-section
 * 
 * @param int $institutionId Institution ID
 * @param int $classId Class ID
 * @param int $sectionId Section ID
 * @param string $academicYear Academic year (e.g., "2025-2026")
 * @return array Result with status and data
 */
function generateTimetable($institutionId, $classId, $sectionId, $academicYear) {
    // 1. Fetch all required data
    $subjects = getSubjectsByClass($classId);
    $teachers = getTeachersByInstitution($institutionId);
    $timeSlots = getTimeSlotsByInstitution($institutionId);
    $workingDays = getWorkingDaysByInstitution($institutionId);
    
    // Filter active data
    $subjects = array_filter($subjects, function($s) { return $s['status'] === 'active'; });
    $teachers = array_filter($teachers, function($t) { return $t['status'] === 'active'; });
    $workingDays = array_filter($workingDays, function($d) { return $d['is_working']; });
    $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
    
    if (empty($subjects)) {
        return ['status' => 'error', 'message' => 'No active subjects found for this class'];
    }
    
    if (empty($teachers)) {
        return ['status' => 'error', 'message' => 'No active teachers found for this institution'];
    }
    
    if (empty($workingDays)) {
        return ['status' => 'error', 'message' => 'No working days configured'];
    }
    
    if (empty($classSlots)) {
        return ['status' => 'error', 'message' => 'No class time slots configured'];
    }
    
    // Calculate total required and available slots
    $totalRequired = array_sum(array_column($subjects, 'weekly_hours'));
    $totalAvailable = count($workingDays) * count($classSlots);
    
    if ($totalRequired > $totalAvailable) {
        return [
            'status' => 'error', 
            'message' => "Insufficient time slots. Required: $totalRequired, Available: $totalAvailable"
        ];
    }
    
    // 2. Build teacher-subject mapping
    $teacherSubjects = [];
    foreach ($teachers as $teacher) {
        $teacherSubjects[$teacher['id']] = dbFetchAll(
            "SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?",
            [$teacher['id']]
        );
        $teacherSubjects[$teacher['id']] = array_column($teacherSubjects[$teacher['id']], 'subject_id');
    }
    
    // Build teacher availability
    $teacherAvailability = [];
    foreach ($teachers as $teacher) {
        $avail = dbFetchAll(
            "SELECT day_of_week FROM teacher_availability WHERE teacher_id = ? AND is_available = TRUE",
            [$teacher['id']]
        );
        $teacherAvailability[$teacher['id']] = array_column($avail, 'day_of_week');
        error_log("Teacher {$teacher['id']} ({$teacher['name']}) available days: " . implode(', ', $teacherAvailability[$teacher['id']]));
    }
    
    // 3. Initialize timetable grid
    $timetableEntries = [];
    $teacherDailyLoad = []; // Track daily load per teacher
    $teacherSlotUsage = []; // Track which slots teachers are assigned to
    
    // 4. Generate timetable - Assign subjects to slots
    foreach ($subjects as $subject) {
        $requiredPeriods = intval($subject['weekly_hours']);
        $assignedCount = 0;
        
        // Find eligible teachers for this subject
        $eligibleTeachers = [];
        foreach ($teachers as $teacher) {
            if (in_array($subject['id'], $teacherSubjects[$teacher['id']])) {
                $eligibleTeachers[] = $teacher;
            }
        }
        
        if (empty($eligibleTeachers)) {
            return [
                'status' => 'error', 
                'message' => "No teacher assigned for subject: {$subject['name']}"
            ];
        }
        
        // Try to assign periods
        $attempts = 0;
        $maxAttempts = $totalAvailable * 2;
        
        while ($assignedCount < $requiredPeriods && $attempts < $maxAttempts) {
            $attempts++;
            
            // Find an available slot
            $slot = findAvailableSlot(
                $timetableEntries, 
                $subject, 
                $eligibleTeachers, 
                $workingDays, 
                $classSlots,
                $teacherAvailability,
                $teacherDailyLoad,
                $teacherSlotUsage,
                $teachers
            );
            
            if ($slot) {
                $timetableEntries[] = $slot;
                
                // Update tracking
                $teacherId = $slot['teacher_id'];
                $day = $slot['day_of_week'];
                $slotId = $slot['time_slot_id'];
                
                if (!isset($teacherDailyLoad[$teacherId][$day])) {
                    $teacherDailyLoad[$teacherId][$day] = 0;
                }
                $teacherDailyLoad[$teacherId][$day]++;
                
                if (!isset($teacherSlotUsage[$teacherId][$day])) {
                    $teacherSlotUsage[$teacherId][$day] = [];
                }
                $teacherSlotUsage[$teacherId][$day][] = $slotId;
                
                $assignedCount++;
            } else {
                // Try redistributing existing entries
                break;
            }
        }
        
        if ($assignedCount < $requiredPeriods) {
            return [
                'status' => 'error',
                'message' => "Could not assign all periods for {$subject['name']}. Assigned $assignedCount of $requiredPeriods."
            ];
        }
    }
    
    // 5. Save timetable to database
    $timetableId = saveTimetable($institutionId, $classId, $sectionId, $academicYear, $timetableEntries);
    
    if (!$timetableId) {
        return ['status' => 'error', 'message' => 'Failed to save timetable'];
    }
    
    // Clear session data
    unset($_SESSION['timetable_institution']);
    unset($_SESSION['timetable_class']);
    unset($_SESSION['timetable_section']);
    unset($_SESSION['timetable_year']);
    
    return [
        'status' => 'success',
        'timetable_id' => $timetableId,
        'timetable' => $timetableEntries,
        'message' => 'Timetable generated successfully'
    ];
}

/**
 * Find an available slot for a subject
 */
function findAvailableSlot($entries, $subject, $teachers, $workingDays, $timeSlots, $teacherAvailability, $teacherDailyLoad, $teacherSlotUsage, $allTeachers) {
    // Shuffle to distribute evenly - use array_values to reset keys
    $shuffledDays = array_values($workingDays);
    shuffle($shuffledDays);
    $shuffledSlots = array_values($timeSlots);
    shuffle($shuffledSlots);
    
    // Debug logging
    error_log("Finding slot for subject: {$subject['name']}, Teachers: " . count($teachers) . ", Days: " . count($shuffledDays) . ", Slots: " . count($shuffledSlots));
    
    foreach ($shuffledDays as $day) {
        foreach ($shuffledSlots as $slot) {
            // Check if slot is already used
            $slotUsed = false;
            foreach ($entries as $entry) {
                if ($entry['day_of_week'] === $day['day_of_week'] && $entry['time_slot_id'] == $slot['id']) {
                    $slotUsed = true;
                    break;
                }
            }
            
            if ($slotUsed) continue;
            
            // Find available teacher for this slot
            shuffle($teachers); // Randomize to distribute load
            
            foreach ($teachers as $teacher) {
                // Check teacher availability for this day
                if (!in_array($day['day_of_week'], $teacherAvailability[$teacher['id']] ?? [])) {
                    continue;
                }
                
                // Check if teacher is already assigned to this slot on this day
                if (isset($teacherSlotUsage[$teacher['id']][$day['day_of_week']]) && 
                    in_array($slot['id'], $teacherSlotUsage[$teacher['id']][$day['day_of_week']])) {
                    continue;
                }
                
                // Check teacher daily load
                $currentLoad = $teacherDailyLoad[$teacher['id']][$day['day_of_week']] ?? 0;
                if ($currentLoad >= $teacher['max_periods_per_day']) {
                    continue;
                }
                
                // Check teacher weekly load
                $weeklyLoad = array_sum($teacherDailyLoad[$teacher['id']] ?? []);
                if ($weeklyLoad >= $teacher['max_periods_per_week']) {
                    continue;
                }
                
                // Check for conflicts with other timetables
                $conflict = dbFetch(
                    "SELECT COUNT(*) as count FROM timetable_entries te
                     JOIN timetables t ON te.timetable_id = t.id
                     WHERE te.teacher_id = ? AND te.day_of_week = ? AND te.time_slot_id = ? AND t.status = 'published'",
                    [$teacher['id'], $day['day_of_week'], $slot['id']]
                );
                
                if ($conflict['count'] > 0) {
                    continue;
                }
                
                // Found a valid slot and teacher
                return [
                    'day_of_week' => $day['day_of_week'],
                    'time_slot_id' => $slot['id'],
                    'subject_id' => $subject['id'],
                    'teacher_id' => $teacher['id']
                ];
            }
        }
    }
    
    return null;
}

/**
 * Save timetable to database
 */
function saveTimetable($institutionId, $classId, $sectionId, $academicYear, $entries) {
    $currentAdmin = getCurrentAdmin();
    
    // Check if timetable exists
    $existing = dbFetch(
        "SELECT id, version FROM timetables 
         WHERE institution_id = ? AND class_id = ? AND section_id = ? AND academic_year = ? AND status = 'draft'
         ORDER BY version DESC LIMIT 1",
        [$institutionId, $classId, $sectionId, $academicYear]
    );
    
    if ($existing) {
        // Update existing draft
        $timetableId = $existing['id'];
        $version = $existing['version'] + 1;
        
        dbQuery(
            "UPDATE timetables SET version = ?, updated_at = NOW() WHERE id = ?",
            [$version, $timetableId]
        );
        
        // Delete old entries
        dbQuery("DELETE FROM timetable_entries WHERE timetable_id = ?", [$timetableId]);
    } else {
        // Create new timetable
        $timetableId = dbInsert(
            "INSERT INTO timetables (institution_id, class_id, section_id, academic_year, version, status, created_by) 
             VALUES (?, ?, ?, ?, 1, 'draft', ?)",
            [$institutionId, $classId, $sectionId, $academicYear, $currentAdmin['id']]
        );
    }
    
    // Insert entries
    foreach ($entries as $entry) {
        dbInsert(
            "INSERT INTO timetable_entries (timetable_id, day_of_week, time_slot_id, subject_id, teacher_id) 
             VALUES (?, ?, ?, ?, ?)",
            [$timetableId, $entry['day_of_week'], $entry['time_slot_id'], $entry['subject_id'], $entry['teacher_id']]
        );
    }
    
    return $timetableId;
}

// API endpoint for AJAX generation
if (basename($_SERVER['PHP_SELF']) === 'timetable_generate.php') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $institutionId = intval($_POST['institution_id'] ?? 0);
    $classId = intval($_POST['class_id'] ?? 0);
    $sectionId = intval($_POST['section_id'] ?? 0);
    $academicYear = sanitize($_POST['academic_year'] ?? '');
    
    if (!$institutionId || !$classId || !$sectionId || !$academicYear) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit;
    }
    
    $result = generateTimetable($institutionId, $classId, $sectionId, $academicYear);
    echo json_encode($result);
}
