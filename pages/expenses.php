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

// Handle ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'expense') {
    $title = trim($_POST['title'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $category = $_POST['category'] ?? 'Other';
    $expense_date = $_POST['expense_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $post_id = (int) ($_POST['edit_id'] ?? 0);

    // Validation
    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Enter a valid amount greater than 0.';
    }
    if ($expense_date === '') {
        $errors[] = 'Date is required.';
    }
    if (!array_key_exists($category, $categories)) {
        $errors[] = 'Invalid category.';
    }

    if (empty($errors)) {
        if ($post_id > 0) {
            $stmt = $pdo->prepare("UPDATE expenses SET title=?,amount=?,category=?,expense_date=?,notes=? WHERE id=? AND user_id=?");
            $stmt->execute([$title, $amount, $category, $expense_date, $notes, $post_id, $current_user_id]);
            header('Location: expenses.php?msg=updated');
        } else {
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id,title,amount,category,expense_date,notes) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$current_user_id, $title, $amount, $category, $expense_date, $notes]);
            header('Location: expenses.php?msg=added');
        }
        exit;
    }
    $edit_item = compact('title', 'amount', 'category', 'expense_date', 'notes') + ['id' => $post_id];
}

// Handle Set Budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'budget') {
    $limit = $_POST['monthly_limit'] ?? '';
    $monthly_val = $_POST['month_year'] ?? date('Y-m');
    if (is_numeric($limit) && $limit > 0) {
        $stmt = $pdo->prepare("INSERT INTO budget_goals (user_id, month_year, monthly_limit) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)");
        $stmt->execute([$current_user_id, $month_val, $limit]);
        header('Location: expenses.php?msg=budget_set');
        exit;
    }
}

// Month Filter
$selected_month = $_GET['month'] ?? date('Y-m');

// Build list of last six month for dropdown
$month_options = [];
for ($i = 0; $i < 6; $i++) {
    $m = date('Y-m', strtotime("-$i months"));
    $month_options[$m] = date('F Y', strtotime("$m-01"));
}

// Fetch expenses slected month
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date,'%Y-%m') = ? ORDER BY expense_date DESC, created_at DESC");
$stmt->execute([$current_user_id, $selected_month]);
$expenses = $stmt->fetchAll();

// Monthly total
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND DATE_FORMAT(expense_date,'%Y-%m')=?");
$stmt->execute([$current_user_id, $selected_month]);
$month_total = (float) $stmt->fetchColumn();

// Spending by category
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total, COUNT(*) as count FROM expenses WHERE user_id=? AND DATE_FORMAT(expense_date,'%Y-%m')=? GROUP BY category ORDER BY total DESC");
$stmt->execute([$current_user_id, $selected_month]);
$by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Budget goal for selected month
$stmt = $pdo->prepare("SELECT monthly_limit FROM budget_goals WHERE user_id = ? AND month_year = ?");
$stmt->execute([$current_user_id, $selected_month]);
$budget_row = $stmt->fetch();
$budget_limit = $budget_row ? (float) $budget_row['monthly_limit'] : 0;
$budget_pct = $budget_limit > 0 ? min(100, round(($month_total / $budget_limit) * 100)) : 0;
$budget_color = $budget_pct >= 100 ? 'danger' : ($budget_pct >= 75 ? 'warning' : 'success');


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

<!-- SUMMARY ROW -->
<div class="row g-3 mb-4">

    <!-- Monthly total card -->
    <div class="col-sm-6 col-lg-3">
        <div class="exp-summary-card" style="border-top:3px solid #4f46e5">
            <div class="exp-sum-label">Total Spent</div>
            <div class="exp-sum-value">$
                <?= number_format($month_total, 2) ?>
            </div>
            <div class="exp-sum-sub">
                <?= $month_options[$selected_month] ?? $selected_month ?>
            </div>
        </div>
    </div>

    <!-- Budget card -->
    <div class="col-sm-6 col-lg-3">
        <div class="exp-summary-card" style="border-top:3px solid #10b981">
            <div class="exp-sum-label">Monthly Budget</div>
            <div class="exp-sum-value">
                <?= $budget_limit > 0 ? '$' . number_format($budget_limit, 0) : '—' ?>
            </div>
            <div class="exp-sum-sub">
                <?= $budget_limit > 0
                    ? '$' . number_format(max(0, $budget_limit - $month_total), 2) . ' remaining'
                    : 'No budget set' ?>
            </div>
        </div>
    </div>

    <!-- Budget progress -->
    <div class="col-lg-6">
        <div class="exp-summary-card" style="border-top:3px solid #f59e0b">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="exp-sum-label">Budget Usage</div>
                <span class="badge bg-<?= $budget_color ?> bg-opacity-10 text-<?= $budget_color ?>"
                    style="font-size:0.75rem">
                    <?= $budget_pct ?>%
                </span>
            </div>
            <?php if ($budget_limit > 0): ?>
                <div class="budget-bar-track">
                    <div class="budget-bar-fill bg-<?= $budget_color ?>" style="width:<?= $budget_pct ?>%"></div>
                </div>
                <div class="exp-sum-sub mt-2">
                    <?php if ($budget_pct >= 100): ?>
                        <span style="color:var(--danger)"><i class="bi bi-exclamation-triangle-fill"></i> Over budget by $
                            <?= number_format($month_total - $budget_limit, 2) ?>
                        </span>
                    <?php elseif ($budget_pct >= 75): ?>
                        <span style="color:var(--warning)"><i class="bi bi-exclamation-circle"></i> Approaching budget
                            limit</span>
                    <?php else: ?>
                        <span style="color:var(--accent)"><i class="bi bi-check-circle"></i> On track</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Set budget form inline -->
                <form method="POST" action="expenses.php" class="d-flex gap-2 mt-1">
                    <input type="hidden" name="form_type" value="budget" />
                    <input type="hidden" name="month_year" value="<?= $selected_month ?>" />
                    <input type="number" name="monthly_limit" class="field-input" placeholder="Set budget…" step="1" min="1"
                        style="flex:1;padding:0.4rem 0.7rem" />
                    <button type="submit" class="btn-submit-sm" style="white-space:nowrap">Set Goal</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</div>

<!--CATEGORY BREAKDOWN -->
<?php if (!empty($by_category)): ?>
    <div class="cat-breakdown mb-4">
        <?php foreach ($by_category as $row):
            $cfg = $categories[$row['category']] ?? $categories['Other'];
            $pct = $month_total > 0 ? round(($row['total'] / $month_total) * 100) : 0; ?>
            <div class="cat-bar-item">
                <div class="cat-bar-icon" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
                    <i class="bi <?= $cfg['icon'] ?>"></i>
                </div>
                <div class="cat-bar-info">
                    <div class="d-flex justify-content-between">
                        <span class="cat-bar-name">
                            <?= $row['category'] ?>
                        </span>
                        <span class="cat-bar-amount">$
                            <?= number_format($row['total'], 2) ?> <small>(<?= $pct ?>%)
                            </small>
                        </span>
                    </div>
                    <div class="cat-bar-track">
                        <div class="cat-bar-fill" style="width:<?= $pct ?>%;background:<?= $cfg['color'] ?>"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- EXPENSES LIST -->
<?php if (empty($expenses)): ?>
    <div class="empty-state-page">
        <i class="bi bi-wallet2"></i>
        <h5>No expenses for
            <?= $month_options[$selected_month] ?? $selected_month ?>
        </h5>
        <p>Click "Add Expense" to start logging your spending.</p>
    </div>
<?php else: ?>

    <!-- Group by date -->
    <?php
    $grouped = [];
    foreach ($expenses as $exp) {
        $grouped[$exp['expense_date']][] = $exp;
    }
    ?>

    <?php foreach ($grouped as $date => $day_expenses):
        $day_total = array_sum(array_column($day_expenses, 'amount'));
        ?>
        <!-- Date group header -->
        <div class="exp-date-header">
            <span class="exp-date-label">
                <?php
                $ts = strtotime($date);
                if ($date === date('Y-m-d'))
                    echo 'Today';
                elseif ($date === date('Y-m-d', strtotime('-1 day')))
                    echo 'Yesterday';
                else
                    echo date('l, M j', $ts);
                ?>
            </span>
            <span class="exp-date-total">$
                <?= number_format($day_total, 2) ?>
            </span>
        </div>

        <!-- Expense rows for this date -->
        <?php foreach ($day_expenses as $exp):
            $cfg = $categories[$exp['category']] ?? $categories['Other'];
            ?>
            <div class="expense-row">
                <div class="exp-row-icon" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
                    <i class="bi <?= $cfg['icon'] ?>"></i>
                </div>
                <div class="exp-row-info">
                    <div class="exp-row-title">
                        <?= htmlspecialchars($exp['title']) ?>
                    </div>
                    <div class="exp-row-meta">
                        <span class="exp-cat-tag" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
                            <?= $exp['category'] ?>
                        </span>
                        <?php if ($exp['notes']): ?>
                            <span class="exp-row-note">
                                <?= htmlspecialchars($exp['notes']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="exp-row-amount">$
                    <?= number_format($exp['amount'], 2) ?>
                </div>
                <div class="exp-row-actions">
                    <a href="expenses.php?edit=<?= $exp['id'] ?>&month=<?= $selected_month ?>" class="icon-btn btn-edit"
                        title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="expenses.php?delete=<?= $exp['id'] ?>&month=<?= $selected_month ?>" class="icon-btn btn-delete"
                        title="Delete" onclick="return confirm('Delete this expense?')">
                        <i class="bi bi-trash3"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>