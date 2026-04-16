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

