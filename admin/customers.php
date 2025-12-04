<?php
require_once 'auth.php';
requireLogin();

// Check permission
if (!canDo('customers', 'view')) {
    showAccessDenied();
}

$db = db();

// Get customers with order stats
$customers = $db->query("
    SELECT u.*, 
           COUNT(o.id) as order_count, 
           COALESCE(SUM(o.total), 0) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
")->fetchAll();

// View single customer
$viewCustomer = null;
$customerOrders = [];
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $viewCustomer = $stmt->fetch();
    
    if ($viewCustomer) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_GET['id']]);
        $customerOrders = $stmt->fetchAll();
    }
}

$pageTitle = $viewCustomer ? 'Customer Details' : 'Customers';
$pageStyles = '
    .customer-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
    .mini-stat { background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .mini-stat h4 { font-size: 28px; color: #1a1a2e; }
    .mini-stat p { color: #888; font-size: 14px; margin-top: 5px; }
    .customer-detail { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
    .customer-card { background: #fff; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .customer-avatar { width: 100px; height: 100px; background: linear-gradient(45deg, #d4af37, #b8860b); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; font-weight: bold; color: #000; }
    .customer-card h2 { font-size: 22px; margin-bottom: 5px; }
    .customer-card .email { color: #888; margin-bottom: 20px; }
    .customer-info-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; text-align: left; }
    .customer-info-item:last-child { border-bottom: none; }
    .customer-info-item .label { color: #888; }
    .orders-section { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .orders-section h3 { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; }
    .back-link:hover { color: #d4af37; }
';
include 'includes/header.php';
?>

            <?php if ($viewCustomer): ?>
            <!-- Single Customer View -->
            <a href="customers.php" class="back-link">← Back to Customers</a>
            
            <div class="customer-detail">
                <div class="customer-card">
                    <div class="customer-avatar">
                        <?= strtoupper(substr($viewCustomer['first_name'], 0, 1) . substr($viewCustomer['last_name'], 0, 1)) ?>
                    </div>
                    <h2><?= htmlspecialchars($viewCustomer['first_name'] . ' ' . $viewCustomer['last_name']) ?></h2>
                    <p class="email"><?= htmlspecialchars($viewCustomer['email']) ?></p>
                    
                    <div class="customer-info-item">
                        <span class="label">Phone</span>
                        <span><?= htmlspecialchars($viewCustomer['phone'] ?: 'Not provided') ?></span>
                    </div>
                    <div class="customer-info-item">
                        <span class="label">City</span>
                        <span><?= htmlspecialchars($viewCustomer['city'] ?: 'Not provided') ?></span>
                    </div>
                    <div class="customer-info-item">
                        <span class="label">Joined</span>
                        <span><?= date('M j, Y', strtotime($viewCustomer['created_at'])) ?></span>
                    </div>
                    <div class="customer-info-item">
                        <span class="label">Total Orders</span>
                        <span><?= count($customerOrders) ?></span>
                    </div>
                </div>

                <div class="orders-section">
                    <h3>Order History</h3>
                    <?php if (empty($customerOrders)): ?>
                    <p style="color: #888; text-align: center; padding: 30px;">No orders yet</p>
                    <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerOrders as $o): ?>
                            <tr>
                                <td><?= $o['order_number'] ?></td>
                                <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                <td>₱<?= number_format($o['total'], 2) ?></td>
                                <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><a href="orders.php?id=<?= $o['id'] ?>">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- Customers List -->
            <div class="customer-stats">
                <div class="mini-stat">
                    <h4><?= count($customers) ?></h4>
                    <p>Total Customers</p>
                </div>
                <div class="mini-stat">
                    <h4><?= count(array_filter($customers, fn($c) => $c['order_count'] > 0)) ?></h4>
                    <p>With Orders</p>
                </div>
                <div class="mini-stat">
                    <h4>₱<?= number_format(array_sum(array_column($customers, 'total_spent')), 2) ?></h4>
                    <p>Total Revenue</p>
                </div>
            </div>

            <div class="dashboard-card">
                <table class="data-table">
                    <thead>
                        <tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #888;">No customers yet</td></tr>
                        <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                            <td><?= $c['order_count'] ?></td>
                            <td>₱<?= number_format($c['total_spent'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                            <td><a href="customers.php?id=<?= $c['id'] ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>