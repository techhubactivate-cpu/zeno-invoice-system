<?php
session_start();
require_once 'config.php';

// IMPORTANT: Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';

// Get current settings from database
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    $settings = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8);
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $setting_key]);
            }
        }
        $message = "Settings saved successfully!";
        $messageType = 'success';
        
        // Refresh settings
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    if (isset($_POST['maintenance_mode'])) {
        $new_mode = $_POST['maintenance_mode'];
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
        $stmt->execute([$new_mode]);
        $message = "Maintenance mode " . ($new_mode == '1' ? 'enabled' : 'disabled');
        $messageType = 'success';
        $settings['maintenance_mode'] = $new_mode;
    }
}

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Settings | Zeno</title>
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
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
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
            transition: all 0.3s;
        }
        .admin-nav-item i { width: 24px; margin-right: 12px; }
        .admin-nav-item:hover, .admin-nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        
        .admin-sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .admin-user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 10px; }
        .admin-user-info i { font-size: 40px; }
        .admin-logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; background: #dc2626; color: white; text-decoration: none; border-radius: 8px; }
        
        .admin-main {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .admin-top-bar {
            background: #1a1a2e;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-top-bar h2 { color: white; font-size: 20px; }
        
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #10b98120; color: #10b981; border: 1px solid #10b98140; }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }
        .settings-card {
            background: #1a1a2e;
            border-radius: 12px;
            overflow: hidden;
        }
        .settings-card-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
        }
        .settings-card-header h2 { font-size: 18px; font-weight: 600; color: white; }
        .settings-card-header h2 i { margin-right: 8px; color: #4f46e5; }
        .settings-card-body { padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #9ca3af; font-size: 13px; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            background: #0f0f1a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #4f46e5; }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }
        .btn-primary:hover { opacity: 0.9; }
        
        .maintenance-toggle {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .maintenance-toggle h3 { color: white; font-size: 16px; }
        .maintenance-toggle p { color: #9ca3af; font-size: 12px; margin-top: 5px; }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #4b5563;
            transition: 0.3s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider { background-color: #4f46e5; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2><i class="fas fa-bolt"></i> ZENO ADMIN</h2>
            <p class="admin-badge"><i class="fas fa-shield-alt"></i> Administrator</p>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="admin_dashboard.php" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_users.php" class="admin-nav-item"><i class="fas fa-users"></i> Users</a>
            <a href="admin_invoices.php" class="admin-nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="admin_payments.php" class="admin-nav-item"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_settings.php" class="admin-nav-item active"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_broadcast.php" class="admin-nav-item"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
            <a href="admin_logs.php" class="admin-nav-item"><i class="fas fa-history"></i> Logs</a>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <i class="fas fa-user-shield"></i>
                <div>
                    <p style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p style="font-size: 11px; opacity: 0.7;"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>
            </div>
            <a href="../logout.php" class="admin-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <main class="admin-main">
        <div class="admin-top-bar">
            <h2><i class="fas fa-cog"></i> System Settings</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Maintenance Mode Toggle -->
        <div class="maintenance-toggle">
            <div>
                <h3><i class="fas fa-tools"></i> Maintenance Mode</h3>
                <p>When enabled, only administrators can access the site</p>
            </div>
            <form method="POST">
                <label class="toggle-switch">
                    <input type="checkbox" name="maintenance_mode" value="1" onchange="this.form.submit()" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </form>
        </div>
        
        <div class="settings-grid">
            <!-- General Settings -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h2><i class="fas fa-globe"></i> General Settings</h2>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="setting_site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Zeno'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="setting_contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'support@zeno.com'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Default Currency</label>
                            <select name="setting_default_currency">
                                <option value="USD" <?php echo ($settings['default_currency'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                <option value="EUR" <?php echo ($settings['default_currency'] ?? 'USD') == 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                <option value="GBP" <?php echo ($settings['default_currency'] ?? 'USD') == 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                                <option value="KES" <?php echo ($settings['default_currency'] ?? 'USD') == 'KES' ? 'selected' : ''; ?>>KES - Kenyan Shilling</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Invoice Prefix</label>
                            <input type="text" name="setting_invoice_prefix" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV'); ?>">
                        </div>
                        <button type="submit" name="save_settings" class="btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
            
            <!-- Fee Settings -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h2><i class="fas fa-percent"></i> Fee Settings</h2>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Platform Fee (%)</label>
                            <input type="number" step="0.1" name="setting_platform_fee_percentage" value="<?php echo htmlspecialchars($settings['platform_fee_percentage'] ?? '2.5'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Fixed Fee ($)</label>
                            <input type="number" step="0.01" name="setting_platform_fee_fixed" value="<?php echo htmlspecialchars($settings['platform_fee_fixed'] ?? '0.50'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Minimum Withdrawal ($)</label>
                            <input type="number" name="setting_min_withdrawal" value="<?php echo htmlspecialchars($settings['min_withdrawal'] ?? '10'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Maximum Withdrawal ($)</label>
                            <input type="number" name="setting_max_withdrawal" value="<?php echo htmlspecialchars($settings['max_withdrawal'] ?? '10000'); ?>">
                        </div>
                        <button type="submit" name="save_settings" class="btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
            
            <!-- Supported Currencies -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h2><i class="fas fa-money-bill-wave"></i> Currency Settings</h2>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Supported Currencies</label>
                            <input type="text" name="setting_supported_currencies" value="<?php echo htmlspecialchars($settings['supported_currencies'] ?? 'USD,EUR,GBP,KES,NGN,GHS'); ?>">
                            <small style="color: #6b7280; display: block; margin-top: 5px;">Comma-separated list of currency codes</small>
                        </div>
                        <button type="submit" name="save_settings" class="btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>