<?php
require_once 'config.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        try {
            // Check if email exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Generate username from email (required field in database)
            $username = explode('@', $data['email'])[0] . '_' . substr(uniqid(), -4);
            
            // Create fullname
            $fullname = trim($data['first_name'] . ' ' . $data['last_name']);
            
            // Insert user with all required fields
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, password, fullname, first_name, last_name, email, phone, role, is_verified, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 0, NOW())"
            );
            
            $stmt->execute([
                $username,
                $hashedPassword,
                $fullname,
                sanitize($data['first_name']),
                sanitize($data['last_name']),
                sanitize($data['email']),
                sanitize($data['phone'] ?? '')
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Send welcome email if enabled and mailer exists
            if (isWelcomeEmailsEnabled() && function_exists('sendWelcomeEmail')) {
                try {
                    sendWelcomeEmail($data['email'], $data['first_name']);
                } catch (Exception $e) {
                    error_log("Welcome email failed: " . $e->getMessage());
                }
            }
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            // Validate inputs
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            // Get user by email
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([sanitize($email)]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Set session variables based on role
            if (in_array($user['role'], ['admin', 'manager', 'staff'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['fullname'] ?: ($user['first_name'] . ' ' . $user['last_name']);
                $_SESSION['admin_role'] = $user['role'];
            }
            
            // Always set user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'] ?: ($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Merge cart items from session to database (if Cart class exists)
            if (class_exists('Cart')) {
                try {
                    $cart = new Cart();
                    if (method_exists($cart, 'mergeSessionToDb')) {
                        $cart->mergeSessionToDb($user['id']);
                    }
                } catch (Exception $e) {
                    error_log("Cart merge failed: " . $e->getMessage());
                }
            }
            
            return ['success' => true, 'user' => $user];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear all session data
        session_unset();
        session_destroy();
        session_start();
        return true;
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, fullname, first_name, last_name, email, phone, 
                        address, city, postal_code, role, is_verified, created_at 
                 FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($id, $data) {
        try {
            // Update fullname when first/last name changes
            $fullname = trim($data['first_name'] . ' ' . $data['last_name']);
            
            $stmt = $this->db->prepare(
                "UPDATE users SET 
                 first_name = ?, last_name = ?, fullname = ?, phone = ?, 
                 address = ?, city = ?, postal_code = ?, updated_at = NOW() 
                 WHERE id = ?"
            );
            
            $result = $stmt->execute([
                sanitize($data['first_name']),
                sanitize($data['last_name']),
                $fullname,
                sanitize($data['phone']),
                sanitize($data['address']),
                sanitize($data['city']),
                sanitize($data['postal_code']),
                $id
            ]);
            
            // Update session name if changed
            if ($result) {
                $_SESSION['user_name'] = $fullname;
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($id, $currentPass, $newPass) {
        try {
            // Get current password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPass, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed, $id]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([sanitize($email)]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user orders
     */
    public function getOrders($userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get orders error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order by ID (with security check)
     */
    public function getOrderById($orderId, $userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1"
            );
            $stmt->execute([$orderId, $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get order error: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Admin Authentication Class
 */
class Admin {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Admin login - checks users table for admin/manager/staff roles
     */
    public function login($username, $password) {
        try {
            // Check in users table for admin roles
            $stmt = $this->db->prepare(
                "SELECT * FROM users 
                 WHERE (username = ? OR email = ?) 
                 AND role IN ('admin', 'manager', 'staff')
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['fullname'] ?: $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_email'] = $admin['email'];
                
                // Regenerate session ID
                session_regenerate_id(true);
                
                return ['success' => true, 'role' => $admin['role']];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * Admin logout
     */
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        return true;
    }
    
    /**
     * Check if user has admin privileges
     */
    public function hasRole($role) {
        $userRole = $_SESSION['admin_role'] ?? '';
        
        $hierarchy = [
            'admin' => 3,
            'manager' => 2,
            'staff' => 1
        ];
        
        return isset($hierarchy[$userRole]) && 
               isset($hierarchy[$role]) && 
               $hierarchy[$userRole] >= $hierarchy[$role];
    }
}