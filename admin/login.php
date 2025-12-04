<?php
/**
 * Fixed Admin Login Page
 * Handles authentication for admin, manager, and staff roles
 */

// Start session BEFORE any output
session_start();

require_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = db();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Check in users table for admin/manager/staff
            $stmt = $pdo->prepare(
                "SELECT * FROM users 
                 WHERE (username = ? OR email = ?) 
                 AND role IN ('admin', 'manager', 'staff')
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if verified
                if (!$user['is_verified']) {
                    $error = 'Your account is not verified. Please contact the administrator.';
                } else {
                    // Login successful - Set all session variables
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['fullname'] ?: $user['username'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_role'] = $user['role'];
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Redirect to dashboard
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuxStore Admin | Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-box { 
            background: #fff; 
            padding: 50px 40px; 
            border-radius: 16px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 420px; 
        }
        .login-box h1 { 
            text-align: center; 
            margin-bottom: 10px; 
            color: #1a1a2e; 
            font-size: 28px; 
        }
        .login-box .subtitle { 
            text-align: center; 
            color: #666; 
            margin-bottom: 35px; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            color: #333; 
            font-weight: 500; 
        }
        .form-group input { 
            width: 100%; 
            padding: 14px 16px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 15px; 
            transition: border-color 0.3s; 
        }
        .form-group input:focus { 
            border-color: #d4af37; 
            outline: none; 
        }
        .btn-login { 
            width: 100%; 
            padding: 15px; 
            background: linear-gradient(45deg, #d4af37, #b8860b); 
            border: none; 
            color: #000; 
            font-size: 16px; 
            font-weight: bold; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s; 
            margin-top: 10px; 
        }
        .btn-login:hover { 
            filter: brightness(1.1); 
            transform: translateY(-2px); 
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4); 
        }
        .error-msg { 
            background: #fee; 
            border: 1px solid #fcc; 
            color: #c00; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
        }
        .logo { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .logo span { 
            font-size: 40px; 
        }
        .roles-info { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 25px; 
            font-size: 13px; 
            color: #666; 
        }
        .roles-info h4 { 
            color: #333; 
            margin-bottom: 8px; 
            font-size: 14px; 
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo"><span>👑</span></div>
        <h1>Admin Panel</h1>
        <p class="subtitle">LuxStore Management System</p>

        <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" placeholder="Enter username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="roles-info">
            <h4>Access Levels:</h4>
            <p>👑 Admin - Full access<br>
               📋 Manager - Manage content<br>
               👤 Staff - View & update orders</p>
        </div>
    </div>
</body>
</html>