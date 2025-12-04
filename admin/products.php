<?php
require_once 'auth.php';
requireLogin();
require_once '../Product.php';

// Check permission
if (!canDo('products', 'view')) {
    showAccessDenied();
}

$productModel = new Product();
$categoryModel = new Category();
$categories = $categoryModel->getAll();
$products = $productModel->getAll();

$action = $_GET['action'] ?? 'list';
$editProduct = null;

// Get product for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $editProduct = $productModel->getById($_GET['id']);
}

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle image upload
    $imageName = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'uploads/products/' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../' . $imageName);
    }

    $data = [
        'category_id' => $_POST['category_id'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'sale_price' => $_POST['sale_price'] ?: null,
        'stock' => $_POST['stock'],
        'image' => $imageName,
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'status' => $_POST['status'] ?? 'active'
    ];

    if (isset($_POST['add_product'])) {
        if ($productModel->create($data)) {
            $message = 'Product added successfully!';
            $action = 'list';
            $products = $productModel->getAll();
        } else {
            $error = 'Failed to add product';
        }
    }

    if (isset($_POST['update_product'])) {
        if ($productModel->update($_POST['product_id'], $data)) {
            $message = 'Product updated successfully!';
            $action = 'list';
            $products = $productModel->getAll();
        } else {
            $error = 'Failed to update product';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $productModel->delete($_GET['delete']);
    redirect('products.php?deleted=1');
}

if (isset($_GET['deleted'])) $message = 'Product deleted successfully!';

$pageTitle = $action === 'add' ? 'Add Product' : ($action === 'edit' ? 'Edit Product' : 'Products');
$pageStyles = '
    .products-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .btn-add { background: linear-gradient(45deg, #d4af37, #b8860b); color: #000; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .product-card img { width: 100%; height: 200px; object-fit: cover; }
    .product-card-body { padding: 20px; }
    .product-card h3 { font-size: 16px; margin-bottom: 8px; }
    .product-card .category { color: #888; font-size: 13px; margin-bottom: 10px; }
    .product-card .price { color: #d4af37; font-weight: bold; font-size: 18px; }
    .product-card .price .sale { color: #e74c3c; margin-left: 10px; }
    .product-card .stock { margin-top: 10px; font-size: 13px; }
    .product-card .stock.low { color: #e74c3c; }
    .product-card-actions { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
    .product-card-actions a { flex: 1; text-align: center; padding: 8px; border-radius: 6px; text-decoration: none; font-size: 14px; }
    .btn-edit { background: #f0f0f0; color: #333; }
    .btn-delete { background: #fee; color: #e74c3c; }
    .featured-badge { position: absolute; top: 10px; right: 10px; background: #d4af37; color: #000; padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; }
    .product-card-img { position: relative; }
    .image-preview { max-width: 200px; max-height: 150px; margin-top: 10px; border-radius: 8px; display: none; }
    .success-msg { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
    .error-msg { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
';
include 'includes/header.php';
?>

            <?php if ($message): ?><div class="success-msg"><?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <a href="products.php" class="back-link" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none;">← Back to Products</a>
            
            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                    <input type="hidden" name="existing_image" value="<?= $editProduct['image'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₱) *</label>
                            <input type="number" name="price" step="0.01" required value="<?= $editProduct['price'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Sale Price (₱)</label>
                            <input type="number" name="sale_price" step="0.01" value="<?= $editProduct['sale_price'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock *</label>
                            <input type="number" name="stock" required value="<?= $editProduct['stock'] ?? 0 ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?= ($editProduct['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($editProduct['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                        <img id="imagePreview" class="image-preview" src="<?= $editProduct ? '../'.$editProduct['image'] : '' ?>" style="<?= $editProduct && $editProduct['image'] ? 'display:block;' : '' ?>">
                    </div>

                    <div class="form-group">
                        <label><input type="checkbox" name="featured" <?= ($editProduct['featured'] ?? 0) ? 'checked' : '' ?>> Featured Product</label>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 25px;">
                        <button type="submit" name="<?= $editProduct ? 'update_product' : 'add_product' ?>" class="btn-primary"><?= $editProduct ? 'Update Product' : 'Add Product' ?></button>
                        <a href="products.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <?php else: ?>
            <!-- Products List -->
            <div class="products-header">
                <p><?= count($products) ?> products</p>
                <a href="products.php?action=add" class="btn-add">+ Add Product</a>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <div class="product-card-img">
                        <img src="../<?= htmlspecialchars($p['image'] ?: 'placeholder.jpg') ?>" alt="">
                        <?php if ($p['featured']): ?><span class="featured-badge">★ Featured</span><?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <p class="category"><?= htmlspecialchars($p['category_name']) ?></p>
                        <h3><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="price">
                            ₱<?= number_format($p['price'], 2) ?>
                            <?php if ($p['sale_price']): ?><span class="sale">₱<?= number_format($p['sale_price'], 2) ?></span><?php endif; ?>
                        </p>
                        <p class="stock <?= $p['stock'] < 5 ? 'low' : '' ?>">Stock: <?= $p['stock'] ?></p>
                        <div class="product-card-actions">
                            <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn-edit">Edit</a>
                            <a href="products.php?delete=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Delete this product?')">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>