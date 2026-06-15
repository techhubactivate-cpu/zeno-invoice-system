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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $broadcast_message = trim($_POST['message']);
    $send_to = $_POST['send_to'] ?? 'all';
    
    if (empty($subject) || empty($broadcast_message)) {
        $message = "Subject and message are required.";
        $messageType = 'error';
    } else {
        // Get recipients based on filter
        if ($send_to == 'all') {
            $recipients = $pdo->query("SELECT id, email, first_name FROM users")->fetchAll();
        } elseif ($send_to == 'active') {
            $recipients = $pdo->query("SELECT id, email, first_name FROM users WHERE status = 'active'")->fetchAll();
        } elseif ($send_to == 'freelancers') {
            $recipients = $pdo->query("SELECT id, email, first_name FROM users WHERE account_type IN ('freelancer', 'creative', 'developer')")->fetchAll();
        } else {
            $recipients = [];
        }
        
        // Create email queue entries
        $stmt = $pdo->prepare("INSERT INTO email_queue (recipient_email, recipient_name, subject, body, status) VALUES (?, ?, ?, ?, 'pending')");
        foreach ($recipients as $recipient) {
            $stmt->execute([$recipient['email'], $recipient['first_name'], $subject, $broadcast_message]);
        }
        
        // Log the broadcast
        $logStmt = $pdo->prepare("INSERT INTO broadcast_messages (subject, message, sent_by, sent_to) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$subject, $broadcast_message, $_SESSION['user_id'], $send_to]);
        
        $message = "Broadcast sent to " . count($recipients) . " users!";
        $messageType = 'success';
    }
}

// Get broadcast history
$broadcasts = $pdo->query("
    SELECT b.*, u.first_name, u.last_name 
    FROM broadcast_messages b 
    LEFT JOIN users u ON b.sent_by = u.id 
    ORDER BY b.sent_at DESC LIMIT 20
")->fetchAll();

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Broadcast | Zeno</title>
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
        
        .broadcast-card {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .broadcast-card h2 { color: white; margin-bottom: 20px; font-size: 18px; }
        .broadcast-card h2 i { margin-right: 8px; color: #4f46e5; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #9ca3af; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: #0f0f1a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #4f46e5; }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
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
            <a href="admin_broadcast.php" class="admin-nav-item active"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
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
            <h2><i class="fas fa-broadcast-tower"></i> Broadcast Message</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $messageType == 'success' ? '✅' : '❌'; ?> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="broadcast-card">
            <h2><i class="fas fa-envelope"></i> Send Broadcast to Users</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Send To</label>
                    <select name="send_to">
                        <option value="all">All Users</option>
                        <option value="active">Active Users Only</option>
                        <option value="freelancers">Freelancers Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" required placeholder="Announcement: New Feature Released">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="6" required placeholder="Dear Zeno User,

We're excited to announce..."></textarea>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send Broadcast</button>
            </form>
        </div>
        
        <div class="table-container">
            <h3><i class="fas fa-history"></i> Recent Broadcasts</h3>
            <table>
                <thead>
                    <tr><th>Date</th><th>Subject</th><th>Sent By</th><th>Recipients</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($broadcasts as $broadcast): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i', strtotime($broadcast['sent_at'])); ?></td>
                        <td><?php echo htmlspecialchars($broadcast['subject']); ?></td>
                        <td><?php echo htmlspecialchars($broadcast['first_name'] . ' ' . $broadcast['last_name']); ?></td>
                        <td><?php echo ucfirst($broadcast['sent_to']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($broadcasts) == 0): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 40px;">No broadcasts yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>