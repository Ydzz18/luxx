<?php
require_once 'config.php';
require_once 'Product.php';

$db = db();
$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 8");
$featured_products = $stmt->fetchAll();

$pageTitle = 'LuxStore | Premium E-Commerce';
$pageStyles = <<<CSS
.slideshow-container {
  position: relative;
  max-width: 600px;
  margin: 30px auto;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  background: #000;
  height: 400px;
}

.slide {
  display: none;
  animation: fade 0.5s ease-in-out;
  width: 100%;
  height: 100%;
}

.slide.active {
  display: block;
}

.slide img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  object-position: center;
}

@keyframes fade {
  0%   { opacity: 0; }
  100% { opacity: 1; }
}

.featured-section {
  max-width: 1400px;
  margin: 60px auto;
  padding: 0 20px;
}

.featured-section h2 {
  text-align: center;
  font-size: 36px;
  margin-bottom: 40px;
  background: linear-gradient(45deg, #d4af37, #b8860b);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
}

.product-card {
  background: #111;
  border: 1px solid #333;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s;
}

.product-card:hover {
  border-color: #d4af37;
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
}

.product-card img {
  width: 100%;
  height: 250px;
  object-fit: cover;
  display: block;
}

.product-info {
  padding: 20px;
}

.product-category {
  color: #d4af37;
  font-size: 12px;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.product-name {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #fff;
  line-height: 1.3;
  min-height: 40px;
}

.product-price {
  font-size: 22px;
  font-weight: 700;
  background: linear-gradient(45deg, #d4af37, #b8860b);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin: 12px 0;
}

.product-stock {
  font-size: 13px;
  color: #27ae60;
  margin-bottom: 15px;
}

.product-stock.out {
  color: #e74c3c;
}

.btn-add {
  width: 100%;
  padding: 12px;
  background: linear-gradient(45deg, #d4af37, #b8860b);
  color: #000;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-add:hover:not(:disabled) {
  filter: brightness(1.2);
  transform: translateY(-2px);
}

.btn-add:disabled {
  background: #333;
  color: #666;
  cursor: not-allowed;
}
CSS;
include 'includes/header.php';
?>

  <section id="home" class="home">
    <div class="home-left">
      <img src="images/logo.png" alt="LuxStore Featured Product">
    </div>

    <div class="home-right">
      <h2>Welcome to <span class="highlight">LuxStore</span></h2>
      <p>Discover premium luxury products curated for elegance, style, and quality.</p>
      <a href="#categories" class="btn-primary">🛒 Shop Now</a>
    </div>
  </section>

  <hr class="divider">

  <section class="slideshow-section">
    <div class="slideshow-container">
      <div class="slide active"><img src="images/model/1.png" alt="Image 1"></div>
      <div class="slide"><img src="images/model/30.png" alt="Image 30"></div>
      <div class="slide"><img src="images/model/31.png" alt="Image 31"></div>
      <div class="slide"><img src="images/model/32.png" alt="Image 32"></div>
      <div class="slide"><img src="images/model/33.png" alt="Image 33"></div>
      <div class="slide"><img src="images/model/34.png" alt="Image 34"></div>
      <div class="slide"><img src="images/model/35.png" alt="Image 35"></div>
      <div class="slide"><img src="images/model/36.png" alt="Image 36"></div>
      <div class="slide"><img src="images/model/37.png" alt="Image 37"></div>
      <div class="slide"><img src="images/model/38.png" alt="Image 38"></div>
      <div class="slide"><img src="images/model/39.png" alt="Image 39"></div>
      <div class="slide"><img src="images/model/40.png" alt="Image 40"></div>
      <div class="slide"><img src="images/model/41.png" alt="Image 41"></div>
      <div class="slide"><img src="images/model/42.png" alt="Image 42"></div>
      <div class="slide"><img src="images/model/43.png" alt="Image 43"></div>
      <div class="slide"><img src="images/model/44.png" alt="Image 44"></div>
      <div class="slide"><img src="images/model/45.png" alt="Image 45"></div>
      <div class="slide"><img src="images/model/46.png" alt="Image 46"></div>
      <div class="slide"><img src="images/model/47.png" alt="Image 47"></div>
      <div class="slide"><img src="images/model/48.png" alt="Image 48"></div>
      <div class="slide"><img src="images/model/49.png" alt="Image 49"></div>
      <div class="slide"><img src="images/model/50.png" alt="Image 50"></div>
      <div class="slide"><img src="images/model/51.png" alt="Image 51"></div>
      <div class="slide"><img src="images/model/52.png" alt="Image 52"></div>
      <div class="slide"><img src="images/model/53.png" alt="Image 53"></div>
      <div class="slide"><img src="images/model/54.png" alt="Image 54"></div>
      <div class="slide"><img src="images/model/2.png" alt="Image 2"></div>
      <div class="slide"><img src="images/model/3.png" alt="Image 3"></div>
      <div class="slide"><img src="images/model/4.png" alt="Image 4"></div>
      <div class="slide"><img src="images/model/5.png" alt="Image 5"></div>
      <div class="slide"><img src="images/model/6.png" alt="Image 6"></div>
      <div class="slide"><img src="images/model/7.png" alt="Image 7"></div>
      <div class="slide"><img src="images/model/8.png" alt="Image 8"></div>
      <div class="slide"><img src="images/model/9.png" alt="Image 9"></div>
      <div class="slide"><img src="images/model/10.png" alt="Image 10"></div>
      <div class="slide"><img src="images/model/11.png" alt="Image 11"></div>
      <div class="slide"><img src="images/model/12.png" alt="Image 12"></div>
      <div class="slide"><img src="images/model/13.png" alt="Image 13"></div>
      <div class="slide"><img src="images/model/14.png" alt="Image 14"></div>
      <div class="slide"><img src="images/model/15.png" alt="Image 15"></div>
      <div class="slide"><img src="images/model/16.png" alt="Image 16"></div>
      <div class="slide"><img src="images/model/17.png" alt="Image 17"></div>
      <div class="slide"><img src="images/model/18.png" alt="Image 18"></div>
      <div class="slide"><img src="images/model/19.png" alt="Image 19"></div>
      <div class="slide"><img src="images/model/20.png" alt="Image 20"></div>
      <div class="slide"><img src="images/model/21.png" alt="Image 21"></div>
      <div class="slide"><img src="images/model/22.png" alt="Image 22"></div>
      <div class="slide"><img src="images/model/23.png" alt="Image 23"></div>
      <div class="slide"><img src="images/model/24.png" alt="Image 24"></div>
      <div class="slide"><img src="images/model/25.png" alt="Image 25"></div>
      <div class="slide"><img src="images/model/26.png" alt="Image 26"></div>
      <div class="slide"><img src="images/model/27.png" alt="Image 27"></div>
      <div class="slide"><img src="images/model/28.png" alt="Image 28"></div>
      <div class="slide"><img src="images/model/29.png" alt="Image 29"></div>
    </div>
  </section>

  <hr class="divider">

  <section id="categories" class="categories">
    <h1>Shop by Category</h1>

    <div class="category-container">

      <div class="category-card" onclick="openGallery('Luxury Bags')">
        <img src="images/bags.png" alt="Luxury Bags">
        <p>Luxury Bags</p>
      </div>

      <div class="category-card" onclick="openGallery('Luxury Watches')">
        <img src="images/watches.png" alt="Luxury Watch">
        <p>Luxury Watches</p>
      </div>

    </div>
  </section>

  <hr class="divider">

  <section class="featured-section">
    <h2>✨ Latest Arrivals</h2>
    
    <?php if (!empty($featured_products)): ?>
    <div class="products-grid">
      <?php foreach ($featured_products as $product): ?>
      <div class="product-card">
        <img src="<?= htmlspecialchars($product['image']) ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>"
             onerror="this.src='images/logo.png'"
             loading="lazy">
        
        <div class="product-info">
          <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Product') ?></div>
          <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
          <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
          <div class="product-stock <?= $product['stock'] <= 0 ? 'out' : '' ?>">
            <?php if ($product['stock'] > 0): ?>
              ✓ In Stock (<?= $product['stock'] ?> available)
            <?php else: ?>
              ✗ Out of Stock
            <?php endif; ?>
          </div>
          <button class="btn-add" 
                  onclick="addToCart(<?= $product['id'] ?>)"
                  <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
            <?= $product['stock'] > 0 ? '🛒 Add to Cart' : 'Out of Stock' ?>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="text-align: center; color: #888;">No products available at this time.</p>
    <?php endif; ?>
  </section>

  <hr class="divider">

  <div id="product-gallery" class="product-gallery hidden">
    <div class="gallery-content">
      <span class="close-btn" onclick="closeGallery()">×</span>
      <h2 id="gallery-title">Products</h2>
      <div id="gallery-items" class="gallery-items">
      </div>
    </div>
  </div>

  <hr class="divider">

  <section id="testimonials">
  <h2 class="testimonial-title">What Our Customers Say</h2>
  <div class="testimonial-container">
    
    <div class="testimonial-box">
      <div class="customer-detail">
        <div class="customer-photo">
          <img src="images/john.jpg" alt="Customer Review">
        </div>
        <p class="customer-name">John Reygun Danag</p>
      </div>
      <div class="star-rating">
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
      </div>
      <p class="testimonial-text">
        "I like the products and the customer service is excellent. Will definitely shop here again!"
      </p>
    </div>

    <div class="testimonial-box">
      <div class="customer-detail">
        <div class="customer-photo">
          <img src="images/ydrian.jpg" alt="Customer Review">
        </div>
        <p class="customer-name">Ydrian Yayen</p>
      </div>
      <div class="star-rating">
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
      </div>
      <p class="testimonial-text">
        "Maganda yung mga products nila lalo na yung mga relo. Sobrang sulit sa presyo at quality!"
      </p>
    </div>

    <div class="testimonial-box">
      <div class="customer-detail">
        <div class="customer-photo">
          <img src="images/nig.jpg" alt="Customer Review">
        </div>
        <p class="customer-name">Andre Pagliawan</p>
      </div>
      <div class="star-rating">
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
        <span class="fa fa-star checked">★</span>
      </div>
      <p class="testimonial-text">
        "Great service and fast delivery! Highly recommend LuxStore for luxury shopping."
      </p>
    </div>

  </div>
</section>

  <hr class="divider">

  <section id="contacts" class="contacts-section">
    <h2>Contact Us</h2>
    <p style="color: #888; margin-bottom: 30px;">Have questions? We'd love to hear from you!</p>

    <form class="feedback-form" id="feedbackForm">
      <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
        <div>
          <label for="name">Your Name</label>
          <input type="text" id="name" name="name" placeholder="John Doe" required 
                 style="width: 100%; padding: 12px; background: #111; border: 1px solid #333; color: #fff; border-radius: 6px; margin-top: 5px;">
        </div>
        <div>
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required
                 style="width: 100%; padding: 12px; background: #111; border: 1px solid #333; color: #fff; border-radius: 6px; margin-top: 5px;">
        </div>
      </div>
    
      <div style="margin-bottom: 15px;">
        <label for="subject">Subject</label>
        <select id="subject" name="subject" style="width: 100%; padding: 12px; background: #111; border: 1px solid #333; color: #fff; border-radius: 6px; margin-top: 5px;">
          <option value="General Inquiry">General Inquiry</option>
          <option value="Product Question">Product Question</option>
          <option value="Order Support">Order Support</option>
          <option value="Feedback">Feedback</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <label for="feedback">Message</label>
      <textarea id="feedback" name="message" placeholder="Write your message here..." required></textarea>
    
      <button type="submit" id="submitBtn">📧 Send Message</button>
    </form>

    <div id="formMessage" style="display: none; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;"></div>

    <p style="margin-top: 25px; color: #888;">Or visit our <a href="contact.php" style="color: #d4af37;">full contact page</a> for more options.</p>

    <hr class="divider" style="margin: 30px auto;">
  </section>

  <script>
    let current = 0;
    const slides = document.querySelectorAll('.slide');
    const total = slides.length;

    function nextSlide() {
      slides[current].classList.remove('active');
      current = (current + 1) % total;
      slides[current].classList.add('active');
    }

    setInterval(nextSlide, 3000);

    function addToCart(productId) {
      <?php if (!isLoggedIn()): ?>
      window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
      return;
      <?php endif; ?>

      fetch('cart-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('✓ Added to cart! Cart count: ' + data.cart_count);
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to add product to cart');
      });
    }
  </script>

  <script src="scripts.js"></script>

<?php include 'includes/footer.php'; ?>
