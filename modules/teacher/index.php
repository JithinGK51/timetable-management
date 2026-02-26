<?php
/**
 * Teacher Management - List View
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('teacher', 'view');

$pageTitle = 'Teachers';
$breadcrumb = [
    ['label' => 'Teachers']
];

require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete']) && hasPermission('teacher', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("UPDATE teachers SET status = 'inactive' WHERE id = ?", [$id]);
    showAlert('Teacher deleted successfully', 'success');
    redirect('/ttc/modules/teacher/index.php');
}

// Handle status toggle
if (isset($_GET['toggle']) && hasPermission('teacher', 'edit')) {
    $id = intval($_GET['toggle']);
    $teacher = dbFetch("SELECT status FROM teachers WHERE id = ?", [$id]);
    if ($teacher) {
        $newStatus = $teacher['status'] === 'active' ? 'inactive' : 'active';
        dbQuery("UPDATE teachers SET status = ? WHERE id = ?", [$newStatus, $id]);
        showAlert('Teacher status updated', 'success');
    }
    redirect('/ttc/modules/teacher/index.php');
}

// Get filters
$institutionFilter = isset($_GET['institution']) ? intval($_GET['institution']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Get institutions
$institutions = getInstitutions('active');

// Get teachers
$sql = "SELECT t.*, i.name as institution_name,
               (SELECT COUNT(*) FROM teacher_subjects ts WHERE ts.teacher_id = t.id) as subject_count,
               (SELECT COUNT(*) FROM timetable_entries te JOIN timetables tt ON te.timetable_id = tt.id WHERE te.teacher_id = t.id AND tt.status = 'published') as assignment_count
        FROM teachers t
        JOIN institutions i ON t.institution_id = i.id
        WHERE 1=1";
$params = [];

if ($institutionFilter) {
    $sql .= " AND t.institution_id = ?";
    $params[] = $institutionFilter;
}

if ($statusFilter) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.name";
$teachers = dbFetchAll($sql, $params);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Teachers</h1>
        <p class="page-subtitle">Manage teachers and their assignments</p>
    </div>
    <?php if (hasPermission('teacher', 'create')): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Teacher
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
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($teachers)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128104;&#8205;&#127979;</div>
                <h3>No Teachers Found</h3>
                <p>Add teachers to your institutions.</p>
                <?php if (hasPermission('teacher', 'create')): ?>
                    <a href="form.php" class="btn btn-primary">Add Teacher</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Employee ID</th>
                            <th>Institution</th>
                            <th>Subjects</th>
                            <th>Workload</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): 
                            // Calculate workload indicator
                            $workloadPercent = min(100, ($teacher['assignment_count'] / ($teacher['max_periods_per_week'] ?: 30)) * 100);
                            $workloadClass = $workloadPercent > 90 ? 'danger' : ($workloadPercent > 70 ? 'warning' : 'success');
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($teacher['name']); ?></strong><br>
                                    <small style="color: var(--text-light);">
                                        <?php if ($teacher['email']): ?>
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['institution_name']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $teacher['subject_count']; ?> subject(s)</span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; background: var(--bg-light); height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $workloadPercent; ?>%; background: var(--<?php echo $workloadClass; ?>-color); height: 100%;"></div>
                                        </div>
                                        <span style="font-size: 12px; color: var(--text-light);"><?php echo round($workloadPercent); ?>%</span>
                                    </div>
                                    <small style="color: var(--text-light);"><?php echo $teacher['assignment_count']; ?> / <?php echo $teacher['max_periods_per_week'] ?: 30; ?> periods</small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $teacher['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <span class="status-dot status-<?php echo $teacher['status']; ?>"></span>
                                        <?php echo ucfirst($teacher['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="form.php?id=<?php echo $teacher['id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle=<?php echo $teacher['id']; ?>" class="action-btn <?php echo $teacher['status'] === 'active' ? 'delete' : 'view'; ?>" title="<?php echo $teacher['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $teacher['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <a href="?delete=<?php echo $teacher['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirmDelete();">
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
