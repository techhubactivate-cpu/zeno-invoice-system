<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $admin_key = trim($_POST['admin_key'] ?? '');
    
    $SECRET_KEY = 'Zeno@2024!Admin#Secure';
    
    if (empty($email) || empty($password) || empty($admin_key)) {
        $error = "All fields are required.";
    } elseif ($admin_key !== $SECRET_KEY) {
        $error = "Invalid admin security key.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['role'] = 'admin';
            
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .login-box{max-width:400px;width:100%;background:#1e293b;border-radius:20px;padding:2.5rem;box-shadow:0 25px 60px rgba(0,0,0,0.5);border:1px solid #334155}
        .shield{text-align:center;font-size:3rem;margin-bottom:0.5rem}
        h1{color:#f59e0b;text-align:center;font-size:1.4rem;margin-bottom:0.3rem}
        p{color:#94a3b8;text-align:center;font-size:0.85rem;margin-bottom:1.5rem}
        .form-group{margin-bottom:1.2rem}
        .form-group label{display:block;color:#94a3b8;margin-bottom:0.3rem;font-size:0.85rem}
        .form-group input{width:100%;padding:0.8rem;background:#0f172a;border:1px solid #334155;border-radius:10px;color:white;font-family:inherit;font-size:1rem}
        .form-group input:focus{outline:none;border-color:#f59e0b}
        .btn-login{width:100%;padding:0.9rem;background:linear-gradient(135deg,#dc2626,#991b1b);color:white;border:none;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer}
        .btn-login:hover{background:linear-gradient(135deg,#ef4444,#dc2626)}
        .alert{padding:0.8rem;border-radius:10px;margin-bottom:1rem;font-size:0.85rem}
        .alert-error{background:#fef2f2;color:#991b1b}
        .back-link{text-align:center;margin-top:1rem}.back-link a{color:#64748b;text-decoration:none;font-size:0.8rem}
        .key-hint{background:#1e293b;border:1px dashed #334155;padding:0.8rem;border-radius:8px;text-align:center;margin-bottom:1.5rem}
        .key-hint code{color:#f59e0b;font-size:0.85rem}
    </style>
</head>
<body>
    <div class="login-box">
        <div class="shield">🛡️</div>
        <h1>Zeno Admin Portal</h1>
        <p>Restricted Access</p>
        <div class="key-hint"><small style="color:#94a3b8;">Security Key:</small><br><code>Zeno@2024!Admin#Secure</code></div>
        <?php if($error): ?><div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group"><label>Admin Email</label><input type="email" name="email" placeholder="admin@zeno.com" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="••••••••" required></div>
            <div class="form-group"><label>Security Key</label><input type="password" name="admin_key" placeholder="Enter admin security key" required></div>
            <button type="submit" name="admin_login" class="btn-login">🔒 Access Admin Panel</button>
        </form>
        <div class="back-link"><a href="login.php">← User Login</a> | <a href="index.php">Home</a></div>
    </div>
</body>
</html>