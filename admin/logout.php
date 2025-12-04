<?php
session_start();

// Clear all admin session data
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_username'],
    $_SESSION['admin_email'],
    $_SESSION['admin_role']
);

// Redirect to admin login
header('Location: login.php');
exit;
?>