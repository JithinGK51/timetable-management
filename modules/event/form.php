<?php
/**
 * Events & Holidays - Add/Edit Form
 */

require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = isset($_GET['id']) ? 'Edit Event' : 'Add Event';
$breadcrumb = [
    ['label' => 'Events & Holidays', 'url' => 'index.php'],
    ['label' => isset($_GET['id']) ? 'Edit' : 'Add']
];

require_once __DIR__ . '/../../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$event = ['title' => '', 'description' => '', 'type' => 'event', 'institution_id' => '', 
          'start_date' => '', 'end_date' => '', 'affects_timetable' => 1];

$institutions = dbFetchAll("SELECT id, name FROM institutions WHERE status = 'active' ORDER BY name");

// Load existing event
if ($id) {
    $eventData = dbFetch("SELECT * FROM events WHERE id = ?", [$id]);
    if ($eventData) {
        $event = $eventData;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'event';
    $institutionId = $_POST['institution_id'] ? intval($_POST['institution_id']) : null;
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $affectsTimetable = isset($_POST['affects_timetable']) ? 1 : 0;
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($startDate)) $errors[] = 'Start date is required';
    if (empty($endDate)) $endDate = $startDate;
    
    if (empty($errors)) {
        if ($id) {
            dbQuery("UPDATE events SET title = ?, description = ?, type = ?, institution_id = ?, 
                    start_date = ?, end_date = ?, affects_timetable = ? WHERE id = ?",
                [$title, $description, $type, $institutionId, $startDate, $endDate, $affectsTimetable, $id]);
            showAlert('Event updated successfully', 'success');
        } else {
            dbQuery("INSERT INTO events (title, description, type, institution_id, start_date, end_date, affects_timetable) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$title, $description, $type, $institutionId, $startDate, $endDate, $affectsTimetable]);
            showAlert('Event created successfully', 'success');
        }
        redirect('/ttc/modules/event/index.php');
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $id ? 'Edit Event' : 'Add Event'; ?></h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-input" 
                           value="<?php echo htmlspecialchars($event['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input">
                        <option value="holiday" <?php echo $event['type'] === 'holiday' ? 'selected' : ''; ?>>Holiday</option>
                        <option value="event" <?php echo $event['type'] === 'event' ? 'selected' : ''; ?>>Event</option>
                        <option value="exam" <?php echo $event['type'] === 'exam' ? 'selected' : ''; ?>>Exam</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Institution</label>
                    <select name="institution_id" class="form-input">
                        <option value="">All Institutions</option>
                        <?php foreach ($institutions as $inst): ?>
                        <option value="<?php echo $inst['id']; ?>" 
                            <?php echo $event['institution_id'] == $inst['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($inst['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center; padding-top: 30px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="affects_timetable" value="1" 
                            <?php echo $event['affects_timetable'] ? 'checked' : ''; ?>>
                        Affects Timetable (no classes)
                    </label>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-input" 
                           value="<?php echo $event['start_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-input" 
                           value="<?php echo $event['end_date']; ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Event</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
