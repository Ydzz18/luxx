<?php
require_once 'auth.php';
requireLogin();
require_once '../Order.php';

// All roles can view orders
if (!canDo('orders', 'view')) {
    showAccessDenied();
}

$orderModel = new Order();
$statusFilter = $_GET['status'] ?? null;
$orders = $orderModel->getAll($statusFilter, 50);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderModel->updateStatus($_POST['order_id'], $_POST['status']);
    redirect('orders.php?updated=1');
}

// View single order
$viewOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $viewOrder = $orderModel->getById($_GET['id']);
    if ($viewOrder) {
        $orderItems = $orderModel->getItems($_GET['id']);
    }
}

$pageTitle = $viewOrder ? 'Order Details' : 'Orders';
$pageStyles = '
    .filters { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 20px; background: #fff; border: 1px solid #ddd; border-radius: 20px; cursor: pointer; text-decoration: none; color: #666; font-size: 14px; }
    .filter-btn:hover, .filter-btn.active { background: #d4af37; color: #000; border-color: #d4af37; }
    .order-detail { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; }
    .detail-box { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .detail-box h3 { font-size: 16px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #1a1a2e; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .info-row .label { color: #888; }
    .order-items-list { margin-top: 15px; }
    .order-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .order-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
    .order-item-info { flex: 1; }
    .order-item-info h4 { font-size: 14px; margin-bottom: 4px; }
    .order-item-info p { color: #888; font-size: 13px; }
    .status-select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; }
    .back-link:hover { color: #d4af37; }
    .success-msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
';
include 'includes/header.php';
?>

            <?php if (isset($_GET['updated'])): ?>
            <div class="success-msg">Order status updated successfully!</div>
            <?php endif; ?>

            <?php if ($viewOrder): ?>
            <!-- Single Order View -->
            <a href="orders.php" class="back-link">← Back to Orders</a>
            
            <div class="order-detail">
                <div>
                    <div class="detail-box">
                        <h3>Order #<?= htmlspecialchars($viewOrder['order_number']) ?></h3>
                        <div class="info-row"><span class="label">Date</span><span><?= date('F j, Y g:i A', strtotime($viewOrder['created_at'])) ?></span></div>
                        <div class="info-row"><span class="label">Status</span><span class="status-badge status-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span></div>
                        <div class="info-row"><span class="label">Payment</span><span><?= ucfirst($viewOrder['payment_method']) ?> - <?= ucfirst($viewOrder['payment_status']) ?></span></div>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                            <label style="font-weight: 500; margin-bottom: 8px; display: block;">Update Status</label>
                            <div style="display: flex; gap: 10px;">
                                <select name="status" class="status-select">
                                    <option value="pending" <?= $viewOrder['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $viewOrder['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $viewOrder['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $viewOrder['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $viewOrder['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="detail-box" style="margin-top: 20px;">
                        <h3>Items Ordered</h3>
                        <div class="order-items-list">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="order-item">
                                <img src="../<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" alt="">
                                <div class="order-item-info">
                                    <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                    <p>₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                </div>
                                <strong>₱<?= number_format($item['total'], 2) ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="detail-box">
                        <h3>Customer Information</h3>
                        <div class="info-row"><span class="label">Name</span><span><?= htmlspecialchars($viewOrder['shipping_name']) ?></span></div>
                        <div class="info-row"><span class="label">Email</span><span><?= htmlspecialchars($viewOrder['shipping_email']) ?></span></div>
                        <div class="info-row"><span class="label">Phone</span><span><?= htmlspecialchars($viewOrder['shipping_phone']) ?></span></div>
                    </div>
                    
                    <div class="detail-box" style="margin-top: 20px;">
                        <h3>Shipping Address</h3>
                        <p><?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?><br>
                        <?= htmlspecialchars($viewOrder['shipping_city']) ?> <?= htmlspecialchars($viewOrder['shipping_postal']) ?></p>
                    </div>
                    
                    <div class="detail-box" style="margin-top: 20px;">
                        <h3>Order Total</h3>
                        <div class="info-row"><span class="label">Subtotal</span><span>₱<?= number_format($viewOrder['subtotal'], 2) ?></span></div>
                        <div class="info-row"><span class="label">Shipping</span><span><?= $viewOrder['shipping_fee'] == 0 ? 'FREE' : '₱'.number_format($viewOrder['shipping_fee'], 2) ?></span></div>
                        <div class="info-row" style="font-weight: bold; font-size: 18px;"><span>Total</span><span style="color: #d4af37;">₱<?= number_format($viewOrder['total'], 2) ?></span></div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Orders List -->
            <div class="filters">
                <a href="orders.php" class="filter-btn <?= !$statusFilter ? 'active' : '' ?>">All</a>
                <a href="orders.php?status=pending" class="filter-btn <?= $statusFilter == 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="orders.php?status=processing" class="filter-btn <?= $statusFilter == 'processing' ? 'active' : '' ?>">Processing</a>
                <a href="orders.php?status=shipped" class="filter-btn <?= $statusFilter == 'shipped' ? 'active' : '' ?>">Shipped</a>
                <a href="orders.php?status=delivered" class="filter-btn <?= $statusFilter == 'delivered' ? 'active' : '' ?>">Delivered</a>
            </div>

            <div class="dashboard-card">
                <table class="data-table">
                    <thead>
                        <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #888;">No orders found</td></tr>
                        <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= $o['order_number'] ?></strong></td>
                            <td><?= htmlspecialchars($o['shipping_name']) ?><br><small style="color:#888;"><?= htmlspecialchars($o['shipping_email']) ?></small></td>
                            <td>₱<?= number_format($o['total'], 2) ?></td>
                            <td><?= ucfirst($o['payment_method']) ?></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td><a href="orders.php?id=<?= $o['id'] ?>">View</a></td>
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