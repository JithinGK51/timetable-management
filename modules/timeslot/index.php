<?php
/**
 * Time Slot Configuration
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Time Slots';
$breadcrumb = [
    ['label' => 'Time Slots']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('timeslot', 'view');

// Get institution filter
$institutionId = isset($_GET['institution']) ? intval($_GET['institution']) : 0;

// Get institutions
$institutions = getInstitutions('active');

// Set default institution if only one exists
if (!$institutionId && count($institutions) === 1) {
    $institutionId = $institutions[0]['id'];
}

$workingDays = [];
$timeSlots = [];

if ($institutionId) {
    $workingDays = getWorkingDaysByInstitution($institutionId);
    $timeSlots = getTimeSlotsByInstitution($institutionId);
}

// Handle working days update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_working_days' && hasPermission('timeslot', 'edit')) {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $isWorking = isset($_POST['working_days']) && in_array($day, $_POST['working_days']) ? 1 : 0;
            
            // Check if record exists
            $exists = dbFetch("SELECT id FROM working_days WHERE institution_id = ? AND day_of_week = ?", [$institutionId, $day]);
            if ($exists) {
                dbQuery("UPDATE working_days SET is_working = ? WHERE id = ?", [$isWorking, $exists['id']]);
            } else {
                dbQuery("INSERT INTO working_days (institution_id, day_of_week, is_working) VALUES (?, ?, ?)", [$institutionId, $day, $isWorking]);
            }
        }
        showAlert('Working days updated successfully', 'success');
        redirect('index.php?institution=' . $institutionId);
    }
    
    if ($_POST['action'] === 'add_time_slot' && hasPermission('timeslot', 'edit')) {
        $periodNumber = intval($_POST['period_number']);
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $isBreak = isset($_POST['is_break']) ? 1 : 0;
        $isLunch = isset($_POST['is_lunch']) ? 1 : 0;
        $displayName = sanitize($_POST['display_name'] ?? '');
        
        if ($periodNumber && $startTime && $endTime) {
            dbQuery(
                "INSERT INTO time_slots (institution_id, period_number, start_time, end_time, is_break, is_lunch, display_name) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$institutionId, $periodNumber, $startTime, $endTime, $isBreak, $isLunch, $displayName]
            );
            showAlert('Time slot added successfully', 'success');
        } else {
            showAlert('Please fill all required fields', 'danger');
        }
        redirect('index.php?institution=' . $institutionId);
    }
}

// Handle delete time slot
if (isset($_GET['delete_slot']) && hasPermission('timeslot', 'delete')) {
    $slotId = intval($_GET['delete_slot']);
    dbQuery("DELETE FROM time_slots WHERE id = ? AND institution_id = ?", [$slotId, $institutionId]);
    showAlert('Time slot deleted', 'success');
    redirect('index.php?institution=' . $institutionId);
}

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Time Slot Configuration</h1>
        <p class="page-subtitle">Configure working days and periods</p>
    </div>
</div>

<!-- Institution Selector -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row" style="margin-bottom: 0;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Select Institution</label>
                    <select name="institution" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select Institution --</option>
                        <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" <?php echo $institutionId == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inst['name']); ?> (<?php echo ucfirst($inst['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($institutionId): ?>

<!-- Working Days -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-week"></i> Working Days</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_working_days">
            <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 20px;">
                <?php 
                $workingDaysMap = [];
                foreach ($workingDays as $wd) {
                    $workingDaysMap[$wd['day_of_week']] = $wd['is_working'];
                }
                foreach ($days as $day): 
                    $isWorking = isset($workingDaysMap[$day]) ? $workingDaysMap[$day] : ($day !== 'saturday' && $day !== 'sunday');
                ?>
                    <label class="form-check" style="min-width: 120px;">
                        <input type="checkbox" name="working_days[]" value="<?php echo $day; ?>" <?php echo $isWorking ? 'checked' : ''; ?>>
                        <span><strong><?php echo getDayName($day); ?></strong></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (hasPermission('timeslot', 'edit')): ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Working Days
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Time Slots -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> Periods / Time Slots</h3>
    </div>
    <div class="card-body">
        <?php if (hasPermission('timeslot', 'edit')): ?>
            <!-- Add New Time Slot Form -->
            <form method="POST" action="" style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px;">
                <input type="hidden" name="action" value="add_time_slot">
                <h4 style="margin-bottom: 15px; font-size: 14px; color: var(--text-medium);">Add New Period</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Period #</label>
                        <input type="number" name="period_number" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Name (Optional)</label>
                        <input type="text" name="display_name" class="form-control" placeholder="e.g., Morning Assembly">
                    </div>
                </div>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label class="form-check">
                        <input type="checkbox" name="is_break" value="1">
                        <span>Break Time</span>
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="is_lunch" value="1">
                        <span>Lunch Period</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-success" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Add Period
                </button>
            </form>
        <?php endif; ?>
        
        <!-- Time Slots List -->
        <?php if (empty($timeSlots)): ?>
            <div class="empty-state" style="padding: 40px;">
                <div class="empty-state-icon">&#9200;</div>
                <h3>No Time Slots Configured</h3>
                <p>Add periods to create your timetable structure.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Period #</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Type</th>
                            <th>Display Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $slot): 
                            $start = strtotime($slot['start_time']);
                            $end = strtotime($slot['end_time']);
                            $duration = ($end - $start) / 60;
                        ?>
                            <tr>
                                <td><strong><?php echo $slot['period_number']; ?></strong></td>
                                <td><?php echo formatTime($slot['start_time']); ?> - <?php echo formatTime($slot['end_time']); ?></td>
                                <td><?php echo $duration; ?> minutes</td>
                                <td>
                                    <?php if ($slot['is_lunch']): ?>
                                        <span class="badge badge-warning">Lunch</span>
                                    <?php elseif ($slot['is_break']): ?>
                                        <span class="badge badge-info">Break</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Class</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($slot['display_name'] ?? '-'); ?></td>
                                <td>
                                    <?php if (hasPermission('timeslot', 'delete')): ?>
                                        <a href="?institution=<?php echo $institutionId; ?>&delete_slot=<?php echo $slot['id']; ?>" class="action-btn delete" onclick="return confirmDelete();">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">&#127979;</div>
            <h3>Select an Institution</h3>
            <p>Please select an institution to configure its time slots.</p>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
