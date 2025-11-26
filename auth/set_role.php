<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && in_array($_POST['role'], $_SESSION['roles'])) {
    $_SESSION['role'] = $_POST['role'];
    $role = $_POST['role'];

    // Handle remember me cookie if requested
    if (!empty($_SESSION['remember']) && $_SESSION['remember'] === true) {
        $uid = $_SESSION['user_id'];
        $token = hash('sha256', $uid . $role . 'SECRET_KEY');

        setcookie(
            "remember_user",
            $uid . "|" . $role . "|" . $token,
            time() + (30 * 24 * 60 * 60), // 30 days
            "/",
            "",
            false,
            true
        );
        unset($_SESSION['remember']); // clear the flag
    }

    header("Location: ../dashboard/{$role}.php");
    exit;
}

header("Location: login.php");
exit;
