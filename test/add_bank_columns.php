<?php
/**
 * Database Migration: Add Bank Transfer Columns
 * Run once: http://localhost/add_bank_columns.php
 */

require_once 'config.php';

$db = db();

echo "<h2>LuxStore - Bank Transfer Columns Migration</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    h2 { color: #333; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .info { color: #3498db; }
    ul { background: white; padding: 20px; border-radius: 8px; list-style: none; }
    li { padding: 8px 0; }
</style>";

try {
    // Check current table structure
    $stmt = $db->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Adding Bank Transfer Columns...</h3><ul>";
    
    // Add bank transfer columns
    $columnsToAdd = [
        'bank_name' => "ALTER TABLE orders ADD COLUMN bank_name VARCHAR(100) AFTER notes",
        'bank_account_name' => "ALTER TABLE orders ADD COLUMN bank_account_name VARCHAR(255) AFTER bank_name",
        'bank_account_number' => "ALTER TABLE orders ADD COLUMN bank_account_number VARCHAR(100) AFTER bank_account_name",
        'bank_reference' => "ALTER TABLE orders ADD COLUMN bank_reference VARCHAR(100) AFTER bank_account_number"
    ];
    
    foreach ($columnsToAdd as $columnName => $sql) {
        if (!in_array($columnName, $columns)) {
            try {
                $db->exec($sql);
                echo "<li class='success'>✓ Added column: <strong>$columnName</strong></li>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<li class='info'>ℹ Column already exists: <strong>$columnName</strong></li>";
                } else {
                    echo "<li class='error'>✗ Error adding $columnName: " . $e->getMessage() . "</li>";
                }
            }
        } else {
            echo "<li class='info'>ℹ Column already exists: <strong>$columnName</strong></li>";
        }
    }
    
    echo "</ul>";
    
    // Also ensure payment_status column exists
    echo "<h3>Checking Payment Status Column...</h3><ul>";
    
    if (!in_array('payment_status', $columns)) {
        try {
            $db->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending' AFTER status");
            echo "<li class='success'>✓ Added column: <strong>payment_status</strong></li>";
        } catch (PDOException $e) {
            echo "<li class='error'>✗ Error: " . $e->getMessage() . "</li>";
        }
    } else {
        echo "<li class='info'>ℹ Column already exists: <strong>payment_status</strong></li>";
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
    
    echo "<h3 class='success'>✓ Migration completed successfully!</h3>";
    echo "<p><a href='checkout.php' style='display: inline-block; padding: 10px 20px; background: #d4af37; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold;'>Test Checkout</a></p>";
    
} catch (Exception $e) {
    echo "<h3 class='error'>Migration Failed!</h3>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
}
?>