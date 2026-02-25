<?php
/**
 * Timetable Creation Wizard
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Create Timetable';
$breadcrumb = [
    ['label' => 'Timetables', 'url' => 'index.php'],
    ['label' => 'Create']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('timetable', 'create');

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$institutionId = isset($_SESSION['timetable_institution']) ? $_SESSION['timetable_institution'] : 0;
$classId = isset($_SESSION['timetable_class']) ? $_SESSION['timetable_class'] : 0;
$sectionId = isset($_SESSION['timetable_section']) ? $_SESSION['timetable_section'] : 0;
$academicYear = isset($_SESSION['timetable_year']) ? $_SESSION['timetable_year'] : '';

// Get institutions
$institutions = getInstitutions('active');

// Handle step 1 - Select Institution, Class, Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $_SESSION['timetable_institution'] = intval($_POST['institution_id']);
    $_SESSION['timetable_class'] = intval($_POST['class_id']);
    $_SESSION['timetable_section'] = intval($_POST['section_id']);
    $_SESSION['timetable_year'] = sanitize($_POST['academic_year']);
    redirect('create.php?step=2');
}

// Handle step 2 - Generate timetable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    // Call generation API
    require_once __DIR__ . '/../../api/timetable_generate.php';
    
    $result = generateTimetable($_SESSION['timetable_institution'], $_SESSION['timetable_class'], $_SESSION['timetable_section'], $_SESSION['timetable_year']);
    
    if ($result['status'] === 'success') {
        $_SESSION['timetable_data'] = $result['timetable'];
        $_SESSION['timetable_id'] = $result['timetable_id'];
        showAlert('Timetable generated successfully', 'success');
        redirect('grid.php?id=' . $result['timetable_id']);
    } else {
        showAlert('Error generating timetable: ' . ($result['message'] ?? 'Unknown error'), 'danger');
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
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Timetable</h1>
        <p class="page-subtitle">Step <?php echo $step; ?> of 2</p>
    </div>
</div>

<!-- Progress Steps -->
<div style="display: flex; margin-bottom: 30px;">
    <div style="flex: 1; text-align: center; padding: 15px; background: <?php echo $step >= 1 ? 'var(--primary-color)' : 'var(--bg-light)'; ?>; color: <?php echo $step >= 1 ? 'white' : 'var(--text-light)'; ?>; border-radius: var(--radius-md) 0 0 var(--radius-md);">
        <i class="fas fa-list"></i> 1. Select Details
    </div>
    <div style="flex: 1; text-align: center; padding: 15px; background: <?php echo $step >= 2 ? 'var(--primary-color)' : 'var(--bg-light)'; ?>; color: <?php echo $step >= 2 ? 'white' : 'var(--text-light)'; ?>; border-radius: 0 var(--radius-md) var(--radius-md) 0;">
        <i class="fas fa-magic"></i> 2. Generate
    </div>
</div>

<?php if ($step === 1): ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Step 1: Select Institution, Class & Section</h3>
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
                
                <div class="form-group">
                    <label class="form-label required">Section</label>
                    <select name="section_id" id="section_id" class="form-control" required <?php echo empty($sections) ? 'disabled' : ''; ?>>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['id']; ?>" <?php echo $sectionId == $sec['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sec['name']); ?>
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
    const sectionSelect = document.getElementById('section_id');
    
    if (!institutionId) {
        classSelect.innerHTML = '<option value="">Select Class</option>';
        classSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
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
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
    });
}

function loadSections(classId) {
    const sectionSelect = document.getElementById('section_id');
    
    if (!classId) {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
        return;
    }
    
    fetchData('/ttc/api/get_sections.php?class_id=' + classId, function(error, response) {
        if (error) {
            console.error('Error loading sections:', error);
            return;
        }
        
        let options = '<option value="">Select Section</option>';
        if (Array.isArray(response)) {
            response.forEach(function(sec) {
                options += '<option value="' + sec.id + '">' + sec.name + '</option>';
            });
        }
        
        sectionSelect.innerHTML = options;
        sectionSelect.disabled = false;
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
        $section = dbFetch("SELECT * FROM sections WHERE id = ?", [$sectionId]);
        $subjects = getSubjectsByClass($classId);
        $teachers = getTeachersByInstitution($institutionId);
        $timeSlots = getTimeSlotsByInstitution($institutionId);
        $workingDays = getWorkingDaysByInstitution($institutionId);
        
        $activeDays = array_filter($workingDays, function($d) { return $d['is_working']; });
        $classSlots = array_filter($timeSlots, function($s) { return !$s['is_break'] && !$s['is_lunch']; });
        $totalSlots = count($activeDays) * count($classSlots);
        $requiredSlots = array_sum(array_column($subjects, 'weekly_hours'));
        ?>
        
        <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px;">Configuration Summary</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <small style="color: var(--text-light);">Institution</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($institution['name']); ?></p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Class - Section</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($class['name'] . ' - ' . $section['name']); ?></p>
                </div>
                <div>
                    <small style="color: var(--text-light);">Academic Year</small>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($academicYear); ?></p>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md);">
                <h5 style="margin-bottom: 10px;">Subjects (<?php echo count($subjects); ?>)</h5>
                <?php if (empty($subjects)): ?>
                    <p style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> No subjects found!</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($subjects as $subj): ?>
                            <li style="padding: 5px 0; border-bottom: 1px solid var(--border-color);">
                                <?php echo htmlspecialchars($subj['name']); ?> 
                                <span class="badge badge-info"><?php echo $subj['weekly_hours']; ?> hrs</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top: 10px;">
                        <strong>Total Required:</strong> <?php echo $requiredSlots; ?> periods/week
                    </p>
                <?php endif; ?>
            </div>
            
            <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md);">
                <h5 style="margin-bottom: 10px;">Available Slots</h5>
                <p><strong>Working Days:</strong> <?php echo count($activeDays); ?> days</p>
                <p><strong>Periods per Day:</strong> <?php echo count($classSlots); ?> periods</p>
                <p><strong>Total Available:</strong> <?php echo $totalSlots; ?> periods/week</p>
                
                <?php if ($requiredSlots > $totalSlots): ?>
                    <div class="alert alert-danger" style="margin-top: 15px;">
                        <i class="fas fa-exclamation-circle"></i>
                        Insufficient slots! Need <?php echo $requiredSlots; ?> but only have <?php echo $totalSlots; ?>.
                    </div>
                <?php elseif ($requiredSlots > 0): ?>
                    <div class="alert alert-success" style="margin-top: 15px;">
                        <i class="fas fa-check-circle"></i>
                        Sufficient slots available.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <?php if (empty($subjects)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Please add subjects to this class before generating a timetable.
                    </div>
                    <a href="/ttc/modules/subject/form.php?class=<?php echo $classId; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Subjects
                    </a>
                <?php elseif ($requiredSlots > $totalSlots): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Cannot generate timetable. Please add more time slots or reduce subject hours.
                    </div>
                    <a href="/ttc/modules/timeslot/index.php?institution=<?php echo $institutionId; ?>" class="btn btn-primary">
                        <i class="fas fa-clock"></i> Configure Time Slots
                    </a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-magic"></i> Generate Timetable
                    </button>
                <?php endif; ?>
                <a href="create.php?step=1" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
