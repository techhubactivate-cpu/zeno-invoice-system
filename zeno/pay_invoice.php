<?php
require_once 'config.php';

$invoice_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT i.*, u.first_name, u.last_name, u.email as freelancer_email FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For MVP, just mark as paid
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $success = "Payment successful! Thank you for your business.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7fc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { max-width: 500px; width: 100%; }
        .card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #2c5282, #1a365d); color: white; padding: 30px; text-align: center; }
        .card-header h1 { font-size: 24px; margin-bottom: 10px; }
        .card-body { padding: 30px; }
        .amount { font-size: 36px; font-weight: bold; color: #2c5282; text-align: center; margin: 20px 0; }
        .divider { height: 1px; background: #e2e8f0; margin: 20px 0; }
        .btn-primary { width: 100%; padding: 14px; background: #2c5282; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn-primary:hover { background: #1a365d; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h1>Invoice #<?php echo $invoice['invoice_number']; ?></h1>
                <p><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></p>
            </div>
            
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($invoice['status'] == 'paid'): ?>
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-check-circle" style="font-size: 64px; color: #48bb78;"></i>
                        <h2 style="margin-top: 15px;">Invoice Paid</h2>
                        <p>Thank you for your payment!</p>
                    </div>
                <?php else: ?>
                    <div class="amount">
                        <?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></p>
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($invoice['description'] ?? 'Services rendered')); ?></p>
                    
                    <div class="divider"></div>
                    
                    <form method="POST">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-credit-card"></i> Pay Now (Demo)
                        </button>
                    </form>
                    <p style="text-align: center; margin-top: 15px; font-size: 12px; color: #718096;">
                        <i class="fas fa-lock"></i> Secure payment processed by Zeno
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>