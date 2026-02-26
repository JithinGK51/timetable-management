<?php
/**
 * Section Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('section', isset($_GET['id']) ? 'edit' : 'create');

$pageTitle = isset($_GET['id']) ? 'Edit Section' : 'Add Section';
$breadcrumb = [
    ['label' => 'Sections', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$section = [
    'id' => '',
    'class_id' => '',
    'name' => '',
    'class_teacher_id' => '',
    'capacity' => 30,
    'room_number' => ''
];

// Get institutions
$institutions = getInstitutions('active');

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = dbFetch("SELECT * FROM sections WHERE id = ?", [$id]);
    if ($data) {
        $section = $data;
    } else {
        showAlert('Section not found', 'danger');
        redirect('index.php');
    }
}

// Get classes
$classes = dbFetchAll("SELECT c.*, i.name as institution_name FROM classes c JOIN institutions i ON c.institution_id = i.id WHERE c.status = 'active' ORDER BY i.name, c.name");

// Get teachers for class teacher dropdown
$teachers = dbFetchAll("SELECT * FROM teachers WHERE status = 'active' ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'class_id' => intval($_POST['class_id']),
        'name' => sanitize($_POST['name']),
        'class_teacher_id' => !empty($_POST['class_teacher_id']) ? intval($_POST['class_teacher_id']) : null,
        'capacity' => intval($_POST['capacity']),
        'room_number' => sanitize($_POST['room_number'])
    ];
    
    if (empty($data['name']) || empty($data['class_id'])) {
        showAlert('Section name and class are required', 'danger');
    } else {
        if ($section['id']) {
            dbQuery(
                "UPDATE sections SET class_id = ?, name = ?, class_teacher_id = ?, capacity = ?, room_number = ? WHERE id = ?",
                [$data['class_id'], $data['name'], $data['class_teacher_id'], $data['capacity'], $data['room_number'], $section['id']]
            );
            showAlert('Section updated successfully', 'success');
        } else {
            dbInsert(
                "INSERT INTO sections (class_id, name, class_teacher_id, capacity, room_number) VALUES (?, ?, ?, ?, ?)",
                [$data['class_id'], $data['name'], $data['class_teacher_id'], $data['capacity'], $data['room_number']]
            );
            showAlert('Section created successfully', 'success');
        }
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $section['id'] ? 'Edit Section' : 'Add Section'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>" <?php echo $section['class_id'] == $cls['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['institution_name'] . ' - ' . $cls['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Section Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($section['name']); ?>" placeholder="e.g., A, B, Section 1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class Teacher</label>
                    <select name="class_teacher_id" class="form-control">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php echo $section['class_teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['name'] . ' (' . $teacher['employee_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="<?php echo $section['capacity']; ?>" min="1" max="200">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($section['room_number']); ?>" placeholder="e.g., 101, Room A">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $section['id'] ? 'Update' : 'Create'; ?> Section
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
