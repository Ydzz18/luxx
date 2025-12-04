<?php
require_once 'config.php';
require_once 'Order.php';

$orderNumber = $_GET['order'] ?? '';
$order = null;
$items = [];

if ($orderNumber) {
    $orderModel = new Order();
    $order = $orderModel->getByOrderNumber($orderNumber);
    if ($order) {
        $items = $orderModel->getItems($order['id']);
    }
}
?>

<?php
$pageTitle = 'LuxStore | Order Confirmed';
$pageStyles = <<<CSS
        .success-container { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .success-box { background: #111; padding: 50px; border-radius: 16px; border: 1px solid #333; text-align: center; }
        .success-icon { width: 100px; height: 100px; background: linear-gradient(45deg, #27ae60, #2ecc71); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; animation: scaleIn 0.5s ease; }
        .success-icon svg { width: 50px; height: 50px; stroke: white; }
        @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }
        .success-box h1 { font-size: 32px; margin-bottom: 10px; background: linear-gradient(45deg, #d4af37, #b8860b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .success-box .subtitle { color: #888; font-size: 18px; margin-bottom: 30px; }
        .order-number { background: #1a1a1a; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .order-number label { color: #888; font-size: 14px; }
        .order-number h2 { color: #d4af37; font-size: 28px; margin-top: 5px; letter-spacing: 2px; }
        .order-details { text-align: left; margin-top: 30px; }
        .order-details h3 { color: #d4af37; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #333; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .detail-section h4 { color: #888; font-size: 13px; text-transform: uppercase; margin-bottom: 10px; }
        .detail-section p { line-height: 1.6; }
        .order-items { margin-top: 30px; }
        .order-item { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #222; }
        .order-item:last-child { border-bottom: none; }
        .item-name { display: flex; align-items: center; gap: 15px; }
        .item-name img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        .order-totals { margin-top: 20px; padding-top: 20px; border-top: 2px solid #333; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.grand { font-size: 20px; font-weight: bold; color: #d4af37; padding-top: 15px; margin-top: 10px; border-top: 1px solid #333; }
        .action-buttons { display: flex; gap: 15px; justify-content: center; margin-top: 40px; flex-wrap: wrap; }
        .btn-primary { padding: 14px 30px; background: linear-gradient(45deg, #d4af37, #b8860b); color: #000; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s; }
        .btn-primary:hover { filter: brightness(1.15); transform: translateY(-2px); }
        .btn-secondary { padding: 14px 30px; border: 1px solid #d4af37; color: #d4af37; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
        .btn-secondary:hover { background: rgba(212, 175, 55, 0.1); }
        .status-badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: rgba(241, 196, 15, 0.2); color: #f1c40f; }
        .status-processing { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .status-shipped { background: rgba(155, 89, 182, 0.2); color: #9b59b6; }
        .status-delivered { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        
        /* NEW STYLES FOR BANK TRANSFER */
        .bank-payment-section { margin-top: 30px; padding: 25px; background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(184, 134, 11, 0.05)); border: 2px solid #d4af37; border-radius: 12px; text-align: left; }
        .bank-payment-section h3 { color: #d4af37; margin-bottom: 20px; text-align: center; font-size: 20px; border-bottom: none; padding-bottom: 0; }
        .bank-account { background: #1a1a1a; padding: 18px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #333; }
        .bank-account h4 { color: #d4af37; margin-bottom: 12px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .bank-info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 14px; }
        .bank-info-row strong { color: #d4af37; font-size: 13px; }
        .bank-info-row .value { color: #fff; font-family: monospace; }
        .copy-btn { background: #d4af37; color: #000; border: none; padding: 6px 14px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold; transition: all 0.2s; }
        .copy-btn:hover { background: #b8860b; transform: scale(1.05); }
        .payment-instructions { background: rgba(212, 175, 55, 0.05); padding: 20px; border-radius: 8px; margin-top: 15px; border: 1px solid rgba(212, 175, 55, 0.3); }
        .payment-instructions h4 { color: #d4af37; margin-bottom: 15px; font-size: 15px; }
        .payment-instructions ol { padding-left: 20px; line-height: 1.8; }
        .payment-instructions li { margin: 10px 0; color: #ccc; }
        .payment-instructions li strong { color: #d4af37; }
        .warning-box { background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; padding: 18px; border-radius: 8px; margin-top: 15px; }
        .warning-box p { color: #ffc107; margin: 8px 0; font-size: 14px; line-height: 1.6; }
        .warning-box strong { font-weight: 600; }
        
        @media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
CSS;
include 'includes/header.php';
?>

    <div class="success-container">
        <?php if (!$order): ?>
        <div class="success-box">
            <h1>Order Not Found</h1>
            <p class="subtitle">We couldn't find an order with that number.</p>
            <div class="action-buttons">
                <a href="index.php" class="btn-primary">Return Home</a>
            </div>
        </div>
        <?php else: ?>
        <div class="success-box">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h1>Thank You for Your Order!</h1>
            <p class="subtitle">Your order has been placed successfully.</p>
            
            <div class="order-number">
                <label>Order Number</label>
                <h2><?= htmlspecialchars($order['order_number']) ?></h2>
                <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
            </div>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-grid">
                    <div class="detail-section">
                        <h4>Shipping Address</h4>
                        <p>
                            <?= htmlspecialchars($order['shipping_name']) ?><br>
                            <?= htmlspecialchars($order['shipping_address']) ?><br>
                            <?= htmlspecialchars($order['shipping_city']) ?> <?= htmlspecialchars($order['shipping_postal']) ?><br>
                            <?= htmlspecialchars($order['shipping_phone']) ?>
                        </p>
                    </div>
                    <div class="detail-section">
                        <h4>Payment Method</h4>
                        <p>
                            <?php
                            $methods = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash', 'bank' => 'Bank Transfer'];
                            echo $methods[$order['payment_method']] ?? htmlspecialchars($order['payment_method']);
                            ?>
                        </p>
                        <h4 style="margin-top: 15px;">Order Date</h4>
                        <p><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
                    </div>
                </div>

                <!-- NEW SECTION: BANK TRANSFER PAYMENT DETAILS -->
                <?php if ($order['payment_method'] == 'bank'): ?>
                <div class="bank-payment-section">
                    <h3>💳 Complete Your Payment</h3>
                    
                    <!-- Bank Account 1: BDO -->
                    <div class="bank-account">
                        <h4>🏦 BDO (Banco de Oro)</h4>
                        <div class="bank-info-row">
                            <div><strong>Account Name:</strong><br><span class="value">LuxStore Philippines</span></div>
                        </div>
                        <div class="bank-info-row">
                            <div><strong>Account Number:</strong><br><span class="value" id="bdo-acct">007-123-456789</span></div>
                            <button class="copy-btn" onclick="copyText('bdo-acct')">Copy</button>
                        </div>
                    </div>

                    <!-- Bank Account 2: BPI -->
                    <div class="bank-account">
                        <h4>🏦 BPI (Bank of the Philippine Islands)</h4>
                        <div class="bank-info-row">
                            <div><strong>Account Name:</strong><br><span class="value">LuxStore Philippines</span></div>
                        </div>
                        <div class="bank-info-row">
                            <div><strong>Account Number:</strong><br><span class="value" id="bpi-acct">1234-5678-90</span></div>
                            <button class="copy-btn" onclick="copyText('bpi-acct')">Copy</button>
                        </div>
                    </div>

                    <!-- Bank Account 3: GCash -->
                    <div class="bank-account">
                        <h4>📱 GCash</h4>
                        <div class="bank-info-row">
                            <div><strong>Account Name:</strong><br><span class="value">LuxStore Philippines</span></div>
                        </div>
                        <div class="bank-info-row">
                            <div><strong>Mobile Number:</strong><br><span class="value" id="gcash-num">0917-123-4567</span></div>
                            <button class="copy-btn" onclick="copyText('gcash-num')">Copy</button>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="payment-instructions">
                        <h4>📋 How to Complete Payment</h4>
                        <ol>
                            <li>Transfer exactly <strong>₱<?= number_format($order['total'], 2) ?></strong> to any account above</li>
                            <li>Take a screenshot or photo of your payment confirmation/receipt</li>
                            <li>Send proof of payment to: <strong>payments@luxstore.com</strong></li>
                            <li>Include your Order Number <strong>#<?= htmlspecialchars($order['order_number']) ?></strong> in the email subject</li>
                            <li>We'll verify your payment within 24 hours and process your order</li>
                        </ol>
                    </div>

                    <!-- Warning Box -->
                    <div class="warning-box">
                        <p>⚠️ <strong>Important Reminder:</strong></p>
                        <p>• Your order will be processed only after payment verification</p>
                        <p>• Please complete payment within 24 hours to secure your items</p>
                        <p>• Make sure to send the proof of payment to avoid delays</p>
                    </div>
                </div>
                <?php endif; ?>
                <!-- END NEW SECTION -->

                <h3>Items Ordered</h3>
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <div class="item-name">
                            <img src="<?= htmlspecialchars($item['image'] ?? 'images/placeholder.jpg') ?>" alt="">
                            <div>
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <p style="color: #888; font-size: 14px;">Qty: <?= $item['quantity'] ?></p>
                            </div>
                        </div>
                        <div>₱<?= number_format($item['total'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-totals">
                    <div class="total-row"><span>Subtotal</span><span>₱<?= number_format($order['subtotal'], 2) ?></span></div>
                    <div class="total-row"><span>Shipping</span><span><?= $order['shipping_fee'] == 0 ? 'FREE' : '₱'.number_format($order['shipping_fee'], 2) ?></span></div>
                    <div class="total-row grand"><span>Total</span><span>₱<?= number_format($order['total'], 2) ?></span></div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="index.html#categories" class="btn-primary">Continue Shopping</a>
                <?php if (isLoggedIn()): ?>
                <a href="account.php?tab=orders" class="btn-secondary">View All Orders</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer><p>© 2025 LuxStore. All Rights Reserved.</p></footer>

    <!-- NEW SCRIPT FOR COPY FUNCTIONALITY -->
    <script>
    function copyText(elementId) {
        const text = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.style.background = '#27ae60';
            btn.textContent = '✓ Copied!';
            setTimeout(() => {
                btn.style.background = '#d4af37';
                btn.textContent = originalText;
            }, 2000);
        }).catch(err => {
            alert('Failed to copy: ' + text);
        });
    }
    </script>

<?php include 'includes/footer.php'; ?>