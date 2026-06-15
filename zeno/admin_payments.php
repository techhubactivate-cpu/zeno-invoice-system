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

// Get all paid invoices (these are your payments)
$payments = $pdo->query("
    SELECT i.*, u.first_name, u.last_name, u.email as user_email 
    FROM invoices i 
    JOIN users u ON i.user_id = u.id 
    WHERE i.status = 'paid'
    ORDER BY i.paid_at DESC
")->fetchAll();

// Also get any actual payment records if they exist
$payment_records = [];
try {
    $payment_records = $pdo->query("SELECT * FROM payments ORDER BY payment_date DESC LIMIT 20")->fetchAll();
} catch(PDOException $e) {
    // Payments table might not exist or have different structure
    $payment_records = [];
}

// Get stats
$total_paid = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'")->fetch()['total'];
$total_count = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'paid'")->fetch()['count'];

// Monthly stats
$this_month = date('Y-m');
$monthly_paid = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' AND DATE_FORMAT(paid_at, '%Y-%m') = '$this_month'")->fetch()['total'];

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Payments | Zeno</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1a1a2e;
            padding: 20px;
            border-radius: 12px;
        }
        .stat-card .value { font-size: 28px; font-weight: bold; color: white; }
        .stat-card .label { font-size: 13px; color: #9ca3af; margin-top: 5px; }
        
        .table-container {
            background: #1a1a2e;
            border-radius: 12px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #9ca3af; font-weight: 600; }
        td { color: #e5e7eb; }
        
        .status-paid { background: #10b98120; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
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
            <a href="admin_payments.php" class="admin-nav-item active"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_settings.php" class="admin-nav-item"><i class="fas fa-cog"></i> Settings</a>
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
            <h2><i class="fas fa-credit-card"></i> Payment Management</h2>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card"><div class="value">$<?php echo number_format($total_paid, 2); ?></div><div class="label">Total Received</div></div>
            <div class="stat-card"><div class="value"><?php echo $total_count; ?></div><div class="label">Total Transactions</div></div>
            <div class="stat-card"><div class="value">$<?php echo number_format($monthly_paid, 2); ?></div><div class="label">This Month</div></div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>User</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Paid Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><a href="../view_invoice.php?id=<?php echo $payment['id']; ?>" style="color: #4f46e5; text-decoration: none;"><?php echo htmlspecialchars($payment['invoice_number']); ?></a></td>
                        <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br><small style="color: #6b7280;"><?php echo htmlspecialchars($payment['user_email']); ?></small></td>
                        <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                        <td><?php echo $payment['currency']; ?> <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo $payment['currency']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($payment['paid_at'] ?? $payment['updated_at'] ?? $payment['created_at'])); ?></td>
                        <td><span class="status-paid">Completed</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($payments) == 0): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px;">No payments found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>