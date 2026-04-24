<?php
require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'expenses';
$errors = [];
$edit_item = null;

// Category Config(icon + color)
$categories = [
    'Food' => ['icon' => 'bi-egg-fried', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'Transport' => ['icon' => 'bi-bus-front', 'color' => '#06b6d4', 'bg' => '#cffafe'],
    'Books' => ['icon' => 'bi-book', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
    'Entertainment' => ['icon' => 'bi-controller', 'color' => '#ec4899', 'bg' => '#fce7f3'],
    'Health' => ['icon' => 'bi-heart-pulse', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'Other' => ['icon' => 'bi-bag', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];

require_once '../includes/header.php';
?>

<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php $msgs = [
        'added' => ['success', 'Expense added!'],
        'updated' => ['success', 'Expense updated!'],
        'deleted' => ['danger', 'Expense deleted.'],
        'budget_set' => ['success', 'Budget goal saved!'],
    ] ?>
    <?php if (isset($msgs[$_GET['msg']])):
        [$type, $text] = $msgs[$_GET['msg']]; ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i><?= $text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">Expense Tracker</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Track where your money goes.</p>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Month Switcher -->
        <form method="GET" action="expenses.php" class="d-flex align-items-center gap-2">
            <select name="month" class="field-input" style="width:auto;padding:0.45rem 0.85rem"
                onchange="this.form.submit()">
                <?php foreach ($month_options as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $val === $selected_month ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <button class="btn-dash-action" id="toggleExpenseBtn">
            <i class="bi bi-plus-lg"></i> Add Expense
        </button>
    </div>
</div>