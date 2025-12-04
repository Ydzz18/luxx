<?php
/**
 * Admin Authentication & Role-Based Access Control - FIXED
 * Include this at the top of every admin page
 */

require_once __DIR__ . '/../config.php';

// Check if logged in
function requireLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Get current user role
function getRole() {
    return $_SESSION['admin_role'] ?? 'staff';
}

// Check if user has specific role
function hasRole($roles) {
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array(getRole(), $roles);
}

// Check if user is admin
function isAdminRole() {
    return getRole() === 'admin';
}

// Check if user is manager or higher
function isManagerOrAbove() {
    return in_array(getRole(), ['admin', 'manager']);
}

// Require specific role(s) - redirect if not authorized
function requireRole($roles, $redirectTo = 'index.php') {
    requireLogin();
    if (!hasRole($roles)) {
        $_SESSION['error_msg'] = 'You do not have permission to access that page.';
        header("Location: $redirectTo");
        exit;
    }
}

// Permission definitions
$permissions = [
    'admin' => [
        'dashboard' => true,
        'orders' => ['view', 'edit', 'delete'],
        'products' => ['view', 'add', 'edit', 'delete'],
        'categories' => ['view', 'add', 'edit', 'delete'],
        'customers' => ['view', 'edit', 'delete'],
        'feedback' => ['view', 'edit', 'delete'],
        'settings' => ['view', 'edit'],
        'users' => ['view', 'add', 'edit', 'delete']
    ],
    'manager' => [
        'dashboard' => true,
        'orders' => ['view', 'edit'],
        'products' => ['view', 'add', 'edit'],
        'categories' => ['view', 'add', 'edit'],
        'customers' => ['view'],
        'feedback' => ['view', 'edit'],
        'settings' => false,
        'users' => false
    ],
    'staff' => [
        'dashboard' => true,
        'orders' => ['view', 'edit'],
        'products' => ['view'],
        'categories' => ['view'],
        'customers' => ['view'],
        'feedback' => ['view'],
        'settings' => false,
        'users' => false
    ]
];

// Check if user can perform action on resource
function canDo($resource, $action = 'view') {
    global $permissions;
    $role = getRole();
    
    if (!isset($permissions[$role][$resource])) {
        return false;
    }
    
    $perm = $permissions[$role][$resource];
    
    if ($perm === true) return true;
    if ($perm === false) return false;
    if (is_array($perm)) return in_array($action, $perm);
    
    return false;
}

// Display error if permission denied
function showAccessDenied() {
    echo '<div style="text-align: center; padding: 60px; color: #888;">
            <h2>🚫 Access Denied</h2>
            <p>You do not have permission to access this feature.</p>
            <a href="index.php" style="color: #d4af37;">← Back to Dashboard</a>
          </div>';
    exit;
}

// Get role badge HTML
function getRoleBadge($role) {
    $colors = [
        'admin' => 'background: #e74c3c; color: white;',
        'manager' => 'background: #3498db; color: white;',
        'staff' => 'background: #95a5a6; color: white;'
    ];
    $style = $colors[$role] ?? $colors['staff'];
    return "<span style='padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: uppercase; $style'>" . ucfirst($role) . "</span>";
}