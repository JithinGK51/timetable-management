<?php
/**
 * Header Template
 * Include at the top of all protected pages
 * NOTE: auth_check.php must be included BEFORE this file
 */

// Ensure auth check was already done
if (!isset($currentAdmin)) {
    die('Error: auth_check.php must be included before header.php');
}

// Get current page for active menu
$currentPage = basename(dirname($_SERVER['PHP_SELF']));
$currentFile = basename($_SERVER['PHP_SELF'], '.php');

// Get alert if any
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Teacher Timetable Management System</title>
    <link rel="stylesheet" href="/ttc/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">&#128197;</div>
                <div class="sidebar-title">
                    TTMS
                    <span>Timetable System</span>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-category">Main</div>
                <a href="/ttc/modules/dashboard/index.php" class="menu-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="menu-category">Academic</div>
                <a href="/ttc/modules/institution/index.php" class="menu-item <?php echo $currentPage === 'institution' ? 'active' : ''; ?>">
                    <i class="fas fa-university"></i>
                    <span>Institutions</span>
                </a>
                <a href="/ttc/modules/class/index.php" class="menu-item <?php echo $currentPage === 'class' ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Classes</span>
                </a>
                <a href="/ttc/modules/section/index.php" class="menu-item <?php echo $currentPage === 'section' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Sections</span>
                </a>
                <a href="/ttc/modules/subject/index.php" class="menu-item <?php echo $currentPage === 'subject' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
                <a href="/ttc/modules/teacher/index.php" class="menu-item <?php echo $currentPage === 'teacher' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                </a>
                
                <div class="menu-category">Timetable</div>
                <a href="/ttc/modules/timeslot/index.php" class="menu-item <?php echo $currentPage === 'timeslot' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Time Slots</span>
                </a>
                <a href="/ttc/modules/timetable/create.php" class="menu-item <?php echo $currentPage === 'timetable' && $currentFile === 'create' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Timetable</span>
                </a>
                <a href="/ttc/modules/timetable/index.php" class="menu-item <?php echo $currentPage === 'timetable' && $currentFile === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>View Timetables</span>
                </a>
                <a href="/ttc/modules/event/index.php" class="menu-item <?php echo $currentPage === 'event' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-day"></i>
                    <span>Events & Holidays</span>
                </a>
                
                <div class="menu-category">Reports</div>
                <a href="/ttc/modules/export/index.php" class="menu-item <?php echo $currentPage === 'export' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf"></i>
                    <span>Export PDF</span>
                </a>
                
                <?php if (isSuperAdmin()): ?>
                <div class="menu-category">Administration</div>
                <a href="/ttc/modules/role/index.php" class="menu-item <?php echo $currentPage === 'role' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Roles</span>
                </a>
                <a href="/ttc/modules/subadmin/index.php" class="menu-item <?php echo $currentPage === 'subadmin' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Sub-Admins</span>
                </a>
                <a href="/ttc/modules/settings/index.php" class="menu-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav class="breadcrumb">
                        <a href="/ttc/modules/dashboard/index.php">Home</a>
                        <?php if (isset($breadcrumb)): ?>
                            <?php foreach ($breadcrumb as $item): ?>
                                <span class="breadcrumb-separator">/</span>
                                <?php if (isset($item['url'])): ?>
                                    <a href="<?php echo $item['url']; ?>"><?php echo $item['label']; ?></a>
                                <?php else: ?>
                                    <span class="breadcrumb-current"><?php echo $item['label']; ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <div class="header-right">
                    <button class="header-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                    
                    <div class="user-menu" onclick="toggleUserDropdown()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentAdmin['name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($currentAdmin['name']); ?></div>
                            <div class="user-role"><?php echo $currentAdmin['is_super_admin'] ? 'Super Admin' : ($currentAdmin['role_name'] ?? 'Admin'); ?></div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: var(--text-light);"></i>
                    </div>
                </div>
            </header>
            
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <?php if ($alert): ?>
                    <div class="alert alert-<?php echo $alert['type']; ?>">
                        <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : ($alert['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        <?php echo $alert['message']; ?>
                    </div>
                <?php endif; ?>
