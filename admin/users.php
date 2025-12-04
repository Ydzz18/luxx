<?php
require_once 'auth.php';
requireLogin();

// Admin only
if (!canDo('users', 'view')) {
    showAccessDenied();
}

$pdo = db();
$message = '';
$error = '';

// Get staff users (admin, manager, staff)
$stmt = $pdo->query("SELECT * FROM users WHERE role IN ('admin', 'manager', 'staff') ORDER BY role, username");
$staffUsers = $stmt->fetchAll();

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        
        // Check if username/email exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = 'Username or email already exists!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, role, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $password, $fullname, $email, $role, $is_verified])) {
                $message = 'User created successfully!';
                header('Location: users.php?added=1');
                exit;
            }
        }
    }
    
    if (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        
        // Update password only if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, role = ?, is_verified = ?, password = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $role, $is_verified, $password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, role = ?, is_verified = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $role, $is_verified, $id]);
        }
        header('Location: users.php?updated=1');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete']) && canDo('users', 'delete')) {
    $deleteId = $_GET['delete'];
    // Prevent deleting yourself
    if ($deleteId == $_SESSION['admin_id']) {
        $error = 'You cannot delete your own account!';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('manager', 'staff')");
        $stmt->execute([$deleteId]);
        header('Location: users.php?deleted=1');
        exit;
    }
}

// Handle toggle verify
if (isset($_GET['toggle_verify'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified = NOT is_verified WHERE id = ?");
    $stmt->execute([$_GET['toggle_verify']]);
    header('Location: users.php');
    exit;
}

if (isset($_GET['added'])) $message = 'User added successfully!';
if (isset($_GET['updated'])) $message = 'User updated successfully!';
if (isset($_GET['deleted'])) $message = 'User deleted successfully!';

// Edit mode
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

$pageTitle = 'User Management';
$pageStyles = '
    .users-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
    .user-card { background: #fff; padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .user-avatar { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff; }
    .user-avatar.admin { background: #e74c3c; }
    .user-avatar.manager { background: #3498db; }
    .user-avatar.staff { background: #95a5a6; }
    .user-info { flex: 1; }
    .user-info h4 { margin-bottom: 3px; }
    .user-info p { color: #888; font-size: 13px; margin: 0; }
    .user-actions { display: flex; gap: 8px; }
    .user-actions a { padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 13px; }
    .verified-badge { color: #27ae60; font-size: 12px; }
    .unverified-badge { color: #e74c3c; font-size: 12px; }
    .form-card h3 { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .success-msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    .error-msg { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    @media (max-width: 900px) { .users-grid { grid-template-columns: 1fr; } }
';
include 'includes/header.php';
?>

            <?php if ($message): ?><div class="success-msg"><?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

            <div class="users-grid">
                <div>
                    <h3 style="margin-bottom: 20px;">Staff Accounts (<?= count($staffUsers) ?>)</h3>
                    
                    <?php foreach ($staffUsers as $u): ?>
                    <div class="user-card">
                        <div class="user-avatar <?= $u['role'] ?>">
                            <?= strtoupper(substr($u['fullname'] ?? $u['username'], 0, 2)) ?>
                        </div>
                        <div class="user-info">
                            <h4><?= htmlspecialchars($u['fullname'] ?? $u['username']) ?></h4>
                            <p>@<?= htmlspecialchars($u['username']) ?> • <?= htmlspecialchars($u['email']) ?></p>
                            <p>
                                <?= getRoleBadge($u['role']) ?>
                                <?php if ($u['is_verified']): ?>
                                    <span class="verified-badge">✓ Verified</span>
                                <?php else: ?>
                                    <span class="unverified-badge">✗ Not Verified</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="user-actions">
                            <a href="users.php?edit=<?= $u['id'] ?>" class="btn-edit">Edit</a>
                            <?php if ($u['id'] != $_SESSION['admin_id'] && $u['role'] !== 'admin'): ?>
                            <a href="users.php?delete=<?= $u['id'] ?>" class="btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-card">
                    <h3><?= $editUser ? 'Edit User' : 'Add New User' ?></h3>
                    <form method="POST">
                        <?php if ($editUser): ?>
                        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                        <?php endif; ?>

                        <?php if (!$editUser): ?>
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" required placeholder="e.g. john_doe">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="fullname" required value="<?= htmlspecialchars($editUser['fullname'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Password <?= $editUser ? '(leave blank to keep current)' : '*' ?></label>
                            <input type="password" name="password" <?= $editUser ? '' : 'required' ?> placeholder="<?= $editUser ? '••••••••' : 'Enter password' ?>">
                        </div>

                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" required>
                                <option value="staff" <?= ($editUser['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="manager" <?= ($editUser['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_verified" <?= ($editUser['is_verified'] ?? 0) ? 'checked' : '' ?>>
                                Account Verified (can login)
                            </label>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="<?= $editUser ? 'update_user' : 'add_user' ?>" class="btn-primary">
                                <?= $editUser ? 'Update User' : 'Add User' ?>
                            </button>
                            <?php if ($editUser): ?>
                            <a href="users.php" class="btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>