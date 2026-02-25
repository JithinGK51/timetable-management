<?php
/**
 * Fix Teacher Availability
 * Adds missing teacher availability records
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Fixing Teacher Availability</h1>";

$teachers = dbFetchAll("SELECT id, name FROM teachers WHERE status = 'active'");
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

$added = 0;
foreach ($teachers as $teacher) {
    foreach ($days as $day) {
        // Check if record exists
        $exists = dbFetch(
            "SELECT id FROM teacher_availability WHERE teacher_id = ? AND day_of_week = ?",
            [$teacher['id'], $day]
        );
        
        if (!$exists) {
            dbQuery(
                "INSERT INTO teacher_availability (teacher_id, day_of_week, is_available) VALUES (?, ?, TRUE)",
                [$teacher['id'], $day]
            );
            $added++;
            echo "Added: {$teacher['name']} - $day<br>";
        }
    }
}

echo "<p>Total records added: $added</p>";
echo "<a href='modules/timetable/create.php'>Try creating timetable again</a>";
