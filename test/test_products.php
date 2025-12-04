<?php
/**
 * Test page to check products and categories
 * DELETE this file after testing!
 */

require_once 'config.php';
require_once 'Product.php';

$pdo = db();
$product = new Product();
$category = new Category();

echo "<h1>LuxStore - Products & Categories Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: #fff; }
    h1, h2 { color: #d4af37; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #444; padding: 10px; text-align: left; }
    th { background: #333; color: #d4af37; }
    tr:nth-child(even) { background: #222; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .warning { color: #f39c12; }
    img { max-width: 80px; border-radius: 6px; }
</style>";

// Check Categories
echo "<h2>📁 Categories</h2>";
$categories = $category->getAll();

if (empty($categories)) {
    echo "<p class='error'>❌ No categories found! You need to add categories first.</p>";
    echo "<p>Go to Admin → Categories to add 'Luxury Bags' and 'Luxury Watches'</p>";
} else {
    echo "<table>
        <tr><th>ID</th><th>Name</th><th>Slug</th><th>Image</th><th>Products Count</th></tr>";
    foreach ($categories as $cat) {
        $products = $product->getByCategory($cat['id']);
        $count = count($products);
        $countClass = $count > 0 ? 'success' : 'warning';
        echo "<tr>
            <td>{$cat['id']}</td>
            <td><strong>{$cat['name']}</strong></td>
            <td>{$cat['slug']}</td>
            <td><img src='{$cat['image']}' alt=''></td>
            <td class='{$countClass}'>{$count} products</td>
        </tr>";
    }
    echo "</table>";
}

// Check Products
echo "<h2>🛍️ All Products</h2>";
$allProducts = $product->getAll();

if (empty($allProducts)) {
    echo "<p class='error'>❌ No products found! You need to add products first.</p>";
    echo "<p>Go to Admin → Products to add products.</p>";
} else {
    echo "<p class='success'>✅ Found " . count($allProducts) . " products</p>";
    echo "<table>
        <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
    foreach ($allProducts as $p) {
        $statusClass = $p['status'] == 'active' ? 'success' : 'error';
        echo "<tr>
            <td>{$p['id']}</td>
            <td><img src='{$p['image']}' alt=''></td>
            <td><strong>{$p['name']}</strong></td>
            <td>{$p['category_name']}</td>
            <td>₱" . number_format($p['price'], 2) . "</td>
            <td>{$p['stock']}</td>
            <td class='{$statusClass}'>{$p['status']}</td>
        </tr>";
    }
    echo "</table>";
}

// Test category matching
echo "<h2>🔍 Category Matching Test</h2>";
$testCategories = ['Luxury Bags', 'Luxury Watches'];

foreach ($testCategories as $testCat) {
    echo "<h3>Testing: '{$testCat}'</h3>";
    
    // Try by slug
    $slug = strtolower(str_replace(' ', '-', $testCat));
    $catBySlug = $category->getBySlug($slug);
    
    if ($catBySlug) {
        echo "<p class='success'>✅ Found by slug '{$slug}' → ID: {$catBySlug['id']}</p>";
        $products = $product->getByCategory($catBySlug['id']);
        echo "<p>Products in this category: " . count($products) . "</p>";
    } else {
        echo "<p class='warning'>⚠️ Not found by slug '{$slug}'</p>";
        
        // Try by name match
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE name LIKE ?");
        $stmt->execute(['%' . $testCat . '%']);
        $catByName = $stmt->fetch();
        
        if ($catByName) {
            echo "<p class='success'>✅ Found by name match → ID: {$catByName['id']}, Slug: {$catByName['slug']}</p>";
        } else {
            echo "<p class='error'>❌ Category '{$testCat}' not found! Create it in Admin → Categories</p>";
        }
    }
}

// API Test
echo "<h2>🔗 API Test</h2>";
echo "<p>Test the products API directly:</p>";
echo "<ul>";
echo "<li><a href='products_api.php?action=get_categories' target='_blank'>Get All Categories</a></li>";
echo "<li><a href='products_api.php?action=get_all' target='_blank'>Get All Products</a></li>";
echo "<li><a href='products_api.php?action=get_by_category_name&category=Luxury%20Bags' target='_blank'>Get Luxury Bags</a></li>";
echo "<li><a href='products_api.php?action=get_by_category_name&category=Luxury%20Watches' target='_blank'>Get Luxury Watches</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p style='color: #888; margin-top: 30px;'>⚠️ <strong>Delete this file after testing!</strong></p>";
echo "<p><a href='index.html' style='color: #d4af37;'>← Back to Store</a> | <a href='admin/index.php' style='color: #d4af37;'>Admin Panel →</a></p>";
?>