<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // In production, send email with reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/zeno/reset_password.php?token=" . $token;
            
            $success = "Password reset link generated! <br><br> 
                        <strong>For demo purposes, use this link:</strong><br>
                        <a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>
                        (In production, this would be emailed to you)";
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { max-width: 450px; width: 100%; }
        .card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .card-header { text-align: center; margin-bottom: 30px; }
        .card-header i { font-size: 50px; color: #2c5282; margin-bottom: 15px; }
        .card-header h1 { font-size: 24px; color: #1a202c; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn-primary { width: 100%; padding: 12px; background: #2c5282; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { background: #1a365d; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #2c5282; text-decoration: none; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-key"></i>
                <h1>Forgot Password?</h1>
                <p style="color: #718096; margin-top: 10px;">Enter your email to reset your password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required autocomplete="off">
                </div>
                <button type="submit" class="btn-primary">Send Reset Link</button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>