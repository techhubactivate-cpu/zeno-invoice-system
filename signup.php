<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// African countries with dial codes
$african_countries = [
    'Kenya' => ['code' => '+254', 'flag' => '🇰🇪', 'format' => '712345678', 'length' => 9],
    'Nigeria' => ['code' => '+234', 'flag' => '🇳🇬', 'format' => '8123456789', 'length' => 10],
    'South Africa' => ['code' => '+27', 'flag' => '🇿🇦', 'format' => '712345678', 'length' => 9],
    'Ghana' => ['code' => '+233', 'flag' => '🇬🇭', 'format' => '501234567', 'length' => 9],
    'Egypt' => ['code' => '+20', 'flag' => '🇪🇬', 'format' => '1234567890', 'length' => 10],
    'Morocco' => ['code' => '+212', 'flag' => '🇲🇦', 'format' => '612345678', 'length' => 9],
    'Tanzania' => ['code' => '+255', 'flag' => '🇹🇿', 'format' => '712345678', 'length' => 9],
    'Uganda' => ['code' => '+256', 'flag' => '🇺🇬', 'format' => '712345678', 'length' => 9],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup_submit'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please refresh the page and try again.";
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $country_code = $african_countries[$country]['code'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');
        $phone = !empty($phone_number) ? $country_code . $phone_number : '';
        $account_type = trim($_POST['account_type'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms_checkbox']);
        
        // Validation
        if (empty($first_name) || empty($last_name)) {
            $error = "First name and last name are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (empty($country)) {
            $error = "Please select your country.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one number.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!$terms) {
            $error = "You must agree to the Terms of Service.";
        } else {
            // Check if email exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $error = "Email already registered. Please login instead.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = generateSecureToken(32);
                
                $sql = "INSERT INTO users (first_name, last_name, email, phone, country, account_type, password_hash, verification_token, status, role, approval_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'user', 'pending')";
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$first_name, $last_name, $email, $phone, $country, $account_type, $password_hash, $verification_token])) {
                    // Log registration
                    logActivity($pdo, null, 'user_registration', "New user registered: $email");
                    
                    $_SESSION['signup_message'] = "Account created successfully! An admin will review and approve your account shortly.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .signup-container { max-width: 600px; width: 100%; animation: slideUp 0.5s ease; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .signup-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .signup-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 40px; text-align: center; }
        .signup-header i { font-size: 50px; margin-bottom: 15px; }
        .signup-header h1 { font-size: 28px; margin-bottom: 8px; }
        .signup-header p { opacity: 0.9; font-size: 14px; }
        .signup-body { padding: 40px; }
        .alert { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        .label-required::after { content: " *"; color: #e53e3e; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
        .input-group input, .input-group select {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0; border-radius: 10px;
            transition: border-color 0.2s;
            background: white;
        }
        .input-group input:focus, .input-group select:focus { outline: none; border-color: #2a5298; }
        .phone-group { display: flex; align-items: center; gap: 10px; }
        .country-code-display {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            font-weight: 600;
            color: #2a5298;
            min-width: 80px;
            text-align: center;
        }
        .password-strength { margin-top: 8px; height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s, background 0.3s; }
        .strength-text { font-size: 11px; margin-top: 5px; color: #718096; }
        .terms { margin: 20px 0; }
        .checkbox-label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: #4a5568; }
        .checkbox-label a { color: #2a5298; text-decoration: none; }
        .btn-signup {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: transform 0.2s;
        }
        .btn-signup:hover { transform: translateY(-2px); }
        .login-link { text-align: center; margin-top: 25px; color: #718096; }
        .login-link a { color: #2a5298; text-decoration: none; font-weight: 600; }
        .phone-hint { font-size: 11px; color: #718096; margin-top: 5px; }
        @media (max-width: 480px) {
            .signup-header { padding: 30px; }
            .signup-body { padding: 30px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .phone-group { flex-direction: column; }
            .country-code-display { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <i class="fas fa-bolt"></i>
                <h1>Create Account</h1>
                <p>Join African freelancers getting paid globally</p>
            </div>
            
            <div class="signup-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form action="signup.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="label-required">First Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="first_name" required autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="label-required">Last Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="last_name" required autocomplete="off">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-required">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-required">Country</label>
                        <div class="input-group">
                            <i class="fas fa-map-marker-alt"></i>
                            <select name="country" id="country" required>
                                <option value="">Select Country</option>
                                <?php foreach ($african_countries as $name => $data): ?>
                                    <option value="<?php echo $name; ?>" data-code="<?php echo $data['code']; ?>">
                                        <?php echo $data['flag'] . ' ' . $name . ' (' . $data['code'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="phone-group">
                            <div class="country-code-display" id="countryCodeDisplay">+254</div>
                            <div class="phone-number">
                                <div class="input-group">
                                    <i class="fas fa-mobile-alt"></i>
                                    <input type="tel" name="phone_number" id="phone_number" placeholder="712345678" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="phone-hint" id="phone_hint">
                            <i class="fas fa-info-circle"></i> Enter phone number without country code
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-required">I am a</label>
                        <div class="input-group">
                            <i class="fas fa-briefcase"></i>
                            <select name="account_type" required>
                                <option value="">Select your profession</option>
                                <option value="freelancer">Freelancer</option>
                                <option value="creative">Creative (Designer, Writer, Artist)</option>
                                <option value="developer">Software Developer</option>
                                <option value="consultant">Consultant</option>
                                <option value="agency">Digital Agency</option>
                                <option value="small_business">Small Business Owner</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-required">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" required autocomplete="new-password">
                        </div>
                        <div class="password-strength"><div class="strength-bar" id="strengthBar"></div></div>
                        <div class="strength-text" id="strengthText"></div>
                        <small style="color: #718096;">Minimum 8 chars, 1 uppercase, 1 number</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-required">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-check-circle"></i>
                            <input type="password" name="confirm_password" id="confirmPassword" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="terms">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms_checkbox" required>
                            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                        </label>
                    </div>
                    
                    <button type="submit" name="signup_submit" value="1" class="btn-signup">
                        <i class="fas fa-user-plus"></i> Create Free Account
                    </button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Country code update
        const countrySelect = document.getElementById('country');
        const countryCodeDisplay = document.getElementById('countryCodeDisplay');
        const countryCodes = {
            'Kenya': '+254', 'Nigeria': '+234', 'South Africa': '+27', 'Ghana': '+233',
            'Egypt': '+20', 'Morocco': '+212', 'Tanzania': '+255', 'Uganda': '+256'
        };
        countrySelect.addEventListener('change', function() {
            const code = countryCodes[this.value] || '+254';
            countryCodeDisplay.textContent = code;
        });
        
        // Password strength
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            const width = (strength / 5) * 100;
            strengthBar.style.width = width + '%';
            
            if (strength <= 2) {
                strengthBar.style.background = '#e53e3e';
                strengthText.textContent = 'Weak password';
            } else if (strength <= 4) {
                strengthBar.style.background = '#ed8936';
                strengthText.textContent = 'Medium password';
            } else {
                strengthBar.style.background = '#48bb78';
                strengthText.textContent = 'Strong password';
            }
        });
        
        // Password match validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = document.getElementById('confirmPassword').value;
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>