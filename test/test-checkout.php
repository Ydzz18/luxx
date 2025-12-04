<?php
/**
 * Checkout System Test
 * Run this to verify your checkout is working
 * Access: http://localhost/test-checkout.php
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>LuxStore - Checkout Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 3px solid #d4af37; padding-bottom: 10px; }
        .test-section { background: white; padding: 25px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section h2 { color: #d4af37; margin-top: 0; font-size: 18px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        ul { line-height: 2; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { display: inline-block; padding: 10px 20px; background: #d4af37; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px; }
        .btn:hover { background: #b8860b; }
    </style>
</head>
<body>
    <h1>🛒 LuxStore Checkout System Test</h1>";

$db = db();
$allTestsPassed = true;

// TEST 1: Check Database Tables
echo "<div class='test-section'>
    <h2>✓ Test 1: Database Tables</h2>";

$requiredTables = ['orders', 'order_items', 'products', 'users', 'cart'];
$missingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "<p class='success'>✓ All required tables exist</p>";
} else {
    echo "<p class='error'>✗ Missing tables: " . implode(', ', $missingTables) . "</p>";
    $allTestsPassed = false;
}
echo "</div>";

// TEST 2: Check Orders Table Columns
echo "<div class='test-section'>
    <h2>✓ Test 2: Orders Table Structure</h2>";

$requiredColumns = [
    'id', 'order_number', 'subtotal', 'shipping_fee', 'total',
    'shipping_name', 'shipping_email', 'shipping_phone',
    'shipping_address', 'shipping_city', 'shipping_postal',
    'payment_method', 'payment_status', 'status', 'notes'
];

try {
    $stmt = $db->query("DESCRIBE orders");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "<p class='success'>✓ All required columns exist in orders table</p>";
        echo "<table><tr><th>Column Name</th><th>Status</th></tr>";
        foreach ($requiredColumns as $col) {
            echo "<tr><td>$col</td><td class='success'>✓ Exists</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
        echo "<p class='warning'>⚠️ You need to run the migration script!</p>";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking table: " . $e->getMessage() . "</p>";
    $allTestsPassed = false;
}
echo "</div>";

// TEST 3: Check Required Files
echo "<div class='test-section'>
    <h2>✓ Test 3: Required Files</h2>";

$requiredFiles = [
    'config.php' => 'Configuration file',
    'Cart.php' => 'Cart model',
    'Order.php' => 'Order model',
    'Product.php' => 'Product model',
    'api.php' => 'API endpoint',
    'checkout.php' => 'Checkout page',
    'order-success.php' => 'Success page'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    if (!file_exists($file)) {
        $missingFiles[$file] = $description;
    }
}

if (empty($missingFiles)) {
    echo "<p class='success'>✓ All required files exist</p>";
    echo "<ul>";
    foreach ($requiredFiles as $file => $desc) {
        echo "<li class='success'>✓ $file - $desc</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>✗ Missing files:</p><ul>";
    foreach ($missingFiles as $file => $desc) {
        echo "<li class='error'>✗ $file - $desc</li>";
    }
    echo "</ul>";
    $allTestsPassed = false;
}
echo "</div>";

// TEST 4: Check Products (for testing)
echo "<div class='test-section'>
    <h2>✓ Test 4: Sample Products</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    
    if ($productCount > 0) {
        echo "<p class='success'>✓ Found $productCount products in database</p>";
        
        // Show sample products
        $stmt = $db->query("SELECT id, name, price, stock FROM products LIMIT 5");
        $products = $stmt->fetchAll();
        
        if (!empty($products)) {
            echo "<table>
                <tr><th>ID</th><th>Product Name</th><th>Price</th><th>Stock</th></tr>";
            foreach ($products as $p) {
                echo "<tr>
                    <td>{$p['id']}</td>
                    <td>{$p['name']}</td>
                    <td>₱" . number_format($p['price'], 2) . "</td>
                    <td>{$p['stock']}</td>
                </tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='warning'>⚠️ No products found. Add products to test checkout.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// TEST 5: API Endpoint Test
echo "<div class='test-section'>
    <h2>✓ Test 5: API Endpoint</h2>";

if (file_exists('api.php')) {
    echo "<p class='success'>✓ api.php file exists</p>";
    echo "<p class='info'>ℹ️ API endpoint is ready at: <code>" . $_SERVER['HTTP_HOST'] . "/api.php</code></p>";
    
    // Check if file is readable
    if (is_readable('api.php')) {
        echo "<p class='success'>✓ api.php is readable</p>";
    } else {
        echo "<p class='error'>✗ api.php is not readable. Check file permissions.</p>";
        $allTestsPassed = false;
    }
} else {
    echo "<p class='error'>✗ api.php file not found!</p>";
    $allTestsPassed = false;
}
echo "</div>";

// FINAL SUMMARY
echo "<div class='test-section' style='background: " . ($allTestsPassed ? "#d4edda" : "#f8d7da") . "'>
    <h2>" . ($allTestsPassed ? "✓ All Tests Passed!" : "⚠️ Some Tests Failed") . "</h2>";

if ($allTestsPassed) {
    echo "<p class='success' style='font-size: 18px;'>Your checkout system is ready! 🎉</p>
    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>Add products to cart from the shop page</li>
        <li>Go to checkout and place a test order</li>
        <li>Check the admin panel for the order</li>
    </ul>
    <p>
        <a href='index.html' class='btn'>🏠 Go to Home</a>
        <a href='checkout.php' class='btn'>🛒 Test Checkout</a>
        <a href='admin/' class='btn'>👤 Admin Panel</a>
    </p>";
} else {
    echo "<p class='error' style='font-size: 18px;'>Please fix the issues above before testing checkout</p>
    <p><strong>Common Solutions:</strong></p>
    <ul>
        <li>If tables are missing: Import your database schema</li>
        <li>If columns are missing: Run <a href='add_payment_columns.php'>add_payment_columns.php</a></li>
        <li>If files are missing: Make sure all files are uploaded to your server</li>
    </ul>";
}

echo "</div>";

// Additional Info
echo "<div class='test-section'>
    <h2>📋 System Information</h2>
    <ul>
        <li><strong>PHP Version:</strong> " . phpversion() . "</li>
        <li><strong>Database:</strong> Connected ✓</li>
        <li><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>
        <li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>
    </ul>
</div>";

echo "</body></html>";