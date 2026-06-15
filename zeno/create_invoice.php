<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';

function generateInvoiceNumber($pdo, $user_id) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ? AND YEAR(created_at) = ?");
    $stmt->execute([$user_id, $year]);
    $count = $stmt->fetchColumn() + 1;
    return "INV-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'USD';
    $due_date = $_POST['due_date'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    if (empty($client_name) || $amount <= 0 || empty($due_date)) {
        $error = "Client name, amount, and due date are required.";
    } else {
        $invoice_number = generateInvoiceNumber($pdo, $user_id);
        
        $sql = "INSERT INTO invoices (user_id, invoice_number, client_name, client_email, amount, currency, issue_date, due_date, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 'draft', NOW())";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $invoice_number, $client_name, $client_email, $amount, $currency, $due_date, $description])) {
            $invoice_id = $pdo->lastInsertId();
            header("Location: view_invoice.php?id=" . $invoice_id);
            exit();
        } else {
            $error = "Failed to create invoice. Please try again.";
        }
    }
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
    <title>Create Invoice - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --card-bg: #ffffff;
            --border-radius: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(12px);
            color: white;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(135deg, var(--primary), #06b6d4);
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
            transition: var(--transition);
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
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #fecaca;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            padding: 24px 32px;
        }

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
        }

        .notifications {
            background: var(--card-bg);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
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
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .page-header h1 i {
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 8px;
        }

        .form-card {
            max-width: 700px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            margin: 0 auto;
        }

        .form-card-header {
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .form-card-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .form-card-body {
            padding: 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            font-family: inherit;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                transition: transform 0.3s ease;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .mobile-menu-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .form-card {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar - NO TOGGLE BUTTON -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-bolt"></i> ZENO</h2>
                <div class="account-badge">
                    <i class="fas <?php echo $config['icon']; ?>"></i> 
                    <?php echo ucwords(str_replace('_', ' ', $account_type)); ?>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="create_invoice.php" class="nav-item active"><i class="fas fa-plus-circle"></i><span>Create Invoice</span></a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i><span>Invoices</span></a>
                <a href="clients.php" class="nav-item"><i class="fas fa-users"></i><span>Clients</span></a>
                <a href="payments.php" class="nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
                <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i><span>Profile</span></a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-actions">
                    <div class="currency-display"><i class="fas fa-dollar-sign"></i><span>USD/KES: 130.50</span></div>
                    <div class="notifications"><i class="fas fa-bell"></i><span class="badge">3</span></div>
                </div>
            </div>

            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Create New Invoice</h1>
                <p style="color: var(--gray-500); margin-top: 4px;">Create a professional invoice for your client</p>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <h2><i class="fas fa-file-invoice"></i> Invoice Details</h2>
                </div>
                <div class="form-card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-error">❌ <?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Client Name *</label>
                            <input type="text" name="client_name" required placeholder="e.g., John Doe">
                        </div>
                        <div class="form-group">
                            <label>Client Email</label>
                            <input type="email" name="client_email" placeholder="client@example.com">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Invoice Amount *</label>
                                <input type="number" name="amount" step="0.01" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Currency *</label>
                                <select name="currency">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                    <option value="KES">KES (KSh)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Due Date *</label>
                                <input type="date" name="due_date" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description / Services Provided</label>
                            <textarea name="description" rows="4" placeholder="Describe the services you provided..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Create Invoice →</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('mobile-open')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    </script>
</body>
</html>