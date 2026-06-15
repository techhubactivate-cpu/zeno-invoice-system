<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser($pdo);
$account_type = $user['account_type'];
$config = getAccountConfig($account_type);

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_clients FROM clients WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_clients = $stmt->fetch()['total_clients'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_invoices FROM invoices WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_invoices = $stmt->fetch()['total_invoices'];

$stmt = $pdo->prepare("SELECT SUM(amount) as total_revenue FROM invoices WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$user['id']]);
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Zeno</title>
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
            transition: all 0.2s;
            font-weight: 500;
        }
        .nav-item i { width: 24px; }
        .nav-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--border-radius);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card .label { font-size: 14px; color: var(--gray-500); margin-bottom: 8px; }
        .stat-card .value { font-size: 32px; font-weight: 800; color: var(--gray-800); }
        .stat-card i { font-size: 48px; opacity: 0.3; color: var(--primary); }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 32px;
            border-radius: var(--border-radius);
            margin-bottom: 32px;
        }
        .welcome-card h2 { font-size: 24px; margin-bottom: 8px; }
        .welcome-card p { opacity: 0.9; }
        .quick-tip {
            background: rgba(255,255,255,0.2);
            padding: 12px 20px;
            border-radius: 12px;
            margin-top: 16px;
            display: inline-block;
        }

        .recent-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }
        .recent-card h3 { margin-bottom: 16px; color: var(--gray-800); }
        .recent-card h3 i { color: var(--primary); margin-right: 8px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; transition: transform 0.3s ease; }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-menu-toggle { display: block; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-bolt"></i> ZENO</h2>
                <div class="account-badge">
                    <i class="fas <?php echo $config['icon']; ?>"></i> 
                    <?php echo ucwords(str_replace('_', ' ', $account_type)); ?>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            </div>

            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h2>
                <p><?php echo $config['greeting']; ?></p>
                <div class="quick-tip">
                    <i class="fas fa-lightbulb"></i> 💡 <?php echo $config['quick_tip']; ?>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div><div class="label">Total Clients</div><div class="value"><?php echo $total_clients; ?></div></div>
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card">
                    <div><div class="label">Total Invoices</div><div class="value"><?php echo $total_invoices; ?></div></div>
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-card">
                    <div><div class="label">Revenue Received</div><div class="value">$<?php echo number_format($total_revenue, 2); ?></div></div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>

            <div class="recent-card">
                <h3><i class="fas fa-clock"></i> Quick Actions</h3>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <a href="create_invoice.php" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none;"><i class="fas fa-plus"></i> New Invoice</a>
                    <a href="clients.php" style="background: var(--gray-100); color: var(--gray-700); padding: 12px 24px; border-radius: 12px; text-decoration: none;"><i class="fas fa-user-plus"></i> Add Client</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
