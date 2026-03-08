<?php
/**
 * Timetable Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('timetable', 'view');

$pageTitle = 'Timetables';
$breadcrumb = [
    ['label' => 'Timetables']
];

require_once __DIR__ . '/../../includes/header.php';

// Handle archive (soft delete)
if (isset($_GET['delete']) && hasPermission('timetable', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE timetables SET status = 'archived' WHERE id = ?", [$id]);
    showAlert('Timetable archived successfully', 'success');
    redirect('/ttc/modules/timetable/index.php');
}

// Handle publish
if (isset($_GET['publish']) && hasPermission('timetable', 'edit')) {
    $id = intval($_GET['publish']);
    dbQuery("UPDATE timetables SET status = 'published' WHERE id = ?", [$id]);
    showAlert('Timetable published successfully', 'success');
    redirect('/ttc/modules/timetable/index.php');
}

// Handle recover (restore from archived)
if (isset($_GET['recover']) && hasPermission('timetable', 'edit')) {
    $id = intval($_GET['recover']);
    dbQuery("UPDATE timetables SET status = 'draft' WHERE id = ?", [$id]);
    showAlert('Timetable recovered successfully', 'success');
    redirect('/ttc/modules/timetable/index.php?view=archived');
}

// Handle permanent delete
if (isset($_GET['permanent_delete']) && hasPermission('timetable', 'delete')) {
    $id = intval($_GET['permanent_delete']);
    
    // Delete timetable entries first (foreign key constraint)
    dbQuery("DELETE FROM timetable_entries WHERE timetable_id = ?", [$id]);
    
    // Delete timetable versions
    dbQuery("DELETE FROM timetable_versions WHERE timetable_id = ?", [$id]);
    
    // Delete the timetable
    dbQuery("DELETE FROM timetables WHERE id = ?", [$id]);
    
    showAlert('Timetable permanently deleted', 'success');
    redirect('/ttc/modules/timetable/index.php?view=archived');
}

// Get filters
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$viewArchived = isset($_GET['view']) && $_GET['view'] === 'archived';

// Get institutions
$institutions = getInstitutions('active');

// Get timetables
$sql = "SELECT t.*, i.name as institution_name, c.name as class_name, s.name as section_name,
               a.name as created_by_name
        FROM timetables t
        JOIN institutions i ON t.institution_id = i.id
        JOIN classes c ON t.class_id = c.id
        JOIN sections s ON t.section_id = s.id
        JOIN admins a ON t.created_by = a.id
        WHERE 1=1";
$params = [];

// Filter by archived status
if ($viewArchived) {
    $sql .= " AND t.status = 'archived'";
} else {
    $sql .= " AND t.status != 'archived'";
}

if ($institutionFilter) {
    $sql .= " AND t.institution_id = ?";
    $params[] = $institutionFilter;
}

if ($statusFilter) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.updated_at DESC";
$timetables = dbFetchAll($sql, $params);
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $viewArchived ? 'Archived Timetables' : 'Timetables'; ?></h1>
        <p class="page-subtitle"><?php echo $viewArchived ? 'View and recover deleted timetables' : 'Manage and view generated timetables'; ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($viewArchived): ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Timetables
            </a>
        <?php else: ?>
            <?php if (hasPermission('timetable', 'create')): ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Timetable
                </a>
            <?php endif; ?>
            <a href="index.php?view=archived" class="btn btn-secondary">
                <i class="fas fa-archive"></i> View Archived
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="filter-bar" style="margin-bottom: 0;">
            <div class="filter-group">
                <label>Institution:</label>
                <select class="form-control" onchange="window.location.href='?institution='+this.value+'&status=<?php echo $statusFilter; ?>'">
                    <option value="">All</option>
                    <?php foreach ($institutions as $inst): ?>
                        <option value="<?php echo $inst['id']; ?>" <?php echo $institutionFilter == $inst['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($inst['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!$viewArchived): ?>
            <div class="filter-group">
                <label>Status:</label>
                <select class="form-control" onchange="window.location.href='?institution=<?php echo $institutionFilter; ?>&status='+this.value">
                    <option value="">All</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($timetables)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128197;</div>
                <h3><?php echo $viewArchived ? 'No Archived Timetables' : 'No Timetables Found'; ?></h3>
                <p><?php echo $viewArchived ? 'There are no archived timetables in the system.' : 'Create your first timetable to get started.'; ?></p>
                <?php if (!$viewArchived && hasPermission('timetable', 'create')): ?>
                    <a href="create.php" class="btn btn-primary">Create Timetable</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Class - Section</th>
                            <th>Academic Year</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th><?php echo $viewArchived ? 'Archived On' : 'Modified'; ?></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timetables as $timetable): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($timetable['institution_name']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($timetable['class_name']); ?></strong> - 
                                    <?php echo htmlspecialchars($timetable['section_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($timetable['academic_year']); ?></td>
                                <td>v<?php echo $timetable['version']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $timetable['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($timetable['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($timetable['created_by_name']); ?></td>
                                <td><?php echo formatDate($timetable['updated_at']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="grid.php?id=<?php echo $timetable['id']; ?>" class="action-btn view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($viewArchived): ?>
                                            <?php if (hasPermission('timetable', 'edit')): ?>
                                                <a href="?recover=<?php echo $timetable['id']; ?>" class="action-btn edit" title="Recover" onclick="return confirmAction('Recover this timetable? It will be restored as a draft.');">
                                                    <i class="fas fa-trash-restore"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (hasPermission('timetable', 'delete')): ?>
                                                <a href="?permanent_delete=<?php echo $timetable['id']; ?>" class="action-btn delete" title="Delete Permanently" onclick="return confirmDelete('Are you sure you want to permanently delete this timetable? This action cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($timetable['status'] !== 'published' && hasPermission('timetable', 'edit')): ?>
                                                <a href="create.php?edit=<?php echo $timetable['id']; ?>" class="action-btn edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($timetable['status'] === 'draft' && hasPermission('timetable', 'edit')): ?>
                                                <a href="?publish=<?php echo $timetable['id']; ?>" class="action-btn view" title="Publish" onclick="return confirmAction('Publish this timetable?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (hasPermission('timetable', 'delete')): ?>
                                                <a href="?delete=<?php echo $timetable['id']; ?>" class="action-btn delete" title="Archive" onclick="return confirmDelete();">
                                                    <i class="fas fa-archive"></i>
                                                </a>
                                            <?php endif; ?>
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