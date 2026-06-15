<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser($pdo);
$account_type = $user['account_type'];
$config = getAccountConfig($account_type);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, country = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $country, $user['id']]);
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $success = "Profile updated successfully!";
        $user = getCurrentUser($pdo);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --card-bg: #ffffff;
            --border-radius: 16px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
        }

        .app-wrapper { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width);
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(12px);
            color: white;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 32px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .account-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
        }

        .sidebar-nav { flex: 1; padding: 24px 16px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin: 4px 0;
            color: var(--gray-300);
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
            font-weight: 500;
        }
        .nav-item i { width: 24px; font-size: 1.1rem; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }

        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            margin-bottom: 12px;
        }
        .user-info i { font-size: 42px; color: var(--primary-light); }
        .user-name { font-weight: 600; font-size: 14px; }
        .user-email { font-size: 11px; opacity: 0.7; }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            text-decoration: none;
            border-radius: 10px;
        }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.4); color: #fecaca; }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            padding: 24px 32px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .mobile-menu-toggle {
            display: none;
            background: var(--card-bg);
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
            border-radius: 12px;
        }
        .header-actions { display: flex; gap: 16px; align-items: center; }
        .currency-display {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--card-bg);
            border-radius: 40px;
            color: var(--primary);
            font-weight: 600;
            font-size: 13px;
        }
        .notifications {
            background: var(--card-bg);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
        }
        .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
        }

        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: var(--gray-800); }
        .page-header h1 i { background: linear-gradient(135deg, var(--primary), #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-right: 8px; }

        .profile-card {
            max-width: 600px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .profile-avatar i { font-size: 50px; color: white; }
        .profile-name { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .profile-email { font-size: 14px; opacity: 0.9; }
        .profile-body { padding: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700); font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: 10px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white; padding: 12px 28px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4); }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; transition: transform 0.3s ease; }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-menu-toggle { display: block; }
            .main-content { margin-left: 0; padding: 20px; }
            .profile-card { margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar - NO TOGGLE BUTTON -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-bolt"></i> ZENO</h2>
                <div class="account-badge">
                    <i class="fas <?php echo $config['icon']; ?>"></i> 
                    <?php echo ucwords(str_replace('_', ' ', $account_type)); ?>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="create_invoice.php" class="nav-item"><i class="fas fa-plus-circle"></i><span>Create Invoice</span></a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i><span>Invoices</span></a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i><span>Clients</span></a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
                <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
                <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i><span>Profile</span></a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-actions">
                    <div class="currency-display"><i class="fas fa-dollar-sign"></i><span>USD/KES: 130.50</span></div>
                    <div class="notifications"><i class="fas fa-bell"></i><span class="badge">3</span></div>
                </div>
            </div>

            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            </div>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="profile-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">✅ <?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-error">❌ <?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: var(--gray-100);">
                            <small style="color: var(--gray-500);">Email cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <select name="country">
                                <option value="">Select Country</option>
                                <option value="Kenya" <?php echo ($user['country'] ?? '') == 'Kenya' ? 'selected' : ''; ?>>Kenya</option>
                                <option value="Nigeria" <?php echo ($user['country'] ?? '') == 'Nigeria' ? 'selected' : ''; ?>>Nigeria</option>
                                <option value="South Africa" <?php echo ($user['country'] ?? '') == 'South Africa' ? 'selected' : ''; ?>>South Africa</option>
                                <option value="Ghana" <?php echo ($user['country'] ?? '') == 'Ghana' ? 'selected' : ''; ?>>Ghana</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('mobile-open')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    </script>
</body>
</html>