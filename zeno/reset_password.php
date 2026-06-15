<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
if (empty($token)) {
    header("Location: forgot_password.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Invalid or expired reset link. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$password_hash, $user['id']]);
        
        $_SESSION['reset_success'] = "Password reset successfully! Please login with your new password.";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { max-width: 450px; width: 100%; }
        .card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .card-header { text-align: center; margin-bottom: 30px; }
        .card-header i { font-size: 50px; color: #2c5282; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn-primary { width: 100%; padding: 12px; background: #2c5282; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h1>Reset Password</h1>
                <p style="color: #718096;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!$error && $user): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required autocomplete="off">
                </div>
                <button type="submit" class="btn-primary">Reset Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>