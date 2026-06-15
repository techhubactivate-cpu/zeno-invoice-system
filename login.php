<?php
session_start();
require_once 'config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    // Simple CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please refresh the page.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = "Invalid email or password.";
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = "Invalid email or password.";
            } elseif ($user['approval_status'] !== 'approved') {
                $error = "Your account is pending approval. Please wait for admin approval.";
            } elseif ($user['status'] !== 'active') {
                $error = "Your account has been suspended. Please contact support.";
            } else {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['account_type'] = $user['account_type'];
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
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
    <title>Login - Zeno</title>
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
        .login-container { max-width: 450px; width: 100%; animation: slideUp 0.5s ease; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .login-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 40px; text-align: center; }
        .login-header i { font-size: 50px; margin-bottom: 15px; }
        .login-header h1 { font-size: 28px; margin-bottom: 8px; }
        .login-header p { opacity: 0.9; font-size: 14px; }
        .login-body { padding: 40px; }
        .alert { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
        .input-group input {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0; border-radius: 10px;
            transition: border-color 0.2s;
            background: white;
        }
        .input-group input:focus { outline: none; border-color: #2a5298; }
        .btn-login {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: transform 0.2s;
        }
        .btn-login:hover { transform: translateY(-2px); }
        .signup-link { text-align: center; margin-top: 25px; color: #718096; }
        .signup-link a { color: #2a5298; text-decoration: none; font-weight: 600; }
        .forgot-password { text-align: right; margin-top: 5px; }
        .forgot-password a { color: #718096; text-decoration: none; font-size: 13px; }
        .clear-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
            display: block;
            width: 100%;
            text-align: center;
        }
        .clear-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-bolt"></i>
                <h1>Welcome Back</h1>
                <p>Sign in to your Zeno account</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" required autocomplete="off">
                        </div>
                        <div class="forgot-password">
                            <a href="forgot_password.php">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" name="login_submit" value="1" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <button type="button" class="clear-btn" onclick="clearForm()">
                        <i class="fas fa-eraser"></i> Clear Form
                    </button>
                </form>
                
                <div class="signup-link">
                    Don't have an account? <a href="signup.php">Sign up free</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function clearForm() {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('email').focus();
        }
        
        window.addEventListener('load', function() {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
        });
        
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>