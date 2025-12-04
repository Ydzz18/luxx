<?php
/**
 * Simple PHP Test File
 * Visit: http://localhost/luxstore/test.php
 * 
 * If you see "PHP is working!" your setup is correct
 * If you see this code, PHP is NOT processing files
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 24px;
            margin: 20px 0;
        }
        .info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info h3 {
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: bold;
            width: 200px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="success">
        ✅ PHP IS WORKING! 🎉
    </div>

    <div class="info">
        <h3>System Information</h3>
        <table>
            <tr>
                <td>PHP Version</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
            </tr>
            <tr>
                <td>Current File</td>
                <td><?php echo __FILE__; ?></td>
            </tr>
            <tr>
                <td>Access URL</td>
                <td><?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></td>
            </tr>
        </table>
    </div>

    <div class="info">
        <h3>Database Test</h3>
        <?php
        try {
            $conn = new PDO("mysql:host=localhost;dbname=luxstore_db", "root", "");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p style='color: green; font-weight: bold;'>✓ Database Connection: SUCCESS</p>";
            
            // Check products
            $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
            $count = $stmt->fetch()['count'];
            echo "<p style='color: green;'>✓ Products in database: <strong>$count</strong></p>";
            
            if ($count == 0) {
                echo "<p style='color: red;'>⚠️ No products found! Import luxstore_db.sql</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red; font-weight: bold;'>✗ Database Connection: FAILED</p>";
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
            echo "<p style='color: orange;'>Make sure:<br>
                  1. MySQL is running in XAMPP<br>
                  2. Database 'luxstore_db' exists<br>
                  3. luxstore_db.sql has been imported</p>";
        }
        ?>
    </div>

    <div class="info">
        <h3>File Check</h3>
        <?php
        $required_files = [
            'config.php',
            'api.php',
            'products_api.php',
            'Cart.php',
            'Product.php',
            'User.php',
            'scripts.js',
            'index.html'
        ];
        
        echo "<table>";
        foreach ($required_files as $file) {
            $exists = file_exists($file);
            $status = $exists ? '<span style="color: green;">✓ EXISTS</span>' : '<span style="color: red;">✗ MISSING</span>';
            echo "<tr><td>$file</td><td>$status</td></tr>";
        }
        echo "</table>";
        ?>
    </div>

    <div class="info">
        <h3>Next Steps</h3>
        <ol style="line-height: 2;">
            <li>If you can read this, PHP is working! ✓</li>
            <li>Check that database connection is green above</li>
            <li>Make sure all files show "EXISTS"</li>
            <li>Visit: <a href="index.html">index.html</a></li>
            <li>Run: <a href="diagnostic.php">diagnostic.php</a> for full check</li>
        </ol>
    </div>

    <div class="info" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>⚠️ If You See PHP Code Instead:</h3>
        <p><strong>Problem:</strong> PHP files are not being processed by the server.</p>
        <p><strong>Solution:</strong></p>
        <ul>
            <li>✓ Make sure Apache is RUNNING in XAMPP Control Panel</li>
            <li>✓ Access via <code>http://localhost/luxstore/test.php</code></li>
            <li>✓ DO NOT open file directly or use file:/// protocol</li>
            <li>✓ Files must be in <code>C:\xampp\htdocs\luxstore\</code></li>
        </ul>
    </div>

</body>
</html>