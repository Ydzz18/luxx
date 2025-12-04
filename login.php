<?php
require_once 'config.php';
require_once 'User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('account.php');
    }
}

$error = '';
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            // Check user role and redirect accordingly
            if (isset($_SESSION['admin_id'])) {
                redirect('admin/dashboard.php');
            } else {
                $redirect = $_GET['redirect'] ?? 'account.php';
                // Sanitize redirect URL to prevent open redirect
                if (filter_var($redirect, FILTER_VALIDATE_URL) === false && strpos($redirect, '://') === false) {
                    redirect($redirect);
                } else {
                    redirect('account.php');
                }
            }
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'LuxStore | Login';
$pageStyles = <<<CSS
.auth-container { max-width: 450px; margin: 60px auto; padding: 0 20px; }
.auth-box { background: #111; padding: 40px; border-radius: 16px; border: 1px solid #333; }
.auth-box h1 { text-align: center; margin-bottom: 10px; background: linear-gradient(45deg, #d4af37, #b8860b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.auth-box .subtitle { text-align: center; color: #888; margin-bottom: 30px; font-size: 14px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #d4af37; font-weight: 500; }
.form-group input { width: 100%; padding: 14px 16px; background: #1a1a1a; border: 1px solid #333; color: #fff; border-radius: 8px; font-size: 15px; transition: border-color 0.3s; box-sizing: border-box; }
.form-group input:focus { border-color: #d4af37; outline: none; }
.form-group input::placeholder { color: #666; }
.btn-submit { width: 100%; padding: 15px; background: linear-gradient(45deg, #d4af37, #b8860b); border: none; color: #000; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
.btn-submit:hover { filter: brightness(1.15); transform: translateY(-2px); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.divider { display: flex; align-items: center; margin: 25px 0; color: #666; font-size: 14px; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #333; }
.divider span { padding: 0 15px; }
.auth-link { text-align: center; margin-top: 20px; color: #888; font-size: 14px; }
.auth-link a { color: #d4af37; text-decoration: none; font-weight: 500; }
.auth-link a:hover { text-decoration: underline; }
.error-msg { background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
.success-msg { background: rgba(39, 174, 96, 0.1); border: 1px solid #27ae60; color: #27ae60; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
.remember-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.remember-row label { display: flex; align-items: center; gap: 8px; color: #888; cursor: pointer; font-size: 14px; }
.remember-row input[type="checkbox"] { width: 18px; height: 18px; accent-color: #d4af37; cursor: pointer; }
.remember-row a { color: #d4af37; text-decoration: none; font-size: 14px; }
.remember-row a:hover { text-decoration: underline; }
.logo-section { text-align: center; margin-bottom: 30px; }
.logo-section img { width: 80px; margin-bottom: 15px; }
CSS;
include 'includes/header.php';
?>

    <div class="auth-container">
        <div class="auth-box">
            <div class="logo-section">
                <img src="images/logo.png" alt="LuxStore">
                <h1>Welcome Back</h1>
                <p class="subtitle">Sign in to your LuxStore account</p>
            </div>

            <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-msg"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" 
                           required autocomplete="current-password">
                </div>

                <div class="remember-row">
                    <label>
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="divider"><span>or</span></div>

            <p class="auth-link">Don't have an account? <a href="register.php">Create one</a></p>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>