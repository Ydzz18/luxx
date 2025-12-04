<?php
require_once 'config.php';
require_once 'Cart.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$cart = new Cart();
$items = $cart->getItems();
$subtotal = $cart->getTotal();
$shipping = $subtotal >= 50000 ? 0 : 500;
$tax = $subtotal * 0.12;
$total = $subtotal + $shipping + $tax;

// Get user data if logged in
$userData = null;
if (isLoggedIn()) {
    require_once 'User.php';
    $user = new User();
    $userData = $user->getById($_SESSION['user_id']);
}

$pageTitle = 'LuxStore | Checkout';
$pageStyles = <<<CSS
* { box-sizing: border-box; }
        .checkout-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; }
        .checkout-form { background: #111; padding: 30px; border-radius: 12px; border: 1px solid #333; }
        .checkout-form h2 { color: #d4af37; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #333; }
        .form-section { margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #d4af37; font-weight: 500; font-size: 14px; }
        .form-group label .required { color: #e74c3c; margin-left: 3px; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #333; 
            color: #fff; border-radius: 6px; font-size: 14px; transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            border-color: #d4af37; outline: none; box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        .form-group input.error, .form-group select.error { border-color: #e74c3c; }
        .form-group input.success { border-color: #27ae60; }
        .error-message { 
            color: #e74c3c; font-size: 12px; margin-top: 5px; display: none; 
            animation: slideDown 0.3s ease;
        }
        .error-message.show { display: block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .order-summary { 
            background: #111; padding: 30px; border-radius: 12px; border: 1px solid #d4af37; 
            height: fit-content; position: sticky; top: 20px;
        }
        .order-summary h2 { color: #d4af37; margin-bottom: 20px; font-size: 20px; }
        .summary-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #333; }
        .summary-item:last-of-type { border-bottom: 2px solid #333; margin-bottom: 15px; }
        .summary-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
        .summary-item-info { flex: 1; }
        .summary-item-info h4 { font-size: 14px; margin-bottom: 5px; }
        .summary-item-info p { color: #888; font-size: 13px; }
        .summary-item-price { font-weight: bold; color: #d4af37; }
        .summary-totals { margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; color: #ccc; }
        .summary-row.total { 
            border-top: 2px solid #d4af37; font-size: 20px; color: #d4af37; 
            font-weight: bold; margin-top: 10px; padding-top: 15px;
        }
        
        .btn-checkout { 
            width: 100%; padding: 15px; background: linear-gradient(45deg, #d4af37, #b8860b); 
            border: none; color: #000; font-weight: bold; font-size: 16px; border-radius: 6px; 
            cursor: pointer; margin-top: 20px; transition: all 0.3s;
        }
        .btn-checkout:hover { filter: brightness(1.15); transform: translateY(-2px); }
        .btn-checkout:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .payment-methods { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 15px; }
        .payment-method { 
            padding: 15px; border: 2px solid #333; border-radius: 8px; cursor: pointer; 
            text-align: center; transition: all 0.3s; position: relative;
        }
        .payment-method:hover { border-color: #555; background: rgba(255,255,255,0.02); }
        .payment-method.selected { border-color: #d4af37; background: rgba(212, 175, 55, 0.1); }
        .payment-method input { position: absolute; opacity: 0; pointer-events: none; }
        .payment-method-icon { font-size: 24px; margin-bottom: 8px; }
        .payment-method-label { font-size: 13px; font-weight: 500; }
        
        .payment-info { 
            margin-top: 15px; padding: 20px; background: rgba(212, 175, 55, 0.05); 
            border: 1px solid #d4af37; border-radius: 8px; display: none;
        }
        .payment-info.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .payment-info h4 { color: #d4af37; margin-bottom: 15px; font-size: 14px; }
        .payment-info p { font-size: 13px; line-height: 1.6; color: #ccc; margin: 5px 0; }
        
        .bank-transfer-fields { margin-top: 15px; display: none; }
        .bank-transfer-fields.active { display: block; }
        
        .alert { 
            padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: none; 
            animation: slideDown 0.3s ease;
        }
        .alert.show { display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; }
        .alert-success { background: rgba(39, 174, 96, 0.1); border: 1px solid #27ae60; color: #27ae60; }
        .alert-icon { font-size: 20px; }
        
        .empty-cart { text-align: center; padding: 80px 20px; }
        .empty-cart h2 { font-size: 28px; color: #d4af37; margin-bottom: 15px; }
        .empty-cart p { color: #888; margin-bottom: 30px; }
        .btn-primary { 
            display: inline-block; padding: 14px 30px; background: linear-gradient(45deg, #d4af37, #b8860b);
            color: #000; text-decoration: none; border-radius: 8px; font-weight: bold;
        }
        
        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0,0,0,0.8); display: none; align-items: center; 
            justify-content: center; z-index: 9999;
        }
        .loading-overlay.active { display: flex; }
        .loading-spinner { 
            width: 50px; height: 50px; border: 4px solid #333; 
            border-top-color: #d4af37; border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        @media (max-width: 900px) { 
            .checkout-container { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .order-summary { position: relative; top: 0; }
        }
CSS;
include 'includes/header.php';
?>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <section class="page-header">
        <h1 class="gold">Checkout</h1>
        <p>Complete your order securely</p>
    </section>

    <?php if (empty($items)): ?>
    <div class="empty-cart">
        <h2>Your cart is empty</h2>
        <p>Add some items to your cart before checking out</p>
        <a href="index.html#categories" class="btn-primary">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="checkout-container">
        <form class="checkout-form" id="checkoutForm" novalidate>
            <div id="formAlert" class="alert"></div>
            
            <div class="form-section">
                <h2>📍 Shipping Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" id="first_name" required 
                               value="<?= $userData ? htmlspecialchars($userData['first_name']) : '' ?>">
                        <div class="error-message" id="first_name-error">Please enter your first name</div>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" id="last_name" required 
                               value="<?= $userData ? htmlspecialchars($userData['last_name']) : '' ?>">
                        <div class="error-message" id="last_name-error">Please enter your last name</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" id="email" required 
                               value="<?= $userData ? htmlspecialchars($userData['email']) : '' ?>">
                        <div class="error-message" id="email-error">Please enter a valid email</div>
                    </div>
                    <div class="form-group">
                        <label>Phone <span class="required">*</span></label>
                        <input type="tel" name="phone" id="phone" required 
                               value="<?= $userData ? htmlspecialchars($userData['phone']) : '' ?>"
                               placeholder="09XX-XXX-XXXX">
                        <div class="error-message" id="phone-error">Please enter a valid phone number</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Complete Address <span class="required">*</span></label>
                    <textarea name="address" id="address" rows="3" required 
                              placeholder="House/Unit #, Street Name, Barangay"><?= $userData ? htmlspecialchars($userData['address']) : '' ?></textarea>
                    <div class="error-message" id="address-error">Please enter your complete address</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City <span class="required">*</span></label>
                        <input type="text" name="city" id="city" required 
                               value="<?= $userData ? htmlspecialchars($userData['city']) : '' ?>">
                        <div class="error-message" id="city-error">Please enter your city</div>
                    </div>
                    <div class="form-group">
                        <label>Postal Code <span class="required">*</span></label>
                        <input type="text" name="postal" id="postal" required 
                               value="<?= $userData ? htmlspecialchars($userData['postal_code']) : '' ?>"
                               pattern="[0-9]{4}" placeholder="4100">
                        <div class="error-message" id="postal-error">Please enter a 4-digit postal code</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Order Notes (optional)</label>
                    <textarea name="notes" rows="2" placeholder="Any special instructions for your order..."></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>💳 Payment Method</h2>
                <div class="payment-methods">
                    <label class="payment-method selected" data-method="cod">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <div class="payment-method-icon">💵</div>
                        <div class="payment-method-label">Cash on Delivery</div>
                    </label>
                    <label class="payment-method" data-method="bank">
                        <input type="radio" name="payment_method" value="bank">
                        <div class="payment-method-icon">🏦</div>
                        <div class="payment-method-label">Bank Transfer</div>
                    </label>
                </div>

                <!-- COD Info -->
                <div class="payment-info active" id="codInfo">
                    <h4>💵 Cash on Delivery</h4>
                    <p>✓ Pay with cash when your order arrives</p>
                    <p>✓ Please prepare exact amount</p>
                    <p>✓ No additional fees</p>
                </div>

                <!-- Bank Transfer Info + Form -->
                <div class="payment-info" id="bankInfo">
                    <h4>🏦 Bank Transfer Payment</h4>
                    <p><strong>You will receive our bank account details after placing your order.</strong></p>
                    <p>Please provide your payment information below:</p>
                    
                    <div class="bank-transfer-fields" id="bankFields">
                        <div class="form-group">
                            <label>Your Bank <span class="required">*</span></label>
                            <select name="bank_name" id="bank_name">
                                <option value="">-- Select Your Bank --</option>
                                <option value="BDO">BDO (Banco de Oro)</option>
                                <option value="BPI">BPI (Bank of the Philippine Islands)</option>
                                <option value="Metrobank">Metrobank</option>
                                <option value="UnionBank">UnionBank</option>
                                <option value="Landbank">Landbank</option>
                                <option value="Security Bank">Security Bank</option>
                                <option value="GCash">GCash</option>
                                <option value="PayMaya">PayMaya</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="error-message" id="bank_name-error">Please select your bank</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Account Name <span class="required">*</span></label>
                            <input type="text" name="bank_account_name" id="bank_account_name" 
                                   placeholder="Your name as it appears on your account">
                            <div class="error-message" id="bank_account_name-error">Please enter the account name</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Account/Mobile Number <span class="required">*</span></label>
                            <input type="text" name="bank_account_number" id="bank_account_number" 
                                   placeholder="Your account or mobile number">
                            <div class="error-message" id="bank_account_number-error">Please enter your account/mobile number</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Reference Number <span class="required">*</span></label>
                            <input type="text" name="bank_reference" id="bank_reference" 
                                   placeholder="Enter after transferring payment">
                            <div class="error-message" id="bank_reference-error">Please enter the reference number</div>
                        </div>
                    </div>
                    
                    <p style="margin-top: 15px; color: #f39c12; font-size: 13px;">
                        ⚠️ <strong>Important:</strong> Complete payment within 24 hours to secure your order.
                    </p>
                </div>
            </div>
        </form>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <?php foreach ($items as $item): 
                $price = $item['sale_price'] ?? $item['price'];
                $itemTotal = $price * $item['quantity'];
            ?>
            <div class="summary-item">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="summary-item-info">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p>Qty: <?= $item['quantity'] ?> × ₱<?= number_format($price, 2) ?></p>
                </div>
                <div class="summary-item-price">₱<?= number_format($itemTotal, 2) ?></div>
            </div>
            <?php endforeach; ?>
            
            <div class="summary-totals">
                <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($subtotal, 2) ?></span></div>
                <div class="summary-row"><span>Shipping</span><span><?= $shipping == 0 ? 'FREE' : '₱'.number_format($shipping, 2) ?></span></div>
                <?php if ($tax > 0): ?>
                <div class="summary-row"><span>Tax</span><span>₱<?= number_format($tax, 2) ?></span></div>
                <?php endif; ?>
                <div class="summary-row total"><span>Total</span><span>₱<?= number_format($total, 2) ?></span></div>
            </div>
            
            <button type="submit" form="checkoutForm" class="btn-checkout" id="submitBtn">
                🔒 Place Order Securely
            </button>
            
            <p style="text-align: center; margin-top: 15px; color: #888; font-size: 12px;">
                🔒 Your information is secure and encrypted
            </p>
        </div>
    </div>
    <?php endif; ?>

    <footer><p>© 2025 LuxStore. All Rights Reserved.</p></footer>

    <script>
    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            // Update UI
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            
            const paymentValue = this.querySelector('input').value;
            
            // Show/hide payment info
            document.querySelectorAll('.payment-info').forEach(info => info.classList.remove('active'));
            
            if (paymentValue === 'cod') {
                document.getElementById('codInfo').classList.add('active');
                document.getElementById('bankFields').classList.remove('active');
                clearBankFieldValidation();
            } else if (paymentValue === 'bank') {
                document.getElementById('bankInfo').classList.add('active');
                document.getElementById('bankFields').classList.add('active');
            }
        });
    });

    function clearBankFieldValidation() {
        ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_reference'].forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.value = '';
                input.classList.remove('error', 'success');
                document.getElementById(`${field}-error`)?.classList.remove('show');
            }
        });
    }

    function showAlert(message, type = 'error') {
        const alert = document.getElementById('formAlert');
        const icon = type === 'error' ? '❌' : '✅';
        alert.className = `alert alert-${type} show`;
        alert.innerHTML = `<span class="alert-icon">${icon}</span><span>${message}</span>`;
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        if (type === 'success') {
            setTimeout(() => alert.classList.remove('show'), 3000);
        }
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(`${fieldId}-error`);
        
        if (field && errorDiv) {
            field.classList.add('error');
            field.classList.remove('success');
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
            
            // Clear error on input
            field.addEventListener('input', function() {
                this.classList.remove('error');
                if (this.value.trim()) {
                    this.classList.add('success');
                }
                errorDiv.classList.remove('show');
            }, { once: true });
        }
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10 && cleaned.length <= 11;
    }

    function validateForm(formData) {
        let isValid = true;
        const errors = [];

        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('input, textarea, select').forEach(el => el.classList.remove('error', 'success'));

        // Validate shipping fields
        const requiredFields = {
            'first_name': 'First name is required',
            'last_name': 'Last name is required',
            'email': 'Email is required',
            'phone': 'Phone number is required',
            'address': 'Address is required',
            'city': 'City is required',
            'postal': 'Postal code is required'
        };
        
        for (const [field, message] of Object.entries(requiredFields)) {
            const value = formData.get(field);
            if (!value || value.trim() === '') {
                showFieldError(field, message);
                isValid = false;
                errors.push(field);
            } else {
                document.getElementById(field)?.classList.add('success');
            }
        }

        // Validate email format
        const email = formData.get('email');
        if (email && !validateEmail(email)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }

        // Validate phone format
        const phone = formData.get('phone');
        if (phone && !validatePhone(phone)) {
            showFieldError('phone', 'Please enter a valid Philippine phone number');
            isValid = false;
        }

        // Validate postal code
        const postal = formData.get('postal');
        if (postal && !/^\d{4}$/.test(postal)) {
            showFieldError('postal', 'Postal code must be 4 digits');
            isValid = false;
        }

        // Validate bank transfer fields if selected
        const paymentMethod = formData.get('payment_method');
        if (paymentMethod === 'bank') {
            const bankFields = {
                'bank_name': 'Please select your bank',
                'bank_account_name': 'Account name is required',
                'bank_account_number': 'Account number is required',
                'bank_reference': 'Reference number is required'
            };
            
            for (const [field, message] of Object.entries(bankFields)) {
                const value = formData.get(field);
                if (!value || value.trim() === '') {
                    showFieldError(field, message);
                    isValid = false;
                    errors.push(field);
                } else {
                    document.getElementById(field)?.classList.add('success');
                }
            }
        }

        return { isValid, errors };
    }

    // Real-time validation
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value.trim()) {
                this.classList.add('success');
                this.classList.remove('error');
            }
        });
    });

    // Form submission
    document.getElementById('checkoutForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submitBtn');
        const overlay = document.getElementById('loadingOverlay');
        const originalText = btn.innerHTML;
        
        // Disable button
        btn.disabled = true;
        btn.innerHTML = '⏳ Processing Order...';
        overlay.classList.add('active');
        
        const formData = new FormData(this);
        
        // Combine first and last name
        const fullName = `${formData.get('first_name')} ${formData.get('last_name')}`;
        formData.set('name', fullName);
        formData.append('action', 'place_order');
        
        // Validate form
        const validation = validateForm(formData);
        if (!validation.isValid) {
            showAlert('Please fill in all required fields correctly', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            overlay.classList.remove('active');
            
            // Scroll to first error
            const firstError = document.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            return;
        }
        
        try {
            const res = await fetch('api.php', { 
                method: 'POST', 
                body: formData 
            });
            
            if (!res.ok) {
                throw new Error(`Server error: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (data.success) {
                showAlert('✅ Order placed successfully! Redirecting...', 'success');
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = `order-success.php?order=${data.order_number}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to place order');
            }
        } catch (err) {
            console.error('Checkout error:', err);
            showAlert(err.message || 'An error occurred. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            overlay.classList.remove('active');
        }
    });

    // Prevent accidental page leave
    let formModified = false;
    document.getElementById('checkoutForm')?.addEventListener('input', () => {
        formModified = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (formModified && !document.getElementById('submitBtn').disabled) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    </script>

<?php include 'includes/footer.php'; ?>