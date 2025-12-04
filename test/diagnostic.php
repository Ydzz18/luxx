<?php
/**
 * LuxStore System Diagnostic Tool
 * Run this file to check your system configuration
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>LuxStore Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <h1>🔍 LuxStore System Diagnostic</h1>";

// Check 1: PHP Version
echo "<div class='section'><h2>1. PHP Configuration</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4', '>=')) {
    echo "<div class='success'>✓ PHP Version: $phpVersion (OK)</div>";
} else {
    echo "<div class='error'>✗ PHP Version: $phpVersion (Need 7.4 or higher)</div>";
}

// Check 2: Required Extensions
echo "<h3>Required Extensions:</h3>";
$required = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✓ $ext extension loaded</div>";
    } else {
        echo "<div class='error'>✗ $ext extension NOT loaded</div>";
    }
}
echo "</div>";

// Check 3: Database Connection
echo "<div class='section'><h2>2. Database Connection</h2>";
try {
    $conn = new PDO("mysql:host=localhost;dbname=luxstore_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✓ Database connection successful</div>";
    
    // Check tables
    $tables = ['users', 'products', 'categories', 'orders', 'cart', 'settings'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✓ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>✗ Table '$table' NOT found</div>";
        }
    }
    
    // Check products count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "<div class='info'>📦 Products in database: $count</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>⚠️ Make sure to:<br>1. Create database 'luxstore_db'<br>2. Import luxstore_db.sql<br>3. Check MySQL is running</div>";
}
echo "</div>";

// Check 4: File Structure
echo "<div class='section'><h2>3. File Structure</h2>";
$required_files = [
    'config.php',
    'api.php',
    'products_api.php',
    'Cart.php',
    'Product.php',
    'User.php',
    'Order.php',
    'Settings.php',
    'scripts.js',
    'styles.css',
    'index.html'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✓ $file exists</div>";
    } else {
        echo "<div class='error'>✗ $file NOT found</div>";
    }
}

// Check images folder
if (is_dir('images')) {
    echo "<div class='success'>✓ images/ folder exists</div>";
    if (file_exists('images/logo.png')) {
        echo "<div class='success'>✓ images/logo.png exists</div>";
    } else {
        echo "<div class='warning'>⚠️ images/logo.png NOT found</div>";
    }
} else {
    echo "<div class='error'>✗ images/ folder NOT found</div>";
}
echo "</div>";

// Check 5: Server Configuration
echo "<div class='section'><h2>4. Server Configuration</h2>";
echo "<div class='info'>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</div>";
echo "<div class='info'>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
echo "<div class='info'>Script Path: " . __FILE__ . "</div>";

// Check if .htaccess exists
if (file_exists('.htaccess')) {
    echo "<div class='success'>✓ .htaccess file exists</div>";
} else {
    echo "<div class='warning'>⚠️ .htaccess file not found (may be optional)</div>";
}

echo "</div>";

// Check 6: Test API Endpoints
echo "<div class='section'><h2>5. API Endpoints Test</h2>";
echo "<div class='info'>Testing API endpoints...</div>";

// Test if we can include config
if (file_exists('config.php')) {
    try {
        include_once 'config.php';
        echo "<div class='success'>✓ config.php loaded successfully</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error loading config.php: " . $e->getMessage() . "</div>";
    }
}

echo "</div>";

// Check 7: Permissions
echo "<div class='section'><h2>6. File Permissions</h2>";
$check_writable = ['uploads', 'logs'];
foreach ($check_writable as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<div class='success'>✓ $dir/ is writable</div>";
        } else {
            echo "<div class='warning'>⚠️ $dir/ is NOT writable (may cause issues with uploads/logs)</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ $dir/ folder doesn't exist (create if needed)</div>";
    }
}
echo "</div>";

// Final Recommendations
echo "<div class='section'><h2>7. Recommendations</h2>";
echo "<div class='info'>
<h3>If you see PHP code in browser:</h3>
<ul>
    <li>✓ Make sure you're accessing files through <code>http://localhost/luxstore/</code> not <code>file:///</code></li>
    <li>✓ Start XAMPP/WAMP and ensure Apache is running</li>
    <li>✓ PHP files must be accessed through web server, not opened directly</li>
</ul>

<h3>If categories don't load:</h3>
<ul>
    <li>✓ Press F12 in browser to open Developer Console</li>
    <li>✓ Check Console tab for JavaScript errors</li>
    <li>✓ Check Network tab for failed API requests</li>
    <li>✓ Verify products exist in database</li>
</ul>

<h3>Quick Fix URLs:</h3>
<ul>
    <li><a href='products_api.php?action=get_by_category_name&category=Luxury%20Bags' target='_blank'>Test Products API</a></li>
    <li><a href='api.php?action=get_products' target='_blank'>Test General API</a></li>
</ul>
</div>
</div>";

echo "<div class='section' style='background: #d4af37; color: #000;'>
    <h2>✅ Next Steps</h2>
    <ol>
        <li>Fix any RED errors shown above</li>
        <li>Make sure all files exist</li>
        <li>Access site through: <code>http://localhost/luxstore/index.html</code></li>
        <li>Check browser console (F12) for JavaScript errors</li>
    </ol>
</div>";

echo "</body></html>";
?>