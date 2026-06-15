<?php
session_start();
require_once 'config.php';

// IMPORTANT: Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get filter
$type_filter = $_GET['type'] ?? 'all';
$logs = [];

if ($type_filter == 'all') {
    $logs = $pdo->query("
        SELECT l.*, u.first_name, u.last_name, u.email 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC LIMIT 100
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.email 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE l.action LIKE ? 
        ORDER BY l.created_at DESC LIMIT 100
    ");
    $stmt->execute(["%$type_filter%"]);
    $logs = $stmt->fetchAll();
}

// Get stats
$total_logs = $pdo->query("SELECT COUNT(*) as count FROM activity_logs")->fetch()['count'];
$today_logs = $pdo->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch()['count'];

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Activity Logs | Zeno</title>
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
            margin-bottom: 25px;
        }
        .stat-card {
            background: #1a1a2e;
            padding: 20px;
            border-radius: 12px;
        }
        .stat-card .value { font-size: 28px; font-weight: bold; color: white; }
        .stat-card .label { font-size: 13px; color: #9ca3af; margin-top: 5px; }
        
        .filters-bar {
            background: #1a1a2e;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-select {
            padding: 8px 15px;
            background: #0f0f1a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            cursor: pointer;
        }
        
        .table-container {
            background: #1a1a2e;
            border-radius: 12px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #9ca3af; font-weight: 600; }
        td { color: #e5e7eb; }
        
        .action-login { color: #10b981; }
        .action-create { color: #4f46e5; }
        .action-delete { color: #dc2626; }
        .action-update { color: #f59e0b; }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .filters-bar { flex-direction: column; }
            .filter-select { width: 100%; }
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
            <a href="admin_settings.php" class="admin-nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_broadcast.php" class="admin-nav-item"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
            <a href="admin_logs.php" class="admin-nav-item active"><i class="fas fa-history"></i> Logs</a>
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
            <h2><i class="fas fa-history"></i> Activity Logs</h2>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card"><div class="value"><?php echo $total_logs; ?></div><div class="label">Total Activities</div></div>
            <div class="stat-card"><div class="value"><?php echo $today_logs; ?></div><div class="label">Today</div></div>
        </div>
        
        <div class="filters-bar">
            <select class="filter-select" id="typeFilter" onchange="window.location.href='?type='+this.value">
                <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Activities</option>
                <option value="login" <?php echo $type_filter == 'login' ? 'selected' : ''; ?>>Logins</option>
                <option value="create" <?php echo $type_filter == 'create' ? 'selected' : ''; ?>>Created</option>
                <option value="update" <?php echo $type_filter == 'update' ? 'selected' : ''; ?>>Updates</option>
                <option value="delete" <?php echo $type_filter == 'delete' ? 'selected' : ''; ?>>Deletions</option>
                <option value="payment" <?php echo $type_filter == 'payment' ? 'selected' : ''; ?>>Payments</option>
            </select>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                                <br><small style="color: #6b7280;"><?php echo htmlspecialchars($log['email']); ?></small>
                            <?php else: ?>
                                System
                            <?php endif; ?>
                        </td>
                        <td><span class="action-<?php echo explode('_', $log['action'])[0]; ?>"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 100)); ?>…</small></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($logs) == 0): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px;">No activity logs found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>