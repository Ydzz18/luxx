<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=cart.php');
}

$user_id = $_SESSION['user_id'];
$db = db();

// Get cart items with product details
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.image, p.stock, p.status 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shippingFee = $subtotal >= 50000 ? 0 : 500;
$total = $subtotal + $shippingFee;

$pageTitle = 'LuxStore | Shopping Cart';
$pageStyles = <<<CSS
.cart-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.cart-header { text-align: center; margin-bottom: 40px; }
.cart-header h1 { font-size: 48px; margin-bottom: 10px; }

.cart-content { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }

.cart-items {
    background: #111;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #333;
}

.cart-item {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid #333;
    align-items: center;
}

.cart-item:last-child { border-bottom: none; }

.item-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #333;
}

.item-details h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: #fff;
}

.item-price {
    font-size: 20px;
    font-weight: 700;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 8px 0;
}

.item-stock {
    font-size: 13px;
    color: #888;
}

.item-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #1a1a1a;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #333;
}

.quantity-btn {
    width: 32px;
    height: 32px;
    background: #333;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
}

.quantity-btn:hover { background: #d4af37; color: #000; }
.quantity-btn:disabled { background: #222; color: #666; cursor: not-allowed; }

.quantity-input {
    width: 50px;
    text-align: center;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

.btn-remove {
    padding: 8px 16px;
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.btn-remove:hover { background: #c0392b; }

.cart-summary {
    background: #111;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #333;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.cart-summary h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #d4af37;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #333;
}

.summary-row:last-of-type { border-bottom: 2px solid #d4af37; }

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 20px 0;
    font-size: 24px;
    font-weight: 700;
}

.summary-total .amount {
    background: linear-gradient(45deg, #d4af37, #b8860b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.btn-checkout {
    width: 100%;
    padding: 15px;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 20px;
}

.btn-checkout:hover { filter: brightness(1.2); transform: translateY(-2px); }

.btn-continue {
    width: 100%;
    padding: 12px;
    background: #333;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    margin-top: 10px;
}

.btn-continue:hover { background: #444; }

.empty-cart {
    text-align: center;
    padding: 80px 20px;
    background: #111;
    border-radius: 12px;
    border: 1px solid #333;
}

.empty-cart h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #888;
}

.empty-cart p {
    color: #666;
    margin-bottom: 20px;
}

.shipping-notice {
    background: rgba(39, 174, 96, 0.1);
    border: 1px solid #27ae60;
    color: #27ae60;
    padding: 12px;
    border-radius: 8px;
    margin-top: 15px;
    font-size: 13px;
}

@media (max-width: 968px) {
    .cart-content { grid-template-columns: 1fr; }
    .cart-item { grid-template-columns: 100px 1fr; }
    .item-actions { flex-direction: row; }
}
CSS;
include 'includes/header.php';
?>

    <div class="cart-container">
        <div class="cart-header fade-in">
            <h1 class="gold">Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-cart fade-in">
            <h3>Your cart is empty</h3>
            <p>Start shopping to add items to your cart</p>
            <a href="shop.php" class="btn-checkout" style="text-decoration: none; display: inline-block; width: auto; padding: 15px 40px;">
                Browse Products
            </a>
        </div>
        <?php else: ?>
        <div class="cart-content">
            <div class="cart-items fade-in">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-item-id="<?= $item['id'] ?>">
                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="item-image"
                         onerror="this.src='images/logo.png'">
                    
                    <div class="item-details">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                        <div class="item-stock">
                            <?php if ($item['stock'] >= $item['quantity']): ?>
                                ✓ In Stock
                            <?php else: ?>
                                ⚠️ Only <?= $item['stock'] ?> left
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="item-actions">
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, -1)">-</button>
                            <input type="text" class="quantity-input" value="<?= $item['quantity'] ?>" readonly>
                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, 1)" 
                                    <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                        </div>
                        <button class="btn-remove" onclick="removeItem(<?= $item['id'] ?>)">🗑️ Remove</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary fade-in">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Subtotal (<?= count($cartItems) ?> items)</span>
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping Fee</span>
                    <span><?= $shippingFee > 0 ? '₱' . number_format($shippingFee, 2) : 'FREE' ?></span>
                </div>
                
                <div class="summary-total">
                    <span>Total</span>
                    <span class="amount">₱<?= number_format($total, 2) ?></span>
                </div>
                
                <?php if ($subtotal < 50000): ?>
                <div class="shipping-notice">
                    ✓ Add ₱<?= number_format(50000 - $subtotal, 2) ?> more for FREE shipping!
                </div>
                <?php else: ?>
                <div class="shipping-notice">
                    🎉 You qualify for FREE shipping!
                </div>
                <?php endif; ?>
                
                <button class="btn-checkout" onclick="window.location.href='checkout.php'">
                    Proceed to Checkout
                </button>
                
                <button class="btn-continue" onclick="window.location.href='shop.php'">
                    Continue Shopping
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartId, change) {
            fetch('cart-update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_id: cartId, change: change })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update quantity');
            });
        }

        function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;
            
            fetch('cart-remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_id: cartId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove item');
            });
        }
    </script>

<?php include 'includes/footer.php'; ?>