<?php
/**
 * Timetable Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Timetables';
$breadcrumb = [
    ['label' => 'Timetables']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('timetable', 'view');

// Handle delete
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

// Get filters
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

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
        WHERE t.status != 'archived'";
$params = [];

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
        <h1 class="page-title">Timetables</h1>
        <p class="page-subtitle">Manage and view generated timetables</p>
    </div>
    <?php if (hasPermission('timetable', 'create')): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Timetable
        </a>
    <?php endif; ?>
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
            <div class="filter-group">
                <label>Status:</label>
                <select class="form-control" onchange="window.location.href='?institution=<?php echo $institutionFilter; ?>&status='+this.value">
                    <option value="">All</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($timetables)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128197;</div>
                <h3>No Timetables Found</h3>
                <p>Create your first timetable to get started.</p>
                <?php if (hasPermission('timetable', 'create')): ?>
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
                            <th>Modified</th>
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