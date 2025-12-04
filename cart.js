/**
 * LuxStore Cart Handler
 * Handles all cart operations via AJAX
 */

const LuxCart = {
    apiUrl: 'products_api.php',
    
    // Add product to cart
    async add(productId, quantity = 1) {
        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                this.updateCartBadge(data.cart_count);
                this.showNotification('Added to cart!', 'success');
            }
            return data;
        } catch (err) {
            this.showNotification('Error adding to cart', 'error');
            return { success: false };
        }
    },
    
    // Update item quantity
    async update(productId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                this.updateCartBadge(data.cart_count);
                this.renderCart();
            }
            return data;
        } catch (err) {
            return { success: false };
        }
    },
    
    // Remove item from cart
    async remove(productId) {
        const formData = new FormData();
        formData.append('action', 'remove_from_cart');
        formData.append('product_id', productId);
        
        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                this.updateCartBadge(data.cart_count);
                this.renderCart();
                this.showNotification('Item removed', 'success');
            }
            return data;
        } catch (err) {
            return { success: false };
        }
    },
    
    // Get cart contents
    async getCart() {
        try {
            const res = await fetch(`${this.apiUrl}?action=get_cart`);
            return await res.json();
        } catch (err) {
            return { success: false, items: [] };
        }
    },
    
    // Render cart items in the cart page or drawer
    async renderCart() {
        const container = document.getElementById('cart-items');
        if (!container) return;
        
        const data = await this.getCart();
        
        if (!data.success || data.items.length === 0) {
            container.innerHTML = `
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="index.html#categories" class="btn-primary">Start Shopping</a>
                </div>`;
            this.updateTotals(0, 0);
            return;
        }
        
        let html = '';
        data.items.forEach(item => {
            const price = item.sale_price || item.price;
            const total = price * item.quantity;
            
            html += `
                <div class="cart-item" data-id="${item.product_id}">
                    <img src="${item.image}" alt="${item.name}">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="price">₱${parseFloat(price).toFixed(2)}</p>
                        <div class="quantity-controls">
                            <button onclick="LuxCart.update(${item.product_id}, ${item.quantity - 1})">−</button>
                            <span>${item.quantity}</span>
                            <button onclick="LuxCart.update(${item.product_id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <div class="cart-item-total">
                        <p>₱${total.toFixed(2)}</p>
                        <button class="remove-btn" onclick="LuxCart.remove(${item.product_id})">Remove</button>
                    </div>
                </div>`;
        });
        
        container.innerHTML = html;
        this.updateTotals(data.subtotal, data.count);
    },
    
    // Update cart badge
    updateCartBadge(count) {
        const badges = document.querySelectorAll('.cart-count, .cart-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    },
    
    // Update totals display
    updateTotals(subtotal, count) {
        const subtotalEl = document.getElementById('cart-subtotal');
        const shippingEl = document.getElementById('cart-shipping');
        const totalEl = document.getElementById('cart-total');
        
        if (subtotalEl) subtotalEl.textContent = `₱${parseFloat(subtotal).toFixed(2)}`;
        
        const shipping = subtotal >= 50000 ? 0 : 500;
        if (shippingEl) shippingEl.textContent = shipping === 0 ? 'FREE' : `₱${shipping.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `₱${(parseFloat(subtotal) + shipping).toFixed(2)}`;
    },
    
    // Show notification
    showNotification(message, type = 'success') {
        const existing = document.querySelector('.cart-notification');
        if (existing) existing.remove();
        
        const notification = document.createElement('div');
        notification.className = `cart-notification ${type}`;
        notification.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 2500);
    },
    
    // Initialize
    init() {
        this.renderCart();
        this.getCart().then(data => {
            if (data.success) this.updateCartBadge(data.count);
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => LuxCart.init());