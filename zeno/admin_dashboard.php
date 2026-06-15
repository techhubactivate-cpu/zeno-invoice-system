<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Display message if any
$message = '';
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// Get pending users
$pending_users = $pdo->query("SELECT * FROM users WHERE approval_status = 'pending' ORDER BY created_at DESC")->fetchAll();
$pending_count = count($pending_users);

// Get all users
$all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Get suspended users
$suspended_users = $pdo->query("SELECT * FROM users WHERE status = 'suspended' ORDER BY suspended_at DESC")->fetchAll();
$suspended_count = count($suspended_users);

// Stats
$total_users = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch()['total'];
$active_users = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'")->fetch()['total'];
$total_invoices = $pdo->query("SELECT COUNT(*) as total FROM invoices")->fetch()['total'];
$total_paid = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'")->fetch()['total'];

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zeno</title>
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
        
        .alert {
            background: #10b98120;
            color: #10b981;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #10b98140;
        }
        
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
        }
        .stat-card .value { font-size: 28px; font-weight: bold; color: white; }
        .stat-card .label { font-size: 13px; color: #9ca3af; margin-top: 5px; }
        
        /* Section Styles */
        .pending-section, .suspended-section {
            background: #1a1a2e;
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .pending-header, .suspended-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pending-header { background: rgba(245, 158, 11, 0.15); }
        .pending-header h3 { color: #f59e0b; font-size: 18px; }
        .suspended-header { background: rgba(220, 38, 38, 0.15); }
        .suspended-header h3 { color: #dc2626; font-size: 18px; }
        .pending-badge, .suspended-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .pending-badge { background: #f59e0b; color: #1a1a2e; }
        .suspended-badge { background: #dc2626; color: white; }
        
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .user-table th { background: rgba(255,255,255,0.03); color: #9ca3af; font-weight: 600; }
        .user-table td { color: #e5e7eb; }
        
        /* Button Styles */
        .btn-approve, .btn-activate {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-reject, .btn-suspend {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            display: inline-block;
        }
        .status-active { background: #10b98120; color: #10b981; }
        .status-suspended { background: #dc262620; color: #dc2626; }
        .status-pending { background: #f59e0b20; color: #f59e0b; }
        
        .table-container {
            background: #1a1a2e;
            border-radius: 12px;
            overflow-x: auto;
            margin-top: 20px;
        }
        .table-container h3 { padding: 20px; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #9ca3af; font-weight: 600; }
        td { color: #e5e7eb; }
        
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
            <a href="admin_dashboard.php" class="admin-nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_users.php" class="admin-nav-item"><i class="fas fa-users"></i> Users</a>
            <a href="admin_invoices.php" class="admin-nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="admin_payments.php" class="admin-nav-item"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_settings.php" class="admin-nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_broadcast.php" class="admin-nav-item"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
            <a href="admin_logs.php" class="admin-nav-item"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_backup.php" class="admin-nav-item"><i class="fas fa-database"></i> Backup</a>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <i class="fas fa-user-shield"></i>
                <div>
                    <p><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <small><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                </div>
            </div>
            <a href="logout.php" class="admin-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <main class="admin-main">
        <div class="admin-top-bar">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            <div><?php echo date('F d, Y'); ?></div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card"><div class="value"><?php echo $total_users; ?></div><div class="label">Total Users</div></div>
            <div class="stat-card"><div class="value"><?php echo $active_users; ?></div><div class="label">Active Users</div></div>
            <div class="stat-card"><div class="value"><?php echo $suspended_count; ?></div><div class="label">Suspended</div></div>
            <div class="stat-card"><div class="value"><?php echo $pending_count; ?></div><div class="label">Pending Approval</div></div>
            <div class="stat-card"><div class="value"><?php echo $total_invoices; ?></div><div class="label">Invoices</div></div>
            <div class="stat-card"><div class="value">$<?php echo number_format($total_paid, 2); ?></div><div class="label">Revenue</div></div>
        </div>
        
        <!-- PENDING APPROVAL SECTION -->
        <?php if ($pending_count > 0): ?>
        <div class="pending-section">
            <div class="pending-header">
                <h3><i class="fas fa-user-clock"></i> Users Awaiting Approval</h3>
                <span class="pending-badge"><?php echo $pending_count; ?> pending</span>
            </div>
            <table class="user-table">
                <thead><tr><th>Name</th><th>Email</th><th>Account Type</th><th>Registered</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pending_users as $pending): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($pending['email']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $pending['account_type'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($pending['created_at'])); ?></td>
                        <td>
                            <a href="admin_actions.php?action=approve&id=<?php echo $pending['id']; ?>" class="btn-approve" onclick="return confirm('Approve this user?')"><i class="fas fa-check"></i> Approve</a>
                            <a href="admin_actions.php?action=reject&id=<?php echo $pending['id']; ?>" class="btn-reject" onclick="return confirm('Reject this user?')"><i class="fas fa-times"></i> Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- SUSPENDED USERS SECTION -->
        <?php if ($suspended_count > 0): ?>
        <div class="suspended-section">
            <div class="suspended-header">
                <h3><i class="fas fa-ban"></i> Suspended Users</h3>
                <span class="suspended-badge"><?php echo $suspended_count; ?> suspended</span>
            </div>
            <table class="user-table">
                <thead><tr><th>Name</th><th>Email</th><th>Account Type</th><th>Suspended On</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($suspended_users as $suspended): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($suspended['first_name'] . ' ' . $suspended['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($suspended['email']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $suspended['account_type'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($suspended['suspended_at'] ?? $suspended['created_at'])); ?></td>
                        <td>
                            <a href="admin_actions.php?action=activate&id=<?php echo $suspended['id']; ?>" class="btn-activate" onclick="return confirm('Activate this user?')"><i class="fas fa-play"></i> Activate</a>
                            <?php if ($suspended['id'] != $_SESSION['user_id']): ?>
                            <a href="admin_actions.php?action=delete&id=<?php echo $suspended['id']; ?>" class="btn-delete" onclick="return confirm('Delete this user permanently? This cannot be undone!')"><i class="fas fa-trash"></i> Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- ALL USERS TABLE -->
        <div class="table-container">
            <h3><i class="fas fa-users"></i> All Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account Type</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $u['account_type'])); ?></td>
                        <td><span class="status-badge status-active"><?php echo ucfirst($u['role']); ?></span></td>
                        <td><span class="status-badge status-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a href="admin_edit_user.php?id=<?php echo $u['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                            <?php if ($u['status'] == 'active' && $u['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_actions.php?action=suspend&id=<?php echo $u['id']; ?>" class="btn-suspend" onclick="return confirm('Suspend this user?')"><i class="fas fa-ban"></i> Suspend</a>
                            <?php endif; ?>
                            <?php if ($u['status'] == 'suspended' && $u['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_actions.php?action=activate&id=<?php echo $u['id']; ?>" class="btn-activate" onclick="return confirm('Activate this user?')"><i class="fas fa-play"></i> Activate</a>
                            <?php endif; ?>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_actions.php?action=delete&id=<?php echo $u['id']; ?>" class="btn-delete" onclick="return confirm('Delete this user permanently? This cannot be undone!')"><i class="fas fa-trash"></i> Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>