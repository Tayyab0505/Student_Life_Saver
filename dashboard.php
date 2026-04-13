<?php

require_once 'includes/auth_check.php';   // Redirect if not logged in
require_once 'db.php';

$active_page = 'dashboard';
require_once 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="page-title">Good day,
            <?= htmlspecialchars($current_user_name) ?> 👋
        </h4>
        <p class="text-muted mb-0">Here's your overview for today.</p>
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