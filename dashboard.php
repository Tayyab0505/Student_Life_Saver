<?php

require_once 'includes/auth_check.php';   // Redirect if not logged in
require_once 'db.php';

$active_page = 'dashboard';

// Today's info
$today = date('l');
$today_date = date('Y-m-d');
$month_year = date('Y-m');

// Stat 1: Pending assignments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE user_id = ? AND status != 'Completed'");
$stmt->execute([$current_user_id]);
$pending_assignments = $stmt->fetchColumn();

// Stat 2: Classes today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM schedule WHERE user_id = ? AND day_of_week = ?");
$stmt->execute([$current_user_id, $today]);
$classes_today = $stmt->fetchColumn();

// Stat 3: Total spending this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date,'%Y-%m') = ?");
$stmt->execute([$current_user_id, $month_year]);
$monthly_spending = $stmt->fetchColumn();

// Stat 4: Average sleep (last 7 days)
$stmt = $pdo->prepare("SELECT COALESCE(AVG(hours_slept),0) FROM sleep_log WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->execute([$current_user_id]);
$avg_sleep = round($stmt->fetchColumn(), 1);

require_once 'includes/header.php';
?>

<!-- Welcome row -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">
            Good <?= (date('H') < 12) ? 'Morning' : ((date('H') < 17) ? 'Afternoon' : 'Evening') ?>,
            <?= htmlspecialchars(explode(' ', $current_user_name)[0]) ?> 👋
        </h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?= date('l, F j, Y') ?> &nbsp;·&nbsp; Here's your academic snapshot.
        </p>
    </div>
    <a href="pages/schedule.php" class="btn-dash-action">
        <i class="bi bi-plus-lg"></i> Add Class
    </a>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon icon-purple"><i class="bi bi-journal-check"></i></div>
            <div class="stat-value">
                <?= $pending_assignments ?>
            </div>
            <div class="stat-label">Pending Tasks</div>
            <a href="pages/assignments.php" class="stat-link">View all <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon icon-cyan"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-value">
                <?= $classes_today ?>
            </div>
            <div class="stat-label">Classes Today</div>
            <a href="pages/schedule.php" class="stat-link">View schedule <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon icon-amber"><i class="bi bi-wallet2"></i></div>
            <div class="stat-value">$
                <?= number_format($monthly_spending, 0) ?>
            </div>
            <div class="stat-label">Spent This Month</div>
            <a href="pages/expenses.php" class="stat-link">Track expenses <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="bi bi-moon-stars"></i></div>
            <div class="stat-value">
                <?= $avg_sleep ?>h
            </div>
            <div class="stat-label">Avg Sleep (7 days)</div>
            <a href="pages/sleep.php" class="stat-link">Log sleep <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<!-- THREE WIDGET CARDS -->
<div class="row g-4">

    <!-- Today's Classes -->
    <div class="col-lg-4">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <span><i class="bi bi-calendar-day me-2" style="color:var(--primary)"></i>Today's Classes</span>
                <span class="badge-day">
                    <?= $today ?>
                </span>
            </div>
            <div class="card-custom-body">
                <?php if (empty($todays_classes)): ?>
                    <div class="empty-card-msg">
                        <i class="bi bi-cup-hot"></i>
                        <p>No classes today.<br><span>Enjoy your free day!</span></p>
                    </div>
                <?php else: ?>
                    <div class="class-list">
                        <?php foreach ($todays_classes as $cls): ?>
                            <div class="class-item">
                                <div class="class-color-bar" style="background:<?= htmlspecialchars($cls['color']) ?>"></div>
                                <div class="class-info">
                                    <div class="class-subject">
                                        <?= htmlspecialchars($cls['subject']) ?>
                                    </div>
                                    <div class="class-meta">
                                        <i class="bi bi-clock"></i>
                                        <?= date('g:i A', strtotime($cls['start_time'])) ?> –
                                        <?= date('g:i A', strtotime($cls['end_time'])) ?>
                                        <?php if ($cls['room']): ?>
                                            &nbsp;·&nbsp; <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars($cls['room']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Assignments -->
    <div class="col-lg-4">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <span><i class="bi bi-alarm me-2" style="color:var(--warning)"></i>Due Soon</span>
                <a href="pages/assignments.php" class="card-header-link">View all</a>
            </div>
            <div class="card-custom-body">
                <?php if (empty($upcoming)): ?>
                    <div class="empty-card-msg">
                        <i class="bi bi-check-circle"></i>
                        <p>No assignments due<br><span>in the next 7 days!</span></p>
                    </div>
                <?php else: ?>
                    <div class="assign-list">
                        <?php foreach ($upcoming as $a):
                            $days_left = (int) ((strtotime($a['due_date']) - strtotime($today_date)) / 86400);
                            $urgency = $days_left === 0 ? 'high' : ($days_left <= 2 ? 'medium' : 'low');
                            ?>
                            <div class="assign-item">
                                <div class="assign-dot dot-<?= $urgency ?>"></div>
                                <div class="assign-info">
                                    <div class="assign-title">
                                        <?= htmlspecialchars($a['title']) ?>
                                    </div>
                                    <div class="assign-sub">
                                        <?= htmlspecialchars($a['subject'] ?? '—') ?>
                                    </div>
                                </div>
                                <div class="assign-due due-<?= $urgency ?>">
                                    <?= $days_left === 0 ? 'Today' : ($days_left === 1 ? 'Tomorrow' : "In {$days_left}d") ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="col-lg-4">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <span><i class="bi bi-receipt me-2" style="color:var(--accent)"></i>Recent Expenses</span>
                <a href="pages/expenses.php" class="card-header-link">View all</a>
            </div>
            <div class="card-custom-body">
                <?php if (empty($recent_expenses)): ?>
                    <div class="empty-card-msg">
                        <i class="bi bi-wallet2"></i>
                        <p>No expenses logged yet.<br><span>Start tracking today!</span></p>
                    </div>
                <?php else: ?>
                    <?php
                    $cat_icons = [
                        'Food' => 'bi-egg-fried',
                        'Transport' => 'bi-bus-front',
                        'Books' => 'bi-book',
                        'Entertainment' => 'bi-controller',
                        'Health' => 'bi-heart-pulse',
                        'Other' => 'bi-bag',
                    ];
                    ?>
                    <div class="expense-list">
                        <?php foreach ($recent_expenses as $exp): ?>
                            <div class="expense-item">
                                <div class="expense-icon">
                                    <i class="bi <?= $cat_icons[$exp['category']] ?? 'bi-bag' ?>"></i>
                                </div>
                                <div class="expense-info">
                                    <div class="expense-title"><?= htmlspecialchars($exp['title']) ?></div>
                                    <div class="expense-cat"><?= $exp['category'] ?></div>
                                </div>
                                <div class="expense-amount">$<?= number_format($exp['amount'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>