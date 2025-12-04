<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart_id']) || !isset($input['change'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$cart_id = (int)$input['cart_id'];
$change = (int)$input['change'];
$user_id = $_SESSION['user_id'];

try {
    $db = db();
    
    $stmt = $db->prepare("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    $newQuantity = $item['quantity'] + $change;
    
    if ($newQuantity < 1) {
        $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$cart_id]);
    } elseif ($newQuantity > $item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Cannot exceed available stock']);
        exit;
    } else {
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cart_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
    
} catch (PDOException $e) {
    error_log("Cart update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
