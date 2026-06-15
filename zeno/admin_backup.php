<?php
session_start();
require_once 'config.php';

// IMPORTANT: Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location:login.php");
    exit();
}

$message = '';
$messageType = '';
$backup_dir = 'admin_backup';

// Create backups directory if it doesn't exist
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Function to create database backup
function createDatabaseBackup($pdo, $backup_dir, $user_id) {
    $backup_name = 'zeno_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_file = $backup_dir . $backup_name;
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Zeno Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- --------------------------------------------------------\n\n";
    
    foreach ($tables as $table) {
        // Get create table syntax
        $create = $pdo->query("SHOW CREATE TABLE $table")->fetch();
        $output .= "-- Table structure for table `$table`\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";
        
        // Get data
        $rows = $pdo->query("SELECT * FROM $table");
        if ($rows->rowCount() > 0) {
            $output .= "-- Dumping data for table `$table`\n";
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, array_values($row));
                $output .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    // Save to file
    file_put_contents($backup_file, $output);
    
    // Get file size
    $size = filesize($backup_file);
    $size_str = $size < 1024 ? $size . ' B' : ($size < 1048576 ? round($size / 1024, 2) . ' KB' : round($size / 1048576, 2) . ' MB');
    
    // Save backup record
    $stmt = $pdo->prepare("INSERT INTO database_backups (backup_name, backup_file, backup_size, backup_type, created_by) VALUES (?, ?, ?, 'manual', ?)");
    $stmt->execute([$backup_name, $backup_file, $size_str, $user_id]);
    
    return ['success' => true, 'file' => $backup_name, 'size' => $size_str];
}

// Create backup
if (isset($_POST['create_backup'])) {
    $result = createDatabaseBackup($pdo, $backup_dir, $_SESSION['user_id']);
    if ($result['success']) {
        $message = "Backup created successfully! File: " . $result['file'] . " (" . $result['size'] . ")";
        $messageType = 'success';
    } else {
        $message = "Failed to create backup.";
        $messageType = 'error';
    }
}

// Delete backup
if (isset($_GET['delete_backup']) && isset($_GET['id'])) {
    $backup_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT backup_file FROM database_backups WHERE id = ?");
    $stmt->execute([$backup_id]);
    $backup = $stmt->fetch();
    
    if ($backup && file_exists($backup['backup_file'])) {
        unlink($backup['backup_file']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM database_backups WHERE id = ?");
    $stmt->execute([$backup_id]);
    $message = "Backup deleted successfully!";
    $messageType = 'success';
}

// Download backup
if (isset($_GET['download_backup']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT backup_file, backup_name FROM database_backups WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $backup = $stmt->fetch();
    
    if ($backup && file_exists($backup['backup_file'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup['backup_name'] . '"');
        header('Content-Length: ' . filesize($backup['backup_file']));
        readfile($backup['backup_file']);
        exit();
    }
}

// Get backups
$backups = $pdo->query("SELECT b.*, u.first_name, u.last_name FROM database_backups b JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC")->fetchAll();

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Database Backup | Zeno</title>
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
        .alert-error { background: #dc262620; color: #dc2626; border: 1px solid #dc262640; }
        
        .backup-card {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }
        .backup-card h2 { color: white; margin-bottom: 10px; }
        .backup-card p { color: #9ca3af; margin-bottom: 20px; }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
        }
        .btn-primary:hover { opacity: 0.9; }
        
        .table-container {
            background: #1a1a2e;
            border-radius: 12px;
            overflow-x: auto;
        }
        .table-container h3 { padding: 20px; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #9ca3af; font-weight: 600; }
        td { color: #e5e7eb; }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 8px;
            color: #9ca3af;
            border-radius: 6px;
        }
        .btn-icon:hover { background: rgba(255,255,255,0.1); color: white; }
        .btn-download { color: #10b981; }
        .btn-delete { color: #dc2626; }
        
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
            <a href="admin_payments.php" class="admin-nav-item"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_settings.php" class="admin-nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="admin_broadcast.php" class="admin-nav-item"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
            <a href="admin_logs.php" class="admin-nav-item"><i class="fas fa-history"></i> Logs</a>
            <a href="admin_backup.php" class="admin-nav-item active"><i class="fas fa-database"></i> Backup</a>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-user-info">
                <i class="fas fa-user-shield"></i>
                <div>
                    <p style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p style="font-size: 11px; opacity: 0.7;"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>
            </div>
            <a href="logout.php" class="admin-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <main class="admin-main">
        <div class="admin-top-bar">
            <h2><i class="fas fa-database"></i> Database Backup</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $messageType == 'success' ? '✅' : '❌'; ?> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="backup-card">
            <h2><i class="fas fa-database"></i> Create Database Backup</h2>
            <p>This will create a complete backup of your database including all tables, users, invoices, and settings.</p>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn-primary"><i class="fas fa-download"></i> Create Backup Now</button>
            </form>
        </div>
        
        <div class="table-container">
            <h3><i class="fas fa-history"></i> Backup History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Backup Name</th>
                        <th>Size</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($backup['backup_name']); ?></td>
                        <td><?php echo htmlspecialchars($backup['backup_size']); ?></td>
                        <td><?php echo htmlspecialchars($backup['first_name'] . ' ' . $backup['last_name']); ?></td>
                        <td><?php echo date('M d, Y H:i:s', strtotime($backup['created_at'])); ?></td>
                        <td>
                            <a href="?download_backup=1&id=<?php echo $backup['id']; ?>" class="btn-icon btn-download" title="Download"><i class="fas fa-download"></i></a>
                            <a href="?delete_backup=1&id=<?php echo $backup['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Delete this backup?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($backups) == 0): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px;">No backups found. Create your first backup!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>