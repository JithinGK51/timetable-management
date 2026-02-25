<?php
/**
 * Sub-Admin Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = isset($_GET['id']) ? 'Edit Sub-Admin' : 'Add Sub-Admin';
$breadcrumb = [
    ['label' => 'Sub-Admins', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$admin = ['name' => '', 'username' => '', 'email' => '', 'role_id' => '', 'status' => 'active'];

// Get available roles
$roles = dbFetchAll("SELECT id, name FROM roles WHERE status = 'active' ORDER BY name");

// Load existing admin
if ($id) {
    $adminData = dbFetch("SELECT * FROM admins WHERE id = ? AND is_super_admin = 0", [$id]);
    if ($adminData) {
        $admin = $adminData;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roleId = intval($_POST['role_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!$id && empty($password)) $errors[] = 'Password is required for new admin';
    
    // Check username uniqueness
    $existing = dbFetch("SELECT id FROM admins WHERE username = ? AND id != ?", [$username, $id]);
    if ($existing) $errors[] = 'Username already exists';
    
    if (empty($errors)) {
        if ($id) {
            // Update
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                dbQuery("UPDATE admins SET name = ?, username = ?, email = ?, role_id = ?, password = ?, plain_password = ?, status = ? WHERE id = ?",
                    [$name, $username, $email, $roleId, $hash, $password, $status, $id]);
            } else {
                dbQuery("UPDATE admins SET name = ?, username = ?, email = ?, role_id = ?, status = ? WHERE id = ?",
                    [$name, $username, $email, $roleId, $status, $id]);
            }
            showAlert('Sub-admin updated successfully', 'success');
        } else {
            // Create
            $hash = password_hash($password, PASSWORD_DEFAULT);
            dbQuery("INSERT INTO admins (name, username, email, password, plain_password, role_id, is_super_admin, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?)",
                [$name, $username, $email, $hash, $password, $roleId, $status]);
            showAlert('Sub-admin created successfully', 'success');
        }
        redirect('/ttc/modules/subadmin/index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $id ? 'Edit Sub-Admin' : 'Add Sub-Admin'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" 
                           value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" 
                           value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-input">
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" 
                            <?php echo $admin['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password <?php echo $id ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" class="form-input" 
                           <?php echo $id ? '' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active" <?php echo $admin['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $admin['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Sub-Admin</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
