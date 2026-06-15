<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all invoices
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at ASC");
$stmt->execute([$user_id]);
$invoices = $stmt->fetchAll();

// Calculate statistics
$total_revenue = 0;
$paid_amount = 0;
$pending_amount = 0;
$overdue_amount = 0;

foreach ($invoices as $invoice) {
    $amount = floatval($invoice['amount']);
    $total_revenue += $amount;
    if ($invoice['status'] == 'paid') $paid_amount += $amount;
    elseif ($invoice['status'] == 'pending') $pending_amount += $amount;
    elseif ($invoice['status'] == 'overdue') $overdue_amount += $amount;
}

// Monthly data
$monthly_data = [];
$current_year = date('Y');
for ($i = 1; $i <= 12; $i++) {
    $monthly_data[$i] = ['month' => date('M', mktime(0,0,0,$i,1)), 'revenue' => 0];
}
foreach ($invoices as $invoice) {
    $month = date('n', strtotime($invoice['created_at']));
    if (date('Y', strtotime($invoice['created_at'])) == $current_year) {
        $monthly_data[$month]['revenue'] += floatval($invoice['amount']);
    }
}

// Status distribution
$status_counts = ['paid' => 0, 'pending' => 0, 'draft' => 0, 'sent' => 0];
foreach ($invoices as $invoice) {
    $status = $invoice['status'];
    if (isset($status_counts[$status])) $status_counts[$status]++;
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
    <title>Analytics - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .page-header { margin-bottom: 32px; }
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

        .stats-grid {
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

        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        .chart-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }
        .chart-card h3 { margin-bottom: 20px; color: var(--gray-800); font-size: 18px; }
        .chart-card h3 i { margin-right: 8px; color: var(--primary); }
        canvas { max-height: 280px; width: 100%; }

        .empty-state {
            text-align: center;
            padding: 60px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            color: var(--gray-500);
        }
        .empty-state i { font-size: 64px; color: var(--gray-300); margin-bottom: 16px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .mobile-menu-toggle { display: block; }
            .main-content { margin-left: 0; padding: 20px; }
            .charts-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i> Analytics</a>
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
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                <p style="color: var(--gray-500);">Track your business performance</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div><div class="label">Total Revenue</div><div class="value">$<?php echo number_format($total_revenue, 2); ?></div></div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-card">
                    <div><div class="label">Paid Amount</div><div class="value">$<?php echo number_format($paid_amount, 2); ?></div></div>
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-card">
                    <div><div class="label">Pending</div><div class="value">$<?php echo number_format($pending_amount, 2); ?></div></div>
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            <!-- Charts -->
            <?php if (count($invoices) > 0): ?>
            <div class="charts-row">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Monthly Revenue (<?php echo $current_year; ?>)</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Invoice Status Distribution</h3>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <h3>No data yet</h3>
                <p style="margin-top: 8px;">Create invoices to see analytics</p>
                <a href="create_invoice.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Create Invoice →</a>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <?php if (count($invoices) > 0): ?>
    <script>
        // Monthly Revenue Chart
        const monthlyLabels = [<?php for ($i = 1; $i <= 12; $i++) echo "'" . $monthly_data[$i]['month'] . "',"; ?>];
        const monthlyRevenue = [<?php for ($i = 1; $i <= 12; $i++) echo $monthly_data[$i]['revenue'] . ","; ?>];
        
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: monthlyRevenue,
                    backgroundColor: '#4f46e5',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
        });
        
        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Draft', 'Sent'],
                datasets: [{
                    data: [<?php echo $status_counts['paid']; ?>, <?php echo $status_counts['pending']; ?>, <?php echo $status_counts['draft']; ?>, <?php echo $status_counts['sent']; ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#9ca3af', '#818cf8']
                }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'right' } } }
        });
    </script>
    <?php endif; ?>

    <script>
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>