<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        $_SESSION['admin_message'] = "User approved successfully!";
    } 
    elseif ($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', status = 'suspended' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['admin_message'] = "User rejected!";
    } 
    elseif ($action == 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', suspended_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['admin_message'] = "User suspended!";
    } 
    elseif ($action == 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active', suspended_at = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['admin_message'] = "User activated!";
    } 
    elseif ($action == 'delete') {
        if ($user_id != $_SESSION['user_id']) {
            // First get user email for logging
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_email = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['admin_message'] = "User deleted successfully!";
            
            // Log deletion
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'delete_user', ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], "Deleted user: $user_email (ID: $user_id)", $_SERVER['REMOTE_ADDR']]);
        } else {
            $_SESSION['admin_message'] = "You cannot delete yourself!";
        }
    }
    elseif ($action == 'edit') {
        // Handle edit via POST, so just redirect to edit page
        header("Location: admin_edit_user.php?id=" . $user_id);
        exit();
    }
}

header("Location: admin_dashboard.php");
exit();
?>