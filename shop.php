<?php
require_once 'config.php';

// Get filter parameters
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'active'";

$params = [];

// Filter by category
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// Filter by search
if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

// Execute query
$db = db();
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'LuxStore | Shop';
$pageStyles = <<<CSS
.shop-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.shop-header { text-align: center; margin-bottom: 40px; }
.shop-header h1 { font-size: 48px; margin-bottom: 10px; }
.shop-header p { color: #888; font-size: 18px; }

/* Filters Section */
.shop-filters { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 20px; 
    margin-bottom: 30px; 
    padding: 20px; 
    background: #111; 
    border-radius: 12px; 
    border: 1px solid #333;
    flex-wrap: wrap;
}

.filter-group { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }

.filter-group label { color: #d4af37; font-weight: 500; }

.filter-select, .search-input {
    padding: 10px 15px;
    background: #1a1a1a;
    border: 1px solid #333;
    color: #fff;
    border-radius: 6px;
    font-size: 14px;
    min-width: 150px;
}

.filter-select:focus, .search-input:focus {
    border-color: #d4af37;
    outline: none;
}

.search-box { display: flex; gap: 10px; }

.btn-search {
    padding: 10px 20px;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    border: none;
    color: #000;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
}

.btn-search:hover { filter: brightness(1.15); }

.btn-clear {
    padding: 10px 20px;
    background: #333;
    border: none;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
}

.btn-clear:hover { background: #444; }

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.product-card {
    background: #111;
    border: 1px solid #333;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    border-color: #d4af37;
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
}

.product-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    background: #000;
    transition: transform 0.3s;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    color: #000;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    z-index: 10;
}

.product-details {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-grow: 1;
}

.product-category {
    font-size: 12px;
    color: #d4af37;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
}

.product-name {
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    margin: 0;
    line-height: 1.4;
    min-height: 50px;
}

.product-description {
    font-size: 14px;
    color: #999;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 42px;
}

.product-price {
    font-size: 24px;
    font-weight: 700;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 10px 0;
}

.product-stock {
    font-size: 13px;
    color: #27ae60;
    font-weight: 500;
}

.product-stock.out-of-stock {
    color: #e74c3c;
}

.btn-add-to-cart {
    width: 100%;
    padding: 12px;
    background: linear-gradient(45deg, #d4af37, #b8860b);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: auto;
}

.btn-add-to-cart:hover:not(:disabled) {
    filter: brightness(1.2);
    transform: translateY(-2px);
}

.btn-add-to-cart:disabled {
    background: #333;
    color: #666;
    cursor: not-allowed;
}

.no-products {
    text-align: center;
    padding: 80px 20px;
    color: #888;
}

.no-products h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

.results-count {
    color: #888;
    margin-bottom: 20px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .shop-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select, .search-input {
        width: 100%;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
}
CSS;
include 'includes/header.php';
?>

    <div class="shop-container">
        <div class="shop-header fade-in">
            <h1 class="gold">Luxury Collection</h1>
            <p>Discover our exclusive range of premium products</p>
        </div>

        <!-- Filters -->
        <div class="shop-filters fade-in">
            <form method="GET" action="shop.php" style="display: contents;">
                <div class="filter-group">
                    <label>Category:</label>
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Sort By:</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
                    </select>
                </div>

                <div class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="Search products..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Search</button>
                    <?php if ($category_id || $search || $sort !== 'newest'): ?>
                    <a href="shop.php" class="btn-clear">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <?php if (!empty($products)): ?>
        <div class="results-count">
            Showing <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
        <div class="no-products fade-in">
            <h3>No Products Found</h3>
            <p>Try adjusting your filters or search terms</p>
            <a href="shop.php" class="btn-search" style="display: inline-block; margin-top: 20px; text-decoration: none;">View All Products</a>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card fade-in">
                <?php if ($product['featured']): ?>
                <span class="featured-badge">⭐ Featured</span>
                <?php endif; ?>
                
                <img src="<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="product-image"
                     onerror="this.src='images/logo.png'"
                     loading="lazy">
                
                <div class="product-details">
                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                    
                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                    
                    <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                    
                    <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                    
                    <div class="product-stock <?= $product['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                        <?php if ($product['stock'] > 0): ?>
                            ✓ In Stock (<?= $product['stock'] ?> available)
                        <?php else: ?>
                            ✗ Out of Stock
                        <?php endif; ?>
                    </div>
                    
                    <button class="btn-add-to-cart" 
                            onclick="addToCart(<?= $product['id'] ?>)" 
                            <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                        <?= $product['stock'] > 0 ? '🛒 Add to Cart' : 'Out of Stock' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(productId) {
            <?php if (!isLoggedIn()): ?>
            // Redirect to login if not logged in
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
            <?php endif; ?>

            // Add to cart via AJAX
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Product added to cart!');
                    // Optionally update cart count in header
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add product to cart');
            });
        }

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>

<?php include 'includes/footer.php'; ?>