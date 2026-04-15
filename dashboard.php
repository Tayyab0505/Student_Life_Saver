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

<div class="row g-4">
    <div class="col-12">
        <div class="card card-custom p-4 text-center text-muted">
            <i class="bi bi-hourglass-split" style="font-size:3rem; opacity:0.3;"></i>
            <p class="mt-3 mb-0">Dashboard content coming in Step 2!<br>
                For now, make sure your login and register work correctly.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>