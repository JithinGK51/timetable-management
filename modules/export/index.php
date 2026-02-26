<?php
/**
 * PDF Export Module
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('export', 'view');

$pageTitle = 'Export PDF';
$breadcrumb = [
    ['label' => 'Export PDF']
];

require_once __DIR__ . '/../../includes/header.php';

// Get institutions
$institutions = getInstitutions('active');

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exportType = $_POST['export_type'];
    $institutionId = intval($_POST['institution_id']);
    $classId = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $sectionId = !empty($_POST['section_id']) ? intval($_POST['section_id']) : null;
    $teacherId = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    
    // Redirect to PDF generation
    $params = http_build_query([
        'type' => $exportType,
        'institution' => $institutionId,
        'class' => $classId,
        'section' => $sectionId,
        'teacher' => $teacherId
    ]);
    
    redirect('generate.php?' . $params);
}

// Get classes and teachers for dropdowns
$classes = dbFetchAll("SELECT c.*, i.name as institution_name FROM classes c JOIN institutions i ON c.institution_id = i.id WHERE c.status = 'active' ORDER BY i.name, c.name");
$teachers = dbFetchAll("SELECT t.*, i.name as institution_name FROM teachers t JOIN institutions i ON t.institution_id = i.id WHERE t.status = 'active' ORDER BY i.name, t.name");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Export Timetable</h1>
        <p class="page-subtitle">Generate PDF reports</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" target="_blank">
            <div class="form-group">
                <label class="form-label required">Export Type</label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <label class="form-check" style="padding: 15px; border: 2px solid var(--border-color); border-radius: var(--radius-md); min-width: 200px;">
                        <input type="radio" name="export_type" value="class" checked onchange="updateForm()">
                        <span><i class="fas fa-graduation-cap"></i> <strong>Class Wise</strong><br><small>Export timetable for a specific class</small></span>
                    </label>
                    <label class="form-check" style="padding: 15px; border: 2px solid var(--border-color); border-radius: var(--radius-md); min-width: 200px;">
                        <input type="radio" name="export_type" value="section" onchange="updateForm()">
                        <span><i class="fas fa-users"></i> <strong>Section Wise</strong><br><small>Export timetable for a specific section</small></span>
                    </label>
                    <label class="form-check" style="padding: 15px; border: 2px solid var(--border-color); border-radius: var(--radius-md); min-width: 200px;">
                        <input type="radio" name="export_type" value="teacher" onchange="updateForm()">
                        <span><i class="fas fa-chalkboard-teacher"></i> <strong>Teacher Wise</strong><br><small>Export timetable for a specific teacher</small></span>
                    </label>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Institution</label>
                    <select name="institution_id" id="institution_id" class="form-control" required>
                        <option value="">Select Institution</option>
                        <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>">
                                <?php echo htmlspecialchars($inst['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div id="class_section_fields">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-control">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?php echo $cls['id']; ?>" data-institution="<?php echo $cls['institution_id']; ?>">
                                    <?php echo htmlspecialchars($cls['institution_name'] . ' - ' . $cls['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-control">
                            <option value="">All Sections</option>
                            <?php 
                            $sections = dbFetchAll("SELECT s.*, c.name as class_name FROM sections s JOIN classes c ON s.class_id = c.id WHERE s.status = 'active' ORDER BY c.name, s.name");
                            foreach ($sections as $sec): 
                            ?>
                                <option value="<?php echo $sec['id']; ?>" data-class="<?php echo $sec['class_id']; ?>">
                                    <?php echo htmlspecialchars($sec['class_name'] . ' - ' . $sec['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div id="teacher_field" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_id" id="teacher_id" class="form-control">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" data-institution="<?php echo $teacher['institution_id']; ?>">
                                    <?php echo htmlspecialchars($teacher['institution_name'] . ' - ' . $teacher['name'] . ' (' . $teacher['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateForm() {
    const exportType = document.querySelector('input[name="export_type"]:checked').value;
    const classSectionFields = document.getElementById('class_section_fields');
    const teacherField = document.getElementById('teacher_field');
    
    if (exportType === 'teacher') {
        classSectionFields.style.display = 'none';
        teacherField.style.display = 'block';
    } else {
        classSectionFields.style.display = 'block';
        teacherField.style.display = 'none';
    }
}

// Filter dropdowns based on institution
document.getElementById('institution_id').addEventListener('change', function() {
    const institutionId = this.value;
    
    // Filter classes
    const classSelect = document.getElementById('class_id');
    Array.from(classSelect.options).forEach(option => {
        if (!option.value || option.dataset.institution === institutionId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    classSelect.value = '';
    
    // Filter teachers
    const teacherSelect = document.getElementById('teacher_id');
    Array.from(teacherSelect.options).forEach(option => {
        if (!option.value || option.dataset.institution === institutionId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    teacherSelect.value = '';
});

// Filter sections based on class
document.getElementById('class_id').addEventListener('change', function() {
    const classId = this.value;
    
    const sectionSelect = document.getElementById('section_id');
    Array.from(sectionSelect.options).forEach(option => {
        if (!option.value || option.dataset.class === classId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    sectionSelect.value = '';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
