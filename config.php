<?php
// ============================================
// ZENO CONFIGURATION - SIMPLE VERSION
// ============================================

// Error reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$host = 'localhost';
$dbname = 'zeno_new';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Simple login check
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

// Get current user
function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Account type configurations
function getAccountConfig($account_type) {
    $configs = [
        'freelancer' => [
            'title' => 'Freelancer Dashboard',
            'icon' => 'fa-user-check',
            'color' => '#4f46e5',
            'greeting' => 'Your freelance business is growing!',
            'quick_tip' => '💰 Send invoices immediately after project completion for faster payments!',
            'features' => ['Track hourly projects', 'Create professional proposals', 'Set recurring invoices'],
        ],
        'creative' => [
            'title' => 'Creative Studio Dashboard',
            'icon' => 'fa-palette',
            'color' => '#8b5cf6',
            'greeting' => 'Your creativity is paying off!',
            'quick_tip' => '🎨 Add portfolio samples to your invoices!',
            'features' => ['Attach design previews', 'Branded invoices', 'Project milestones'],
        ],
        'developer' => [
            'title' => 'Dev Studio Dashboard',
            'icon' => 'fa-code',
            'color' => '#10b981',
            'greeting' => 'Code. Create. Get paid.',
            'quick_tip' => '💻 Break large projects into milestone-based payments!',
            'features' => ['GitHub integration', 'Sprint billing', 'API documentation'],
        ],
        'consultant' => [
            'title' => 'Consulting Dashboard',
            'icon' => 'fa-chalkboard-user',
            'color' => '#f59e0b',
            'greeting' => 'Your expertise is valuable!',
            'quick_tip' => '📊 Use retainer invoices for monthly consulting fees!',
            'features' => ['Hourly billing', 'Retainer management', 'Client portals'],
        ],
        'agency' => [
            'title' => 'Agency Command Center',
            'icon' => 'fa-building',
            'color' => '#ef4444',
            'greeting' => 'Managing multiple clients smoothly!',
            'quick_tip' => '🚀 Create team accounts to manage multiple clients!',
            'features' => ['Team collaboration', 'White-label invoices', 'Bulk invoicing'],
        ],
        'small_business' => [
            'title' => 'Business Dashboard',
            'icon' => 'fa-store',
            'color' => '#3b82f6',
            'greeting' => 'Your business is thriving!',
            'quick_tip' => '🏢 Offer early payment discounts to improve cash flow!',
            'features' => ['Expense tracking', 'Financial reports', 'Multi-currency accounts'],
        ],
    ];
    
    return $configs[$account_type] ?? $configs['freelancer'];
}
?>