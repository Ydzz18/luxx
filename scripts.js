/* =========================================================
   LUXSTORE MAIN SCRIPT
   Loads products from database
   ========================================================= */

/* =========================================================
   PRODUCT GALLERY - DATABASE VERSION
   ========================================================= */

// Open Gallery - Fetches products from database
async function openGallery(categoryName) {
    const gallery = document.getElementById("product-gallery");
    const title = document.getElementById("gallery-title");
    const items = document.getElementById("gallery-items");

    // Show loading
    title.textContent = categoryName;
    items.innerHTML = '<p style="text-align: center; color: #888; padding: 40px; grid-column: 1/-1;">Loading products...</p>';
    gallery.classList.remove("hidden");
    document.body.style.overflow = "hidden";

    try {
        // Fetch products from database
        const response = await fetch(`products_api.php?action=get_by_category_name&category=${encodeURIComponent(categoryName)}`);
        const data = await response.json();

        if (data.success && data.products.length > 0) {
            items.innerHTML = "";
            const currency = data.currency || '₱';

            data.products.forEach(product => {
                const price = product.sale_price || product.price;
                const originalPrice = product.sale_price ? product.price : null;
                
                // Create product card directly (no wrapper)
                const card = document.createElement("div");
                card.className = "product-card-gallery";
                card.innerHTML = `
                    <img src="${product.image || 'images/logo.png'}" alt="${product.name}" onerror="this.src='images/logo.png'"loading="lazy">
                    ${product.featured ? '<span class="featured-tag">★ Featured</span>' : ''}
                    <div class="product-info">
                        <h3>${product.name}</h3>
                        <p class="product-desc">${product.description || ''}</p>
                        <div class="product-price">
                            <span class="current-price">${currency}${parseFloat(price).toLocaleString()}</span>
                            ${originalPrice ? `<span class="original-price">${currency}${parseFloat(originalPrice).toLocaleString()}</span>` : ''}
                        </div>
                        <p class="stock-info">${product.stock > 0 ? `In Stock: ${product.stock}` : '<span style="color: #e74c3c;">Out of Stock</span>'}</p>
                        <button class="btn-add-cart" onclick="addToCart(${product.id})" ${product.stock <= 0 ? 'disabled' : ''}>
                            ${product.stock > 0 ? '🛒 Add to Cart' : 'Out of Stock'}
                        </button>
                    </div>
                `;
                items.appendChild(card);
            });
        } else {
            items.innerHTML = `
                <div style="text-align: center; padding: 60px; color: #888; grid-column: 1/-1;">
                    <p style="font-size: 48px;">📦</p>
                    <h3>No Products Found</h3>
                    <p>This category doesn't have any products yet.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading products:', error);
        items.innerHTML = `
            <div style="text-align: center; padding: 60px; color: #e74c3c; grid-column: 1/-1;">
                <p style="font-size: 48px;">⚠️</p>
                <h3>Error Loading Products</h3>
                <p>Please try again later.</p>
            </div>
        `;
    }
}

// Close Gallery
function closeGallery() {
    const gallery = document.getElementById("product-gallery");
    gallery.classList.add("hidden");
    document.body.style.overflow = "auto";
}

// Close gallery when clicking outside
document.getElementById("product-gallery")?.addEventListener("click", e => {
    if (e.target.id === "product-gallery") closeGallery();
});

// Close with ESC key
window.addEventListener("keydown", e => {
    if (e.key === "Escape") closeGallery();
});

/* =========================================================
   ADD TO CART
   ========================================================= */
async function addToCart(productId, quantity = 1) {
    try {
        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showNotification('✓ Added to cart!', 'success');
            updateCartCount(data.cart_count);
        } else {
            showNotification('Failed to add to cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
    }
}

// Update cart count badge
function updateCartCount(count) {
    const badges = document.querySelectorAll('.cart-count, .cart-badge');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
    });
}

// Show notification
function showNotification(message, type = 'success') {
    // Remove existing notification
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: #fff;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
    `;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/* =========================================================
   MOBILE MENU - FIXED VERSION
   ========================================================= */
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById("mobileMenuBtn");
    const navLinks = document.querySelector(".nav-links");

    if (menuBtn && navLinks) {
        menuBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            navLinks.classList.toggle("show");
        });

        // Close menu when clicking on any navigation link
        document.querySelectorAll(".nav-links a").forEach(link => {
            link.addEventListener("click", () => {
                navLinks.classList.remove("show");
            });
        });

        // Close menu when clicking outside
        document.addEventListener("click", (e) => {
            if (!navLinks.contains(e.target) && !menuBtn.contains(e.target)) {
                navLinks.classList.remove("show");
            }
        });
    }

    // Mobile dropdown toggle
    document.querySelectorAll(".dropdown").forEach(drop => {
        drop.addEventListener("click", (e) => {
            if (window.innerWidth <= 900) {
                e.preventDefault();
                e.currentTarget.classList.toggle("active");
            }
        });
    });
});

/* =========================================================
   HEADER SHADOW ON SCROLL
   ========================================================= */
window.addEventListener("scroll", () => {
    const header = document.querySelector("header");
    header?.classList.toggle("scrolled", window.scrollY > 50);
});

/* =========================================================
   SCROLL FADE-IN ANIMATION
   ========================================================= */
const revealElements = document.querySelectorAll(".fade-in");

function revealOnScroll() {
    revealElements.forEach(el => {
        const isVisible = el.getBoundingClientRect().top < window.innerHeight - 80;
        if (isVisible) el.classList.add("visible");
    });
}

window.addEventListener("scroll", revealOnScroll);
revealOnScroll();

/* =========================================================
   FEEDBACK FORM
   ========================================================= */
const feedbackForm = document.getElementById('feedbackForm');

feedbackForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const btn = document.getElementById('submitBtn');
    const msgDiv = document.getElementById('formMessage');
    
    btn.disabled = true;
    btn.textContent = 'Sending...';
    
    const formData = new FormData(feedbackForm);
    formData.append('action', 'submit_feedback');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        msgDiv.style.display = 'block';
        
        if (data.success) {
            msgDiv.style.background = 'rgba(39, 174, 96, 0.1)';
            msgDiv.style.border = '1px solid #27ae60';
            msgDiv.style.color = '#27ae60';
            msgDiv.innerHTML = '✓ ' + data.message;
            feedbackForm.reset();
        } else {
            msgDiv.style.background = 'rgba(231, 76, 60, 0.1)';
            msgDiv.style.border = '1px solid #e74c3c';
            msgDiv.style.color = '#e74c3c';
            msgDiv.innerHTML = '✗ ' + (data.message || 'Failed to send. Please try again.');
        }
    } catch (error) {
        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(231, 76, 60, 0.1)';
        msgDiv.style.border = '1px solid #e74c3c';
        msgDiv.style.color = '#e74c3c';
        msgDiv.innerHTML = '✗ Connection error. Please try again.';
    }
    
    btn.disabled = false;
    btn.textContent = 'Send Message';
    
    setTimeout(() => {
        msgDiv.style.display = 'none';
    }, 5000);
});