<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            header('Location: ../dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1>College Lab Inventory</h1>
    <p>Login to continue</p>
    <?php if (isset($_GET['timeout'])): ?>
        <div class="alert">Your session expired. Please log in again.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn-primary">Login</button>
    </form>
</div>
</body>
</html>
