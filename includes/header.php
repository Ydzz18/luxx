<?php
$pageTitle = $pageTitle ?? 'LuxStore';
$pageStyles = $pageStyles ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <?php if ($pageStyles): ?>
    <style>
        <?= $pageStyles ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="logo"><a href="index.php" style="display: flex; align-items: center; gap: 10px;"><img src="images/logo.png" alt="LuxStore"><h1>LuxStore</h1></a></div>
        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="index.php">🏠 Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">🧭 About ▼</a>
                    <div class="dropdown-content">
                        <a href="about.php">💬 Our Story</a>
                        <a href="mission_and_vision.php">🎯 What We Stand For</a>
                        <a href="team.php">👥 Team</a>
                    </div>
                </li>
                <li><a href="shop.php">🛍️ Shop</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="cart.php">🛒 Cart</a></li>
                <?php endif; ?>
                <li><a href="contact.php">📞 Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="account.php">📇 My Account</a></li>
                    <li><a href="logout.php">🚪 Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">🔐 Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
