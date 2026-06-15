<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: invoices.php");
    exit();
}

// Update status if needed
if ($invoice['status'] == 'sent') {
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'viewed' WHERE id = ?");
    $stmt->execute([$invoice_id]);
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
    <title>Invoice #<?php echo $invoice['invoice_number']; ?> - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
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
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .invoice-container { max-width: 800px; margin: 0 auto; }
        .invoice-card { background: var(--card-bg); border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--shadow-lg); }
        .invoice-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 30px; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .invoice-title h1 { font-size: 28px; margin-bottom: 5px; }
        .invoice-status { padding: 8px 16px; border-radius: 40px; font-size: 12px; font-weight: 600; display: inline-block; background: rgba(255,255,255,0.2); }
        .invoice-body { padding: 30px; }
        .client-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-200); }
        .client-section h3 { font-size: 14px; color: var(--gray-500); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .amount-section { background: var(--gray-50); padding: 25px; border-radius: 12px; margin: 20px 0; text-align: center; }
        .amount-label { font-size: 14px; color: var(--gray-500); }
        .amount-value { font-size: 48px; font-weight: 800; color: var(--primary); }
        .action-buttons { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
        .btn-success { background: var(--success); color: white; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
        .btn-secondary { background: var(--gray-100); color: var(--gray-700); padding: 12px 24px; border: 1px solid var(--gray-200); border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--gray-200); }
        .payment-link-box { background: var(--gray-50); padding: 20px; border-radius: 12px; margin-top: 20px; }
        .payment-link-box input { width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: 8px; font-family: monospace; margin-top: 8px; background: var(--card-bg); }
        @media (max-width: 768px) { .invoice-header { flex-direction: column; gap: 15px; } .action-buttons { flex-direction: column; } .btn-primary, .btn-success, .btn-secondary { justify-content: center; } }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-card">
            <div class="invoice-header">
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <p>#<?php echo $invoice['invoice_number']; ?></p>
                </div>
                <div>
                    <span class="invoice-status"><?php echo strtoupper($invoice['status']); ?></span>
                </div>
            </div>
            
            <div class="invoice-body">
                <div class="client-section">
                    <h3>BILL TO</h3>
                    <p><strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong></p>
                    <?php if ($invoice['client_email']): ?>
                        <p><?php echo htmlspecialchars($invoice['client_email']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="detail-row">
                    <span>Invoice Date:</span>
                    <strong><?php echo date('F d, Y', strtotime($invoice['issue_date'])); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Due Date:</span>
                    <strong><?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></strong>
                </div>
                <?php if ($invoice['description']): ?>
                <div class="detail-row">
                    <span>Description:</span>
                    <strong><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="amount-section">
                    <div class="amount-label">Total Amount</div>
                    <div class="amount-value"><?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?></div>
                </div>
                
                <!-- Payment Link -->
                <div class="payment-link-box">
                    <label><i class="fas fa-link"></i> Payment Link (Share with client)</label>
                    <input type="text" readonly value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/zeno/pay_invoice.php?id=<?php echo $invoice['id']; ?>" onclick="this.select();document.execCommand('copy');alert('Payment link copied!')">
                    <small style="color: var(--gray-500);">Click to copy link and share with your client</small>
                </div>
                
                <div class="action-buttons">
                    <?php if ($invoice['status'] == 'draft'): ?>
                        <a href="send_invoice_email.php?id=<?php echo $invoice['id']; ?>" class="btn-primary"><i class="fas fa-envelope"></i> Email to Client</a>
                        <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                    <?php endif; ?>
                    
                    <a href="download_pdf.php?id=<?php echo $invoice['id']; ?>" class="btn-secondary" target="_blank"><i class="fas fa-file-pdf"></i> Download PDF</a>
                    <a href="pay_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn-success" target="_blank"><i class="fas fa-credit-card"></i> Payment Page</a>
                    
                    <?php if ($invoice['status'] != 'paid' && $invoice['status'] != 'cancelled'): ?>
                        <button onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="btn-success"><i class="fas fa-check-circle"></i> Mark as Paid</button>
                    <?php endif; ?>
                    
                    <button onclick="window.print()" class="btn-secondary"><i class="fas fa-print"></i> Print</button>
                    <a href="invoices.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function markAsPaid(invoiceId) {
            if (confirm('Mark this invoice as paid? This action cannot be undone.')) {
                window.location.href = 'mark_paid.php?id=' + invoiceId;
            }
        }
    </script>
</body>
</html>