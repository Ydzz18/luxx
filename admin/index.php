<?php
require_once 'auth.php';
requireLogin();

require_once '../Product.php';
require_once '../Order.php';

$order = new Order();
$stats = $order->getStats();
$recentOrders = $order->getAll(null, 5);

$product = new Product();
$totalProducts = count($product->getAll());

$role = getRole();

// Fix for deprecated warning - ensure numeric values
$stats['total_orders'] = $stats['total_orders'] ?? 0;
$stats['total_revenue'] = $stats['total_revenue'] ?? 0;
$stats['pending_orders'] = $stats['pending_orders'] ?? 0;

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

            <?php if (isset($_SESSION['error_msg'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px;">
                <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            </div>
            <?php endif; ?>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_orders']) ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <?php if (isManagerOrAbove()): ?>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>₱<?= number_format($stats['total_revenue'], 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['pending_orders']) ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div class="stat-info">
                        <h3><?= number_format($totalProducts) ?></h3>
                        <p>Products</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h2>Recent Orders</h2>
                    <table class="data-table">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><a href="orders.php?id=<?= $o['id'] ?>"><?= $o['order_number'] ?></a></td>
                                <td><?= htmlspecialchars($o['shipping_name']) ?></td>
                                <td>₱<?= number_format($o['total'], 2) ?></td>
                                <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="orders.php" class="view-all">View All Orders →</a>
                </div>

                <div class="dashboard-card">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <?php if (canDo('products', 'add')): ?>
                        <a href="products.php?action=add" class="action-btn"><span>➕</span> Add Product</a>
                        <?php endif; ?>
                        
                        <a href="orders.php" class="action-btn"><span>📋</span> Manage Orders</a>
                        
                        <?php if (canDo('products', 'view')): ?>
                        <a href="products.php" class="action-btn"><span>📦</span> View Products</a>
                        <?php endif; ?>
                        
                        <?php if (canDo('categories', 'view')): ?>
                        <a href="categories.php" class="action-btn"><span>🏷️</span> Categories</a>
                        <?php endif; ?>
                        
                        <?php if (canDo('users', 'view')): ?>
                        <a href="users.php" class="action-btn"><span>👥</span> Manage Users</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Role Info Card -->
            <div class="dashboard-card" style="margin-top: 25px;">
                <h2>Your Access Level</h2>
                <p style="margin-top: 10px;">
                    You are logged in as <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong> 
                    with <?= getRoleBadge($role) ?> privileges.
                </p>
                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 14px;">
                    <?php if ($role === 'admin'): ?>
                        <p>✅ Full access to all features including settings and user management.</p>
                    <?php elseif ($role === 'manager'): ?>
                        <p>✅ Can manage products, orders, categories, customers, and feedback.<br>
                           ❌ Cannot access settings or user management.</p>
                    <?php else: ?>
                        <p>✅ Can view and update order status.<br>
                           ❌ Cannot add/edit/delete products or access sensitive areas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>