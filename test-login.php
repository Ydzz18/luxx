<?php
// Enable error display for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h2>LuxStore Login Test</h2>";
echo "<style>body{font-family:Arial;padding:20px;background:#0a0a0a;color:#fff;} table{border-collapse:collapse;margin:20px 0;} th,td{padding:12px;border:1px solid #333;text-align:left;} th{background:#d4af37;color:#000;} .success{color:#27ae60;} .error{color:#e74c3c;}</style>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $db = db();
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    die();
}

// Fetch users from database
echo "<h3>2. Users in Database</h3>";
try {
    $stmt = $db->query("SELECT id, username, email, role, fullname FROM users ORDER BY role, id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p class='error'>❌ No users found in database</p>";
    } else {
        echo "<p class='success'>✅ Found " . count($users) . " users</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['fullname']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['role']) . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error fetching users: " . $e->getMessage() . "</p>";
}

// Test password for admin
echo "<h3>3. Password Hash Test</h3>";
echo "<p>Testing if default password '<strong>admin123</strong>' works for admin user...</p>";

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['royroyquimado@gmail.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user found: " . htmlspecialchars($admin['email']) . "</p>";
        
        // Test password
        if (password_verify('admin123', $admin['password'])) {
            echo "<p class='success'>✅ Password 'admin123' is CORRECT</p>";
        } else {
            echo "<p class='error'>❌ Password 'admin123' is INCORRECT</p>";
            echo "<p>The password in the database is: <code>" . htmlspecialchars($admin['password']) . "</code></p>";
            
            // Generate new password
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            echo "<p>To reset password to 'admin123', run this SQL:</p>";
            echo "<pre style='background:#1a1a1a;padding:10px;border:1px solid #333;'>UPDATE users SET password = '$newHash' WHERE email = 'royroyquimado@gmail.com';</pre>";
        }
    } else {
        echo "<p class='error'>❌ Admin user not found</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error testing password: " . $e->getMessage() . "</p>";
}

// Test User class
echo "<h3>4. User Class Test</h3>";
try {
    require_once 'User.php';
    $userObj = new User();
    echo "<p class='success'>✅ User class loaded successfully</p>";
    
    // Test login method
    echo "<p>Testing login with royroyquimado@gmail.com / admin123...</p>";
    $result = $userObj->login('royroyquimado@gmail.com', 'admin123');
    
    if ($result['success']) {
        echo "<p class='success'>✅ Login test SUCCESSFUL</p>";
        echo "<p>Session variables set:</p>";
        echo "<ul>";
        echo "<li>user_id: " . ($_SESSION['user_id'] ?? 'not set') . "</li>";
        echo "<li>user_name: " . ($_SESSION['user_name'] ?? 'not set') . "</li>";
        echo "<li>admin_id: " . ($_SESSION['admin_id'] ?? 'not set') . "</li>";
        echo "<li>admin_role: " . ($_SESSION['admin_role'] ?? 'not set') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Login test FAILED: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ User class error: " . $e->getMessage() . "</p>";
}

echo "<hr style='margin:30px 0;border-color:#333;'>";
echo "<h3>Test Complete</h3>";
echo "<p>If all tests pass, you can try logging in at: <a href='login.php' style='color:#d4af37;'>login.php</a></p>";
?>