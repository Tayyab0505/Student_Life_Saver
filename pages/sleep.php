<?php

require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'sleep';
$errors = [];
$edit_item = null;

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