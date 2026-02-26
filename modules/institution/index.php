<?php
/**
 * Institution Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('institution', 'view');

$pageTitle = 'Institutions';
$breadcrumb = [
    ['label' => 'Institutions']
];

require_once __DIR__ . '/../../includes/header.php';

// Handle delete action
if (isset($_GET['delete']) && hasPermission('institution', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE institutions SET status = 'inactive' WHERE id = ?", [$id]);
    showAlert('Institution deleted successfully', 'success');
    redirect('/ttc/modules/institution/index.php');
}

// Get all institutions
$institutions = getInstitutions();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Institutions</h1>
        <p class="page-subtitle">Manage schools, colleges, and universities</p>
    </div>
    <?php if (hasPermission('institution', 'create')): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Institution
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($institutions)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#127979;</div>
                <h3>No Institutions Found</h3>
                <p>Start by adding your first institution.</p>
                <?php if (hasPermission('institution', 'create')): ?>
                    <a href="form.php" class="btn btn-primary">Add Institution</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table" id="institutionsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($institutions as $inst): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($inst['name']); ?></strong>
                                    <?php if ($inst['address']): ?>
                                        <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($inst['address']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($inst['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($inst['contact_email']): ?>
                                        <i class="fas fa-envelope" style="color: var(--text-light);"></i> <?php echo htmlspecialchars($inst['contact_email']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($inst['contact_phone']): ?>
                                        <i class="fas fa-phone" style="color: var(--text-light);"></i> <?php echo htmlspecialchars($inst['contact_phone']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $inst['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <span class="status-dot status-<?php echo $inst['status']; ?>"></span>
                                        <?php echo ucfirst($inst['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($inst['created_at']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <?php if (hasPermission('institution', 'edit')): ?>
                                            <a href="form.php?id=<?php echo $inst['id']; ?>" class="action-btn edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('institution', 'delete')): ?>
                                            <a href="?delete=<?php echo $inst['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirmDelete('Are you sure you want to delete this institution?');">
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
