<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Dashboard';
$breadcrumb = [
    ['label' => 'Dashboard']
];

require_once __DIR__ . '/../../includes/header.php';

// Get dashboard statistics
$stats = getDashboardStats();
$recentTimetables = getRecentTimetables(5);

// Get today's schedule (simplified - would need actual data)
$today = date('l');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($currentAdmin['name']); ?>! Here's what's happening today.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/ttc/modules/timetable/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Timetable
        </a>
        <a href="/ttc/modules/teacher/form.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Add Teacher
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
            <div class="stat-label">Total Teachers</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['active_teachers']; ?></div>
            <div class="stat-label">Active Teachers</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total_classes']; ?></div>
            <div class="stat-label">Total Classes</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total_sections']; ?></div>
            <div class="stat-label">Total Sections</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total_subjects']; ?></div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon <?php echo $stats['timetables_published'] > 0 ? 'success' : 'secondary'; ?>">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['timetables_published']; ?>/<?php echo $stats['timetables_generated']; ?></div>
            <div class="stat-label">Timetables Published</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <!-- Recent Timetables -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i> Recently Modified Timetables</h3>
            <a href="/ttc/modules/timetable/index.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentTimetables)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">&#128197;</div>
                    <h3>No Timetables Yet</h3>
                    <p>Create your first timetable to get started.</p>
                    <a href="/ttc/modules/timetable/create.php" class="btn btn-primary">Create Timetable</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Class - Section</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Modified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTimetables as $timetable): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($timetable['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($timetable['class_name'] . ' - ' . $timetable['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($timetable['academic_year']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $timetable['status'] === 'published' ? 'success' : ($timetable['status'] === 'draft' ? 'secondary' : 'warning'); ?>">
                                        <?php echo ucfirst($timetable['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($timetable['updated_at']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="/ttc/modules/timetable/grid.php?id=<?php echo $timetable['id']; ?>" class="action-btn view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/ttc/modules/timetable/create.php?edit=<?php echo $timetable['id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions & Today's Schedule -->
    <div>
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <a href="/ttc/modules/institution/form.php" class="btn btn-secondary" style="flex-direction: column; padding: 15px;">
                        <i class="fas fa-university" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <span>Add Institution</span>
                    </a>
                    <a href="/ttc/modules/class/form.php" class="btn btn-secondary" style="flex-direction: column; padding: 15px;">
                        <i class="fas fa-graduation-cap" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <span>Add Class</span>
                    </a>
                    <a href="/ttc/modules/section/form.php" class="btn btn-secondary" style="flex-direction: column; padding: 15px;">
                        <i class="fas fa-users" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <span>Add Section</span>
                    </a>
                    <a href="/ttc/modules/subject/form.php" class="btn btn-secondary" style="flex-direction: column; padding: 15px;">
                        <i class="fas fa-book" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <span>Add Subject</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Today's Schedule -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day"></i> Today (<?php echo $today; ?>)</h3>
            </div>
            <div class="card-body">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; margin-bottom: 10px;">&#128197;</div>
                    <p style="color: var(--text-light);">View complete schedule in the Timetable section</p>
                    <a href="/ttc/modules/timetable/index.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                        View Timetables
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
