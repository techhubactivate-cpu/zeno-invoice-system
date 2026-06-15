<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && isset($_POST['user_ids']) && !empty($_POST['bulk_action'])) {
        $user_ids = $_POST['user_ids'];
        $action = $_POST['bulk_action'];
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        
        if ($action == 'bulk_approve') {
            $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', status = 'active' WHERE id IN ($placeholders)");
            $stmt->execute($user_ids);
            $message = count($user_ids) . " users approved!";
        } elseif ($action == 'bulk_suspend') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id IN ($placeholders)");
            $stmt->execute($user_ids);
            $message = count($user_ids) . " users suspended!";
        } elseif ($action == 'bulk_delete') {
            $filtered_ids = array_diff($user_ids, [$_SESSION['user_id']]);
            if (!empty($filtered_ids)) {
                $placeholders = implode(',', array_fill(0, count($filtered_ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->execute($filtered_ids);
                $message = count($filtered_ids) . " users deleted!";
            } else {
                $message = "Cannot delete yourself!";
            }
        }
        $messageType = 'success';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($status_filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}
if ($role_filter != 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}
if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin | Zeno</title>
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
            border-right: 1px solid rgba(255,255,255,0.1);
            overflow-y: auto;
        }
        .admin-sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-sidebar-header h2 { font-size: 24px; color: #fff; }
        .admin-badge { background: #e53e3e; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; margin-top: 8px; }
        .admin-sidebar-nav { flex: 1; padding: 20px; }
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            margin: 5px 0;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .admin-nav-item i { width: 20px; }
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
        .filter-select, .search-input {
            padding: 8px 15px;
            background: #0f0f1a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
        }
        .search-input { flex: 1; min-width: 200px; }
        
        .table-container {
            background: #1a1a2e;
            border-radius: 12px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #9ca3af; font-weight: 600; font-size: 13px; }
        td { color: #e5e7eb; font-size: 14px; }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-active { background: #10b98120; color: #10b981; }
        .status-suspended { background: #dc262620; color: #dc2626; }
        .status-pending { background: #f59e0b20; color: #f59e0b; }
        
        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .role-admin { background: #dc262620; color: #dc2626; }
        .role-user { background: #3b82f620; color: #3b82f6; }
        
        .account-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            background: #4f46e520;
            color: #818cf8;
            display: inline-block;
        }
        
        .btn-sm {
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            border: none;
            margin: 2px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-suspend { background: #f59e0b; color: white; }
        .btn-activate { background: #10b981; color: white; }
        .btn-delete { background: #dc2626; color: white; }
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: #dc2626; color: white; }
        
        .bulk-actions {
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .checkbox-col { width: 30px; }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .filters-bar { flex-direction: column; }
            .search-input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar" id="sidebar">
        <div class="admin-sidebar-header">
            <h2><i class="fas fa-bolt"></i> ZENO ADMIN</h2>
            <p class="admin-badge"><i class="fas fa-shield-alt"></i> Administrator</p>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="admin_dashboard.php" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_users.php" class="admin-nav-item active"><i class="fas fa-users"></i> Users</a>
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
                    <p><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                    <small><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                </div>
            </div>
            <a href="logout.php" class="admin-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <main class="admin-main">
        <div class="admin-top-bar">
            <h2><i class="fas fa-users"></i> User Management</h2>
            <button class="btn-sm" style="background: #4f46e5;" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i> Menu</button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="filters-bar">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>📊 All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>✅ Active</option>
                    <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>⛔ Suspended</option>
                </select>
                <select name="role" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>👥 All Roles</option>
                    <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>👤 Users</option>
                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>🛡️ Admins</option>
                </select>
                <input type="text" name="search" class="search-input" placeholder="🔍 Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-sm" style="background: #4f46e5; padding: 8px 20px;">Search</button>
                <?php if ($search || $status_filter != 'all' || $role_filter != 'all'): ?>
                    <a href="admin_users.php" class="btn-sm" style="background: #4b5563; padding: 8px 20px;">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container">
            <form method="POST" id="bulkForm">
                <div class="bulk-actions">
                    <span style="color: #9ca3af; font-size: 13px;"><i class="fas fa-check-double"></i> Bulk Actions:</span>
                    <select name="bulk_action" class="filter-select" style="padding: 6px 12px;">
                        <option value="">Select Action</option>
                        <option value="bulk_approve">✅ Approve Selected</option>
                        <option value="bulk_suspend">⛔ Suspend Selected</option>
                        <option value="bulk_delete">🗑 Delete Selected</option>
                    </select>
                    <button type="submit" class="btn-sm" style="background: #4f46e5;" onclick="return confirm('Apply bulk action to selected users?')">Apply</button>
                    <span style="color: #6b7280; font-size: 12px; margin-left: auto;">Total: <?php echo count($users); ?> users</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Account Type</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?php echo $u['id']; ?>" class="user-checkbox"></td>
                            <td style="color: #818cf8;">#<?php echo $u['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="account-badge"><?php echo ucfirst(str_replace('_', ' ', $u['account_type'])); ?></span></td>
                            <td><span class="role-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                            <td><span class="status-badge status-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="admin_edit_user.php?id=<?php echo $u['id']; ?>" class="btn-sm btn-edit" title="Edit User"><i class="fas fa-edit"></i> Edit</a>
                                    
                                    <?php if ($u['approval_status'] == 'pending'): ?>
                                        <a href="admin_actions.php?action=approve&id=<?php echo $u['id']; ?>" class="btn-sm btn-approve" onclick="return confirm('Approve this user?')"><i class="fas fa-check"></i> Approve</a>
                                        <a href="admin_actions.php?action=reject&id=<?php echo $u['id']; ?>" class="btn-sm btn-reject" onclick="return confirm('Reject this user?')"><i class="fas fa-times"></i> Reject</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['status'] == 'active' && $u['id'] != $_SESSION['user_id']): ?>
                                        <a href="admin_actions.php?action=suspend&id=<?php echo $u['id']; ?>" class="btn-sm btn-suspend" onclick="return confirm('Suspend this user?')"><i class="fas fa-ban"></i> Suspend</a>
                                    <?php elseif ($u['status'] == 'suspended'): ?>
                                        <a href="admin_actions.php?action=activate&id=<?php echo $u['id']; ?>" class="btn-sm btn-activate" onclick="return confirm('Activate this user?')"><i class="fas fa-play"></i> Activate</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="admin_actions.php?action=delete&id=<?php echo $u['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this user permanently? This cannot be undone!')"><i class="fas fa-trash"></i> Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($users) == 0): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 60px; color: #6b7280;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px;"></i>
                                <p>No users found matching your criteria</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </main>
    
    <script>
        // Select All checkbox functionality
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = this.checked);
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const isMobile = window.innerWidth <= 768;
            if (isMobile && sidebar && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && !event.target.closest('.admin-top-bar button')) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>