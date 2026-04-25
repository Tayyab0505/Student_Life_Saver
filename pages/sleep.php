<?php

require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'sleep';
$errors = [];
$edit_item = null;

// Mood config
$moods = [
    'Great' => ['emoji' => '😄', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'Good' => ['emoji' => '🙂', 'color' => '#06b6d4', 'bg' => '#cffafe'],
    'Okay' => ['emoji' => '😐', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'Tired' => ['emoji' => '😴', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
    'Exhausted' => ['emoji' => '😫', 'color' => '#ef4444', 'bg' => '#fee2e2'],
];

// Handle DELETE
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM sleep_log WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: sleep.php?msg=deleted');
    exit;
}

// Load log for editing
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM sleep_log WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $current_user_id]);
    $edit_item = $stmt->fetch();
}

require_once '../includes/header.php';
?>

<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php $msgs = [
        'added' => ['success', 'Sleep log saved!'],
        'updated' => ['success', 'Sleep log updated!'],
        'deleted' => ['danger', 'Log entry deleted.'],
    ]; ?>
    <?php if (isset($msgs[$_GET['msg']])):
        [$type, $text] = $msgs[$_GET['msg']]; ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i>
            <?= $text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">Sleep & Routine</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Track your rest and daily energy levels.</p>
    </div>
    <button class="btn-dash-action" id="toggleSleepBtn">
        <i class="bi bi-plus-lg"></i> Log Sleep
    </button>
</div>