<?php
require_once 'config.php';
require_once 'User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$user = new User();
$userData = $user->getById($_SESSION['user_id']);
$orders = $user->getOrders($_SESSION['user_id']);

$tab = $_GET['tab'] ?? 'profile';
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $result = $user->updateProfile($_SESSION['user_id'], $_POST);
    if ($result) {
        $message = 'Profile updated successfully!';
        $userData = $user->getById($_SESSION['user_id']);
    } else {
        $error = 'Failed to update profile';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if ($_POST['new_password'] === $_POST['confirm_password']) {
        $result = $user->changePassword($_SESSION['user_id'], $_POST['current_password'], $_POST['new_password']);
        if ($result['success']) {
            $message = 'Password changed successfully!';
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'New passwords do not match';
    }
}

$pageTitle = 'LuxStore | My Account';
$pageStyles = <<<CSS
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0, 0, 0, 0.9); border-bottom: 1px solid #333; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo img { width: 50px; height: 50px; }
        .logo h1 { margin: 0; font-size: 24px; background: linear-gradient(45deg, #d4af37, #b8860b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo a { text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; list-style: none; gap: 30px; }
        .nav-links a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: #d4af37; }
        .page-header { text-align: center; padding: 60px 20px 40px; background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); border-bottom: 1px solid #333; }
        .page-header h1 { font-size: 42px; margin-bottom: 10px; background: linear-gradient(45deg, #d4af37, #b8860b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-header p { color: #888; font-size: 16px; }
        .account-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 250px 1fr; gap: 30px; }
        .account-sidebar { background: #111; padding: 25px; border-radius: 12px; border: 1px solid #333; height: fit-content; }
        .account-sidebar .user-info { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #333; margin-bottom: 20px; }
        .account-sidebar .avatar { width: 80px; height: 80px; background: linear-gradient(45deg, #d4af37, #b8860b); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px; font-weight: bold; color: #000; }
        .account-sidebar .user-name { font-size: 18px; font-weight: 600; margin-bottom: 5px; }
        .account-sidebar .user-email { color: #888; font-size: 14px; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 5px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #fff; text-decoration: none; border-radius: 8px; transition: all 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(212, 175, 55, 0.1); color: #d4af37; }
        .sidebar-nav a.logout { color: #e74c3c; }
        .sidebar-nav a.logout:hover { background: rgba(231, 76, 60, 0.1); }
        .account-content { background: #111; padding: 30px; border-radius: 12px; border: 1px solid #333; }
        .account-content h2 { color: #d4af37; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #d4af37; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #333; color: #fff; border-radius: 6px; }
        .form-group input:focus, .form-group textarea:focus { border-color: #d4af37; outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn-save { padding: 12px 30px; background: linear-gradient(45deg, #d4af37, #b8860b); border: none; color: #000; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 15px; }
        .btn-save:hover { filter: brightness(1.15); }
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .orders-table th { text-align: left; padding: 12px; background: #1a1a1a; color: #d4af37; border-bottom: 2px solid #333; }
        .orders-table td { padding: 15px 12px; border-bottom: 1px solid #222; }
        .orders-table tr:hover { background: #1a1a1a; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: rgba(241, 196, 15, 0.2); color: #f1c40f; }
        .status-processing { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .status-shipped { background: rgba(155, 89, 182, 0.2); color: #9b59b6; }
        .status-delivered { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .status-cancelled { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .view-btn { color: #d4af37; text-decoration: none; padding: 8px 16px; border: 1px solid #d4af37; border-radius: 6px; font-size: 13px; display: inline-block; }
        .view-btn:hover { background: rgba(212, 175, 55, 0.1); }
        .msg-success { background: rgba(39, 174, 96, 0.1); border: 1px solid #27ae60; color: #27ae60; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .msg-error { background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .no-orders { text-align: center; padding: 60px 20px; color: #888; }
        .no-orders p { font-size: 18px; margin-bottom: 20px; }
        footer { text-align: center; padding: 40px 20px; color: #666; font-size: 14px; margin-top: 60px; border-top: 1px solid #333; }
        @media (max-width: 800px) { 
            .account-container { grid-template-columns: 1fr; } 
            .form-row { grid-template-columns: 1fr; }
        }
CSS;
include 'includes/header.php';
?>

    <section class="page-header">
        <h1>My Account</h1>
        <p>Manage your profile and orders</p>
    </section>

    <div class="account-container">
        <div class="account-sidebar">
            <div class="user-info">
                <div class="avatar">
                    <?php 
                    $initials = strtoupper(substr($userData['first_name'] ?? 'U', 0, 1) . substr($userData['last_name'] ?? 'U', 0, 1));
                    echo htmlspecialchars($initials);
                    ?>
                </div>
                <div class="user-name"><?= htmlspecialchars(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')) ?></div>
                <div class="user-email"><?= htmlspecialchars($userData['email'] ?? '') ?></div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="?tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>">👤 Profile</a></li>
                <li><a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">📦 My Orders</a></li>
                <li><a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">🔒 Change Password</a></li>
                <li><a href="logout.php" class="logout">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="account-content">
            <?php if ($message): ?><div class="msg-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($tab === 'profile'): ?>
            <h2>Profile Information</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($userData['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" value="<?= htmlspecialchars($userData['postal_code'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
            </form>

            <?php elseif ($tab === 'orders'): ?>
            <h2>My Orders</h2>
            <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="index.php" class="btn-save" style="text-decoration: none; display: inline-block;">Start Shopping</a>
            </div>
            <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                    <td>₱<?= number_format($order['total'], 2) ?></td>
                    <td><span class="status-badge status-<?= htmlspecialchars($order['status']) ?>"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></td>
                    <td><a href="order-details.php?order=<?= htmlspecialchars($order['order_number']) ?>" class="view-btn">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php elseif ($tab === 'password'): ?>
            <h2>Change Password</h2>
            <form method="POST" style="max-width: 500px;">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="8">
                    <small style="color: #888; font-size: 12px;">Must be at least 8 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn-save">Update Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>


<?php include 'includes/footer.php'; ?>