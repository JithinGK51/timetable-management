<?php
/**
 * Section Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Sections';
$breadcrumb = [
    ['label' => 'Sections']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('section', 'view');

// Handle delete
if (isset($_GET['delete']) && hasPermission('section', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE sections SET status = 'inactive' WHERE id = ?", [$id]);
    showAlert('Section deleted successfully', 'success');
    redirect('/ttc/modules/section/index.php');
}

// Get filters
$classFilter = isset($_GET['class']) ? intval($_GET['class']) : 0;
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;

// Get institutions
$institutions = getInstitutions('active');

// Get sections with related data
$sql = "SELECT s.*, c.name as class_name, c.id as class_id, i.name as institution_name,
               t.name as class_teacher_name
        FROM sections s
        JOIN classes c ON s.class_id = c.id
        JOIN institutions i ON c.institution_id = i.id
        LEFT JOIN teachers t ON s.class_teacher_id = t.id
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
$sections = dbFetchAll($sql, $params);

// Get classes for filter
$classes = dbFetchAll("SELECT c.*, i.name as institution_name FROM classes c JOIN institutions i ON c.institution_id = i.id WHERE c.status = 'active' ORDER BY i.name, c.name");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sections</h1>
        <p class="page-subtitle">Manage class sections and assign class teachers</p>
    </div>
    <?php if (hasPermission('section', 'create')): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Section
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
        <?php if (empty($sections)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128101;</div>
                <h3>No Sections Found</h3>
                <p>Create sections for your classes.</p>
                <?php if (hasPermission('section', 'create')): ?>
                    <a href="form.php" class="btn btn-primary">Add Section</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Section Name</th>
                            <th>Class</th>
                            <th>Institution</th>
                            <th>Class Teacher</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($section['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($section['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['institution_name']); ?></td>
                                <td>
                                    <?php if ($section['class_teacher_name']): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($section['class_teacher_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $section['capacity']; ?> students</td>
                                <td>
                                    <div class="action-btns">
                                        <a href="form.php?id=<?php echo $section['id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $section['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirmDelete();">
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
