<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all payments - simplified query
$payments = [];
try {
    // Check if payments table has data
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll();
} catch(PDOException $e) {
    $payments = [];
}

// Get paid invoices instead (since payments table might be empty)
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? AND status = 'paid' ORDER BY updated_at DESC");
$stmt->execute([$user_id]);
$paid_invoices = $stmt->fetchAll();

// Calculate stats
$total_received = 0;
foreach ($payments as $p) { $total_received += $p['amount']; }
foreach ($paid_invoices as $inv) { $total_received += $inv['amount']; }

$user = getCurrentUser($pdo);
$account_type = $user['account_type'];
$config = getAccountConfig($account_type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
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
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
        }

        .app-wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
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
            transition: all 0.2s;
        }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.4); color: #fecaca; }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
            padding: 24px 32px;
            overflow-y: auto;
        }

        .main-content::-webkit-scrollbar { width: 8px; }
        .main-content::-webkit-scrollbar-track { background: var(--gray-200); border-radius: 4px; }
        .main-content::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }

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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-800);
        }
        .page-header h1 i {
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 8px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }
        .stat-card .label { font-size: 13px; color: var(--gray-500); margin-bottom: 4px; }
        .stat-card .value { font-size: 28px; font-weight: 800; color: var(--gray-800); }
        .stat-card i { font-size: 32px; opacity: 0.5; }

        .table-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow-x: auto;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 16px 20px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-50); font-weight: 600; color: var(--gray-600); font-size: 13px; }
        tr:hover { background: var(--gray-50); }

        .status-badge {
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.paid { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fed7aa; color: #9a3412; }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--gray-500);
        }
        .empty-state i { font-size: 64px; color: var(--gray-300); margin-bottom: 16px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .mobile-menu-toggle { display: block; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-bolt"></i> ZENO</h2>
                <div class="account-badge">
                    <i class="fas <?php echo $config['icon']; ?>"></i> 
                    <?php echo ucwords(str_replace('_', ' ', $account_type)); ?>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="create_invoice.php" class="nav-item"><i class="fas fa-plus-circle"></i> Create Invoice</a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
                <a href="payments.php" class="nav-item active"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-actions">
                    <div class="currency-display">
                        <i class="fas fa-dollar-sign"></i>
                        <span>USD/KES: 130.50</span>
                    </div>
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> Payment History</h1>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div><div class="label">Total Received</div><div class="value">$<?php echo number_format($total_received, 2); ?></div></div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-card">
                    <div><div class="label">Paid Invoices</div><div class="value"><?php echo count($paid_invoices); ?></div></div>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

            <!-- Paid Invoices Table -->
            <div class="table-container">
                <?php if (count($paid_invoices) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Paid Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_invoices as $inv): ?>
                            <tr>
                                <td><a href="view_invoice.php?id=<?php echo $inv['id']; ?>"><?php echo $inv['invoice_number']; ?></a></td>
                                <td><?php echo htmlspecialchars($inv['client_name']); ?></td>
                                <td>$<?php echo number_format($inv['amount'], 2); ?></td>
                                <td><?php echo $inv['currency']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($inv['paid_at'] ?? $inv['updated_at'] ?? $inv['created_at'])); ?></td>
                                <td><span class="status-badge paid">Paid</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-credit-card"></i>
                        <h3>No payments yet</h3>
                        <p style="margin-top: 8px;">When clients pay your invoices, they'll appear here</p>
                        <a href="create_invoice.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Create Invoice →</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>