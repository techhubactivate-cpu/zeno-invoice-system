<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h2>Testing Database Connection</h2>";

// Test connection
if ($conn) {
    echo "✅ Database connected successfully<br>";
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists<br>";
    } else {
        echo "❌ Users table does NOT exist<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}
?>
