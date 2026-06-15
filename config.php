<?php
// Error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Aiven MySQL Database Configuration - Credentials from Environment Variables
$host = 'mysql-34e81043-techhubactivate-0ca2.j.aivencloud.com';
$port = '17010';
$dbname = 'defaultdb';
$username = 'avnadmin';
$password = getenv('DB_PASSWORD'); // Read from Render environment variable

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// MySQLi connection for compatibility
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$conn) {
    die("MySQLi Connection failed: " . mysqli_connect_error());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
