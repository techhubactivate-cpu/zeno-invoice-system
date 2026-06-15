<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = $_POST['invoice_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Update invoice status
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$status, $invoice_id]);
    
    // If marked as paid, update paid_at timestamp
    if ($status == 'paid') {
        $stmt = $pdo->prepare("UPDATE invoices SET paid_at = NOW() WHERE id = ?");
        $stmt->execute([$invoice_id]);
    }
    
    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                              VALUES (?, 'updated_invoice_status', 'invoice', ?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], $invoice_id, $notes, $_SERVER['REMOTE_ADDR']]);
    
    header("Location: invoices.php?message=Status updated successfully");
    exit();
}
?>