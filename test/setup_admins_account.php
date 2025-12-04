<?php
/**
 * LuxStore - Setup Admin, Manager, and Staff Accounts
 * Run this file ONCE to create the accounts
 * DELETE THIS FILE after running for security!
 */

require_once 'config.php';

$pdo = db();

// Define accounts to create
$accounts = [
    [
        'username'   => 'admin',
        'password'   => 'admin123',      // Change this!
        'fullname'   => 'Kim Quimado',
        'first_name' => 'Kim',
        'last_name'  => 'Quimado',
        'email'      => 'royroyquimado@gmail.com',
        'phone'      => '+63 912 345 6789',
        'role'       => 'admin',
        'is_verified'=> 1
    ],
    [
        'username'   => 'manager',
        'password'   => 'manager123',    // Change this!
        'fullname'   => 'Manager Staff',
        'first_name' => 'Manager',
        'last_name'  => 'Staff',
        'email'      => 'manager@luxstore.com',
        'phone'      => '+63 912 345 6780',
        'role'       => 'manager',
        'is_verified'=> 1
    ],
    [
        'username'   => 'staff',
        'password'   => 'staff123',      // Change this!
        'fullname'   => 'Staff Member',
        'first_name' => 'Staff',
        'last_name'  => 'Member',
        'email'      => 'staff@luxstore.com',
        'phone'      => '+63 912 345 6781',
        'role'       => 'staff',
        'is_verified'=> 1
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuxStore - Account Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; padding: 40px 20px; color: #fff; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { text-align: center; color: #d4af37; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #888; margin-bottom: 40px; }
        .card { background: #fff; border-radius: 12px; padding: 30px; margin-bottom: 20px; color: #333; }
        .card h2 { color: #1a1a2e; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .update { color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #666; font-size: 13px; text-transform: uppercase; }
        .role-badge { padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #e74c3c; color: #fff; }
        .role-manager { background: #3498db; color: #fff; }
        .role-staff { background: #95a5a6; color: #fff; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 20px; border-radius: 8px; margin-top: 30px; }
        .warning h3 { margin-bottom: 10px; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(45deg, #d4af37, #b8860b); color: #000; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .btn:hover { filter: brightness(1.1); }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 LuxStore Account Setup</h1>
        <p class="subtitle">Creating admin, manager, and staff accounts</p>

        <div class="card">
            <h2>📋 Setup Results</h2>
            
            <?php
            $results = [];
            
            foreach ($accounts as $account) {
                $username = $account['username'];
                $passwordHash = password_hash($account['password'], PASSWORD_DEFAULT);
                
                // Check if user exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $account['email']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing user
                    $stmt = $pdo->prepare('UPDATE users SET 
                        password = ?, 
                        fullname = ?, 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        role = ?, 
                        is_verified = ? 
                        WHERE username = ? OR email = ?');
                    $stmt->execute([
                        $passwordHash,
                        $account['fullname'],
                        $account['first_name'],
                        $account['last_name'],
                        $account['email'],
                        $account['phone'],
                        $account['role'],
                        $account['is_verified'],
                        $username,
                        $account['email']
                    ]);
                    $results[] = [
                        'account' => $account,
                        'status' => 'updated',
                        'message' => 'Account updated successfully'
                    ];
                } else {
                    // Create new user
                    $stmt = $pdo->prepare('INSERT INTO users 
                        (username, password, fullname, first_name, last_name, email, phone, role, is_verified) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $username,
                        $passwordHash,
                        $account['fullname'],
                        $account['first_name'],
                        $account['last_name'],
                        $account['email'],
                        $account['phone'],
                        $account['role'],
                        $account['is_verified']
                    ]);
                    $results[] = [
                        'account' => $account,
                        'status' => 'created',
                        'message' => 'Account created successfully'
                    ];
                }
            }
            
            // Display results
            foreach ($results as $result): 
                $statusClass = $result['status'] === 'created' ? 'success' : 'update';
                $icon = $result['status'] === 'created' ? '✅' : '🔄';
            ?>
            <p class="<?= $statusClass ?>">
                <?= $icon ?> <strong><?= ucfirst($result['account']['role']) ?></strong> account 
                (<?= $result['account']['username'] ?>) - <?= $result['message'] ?>
            </p>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>🔐 Login Credentials</h2>
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): ?>
                    <tr>
                        <td><span class="role-badge role-<?= $acc['role'] ?>"><?= ucfirst($acc['role']) ?></span></td>
                        <td><strong><?= $acc['username'] ?></strong></td>
                        <td><code><?= $acc['password'] ?></code></td>
                        <td><?= $acc['email'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>👥 Role Permissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Admin</th>
                        <th>Manager</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Dashboard</td><td>✅</td><td>✅</td><td>✅</td></tr>
                    <tr><td>View Orders</td><td>✅</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Update Orders</td><td>✅</td><td>✅</td><td>✅</td></tr>
                    <tr><td>View Products</td><td>✅</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Add/Edit Products</td><td>✅</td><td>✅</td><td>❌</td></tr>
                    <tr><td>Delete Products</td><td>✅</td><td>❌</td><td>❌</td></tr>
                    <tr><td>Categories</td><td>✅</td><td>✅</td><td>View Only</td></tr>
                    <tr><td>Customers</td><td>✅</td><td>View Only</td><td>View Only</td></tr>
                    <tr><td>Feedback</td><td>✅</td><td>✅</td><td>View Only</td></tr>
                    <tr><td>User Management</td><td>✅</td><td>❌</td><td>❌</td></tr>
                    <tr><td>Settings</td><td>✅</td><td>❌</td><td>❌</td></tr>
                </tbody>
            </table>
        </div>

        <div class="warning">
            <h3>⚠️ Important Security Notice</h3>
            <p><strong>DELETE this file immediately after running!</strong></p>
            <p style="margin-top: 10px;">This file contains sensitive credentials and should not remain on your server.</p>
            <p style="margin-top: 10px;">File location: <code><?= __FILE__ ?></code></p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin/login.php" class="btn">Go to Admin Login →</a>
        </div>
    </div>
</body>
</html>