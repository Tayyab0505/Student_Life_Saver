<?php

require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'schedule';
$errors = [];
$edit_class = null;

// Days order for the weekly grid
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Color palette options for classes
$colors = [
    '#4f46e5' => 'Indigo',
    '#10b981' => 'Green',
    '#f59e0b' => 'Amber',
    '#ef4444' => 'Red',
    '#06b6d4' => 'Cyan',
    '#8b5cf6' => 'Purple',
    '#f97316' => 'Orange',
    '#ec4899' => 'Pink',
];

// Handle DELETE
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    // Make sure the class belongs to THIS user before deleting
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: schedule.php?msg=deleted');
    exit;
}

// Load class for editing 
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM schedule WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $current_user_id]);
    $edit_class = $stmt->fetch();
}

// Handle ADD / UPDATE form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject = trim($_POST['subject'] ?? '');
    $day = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room = trim($_POST['room'] ?? '');
    $color = $_POST['color'] ?? '#4f46e5';
    $post_id = (int) ($_POST['edit_id'] ?? 0);

    // Validation
    if ($subject === '')
        $errors[] = 'Subject name is required.';
    if (!in_array($day, $days))
        $errors[] = 'Please select a valid day.';
    if ($start_time === '')
        $errors[] = 'Start time is required.';
    if ($end_time === '')
        $errors[] = 'End time is required.';
    if ($start_time && $end_time && $start_time >= $end_time)
        $errors[] = 'End time must be after start time.';

    if (empty($errors)) {
        if ($post_id > 0) {
            // UPDATE existing class
            $stmt = $pdo->prepare("
                UPDATE schedule
                SET subject=?, day_of_week=?, start_time=?, end_time=?, room=?, color=?
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([$subject, $day, $start_time, $end_time, $room, $color, $post_id, $current_user_id]);
            header('Location: schedule.php?msg=updated');
        } else {
            // INSERT new class
            $stmt = $pdo->prepare("
                INSERT INTO schedule (user_id, subject, day_of_week, start_time, end_time, room, color)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$current_user_id, $subject, $day, $start_time, $end_time, $room, $color]);
            header('Location: schedule.php?msg=added');
        }
        exit;
    }

    // If errors, re-populate edit_class so form stays filled
    $edit_class = [
        'id' => $post_id,
        'subject' => $subject,
        'day_of_week' => $day,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'room' => $room,
        'color' => $color
    ];
}

// Fetch all classes grouped by day
$stmt = $pdo->prepare("SELECT * FROM schedule WHERE user_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time ASC");
$stmt->execute([$current_user_id]);
$all_classes = $stmt->fetchAll();
$by_day = [];
foreach ($days as $d) {
    $by_day[$d] = [];
}

foreach ($all_classes as $cls) {
    $by_day[$cls['day_of_week']][] = $cls;
}

require_once '../includes/header.php'
    ?>

<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php $msgs = ['added' => ['success', 'Class added!'], 'updated' => ['success', 'Class updated!'], 'deleted' => ['danger', 'Class deleted.']]; ?>
    <?php if (isset($msgs[$_GET['msg']])):
        [$type, $text] = $msgs[$_GET['msg']]; ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i> <?= $text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">Class Schedule</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Manage your weekly timetable.</p>
    </div>
    <button class="btn-dash-action" id="toggleFormBtn">
        <i class="bi bi-<?= $edit_class ? 'pencil' : 'plus-lg' ?>"></i>
        <?= $edit_class ? 'Edit Class' : 'Add Class' ?>
    </button>
</div>

<!-- ADD / EDIT FORM (collapsible)-->
<div class="form-panel <?= ($edit_class || !empty($errors)) ? 'open' : '' ?>" id="scheduleForm">
    <div class="form-panel-inner">
        <h6>
            <i class="bi bi-<?= $edit_class['id'] ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= $edit_class && $edit_class['id'] ? 'Edit Class' : 'Add New Class' ?>
        </h6>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php foreach ($errors as $e): ?>
                    <div> <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?> </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="schedule.php">
            <!-- Hidden field — if > 0 we're editing, if 0 we're adding -->
            <input type="hidden" name="edit_id" value="<?= $edit_class['id'] ?? 0 ?>" />
            <div class="row g-3">
                <!-- Subject -->
                <div class="col-sm-6">
                    <label class="field-label">Subject / Course *</label>
                    <input type="text" name="subject" class="field-input" placeholder="e.g. Web Application"
                        value="<?= htmlspecialchars($edit_class['subject'] ?? '') ?>" required />
                </div>

                <!-- Day -->
                <div class="col-sm-6">
                    <label class="field-label">Day of Week *</label>
                    <select name="day_of_week" class="field-input" required>
                        <option value="">Select day…</option>
                        <?php foreach ($days as $d): ?>
                            <option value="<?= $d ?>" <?= ($edit_class['day_of_week'] ?? '') === $d ? 'selected' : '' ?>>
                                <?= $d ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Start time -->
                <div class="col-sm-4">
                    <label class="field-label">Start Time *</label>
                    <input type="time" name="start_time" class="field-input"
                        value="<?= $edit_class['start_time'] ?? '' ?>" required />
                </div>

                <!-- End time -->
                <div class="col-sm-4">
                    <label class="field-label">End Time *</label>
                    <input type="time" name="end_time" class="field-input" value="<?= $edit_class['end_time'] ?? '' ?>"
                        required />
                </div>

                <!-- Room -->
                <div class="col-sm-4">
                    <label class="field-label">Room / Location</label>
                    <input type="text" name="room" class="field-input" placeholder="e.g. Block B - 204"
                        value="<?= htmlspecialchars($edit_class['room'] ?? '') ?>" />
                </div>

                <!-- Color picker -->
                <div class="col-12">
                    <label class="field-label">Class Color</label>
                    <div class="color-picker">
                        <?php foreach ($colors as $hex => $name): ?>
                            <label class="color-option" title="<?= $name ?>">
                                <input type="radio" name="color" value="<?= $hex ?>" <?= ($edit_class['color'] ?? '#4f46e5') === $hex ? 'checked' : '' ?> />
                                <span class="color-swatch" style="background:<?= $hex ?>"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn-submit-sm">
                        <i class="bi bi-<?= $edit_class && $edit_class['id'] ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $edit_class && $edit_class['id'] ? 'Save Changes' : 'Add Class' ?>
                    </button>
                    <a href="schedule.php" class="btn-cancel-sm">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- WEEKLY GRID -->
<?php if (empty($all_classes)): ?>
    <div class="empty-state-page">
        <i class="bi bi-calendar-week"></i>
        <h5>No classes added yet</h5>
        <p>Click "Add Class" above to build your timetable.</p>
    </div>
<?php else: ?>

    <!-- Summary row: total classes per day pill -->
    <div class="day-summary-row mb-3">
        <?php foreach ($days as $d): ?>
            <div class="day-pill <?= count($by_day[$d]) > 0 ? 'has-class' : '' ?>
                           <?= $d === date('l') ? 'today' : '' ?>">
                <span class="day-pill-name">
                    <?= substr($d, 0, 3) ?>
                </span>
                <?php if (count($by_day[$d]) > 0): ?>
                    <span class="day-pill-count">
                        <?= count($by_day[$d]) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Weekly columns grid -->
    <div class="weekly-grid">
        <?php foreach ($days as $d): ?>
            <div class="day-col <?= $d === date('l') ? 'today-col' : '' ?>">
                <div class="day-col-header">
                    <span class="day-name">
                        <?= substr($d, 0, 3) ?>
                    </span>
                    <?php if ($d === date('l')): ?>
                        <span class="today-badge">Today</span>
                    <?php endif; ?>
                </div>
                <div class="day-col-body">
                    <?php if (empty($by_day[$d])): ?>
                        <div class="no-class-slot">—</div>
                    <?php else: ?>
                        <?php foreach ($by_day[$d] as $cls): ?>
                            <div class="schedule-card"
                                style="border-left-color:<?= htmlspecialchars($cls['color']) ?>; background:<?= htmlspecialchars($cls['color']) ?>18">
                                <div class="sc-subject">
                                    <?= htmlspecialchars($cls['subject']) ?>
                                </div>
                                <div class="sc-time">
                                    <i class="bi bi-clock"></i>
                                    <?= date('g:i A', strtotime($cls['start_time'])) ?><br>
                                    <?= date('g:i A', strtotime($cls['end_time'])) ?>
                                </div>
                                <?php if ($cls['room']): ?>
                                    <div class="sc-room">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($cls['room']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="sc-actions">
                                    <a href="schedule.php?edit=<?= $cls['id'] ?>" class="sc-btn sc-edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="schedule.php?delete=<?= $cls['id'] ?>" class="sc-btn sc-delete" title="Delete"
                                        onclick="return confirm('Delete <?= htmlspecialchars(addslashes($cls['subject'])) ?>?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>