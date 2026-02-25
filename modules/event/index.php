<?php
/**
 * Events & Holidays Management
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Events & Holidays';
$breadcrumb = [
    ['label' => 'Events & Holidays']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('event', 'view');

// Handle delete action
if (isset($_GET['delete']) && hasPermission('event', 'delete')) {
    $id = intval($_GET['delete']);
    dbQuery("DELETE FROM events WHERE id = ?", [$id]);
    showAlert('Event deleted successfully', 'success');
    redirect('/ttc/modules/event/index.php');
}

// Get filter
$filterType = $_GET['type'] ?? 'all';
$filterInstitution = intval($_GET['institution'] ?? 0);

// Build query
$sql = "SELECT e.*, i.name as institution_name 
    FROM events e 
    LEFT JOIN institutions i ON e.institution_id = i.id 
    WHERE 1=1";
$params = [];

if ($filterType !== 'all') {
    $sql .= " AND e.type = ?";
    $params[] = $filterType;
}
if ($filterInstitution) {
    $sql .= " AND e.institution_id = ?";
    $params[] = $filterInstitution;
}

$sql .= " ORDER BY e.start_date DESC";

$events = dbFetchAll($sql, $params);
$institutions = dbFetchAll("SELECT id, name FROM institutions WHERE status = 'active' ORDER BY name");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Events & Holidays</h1>
        <p class="page-subtitle">Manage academic events and holidays</p>
    </div>
    <?php if (hasPermission('event', 'create')): ?>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Event
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="form-inline" style="display: flex; gap: 10px;">
            <select name="type" class="form-input" style="width: auto;">
                <option value="all">All Types</option>
                <option value="holiday" <?php echo $filterType === 'holiday' ? 'selected' : ''; ?>>Holiday</option>
                <option value="event" <?php echo $filterType === 'event' ? 'selected' : ''; ?>>Event</option>
                <option value="exam" <?php echo $filterType === 'exam' ? 'selected' : ''; ?>>Exam</option>
            </select>
            <select name="institution" class="form-input" style="width: auto;">
                <option value="0">All Institutions</option>
                <?php foreach ($institutions as $inst): ?>
                <option value="<?php echo $inst['id']; ?>" 
                    <?php echo $filterInstitution === $inst['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($inst['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Institution</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                    <td>
                        <span class="badge badge-<?php 
                            echo $event['type'] === 'holiday' ? 'danger' : ($event['type'] === 'exam' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo ucfirst($event['type']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($event['institution_name'] ?? 'All'); ?></td>
                    <td><?php echo formatDate($event['start_date']); ?></td>
                    <td><?php echo formatDate($event['end_date']); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="form.php?id=<?php echo $event['id']; ?>" class="action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (hasPermission('event', 'delete')): ?>
                            <a href="?delete=<?php echo $event['id']; ?>" class="action-btn delete" title="Delete" 
                               onclick="return confirm('Delete this event?')">
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
