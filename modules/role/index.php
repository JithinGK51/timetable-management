<?php
/**
 * Role Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Roles';
$breadcrumb = [
    ['label' => 'Roles']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('role', 'view');

// Handle delete action
if (isset($_GET['delete']) && hasPermission('role', 'delete')) {
    $id = intval($_GET['delete']);
    // Prevent deleting admin role
    $role = dbFetch("SELECT is_system FROM roles WHERE id = ?", [$id]);
    if ($role && !$role['is_system']) {
        dbQuery("DELETE FROM roles WHERE id = ?", [$id]);
        showAlert('Role deleted successfully', 'success');
        redirect('/ttc/modules/role/index.php');
    } else {
        showAlert('Cannot delete system role', 'error');
    }
}

// Get all roles
$roles = dbFetchAll("SELECT r.*, COUNT(a.id) as admin_count 
    FROM roles r 
    LEFT JOIN admins a ON a.role_id = r.id 
    GROUP BY r.id 
    ORDER BY r.is_system DESC, r.name");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Role Management</h1>
        <p class="page-subtitle">Manage user roles and permissions</p>
    </div>
    <?php if (hasPermission('role', 'create')): ?>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Role
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Admins</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($role['description'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($role['is_system']): ?>
                            <span class="badge badge-primary">System</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Custom</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $role['admin_count']; ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="form.php?id=<?php echo $role['id']; ?>" class="action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (!$role['is_system'] && hasPermission('role', 'delete')): ?>
                            <a href="?delete=<?php echo $role['id']; ?>" class="action-btn delete" title="Delete" 
                               onclick="return confirm('Delete this role?')">
                                <i class="fas fa-trash"></i>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
