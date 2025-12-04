<?php
require_once 'config.php';

class Feedback {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    // Submit new feedback
    public function submit($data) {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        $stmt = $this->db->prepare(
            "INSERT INTO feedback (user_id, name, email, subject, message) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        try {
            $stmt->execute([
                $userId,
                sanitize($data['name'] ?? ''),
                sanitize($data['email'] ?? ''),
                sanitize($data['subject'] ?? 'General Feedback'),
                sanitize($data['message'])
            ]);
            return ['success' => true, 'message' => 'Thank you for your feedback!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to submit feedback'];
        }
    }
    
    // Get all feedback (admin)
    public function getAll($status = null, $limit = 50) {
        $sql = "SELECT f.*, u.first_name, u.last_name 
                FROM feedback f 
                LEFT JOIN users u ON f.user_id = u.id";
        if ($status) {
            $sql .= " WHERE f.status = ?";
        }
        $sql .= " ORDER BY f.created_at DESC LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        if ($status) {
            $stmt->execute([$status, $limit]);
        } else {
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    }
    
    // Get single feedback
    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT f.*, u.first_name, u.last_name 
             FROM feedback f 
             LEFT JOIN users u ON f.user_id = u.id 
             WHERE f.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Update status (admin)
    public function updateStatus($id, $status, $notes = null) {
        $stmt = $this->db->prepare(
            "UPDATE feedback SET status = ?, admin_notes = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $notes, $id]);
    }
    
    // Delete feedback (admin)
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM feedback WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Get counts by status (for admin dashboard)
    public function getCounts() {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) as count FROM feedback GROUP BY status"
        );
        $results = $stmt->fetchAll();
        $counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'resolved' => 0, 'total' => 0];
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
            $counts['total'] += $row['count'];
        }
        return $counts;
    }
}
?>