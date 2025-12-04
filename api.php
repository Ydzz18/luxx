<?php
/**
 * LuxStore API Endpoints
 * Handle all AJAX requests for cart, products, orders, etc.
 */

// Start output buffering to catch any accidental output
ob_start();

// Ensure NO output before this point
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');

require_once 'config.php';
require_once 'Product.php';
require_once 'CartClass.php';
require_once 'User.php';
require_once 'Order.php';

// Clear any accidental output from includes
ob_clean();

// Set JSON header
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        
        // ============ CART OPERATIONS ============
        case 'add_to_cart':
            $cart = new Cart();
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($cart->add($productId, $quantity)) {
                $response = [
                    'success' => true,
                    'message' => 'Added to cart!',
                    'cart_count' => $cart->getCount(),
                    'cart_total' => formatPrice($cart->getTotal())
                ];
            }
            break;
            
        case 'update_cart':
            $cart = new Cart();
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            if ($cart->update($productId, $quantity)) {
                $response = [
                    'success' => true,
                    'cart_count' => $cart->getCount(),
                    'cart_total' => formatPrice($cart->getTotal())
                ];
            }
            break;
            
        case 'remove_from_cart':
            $cart = new Cart();
            $productId = (int)$_POST['product_id'];
            
            if ($cart->remove($productId)) {
                $response = [
                    'success' => true,
                    'cart_count' => $cart->getCount(),
                    'cart_total' => formatPrice($cart->getTotal())
                ];
            }
            break;
            
        case 'get_cart':
            $cart = new Cart();
            $items = $cart->getItems();
            
            $response = [
                'success' => true,
                'items' => $items,
                'count' => $cart->getCount(),
                'subtotal' => $cart->getTotal(),
                'subtotal_formatted' => formatPrice($cart->getTotal())
            ];
            break;
            
        case 'clear_cart':
            $cart = new Cart();
            $cart->clear();
            $response = ['success' => true];
            break;
        
        // ============ PRODUCT OPERATIONS ============
        case 'get_products':
            $product = new Product();
            $categoryId = $_GET['category'] ?? null;
            
            if ($categoryId) {
                $products = $product->getByCategory($categoryId);
            } else {
                $products = $product->getAll();
            }
            
            $response = ['success' => true, 'products' => $products];
            break;
            
        case 'get_product':
            $product = new Product();
            $id = (int)$_GET['id'];
            $data = $product->getById($id);
            
            $response = $data 
                ? ['success' => true, 'product' => $data]
                : ['success' => false, 'message' => 'Product not found'];
            break;
            
        case 'search_products':
            $product = new Product();
            $query = sanitize($_GET['q'] ?? '');
            $results = $product->search($query);
            
            $response = ['success' => true, 'products' => $results];
            break;
            
        case 'get_featured':
            $product = new Product();
            $response = ['success' => true, 'products' => $product->getFeatured()];
            break;
        
        // ============ USER OPERATIONS ============
        case 'register':
            $user = new User();
            $result = $user->register($_POST);
            $response = $result;
            break;
            
        case 'login':
            $user = new User();
            $result = $user->login($_POST['email'], $_POST['password']);
            $response = $result;
            break;
            
        case 'logout':
            $user = new User();
            $user->logout();
            $response = ['success' => true];
            break;
            
        case 'get_profile':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Not logged in'];
                break;
            }
            $user = new User();
            $profile = $user->getById($_SESSION['user_id']);
            $response = ['success' => true, 'user' => $profile];
            break;
            
        case 'update_profile':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Not logged in'];
                break;
            }
            $user = new User();
            $user->updateProfile($_SESSION['user_id'], $_POST);
            $response = ['success' => true, 'message' => 'Profile updated'];
            break;
        
        // ============ ORDER OPERATIONS ============
        case 'place_order':
            try {
                $cart = new Cart();
                $items = $cart->getItems();
                
                // Check if cart is empty
                if (empty($items)) {
                    $response = ['success' => false, 'message' => 'Your cart is empty'];
                    break;
                }
                
                // Validate required fields
                $required = ['name', 'phone', 'email', 'address', 'city', 'postal', 'payment_method'];
                $missingFields = [];
                
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    $response = [
                        'success' => false, 
                        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                    ];
                    break;
                }
                
                // Additional validation for bank transfer
                if ($_POST['payment_method'] === 'bank') {
                    $bankRequired = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_reference'];
                    $missingBankFields = [];
                    
                    foreach ($bankRequired as $field) {
                        if (empty($_POST[$field])) {
                            $missingBankFields[] = $field;
                        }
                    }
                    
                    if (!empty($missingBankFields)) {
                        $response = [
                            'success' => false,
                            'message' => 'Missing bank transfer information: ' . implode(', ', $missingBankFields)
                        ];
                        break;
                    }
                }
                
                // Create order
                $order = new Order();
                $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
                
                $result = $order->create($items, $_POST, $userId);
                
                // IMPORTANT: Only clear cart if order was successful
                if ($result['success']) {
                    $cart->clear();
                    $response = $result;
                } else {
                    // Order failed - DO NOT clear cart
                    $response = $result;
                }
                
            } catch (Exception $e) {
                // Log the error
                error_log("Order placement error: " . $e->getMessage());
                
                // Return error but keep cart intact
                $response = [
                    'success' => false,
                    'message' => 'An error occurred while placing your order. Please try again.',
                    'error_detail' => $e->getMessage()
                ];
            }
            break;
            
        case 'get_order':
            $order = new Order();
            $orderNumber = sanitize($_GET['order_number']);
            $orderData = $order->getByOrderNumber($orderNumber);
            
            if ($orderData) {
                $orderData['items'] = $order->getItems($orderData['id']);
                $response = ['success' => true, 'order' => $orderData];
            } else {
                $response = ['success' => false, 'message' => 'Order not found'];
            }
            break;
            
        case 'my_orders':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Not logged in'];
                break;
            }
            $user = new User();
            $orders = $user->getOrders($_SESSION['user_id']);
            $response = ['success' => true, 'orders' => $orders];
            break;
        
        // ============ CATEGORIES ============
        case 'get_categories':
            $category = new Category();
            $response = ['success' => true, 'categories' => $category->getAll()];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    $response = [
        'success' => false,
        'message' => 'A server error occurred. Please try again.',
        'error' => $e->getMessage()
    ];
}

// Clear output buffer and send clean JSON
ob_clean();
echo json_encode($response);
ob_end_flush();