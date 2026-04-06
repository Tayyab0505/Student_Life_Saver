<?php

session_start();
require_once 'db.php';

// Handle register form

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'register'){
    $active_tab = 'register';  //keep register tab open if there is an error

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // Basic server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be atleast 6 characters.';
    } elseif($password == $confirm) {
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
 
            $success    = 'Account created! You can now log in.';
            $active_tab = 'login';  // Switch to login tab after success
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
                <div>
                    <div>Class Schedule</div>
                    <div>Assignment Tracker</div>
                    <div>Expense Tracker</div>
                    <div>Sleep Tracker</div>
                    <div>Progress Dashboard</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>