<?php

$active_page = $active_page ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student LifeSaver —
        <?= ucfirst($active_page) ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../css/style.css" />
</head>

<body>

    <!-- SideBar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-mortarboard-fill"></i>
            <span>LifeSaver</span>
        </div>

        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>"><i
                    class="bi bi-speedometer2"></i> <span>Dashboard</span> </a>
        </nav>

        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
            <a href="../pages/schedule.php" class="nav-item <?= $active_page === 'schedule' ? 'active' : '' ?>">
                <i class="bi bi-calendar-week"></i> <span>Schedule</span>
            </a>
            <a href="../pages/assignments.php" class="nav-item <?= $active_page === 'assignments' ? 'active' : '' ?>">
                <i class="bi bi-journal-check"></i> <span>Assignments</span>
            </a>
            <a href="../pages/expenses.php" class="nav-item <?= $active_page === 'expenses' ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i> <span>Expenses</span>
            </a>
            <a href="../pages/sleep.php" class="nav-item <?= $active_page === 'sleep' ? 'active' : '' ?>">
                <i class="bi bi-moon-stars"></i> <span>Sleep & Routine</span>
            </a>
            <a href="../pages/progress.php" class="nav-item <?= $active_page === 'progress' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> <span>Progress</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item text-danger">
                <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <!-- Hamburger for mobile -->
            <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <div class="topbar-title">
                <?php
                // Map names to display title
                $titles = [
                    'dashboard' => 'Dashboard',
                    'schedule' => 'Class Schedule',
                    'assignments' => 'Assignments',
                    'expenses' => 'Expense Tracker',
                    'sleep' => 'Sleep & Routine',
                    'progress' => 'Progress Report'
                ];
                echo $titles[$active_page] ?? 'Student LifeSaver';
                ?>
            </div>

            <div class="topbar-user">
                <i class="bi bi-person-circle"></i>
                <span>
                    <?= htmlspecialchars($current_user_name) ?>
                </span>
            </div>
        </header>

        <main class="page-content">

</body>

</html>