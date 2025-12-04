<?php
require_once 'config.php';
require_once 'Product.php';
require_once 'settings.php';
require_once 'mailer.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    // Create new order
    public function create($cartItems, $shippingData, $userId = null) {
        // Validate cart items first
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }
        
        // Validate required shipping data
        $requiredFields = ['name', 'email', 'phone', 'address', 'city', 'postal', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (empty($shippingData[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Validate bank transfer fields if selected
        if ($shippingData['payment_method'] === 'bank') {
            $bankFields = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_reference'];
            foreach ($bankFields as $field) {
                if (empty($shippingData[$field])) {
                    return ['success' => false, 'message' => "Missing bank transfer information: $field"];
                }
            }
        }
        
        $this->db->beginTransaction();
        
        try {
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $price = $item['sale_price'] ?? $item['price'];
                $subtotal += $price * $item['quantity'];
            }
            
            // Use settings for shipping and tax
            $shippingFee = Settings::calculateShipping($subtotal);
            $taxAmount = Settings::calculateTax($subtotal);
            $total = $subtotal + $shippingFee + $taxAmount;
            $orderNumber = generateOrderNumber();
            
            // Prepare bank transfer notes
            $bankTransferInfo = '';
            if ($shippingData['payment_method'] === 'bank') {
                $bankTransferInfo = sprintf(
                    "Bank: %s | Account Name: %s | Account Number: %s | Reference: %s",
                    $shippingData['bank_name'],
                    $shippingData['bank_account_name'],
                    $shippingData['bank_account_number'],
                    $shippingData['bank_reference']
                );
            }
            
            // Combine notes
            $orderNotes = trim(($shippingData['notes'] ?? '') . "\n\n" . $bankTransferInfo);
            
            // Insert order
            $stmt = $this->db->prepare(
                "INSERT INTO orders (
                    user_id, order_number, subtotal, shipping_fee, total,
                    shipping_name, shipping_email, shipping_phone,
                    shipping_address, shipping_city, shipping_postal,
                    payment_method, notes, status, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)"
            );
            
            // Set payment status based on method
            $paymentStatus = ($shippingData['payment_method'] === 'cod') ? 'pending' : 'awaiting';
            
            $stmt->execute([
                $userId,
                $orderNumber,
                $subtotal,
                $shippingFee,
                $total,
                sanitize($shippingData['name']),
                sanitize($shippingData['email']),
                sanitize($shippingData['phone']),
                sanitize($shippingData['address']),
                sanitize($shippingData['city']),
                sanitize($shippingData['postal']),
                sanitize($shippingData['payment_method']),
                sanitize($orderNotes),
                $paymentStatus
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }
            
            // Insert order items & update stock
            $product = new Product();
            foreach ($cartItems as $item) {
                $price = $item['sale_price'] ?? $item['price'];
                $itemTotal = $price * $item['quantity'];
                
                // Check stock availability
                $productData = $product->getById($item['product_id']);
                if (!$productData || $productData['stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$item['name']}");
                }
                
                $stmt = $this->db->prepare(
                    "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $price,
                    $item['quantity'],
                    $itemTotal
                ]);
                
                // Update stock
                $product->updateStock($item['product_id'], $item['quantity']);
            }
            
            $this->db->commit();
            
            // Send email notifications (don't fail order if email fails)
            try {
                $mailer = new Mailer();
                $orderData = $this->getById($orderId);
                $orderItems = $this->getItems($orderId);
                
                // Send confirmation to customer
                $mailer->sendOrderConfirmation($orderData, $orderItems);
                
                // Send notification to admin
                $mailer->sendAdminOrderNotification($orderData, $orderItems);
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $total,
                'message' => 'Order placed successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order creation failed: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'error_detail' => $e->getMessage()
            ];
        }
    }
    
    // Get order by ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get order by order number
    public function getByOrderNumber($orderNumber) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch();
    }
    
    // Get order items
    public function getItems($orderId) {
        $stmt = $this->db->prepare(
            "SELECT oi.*, p.image 
             FROM order_items oi 
             LEFT JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    // Get all orders (admin)
    public function getAll($status = null, $limit = 50, $offset = 0) {
        $sql = "SELECT o.*, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id";
        if ($status) $sql .= " WHERE o.status = :status";
        $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        if ($status) $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Update order status (admin)
    public function updateStatus($orderId, $status, $sendEmail = true) {
        $stmt = $this->db->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );
        $result = $stmt->execute([$status, $orderId]);
        
        // Send status update email
        if ($result && $sendEmail) {
            try {
                $order = $this->getById($orderId);
                $mailer = new Mailer();
                $mailer->sendStatusUpdate($order, $status);
            } catch (Exception $e) {
                error_log("Status update email failed: " . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    // Update payment status (admin)
    public function updatePaymentStatus($orderId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE orders SET payment_status = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $orderId]);
    }
    
    // Get order statistics (admin dashboard) - FIXED WITH avg_order_value
    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(AVG(total), 0) as avg_order_value
            FROM orders
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>