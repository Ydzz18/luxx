<?php
require_once 'config.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    // Get all products
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active' 
                ORDER BY p.created_at DESC";
        if ($limit) $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get single product
    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get by slug
    public function getBySlug($slug) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.slug = ?"
        );
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    // Get products by category
    public function getByCategory($categoryId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? AND p.status = 'active' 
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    // Get featured products
    public function getFeatured($limit = 8) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.featured = 1 AND p.status = 'active' 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // Search products
    public function search($query) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE (p.name LIKE ? OR p.description LIKE ?) 
             AND p.status = 'active'"
        );
        $search = "%$query%";
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll();
    }
    
    // Create product (admin)
    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, image, featured) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $this->createSlug($data['name']),
            $data['description'],
            $data['price'],
            $data['sale_price'] ?? null,
            $data['stock'],
            $data['image'],
            $data['featured'] ?? 0
        ]);
    }
    
    // Update product (admin)
    public function update($id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE products SET 
             category_id = ?, name = ?, description = ?, 
             price = ?, sale_price = ?, stock = ?, 
             image = ?, featured = ?, status = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['category_id'], $data['name'], $data['description'],
            $data['price'], $data['sale_price'] ?? null, $data['stock'],
            $data['image'], $data['featured'] ?? 0, $data['status'], $id
        ]);
    }
    
    // Delete product (admin)
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Update stock
    public function updateStock($id, $quantity) {
        $stmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }
    
    // Create URL slug
    private function createSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug . '-' . substr(uniqid(), -4);
    }
}

// Category Class
class Category {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
}
?>