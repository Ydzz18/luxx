<?php
/**
 * Database Migration: Add Payment Method and Shipping Columns to Orders Table
 * Run this file once in your browser: http://localhost/add_payment_columns.php
 */

require_once 'config.php';

$db = db();

echo "<h2>LuxStore Database Migration</h2>";
echo "<p>Adding payment method and shipping columns to orders table...</p>";

try {
    // Check current table structure
    $stmt = $db->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Current Columns:</h3><ul>";
    foreach ($columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    // Add missing columns
    $columnsToAdd = [
        'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'cod' AFTER total",
        'shipping_name' => "ALTER TABLE orders ADD COLUMN shipping_name VARCHAR(255) AFTER payment_method",
        'shipping_email' => "ALTER TABLE orders ADD COLUMN shipping_email VARCHAR(255) AFTER shipping_name",
        'shipping_phone' => "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(50) AFTER shipping_email",
        'shipping_address' => "ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER shipping_phone",
        'shipping_city' => "ALTER TABLE orders ADD COLUMN shipping_city VARCHAR(100) AFTER shipping_address",
        'shipping_postal' => "ALTER TABLE orders ADD COLUMN shipping_postal VARCHAR(20) AFTER shipping_city",
        'notes' => "ALTER TABLE orders ADD COLUMN notes TEXT AFTER shipping_postal",
    ];
    
    echo "<h3>Migration Results:</h3><ul>";
    
    foreach ($columnsToAdd as $columnName => $sql) {
        if (!in_array($columnName, $columns)) {
            try {
                $db->exec($sql);
                echo "<li style='color: green;'>✓ Added column: <strong>$columnName</strong></li>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<li style='color: orange;'>⚠ Column already exists: <strong>$columnName</strong></li>";
                } else {
                    echo "<li style='color: red;'>✗ Error adding $columnName: " . $e->getMessage() . "</li>";
                }
            }
        } else {
            echo "<li style='color: blue;'>ℹ Column already exists: <strong>$columnName</strong></li>";
        }
    }
    
    echo "</ul>";
    
    // Verify final structure
    $stmt = $db->query("DESCRIBE orders");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Final Table Structure:</h3><ul>";
    foreach ($finalColumns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    echo "<h3 style='color: green;'>✓ Migration completed successfully!</h3>";
    echo "<p><a href='checkout.php'>Go to Checkout Page</a> | <a href='index.html'>Go to Home</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Migration Failed!</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    h2 { color: #333; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
    h3 { color: #555; margin-top: 20px; }
    ul { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    li { padding: 5px 0; }
    a { display: inline-block; margin: 10px 10px 0 0; padding: 10px 20px; background: #d4af37; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; }
    a:hover { background: #b8860b; }
</style>