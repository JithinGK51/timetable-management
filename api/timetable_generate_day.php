<?php
/**
 * Single Day Multi-Section Timetable Generation API
 * Generates timetables for multiple sections on a single day
 */

require_once __DIR__ . '/../includes/functions.php';

/**
 * Generate timetables for multiple sections on a single day
 * 
 * @param int $institutionId Institution ID
 * @param int $classId Class ID
 * @param array $sectionIds Array of Section IDs
 * @param string $dayOfWeek Day of week (monday, tuesday, etc.)
 * @param string $academicYear Academic year (e.g., "2025-2026")
 * @return array Result with status and data
 */
function generateSingleDayTimetable($institutionId, $classId, $sectionIds, $dayOfWeek, $academicYear) {
    // Validate inputs
    if (empty($sectionIds)) {
        return ['status' => 'error', 'message' => 'No sections selected'];
    }
    
    // Validate day of week
    $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    if (!in_array($dayOfWeek, $validDays)) {
        return ['status' => 'error', 'message' => 'Invalid day of week'];
    }
    
    // Check if it's a working day
    $workingDay = dbFetch(
        "SELECT * FROM working_days WHERE institution_id = ? AND day_of_week = ? AND is_working = TRUE",
        [$institutionId, $dayOfWeek]
    );
    
    if (!$workingDay) {
        return ['status' => 'error', 'message' => ucfirst($dayOfWeek) . ' is not a working day for this institution'];
    }
    
    // Fetch time slots for the institution
    $timeSlots = getTimeSlotsByInstitution($institutionId);
    $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
    
    if (empty($classSlots)) {
        return ['status' => 'error', 'message' => 'No class time slots configured'];
    }
    
    // Fetch teachers for the institution
    $teachers = getTeachersByInstitution($institutionId);
    $teachers = array_filter($teachers, function($t) { return $t['status'] === 'active'; });
    
    if (empty($teachers)) {
        return ['status' => 'error', 'message' => 'No active teachers found for this institution'];
    }
    
    // Build teacher-subject mapping
    $teacherSubjects = [];
    foreach ($teachers as $teacher) {
        $teacherSubjects[$teacher['id']] = dbFetchAll(
            "SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?",
            [$teacher['id']]
        );
        $teacherSubjects[$teacher['id']] = array_column($teacherSubjects[$teacher['id']], 'subject_id');
    }
    
    // Build teacher availability for the specific day
    $teacherAvailability = [];
    foreach ($teachers as $teacher) {
        $avail = dbFetch(
            "SELECT is_available FROM teacher_availability WHERE teacher_id = ? AND day_of_week = ?",
            [$teacher['id'], $dayOfWeek]
        );
        $teacherAvailability[$teacher['id']] = ($avail && $avail['is_available']);
    }
    
    // Results array
    $results = [];
    $errors = [];
    
    // Process each section
    foreach ($sectionIds as $sectionId) {
        $sectionResult = generateSectionDayTimetable(
            $institutionId,
            $classId,
            $sectionId,
            $dayOfWeek,
            $academicYear,
            $classSlots,
            $teachers,
            $teacherSubjects,
            $teacherAvailability
        );
        
        if ($sectionResult['status'] === 'success') {
            $results[] = $sectionResult;
        } else {
            $errors[] = "Section {$sectionId}: " . $sectionResult['message'];
        }
    }
    
    if (empty($results)) {
        return [
            'status' => 'error',
            'message' => 'Failed to generate timetables for all sections. ' . implode('; ', $errors)
        ];
    }
    
    return [
        'status' => 'success',
        'timetables' => $results,
        'errors' => $errors,
        'message' => 'Generated ' . count($results) . ' timetable(s) for ' . ucfirst($dayOfWeek)
    ];
}

/**
 * Generate timetable for a single section on a single day
 */
function generateSectionDayTimetable($institutionId, $classId, $sectionId, $dayOfWeek, $academicYear, $timeSlots, $teachers, $teacherSubjects, $teacherAvailability) {
    // Get subjects for this class
    $subjects = getSubjectsByClass($classId);
    $subjects = array_filter($subjects, function($s) { return $s['status'] === 'active'; });
    
    if (empty($subjects)) {
        return ['status' => 'error', 'message' => 'No active subjects found for this class'];
    }
    
    // Calculate how many periods per day for each subject
    // Distribute weekly hours across working days
    $workingDaysCount = count(getWorkingDaysByInstitution($institutionId));
    $availableSlots = count($timeSlots);
    
    $timetableEntries = [];
    $teacherSlotUsage = []; // Track which slots teachers are assigned to on this day
    $teacherDailyLoad = []; // Track daily load per teacher for this day
    
    // Sort subjects by priority (those with fewer eligible teachers first)
    usort($subjects, function($a, $b) use ($teachers, $teacherSubjects) {
        $aTeachers = count(array_filter($teachers, function($t) use ($a, $teacherSubjects) {
            return in_array($a['id'], $teacherSubjects[$t['id']] ?? []);
        }));
        $bTeachers = count(array_filter($teachers, function($t) use ($b, $teacherSubjects) {
            return in_array($b['id'], $teacherSubjects[$t['id']] ?? []);
        }));
        return $aTeachers - $bTeachers;
    });
    
    // Calculate periods per subject for this day
    $subjectPeriods = [];
    $totalAssigned = 0;
    
    foreach ($subjects as $subject) {
        // Distribute weekly hours evenly across working days
        $periodsForDay = max(1, round($subject['weekly_hours'] / max(1, $workingDaysCount)));
        $subjectPeriods[$subject['id']] = min($periodsForDay, $availableSlots - $totalAssigned);
        $totalAssigned += $subjectPeriods[$subject['id']];
        
        if ($totalAssigned >= $availableSlots) {
            break;
        }
    }
    
    // Shuffle time slots for better distribution
    $timeSlotsArray = array_values($timeSlots);
    shuffle($timeSlotsArray);
    
    // Assign subjects to slots
    $slotIndex = 0;
    
    foreach ($subjects as $subject) {
        $requiredPeriods = $subjectPeriods[$subject['id']] ?? 0;
        $assignedCount = 0;
        
        if ($requiredPeriods == 0) continue;
        
        // Find eligible teachers for this subject
        $eligibleTeachers = [];
        foreach ($teachers as $teacher) {
            if (in_array($subject['id'], $teacherSubjects[$teacher['id']] ?? []) && 
                ($teacherAvailability[$teacher['id']] ?? false)) {
                $eligibleTeachers[] = $teacher;
            }
        }
        
        if (empty($eligibleTeachers)) {
            return ['status' => 'error', 'message' => "No teacher available for subject: {$subject['name']}"];
        }
        
        // Try to assign periods with multiple attempts
        $attempts = 0;
        $maxAttempts = $availableSlots * 2;
        
        while ($assignedCount < $requiredPeriods && $attempts < $maxAttempts) {
            $attempts++;
            
            if ($slotIndex >= count($timeSlotsArray)) {
                break;
            }
            
            $slot = $timeSlotsArray[$slotIndex];
            
            // Check if slot is already used
            $slotUsed = false;
            foreach ($timetableEntries as $entry) {
                if ($entry['time_slot_id'] == $slot['id']) {
                    $slotUsed = true;
                    break;
                }
            }
            
            if ($slotUsed) {
                $slotIndex++;
                continue;
            }
            
            // Find an available teacher for this slot
            shuffle($eligibleTeachers);
            $teacherAssigned = false;
            
            foreach ($eligibleTeachers as $teacher) {
                // Check if teacher is already assigned to this slot on this day
                if (isset($teacherSlotUsage[$teacher['id']]) && 
                    in_array($slot['id'], $teacherSlotUsage[$teacher['id']])) {
                    continue;
                }
                
                // Check teacher daily load
                $currentLoad = $teacherDailyLoad[$teacher['id']] ?? 0;
                if ($currentLoad >= $teacher['max_periods_per_day']) {
                    continue;
                }
                
                // Check for conflicts with other published timetables
                $conflict = dbFetch(
                    "SELECT COUNT(*) as count FROM timetable_entries te
                     JOIN timetables t ON te.timetable_id = t.id
                     WHERE te.teacher_id = ? AND te.day_of_week = ? AND te.time_slot_id = ? AND t.status = 'published'",
                    [$teacher['id'], $dayOfWeek, $slot['id']]
                );
                
                if ($conflict['count'] > 0) {
                    continue;
                }
                
                // Assign this teacher to the slot
                $timetableEntries[] = [
                    'day_of_week' => $dayOfWeek,
                    'time_slot_id' => $slot['id'],
                    'subject_id' => $subject['id'],
                    'teacher_id' => $teacher['id']
                ];
                
                // Update teacher daily load
                if (!isset($teacherDailyLoad[$teacher['id']])) {
                    $teacherDailyLoad[$teacher['id']] = 0;
                }
                $teacherDailyLoad[$teacher['id']]++;
                
                if (!isset($teacherSlotUsage[$teacher['id']])) {
                    $teacherSlotUsage[$teacher['id']] = [];
                }
                $teacherSlotUsage[$teacher['id']][] = $slot['id'];
                
                $assignedCount++;
                $slotIndex++;
                $teacherAssigned = true;
                break;
            }
            
            if (!$teacherAssigned) {
                // No teacher available for this slot, skip it
                $slotIndex++;
            }
        }
        
        if ($assignedCount < $requiredPeriods) {
            return [
                'status' => 'error',
                'message' => "Could not assign all periods for {$subject['name']}. Assigned $assignedCount of $requiredPeriods."
            ];
        }
    }
    
    // Save timetable to database
    $timetableId = saveTimetable($institutionId, $classId, $sectionId, $academicYear, $timetableEntries);
    
    if (!$timetableId) {
        return ['status' => 'error', 'message' => 'Failed to save timetable'];
    }
    
    return [
        'status' => 'success',
        'timetable_id' => $timetableId,
        'section_id' => $sectionId,
        'timetable' => $timetableEntries
    ];
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
if (basename($_SERVER['PHP_SELF']) === 'timetable_generate_day.php') {
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
    $sectionIds = $_POST['section_ids'] ?? [];
    $dayOfWeek = sanitize($_POST['day_of_week'] ?? '');
    $academicYear = sanitize($_POST['academic_year'] ?? '');
    
    // Convert section_ids to array if it's a string
    if (is_string($sectionIds)) {
        $sectionIds = explode(',', $sectionIds);
    }
    
    // Convert to integers
    $sectionIds = array_map('intval', $sectionIds);
    $sectionIds = array_filter($sectionIds);
    
    if (!$institutionId || !$classId || empty($sectionIds) || !$dayOfWeek || !$academicYear) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit;
    }
    
    $result = generateSingleDayTimetable($institutionId, $classId, $sectionIds, $dayOfWeek, $academicYear);
    echo json_encode($result);
}
