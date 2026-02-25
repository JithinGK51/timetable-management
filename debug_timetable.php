<?php
/**
 * Debug Timetable Generation
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Timetable Generation Debug</h1>";

$institutionId = 2; // Riverside College
$classId = 4; // First Year CS
$sectionId = 6; // Section A

echo "<h2>Parameters</h2>";
echo "Institution ID: $institutionId<br>";
echo "Class ID: $classId<br>";
echo "Section ID: $sectionId<br>";

// Get subjects
$subjects = getSubjectsByClass($classId);
echo "<h2>Subjects (" . count($subjects) . ")</h2>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Weekly Hours</th><th>Status</th></tr>";
foreach ($subjects as $s) {
    echo "<tr><td>{$s['id']}</td><td>{$s['name']}</td><td>{$s['weekly_hours']}</td><td>{$s['status']}</td></tr>";
}
echo "</table>";

// Get teachers
$teachers = getTeachersByInstitution($institutionId);
echo "<h2>Teachers (" . count($teachers) . ")</h2>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Max/Day</th><th>Max/Week</th><th>Status</th></tr>";
foreach ($teachers as $t) {
    echo "<tr><td>{$t['id']}</td><td>{$t['name']}</td><td>{$t['max_periods_per_day']}</td><td>{$t['max_periods_per_week']}</td><td>{$t['status']}</td></tr>";
}
echo "</table>";

// Get teacher-subject assignments
echo "<h2>Teacher-Subject Assignments</h2>";
foreach ($teachers as $teacher) {
    $assignments = dbFetchAll("SELECT ts.*, s.name as subject_name 
        FROM teacher_subjects ts 
        JOIN subjects s ON ts.subject_id = s.id 
        WHERE ts.teacher_id = ?", [$teacher['id']]);
    if (!empty($assignments)) {
        echo "<b>{$teacher['name']}:</b> ";
        $subjNames = array_column($assignments, 'subject_name');
        echo implode(', ', $subjNames) . "<br>";
    }
}

// Get time slots
$timeSlots = getTimeSlotsByInstitution($institutionId);
$classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
echo "<h2>Class Time Slots (" . count($classSlots) . ")</h2>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Start</th><th>End</th></tr>";
foreach ($classSlots as $s) {
    echo "<tr><td>{$s['id']}</td><td>{$s['display_name']}</td><td>{$s['start_time']}</td><td>{$s['end_time']}</td></tr>";
}
echo "</table>";

// Get working days
$workingDays = getWorkingDaysByInstitution($institutionId);
$workingDays = array_filter($workingDays, function($d) { return $d['is_working']; });
echo "<h2>Working Days (" . count($workingDays) . ")</h2>";
foreach ($workingDays as $d) {
    echo $d['day_of_week'] . "<br>";
}

// Check teacher availability
echo "<h2>Teacher Availability</h2>";
foreach ($teachers as $teacher) {
    $avail = dbFetchAll(
        "SELECT day_of_week FROM teacher_availability WHERE teacher_id = ? AND is_available = TRUE",
        [$teacher['id']]
    );
    $days = array_column($avail, 'day_of_week');
    echo "<b>{$teacher['name']}:</b> " . implode(', ', $days) . "<br>";
}

// Calculate totals
$totalRequired = array_sum(array_column($subjects, 'weekly_hours'));
$totalAvailable = count($workingDays) * count($classSlots);
echo "<h2>Slot Analysis</h2>";
echo "Total Required: $totalRequired<br>";
echo "Total Available: $totalAvailable<br>";
echo "Sufficient: " . ($totalRequired <= $totalAvailable ? 'YES' : 'NO') . "<br>";

echo "<hr><a href='modules/timetable/create.php'>Go to Timetable Creation</a>";
