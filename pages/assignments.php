<?php
require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'assignments';
$errors = [];
$edit_item = null;

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ? AND user_id = ?');
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: assignments.php?msg=deleted');
    exit();
}

// Handle quick STATUS UPDATE (Mark Complete / Reopen)
if (isset($_GET['toggle_status'])) {
    $tog_id = (int) $_GET['toggle_status'];

    $stmt = $pdo->prepare('Select status from assignments where id = ? and user_id = ?');
    $stmt->execute([$tog_id, $current_user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $new_status = $row['status'] === 'Completed' ? 'Pending' : 'Completed';
        $stmt = $pdo->prepare("UPDATE assignments SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_status, $tog_id, $current_user_id]);
    }
    header('Location: assignments.php?msg=updated');
    exit;
}

// Load assignment for editing
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $current_user_id]);
    $edit_item = $stmt->fetch();
}

// Handle Add/update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Pending';
    $notes = trim($_POST['notes'] ?? '');
    $post_id = (int) ($_POST['edit_id'] ?? 0);

    // Validation
    if ($title === '') {
        $errors[] = 'Assignment title is required.';
    }

    if ($due_date === '') {
        $errors[] = 'Due date is required.';
    }

    if (!in_array($priority, ['Low', 'Medium', 'High'])) {
        $errors[] = 'Invalid priority.';
    }

    if (!in_array($status, ['Pending', 'In Progress', 'Completed'])) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        if ($post_id > 0) {
            $stmt = $pdo->prepare(" UPDATE assignments
                SET title=?, subject=?, due_date=?, priority=?, status=?, notes=?
                WHERE id=? AND user_id=?");
            $stmt->execute([$title, $subject, $due_date, $priority, $status, $notes, $post_id, $current_user_id]);
            header('Location: assignments.php?msg=updated');
        } else {
            $stmt = $pdo->prepare("INSERT INTO assignments (user_id, title, subject, due_date, priority, status, notes)
                VALUES (?,?,?,?,?,?,?) ");
            $stmt->execute([$current_user_id, $title, $subject, $due_date, $priority, $status, $notes]);
            header('Location: assignments.php?msg=added');
        }
        exit;
    }

    // Keep form open with entered values on error
    $edit_item = compact('title', 'subject', 'due_date', 'priority', 'status', 'notes') + ['id' => $post_id];
}

// Filters from URL
$filter_status = $_GET['filter_status'] ?? 'all';
$filter_priority = $_GET['filter_priority'] ?? 'all';

// Build query with optional features
$where = "WHERE user_id = ?";
$params = [$current_user_id];

if ($filter_status !== 'all') {
    $where .= " AND status = ?";
    $params[] = $filter_status;
}
if ($filter_priority !== 'all') {
    $where .= " AND priority = ?";
    $params[] = $filter_priority;
}

// Order: incomplete first, then by due date soonest
$stmt = $pdo->prepare("SELECT * FROM assignments $where ORDER BY FIELD(status,'Pending','In Progress','Completed'), due_date ASC");

$stmt->execute($params);
$assignments = $stmt->fetchAll();


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
            <span style="color:var(--warning)"><?= $pending_count ?> pending</span> &nbsp;·&nbsp;
            <span style="color:var(--accent)"><?= $progress_count ?> in progress</span> &nbsp;·&nbsp;
            <span style="color:var(--accent2)"><?= $completed_count ?> completed</span>
        </p>
    </div>
    <button class="btn-dash-action" id="toggleAssignBtn">
        <i class="bi bi-plus-lg"></i> Add Assignment
    </button>
</div>

<!-- ADD / EDIT FORM -->
<div class="form-panel <?= ($edit_item || !empty($errors)) ? 'open' : '' ?>" id="assignForm">
    <div class="form-panel-inner">
        <h6 class="form-panel-title">
            <i class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= isset($edit_item['id']) && $edit_item['id'] ? 'Edit Assignment' : 'Add New Assignment' ?>
        </h6>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php foreach ($errors as $e): ?>
                    <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
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

                <!-- Due date -->
                <div class="col-sm-4">
                    <label class="field-label">Due Date *</label>
                    <input type="date" name="due_date" class="field-input"
                        value="<?= htmlspecialchars($edit_item['due_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>"
                        required />
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

                <!-- Notes -->
                <div class="col-12">
                    <label class="field-label">Notes <span class="text-muted fw-normal"
                            style="text-transform:none">(optional)</span></label>
                    <textarea name="notes" class="field-input" rows="2"
                        placeholder="Any extra details about this assignment…"><?= htmlspecialchars($edit_item['notes'] ?? '') ?></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn-submit-sm">
                        <i
                            class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= isset($edit_item['id']) && $edit_item['id'] ? 'Save Changes' : 'Add Assignment' ?>
                    </button>
                    <a href="assignments.php" class="btn-cancel-sm">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <!-- Status Filters -->
    <div class="filter-group">
        <?php
        $statuses = [
            'all' => ['All', $total_count, ''],
            'Pending' => ['Pending', $pending_count, 'warning'],
            'In Progress' => ['In Progress', $progress_count, 'primary'],
            'Completed' => ['Completed', $completed_count, 'success'],
        ];
        foreach ($statuses as $val => [$label, $count, $color]):
            $active = $filter_status === $val ? 'active' : '';
            $params = $_GET;
            unset($params['msg']);
            $url = http_build_query(array_merge($params, ['filter_status' => $val]));
            ?>
            <a href="assignments.php?<?= $url ?>" class="filter-btn <?= $active ?> <?= $color ? "filter-$color" : '' ?>">
                <?= $label ?>
                <span class="filter-count"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Priority Filter -->
    <div class="filter-group">
        <span class="filter-label">Priority:</span>
        <?php foreach (['all' => 'All', 'High' => 'High', 'Medium' => 'Medium', 'Low' => 'Low'] as $val => $label):
            $active = $filter_priority === $val ? 'active' : '';
            $params = $_GET;
            unset($params['msg']);
            $url = http_build_query(array_merge($params, ['filter_priority' => $val]));
            ?>
            <a href="assignments.php?<?= $url ?>" class="filter-btn filter-sm <?= $active ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Assignments List -->
<?php if (empty($assignments)): ?>
    <div class="empty-state-page">
        <i class="bi bi-journal-x"></i>
        <h5><?= $filter_status !== 'all' || $filter_priority !== 'all' ? 'No assignments match your filters.' : 'No assignments yet.' ?>
        </h5>
        <p><?= $filter_status !== 'all' || $filter_priority !== 'all' ? 'Try changing the filters above.' : 'Click "Add Assignment" to get started.' ?>
        </p>
    </div>

<?php else: ?>
    <div class="assign-cards">
        <?php foreach ($assignments as $a):
            $today = date('Y-m-d');
            $days_left = (int) ((strtotime($a['due_date']) - strtotime($today)) / 86400);
            $is_done = $a['status'] === 'Completed';
            $overdue = !$is_done && $days_left < 0;
            ?>

            <div class="assign-card <?= $is_done ? 'is-done' : '' ?> <?= $overdue ? 'is-overdue' : '' ?>">
                <!-- Checkbox style toggle -->
                <a href="assignments.php?toggle_status=<?= $a['id'] ?>" class="assign-check <?= $is_done ? 'checked' : '' ?>"
                    title="<?= $is_done ? 'Mark as Pending' : 'Mark as Completed' ?>">
                    <i class="bi bi-<?= $is_done ? 'check-circle-fill' : 'circle' ?>"></i>
                </a>

                <!-- Main Content -->
                <div class="assign-card-body">
                    <div class="assign-card-top">
                        <span class="assign-card-title <?= $is_done ? 'line-through' : '' ?>">
                            <?= htmlspecialchars($a['title']) ?>
                        </span>
                        <div class="assign-card-badges">
                            <!-- Priority badge -->
                            <span class="badge-custom badge-<?= strtolower($a['priority']) ?>">
                                <?= $a['priority'] ?>
                            </span>
                            <!-- Status badge -->
                            <span class="badge-custom badge-status-<?= strtolower(str_replace(' ', '-', $a['status'])) ?>">
                                <?= $a['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="assign-card-meta">
                        <?php if ($a['subject']): ?>
                            <span><i class="bi bi-book"></i> <?= htmlspecialchars($a['subject']) ?></span>
                        <?php endif; ?>

                        <!-- Due date with urgency color -->
                        <span class="due-tag <?= $overdue ? 'overdue' : ($days_left <= 2 ? 'urgent' : '') ?>">
                            <i class="bi bi-calendar-event"></i>
                            <?php if ($overdue): ?>
                                Overdue by <?= abs($days_left) ?> day<?= abs($days_left) != 1 ? 's' : '' ?>
                            <?php elseif ($days_left === 0): ?>
                                Due Today
                            <?php elseif ($days_left === 1): ?>
                                Due Tomorrow
                            <?php else: ?>
                                Due <?= date('M j', strtotime($a['due_date'])) ?> (<?= $days_left ?> days)
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Notes preview -->
                    <?php if ($a['notes']): ?>
                        <p class="assign-card-notes">
                            <?= htmlspecialchars(mb_substr($a['notes'], 0, 100)) ?>             <?= strlen($a['notes']) > 100 ? '…' : '' ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="assign-card-actions">
                    <a href="assignments.php?edit=<?= $a['id'] ?>" class="icon-btn btn-edit" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="assignments.php?delete=<?= $a['id'] ?>" class="icon-btn btn-delete" title="Delete"
                        onclick="return confirm('Delete this assignment?')">
                        <i class="bi bi-trash3"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>