<?php
session_start();
require_once 'config.php';

// IMPORTANT: Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

$message = '';
$messageType = '';

// Update invoice status
if (isset($_GET['action']) && isset($_GET['id'])) {
    $invoice_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'paid') {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $message = "Invoice marked as paid!";
        $messageType = 'success';
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $message = "Invoice deleted!";
        $messageType = 'success';
    }
}

// Get all invoices with user info
$invoices = $pdo->query("
    SELECT i.*, u.first_name, u.last_name, u.email as user_email 
    FROM invoices i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC
")->fetchAll();

// Get stats
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_paid
    FROM invoices
")->fetch();

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Invoices | Zeno</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1a1a2e;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
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
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-paid { background: #10b98120; color: #10b981; }
        .status-pending { background: #f59e0b20; color: #f59e0b; }
        .status-draft { background: #6b728020; color: #9ca3af; }
        .status-overdue { background: #dc262620; color: #dc2626; }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 8px;
            color: #9ca3af;
            border-radius: 6px;
        }
        .btn-icon:hover { background: rgba(255,255,255,0.1); color: white; }
        
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
            <a href="admin_invoices.php" class="admin-nav-item active"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="admin_payments.php" class="admin-nav-item"><i class="fas fa-credit-card"></i> Payments</a>
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
            <h2><i class="fas fa-file-invoice"></i> Invoice Management</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card"><div class="value"><?php echo $stats['total']; ?></div><div class="label">Total Invoices</div></div>
            <div class="stat-card"><div class="value"><?php echo $stats['paid']; ?></div><div class="label">Paid</div></div>
            <div class="stat-card"><div class="value"><?php echo $stats['pending']; ?></div><div class="label">Pending</div></div>
            <div class="stat-card"><div class="value">$<?php echo number_format($stats['total_paid'], 2); ?></div><div class="label">Total Revenue</div></div>
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
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']); ?><br><small style="color: #6b7280;"><?php echo htmlspecialchars($inv['user_email']); ?></small></td>
                        <td><?php echo htmlspecialchars($inv['client_name']); ?></td>
                        <td><?php echo $inv['currency']; ?> <?php echo number_format($inv['amount'], 2); ?></td>
                        <td><?php echo $inv['currency']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                        <td>
                            <?php if ($inv['status'] != 'paid'): ?>
                                <a href="?action=paid&id=<?php echo $inv['id']; ?>" class="btn-icon" title="Mark as Paid" onclick="return confirm('Mark this invoice as paid?')"><i class="fas fa-check-circle"></i></a>
                            <?php endif; ?>
                            <a href="../view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-icon" title="View" target="_blank"><i class="fas fa-eye"></i></a>
                            <a href="?action=delete&id=<?php echo $inv['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Delete this invoice? This cannot be undone.')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($invoices) == 0): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px;">No invoices found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>