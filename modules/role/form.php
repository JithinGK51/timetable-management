<?php
/**
 * Role Management - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = isset($_GET['id']) ? 'Edit Role' : 'Add Role';
$breadcrumb = [
    ['label' => 'Roles', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$role = ['name' => '', 'description' => '', 'permissions' => []];

// Get available permissions
$allPermissions = [
    'institution' => ['view', 'create', 'edit', 'delete'],
    'class' => ['view', 'create', 'edit', 'delete'],
    'section' => ['view', 'create', 'edit', 'delete'],
    'subject' => ['view', 'create', 'edit', 'delete'],
    'teacher' => ['view', 'create', 'edit', 'delete'],
    'timeslot' => ['view', 'create', 'edit', 'delete'],
    'timetable' => ['view', 'create', 'edit', 'delete', 'publish'],
    'event' => ['view', 'create', 'edit', 'delete'],
    'export' => ['view'],
    'role' => ['view', 'create', 'edit', 'delete'],
    'subadmin' => ['view', 'create', 'edit', 'delete'],
    'settings' => ['view', 'edit']
];

// Load existing role
if ($id) {
    $roleData = dbFetch("SELECT * FROM roles WHERE id = ?", [$id]);
    if ($roleData) {
        $role = $roleData;
        // Load permissions from role_permissions table
        $permissionsData = dbFetchAll("SELECT module, can_view, can_create, can_edit, can_delete, can_export FROM role_permissions WHERE role_id = ?", [$id]);
        $role['permissions'] = [];
        foreach ($permissionsData as $perm) {
            $module = $perm['module'];
            $role['permissions'][$module] = [];
            if ($perm['can_view']) $role['permissions'][$module][] = 'view';
            if ($perm['can_create']) $role['permissions'][$module][] = 'create';
            if ($perm['can_edit']) $role['permissions'][$module][] = 'edit';
            if ($perm['can_delete']) $role['permissions'][$module][] = 'delete';
            if ($perm['can_export']) $role['permissions'][$module][] = 'export';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($name)) {
        $error = 'Role name is required';
    } else {
        if ($id) {
            // Update role
            dbQuery("UPDATE roles SET name = ?, description = ? WHERE id = ?",
                [$name, $description, $id]);
            
            // Delete old permissions
            dbQuery("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            
            // Insert new permissions
            foreach ($permissions as $module => $actions) {
                $canView = in_array('view', $actions) ? 1 : 0;
                $canCreate = in_array('create', $actions) ? 1 : 0;
                $canEdit = in_array('edit', $actions) ? 1 : 0;
                $canDelete = in_array('delete', $actions) ? 1 : 0;
                $canExport = in_array('export', $actions) ? 1 : 0;
                
                dbQuery("INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$id, $module, $canView, $canCreate, $canEdit, $canDelete, $canExport]);
            }
            
            showAlert('Role updated successfully', 'success');
        } else {
            // Create role
            dbQuery("INSERT INTO roles (name, description, is_system) VALUES (?, ?, 0)",
                [$name, $description]);
            $newRoleId = getDB()->lastInsertId();
            
            // Insert permissions
            foreach ($permissions as $module => $actions) {
                $canView = in_array('view', $actions) ? 1 : 0;
                $canCreate = in_array('create', $actions) ? 1 : 0;
                $canEdit = in_array('edit', $actions) ? 1 : 0;
                $canDelete = in_array('delete', $actions) ? 1 : 0;
                $canExport = in_array('export', $actions) ? 1 : 0;
                
                dbQuery("INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_export) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$newRoleId, $module, $canView, $canCreate, $canEdit, $canDelete, $canExport]);
            }
            
            showAlert('Role created successfully', 'success');
        }
        redirect('/ttc/modules/role/index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $id ? 'Edit Role' : 'Add Role'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="form">
            <div class="form-group">
                <label class="form-label">Role Name *</label>
                <input type="text" name="name" class="form-input" 
                       value="<?php echo htmlspecialchars($role['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="2"><?php echo htmlspecialchars($role['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Permissions</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach ($allPermissions as $module => $actions): ?>
                    <div style="border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-md);">
                        <h4 style="text-transform: capitalize; margin-bottom: 10px;">
                            <?php echo $module; ?>
                            <small style="float: right; font-weight: normal;">
                                <label style="cursor: pointer; font-size: 12px; color: var(--primary-color);">
                                    <input type="checkbox" onclick="toggleModule('<?php echo $module; ?>', this.checked)"> All
                                </label>
                            </small>
                        </h4>
                        <?php foreach ($actions as $action): 
                            $isChecked = isset($role['permissions'][$module]) && in_array($action, $role['permissions'][$module]);
                        ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="permissions[<?php echo $module; ?>][]" 
                                   value="<?php echo $action; ?>"
                                   class="perm-<?php echo $module; ?>"
                                   <?php echo $isChecked ? 'checked' : ''; ?>>
                            <?php echo ucfirst($action); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <script>
            function toggleModule(module, checked) {
                document.querySelectorAll('.perm-' + module).forEach(function(cb) {
                    cb.checked = checked;
                });
            }
            </script>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Role</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
