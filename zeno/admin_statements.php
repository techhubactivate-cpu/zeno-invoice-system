<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

// Get payments
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM payments p JOIN users u ON p.user_id = u.id WHERE DATE(p.created_at) BETWEEN ? AND ? ORDER BY p.created_at DESC");
$stmt->execute([$from, $to]);
$payments = $stmt->fetchAll();

// Totals
$totalAmount = 0;
foreach ($payments as $p) { $totalAmount += $p['amount']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statements - Zeno Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f1f5f9;display:flex;min-height:100vh}
        .sidebar{width:260px;background:#1e293b;color:white;padding:2rem 1rem;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}
        .sidebar h2{color:#f59e0b;margin-bottom:2rem;font-size:1.3rem;text-align:center}
        .sidebar a{display:flex;align-items:center;gap:0.5rem;color:#94a3b8;text-decoration:none;padding:0.7rem 1rem;border-radius:8px;margin-bottom:0.2rem;font-size:0.9rem}
        .sidebar a:hover,.sidebar a.active{background:#334155;color:white}
        .main-content{margin-left:260px;flex:1;padding:2rem}
        .top-bar{background:white;padding:1rem 2rem;border-radius:12px;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
        .btn-primary{background:#1e3c72;color:white;padding:0.5rem 1rem;border-radius:8px;text-decoration:none;font-weight:600}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
        .stat-card{background:white;padding:1.2rem;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);text-align:center}
        .stat-card h3{font-size:1.5rem;color:#1e293b}.stat-card p{color:#64748b;font-size:0.85rem}
        .card{background:white;padding:1.5rem;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:1rem}
        table{width:100%;border-collapse:collapse}th,td{padding:0.7rem;text-align:left;border-bottom:1px solid #e2e8f0;font-size:0.85rem}th{background:#f8fafc;font-weight:600}
        .filter-bar{display:flex;gap:0.5rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap}
        .filter-bar input{padding:0.5rem;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit}
        .filter-bar button{padding:0.5rem 1rem;background:#f59e0b;color:#1e293b;border:none;border-radius:8px;cursor:pointer;font-weight:600}
        .btn-print{background:#16a34a;color:white;padding:0.7rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;margin-top:1rem}
        @media print{body{background:white}.sidebar,.top-bar,.filter-bar,.btn-print{display:none}.main-content{margin-left:0;padding:0}}
        @media(max-width:768px){.sidebar{width:200px}.main-content{margin-left:200px}}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>⚡ Zeno Admin</h2>
        <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="admin_statements.php" class="active"><i class="fas fa-file-invoice"></i> Statements</a>
        <a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="admin_logs.php"><i class="fas fa-history"></i> Logs</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="main-content">
        <div class="top-bar"><h2>📊 Financial Statements</h2><a href="logout.php" class="btn-primary">Logout</a></div>
        
        <div class="stats">
            <div class="stat-card"><p>Total Revenue</p><h3>$<?php echo number_format($totalAmount, 2); ?></h3></div>
            <div class="stat-card"><p>Transactions</p><h3><?php echo count($payments); ?></h3></div>
        </div>
        
        <div class="card">
            <h3>📅 Payment History</h3>
            <form class="filter-bar" method="GET">
                <span>From:</span><input type="date" name="from" value="<?php echo $from; ?>">
                <span>To:</span><input type="date" name="to" value="<?php echo $to; ?>">
                <button type="submit">Filter</button>
            </form>
            
            <?php if(count($payments) > 0): ?>
            <table><thead><tr><th>Date</th><th>User</th><th>Email</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                <tbody><?php foreach($payments as $p): ?><tr><td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td><td><?php echo htmlspecialchars(($p['first_name']??'').' '.($p['last_name']??'')); ?></td><td><?php echo htmlspecialchars($p['email']); ?></td><td>$<?php echo number_format($p['amount'], 2); ?></td><td><?php echo ucfirst($p['payment_method']??'N/A'); ?></td><td><?php echo ucfirst($p['status']); ?></td></tr><?php endforeach; ?></tbody></table>
            <?php else: ?><p style="text-align:center;color:#94a3b8;padding:2rem;">No payments found for this period.</p><?php endif; ?>
            
            <button onclick="window.print()" class="btn-print">🖨️ Print Statement</button>
        </div>
    </div>
</body>
</html>