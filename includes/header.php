<?php

$active_page = $active_page ?? '';
>?

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student LifeSaver — <?= ucfirst($active_page) ?>
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
    </aside>

    <nav class="sidebar-nav">
        <a href="../dashboard.php" class="nav-item <?= $active_page==='dashboard'   ? 'active':'' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        <a href="../pages/schedule.php" class="nav-item <?= $active_page==='schedule'    ? 'active':'' ?>">
            <i class="bi bi-calendar-week"></i> <span>Schedule</span>
        </a>
        <a href="../pages/assignments.php" class="nav-item <?= $active_page==='assignments' ? 'active':'' ?>">
            <i class="bi bi-journal-check"></i> <span>Assignments</span>
        </a>
        <a href="../pages/expenses.php" class="nav-item <?= $active_page==='expenses'    ? 'active':'' ?>">
            <i class="bi bi-wallet2"></i> <span>Expenses</span>
        </a>
        <a href="../pages/sleep.php" class="nav-item <?= $active_page==='sleep'       ? 'active':'' ?>">
            <i class="bi bi-moon-stars"></i> <span>Sleep & Routine</span>
        </a>
        <a href="../pages/progress.php" class="nav-item <?= $active_page==='progress'    ? 'active':'' ?>">
            <i class="bi bi-bar-chart-line"></i> <span>Progress</span>
        </a>
    </nav>

</body>

</html>