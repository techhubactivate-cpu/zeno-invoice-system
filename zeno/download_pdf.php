<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found");
}

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $invoice['invoice_number']; ?></title>
    <style>
        body { font-family: "Helvetica", sans-serif; margin: 40px; }
        .invoice-header { text-align: center; margin-bottom: 40px; }
        .invoice-title { font-size: 32px; color: #2c5282; }
        .company-info { margin-bottom: 30px; }
        .client-info { margin-bottom: 30px; }
        .invoice-details { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-details th, .invoice-details td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .invoice-details th { background: #f7fafc; }
        .total-row { font-weight: bold; background: #f0f9ff; }
        .amount { font-size: 24px; color: #2c5282; }
        .footer { text-align: center; margin-top: 50px; color: #718096; font-size: 12px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        .status-paid { background: #c6f6d5; color: #22543d; }
        .status-draft { background: #e2e8f0; color: #4a5568; }
        .status-sent { background: #fefcbf; color: #744210; }
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div style="text-align: right; margin-bottom: 20px;" class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2c5282; color: white; border: none; border-radius: 8px; cursor: pointer;">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
        <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" style="margin-left: 10px; padding: 10px 20px; background: #e2e8f0; color: #4a5568; text-decoration: none; border-radius: 8px;">Back</a>
    </div>
    
    <div class="invoice-header">
        <h1 class="invoice-title">INVOICE</h1>
        <p>#<?php echo $invoice['invoice_number']; ?></p>
        <p><span class="status-badge status-<?php echo $invoice['status']; ?>"><?php echo strtoupper($invoice['status']); ?></span></p>
    </div>
    
    <div class="company-info">
        <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong><br>
        <?php echo htmlspecialchars($user['email']); ?><br>
        <?php echo htmlspecialchars($user['country'] ?? ''); ?>
    </div>
    
    <div class="client-info">
        <strong>Bill To:</strong><br>
        <?php echo htmlspecialchars($invoice['client_name']); ?><br>
        <?php echo htmlspecialchars($invoice['client_email']); ?>
    </div>
    
    <table class="invoice-details">
        <tr>
            <th>Description</th>
            <th>Amount</th>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars($invoice['description'] ?? 'Services rendered'); ?></td>
            <td><?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?></td>
        </tr>
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td><strong><?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?></strong></td>
        </tr>
    </table>
    
    <p><strong>Issue Date:</strong> <?php echo date('F d, Y', strtotime($invoice['issue_date'])); ?></p>
    <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></p>
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Payment link: http://<?php echo $_SERVER['HTTP_HOST']; ?>/zeno/pay_invoice.php?id=<?php echo $invoice_id; ?></p>
    </div>
</body>
</html>