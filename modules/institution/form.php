<?php
/**
 * Institution Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('institution', isset($_GET['id']) ? 'edit' : 'create');

$pageTitle = isset($_GET['id']) ? 'Edit Institution' : 'Add Institution';
$breadcrumb = [
    ['label' => 'Institutions', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$institution = [
    'id' => '',
    'name' => '',
    'type' => 'school',
    'address' => '',
    'contact_email' => '',
    'contact_phone' => '',
    'status' => 'active'
];

// Edit mode - fetch existing data
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = getInstitutionById($id);
    if ($data) {
        $institution = $data;
    } else {
        showAlert('Institution not found', 'danger');
        redirect('index.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'type' => $_POST['type'],
        'address' => sanitize($_POST['address']),
        'contact_email' => sanitize($_POST['contact_email']),
        'contact_phone' => sanitize($_POST['contact_phone']),
        'status' => $_POST['status']
    ];
    
    if (empty($data['name'])) {
        showAlert('Institution name is required', 'danger');
    } else {
        if ($institution['id']) {
            // Update
            dbQuery(
                "UPDATE institutions SET name = ?, type = ?, address = ?, contact_email = ?, contact_phone = ?, status = ? WHERE id = ?",
                [$data['name'], $data['type'], $data['address'], $data['contact_email'], $data['contact_phone'], $data['status'], $institution['id']]
            );
            showAlert('Institution updated successfully', 'success');
        } else {
            // Insert
            $newId = dbInsert(
                "INSERT INTO institutions (name, type, address, contact_email, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?)",
                [$data['name'], $data['type'], $data['address'], $data['contact_email'], $data['contact_phone'], $data['status']]
            );
            
            // Create default working days for the institution
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($days as $day) {
                dbQuery(
                    "INSERT INTO working_days (institution_id, day_of_week, is_working) VALUES (?, ?, TRUE)",
                    [$newId, $day]
                );
            }
            
            showAlert('Institution created successfully', 'success');
        }
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $institution['id'] ? 'Edit Institution' : 'Add Institution'; ?></h1>
        <p class="page-subtitle"><?php echo $institution['id'] ? 'Update institution details' : 'Create a new institution'; ?></p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" id="institutionForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Institution Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($institution['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Institution Type</label>
                    <select name="type" class="form-control" required>
                        <option value="school" <?php echo $institution['type'] === 'school' ? 'selected' : ''; ?>>School</option>
                        <option value="college" <?php echo $institution['type'] === 'college' ? 'selected' : ''; ?>>College</option>
                        <option value="university" <?php echo $institution['type'] === 'university' ? 'selected' : ''; ?>>University</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($institution['address']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($institution['contact_email']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="tel" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($institution['contact_phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $institution['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $institution['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $institution['id'] ? 'Update' : 'Create'; ?> Institution
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
