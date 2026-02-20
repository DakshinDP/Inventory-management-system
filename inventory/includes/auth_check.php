<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$timeoutSeconds = 1800;

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
    session_unset();
    session_destroy();
    header('Location: auth/login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();
