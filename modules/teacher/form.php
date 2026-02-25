<?php
/**
 * Teacher Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = isset($_GET['id']) ? 'Edit Teacher' : 'Add Teacher';
$breadcrumb = [
    ['label' => 'Teachers', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('teacher', isset($_GET['id']) ? 'edit' : 'create');

$teacher = [
    'id' => '',
    'institution_id' => '',
    'employee_id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'qualification' => '',
    'max_periods_per_day' => 6,
    'max_periods_per_week' => 30,
    'status' => 'active'
];

// Get institutions
$institutions = getInstitutions('active');

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = dbFetch("SELECT * FROM teachers WHERE id = ?", [$id]);
    if ($data) {
        $teacher = $data;
    } else {
        showAlert('Teacher not found', 'danger');
        redirect('index.php');
    }
}

// Get teacher subjects
$teacherSubjects = [];
if ($teacher['id']) {
    $teacherSubjects = dbFetchAll("SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?", [$teacher['id']]);
    $teacherSubjects = array_column($teacherSubjects, 'subject_id');
}

// Get teacher availability
$availability = [];
if ($teacher['id']) {
    $availData = dbFetchAll("SELECT * FROM teacher_availability WHERE teacher_id = ?", [$teacher['id']]);
    foreach ($availData as $avail) {
        $availability[$avail['day_of_week']] = $avail;
    }
}

// Get all subjects for assignment
$allSubjects = dbFetchAll("SELECT s.*, c.name as class_name, i.name as institution_name 
                           FROM subjects s 
                           JOIN classes c ON s.class_id = c.id 
                           JOIN institutions i ON c.institution_id = i.id 
                           WHERE s.status = 'active' 
                           ORDER BY i.name, c.name, s.name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'institution_id' => intval($_POST['institution_id']),
        'employee_id' => sanitize($_POST['employee_id']),
        'name' => sanitize($_POST['name']),
        'email' => sanitize($_POST['email']),
        'phone' => sanitize($_POST['phone']),
        'qualification' => sanitize($_POST['qualification']),
        'max_periods_per_day' => intval($_POST['max_periods_per_day']),
        'max_periods_per_week' => intval($_POST['max_periods_per_week']),
        'status' => $_POST['status']
    ];
    
    if (empty($data['name']) || empty($data['employee_id']) || empty($data['institution_id'])) {
        showAlert('Name, Employee ID and Institution are required', 'danger');
    } else {
        if ($teacher['id']) {
            // Update teacher
            dbQuery(
                "UPDATE teachers SET institution_id = ?, employee_id = ?, name = ?, email = ?, phone = ?, qualification = ?, max_periods_per_day = ?, max_periods_per_week = ?, status = ? WHERE id = ?",
                [$data['institution_id'], $data['employee_id'], $data['name'], $data['email'], $data['phone'], $data['qualification'], $data['max_periods_per_day'], $data['max_periods_per_week'], $data['status'], $teacher['id']]
            );
            $teacherId = $teacher['id'];
            showAlert('Teacher updated successfully', 'success');
        } else {
            // Insert teacher
            $teacherId = dbInsert(
                "INSERT INTO teachers (institution_id, employee_id, name, email, phone, qualification, max_periods_per_day, max_periods_per_week, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$data['institution_id'], $data['employee_id'], $data['name'], $data['email'], $data['phone'], $data['qualification'], $data['max_periods_per_day'], $data['max_periods_per_week'], $data['status']]
            );
            showAlert('Teacher created successfully', 'success');
        }
        
        // Update subjects
        dbQuery("DELETE FROM teacher_subjects WHERE teacher_id = ?", [$teacherId]);
        if (!empty($_POST['subjects'])) {
            foreach ($_POST['subjects'] as $subjectId) {
                dbQuery("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", [$teacherId, intval($subjectId)]);
            }
        }
        
        // Update availability
        dbQuery("DELETE FROM teacher_availability WHERE teacher_id = ?", [$teacherId]);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            if (isset($_POST['available_days']) && in_array($day, $_POST['available_days'])) {
                dbQuery(
                    "INSERT INTO teacher_availability (teacher_id, day_of_week, is_available) VALUES (?, ?, TRUE)",
                    [$teacherId, $day]
                );
            }
        }
        
        redirect('index.php');
    }
}

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $teacher['id'] ? 'Edit Teacher' : 'Add Teacher'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <h3 style="margin-bottom: 20px; color: var(--text-dark);">Basic Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Institution</label>
                    <select name="institution_id" class="form-control" required>
                        <option value="">Select Institution</option>
                        <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" <?php echo $teacher['institution_id'] == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inst['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($teacher['employee_id']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <textarea name="qualification" class="form-control" rows="2"><?php echo htmlspecialchars($teacher['qualification']); ?></textarea>
            </div>
            
            <h3 style="margin: 30px 0 20px; color: var(--text-dark);">Workload Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Max Periods per Day</label>
                    <input type="number" name="max_periods_per_day" class="form-control" value="<?php echo $teacher['max_periods_per_day']; ?>" min="1" max="20">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Max Periods per Week</label>
                    <input type="number" name="max_periods_per_week" class="form-control" value="<?php echo $teacher['max_periods_per_week']; ?>" min="1" max="50">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $teacher['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $teacher['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <h3 style="margin: 30px 0 20px; color: var(--text-dark);">Available Days</h3>
            
            <div class="form-group">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <?php foreach ($days as $day): 
                        $isAvailable = isset($availability[$day]) && $availability[$day]['is_available'];
                    ?>
                        <label class="form-check" style="min-width: 100px;">
                            <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" <?php echo $isAvailable ? 'checked' : ''; ?>>
                            <span><?php echo getDayName($day); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <h3 style="margin: 30px 0 20px; color: var(--text-dark);">Assigned Subjects</h3>
            
            <div class="form-group">
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 15px;">
                    <?php if (empty($allSubjects)): ?>
                        <p style="color: var(--text-light);">No subjects available. Please add subjects first.</p>
                    <?php else: ?>
                        <?php foreach ($allSubjects as $subject): ?>
                            <label class="form-check" style="margin-bottom: 10px;">
                                <input type="checkbox" name="subjects[]" value="<?php echo $subject['id']; ?>" <?php echo in_array($subject['id'], $teacherSubjects) ? 'checked' : ''; ?>>
                                <span>
                                    <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                    <small style="color: var(--text-light); display: block;">
                                        <?php echo htmlspecialchars($subject['institution_name'] . ' - ' . $subject['class_name']); ?> | 
                                        <?php echo $subject['weekly_hours']; ?> hrs/week
                                    </small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $teacher['id'] ? 'Update' : 'Create'; ?> Teacher
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
