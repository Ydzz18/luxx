<?php
/**
 * Products API - Fetch products by category name for frontend
 */

require_once 'config.php';
require_once 'Product.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];
$currency = getSetting('currency', '₱');

try {
    switch ($action) {
        case 'get_by_category_name':
            $categoryName = sanitize($_GET['category'] ?? '');
            
            if (empty($categoryName)) {
                $response = ['success' => false, 'message' => 'Category name required'];
                break;
            }
            
            // Get category by name
            $db = db();
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$categoryName]);
            $category = $stmt->fetch();
            
            if (!$category) {
                $response = ['success' => false, 'message' => 'Category not found'];
                break;
            }
            
            // Get products in this category
            $product = new Product();
            $products = $product->getByCategory($category['id']);
            
            $response = [
                'success' => true,
                'products' => $products,
                'currency' => $currency,
                'count' => count($products)
            ];
            break;
            
        case 'get_all':
            $product = new Product();
            $products = $product->getAll();
            
            $response = [
                'success' => true,
                'products' => $products,
                'currency' => $currency
            ];
            break;
            
        case 'get_featured':
            $product = new Product();
            $products = $product->getFeatured();
            
            $response = [
                'success' => true,
                'products' => $products,
                'currency' => $currency
            ];
            break;
            
        case 'search':
            $query = sanitize($_GET['q'] ?? '');
            
            if (empty($query)) {
                $response = ['success' => false, 'message' => 'Search query required'];
                break;
            }
            
            $product = new Product();
            $products = $product->search($query);
            
            $response = [
                'success' => true,
                'products' => $products,
                'currency' => $currency,
                'query' => $query
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);