<?php
/**
 * Global Settings
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Settings';
$breadcrumb = [
    ['label' => 'Settings']
];

require_once __DIR__ . '/../../includes/header.php';
requirePermission('settings', 'edit');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'academic_year' => sanitize($_POST['academic_year']),
        'system_name' => sanitize($_POST['system_name']),
        'timezone' => sanitize($_POST['timezone']),
        'date_format' => sanitize($_POST['date_format']),
        'time_format' => sanitize($_POST['time_format'])
    ];
    
    foreach ($settings as $key => $value) {
        setSetting($key, $value, null, 'general');
    }
    
    showAlert('Settings updated successfully', 'success');
    redirect('index.php');
}

// Get current settings
$academicYear = getSetting('academic_year') ?: date('Y') . '-' . (date('Y') + 1);
$systemName = getSetting('system_name') ?: 'Teacher Timetable Management System';
$timezone = getSetting('timezone') ?: 'Asia/Kolkata';
$dateFormat = getSetting('date_format') ?: 'd-m-Y';
$timeFormat = getSetting('time_format') ?: 'H:i';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">Configure global system preferences</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">System Name</label>
                    <input type="text" name="system_name" class="form-control" value="<?php echo htmlspecialchars($systemName); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($academicYear); ?>" placeholder="e.g., 2025-2026">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-control">
                        <option value="Asia/Kolkata" <?php echo $timezone === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                        <option value="Asia/Dubai" <?php echo $timezone === 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai</option>
                        <option value="Asia/Singapore" <?php echo $timezone === 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                        <option value="Europe/London" <?php echo $timezone === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                        <option value="America/New_York" <?php echo $timezone === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-control">
                        <option value="d-m-Y" <?php echo $dateFormat === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY (31-12-2025)</option>
                        <option value="m-d-Y" <?php echo $dateFormat === 'm-d-Y' ? 'selected' : ''; ?>>MM-DD-YYYY (12-31-2025)</option>
                        <option value="Y-m-d" <?php echo $dateFormat === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2025-12-31)</option>
                        <option value="d/m/Y" <?php echo $dateFormat === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY (31/12/2025)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Time Format</label>
                    <select name="time_format" class="form-control">
                        <option value="H:i" <?php echo $timeFormat === 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                        <option value="h:i A" <?php echo $timeFormat === 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
