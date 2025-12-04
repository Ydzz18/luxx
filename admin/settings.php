<?php
require_once 'auth.php';
requireLogin();

// Admin only
if (!canDo('settings', 'view')) {
    showAccessDenied();
}

$pdo = db();
$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'general';

// Create settings table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Default settings
$defaults = [
    'site_name' => 'LuxStore',
    'site_tagline' => 'Premium Luxury E-Commerce',
    'site_email' => 'luxstore@gmail.com',
    'site_phone' => '+63 912 345 6789',
    'site_address' => '123 Luxury Avenue, Puerto Princesa City, Philippines',
    'currency' => '₱',
    'currency_code' => 'PHP',
    'free_shipping_min' => '100000',
    'shipping_fee' => '1000',
    'tax_rate' => '12',
    'facebook_url' => 'https://www.facebook.com/share/181RCtTzMB/?mibextid=wwXIfr',
    'instagram_url' => '',
    'twitter_url' => '',
    'maintenance_mode' => '0',
    'allow_guest_checkout' => '1'
];

// Save setting (insert or update)
function saveSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        saveSetting('site_name', $_POST['site_name']);
        saveSetting('site_tagline', $_POST['site_tagline']);
        saveSetting('site_email', $_POST['site_email']);
        saveSetting('site_phone', $_POST['site_phone']);
        saveSetting('site_address', $_POST['site_address']);
        $message = 'General settings saved!';
    }
    
    if (isset($_POST['save_payment'])) {
        saveSetting('currency', $_POST['currency']);
        saveSetting('currency_code', $_POST['currency_code']);
        saveSetting('free_shipping_min', $_POST['free_shipping_min']);
        saveSetting('shipping_fee', $_POST['shipping_fee']);
        saveSetting('tax_rate', $_POST['tax_rate']);
        saveSetting('allow_guest_checkout', isset($_POST['allow_guest_checkout']) ? '1' : '0');
        $message = 'Payment settings saved!';
    }
    
    if (isset($_POST['save_social'])) {
        saveSetting('facebook_url', $_POST['facebook_url']);
        saveSetting('instagram_url', $_POST['instagram_url']);
        saveSetting('twitter_url', $_POST['twitter_url']);
        $message = 'Social media settings saved!';
    }
    
    if (isset($_POST['save_advanced'])) {
        saveSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        $message = 'Advanced settings saved!';
    }
    
    if (isset($_POST['save_email'])) {
        saveSetting('email_order_confirm', isset($_POST['email_order_confirm']) ? '1' : '0');
        saveSetting('email_admin_notify', isset($_POST['email_admin_notify']) ? '1' : '0');
        saveSetting('email_status_update', isset($_POST['email_status_update']) ? '1' : '0');
        saveSetting('email_welcome', isset($_POST['email_welcome']) ? '1' : '0');
        $message = 'Email settings saved!';
        $tab = 'email';
    }
    
    if (isset($_POST['send_test_email'])) {
        require_once '../mailer.php';
        $testEmail = trim($_POST['test_email']);
        $mailer = new Mailer();
        
        // Use reflection to access private method or create a test method
        $testContent = '
            <h2 style="color: #1a1a2e; margin-top: 0;">✅ Test Email Successful!</h2>
            <p style="color: #666;">This is a test email from your LuxStore admin panel.</p>
            <p style="color: #666;">If you received this, your email configuration is working correctly.</p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <p style="margin: 0; color: #888;"><strong>Sent:</strong> ' . date('F j, Y g:i A') . '</p>
                <p style="margin: 5px 0 0 0; color: #888;"><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>
            </div>
        ';
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . getSetting('site_name') . ' <' . getSetting('site_email') . '>'
        ];
        
        if (mail($testEmail, '✅ Test Email from ' . getSetting('site_name'), $mailer->getTemplate($testContent), implode("\r\n", $headers))) {
            $message = 'Test email sent to ' . $testEmail . '!';
        } else {
            $error = 'Failed to send test email. Check your server configuration.';
        }
        $tab = 'email';
    }
    
    if (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($currentPass, $user['password'])) {
            $error = 'Current password is incorrect!';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New passwords do not match!';
        } elseif (strlen($newPass) < 8) {
            $error = 'Password must be at least 8 characters!';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['admin_id']]);
            $message = 'Password changed successfully!';
        }
        $tab = 'account';
    }
}

$pageTitle = 'Settings';
$pageStyles = '
    .settings-container {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 30px;
    }
    .settings-nav {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        height: fit-content;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .settings-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: all 0.2s;
    }
    .settings-nav a:hover,
    .settings-nav a.active {
        background: rgba(212, 175, 55, 0.1);
        color: #d4af37;
    }
    .settings-nav a .icon {
        font-size: 18px;
    }
    .settings-content {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .settings-content h2 {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        color: #1a1a2e;
    }
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #eee;
    }
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .form-section h3 {
        font-size: 16px;
        color: #666;
        margin-bottom: 15px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #d4af37;
        outline: none;
    }
    .form-group small {
        color: #888;
        font-size: 13px;
        margin-top: 5px;
        display: block;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }
    .toggle-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toggle-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #d4af37;
    }
    .btn-save {
        background: linear-gradient(45deg, #d4af37, #b8860b);
        color: #000;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 15px;
    }
    .btn-save:hover {
        filter: brightness(1.1);
    }
    .success-msg {
        background: #d4edda;
        color: #155724;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    .error-msg {
        background: #f8d7da;
        color: #721c24;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    @media (max-width: 800px) {
        .settings-container {
            grid-template-columns: 1fr;
        }
        .form-row,
        .form-row-3 {
            grid-template-columns: 1fr;
        }
    }
';

include 'includes/header.php';
?>

            <?php if ($message): ?><div class="success-msg">✓ <?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

            <div class="settings-container">
                <nav class="settings-nav">
                    <a href="?tab=general" class="<?= $tab === 'general' ? 'active' : '' ?>"><span class="icon">🏪</span> General</a>
                    <a href="?tab=payment" class="<?= $tab === 'payment' ? 'active' : '' ?>"><span class="icon">💳</span> Payment & Shipping</a>
                    <a href="?tab=social" class="<?= $tab === 'social' ? 'active' : '' ?>"><span class="icon">📱</span> Social Media</a>
                    <a href="?tab=account" class="<?= $tab === 'account' ? 'active' : '' ?>"><span class="icon">🔐</span> My Account</a>
                    <a href="?tab=advanced" class="<?= $tab === 'advanced' ? 'active' : '' ?>"><span class="icon">⚙️</span> Advanced</a>
                    <a href="?tab=email" class="<?= $tab === 'email' ? 'active' : '' ?>"><span class="icon">📧</span> Email</a>
                </nav>

                <div class="settings-content">
                    <?php if ($tab === 'general'): ?>
                    <h2>🏪 General Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars(getSetting('site_name')) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tagline</label>
                            <input type="text" name="site_tagline" value="<?= htmlspecialchars(getSetting('site_tagline')) ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="site_email" value="<?= htmlspecialchars(getSetting('site_email')) ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="site_phone" value="<?= htmlspecialchars(getSetting('site_phone')) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="site_address" rows="2"><?= htmlspecialchars(getSetting('site_address')) ?></textarea>
                        </div>
                        <button type="submit" name="save_general" class="btn-save">Save Changes</button>
                    </form>

                    <?php elseif ($tab === 'payment'): ?>
                    <h2>💳 Payment & Shipping</h2>
                    <form method="POST">
                        <div class="form-section">
                            <h3>Currency</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Currency Symbol</label>
                                    <input type="text" name="currency" value="<?= htmlspecialchars(getSetting('currency')) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Currency Code</label>
                                    <input type="text" name="currency_code" value="<?= htmlspecialchars(getSetting('currency_code')) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-section">
                            <h3>Shipping</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Shipping Fee (<?= getSetting('currency') ?>)</label>
                                    <input type="number" name="shipping_fee" value="<?= htmlspecialchars(getSetting('shipping_fee')) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Free Shipping Minimum (<?= getSetting('currency') ?>)</label>
                                    <input type="number" name="free_shipping_min" value="<?= htmlspecialchars(getSetting('free_shipping_min')) ?>">
                                    <small>Orders above this amount get free shipping</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-section">
                            <h3>Tax</h3>
                            <div class="form-group" style="max-width: 200px;">
                                <label>Tax Rate (%)</label>
                                <input type="number" name="tax_rate" step="0.01" value="<?= htmlspecialchars(getSetting('tax_rate')) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="toggle-group">
                                <input type="checkbox" name="allow_guest_checkout" <?= getSetting('allow_guest_checkout') === '1' ? 'checked' : '' ?>>
                                Allow Guest Checkout (without account)
                            </label>
                        </div>
                        <button type="submit" name="save_payment" class="btn-save">Save Changes</button>
                    </form>

                    <?php elseif ($tab === 'social'): ?>
                    <h2>📱 Social Media</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>📘 Facebook URL</label>
                            <input type="url" name="facebook_url" value="<?= htmlspecialchars(getSetting('facebook_url')) ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        <div class="form-group">
                            <label>📸 Instagram URL</label>
                            <input type="url" name="instagram_url" value="<?= htmlspecialchars(getSetting('instagram_url')) ?>" placeholder="https://instagram.com/yourpage">
                        </div>
                        <div class="form-group">
                            <label>🦜 Twitter URL</label>
                            <input type="url" name="twitter_url" value="<?= htmlspecialchars(getSetting('twitter_url')) ?>" placeholder="https://twitter.com/yourpage">
                        </div>
                        <button type="submit" name="save_social" class="btn-save">Save Changes</button>
                    </form>

                    <?php elseif ($tab === 'account'): ?>
                    <h2>🔐 My Account</h2>
                    <div class="form-section">
                        <h3>Account Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <div><?= getRoleBadge(getRole()) ?></div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h3>Change Password</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn-save">Change Password</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>