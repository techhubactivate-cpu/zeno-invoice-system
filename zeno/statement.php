<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config.php';

// Require login
requireLogin();

$user = getCurrentUser($pdo);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$account_type = $user['account_type'];
$config = getAccountConfig($account_type);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

// Get payments
$payments = [];
$totalAmount = 0;
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
    $stmt->execute([$user['id'], $from, $to]);
    $payments = $stmt->fetchAll();
    foreach ($payments as $p) { $totalAmount += $p['amount']; }
} catch (PDOException $e) {
    $payments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f4f7fc}
        .dashboard-container{display:flex;min-height:100vh}
        .sidebar{width:280px;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;position:fixed;height:100vh;display:flex;flex-direction:column;z-index:100}
        .main-content{margin-left:280px;flex:1;padding:20px}
        .sidebar-header{padding:30px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header h2{font-size:28px;margin-bottom:5px}.account-badge{display:inline-block;background:<?php echo $config['color']; ?>;padding:5px 15px;border-radius:20px;font-size:12px;margin-top:10px}
        .sidebar-nav{flex:1;padding:20px}.nav-item{display:flex;align-items:center;padding:12px 15px;margin:5px 0;color:white;text-decoration:none;border-radius:10px;transition:background 0.3s}.nav-item i{width:24px;margin-right:12px}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.2)}
        .sidebar-footer{padding:20px;border-top:1px solid rgba(255,255,255,0.1)}
        .user-info{display:flex;align-items:center;margin-bottom:15px;padding:10px;background:rgba(255,255,255,0.1);border-radius:10px}.user-info i{font-size:40px;margin-right:12px}.user-name{font-weight:600;font-size:14px}.user-email{font-size:11px;opacity:0.8}
        .logout-btn{display:flex;align-items:center;justify-content:center;padding:10px;background:#dc2626;color:white;text-decoration:none;border-radius:8px}.logout-btn:hover{background:#b91c1c}
        .top-header{background:white;padding:15px 20px;border-radius:12px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.05)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:20px}
        .stat-card{background:white;padding:20px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);text-align:center}
        .stat-card h3{font-size:1.8rem;color:#1e293b}.stat-card p{color:#64748b;font-size:0.85rem}
        .card{background:white;padding:20px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:20px}
        table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:0.85rem}th{background:#f7fafc;font-weight:600;color:#4a5568}
        .filter-bar{display:flex;gap:0.5rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap}
        .filter-bar input{padding:0.5rem;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit}
        .filter-bar button{padding:0.5rem 1rem;background:<?php echo $config['color']; ?>;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600}
        .btn-print{background:#16a34a;color:white;padding:0.6rem 1.2rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;margin-top:1rem;border:none;cursor:pointer;font-family:inherit}
        .btn-back{background:#64748b;color:white;padding:0.6rem 1.2rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;margin-top:1rem;margin-left:0.5rem}
        .status-paid{background:#c6f6d5;color:#22543d;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600}
        .status-pending{background:#fefcbf;color:#744210;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600}
        .mobile-menu-toggle{display:none;background:none;border:none;font-size:24px;cursor:pointer}
        @media print{body{background:white}.sidebar,.top-header,.filter-bar,.btn-print,.btn-back,.mobile-menu-toggle{display:none}.main-content{margin-left:0;padding:0}}
        @media(max-width:768px){.sidebar{transform:translateX(-100%);transition:transform 0.3s}.sidebar.open{transform:translateX(0)}.mobile-menu-toggle{display:block}.main-content{margin-left:0}.stats-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><i class="fas fa-bolt"></i> ZENO</h2><div class="account-badge"><i class="fas <?php echo $config['icon']; ?>"></i> <?php echo ucwords(str_replace('_',' ',$account_type)); ?></div></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="statement.php" class="nav-item active"><i class="fas fa-file-invoice"></i> Statements</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info"><i class="fas fa-user-circle"></i><div><p class="user-name"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></p><p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p></div></div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
                <h2 style="color:#1a202c;">📊 Financial Statement</h2>
                <a href="dashboard.php" style="color:<?php echo $config['color']; ?>;text-decoration:none;font-weight:600;">← Dashboard</a>
            </header>

            <div class="stats-grid">
                <div class="stat-card"><p>Total Received</p><h3>$<?php echo number_format($totalAmount, 2); ?></h3></div>
                <div class="stat-card"><p>Transactions</p><h3><?php echo count($payments); ?></h3></div>
            </div>

            <div class="card">
                <h3>💳 Payment History</h3>
                <form class="filter-bar" method="GET">
                    <span>From:</span><input type="date" name="from" value="<?php echo $from; ?>">
                    <span>To:</span><input type="date" name="to" value="<?php echo $to; ?>">
                    <button type="submit">Filter</button>
                </form>

                <?php if(count($payments) > 0): ?>
                <div style="overflow-x:auto">
                <table><thead><tr><th>Date</th><th>Description</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody><?php foreach($payments as $p): ?><tr><td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td><td><?php echo htmlspecialchars($p['description']??'Payment'); ?></td><td><?php echo ucfirst($p['payment_method']??'N/A'); ?></td><td>$<?php echo number_format($p['amount'], 2); ?></td><td><span class="status-<?php echo $p['status']??'completed'; ?>"><?php echo ucfirst($p['status']??'Completed'); ?></span></td></tr><?php endforeach; ?></tbody></table>
                </div>
                <?php else: ?><p style="text-align:center;color:#718096;padding:2rem;">No payments found for this period.</p><?php endif; ?>

                <button onclick="window.print()" class="btn-print">🖨️ Print Statement</button>
                <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
            </div>
        </main>
    </div>
    <script>document.getElementById('mobileMenuToggle').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open')});</script>
</body>
</html>