<?php
/**
 * Sub-Admin Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Sub-Admins';
$breadcrumb = [
    ['label' => 'Sub-Admins']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('subadmin', 'view');

// Handle delete action
if (isset($_GET['delete']) && hasPermission('subadmin', 'delete')) {
    $id = intval($_GET['delete']);
    // Prevent self-deletion
    if ($id !== $_SESSION['admin_id']) {
        dbQuery("UPDATE admins SET status = 'inactive' WHERE id = ?", [$id]);
        showAlert('Sub-admin deactivated successfully', 'success');
    } else {
        showAlert('Cannot deactivate yourself', 'error');
    }
    redirect('/ttc/modules/subadmin/index.php');
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    requirePermission('subadmin', 'edit');
    $id = intval($_POST['admin_id']);
    $newPassword = $_POST['new_password'];
    
    if (strlen($newPassword) >= 6) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        dbQuery("UPDATE admins SET password = ?, plain_password = ? WHERE id = ?", [$hash, $newPassword, $id]);
        showAlert('Password updated successfully', 'success');
    } else {
        showAlert('Password must be at least 6 characters', 'error');
    }
    redirect('/ttc/modules/subadmin/index.php');
}

// Get all sub-admins (non-super admins)
$admins = dbFetchAll("SELECT a.*, r.name as role_name 
    FROM admins a 
    LEFT JOIN roles r ON a.role_id = r.id 
    WHERE a.is_super_admin = 0 
    ORDER BY a.created_at DESC");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sub-Admin Management</h1>
        <p class="page-subtitle">Manage system administrators</p>
    </div>
    <?php if (hasPermission('subadmin', 'create')): ?>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Sub-Admin
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($admin['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td>
                        <?php 
                        $plainPwd = $admin['plain_password'] ?? 'admin123';
                        ?>
                        <span class="password-display" id="pwd-<?php echo $admin['id']; ?>">••••••</span>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="togglePassword(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($plainPwd); ?>')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><?php echo htmlspecialchars($admin['role_name'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $admin['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($admin['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="form.php?id=<?php echo $admin['id']; ?>" class="action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (hasPermission('subadmin', 'edit')): ?>
                            <button type="button" class="action-btn" style="background: var(--warning-color); color: white;" 
                                    title="Change Password" onclick="openPasswordModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name']); ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($admin['id'] !== $_SESSION['admin_id'] && hasPermission('subadmin', 'delete')): ?>
                            <a href="?delete=<?php echo $admin['id']; ?>" class="action-btn delete" title="Deactivate" 
                               onclick="return confirm('Deactivate this admin?')">
                                <i class="fas fa-ban"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (hasPermission('subadmin', 'edit')): ?>
<!-- Change Password Modal -->
<div class="modal-overlay" id="passwordModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Change Password</h3>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="passwordForm" method="POST">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" id="password_admin_id" name="admin_id">
                
                <div class="form-group">
                    <label class="form-label">Admin</label>
                    <input type="text" id="password_admin_name" class="form-input" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="text" name="new_password" id="new_password" class="form-input" required minlength="6">
                    <small style="color: var(--text-light);">Minimum 6 characters</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="savePassword()">Update Password</button>
        </div>
    </div>
</div>

<script>
function togglePassword(id, password) {
    const span = document.getElementById('pwd-' + id);
    if (span.textContent === '••••••') {
        span.textContent = password;
    } else {
        span.textContent = '••••••';
    }
}

function openPasswordModal(id, name) {
    document.getElementById('password_admin_id').value = id;
    document.getElementById('password_admin_name').value = name;
    document.getElementById('new_password').value = '';
    
    const modal = document.getElementById('passwordModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function savePassword() {
    const form = document.getElementById('passwordForm');
    const password = document.getElementById('new_password').value;
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters');
        return;
    }
    
    form.submit();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
