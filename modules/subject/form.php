<?php
/**
 * Subject Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = isset($_GET['id']) ? 'Edit Subject' : 'Add Subject';
$breadcrumb = [
    ['label' => 'Subjects', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('subject', isset($_GET['id']) ? 'edit' : 'create');

$subject = [
    'id' => '',
    'class_id' => '',
    'name' => '',
    'code' => '',
    'weekly_hours' => 1,
    'type' => 'theory',
    'description' => ''
];

// Get institutions
$institutions = getInstitutions('active');

// Edit mode
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = dbFetch("SELECT * FROM subjects WHERE id = ?", [$id]);
    if ($data) {
        $subject = $data;
    } else {
        showAlert('Subject not found', 'danger');
        redirect('index.php');
    }
}

// Get classes
$classes = dbFetchAll("SELECT c.*, i.name as institution_name FROM classes c JOIN institutions i ON c.institution_id = i.id WHERE c.status = 'active' ORDER BY i.name, c.name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'class_id' => intval($_POST['class_id']),
        'name' => sanitize($_POST['name']),
        'code' => sanitize($_POST['code']),
        'weekly_hours' => intval($_POST['weekly_hours']),
        'type' => $_POST['type'],
        'description' => sanitize($_POST['description'])
    ];
    
    if (empty($data['name']) || empty($data['class_id'])) {
        showAlert('Subject name and class are required', 'danger');
    } elseif ($data['weekly_hours'] < 1 || $data['weekly_hours'] > 40) {
        showAlert('Weekly hours must be between 1 and 40', 'danger');
    } else {
        if ($subject['id']) {
            dbQuery(
                "UPDATE subjects SET class_id = ?, name = ?, code = ?, weekly_hours = ?, type = ?, description = ? WHERE id = ?",
                [$data['class_id'], $data['name'], $data['code'], $data['weekly_hours'], $data['type'], $data['description'], $subject['id']]
            );
            showAlert('Subject updated successfully', 'success');
        } else {
            dbInsert(
                "INSERT INTO subjects (class_id, name, code, weekly_hours, type, description) VALUES (?, ?, ?, ?, ?, ?)",
                [$data['class_id'], $data['name'], $data['code'], $data['weekly_hours'], $data['type'], $data['description']]
            );
            showAlert('Subject created successfully', 'success');
        }
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $subject['id'] ? 'Edit Subject' : 'Add Subject'; ?></h1>
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
                            <option value="<?php echo $cls['id']; ?>" <?php echo $subject['class_id'] == $cls['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['institution_name'] . ' - ' . $cls['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Subject Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Subject Code</label>
                    <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($subject['code']); ?>" placeholder="e.g., MATH101">
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Weekly Hours</label>
                    <input type="number" name="weekly_hours" class="form-control" value="<?php echo $subject['weekly_hours']; ?>" min="1" max="40" required>
                    <small style="color: var(--text-light);">Number of periods per week</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Subject Type</label>
                    <select name="type" class="form-control" required>
                        <option value="theory" <?php echo $subject['type'] === 'theory' ? 'selected' : ''; ?>>Theory</option>
                        <option value="lab" <?php echo $subject['type'] === 'lab' ? 'selected' : ''; ?>>Lab</option>
                        <option value="both" <?php echo $subject['type'] === 'both' ? 'selected' : ''; ?>>Theory + Lab</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($subject['description']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $subject['id'] ? 'Update' : 'Create'; ?> Subject
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
