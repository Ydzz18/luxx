<?php
require_once 'config.php';
require_once 'feedback.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = new Feedback();
    $result = $feedback->submit($_POST);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Pre-fill if logged in
$userName = '';
$userEmail = '';
if (isLoggedIn()) {
    $userName = $_SESSION['user_name'] ?? '';
    $userEmail = $_SESSION['user_email'] ?? '';
}

$pageTitle = 'LuxStore | Contact Us';
$pageStyles = '
    .contact-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
    .contact-info { padding: 20px 0; }
    .contact-info h2 { color: #d4af37; margin-bottom: 20px; font-size: 28px; }
    .contact-info p { color: #ccc; line-height: 1.8; margin-bottom: 30px; }
    .info-item { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px; padding: 20px; background: #111; border-radius: 10px; border: 1px solid #333; }
    .info-item .icon { font-size: 28px; }
    .info-item h4 { color: #d4af37; margin-bottom: 5px; }
    .info-item p { color: #fff; margin: 0; }
    .info-item a { color: #d4af37; text-decoration: none; }
    .info-item a:hover { text-decoration: underline; }
    .contact-form { background: #111; padding: 40px; border-radius: 16px; border: 1px solid #333; }
    .contact-form h2 { color: #d4af37; margin-bottom: 25px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; color: #d4af37; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 14px; background: #1a1a1a; border: 1px solid #333; color: #fff; border-radius: 8px; font-size: 15px; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #d4af37; outline: none; }
    .form-group textarea { min-height: 150px; resize: vertical; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .btn-submit { width: 100%; padding: 15px; background: linear-gradient(45deg, #d4af37, #b8860b); border: none; color: #000; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { filter: brightness(1.15); transform: translateY(-2px); }
    .success-msg { background: rgba(39, 174, 96, 0.1); border: 1px solid #27ae60; color: #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    .error-msg { background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    .social-links { display: flex; gap: 15px; margin-top: 30px; }
    .social-links a { width: 45px; height: 45px; background: #222; border: 1px solid #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; text-decoration: none; transition: all 0.3s; }
    .social-links a:hover { background: #d4af37; border-color: #d4af37; transform: translateY(-3px); }
    @media (max-width: 800px) { .contact-container { grid-template-columns: 1fr; } .form-row { grid-template-columns: 1fr; } }
';
include 'includes/header.php';
?>

    <section class="page-header">
        <h1 class="gold">Contact Us</h1>
        <p>We'd love to hear from you</p>
    </section>

    <div class="contact-container">
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <p>Have questions about our luxury products? Need help with an order? Or just want to share your feedback? We're here to help!</p>
            
            <div class="info-item">
                <span class="icon">📍</span>
                <div>
                    <h4>Visit Us</h4>
                    <p>123 Luxury Avenue<br>Puerto Princesa City, Philippines</p>
                </div>
            </div>
            
            <div class="info-item">
                <span class="icon">📞</span>
                <div>
                    <h4>Call Us</h4>
                    <p><a href="tel:+639123456789">+63 912 345 6789</a></p>
                </div>
            </div>
            
            <div class="info-item">
                <span class="icon">✉️</span>
                <div>
                    <h4>Email Us</h4>
                    <p><a href="mailto:luxstoreecommerce@gmail.com">luxstoreecommerce@gmail.com</a></p>
                </div>
            </div>
            
            <div class="info-item">
                <span class="icon">🕐</span>
                <div>
                    <h4>Business Hours</h4>
                    <p>Mon - Sat: 9:00 AM - 8:00 PM<br>Sunday: 10:00 AM - 6:00 PM</p>
                </div>
            </div>

            <div class="social-links">
                <a href="https://www.facebook.com/share/181RCtTzMB/?mibextid=wwXIfr" target="_blank">📘</a>
                <a href="#" target="_blank">📸</a>
                <a href="#" target="_blank">🐦</a>
            </div>
        </div>

        <div class="contact-form">
            <h2>Send us a Message</h2>
            
            <?php if ($success): ?>
            <div class="success-msg">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="contactForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($userName) ?>" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($userEmail) ?>" placeholder="you@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Product Question">Product Question</option>
                        <option value="Order Support">Order Support</option>
                        <option value="Returns & Refunds">Returns & Refunds</option>
                        <option value="Feedback">Feedback</option>
                        <option value="Partnership">Partnership</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Your Message *</label>
                    <textarea name="message" required placeholder="How can we help you?"></textarea>
                </div>

                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>

    <script>
    // Optional: AJAX submission
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        const btn = this.querySelector('.btn-submit');
        btn.textContent = 'Sending...';
        btn.disabled = true;
    });
    </script>

<?php include 'includes/footer.php'; ?>