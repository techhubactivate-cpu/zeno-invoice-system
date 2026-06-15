<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get real statistics from database
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoices WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_invoices = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$user_id]);
$total_earned = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE user_id = ? AND status IN ('draft', 'sent', 'viewed', 'pending')");
$stmt->execute([$user_id]);
$pending_amount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT client_email) as total FROM invoices WHERE user_id = ? AND client_email IS NOT NULL AND client_email != ''");
$stmt->execute([$user_id]);
$active_clients = $stmt->fetchColumn();

// Get recent invoices
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_invoices = $stmt->fetchAll();

// Get currency totals
$stmt = $pdo->prepare("SELECT currency, COALESCE(SUM(amount), 0) as total FROM invoices WHERE user_id = ? AND status = 'paid' GROUP BY currency");
$stmt->execute([$user_id]);
$currency_totals = $stmt->fetchAll();

$usd_total = 0;
$eur_total = 0;
$gbp_total = 0;
$kes_total = 0;

foreach ($currency_totals as $currency) {
    switch ($currency['currency']) {
        case 'USD': $usd_total = $currency['total']; break;
        case 'EUR': $eur_total = $currency['total']; break;
        case 'GBP': $gbp_total = $currency['total']; break;
        case 'KES': $kes_total = $currency['total']; break;
    }
}

$user = getCurrentUser($pdo);
$account_type = $user['account_type'];
$config = getAccountConfig($account_type);
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Fixed, No Toggle Button */
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
            overflow-x: hidden;
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

        .sidebar-nav {
            flex: 1;
            padding: 24px 16px;
        }

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

        .nav-item i {
            width: 24px;
            font-size: 1.1rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .user-info i {
            font-size: 42px;
            color: var(--primary-light);
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-email {
            font-size: 11px;
            opacity: 0.7;
        }

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
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #fecaca;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            padding: 24px 32px;
        }

        /* Top Header */
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

        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

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

        .welcome-section {
            margin-bottom: 32px;
        }

        .welcome-section h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: 8px;
        }

        .highlight {
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profession-banner {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(6, 182, 212, 0.1));
            padding: 16px 24px;
            border-radius: var(--border-radius);
            margin-top: 12px;
            border-left: 4px solid var(--primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
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
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-info h3 {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 26px;
            color: white;
        }

        .quick-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: var(--card-bg);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            transform: translateY(-2px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
        }

        .invoices-table-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow-x: auto;
            box-shadow: var(--shadow-md);
            margin-bottom: 32px;
            border: 1px solid var(--gray-200);
        }

        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .invoices-table th,
        .invoices-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .invoices-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-600);
            font-size: 13px;
        }

        .invoices-table tr:hover {
            background: var(--gray-50);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background: #fed7aa;
            color: #9a3412;
        }

        .status-badge.draft {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        .status-badge.sent {
            background: #bfdbfe;
            color: #1e40af;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin: 0 4px;
            color: var(--gray-400);
            border-radius: 8px;
            transition: var(--transition);
        }

        .icon-btn:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .info-card h3 {
            margin-bottom: 20px;
            color: var(--gray-800);
            font-size: 18px;
            font-weight: 600;
        }

        .info-card h3 i {
            margin-right: 8px;
            color: var(--primary);
        }

        .currency-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .currency-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 12px;
        }

        .currency-name {
            font-weight: 600;
            color: var(--primary);
        }

        .currency-total {
            font-weight: 600;
            color: var(--gray-800);
        }

        .quick-tip {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border-left: 4px solid var(--warning);
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
        }

        .features-list {
            list-style: none;
            margin-top: 16px;
        }

        .features-list li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features-list li i {
            color: var(--success);
        }

        .empty-invoices {
            text-align: center;
            padding: 60px;
            color: var(--gray-500);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                transition: transform 0.3s ease;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .mobile-menu-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .two-columns {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                flex-direction: column;
            }
            .btn-primary, .btn-secondary {
                justify-content: center;
            }
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
                <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="create_invoice.php" class="nav-item"><i class="fas fa-plus-circle"></i><span>Create Invoice</span></a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i><span>Invoices</span></a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i><span>Clients</span></a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
                <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profile</span></a>
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

        <!-- Main Content -->
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

            <div class="welcome-section">
                <h1>Welcome back, <span class="highlight"><?php echo htmlspecialchars($user['first_name']); ?></span> 👋</h1>
                <div class="profession-banner">
                    <i class="fas <?php echo $config['icon']; ?>"></i>
                    <strong><?php echo $config['greeting']; ?></strong>
                </div>
            </div>

            <div class="stats-grid">
                <a href="invoices.php" class="stat-card">
                    <div class="stat-info"><h3>Total Invoices</h3><p class="stat-number"><?php echo $total_invoices; ?></p></div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4f46e5, #818cf8);"><i class="fas fa-file-invoice"></i></div>
                </a>
                <a href="payments.php" class="stat-card">
                    <div class="stat-info"><h3>Total Earned</h3><p class="stat-number">$<?php echo number_format($total_earned, 2); ?></p></div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);"><i class="fas fa-check-circle"></i></div>
                </a>
                <a href="invoices.php?status=pending" class="stat-card">
                    <div class="stat-info"><h3>Pending</h3><p class="stat-number">$<?php echo number_format($pending_amount, 2); ?></p></div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);"><i class="fas fa-clock"></i></div>
                </a>
                <a href="clients.php" class="stat-card">
                    <div class="stat-info"><h3>Active Clients</h3><p class="stat-number"><?php echo $active_clients; ?></p></div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);"><i class="fas fa-users"></i></div>
                </a>
            </div>

            <div class="quick-actions">
                <a href="create_invoice.php" class="btn-primary"><i class="fas fa-plus"></i> Create New Invoice</a>
                <a href="clients.php" class="btn-secondary"><i class="fas fa-user-plus"></i> Manage Clients</a>
            </div>

            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Invoices</h2>
                <a href="invoices.php" class="view-all">View All →</a>
            </div>

            <div class="invoices-table-container">
                <?php if (count($recent_invoices) > 0): ?>
                    <table class="invoices-table">
                        <thead><tr><th>Invoice #</th><th>Client</th><th>Amount</th><th>Currency</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_invoices as $invoice): ?>
                            <tr>
                                <td><a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" style="font-weight:600;color:var(--primary);text-decoration:none;"><?php echo htmlspecialchars($invoice['invoice_number']); ?></a></td>
                                <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                <td><?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?></td>
                                <td><?php echo $invoice['currency']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                <td><span class="status-badge <?php echo $invoice['status']; ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                                <td>
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="icon-btn"><i class="fas fa-eye"></i></a>
                                    <a href="download_pdf.php?id=<?php echo $invoice['id']; ?>" class="icon-btn"><i class="fas fa-download"></i></a>
                                    <a href="send_invoice_email.php?id=<?php echo $invoice['id']; ?>" class="icon-btn"><i class="fas fa-envelope"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-invoices"><i class="fas fa-file-invoice" style="font-size:64px;color:var(--gray-300);margin-bottom:16px;"></i><p>No invoices yet</p><a href="create_invoice.php" class="btn-primary" style="margin-top:16px;display:inline-block;">Create Your First Invoice →</a></div>
                <?php endif; ?>
            </div>

            <div class="two-columns">
                <div class="info-card">
                    <h3><i class="fas fa-chart-line"></i> Currency Overview</h3>
                    <div class="currency-list">
                        <?php if ($usd_total > 0): ?><div class="currency-item"><span class="currency-name">USD</span><span class="currency-total">$<?php echo number_format($usd_total, 2); ?></span></div><?php endif; ?>
                        <?php if ($eur_total > 0): ?><div class="currency-item"><span class="currency-name">EUR</span><span class="currency-total">€<?php echo number_format($eur_total, 2); ?></span></div><?php endif; ?>
                        <?php if ($gbp_total > 0): ?><div class="currency-item"><span class="currency-name">GBP</span><span class="currency-total">£<?php echo number_format($gbp_total, 2); ?></span></div><?php endif; ?>
                        <?php if ($kes_total > 0): ?><div class="currency-item"><span class="currency-name">KES</span><span class="currency-total">KES <?php echo number_format($kes_total, 2); ?></span></div><?php endif; ?>
                        <?php if ($usd_total == 0 && $eur_total == 0 && $gbp_total == 0 && $kes_total == 0): ?><div style="text-align:center;padding:20px;"><i class="fas fa-chart-simple"></i><p>No earnings yet</p></div><?php endif; ?>
                    </div>
                </div>
               
            </div>
        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        // Close mobile sidebar when clicking outside
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