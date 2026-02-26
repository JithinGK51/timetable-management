<?php
/**
 * Timetable Grid View
 * Visual representation and editing interface
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Handle AJAX entry update FIRST (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_entry') {
    requirePermission('timetable', 'edit');
    
    try {
        $timetableId = intval($_POST['timetable_id'] ?? 0);
        $day = $_POST['day'] ?? '';
        $slotId = intval($_POST['slot_id'] ?? 0);
        $subjectId = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
        $teacherId = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
        $isEvent = !empty($_POST['is_event']);
        $eventName = $_POST['event_name'] ?? '';
        $eventType = $_POST['event_type'] ?? 'event';
        
        if (empty($timetableId) || empty($day) || empty($slotId)) {
            throw new Exception('Missing required fields');
        }
        
        // Check for conflicts (only for class entries)
        if (!$isEvent && $teacherId) {
            $conflict = checkTeacherConflict($teacherId, $day, $slotId, $timetableId);
            if ($conflict) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Teacher has a conflict at this time slot']);
                exit;
            }
        }
        
        // Delete existing entry for this slot
        dbQuery(
            "DELETE FROM timetable_entries WHERE timetable_id = ? AND day_of_week = ? AND time_slot_id = ?",
            [$timetableId, $day, $slotId]
        );
        
        // Insert new entry
        if ($isEvent && !empty($eventName)) {
            // Insert as event entry
            dbInsert(
                "INSERT INTO timetable_entries (timetable_id, day_of_week, time_slot_id, subject_id, teacher_id, is_override, is_event, event_name, event_type) 
                 VALUES (?, ?, ?, NULL, NULL, TRUE, TRUE, ?, ?)",
                [$timetableId, $day, $slotId, $eventName, $eventType]
            );
        } elseif ($subjectId) {
            // Insert as regular class entry
            dbInsert(
                "INSERT INTO timetable_entries (timetable_id, day_of_week, time_slot_id, subject_id, teacher_id, is_override, is_event) 
                 VALUES (?, ?, ?, ?, ?, TRUE, FALSE)",
                [$timetableId, $day, $slotId, $subjectId, $teacherId]
            );
        }
        
        // Update version
        dbQuery("UPDATE timetables SET version = version + 1 WHERE id = ?", [$timetableId]);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Entry saved successfully']);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

$pageTitle = 'Timetable Grid';
$breadcrumb = [
    ['label' => 'Timetables', 'url' => 'index.php'],
    ['label' => 'Grid View']
];

requirePermission('timetable', 'view');
require_once __DIR__ . '/../../includes/header.php';

$timetableId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$timetableId) {
    showAlert('Timetable not found', 'danger');
    redirect('index.php');
}

// Get timetable data
$timetable = getTimetableById($timetableId);

if (!$timetable) {
    showAlert('Timetable not found', 'danger');
    redirect('index.php');
}

// Get related data
$timeSlots = getTimeSlotsByInstitution($timetable['institution_id']);
$workingDays = getWorkingDaysByInstitution($timetable['institution_id']);
$entries = getTimetableEntries($timetableId);
$subjects = getSubjectsByClass($timetable['class_id']);
$teachers = getTeachersByInstitution($timetable['institution_id']);

// Get events/holidays for this institution (including global events)
$events = dbFetchAll("SELECT id, title, type, start_date FROM events WHERE institution_id = ? OR institution_id IS NULL ORDER BY start_date", 
    [$timetable['institution_id']]);

// Debug: log event count
error_log('Events found for institution ' . $timetable['institution_id'] . ': ' . count($events));

// Filter active working days and all slots (including break/lunch)
$activeDays = array_filter($workingDays, function($d) { return $d['is_working']; });
$classSlots = $timeSlots; // Include all slots including break and lunch

// Build entries grid for easy lookup
$entryGrid = [];
foreach ($entries as $entry) {
    $key = $entry['day_of_week'] . '_' . $entry['time_slot_id'];
    $entryGrid[$key] = $entry;
}

// Build subjects and teachers lookup
$subjectsMap = [];
foreach ($subjects as $s) {
    $subjectsMap[$s['id']] = $s;
}

$teachersMap = [];
foreach ($teachers as $t) {
    $teachersMap[$t['id']] = $t;
}

$canEdit = hasPermission('timetable', 'edit') && $timetable['status'] !== 'published';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Timetable Grid</h1>
        <p class="page-subtitle">
            <?php echo htmlspecialchars($timetable['institution_name']); ?> | 
            <?php echo htmlspecialchars($timetable['class_name'] . ' - ' . $timetable['section_name']); ?> | 
            <?php echo htmlspecialchars($timetable['academic_year']); ?>
            <span class="badge badge-<?php echo $timetable['status'] === 'published' ? 'success' : 'secondary'; ?>">
                <?php echo ucfirst($timetable['status']); ?>
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
            <button class="btn btn-primary" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Edit Entry
            </button>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button class="btn btn-success" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Timetable Grid -->
<div class="card">
    <div class="card-body" style="overflow-x: auto;">
        <table class="data-table" style="min-width: 800px;">
            <thead>
                <tr>
                    <th style="width: 100px;">Day / Period</th>
                    <?php foreach ($classSlots as $slot): ?>
                        <th style="text-align: center; min-width: 120px; <?php echo $slot['is_break'] ? 'background: #e3f2fd;' : ($slot['is_lunch'] ? 'background: #fff3e0;' : ''); ?>">
                            <div>
                                <?php if ($slot['is_break']): ?>
                                    <i class="fas fa-coffee"></i> Break
                                <?php elseif ($slot['is_lunch']): ?>
                                    <i class="fas fa-utensils"></i> Lunch
                                <?php else: ?>
                                    Period <?php echo $slot['period_number']; ?>
                                <?php endif; ?>
                            </div>
                            <small style="color: var(--text-light); font-weight: normal;">
                                <?php echo formatTime($slot['start_time']); ?> - <?php echo formatTime($slot['end_time']); ?>
                            </small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeDays as $day): ?>
                    <tr>
                        <td style="background: var(--bg-light); font-weight: 600;">
                            <?php echo getDayName($day['day_of_week']); ?>
                        </td>
                        <?php foreach ($classSlots as $slot): 
                            $key = $day['day_of_week'] . '_' . $slot['id'];
                            $entry = isset($entryGrid[$key]) ? $entryGrid[$key] : null;
                        ?>
                            <?php if ($slot['is_break']): ?>
                                <!-- Break Slot -->
                                <td style="padding: 8px; vertical-align: middle; height: 80px; background: #e3f2fd; text-align: center;">
                                    <div style="color: #1976d2; font-weight: 600;">
                                        <i class="fas fa-coffee" style="font-size: 20px;"></i><br>
                                        <small>BREAK</small>
                                    </div>
                                </td>
                            <?php elseif ($slot['is_lunch']): ?>
                                <!-- Lunch Slot -->
                                <td style="padding: 8px; vertical-align: middle; height: 80px; background: #fff3e0; text-align: center;">
                                    <div style="color: #f57c00; font-weight: 600;">
                                        <i class="fas fa-utensils" style="font-size: 20px;"></i><br>
                                        <small>LUNCH</small>
                                    </div>
                                </td>
                            <?php else: ?>
                                <!-- Regular Class Slot -->
                                <td style="padding: 8px; vertical-align: top; height: 80px;">
                                    <?php if ($entry): 
                                        $subject = $subjectsMap[$entry['subject_id']] ?? null;
                                        $teacher = $teachersMap[$entry['teacher_id']] ?? null;
                                    ?>
                                        <?php if (!empty($entry['is_event'])): ?>
                                            <!-- Event/Holiday Entry -->
                                            <div class="timetable-entry" 
                                                 style="cursor: <?php echo $canEdit ? 'pointer' : 'default'; ?>; background: #fff3cd; border-color: #ffc107;"
                                                 <?php if ($canEdit): ?>onclick="editEntry('<?php echo $day['day_of_week']; ?>', <?php echo $slot['id']; ?>)"<?php endif; ?>>
                                                <div class="subject" style="color: #856404; font-weight: 600;">
                                                    <i class="fas fa-calendar-star"></i> 
                                                    <?php echo htmlspecialchars($entry['event_name']); ?>
                                                </div>
                                                <small style="color: #856404; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($entry['event_type']); ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <!-- Regular Class Entry -->
                                            <div class="timetable-entry" 
                                                 style="cursor: <?php echo $canEdit ? 'pointer' : 'default'; ?>;"
                                                 <?php if ($canEdit): ?>onclick="editEntry('<?php echo $day['day_of_week']; ?>', <?php echo $slot['id']; ?>)"<?php endif; ?>>
                                                <div class="subject"><?php echo $subject ? htmlspecialchars($subject['name']) : 'Unknown'; ?></div>
                                                <?php if ($teacher): ?>
                                                    <div class="teacher">
                                                        <i class="fas fa-chalkboard-teacher"></i> 
                                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($entry['is_override']): ?>
                                                    <small style="color: var(--warning-color);"><i class="fas fa-hand-pointer"></i> Manual</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($canEdit): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" style="width: 100%; height: 60px; border: 2px dashed var(--border-color); background: transparent; color: var(--text-light);"
                                                    onclick="editEntry('<?php echo $day['day_of_week']; ?>', <?php echo $slot['id']; ?>)">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        <?php else: ?>
                                            <div style="text-align: center; color: var(--text-light); padding: 15px;">-</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Legend -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> Legend</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            <div><span style="display: inline-block; width: 20px; height: 20px; background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span> Auto-generated</div>
            <div><span style="display: inline-block; width: 20px; height: 20px; background: rgba(237, 137, 54, 0.1); border: 1px solid rgba(237, 137, 54, 0.3); border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span> Manual Override</div>
            <div><span style="display: inline-block; width: 20px; height: 20px; background: rgba(245, 101, 101, 0.1); border: 1px solid var(--danger-color); border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span> Conflict</div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Timetable Entry</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="edit_timetable_id" name="timetable_id" value="<?php echo $timetableId; ?>">
                <input type="hidden" id="edit_day" name="day">
                <input type="hidden" id="edit_slot_id" name="slot_id">
                <input type="hidden" name="action" value="update_entry">
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 30px;">
                        <div>
                            <label style="font-size: 12px; color: #666; text-transform: uppercase;">Day</label>
                            <div id="display_day" style="font-size: 18px; font-weight: 600; color: #333;">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: #666; text-transform: uppercase;">Period</label>
                            <div id="display_period" style="font-size: 18px; font-weight: 600; color: #333;">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: #666; text-transform: uppercase;">Time</label>
                            <div id="display_time" style="font-size: 18px; font-weight: 600; color: #333;">-</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Entry Type</label>
                    <select id="entry_type" class="form-control" onchange="toggleEntryType()">
                        <option value="class">Regular Class</option>
                        <option value="event">Event / Holiday</option>
                    </select>
                </div>
                
                <div id="classSection">
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" id="edit_subject_id" class="form-control">
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?> (<?php echo $subject['weekly_hours']; ?> hrs/week)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teacher *</label>
                        <select name="teacher_id" id="edit_teacher_id" class="form-control">
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name'] . ' (' . $teacher['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="eventSection" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Select Event/Holiday</label>
                        <?php if (empty($events)): ?>
                            <div class="alert alert-info" style="padding: 10px; font-size: 14px;">
                                <i class="fas fa-info-circle"></i> No events found. 
                                <a href="/ttc/modules/event/index.php" target="_blank">Create events first</a>
                            </div>
                        <?php else: ?>
                            <select id="existing_event" class="form-control" onchange="fillEventData()">
                                <option value="">-- Select Existing Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?php echo htmlspecialchars($event['title']); ?>" data-type="<?php echo $event['type']; ?>">
                                        <?php echo htmlspecialchars($event['title']); ?> (<?php echo ucfirst($event['type']); ?> - <?php echo date('M d', strtotime($event['start_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #666;">Or enter a new event below</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Event/Holiday Name *</label>
                        <input type="text" name="event_name" id="event_name" class="form-control" placeholder="e.g., Sports Day, Holiday, Exam">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select name="event_type" id="event_type" class="form-control">
                            <option value="event">Event</option>
                            <option value="holiday">Holiday</option>
                            <option value="exam">Exam</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div id="conflictWarning" class="alert alert-danger" style="display: none; margin-top: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> <span id="conflictMessage"></span>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="clearEntry()" id="clearBtn" style="display: none;">
                <i class="fas fa-trash"></i> Clear Entry
            </button>
            <button type="button" class="btn btn-primary" onclick="saveEntry()">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>

<script>
const slots = <?php echo json_encode(array_values($classSlots)); ?>;
const days = <?php echo json_encode(array_values($activeDays)); ?>;
const entryGrid = <?php echo json_encode($entryGrid); ?>;

function editEntry(day, slotId) {
    console.log('Edit entry clicked:', day, slotId);
    
    const dayInput = document.getElementById('edit_day');
    const slotInput = document.getElementById('edit_slot_id');
    const displayDay = document.getElementById('display_day');
    const displayPeriod = document.getElementById('display_period');
    const displayTime = document.getElementById('display_time');
    
    if (!dayInput || !slotInput) {
        console.error('Form elements not found!');
        return;
    }
    
    dayInput.value = day;
    slotInput.value = slotId;
    displayDay.textContent = day.charAt(0).toUpperCase() + day.slice(1);
    
    const slot = slots.find(s => s.id == slotId);
    if (slot) {
        displayPeriod.textContent = 'Period ' + slot.period_number;
        displayTime.textContent = slot.start_time + ' - ' + slot.end_time;
    } else {
        displayPeriod.textContent = '-';
        displayTime.textContent = '-';
    }
    
    // Check if there's an existing entry for this slot
    const entryKey = day + '_' + slotId;
    const existingEntry = entryGrid[entryKey];
    
    const clearBtn = document.getElementById('clearBtn');
    const entryTypeSelect = document.getElementById('entry_type');
    
    if (existingEntry) {
        // Pre-fill with existing data
        if (existingEntry.is_event) {
            entryTypeSelect.value = 'event';
            document.getElementById('event_name').value = existingEntry.event_name || '';
            document.getElementById('event_type').value = existingEntry.event_type || 'event';
        } else {
            entryTypeSelect.value = 'class';
            document.getElementById('edit_subject_id').value = existingEntry.subject_id || '';
            document.getElementById('edit_teacher_id').value = existingEntry.teacher_id || '';
        }
        toggleEntryType();
        if (clearBtn) clearBtn.style.display = 'inline-block';
    } else {
        // Reset form for new entry
        entryTypeSelect.value = 'class';
        document.getElementById('edit_subject_id').value = '';
        document.getElementById('edit_teacher_id').value = '';
        document.getElementById('event_name').value = '';
        document.getElementById('event_type').value = 'event';
        toggleEntryType();
        if (clearBtn) clearBtn.style.display = 'none';
    }
    
    document.getElementById('conflictWarning').style.display = 'none';
    
    // Open modal
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function openEditModal() {
    openModal('editModal');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function toggleEntryType() {
    const entryType = document.getElementById('entry_type').value;
    const classSection = document.getElementById('classSection');
    const eventSection = document.getElementById('eventSection');
    
    if (entryType === 'event') {
        classSection.style.display = 'none';
        eventSection.style.display = 'block';
        document.getElementById('edit_subject_id').removeAttribute('required');
        document.getElementById('edit_teacher_id').removeAttribute('required');
    } else {
        classSection.style.display = 'block';
        eventSection.style.display = 'none';
        document.getElementById('edit_subject_id').setAttribute('required', '');
        document.getElementById('edit_teacher_id').setAttribute('required', '');
    }
}

function fillEventData() {
    const select = document.getElementById('existing_event');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('event_name').value = selectedOption.value;
        document.getElementById('event_type').value = selectedOption.getAttribute('data-type');
    }
}

function clearEntry() {
    if (confirm('Are you sure you want to clear this entry?')) {
        document.getElementById('edit_subject_id').value = '';
        document.getElementById('edit_teacher_id').value = '';
        document.getElementById('event_name').value = '';
        saveEntry();
    }
}

function saveEntry() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const entryType = document.getElementById('entry_type').value;
    
    // Validate based on entry type
    if (entryType === 'class') {
        const subjectId = document.getElementById('edit_subject_id').value;
        const teacherId = document.getElementById('edit_teacher_id').value;
        
        if (!subjectId) {
            alert('Please select a subject');
            return;
        }
        if (!teacherId) {
            alert('Please select a teacher');
            return;
        }
    } else {
        const eventName = document.getElementById('event_name').value.trim();
        if (!eventName) {
            alert('Please enter an event name');
            return;
        }
        // Add event data to formData
        formData.append('is_event', '1');
        formData.append('event_name', eventName);
        formData.append('event_type', document.getElementById('event_type').value);
    }
    
    console.log('Saving entry...');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                alert('Entry saved successfully!');
                closeEditModal();
                location.reload();
            } else {
                document.getElementById('conflictWarning').style.display = 'block';
                document.getElementById('conflictMessage').textContent = data.message;
            }
        } catch (e) {
            alert('Error: ' + text);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
