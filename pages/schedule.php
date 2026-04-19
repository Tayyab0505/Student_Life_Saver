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

// Fetch all classes grouped by day
$stmt = $pdo->prepare("SELECT * FROM schedule WHERE user_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time ASC");
$stmt->execute([$current_user_id]);
$all_classes = $stmt->fetchAll();

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