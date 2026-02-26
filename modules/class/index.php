<?php
/**
 * Class Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('class', 'view');

$pageTitle = 'Classes';
$breadcrumb = [
    ['label' => 'Classes']
];

require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete']) && hasPermission('class', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE classes SET status = 'inactive' WHERE id = ?", [$id]);
    showAlert('Class deleted successfully', 'success');
    redirect('/ttc/modules/class/index.php');
}

// Get filter
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;

// Get all institutions for filter
$institutions = getInstitutions('active');

// Get classes
$sql = "SELECT c.*, i.name as institution_name, d.name as department_name 
        FROM classes c 
        JOIN institutions i ON c.institution_id = i.id 
        LEFT JOIN departments d ON c.department_id = d.id 
        WHERE c.status = 'active'";
$params = [];

if ($institutionFilter) {
    $sql .= " AND c.institution_id = ?";
    $params[] = $institutionFilter;
}

$sql .= " ORDER BY i.name, c.name";
$classes = dbFetchAll($sql, $params);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Classes</h1>
        <p class="page-subtitle">Manage academic classes</p>
    </div>
    <?php if (hasPermission('class', 'create')): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Class
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="filter-bar" style="margin-bottom: 0;">
            <div class="filter-group">
                <label>Institution:</label>
                <select class="form-control" onchange="window.location.href='?institution='+this.value">
                    <option value="">All Institutions</option>
                    <?php foreach ($institutions as $inst): ?>
                        <option value="<?php echo $inst['id']; ?>" <?php echo $institutionFilter == $inst['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($inst['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($classes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#127891;</div>
                <h3>No Classes Found</h3>
                <p>Start by adding your first class.</p>
                <?php if (hasPermission('class', 'create')): ?>
                    <a href="form.php" class="btn btn-primary">Add Class</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Code</th>
                            <th>Institution</th>
                            <th>Department</th>
                            <th>Sections</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): 
                            $sectionCount = dbFetch("SELECT COUNT(*) as count FROM sections WHERE class_id = ? AND status = 'active'", [$class['id']])['count'];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($class['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['code'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($class['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['department_name'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $sectionCount; ?> section(s)</span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="form.php?id=<?php echo $class['id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $class['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirmDelete();">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
