<?php
require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'assignments';
$errors = [];
$edit_item = null;

// Counts for filter badges
$stmt = $pdo->prepare('Select status, count(*) as cnt from assignments where user_id = ? group by status');
$stmt->execute([$current_user_id]);
$status_counts = array_column($stmt->fetchAll(), 'cnt', 'status');

$total_count = array_sum($status_counts);
$pending_count = $status_counts['Pending'] ?? 0;
$progress_count = $status_counts['In Progress'] ?? 0;
$completed_count = $status_counts['Completed'] ?? 0;

require_once '../includes/header.php';
?>


<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php $msgs = [
        'added' => ['success', 'Assignment added successfully!'],
        'updated' => ['success', 'Assignment updated!'],
        'deleted' => ['danger', 'Assignment deleted.'],
    ]; ?>
    <?php if (isset($msgs[$_GET['msg']])):
        [$type, $text] = $msgs[$_GET['msg']]; ?>

        <div class="alert alert-<?= $type ?> alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i><?= $text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">Assignments</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?= $total_count ?> total &nbsp;·&nbsp;
            <span style="color:var(--warning)"> <?= $pending_count ?> pending </span> &nbsp;·&nbsp;
            <span style="color:var(--accent)"> <?= $progress_count ?> in progress </span> &nbsp;·&nbsp;
            <span style="color:var(--accent2)"> <?= $completed_count ?> completed </span>
        </p>
    </div>
    <button class="btn-dash-action" id="toggleAssignBtn">
        <i class="bi bi-plus-lg"></i> Add Assignment
    </button>
</div>

<!-- Add/Edit form -->
<div class="form-panel <?= ($edit_item || !empty($errors)) ? 'open' : '' ?>" id="assignForm">
    <div class="form-panel-inner">
        <h6 class="form-panel-title">
            <i class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= isset($edit_item['id']) && $edit_item['id'] ? 'Edit Assignment' : 'Add New Assignment' ?>
        </h6>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php foreach ($errors as $e): ?>
                    <div><i class="bi bi-exclamation-circle me-1"></i>
                        <?= htmlspecialchars($e) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="assignments.php">
            <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? 0 ?>" />

            <div class="row g-3">

                <!-- Title -->
                <div class="col-sm-6">
                    <label class="field-label">Assignment Title *</label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Lab Report Chapter 5"
                        value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>" required />
                </div>

                <!-- Subject -->
                <div class="col-sm-6">
                    <label class="field-label">Subject / Course</label>
                    <input type="text" name="subject" class="field-input" placeholder="e.g. Data Structures"
                        value="<?= htmlspecialchars($edit_item['subject'] ?? '') ?>" />
                </div>
            </div>

            <!-- Due date -->
            <div class="col-sm-4">
                <label class="field-label">Due Date *</label>
                <input type="date" name="due_date" class="field-input"
                    value="<?= htmlspecialchars($edit_item['due_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>" required />
            </div>

            <!-- Priority -->
            <div class="col-sm-4">
                <label class="field-label">Priority</label>
                <select name="priority" class="field-input">
                    <?php foreach (['Low', 'Medium', 'High'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($edit_item['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>>
                            <?= $p ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-sm-4">
                <label class="field-label">Status</label>
                <select name="status" class="field-input">
                    <?php foreach (['Pending', 'In Progress', 'Completed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($edit_item['status'] ?? 'Pending') === $s ? 'selected' : '' ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>