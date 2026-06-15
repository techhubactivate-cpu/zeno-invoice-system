<?php
require_once 'config.php';
requireLogin();

$client_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM clients WHERE id = ? AND user_id = ?");
$stmt->execute([$client_id, $_SESSION['user_id']]);

header("Location: clients.php");
exit();
?>