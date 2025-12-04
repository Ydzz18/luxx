<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$cart_id = (int)$input['cart_id'];
$user_id = $_SESSION['user_id'];

try {
    $db = db();
    
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    
} catch (PDOException $e) {
    error_log("Cart remove error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
