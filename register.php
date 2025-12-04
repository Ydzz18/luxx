<?php
require_once 'config.php';
require_once 'User.php';

if (isLoggedIn()) {
    redirect('account.php');
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validation Rules
    
    // First Name validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    } elseif (strlen($first_name) < 2) {
        $errors[] = 'First name must be at least 2 characters';
    } elseif (strlen($first_name) > 50) {
        $errors[] = 'First name must not exceed 50 characters';
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $first_name)) {
        $errors[] = 'First name can only contain letters, spaces, hyphens, and apostrophes';
    }
    
    // Last Name validation
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    } elseif (strlen($last_name) < 2) {
        $errors[] = 'Last name must be at least 2 characters';
    } elseif (strlen($last_name) > 50) {
        $errors[] = 'Last name must not exceed 50 characters';
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $last_name)) {
        $errors[] = 'Last name can only contain letters, spaces, hyphens, and apostrophes';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address format';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email address is too long';
    } else {
        // Check if email already exists
        $user = new User();
        if ($user->emailExists($email)) {
            $errors[] = 'This email address is already registered';
        }
    }
    
    // Phone validation (optional but if provided, must be valid)
    if (!empty($phone)) {
        // Remove common formatting characters
        $cleaned_phone = preg_replace('/[\s\-\(\)]+/', '', $phone);
        
        if (!preg_match('/^\+?[0-9]{10,15}$/', $cleaned_phone)) {
            $errors[] = 'Invalid phone number format. Use format: +63 912 345 6789';
        }
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } else {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^a-zA-Z\d]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check for common weak passwords
        $weak_passwords = ['password', '12345678', 'qwerty123', 'admin123'];
        if (in_array(strtolower($password), $weak_passwords)) {
            $errors[] = 'Password is too common. Please choose a stronger password';
        }
    }
    
    // Confirm Password validation
    if (empty($confirm_password)) {
        $errors[] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Terms and conditions validation
    if (!$terms) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    // CSRF Token validation (add this to your form)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid security token. Please refresh the page and try again';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $user = new User();
        $result = $user->register([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ]);
        
        if ($result['success']) {
            flash('success', 'Account created successfully! Please login.');
            redirect('login.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'LuxStore | Create Account';
$pageStyles = <<<CSS
.auth-container { max-width: 500px; margin: 60px auto; padding: 0 20px; }
.auth-box { background: #111; padding: 40px; border-radius: 16px; border: 1px solid #333; }
.auth-box h1 { text-align: center; margin-bottom: 10px; background: linear-gradient(45deg, #d4af37, #b8860b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.auth-box .subtitle { text-align: center; color: #888; margin-bottom: 30px; }
.form-group { margin-bottom: 20px; position: relative; }
.form-group label { display: block; margin-bottom: 8px; color: #d4af37; font-weight: 500; }
.form-group input { width: 100%; padding: 14px 16px; background: #1a1a1a; border: 1px solid #333; color: #fff; border-radius: 8px; font-size: 15px; transition: border-color 0.3s; }
.form-group input:focus { border-color: #d4af37; outline: none; }
.form-group input.invalid { border-color: #e74c3c; }
.form-group input.valid { border-color: #27ae60; }
.form-group .field-error { color: #e74c3c; font-size: 13px; margin-top: 5px; display: none; }
.form-group .field-error.show { display: block; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.btn-submit { width: 100%; padding: 15px; background: linear-gradient(45deg, #d4af37, #b8860b); border: none; color: #000; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
.btn-submit:hover { filter: brightness(1.15); transform: translateY(-2px); }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.divider { display: flex; align-items: center; margin: 25px 0; color: #666; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #333; }
.divider span { padding: 0 15px; }
.auth-link { text-align: center; margin-top: 20px; color: #888; }
.auth-link a { color: #d4af37; text-decoration: none; font-weight: 500; }
.error-msg { background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
.error-msg ul { margin: 5px 0; padding-left: 20px; }
.password-strength { height: 4px; background: #333; border-radius: 2px; margin-top: 8px; overflow: hidden; }
.password-strength .bar { height: 100%; width: 0; transition: all 0.3s; }
.password-requirements { font-size: 12px; color: #666; margin-top: 8px; }
.password-requirements div { margin: 3px 0; }
.password-requirements .met { color: #27ae60; }
.terms { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; color: #888; font-size: 14px; }
.terms input { width: 18px; height: 18px; accent-color: #d4af37; margin-top: 2px; }
.terms a { color: #d4af37; }
.logo-section { text-align: center; margin-bottom: 25px; }
.logo-section img { width: 70px; margin-bottom: 10px; }
.validation-icon { position: absolute; right: 16px; top: 43px; font-size: 18px; display: none; }
.validation-icon.show { display: block; }
@media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }
CSS;
include 'includes/header.php';
?>

    <div class="auth-container">
        <div class="auth-box">
            <div class="logo-section">
                <img src="images/logo.png" alt="LuxStore">
                <h1>Create Account</h1>
                <p class="subtitle">Join LuxStore for exclusive benefits</p>
            </div>

            <?php if ($error || !empty($errors)): ?>
            <div class="error-msg">
                <?php if ($error): ?>
                    <?= htmlspecialchars($error) ?>
                <?php else: ?>
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" id="first_name" placeholder="John" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        <span class="validation-icon" id="first_name_icon"></span>
                        <div class="field-error" id="first_name_error"></div>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Doe" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        <span class="validation-icon" id="last_name_icon"></span>
                        <div class="field-error" id="last_name_error"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <span class="validation-icon" id="email_icon"></span>
                    <div class="field-error" id="email_error"></div>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="+63 912 345 6789" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    <span class="validation-icon" id="phone_icon"></span>
                    <div class="field-error" id="phone_error"></div>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" id="password" placeholder="Min. 8 characters" required>
                    <span class="validation-icon" id="password_icon"></span>
                    <div class="password-strength"><div class="bar" id="strengthBar"></div></div>
                    <div class="password-requirements" id="passwordReqs">
                        <div id="req_length">• At least 8 characters</div>
                        <div id="req_lower">• One lowercase letter</div>
                        <div id="req_upper">• One uppercase letter</div>
                        <div id="req_number">• One number</div>
                        <div id="req_special">• One special character</div>
                    </div>
                    <div class="field-error" id="password_error"></div>
                </div>

                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                    <span class="validation-icon" id="confirm_password_icon"></span>
                    <div class="field-error" id="confirm_password_error"></div>
                </div>

                <div class="terms">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </div>
                <div class="field-error" id="terms_error"></div>

                <button type="submit" class="btn-submit" id="submitBtn">Create Account</button>
            </form>

            <div class="divider"><span>or</span></div>

            <p class="auth-link">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>

    <script>
    // Validation functions
    function showError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '_error');
        const icon = document.getElementById(fieldId + '_icon');
        
        input.classList.add('invalid');
        input.classList.remove('valid');
        error.textContent = message;
        error.classList.add('show');
        icon.textContent = '✗';
        icon.style.color = '#e74c3c';
        icon.classList.add('show');
    }

    function showSuccess(fieldId) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '_error');
        const icon = document.getElementById(fieldId + '_icon');
        
        input.classList.remove('invalid');
        input.classList.add('valid');
        error.classList.remove('show');
        icon.textContent = '✓';
        icon.style.color = '#27ae60';
        icon.classList.add('show');
    }

    function clearValidation(fieldId) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '_error');
        const icon = document.getElementById(fieldId + '_icon');
        
        input.classList.remove('invalid', 'valid');
        error.classList.remove('show');
        icon.classList.remove('show');
    }

    // Name validation
    function validateName(fieldId, fieldName) {
        const input = document.getElementById(fieldId);
        const value = input.value.trim();
        
        if (value === '') {
            showError(fieldId, fieldName + ' is required');
            return false;
        } else if (value.length < 2) {
            showError(fieldId, fieldName + ' must be at least 2 characters');
            return false;
        } else if (value.length > 50) {
            showError(fieldId, fieldName + ' must not exceed 50 characters');
            return false;
        } else if (!/^[a-zA-Z\s'-]+$/.test(value)) {
            showError(fieldId, fieldName + ' can only contain letters, spaces, hyphens, and apostrophes');
            return false;
        }
        
        showSuccess(fieldId);
        return true;
    }

    // Email validation
    function validateEmail() {
        const input = document.getElementById('email');
        const value = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (value === '') {
            showError('email', 'Email address is required');
            return false;
        } else if (!emailRegex.test(value)) {
            showError('email', 'Invalid email address format');
            return false;
        } else if (value.length > 100) {
            showError('email', 'Email address is too long');
            return false;
        }
        
        showSuccess('email');
        return true;
    }

    // Phone validation
    function validatePhone() {
        const input = document.getElementById('phone');
        const value = input.value.trim();
        
        if (value === '') {
            clearValidation('phone');
            return true; // Optional field
        }
        
        const cleaned = value.replace(/[\s\-\(\)]+/g, '');
        if (!/^\+?[0-9]{10,15}$/.test(cleaned)) {
            showError('phone', 'Invalid phone number format');
            return false;
        }
        
        showSuccess('phone');
        return true;
    }

    // Password validation and strength
    document.getElementById('password').addEventListener('input', function() {
        const bar = document.getElementById('strengthBar');
        const val = this.value;
        let strength = 0;
        
        // Check requirements
        const hasLength = val.length >= 8;
        const hasLower = /[a-z]/.test(val);
        const hasUpper = /[A-Z]/.test(val);
        const hasNumber = /\d/.test(val);
        const hasSpecial = /[^a-zA-Z\d]/.test(val);
        
        // Update requirement indicators
        document.getElementById('req_length').className = hasLength ? 'met' : '';
        document.getElementById('req_lower').className = hasLower ? 'met' : '';
        document.getElementById('req_upper').className = hasUpper ? 'met' : '';
        document.getElementById('req_number').className = hasNumber ? 'met' : '';
        document.getElementById('req_special').className = hasSpecial ? 'met' : '';
        
        // Calculate strength
        if (hasLength) strength += 20;
        if (hasLower) strength += 20;
        if (hasUpper) strength += 20;
        if (hasNumber) strength += 20;
        if (hasSpecial) strength += 20;
        
        bar.style.width = strength + '%';
        bar.style.background = strength < 40 ? '#e74c3c' : strength < 80 ? '#f39c12' : '#27ae60';
        
        // Validate on blur
        if (val !== '') {
            validatePassword();
        }
    });

    function validatePassword() {
        const input = document.getElementById('password');
        const value = input.value;
        
        if (value === '') {
            showError('password', 'Password is required');
            return false;
        }
        
        const errors = [];
        if (value.length < 8) errors.push('at least 8 characters');
        if (!/[a-z]/.test(value)) errors.push('one lowercase letter');
        if (!/[A-Z]/.test(value)) errors.push('one uppercase letter');
        if (!/\d/.test(value)) errors.push('one number');
        if (!/[^a-zA-Z\d]/.test(value)) errors.push('one special character');
        
        if (errors.length > 0) {
            showError('password', 'Password must contain ' + errors.join(', '));
            return false;
        }
        
        const weak = ['password', '12345678', 'qwerty123', 'admin123'];
        if (weak.includes(value.toLowerCase())) {
            showError('password', 'Password is too common');
            return false;
        }
        
        showSuccess('password');
        return true;
    }

    // Confirm password validation
    function validateConfirmPassword() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if (confirm === '') {
            showError('confirm_password', 'Please confirm your password');
            return false;
        } else if (password !== confirm) {
            showError('confirm_password', 'Passwords do not match');
            return false;
        }
        
        showSuccess('confirm_password');
        return true;
    }

    // Add event listeners
    document.getElementById('first_name').addEventListener('blur', () => validateName('first_name', 'First name'));
    document.getElementById('last_name').addEventListener('blur', () => validateName('last_name', 'Last name'));
    document.getElementById('email').addEventListener('blur', validateEmail);
    document.getElementById('phone').addEventListener('blur', validatePhone);
    document.getElementById('password').addEventListener('blur', validatePassword);
    document.getElementById('confirm_password').addEventListener('blur', validateConfirmPassword);
    document.getElementById('confirm_password').addEventListener('input', function() {
        if (this.value !== '') validateConfirmPassword();
    });

    // Form submission
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        const validations = [
            validateName('first_name', 'First name'),
            validateName('last_name', 'Last name'),
            validateEmail(),
            validatePhone(),
            validatePassword(),
            validateConfirmPassword()
        ];
        
        // Check terms
        const terms = document.getElementById('terms');
        const termsError = document.getElementById('terms_error');
        if (!terms.checked) {
            termsError.textContent = 'You must agree to the Terms of Service and Privacy Policy';
            termsError.classList.add('show');
            validations.push(false);
        } else {
            termsError.classList.remove('show');
        }
        
        // Submit if all valid
        if (validations.every(v => v !== false)) {
            this.submit();
        } else {
            // Scroll to first error
            const firstError = document.querySelector('.invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    </script>

<?php include 'includes/footer.php'; ?>