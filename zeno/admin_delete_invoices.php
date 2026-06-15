<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$invoice_id = $_GET['id'] ?? 0;

if ($invoice_id) {
    // Log before deleting
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                              VALUES (?, 'deleted_invoice', 'invoice', ?, 'Invoice deleted by admin', ?)");
    $logStmt->execute([$_SESSION['user_id'], $invoice_id, $_SERVER['REMOTE_ADDR']]);
    
    // Delete invoice (cascade will delete associated payments)
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
}

header("Location: invoices.php?message=Invoice deleted");
exit();
?>