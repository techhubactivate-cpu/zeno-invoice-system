<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);

header("Location: view_invoice.php?id=" . $invoice_id);
exit();
?>