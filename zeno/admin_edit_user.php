<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$edit_user = $stmt->fetch();

if (!$edit_user) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $account_type = $_POST['account_type'];
    $role = $_POST['role'];
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, account_type = ?, role = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $email, $account_type, $role, $user_id])) {
            $success = "User updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $edit_user = $stmt->fetch();
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'edit_user', ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], "Edited user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
        } else {
            $error = "Failed to update user.";
        }
    }
}

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin | Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f0f1a; }
        
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .admin-sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-sidebar-header h2 { font-size: 24px; color: #fff; }
        .admin-badge { background: #e53e3e; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; margin-top: 8px; }
        .admin-sidebar-nav { flex: 1; padding: 20px; }
        .admin-nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin: 5px 0;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
        }
        .admin-nav-item i { width: 24px; margin-right: 12px; }
        .admin-nav-item:hover, .admin-nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .admin-user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 10px; }
        .admin-user-info i { font-size: 40px; }
        .admin-logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; background: #dc2626; color: white; text-decoration: none; border-radius: 8px; }
        
        .admin-main { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .admin-top-bar { background: #1a1a2e; padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .admin-top-bar h2 { color: white; font-size: 20px; }
        
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #10b98120; color: #10b981; border: 1px solid #10b98140; }
        .alert-error { background: #dc262620; color: #dc2626; border: 1px solid #dc262640; }
        
        .edit-card {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 25px;
            max-width: 600px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #9ca3af; font-weight: 600; font-size: 13px; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            background: #0f0f1a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #4f46e5; }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
        }
        .btn-secondary {
            background: #4b5563;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .edit-card { margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2><i class="fas fa-bolt"></i> ZENO ADMIN</h2>
            <p class="admin-badge">Administrator</p>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="admin_dashboard.php" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_users.php" class="admin-nav-item"><i class="fas fa-users"></i> Users</a>
            <a href="admin_invoices.php" class="admin-nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="admin_payments.php" class="admin-nav-item"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_settings.php" class="admin-nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_broadcast.php" class="admin-nav-item"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
            <a href="admin_logs.php" class="admin-nav-item"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_backup.php" class="admin-nav-item"><i class="fas fa-database"></i> Backup</a>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <i class="fas fa-user-shield"></i>
                <div>
                    <p><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <small><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                </div>
            </div>
            <a href="logout.php" class="admin-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <main class="admin-main">
        <div class="admin-top-bar">
            <h2><i class="fas fa-edit"></i> Edit User</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="edit-card">
            <form method="POST">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($edit_user['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type">
                        <option value="freelancer" <?php echo $edit_user['account_type'] == 'freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                        <option value="creative" <?php echo $edit_user['account_type'] == 'creative' ? 'selected' : ''; ?>>Creative</option>
                        <option value="developer" <?php echo $edit_user['account_type'] == 'developer' ? 'selected' : ''; ?>>Developer</option>
                        <option value="consultant" <?php echo $edit_user['account_type'] == 'consultant' ? 'selected' : ''; ?>>Consultant</option>
                        <option value="agency" <?php echo $edit_user['account_type'] == 'agency' ? 'selected' : ''; ?>>Agency</option>
                        <option value="small_business" <?php echo $edit_user['account_type'] == 'small_business' ? 'selected' : ''; ?>>Small Business</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="admin_dashboard.php" class="btn-secondary">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>