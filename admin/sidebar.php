<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
require_once 'auth.php';
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>👑 LuxStore</h2>
        <span>Admin Panel</span>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard - All roles -->
        <a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        
        <!-- Orders - All roles -->
        <a href="order.php" class="<?= $currentPage === 'orders' ? 'active' : '' ?>">
            <span class="icon">📦</span> Orders
            <?php
            require_once '../Order.php';
            $o = new Order();
            $s = $o->getStats();
            if ($s['pending_orders'] > 0): ?>
            <span class="badge"><?= $s['pending_orders'] ?></span>
            <?php endif; ?>
        </a>
        
        <!-- Products - All can view, but show based on permission -->
        <?php if (canDo('products', 'view')): ?>
        <a href="products.php" class="<?= $currentPage === 'products' ? 'active' : '' ?>">
            <span class="icon">🛍️</span> Products
        </a>
        <?php endif; ?>
        
        <!-- Categories - Manager and above -->
        <?php if (canDo('categories', 'view')): ?>
        <a href="categories.php" class="<?= $currentPage === 'categories' ? 'active' : '' ?>">
            <span class="icon">🏷️</span> Categories
        </a>
        <?php endif; ?>
        
        <!-- Customers - All can view -->
        <?php if (canDo('customers', 'view')): ?>
        <a href="customers.php" class="<?= $currentPage === 'customers' ? 'active' : '' ?>">
            <span class="icon">👥</span> Customers
        </a>
        <?php endif; ?>
        
        <!-- Feedback - All can view -->
        <?php if (canDo('feedback', 'view')): ?>
        <a href="feedback.php" class="<?= $currentPage === 'feedback' ? 'active' : '' ?>">
            <span class="icon">💬</span> Feedback
            <?php
            require_once '../Feedback.php';
            $fb = new Feedback();
            $fbCounts = $fb->getCounts();
            if ($fbCounts['new'] > 0): ?>
            <span class="badge"><?= $fbCounts['new'] ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        
        <div class="nav-divider"></div>
        
        <!-- Users - Admin only -->
        <?php if (canDo('users', 'view')): ?>
        <a href="users.php" class="<?= $currentPage === 'users' ? 'active' : '' ?>">
            <span class="icon">🔐</span> Users
        </a>
        <?php endif; ?>
        
        <!-- Settings - Admin only -->
        <?php if (canDo('settings', 'view')): ?>
        <a href="settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
            <span class="icon">⚙️</span> Settings
        </a>
        <?php endif; ?>
        
        <a href="../index.html" target="_blank">
            <span class="icon">🌐</span> View Store
        </a>
        
        <a href="logout.php" class="logout">
            <span class="icon">🚪</span> Logout
        </a>
    </nav>
    
    <!-- Current User Info -->
    <div style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto;">
        <p style="color: rgba(255,255,255,0.5); font-size: 12px;">Logged in as:</p>
        <p style="color: #fff; font-size: 14px;"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'User') ?></p>
        <?= getRoleBadge(getRole()) ?>
    </div>
</aside>