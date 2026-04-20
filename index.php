<?php

session_start();
require_once 'db.php';

// If already logged in → skip this page and go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = "";
$success = "";
$active_tab = 'login'; // tab to show by default


// Handle register form
// $_SERVER---ye aik superglobal array h jo server aur request ki info deta h
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $active_tab = 'register';  //keep register tab open if there is an error

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // Basic server-side validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be atleast 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Hash the password before storing — NEVER store plain text passwords
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed]);

            $success = 'Account created! You can now log in.';
            $active_tab = 'login';  // Switch to login tab after success
        }
    }
}

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'All fields are required';
    } else {
        // Fetch user by email 
        $stmt = $pdo->prepare('Select * from users where email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Life Saver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <div class="auth-wrapper">
        <!-- Left panel branding -->
        <div class="auth-left d-none d-lg-flex">
            <div class="auth-left-content">
                <div class="brand-icon mb-4">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1>Student<br><span>Life Saver</span> </h1>
                <p>Your all-in-one academic companion. Track classes, assignments, expenses, sleep, and your overall
                    progress — all in one place.</p>

                <!-- Feature pills -->
                <div class="feature-list mt-4">
                    <div class="feature-pill"><i class="bi bi-calendar-week"></i>Class Schedule</div>
                    <div class="feature-pill"><i class="bi bi-journal-check"></i>Assignment Tracker</div>
                    <div class="feature-pill"><i class="bi bi-wallet2"></i>Expense Tracker</div>
                    <div class="feature-pill"><i class="bi bi-moon-stars"></i>Sleep Tracker</div>
                    <div class="feature-pill"><i class="bi bi-bar-chart-line"></i>Progress Dashboard</div>
                </div>
            </div>
        </div>

        <!-- Right panel form -->
        <div class="auth-right">
            <div class="auth-form-wrap">
                <!-- Mobile logo -->
                <div class="d-lg-none text-center mb-4">
                    <i class="bi bi-mortarboard-fill" style="font-size:2.5rem; color:var(--primary)"></i>
                    <h4 class="fw-bold mt-2" style="font-family:'Sora',sans-serif;">Student Life Saver</h4>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-pills auth-tabs mb-4" id="auth-tabs">
                    <li class="nav-item flex-fill">
                        <button class="nav-link <?= $active_tab === 'login' ? 'active' : '' ?> w-100"
                            data-bs-toggle="pill" data-bs-target="#loginTab">Sign In</button>
                    </li>

                    <li class="nav-item flex-fill">
                        <button class="nav-link <?= $active_tab === 'register' ? 'active' : '' ?> w-100"
                            data-bs-toggle="pill" data-bs-target="#registerTab">Create Account</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Login tab -->
                    <div class="tab-pane <?= $active_tab === 'login' ? 'show active' : '' ?>" id="loginTab">
                        <h5 class="auth-heading">Welcome back</h5>
                        <p class="auth-sub">Sign In to continue to your Dashboard.</p>

                        <form method="post" action="index.php" novalidate>
                            <input type="hidden" name="action" value="login">

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"> <i class="bi bi-envelope"></i> </span>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="you@university.edu"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"> <i class="bi bi-lock"></i> </span>
                                    <input type="password" name="password" id="loginPass" class="form-control"
                                        placeholder="*******" required>

                                    <!-- Toggle password visibility -->
                                    <button class="input-group-text toggle-pass" type="button" data-target="loginPass">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100">
                                Sign In <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Register Tab -->
                    <div class="tab-pane <?= $active_tab === 'register' ? 'show active' : '' ?>" id="registerTab">
                        <h5 class="auth-heading">Create your account </h5>
                        <p class="auth-sub">It's free and take less then a minute.</p>

                        <form method="post" action="index.php" novalidate>
                            <input type="hidden" name="action" value="register">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control" placeholder="Your full name"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"> <i class="bi bi-envelope"></i> </span>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="you@university.edu"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="regPass" class="form-control"
                                        placeholder="Min. 6 characters" required />
                                    <button class="input-group-text toggle-pass" type="button" data-target="regPass">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="confirm_password" id="regConfirm" class="form-control"
                                        placeholder="Repeat your password" required />
                                    <button class="input-group-text toggle-pass" type="button" data-target="regConfirm">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100">
                                Create Account <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php' ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle show/hide password
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'bi bi-eye-slash'
                } else {
                    input.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            });
        });
    </script>

</body>

</html>