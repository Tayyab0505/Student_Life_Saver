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

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM expenses where id = ? AND user_id = ?');
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: expenses.php?msg=deleted');
    exit;
}

// Load expense for editing
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ? AND user_id = ?');
    $stmt->execute([$edit_id, $current_user_id]);
    $edit_item = $stmt->fetch();
}

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

<!-- ADD/EDIT FORM -->
<div class="form-panel <?= ($edit_item || !empty($errors)) ? 'open' : '' ?>" id="expenseForm">
    <div class="form-panel-inner">
        <h6 class="form-panel-title">
            <i class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= isset($edit_item['id']) && $edit_item['id'] ? 'Edit Expense' : 'Add New Expense' ?>
        </h6>

        <?php if (!empty($errors)): ?>
            <div>
                <?php foreach ($errors as $e): ?>
                    <div> <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?> </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="expenses.php">
            <input type="hidden" name="form_type" value="expense" />
            <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? 0 ?>" />

            <div class="row g-3">
                <!-- Title -->
                <div class="col-sm-6">
                    <label class="field-label">Description *</label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Lunch at cafeteria"
                        value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>" required />
                </div>

                <!-- Amount -->
                <div class="col-sm-3">
                    <label class="field-label">Amount ($) *</label>
                    <input type="number" name="amount" class="field-input" placeholder="0.00" step="0.01" min="0.01"
                        value="<?= htmlspecialchars($edit_item['amount'] ?? '') ?>" required />
                </div>

                <!-- Date -->
                <div class="col-sm-3">
                    <label class="field-label">Date *</label>
                    <input type="date" name="expense_date" class="field-input"
                        value="<?= htmlspecialchars($edit_item['expense_date'] ?? date('Y-m-d')) ?>" required />
                </div>

                <!-- Category -->
                <div class="col-12">
                    <label class="field-label">Category</label>
                    <div class="category-picker">
                        <?php foreach ($categories as $cat => $cfg): ?>
                            <label class="cat-option">
                                <input type="radio" name="category" value="<?= $cat ?>" <?= ($edit_item['category'] ?? 'Food') === $cat ? 'checked' : '' ?> />
                                <span class="cat-pill" style="--cat-color:<?= $cfg['color'] ?>;--cat-bg:<?= $cfg['bg'] ?>">
                                    <i class="bi <?= $cfg['icon'] ?>"></i> <?= $cat ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="field-label">Notes <span class="text-muted fw-normal"
                            style="text-transform:none">(optional)</span></label>
                    <input type="text" name="notes" class="field-input" placeholder="Any extra details…"
                        value="<?= htmlspecialchars($edit_item['notes'] ?? '') ?>" />
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn-submit-sm">
                        <i
                            class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= isset($edit_item['id']) && $edit_item['id'] ? 'Save Changes' : 'Add Expense' ?>
                    </button>
                    <a href="expenses.php?month=<?= $selected_month ?>" class="btn-cancel-sm">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>