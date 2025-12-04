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
$stats = $orderModel->getStats();

// Add fallback for missing avg_order_value
if (!isset($stats['avg_order_value'])) {
    $stats['avg_order_value'] = $stats['total_orders'] > 0 
        ? $stats['total_revenue'] / $stats['total_orders'] 
        : 0;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (canDo('orders', 'edit')) {
        $orderModel->updateStatus($_POST['order_id'], $_POST['status']);
        redirect('orders.php?updated=1');
    }
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

$pageTitle = $viewOrder ? 'Order Details' : 'Orders Management';
$pageStyles = '
    .order-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .mini-stat {
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .mini-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .mini-stat h4 {
        font-size: 32px;
        color: #1a1a2e;
        margin-bottom: 8px;
        font-weight: 700;
    }
    .mini-stat p {
        color: #888;
        font-size: 14px;
        font-weight: 500;
    }
    .filters {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    .filter-btn {
        padding: 10px 24px;
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 25px;
        cursor: pointer;
        text-decoration: none;
        color: #666;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .filter-btn:hover,
    .filter-btn.active {
        background: linear-gradient(45deg, #d4af37, #b8860b);
        color: #000;
        border-color: #d4af37;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }
    .order-detail {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 25px;
    }
    .detail-box {
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }
    .detail-box h3 {
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        color: #1a1a2e;
        font-weight: 700;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f8f8f8;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-row .label {
        color: #888;
        font-weight: 600;
        font-size: 14px;
    }
    .info-row .value {
        color: #333;
        font-weight: 500;
        text-align: right;
    }
    .order-items-list {
        margin-top: 20px;
    }
    .order-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        border-radius: 8px;
        transition: background 0.2s ease;
    }
    .order-item:hover {
        background: #f8f9fa;
    }
    .order-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .order-item-info {
        flex: 1;
    }
    .order-item-info h4 {
        font-size: 15px;
        margin-bottom: 5px;
        color: #1a1a2e;
    }
    .order-item-info p {
        color: #888;
        font-size: 13px;
    }
    .order-item-total {
        font-weight: 700;
        font-size: 16px;
        color: #d4af37;
    }
    .status-select {
        padding: 10px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .status-select:focus {
        border-color: #d4af37;
        outline: none;
    }
    .order-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    .order-summary .info-row {
        padding: 8px 0;
    }
    .order-summary .total-row {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #e0e0e0;
        font-size: 20px;
        font-weight: 700;
    }
    .order-summary .total-row .value {
        color: #d4af37;
    }
    @media (max-width: 900px) {
        .order-detail {
            grid-template-columns: 1fr;
        }
    }
';
include 'includes/header.php';
?>

            <?php if (isset($_GET['updated'])): ?>
            <div class="success-msg">✓ Order status updated successfully!</div>
            <?php endif; ?>

            <?php if ($viewOrder): ?>
            <!-- Single Order View -->
            <a href="orders.php" class="back-link">← Back to Orders</a>
            
            <div class="order-detail">
                <div>
                    <!-- Order Info -->
                    <div class="detail-box">
                        <h3>Order #<?= htmlspecialchars($viewOrder['order_number']) ?></h3>
                        <div class="info-row">
                            <span class="label">Order Date</span>
                            <span class="value"><?= date('F j, Y g:i A', strtotime($viewOrder['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Status</span>
                            <span class="value"><span class="status-badge status-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Payment Method</span>
                            <span class="value"><?= ucfirst($viewOrder['payment_method']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Payment Status</span>
                            <span class="value"><?= ucfirst($viewOrder['payment_status']) ?></span>
                        </div>
                        
                        <?php if (canDo('orders', 'edit')): ?>
                        <form method="POST" style="margin-top: 25px;">
                            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                            <label style="font-weight: 600; margin-bottom: 10px; display: block; color: #333;">Update Order Status</label>
                            <div style="display: flex; gap: 12px;">
                                <select name="status" class="status-select" style="flex: 1;">
                                    <option value="pending" <?= $viewOrder['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $viewOrder['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $viewOrder['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $viewOrder['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $viewOrder['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-primary" style="padding: 10px 24px;">Update</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="detail-box" style="margin-top: 25px;">
                        <h3>Order Items (<?= count($orderItems) ?>)</h3>
                        <div class="order-items-list">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="order-item">
                                <img src="../<?= htmlspecialchars($item['image'] ?? 'uploads/placeholder.jpg') ?>" alt="">
                                <div class="order-item-info">
                                    <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                    <p>₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                </div>
                                <div class="order-item-total">
                                    ₱<?= number_format($item['total'], 2) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Customer Info -->
                    <div class="detail-box">
                        <h3>Customer Information</h3>
                        <div class="info-row">
                            <span class="label">Name</span>
                            <span class="value"><?= htmlspecialchars($viewOrder['shipping_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email</span>
                            <span class="value"><?= htmlspecialchars($viewOrder['shipping_email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Phone</span>
                            <span class="value"><?= htmlspecialchars($viewOrder['shipping_phone']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="detail-box" style="margin-top: 20px;">
                        <h3>Shipping Address</h3>
                        <p style="line-height: 1.8; color: #333;">
                            <?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?><br>
                            <?= htmlspecialchars($viewOrder['shipping_city']) ?> <?= htmlspecialchars($viewOrder['shipping_postal']) ?>
                        </p>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="detail-box" style="margin-top: 20px;">
                        <h3>Order Summary</h3>
                        <div class="order-summary">
                            <div class="info-row">
                                <span class="label">Subtotal</span>
                                <span class="value">₱<?= number_format($viewOrder['subtotal'], 2) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Shipping Fee</span>
                                <span class="value"><?= $viewOrder['shipping_fee'] == 0 ? 'FREE' : '₱'.number_format($viewOrder['shipping_fee'], 2) ?></span>
                            </div>
                            <div class="info-row total-row">
                                <span class="label">Total</span>
                                <span class="value">₱<?= number_format($viewOrder['total'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Orders List -->
            
            <!-- Stats -->
            <div class="order-stats">
                <div class="mini-stat">
                    <h4><?= number_format($stats['total_orders']) ?></h4>
                    <p>Total Orders</p>
                </div>
                <div class="mini-stat">
                    <h4><?= number_format($stats['pending_orders']) ?></h4>
                    <p>Pending</p>
                </div>
                <?php if (isManagerOrAbove()): ?>
                <div class="mini-stat">
                    <h4>₱<?= number_format($stats['total_revenue'], 2) ?></h4>
                    <p>Total Revenue</p>
                </div>
                <div class="mini-stat">
                    <h4>₱<?= number_format($stats['avg_order_value'], 2) ?></h4>
                    <p>Avg Order Value</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <a href="orders.php" class="filter-btn <?= !$statusFilter ? 'active' : '' ?>">All Orders</a>
                <a href="orders.php?status=pending" class="filter-btn <?= $statusFilter == 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="orders.php?status=processing" class="filter-btn <?= $statusFilter == 'processing' ? 'active' : '' ?>">Processing</a>
                <a href="orders.php?status=shipped" class="filter-btn <?= $statusFilter == 'shipped' ? 'active' : '' ?>">Shipped</a>
                <a href="orders.php?status=delivered" class="filter-btn <?= $statusFilter == 'delivered' ? 'active' : '' ?>">Delivered</a>
                <a href="orders.php?status=cancelled" class="filter-btn <?= $statusFilter == 'cancelled' ? 'active' : '' ?>">Cancelled</a>
            </div>

            <!-- Orders Table -->
            <div class="dashboard-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: #888;">
                                <p style="font-size: 18px; margin-bottom: 10px;">📦</p>
                                <p>No orders found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong><?= $o['order_number'] ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($o['shipping_name']) ?><br>
                                <small style="color:#888;"><?= htmlspecialchars($o['shipping_email']) ?></small>
                            </td>
                            <td><?= $o['total_items'] ?? '-' ?> items</td>
                            <td><strong>₱<?= number_format($o['total'], 2) ?></strong></td>
                            <td><?= ucfirst($o['payment_method']) ?></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?><br><small style="color:#888;"><?= date('g:i A', strtotime($o['created_at'])) ?></small></td>
                            <td><a href="orders.php?id=<?= $o['id'] ?>">View →</a></td>
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