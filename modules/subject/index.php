<?php
/**
 * Subject Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Subjects';
$breadcrumb = [
    ['label' => 'Subjects']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('subject', 'view');

// Handle delete
if (isset($_GET['delete']) && hasPermission('subject', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE subjects SET status = 'inactive' WHERE id = ?", [$id]);
    showAlert('Subject deleted successfully', 'success');
    redirect('/ttc/modules/subject/index.php');
}

// Get filters
$classFilter = isset($_GET['class']) ? intval($_GET['class']) : 0;
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;

// Get institutions
$institutions = getInstitutions('active');

// Get subjects
$sql = "SELECT s.*, c.name as class_name, c.id as class_id, i.name as institution_name
        FROM subjects s
        JOIN classes c ON s.class_id = c.id
        JOIN institutions i ON c.institution_id = i.id
        WHERE s.status = 'active'";
$params = [];

if ($classFilter) {
    $sql .= " AND s.class_id = ?";
    $params[] = $classFilter;
}

if ($institutionFilter) {
    $sql .= " AND c.institution_id = ?";
    $params[] = $institutionFilter;
}

$sql .= " ORDER BY i.name, c.name, s.name";
$subjects = dbFetchAll($sql, $params);

// Get classes for filter
$classes = dbFetchAll("SELECT c.*, i.name as institution_name FROM classes c JOIN institutions i ON c.institution_id = i.id WHERE c.status = 'active' ORDER BY i.name, c.name");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Subjects</h1>
        <p class="page-subtitle">Manage subjects and their weekly hours</p>
    </div>
    <?php if (hasPermission('subject', 'create')): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Subject
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="filter-bar" style="margin-bottom: 0;">
            <div class="filter-group">
                <label>Institution:</label>
                <select class="form-control" onchange="window.location.href='?institution='+this.value">
                    <option value="">All</option>
                    <?php foreach ($institutions as $inst): ?>
                        <option value="<?php echo $inst['id']; ?>" <?php echo $institutionFilter == $inst['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($inst['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Class:</label>
                <select class="form-control" onchange="window.location.href='?class='+this.value">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>" <?php echo $classFilter == $cls['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cls['institution_name'] . ' - ' . $cls['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128218;</div>
                <h3>No Subjects Found</h3>
                <p>Add subjects for your classes.</p>
                <?php if (hasPermission('subject', 'create')): ?>
                    <a href="form.php" class="btn btn-primary">Add Subject</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Code</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Weekly Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($subject['code'] ?? '-'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($subject['institution_name']); ?><br>
                                    <small style="color: var(--text-light);"><?php echo htmlspecialchars($subject['class_name']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $subject['type'] === 'lab' ? 'warning' : ($subject['type'] === 'both' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst($subject['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $subject['weekly_hours']; ?> hrs/week</span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="form.php?id=<?php echo $subject['id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $subject['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirmDelete();">
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
