<?php
// Enable error display temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>LuxStore Debug Check</h2>";

// 1. Check config.php
echo "<h3>1. Testing config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    try {
        require_once 'config.php';
        echo "✅ config.php loaded successfully<br>";
        
        // Test database connection
        try {
            $db = db();
            echo "✅ Database connection successful<br>";
        } catch (Exception $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ config.php error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php not found<br>";
}

// 2. Check User.php
echo "<h3>2. Testing User.php</h3>";
if (file_exists('User.php')) {
    echo "✅ User.php exists<br>";
    try {
        require_once 'User.php';
        echo "✅ User.php loaded successfully<br>";
        
        // Test User class
        try {
            $user = new User();
            echo "✅ User class instantiated<br>";
        } catch (Exception $e) {
            echo "❌ User class error: " . $e->getMessage() . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ User.php error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ User.php not found<br>";
}

// 3. Check Cart.php
echo "<h3>3. Testing Cart.php</h3>";
if (file_exists('Cart.php')) {
    echo "✅ Cart.php exists<br>";
    try {
        require_once 'Cart.php';
        echo "✅ Cart.php loaded<br>";
    } catch (Exception $e) {
        echo "❌ Cart.php error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ Cart.php not found (required by User.php)<br>";
}

// 4. Check mailer.php
echo "<h3>4. Testing mailer.php</h3>";
if (file_exists('mailer.php')) {
    echo "✅ mailer.php exists<br>";
    try {
        require_once 'mailer.php';
        echo "✅ mailer.php loaded<br>";
    } catch (Exception $e) {
        echo "❌ mailer.php error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ mailer.php not found (required by User.php)<br>";
}

// 5. Check login.php
echo "<h3>5. Testing login.php</h3>";
if (file_exists('login.php')) {
    echo "✅ login.php exists<br>";
} else {
    echo "❌ login.php not found<br>";
}

// 6. Check PHP error log
echo "<h3>6. Recent PHP Errors</h3>";
$error_log = __DIR__ . '/php_errors.log';
if (file_exists($error_log)) {
    echo "✅ Error log exists<br>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto; max-height: 300px;'>";
    $lines = file($error_log);
    $recent = array_slice($lines, -20); // Last 20 lines
    echo htmlspecialchars(implode('', $recent));
    echo "</pre>";
} else {
    echo "⚠️ No error log found yet<br>";
}

echo "<h3>Summary</h3>";
echo "<p>If you see any ❌ errors above, fix those files first.</p>";
echo "<p>Check the error log for specific error messages.</p>";
?>