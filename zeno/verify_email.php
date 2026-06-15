<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid verification link.");
}

$stmt = $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active' WHERE verification_token = ?");
$stmt->execute([$token]);

if ($stmt->rowCount() > 0) {
    $_SESSION['verify_success'] = "Email verified successfully! You can now login.";
    header("Location: login.php");
    exit();
} else {
    die("Invalid or expired verification link.");
}
?>