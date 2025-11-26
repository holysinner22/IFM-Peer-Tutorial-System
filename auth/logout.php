<?php
session_start();

// Destroy session
$_SESSION = [];
session_destroy();

// Clear "remember me" cookie if set
if (isset($_COOKIE['remember_user'])) {
    setcookie("remember_user", "", time() - 3600, "/", "", false, true);
}

header("Location: login.php?logout=1");
exit;
?>
