<?php
require_once 'config.php';
requireLogin();

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);

// For MVP, just show success message
$_SESSION['success'] = "Invoice marked as sent! You can now share the link with your client.";

header("Location: view_invoice.php?id=" . $invoice_id);
exit();
?>