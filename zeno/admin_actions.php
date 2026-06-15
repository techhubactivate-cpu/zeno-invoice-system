<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if action and id are provided
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    $_SESSION['admin_message'] = "Invalid request: Missing action or user ID";
    header("Location: admin_dashboard.php");
    exit();
}

$action = $_GET['action'];
$user_id = intval($_GET['id']);
$message = '';

try {
    switch ($action) {
        case 'delete':
            // Don't allow deleting yourself
            if ($user_id == $_SESSION['user_id']) {
                $message = "You cannot delete your own account!";
            } else {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $message = "User " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been deleted permanently!";
                    
                    // Log the action
                    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'delete_user', ?, ?)");
                    $logStmt->execute([$_SESSION['user_id'], "Deleted user: {$user['email']} (ID: $user_id)", $_SERVER['REMOTE_ADDR']]);
                } else {
                    $message = "User not found!";
                }
            }
            break;
            
        case 'suspend':
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', suspended_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been suspended!";
                
                // Log the action
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'suspend_user', ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], "Suspended user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
            } else {
                $message = "User not found!";
            }
            break;
            
        case 'activate':
            $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', suspended_at = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been activated!";
                
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'activate_user', ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], "Activated user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
            } else {
                $message = "User not found!";
            }
            break;
            
        case 'approve':
            $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $user_id]);
                $message = "User " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been approved!";
                
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'approve_user', ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], "Approved user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
            } else {
                $message = "User not found!";
            }
            break;
            
        case 'reject':
            $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', status = 'suspended' WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been rejected!";
                
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'reject_user', ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], "Rejected user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
            } else {
                $message = "User not found!";
            }
            break;
            
        default:
            $message = "Invalid action: " . htmlspecialchars($action);
    }
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}

// Store message in session and redirect
$_SESSION['admin_message'] = $message;
header("Location: admin_dashboard.php");
exit();
?>