<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get invoice details
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: invoices.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_email = $_POST['client_email'] ?? $invoice['client_email'];
    $subject = $_POST['subject'] ?? "Invoice #" . $invoice['invoice_number'] . " from " . $_SESSION['user_name'];
    $message = $_POST['message'] ?? "Dear " . $invoice['client_name'] . ",\n\nPlease find attached invoice #" . $invoice['invoice_number'] . " for your records.\n\nAmount: " . $invoice['currency'] . " " . number_format($invoice['amount'], 2) . "\nDue Date: " . date('F d, Y', strtotime($invoice['due_date'])) . "\n\nPay here: http://" . $_SERVER['HTTP_HOST'] . "/zeno/pay_invoice.php?id=" . $invoice_id . "\n\nThank you for your business!";
    
    // Update invoice status to 'sent'
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ?");
    $stmt->execute([$invoice_id]);
    
    $success = "Invoice marked as sent! You can now share the payment link with your client.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Invoice - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7fc; padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn-primary { background: #2c5282; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-secondary { background: #e2e8f0; color: #4a5568; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; text-align: center; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .invoice-summary { background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-envelope"></i> Send Invoice to Client</h1>
            <p style="color: #718096; margin: 10px 0 20px;">Invoice #<?php echo $invoice['invoice_number']; ?></p>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?> <a href="view_invoice.php?id=<?php echo $invoice_id; ?>">View Invoice</a></div>
            <?php endif; ?>
            
            <div class="invoice-summary">
                <p><strong>Client:</strong> <?php echo htmlspecialchars($invoice['client_name']); ?></p>
                <p><strong>Amount:</strong> <?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?></p>
                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Client Email</label>
                    <input type="email" name="client_email" value="<?php echo htmlspecialchars($invoice['client_email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" value="Invoice #<?php echo $invoice['invoice_number']; ?> from <?php echo $_SESSION['user_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="8">Dear <?php echo $invoice['client_name']; ?>,

Please find attached invoice #<?php echo $invoice['invoice_number']; ?> for your records.

Amount: <?php echo $invoice['currency']; ?> <?php echo number_format($invoice['amount'], 2); ?>
Due Date: <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?>

Pay here: http://<?php echo $_SERVER['HTTP_HOST']; ?>/zeno/pay_invoice.php?id=<?php echo $invoice_id; ?>

Thank you for your business!

<?php echo $_SESSION['user_name']; ?></textarea>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Mark as Sent</button>
                <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>