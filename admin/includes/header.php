<?php
$pageTitle = $pageTitle ?? 'LuxStore Admin';
$pageStyles = $pageStyles ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | LuxStore Admin</title>
    <link rel="icon" href="../images/logo.png" type="image/png">
    <link rel="stylesheet" href="admin-styles.css">
    <?php if ($pageStyles): ?>
    <style>
        <?= $pageStyles ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/../sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="header-right">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'User') ?></span>
                    <?= getRoleBadge(getRole()) ?>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            </header>