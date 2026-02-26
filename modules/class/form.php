<?php
/**
 * Class Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('class', isset($_GET['id']) ? 'edit' : 'create');

$pageTitle = isset($_GET['id']) ? 'Edit Class' : 'Add Class';
$breadcrumb = [
    ['label' => 'Classes', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$class = [
    'id' => '',
    'institution_id' => '',
    'department_id' => '',
    'name' => '',
    'code' => '',
    'description' => ''
];

// Get institutions
$institutions = getInstitutions('active');

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = dbFetch("SELECT * FROM classes WHERE id = ?", [$id]);
    if ($data) {
        $class = $data;
    } else {
        showAlert('Class not found', 'danger');
        redirect('index.php');
    }
}

// Get departments for selected institution
$departments = [];
if ($class['institution_id']) {
    $departments = dbFetchAll("SELECT * FROM departments WHERE institution_id = ? AND status = 'active'", [$class['institution_id']]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'institution_id' => intval($_POST['institution_id']),
        'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
        'name' => sanitize($_POST['name']),
        'code' => sanitize($_POST['code']),
        'description' => sanitize($_POST['description'])
    ];
    
    if (empty($data['name']) || empty($data['institution_id'])) {
        showAlert('Class name and institution are required', 'danger');
    } else {
        if ($class['id']) {
            dbQuery(
                "UPDATE classes SET institution_id = ?, department_id = ?, name = ?, code = ?, description = ? WHERE id = ?",
                [$data['institution_id'], $data['department_id'], $data['name'], $data['code'], $data['description'], $class['id']]
            );
            showAlert('Class updated successfully', 'success');
        } else {
            dbInsert(
                "INSERT INTO classes (institution_id, department_id, name, code, description) VALUES (?, ?, ?, ?, ?)",
                [$data['institution_id'], $data['department_id'], $data['name'], $data['code'], $data['description']]
            );
            showAlert('Class created successfully', 'success');
        }
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $class['id'] ? 'Edit Class' : 'Add Class'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Institution</label>
                    <select name="institution_id" id="institution_id" class="form-control" required onchange="loadDepartments(this.value)">
                        <option value="">Select Institution</option>
                        <?php foreach ($institutions as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>" <?php echo $class['institution_id'] == $inst['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inst['name']); ?> (<?php echo ucfirst($inst['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-control" <?php echo empty($departments) ? 'disabled' : ''; ?>>
                        <option value="">No Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $class['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Class Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($class['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Class Code</label>
                    <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($class['code']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($class['description']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $class['id'] ? 'Update' : 'Create'; ?> Class
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function loadDepartments(institutionId) {
    const deptSelect = document.getElementById('department_id');
    if (!institutionId) {
        deptSelect.innerHTML = '<option value="">No Department</option>';
        deptSelect.disabled = true;
        return;
    }
    
    fetchData('/ttc/api/get_departments.php?institution_id=' + institutionId, function(error, response) {
        if (error) {
            console.error('Error loading departments:', error);
            return;
        }
        
        let options = '<option value="">No Department</option>';
        if (Array.isArray(response)) {
            response.forEach(function(dept) {
                options += '<option value="' + dept.id + '">' + dept.name + '</option>';
            });
        }
        
        deptSelect.innerHTML = options;
        deptSelect.disabled = false;
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
