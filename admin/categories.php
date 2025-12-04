<?php
require_once 'auth.php';
requireLogin();
require_once '../Product.php';

// Check permission
if (!canDo('categories', 'view')) {
    showAccessDenied();
}

$db = db();
$message = '';
$error = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
    $description = sanitize($_POST['description']);
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = 'uploads/categories/' . $slug . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image);
    }
    
    $stmt = $db->prepare("INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $slug, $description, $image])) {
        $message = 'Category added successfully!';
    } else {
        $error = 'Failed to add category';
    }
}

// Update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // Handle image upload for updates
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = 'uploads/categories/' . $slug . '.' . $ext;
        
        move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image);
        
        // Update with image
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $image, $id]);
    } else {
        // Update without changing image
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
    }
    
    $message = 'Category updated successfully!';
}

// Delete category
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    redirect('categories.php?deleted=1');
}

if (isset($_GET['deleted'])) $message = 'Category deleted successfully!';

// Get categories with product count
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();

$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

$pageTitle = 'Categories';
$pageStyles = '
    .categories-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
    .category-list { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .category-item { display: flex; align-items: center; gap: 15px; padding: 18px 20px; border-bottom: 1px solid #eee; }
    .category-item:last-child { border-bottom: none; }
    .category-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; background: #f0f0f0; }
    .category-info { flex: 1; }
    .category-info h4 { font-size: 16px; margin-bottom: 4px; }
    .category-info p { color: #888; font-size: 13px; }
    .category-actions { display: flex; gap: 10px; }
    .category-actions a { padding: 6px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; }
    .btn-edit { background: #f0f0f0; color: #333; }
    .btn-delete { background: #fee; color: #e74c3c; }
    .form-card h3 { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .success-msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    .error-msg { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    .empty-state { text-align: center; padding: 50px; color: #888; }
    .current-image-preview { margin-bottom: 10px; }
    .current-image-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; }
    .current-image-preview p { font-size: 12px; color: #666; margin-top: 5px; }
    @media (max-width: 900px) { .categories-grid { grid-template-columns: 1fr; } }
';
include 'includes/header.php';
?>

            <?php if ($message): ?><div class="success-msg"><?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

            <div class="categories-grid">
                <div class="category-list">
                    <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <p>No categories yet. Create your first category!</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <img src="../<?= htmlspecialchars($cat['image'] ?: 'placeholder.jpg') ?>" alt="">
                        <div class="category-info">
                            <h4><?= htmlspecialchars($cat['name']) ?></h4>
                            <p><?= $cat['product_count'] ?> products</p>
                        </div>
                        <div class="category-actions">
                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn-edit">Edit</a>
                            <?php if ($cat['product_count'] == 0): ?>
                            <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn-delete" onclick="return confirm('Delete this category?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="form-card">
                    <h3><?= $editCategory ? 'Edit Category' : 'Add New Category' ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Image <?= $editCategory ? '(Leave empty to keep current)' : '' ?></label>
                            <?php if ($editCategory && $editCategory['image']): ?>
                            <div class="current-image-preview">
                                <img src="../<?= htmlspecialchars($editCategory['image']) ?>" alt="Current image">
                                <p>Current image</p>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*">
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="<?= $editCategory ? 'update_category' : 'add_category' ?>" class="btn-primary"><?= $editCategory ? 'Update' : 'Add Category' ?></button>
                            <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>