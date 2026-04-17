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

// ── Handle DELETE ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    // Make sure the class belongs to THIS user before deleting
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: schedule.php?msg=deleted');
    exit;
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

<!-- ── Page header ──────────────────────────────────────────── -->
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