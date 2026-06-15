<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$error = '';

// Get invoice
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: invoices.php");
    exit();
}

// Only allow editing draft invoices
if ($invoice['status'] != 'draft') {
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit();
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
        $stmt = $pdo->prepare("UPDATE invoices SET client_name = ?, client_email = ?, amount = ?, currency = ?, due_date = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$client_name, $client_email, $amount, $currency, $due_date, $description, $invoice_id, $user_id]);
        header("Location: view_invoice.php?id=" . $invoice_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice - Zeno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7fc; padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn-primary { background: #2c5282; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-secondary { background: #e2e8f0; color: #4a5568; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; text-align: center; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; background: #fee; color: #c33; border: 1px solid #fcc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-edit"></i> Edit Invoice</h1>
            <p style="color: #718096; margin: 10px 0 20px;">Invoice #<?php echo $invoice['invoice_number']; ?></p>
            
            <?php if ($error): ?>
                <div class="alert">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Client Name *</label>
                    <input type="text" name="client_name" value="<?php echo htmlspecialchars($invoice['client_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Client Email</label>
                    <input type="email" name="client_email" value="<?php echo htmlspecialchars($invoice['client_email']); ?>">
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" step="0.01" value="<?php echo $invoice['amount']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency">
                        <option value="USD" <?php echo $invoice['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="EUR" <?php echo $invoice['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        <option value="GBP" <?php echo $invoice['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        <option value="KES" <?php echo $invoice['currency'] == 'KES' ? 'selected' : ''; ?>>KES (KSh)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" value="<?php echo $invoice['due_date']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($invoice['description']); ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>