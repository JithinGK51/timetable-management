<?php
/**
 * Single Day Multi-Section Timetable Creation
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('timetable', 'create');

$pageTitle = 'Create Single Day Timetable';
$breadcrumb = [
    ['label' => 'Timetables', 'url' => 'index.php'],
    ['label' => 'Create Single Day']
];

require_once __DIR__ . '/../../includes/header.php';

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$institutionId = isset($_SESSION['day_timetable_institution']) ? $_SESSION['day_timetable_institution'] : 0;
$classId = isset($_SESSION['day_timetable_class']) ? $_SESSION['day_timetable_class'] : 0;
$sectionIds = isset($_SESSION['day_timetable_sections']) ? $_SESSION['day_timetable_sections'] : [];
$dayOfWeek = isset($_SESSION['day_timetable_day']) ? $_SESSION['day_timetable_day'] : '';
$academicYear = isset($_SESSION['day_timetable_year']) ? $_SESSION['day_timetable_year'] : '';

// Get institutions
$institutions = getInstitutions('active');

// Handle step 1 - Select Institution, Class, Sections, and Day
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $_SESSION['day_timetable_institution'] = intval($_POST['institution_id']);
    $_SESSION['day_timetable_class'] = intval($_POST['class_id']);
    $_SESSION['day_timetable_sections'] = $_POST['section_ids'] ?? [];
    $_SESSION['day_timetable_day'] = sanitize($_POST['day_of_week']);
    $_SESSION['day_timetable_year'] = sanitize($_POST['academic_year']);
    redirect('create_day.php?step=2');
}

// Handle step 2 - Generate timetable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    // Call generation API
    require_once __DIR__ . '/../../api/timetable_generate_day.php';
    
    $result = generateSingleDayTimetable(
        $_SESSION['day_timetable_institution'], 
        $_SESSION['day_timetable_class'], 
        $_SESSION['day_timetable_sections'], 
        $_SESSION['day_timetable_day'], 
        $_SESSION['day_timetable_year']
    );
    
    if ($result['status'] === 'success') {
        $_SESSION['day_timetable_results'] = $result['timetables'];
        showAlert('Timetables generated successfully for ' . count($result['timetables']) . ' section(s)', 'success');
        redirect('create_day.php?step=3');
    } else {
        showAlert('Error generating timetables: ' . ($result['message'] ?? 'Unknown error'), 'danger');
    }
}

// Get academic year setting
$defaultYear = getSetting('academic_year') ?: date('Y') . '-' . (date('Y') + 1);

// Get classes and sections for dropdowns
$classes = [];
$sections = [];
if ($institutionId) {
    $classes = getClassesByInstitution($institutionId);
}
if ($classId) {
    $sections = getSectionsByClass($classId);
}

// Days of week
$daysOfWeek = [
    'monday' => 'Monday',
    'tuesday' => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday',
    'saturday' => 'Saturday',
    'sunday' => 'Sunday'
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Single Day Timetable</h1>
        <p class="page-subtitle">Generate timetables for multiple sections on a single day</p>
    </div>
</div>

<!-- Progress Steps -->
<div style="display: flex; margin-bottom: 30px;">
    <div style="flex: 1; text-align: center; padding: 15px; background: <?php echo $step >= 1 ? 'var(--primary-color)' : 'var(--bg-light)'; ?>; color: <?php echo $step >= 1 ? 'white' : 'var(--text-light)'; ?>; border-radius: var(--radius-md) 0 0 var(--radius-md);">
        <i class="fas fa-list"></i> 1. Select Details
    </div>
    <div style="flex: 1; text-align: center; padding: 15px; background: <?php echo $step >= 2 ? 'var(--primary-color)' : 'var(--bg-light)'; ?>; color: <?php echo $step >= 2 ? 'white' : 'var(--text-light)'; ?>">
        <i class="fas fa-magic"></i> 2. Review & Generate
    </div>
    <div style="flex: 1; text-align: center; padding: 15px; background: <?php echo $step >= 3 ? 'var(--primary-color)' : 'var(--bg-light)'; ?>; color: <?php echo $step >= 3 ? 'white' : 'var(--text-light)'; ?>; border-radius: 0 var(--radius-md) var(--radius-md) 0;">
        <i class="fas fa-check"></i> 3. Results
    </div>
</div>

<?php if ($step === 1): ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Step 1: Select Institution, Class, Sections & Day</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Institution</label>
                    <select name="institution_id" id="institution_id" class="form-control" required onchange="loadClasses(this.value)">
                        <option value="">Select Institution</option>
                        <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" <?php echo $institutionId == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inst['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Class</label>
                    <select name="class_id" id="class_id" class="form-control" required onchange="loadSections(this.value)" <?php echo empty($classes) ? 'disabled' : ''; ?>>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>" <?php echo $classId == $cls['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label required">Sections (Select Multiple)</label>
                    <div id="section_checkboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; padding: 15px; background: var(--bg-light); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php if (empty($sections)): ?>
                            <p style="color: var(--text-light); margin: 0;">Please select a class first</p>
                        <?php else: ?>
                            <?php foreach ($sections as $sec): ?>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: var(--radius-sm); transition: background 0.2s;" onmouseover="this.style.background='var(--bg-white)'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" name="section_ids[]" value="<?php echo $sec['id']; ?>" <?php echo in_array($sec['id'], (array)$sectionIds) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                                    <span>
                                        <?php echo htmlspecialchars($sec['name']); ?>
                                        <?php echo $sec['room_number'] ? '<small style="color: var(--text-light);">(' . htmlspecialchars($sec['room_number']) . ')</small>' : ''; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small style="color: var(--text-light);">Check the sections you want to generate timetables for</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Day of Week</label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">Select Day</option>
                        <?php foreach ($daysOfWeek as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $dayOfWeek === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($academicYear ?: $defaultYear); ?>" placeholder="e.g., 2025-2026" required>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Next Step
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function loadClasses(institutionId) {
    const classSelect = document.getElementById('class_id');
    const sectionCheckboxes = document.getElementById('section_checkboxes');
    
    if (!institutionId) {
        classSelect.innerHTML = '<option value="">Select Class</option>';
        classSelect.disabled = true;
        sectionCheckboxes.innerHTML = '<p style="color: var(--text-light); margin: 0;">Please select a class first</p>';
        return;
    }
    
    fetchData('/ttc/api/get_classes.php?institution_id=' + institutionId, function(error, response) {
        if (error) {
            console.error('Error loading classes:', error);
            return;
        }
        
        let options = '<option value="">Select Class</option>';
        if (Array.isArray(response)) {
            response.forEach(function(cls) {
                options += '<option value="' + cls.id + '">' + cls.name + '</option>';
            });
        }
        
        classSelect.innerHTML = options;
        classSelect.disabled = false;
        sectionCheckboxes.innerHTML = '<p style="color: var(--text-light); margin: 0;">Please select a class first</p>';
    });
}

function loadSections(classId) {
    const sectionCheckboxes = document.getElementById('section_checkboxes');
    
    if (!classId) {
        sectionCheckboxes.innerHTML = '<p style="color: var(--text-light); margin: 0;">Please select a class first</p>';
        return;
    }
    
    fetchData('/ttc/api/get_sections.php?class_id=' + classId, function(error, response) {
        if (error) {
            console.error('Error loading sections:', error);
            return;
        }
        
        let checkboxes = '';
        if (Array.isArray(response) && response.length > 0) {
            response.forEach(function(sec) {
                const roomInfo = sec.room_number ? ' <small style="color: var(--text-light);">(' + sec.room_number + ')</small>' : '';
                checkboxes += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: var(--radius-sm); transition: background 0.2s;" onmouseover="this.style.background=\'var(--bg-white)\'" onmouseout="this.style.background=\'transparent\'">';
                checkboxes += '<input type="checkbox" name="section_ids[]" value="' + sec.id + '" style="width: 18px; height: 18px; cursor: pointer;">';
                checkboxes += '<span>' + sec.name + roomInfo + '</span>';
                checkboxes += '</label>';
            });
        } else {
            checkboxes = '<p style="color: var(--text-light); margin: 0;">No sections found for this class</p>';
        }
        
        sectionCheckboxes.innerHTML = checkboxes;
    });
}
</script>

<?php elseif ($step === 2): ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Step 2: Review & Generate</h3>
    </div>
    <div class="card-body">
        <?php
        // Fetch data for review
        $institution = getInstitutionById($institutionId);
        $class = dbFetch("SELECT * FROM classes WHERE id = ?", [$classId]);
        $subjects = getSubjectsByClass($classId);
        $teachers = getTeachersByInstitution($institutionId);
        $timeSlots = getTimeSlotsByInstitution($institutionId);
        $workingDays = getWorkingDaysByInstitution($institutionId);
        
        // Get selected sections info
        $selectedSections = [];
        foreach ($sectionIds as $sid) {
            $sec = dbFetch("SELECT * FROM sections WHERE id = ?", [$sid]);
            if ($sec) $selectedSections[] = $sec;
        }
        
        $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
        $totalSlots = count($classSlots);
        $requiredSlots = array_sum(array_column($subjects, 'weekly_hours'));
        
        // Check if selected day is a working day
        $isWorkingDay = false;
        foreach ($workingDays as $wd) {
            if ($wd['day_of_week'] === $dayOfWeek && $wd['is_working']) {
                $isWorkingDay = true;
                break;
            }
        }
        ?>
        
        <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px;">Configuration Summary</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <small style="color: var(--text-light);">Institution</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($institution['name']); ?></p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Class</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($class['name']); ?></p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Selected Sections</small>
                    <p style="font-weight: 600;"><?php echo count($selectedSections); ?> section(s)</p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Day</small>
                    <p style="font-weight: 600;"><?php echo ucfirst($dayOfWeek); ?></p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Academic Year</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($academicYear); ?></p>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md);">
                <h5 style="margin-bottom: 10px;">Selected Sections</h5>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($selectedSections as $sec): ?>
                        <li style="padding: 5px 0; border-bottom: 1px solid var(--border-color);">
                            <i class="fas fa-users"></i> 
                            <?php echo htmlspecialchars($sec['name']); ?>
                            <?php echo $sec['room_number'] ? '<small>(' . htmlspecialchars($sec['room_number']) . ')</small>' : ''; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md);">
                <h5 style="margin-bottom: 10px;">Available Slots for <?php echo ucfirst($dayOfWeek); ?></h5>
                <p><strong>Periods:</strong> <?php echo $totalSlots; ?> periods</p>
                <p><strong>Subjects:</strong> <?php echo count($subjects); ?> subjects</p>
                <p><strong>Teachers:</strong> <?php echo count($teachers); ?> teachers</p>
                
                <?php if (!$isWorkingDay): ?>
                    <div class="alert alert-danger" style="margin-top: 15px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo ucfirst($dayOfWeek); ?> is not a working day for this institution!
                    </div>
                <?php elseif (empty($subjects)): ?>
                    <div class="alert alert-danger" style="margin-top: 15px;">
                        <i class="fas fa-exclamation-circle"></i>
                        No subjects found for this class.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-top: 15px;">
                        <i class="fas fa-check-circle"></i>
                        Ready to generate timetables.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px;">
            <h5 style="margin-bottom: 10px;">Subjects (<?php echo count($subjects); ?>)</h5>
            <?php if (empty($subjects)): ?>
                <p style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> No subjects found!</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($subjects as $subj): ?>
                        <li style="padding: 5px 0; border-bottom: 1px solid var(--border-color);">
                            <?php echo htmlspecialchars($subj['name']); ?> 
                            <span class="badge badge-info"><?php echo $subj['weekly_hours']; ?> hrs/week</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <?php if (empty($subjects)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Please add subjects to this class before generating timetables.
                    </div>
                    <a href="/ttc/modules/subject/form.php?class=<?php echo $classId; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Subjects
                    </a>
                <?php elseif (!$isWorkingDay): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Cannot generate timetable. <?php echo ucfirst($dayOfWeek); ?> is not a working day.
                    </div>
                    <a href="/ttc/modules/timeslot/index.php?institution=<?php echo $institutionId; ?>" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> Configure Working Days
                    </a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-magic"></i> Generate Timetables for <?php echo count($selectedSections); ?> Section(s)
                    </button>
                <?php endif; ?>
                <a href="create_day.php?step=1" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($step === 3): ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Step 3: Generation Results</h3>
    </div>
    <div class="card-body">
        <?php
        $results = $_SESSION['day_timetable_results'] ?? [];
        
        if (empty($results)): 
        ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No timetables were generated.
            </div>
        <?php else: ?>
            <div class="alert alert-success" style="margin-bottom: 30px;">
                <i class="fas fa-check-circle"></i> 
                Successfully generated <?php echo count($results); ?> timetable(s) for <?php echo ucfirst($dayOfWeek); ?>!
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($results as $result): 
                    $timetable = getTimetableById($result['timetable_id']);
                    $section = dbFetch("SELECT * FROM sections WHERE id = ?", [$result['section_id']]);
                ?>
                    <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); border-left: 4px solid var(--success-color);">
                        <h5 style="margin-bottom: 10px;">
                            <i class="fas fa-calendar-check"></i> 
                            <?php echo htmlspecialchars($section['name'] ?? 'Unknown Section'); ?>
                        </h5>
                        <p style="margin-bottom: 5px;">
                            <small style="color: var(--text-light);">Timetable ID:</small> 
                            #<?php echo $result['timetable_id']; ?>
                        </p>
                        <p style="margin-bottom: 5px;">
                            <small style="color: var(--text-light);">Entries:</small> 
                            <?php echo count($result['timetable'] ?? []); ?> periods
                        </p>
                        <p style="margin-bottom: 15px;">
                            <span class="badge badge-success">Draft</span>
                        </p>
                        <a href="grid.php?id=<?php echo $result['timetable_id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View & Edit
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group" style="margin-top: 30px;">
            <a href="create_day.php?step=1" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Another
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> View All Timetables
            </a>
        </div>
    </div>
</div>

<?php 
// Clear session data after showing results
if ($step === 3) {
    unset($_SESSION['day_timetable_institution']);
    unset($_SESSION['day_timetable_class']);
    unset($_SESSION['day_timetable_sections']);
    unset($_SESSION['day_timetable_day']);
    unset($_SESSION['day_timetable_year']);
    unset($_SESSION['day_timetable_results']);
}
?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
