<?php
require_once 'config.php';

$db = db();

echo "=== CATEGORIES ===\n";
$stmt = $db->query("SELECT * FROM categories");
$cats = $stmt->fetchAll();
echo "Count: " . count($cats) . "\n";
foreach ($cats as $cat) {
    echo "- ID: {$cat['id']}, Name: {$cat['name']}\n";
}

echo "\n=== PRODUCTS ===\n";
$stmt = $db->query("SELECT * FROM products LIMIT 5");
$prods = $stmt->fetchAll();
echo "Count: " . count($prods) . "\n";
foreach ($prods as $prod) {
    echo "- ID: {$prod['id']}, Name: {$prod['name']}, Category ID: {$prod['category_id']}, Status: {$prod['status']}\n";
}

echo "\n=== PRODUCTS WITH JOINS ===\n";
$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' LIMIT 5");
$prods2 = $stmt->fetchAll();
echo "Count: " . count($prods2) . "\n";
foreach ($prods2 as $prod) {
    echo "- Name: {$prod['name']}, Category: {$prod['category_name']}\n";
}
?>
