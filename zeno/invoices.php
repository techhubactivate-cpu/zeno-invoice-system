<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get filter
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM invoices WHERE user_id = ?";
$params = [$user_id];

if ($status_filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}
if (!empty($search)) {
    $query .= " AND (client_name LIKE ? OR invoice_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Get summary stats
$total_amount = 0;
$paid_amount = 0;
$pending_amount = 0;
$draft_amount = 0;
foreach ($invoices as $inv) {
    $total_amount += $inv['amount'];
    if ($inv['status'] == 'paid') $paid_amount += $inv['amount'];
    if ($inv['status'] == 'pending') $pending_amount += $inv['amount'];
    if ($inv['status'] == 'draft') $draft_amount += $inv['amount'];
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
    <title>Invoices - Zeno</title>
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
            --secondary: #0f172a;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --card-bg: #ffffff;
            --border-radius: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
        }

        /* Layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Glassmorphism */
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
            transition: all 0.3s ease;
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
            background: linear-gradient(135deg, var(--primary), var(--accent));
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
            transition: all 0.2s;
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
            transition: all 0.2s;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #fecaca;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
            padding: 24px 32px;
            overflow-y: auto;
        }

        /* Scrollbar */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        .main-content::-webkit-scrollbar-track {
            background: var(--gray-200);
            border-radius: 4px;
        }
        .main-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
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
            box-shadow: var(--shadow-sm);
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
            box-shadow: var(--shadow-sm);
        }

        .notifications {
            position: relative;
            background: var(--card-bg);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        .notifications i {
            font-size: 18px;
            color: var(--gray-600);
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
            font-weight: bold;
        }

        /* Page Header */
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
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 8px;
        }

        /* Stats Row */
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
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card .label {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 4px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .stat-card i {
            font-size: 32px;
            opacity: 0.5;
        }

        /* Filters */
        .filters-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 20px;
            background: var(--card-bg);
            border: 1px solid var(--gray-200);
            border-radius: 40px;
            text-decoration: none;
            color: var(--gray-600);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .search-box input {
            padding: 8px 16px;
            border: 1px solid var(--gray-200);
            border-radius: 40px;
            width: 250px;
            font-family: inherit;
            background: var(--card-bg);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .search-box button {
            padding: 8px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 500;
        }

        /* Table */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow-x: auto;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-600);
            font-size: 13px;
        }

        tr:hover {
            background: var(--gray-50);
        }

        .invoice-number {
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .invoice-number:hover {
            text-decoration: underline;
        }

        /* Status Badges */
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

        .status-badge.overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: var(--gray-400);
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }

        .icon-btn:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .mobile-menu-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-row {
                grid-template-columns: 1fr;
            }
            .filters-bar {
                flex-direction: column;
            }
            .search-box {
                margin-left: 0;
                width: 100%;
            }
            .search-box input {
                width: 100%;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
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
                <a href="invoices.php" class="nav-item active"><i class="fas fa-file-invoice"></i> Invoices</a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i> Payments</a>
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
                <h1><i class="fas fa-file-invoice"></i> My Invoices</h1>
                <a href="create_invoice.php" class="btn-primary"><i class="fas fa-plus"></i> Create Invoice</a>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div>
                        <div class="label">Total Invoices</div>
                        <div class="value"><?php echo count($invoices); ?></div>
                    </div>
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="label">Total Amount</div>
                        <div class="value">$<?php echo number_format($total_amount, 2); ?></div>
                    </div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="label">Paid</div>
                        <div class="value">$<?php echo number_format($paid_amount, 2); ?></div>
                    </div>
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="label">Pending</div>
                        <div class="value">$<?php echo number_format($pending_amount, 2); ?></div>
                    </div>
                    <i class="fas fa-clock" style="color: var(--warning);"></i>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-bar">
                <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?status=paid" class="filter-btn <?php echo $status_filter == 'paid' ? 'active' : ''; ?>">Paid</a>
                <a href="?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=draft" class="filter-btn <?php echo $status_filter == 'draft' ? 'active' : ''; ?>">Draft</a>
                <a href="?status=sent" class="filter-btn <?php echo $status_filter == 'sent' ? 'active' : ''; ?>">Sent</a>
                <a href="?status=overdue" class="filter-btn <?php echo $status_filter == 'overdue' ? 'active' : ''; ?>">Overdue</a>
                
                <form method="GET" class="search-box">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="text" name="search" placeholder="Search by client or invoice #..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <!-- Invoices Table -->
            <div class="table-container">
                <?php if (count($invoices) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="invoice-number"><?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                <td><?php echo htmlspecialchars($inv['client_name']); ?></td>
                                <td>$<?php echo number_format($inv['amount'], 2); ?></td>
                                <td><?php echo $inv['currency']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($inv['issue_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                <td><span class="status-badge <?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                                <td class="action-btns">
                                    <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="icon-btn" title="View"><i class="fas fa-eye"></i></a>
                                    <?php if ($inv['status'] == 'draft'): ?>
                                        <a href="edit_invoice.php?id=<?php echo $inv['id']; ?>" class="icon-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    <a href="send_invoice_email.php?id=<?php echo $inv['id']; ?>" class="icon-btn" title="Send Email"><i class="fas fa-envelope"></i></a>
                                    <a href="download_pdf.php?id=<?php echo $inv['id']; ?>" class="icon-btn" title="Download PDF" target="_blank"><i class="fas fa-download"></i></a>
                                    <?php if ($inv['status'] != 'paid'): ?>
                                        <a href="mark_paid.php?id=<?php echo $inv['id']; ?>" class="icon-btn" title="Mark as Paid" style="color: var(--success);" onclick="return confirm('Mark this invoice as paid?')"><i class="fas fa-check-circle"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <h3>No invoices found</h3>
                        <p style="margin-top: 8px;">Create your first invoice to get started</p>
                        <a href="create_invoice.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Create Invoice →</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>