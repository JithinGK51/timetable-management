<?php
/**
 * PDF Generation Stub
 * In production, integrate with TCPDF or FPDF library
 */

require_once __DIR__ . '/../../includes/functions.php';
requirePermission('export', 'view');

$type = $_GET['type'] ?? 'class';
$institutionId = intval($_GET['institution'] ?? 0);
$classId = intval($_GET['class'] ?? 0);
$sectionId = intval($_GET['section'] ?? 0);
$teacherId = intval($_GET['teacher'] ?? 0);

// Get data based on type
$institution = getInstitutionById($institutionId);

if (!$institution) {
    die('Institution not found');
}

// For now, output HTML that can be printed to PDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Timetable Export - <?php echo htmlspecialchars($institution['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .day-header {
            background: #e0e0e0;
            font-weight: bold;
        }
        .subject {
            font-weight: bold;
            color: #333;
        }
        .teacher {
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($institution['name']); ?></h1>
        <p><?php echo htmlspecialchars($institution['address'] ?? ''); ?></p>
        <p><strong>Academic Timetable</strong></p>
        <?php if ($type === 'section' && $sectionId): 
            $section = dbFetch("SELECT s.*, c.name as class_name FROM sections s JOIN classes c ON s.class_id = c.id WHERE s.id = ?", [$sectionId]);
        ?>
            <p>Class: <?php echo htmlspecialchars($section['class_name'] . ' - Section ' . $section['name']); ?></p>
        <?php elseif ($type === 'teacher' && $teacherId):
            $teacher = dbFetch("SELECT * FROM teachers WHERE id = ?", [$teacherId]);
        ?>
            <p>Teacher: <?php echo htmlspecialchars($teacher['name'] . ' (' . $teacher['employee_id'] . ')'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($type === 'section' && $sectionId): 
        // Get timetable for section
        $timetable = dbFetch("SELECT * FROM timetables WHERE section_id = ? AND status = 'published' ORDER BY id DESC LIMIT 1", [$sectionId]);
        if ($timetable):
            $entries = getTimetableEntries($timetable['id']);
            $timeSlots = getTimeSlotsByInstitution($institutionId);
            $workingDays = getWorkingDaysByInstitution($institutionId);
            $activeDays = array_filter($workingDays, function($d) { return $d['is_working']; });
            $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
            
            // Build grid
            $grid = [];
            foreach ($entries as $entry) {
                $key = $entry['day_of_week'] . '_' . $entry['time_slot_id'];
                $grid[$key] = $entry;
            }
    ?>
        <table>
            <thead>
                <tr>
                    <th>Day / Period</th>
                    <?php foreach ($classSlots as $slot): ?>
                        <th>
                            Period <?php echo $slot['period_number']; ?><br>
                            <small><?php echo formatTime($slot['start_time']); ?> - <?php echo formatTime($slot['end_time']); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeDays as $day): ?>
                    <tr>
                        <td class="day-header"><?php echo getDayName($day['day_of_week']); ?></td>
                        <?php foreach ($classSlots as $slot): 
                            $key = $day['day_of_week'] . '_' . $slot['id'];
                            $entry = $grid[$key] ?? null;
                        ?>
                            <td>
                                <?php if ($entry): 
                                    $subject = dbFetch("SELECT name FROM subjects WHERE id = ?", [$entry['subject_id']]);
                                    $teacher = dbFetch("SELECT name FROM teachers WHERE id = ?", [$entry['teacher_id']]);
                                ?>
                                    <div class="subject"><?php echo htmlspecialchars($subject['name'] ?? 'N/A'); ?></div>
                                    <div class="teacher"><?php echo htmlspecialchars($teacher['name'] ?? 'N/A'); ?></div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: #666;">No published timetable found for this section.</p>
    <?php endif; endif; ?>
    
    <?php if ($type === 'teacher' && $teacherId):
        // Get all timetables where teacher is assigned
        $teacherEntries = dbFetchAll(
            "SELECT te.*, t.class_id, t.section_id, c.name as class_name, s.name as section_name, sub.name as subject_name
             FROM timetable_entries te
             JOIN timetables t ON te.timetable_id = t.id
             JOIN classes c ON t.class_id = c.id
             JOIN sections s ON t.section_id = s.id
             JOIN subjects sub ON te.subject_id = sub.id
             WHERE te.teacher_id = ? AND t.status = 'published'
             ORDER BY te.day_of_week, te.time_slot_id",
            [$teacherId]
        );
        
        if (!empty($teacherEntries)):
            $timeSlots = getTimeSlotsByInstitution($institutionId);
            $workingDays = getWorkingDaysByInstitution($institutionId);
            $activeDays = array_filter($workingDays, function($d) { return $d['is_working']; });
            $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
            
            // Build grid
            $grid = [];
            foreach ($teacherEntries as $entry) {
                $key = $entry['day_of_week'] . '_' . $entry['time_slot_id'];
                $grid[$key] = $entry;
            }
    ?>
        <table>
            <thead>
                <tr>
                    <th>Day / Period</th>
                    <?php foreach ($classSlots as $slot): ?>
                        <th>
                            Period <?php echo $slot['period_number']; ?><br>
                            <small><?php echo formatTime($slot['start_time']); ?> - <?php echo formatTime($slot['end_time']); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeDays as $day): ?>
                    <tr>
                        <td class="day-header"><?php echo getDayName($day['day_of_week']); ?></td>
                        <?php foreach ($classSlots as $slot): 
                            $key = $day['day_of_week'] . '_' . $slot['id'];
                            $entry = $grid[$key] ?? null;
                        ?>
                            <td>
                                <?php if ($entry): ?>
                                    <div class="subject"><?php echo htmlspecialchars($entry['subject_name']); ?></div>
                                    <div class="teacher"><?php echo htmlspecialchars($entry['class_name'] . ' - ' . $entry['section_name']); ?></div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: #666;">No timetable assignments found for this teacher.</p>
    <?php endif; endif; ?>
    
    <div class="footer">
        <p>Generated on <?php echo date('F d, Y'); ?> by Teacher Timetable Management System</p>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>
</body>
</html>
