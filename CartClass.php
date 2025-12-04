<?php
require_once 'config.php';

class Cart {
    private $db;
    private $user_id;
    
    public function __construct() {
        $this->db = db();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    public function add($product_id, $quantity = 1) {
        if (!$this->user_id) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$this->user_id, $product_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $this->db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
                return $stmt->execute([$quantity, $existing['id']]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                return $stmt->execute([$this->user_id, $product_id, $quantity]);
            }
        } catch (PDOException $e) {
            error_log("Cart add error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($product_id, $quantity) {
        if (!$this->user_id || $quantity < 1) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$quantity, $this->user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function remove($product_id) {
        if (!$this->user_id) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$this->user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart remove error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getItems() {
        if (!$this->user_id) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.name, p.price, p.image, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Cart get items error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCount() {
        if (!$this->user_id) {
            return 0;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Cart get count error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotal() {
        if (!$this->user_id) {
            return 0;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(p.price * c.quantity) as total 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$this->user_id]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Cart get total error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function clear() {
        if (!$this->user_id) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
            return $stmt->execute([$this->user_id]);
        } catch (PDOException $e) {
            error_log("Cart clear error: " . $e->getMessage());
            return false;
        }
    }
}
?>
