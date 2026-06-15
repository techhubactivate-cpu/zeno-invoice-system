<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop the database completely
    $pdo->exec("DROP DATABASE IF EXISTS zeno");
    echo "✅ Old database dropped<br>";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE zeno");
    echo "✅ New database created<br>";
    
    // Use database
    $pdo->exec("USE zeno");
    
    // Create users table
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20),
        country VARCHAR(50),
        account_type VARCHAR(50) DEFAULT 'freelancer',
        password_hash VARCHAR(255) NOT NULL,
        email_verified INT DEFAULT 0,
        role VARCHAR(20) DEFAULT 'user',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Users table created<br>";
    
    // Create invoices table
    $pdo->exec("CREATE TABLE invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        invoice_number VARCHAR(50) NOT NULL,
        client_name VARCHAR(100) NOT NULL,
        client_email VARCHAR(100),
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'USD',
        total_amount DECIMAL(10,2) NOT NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Invoices table created<br>";
    
    // Create admin user
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (first_name, last_name, email, password_hash, role, status) 
                VALUES ('Admin', 'User', 'admin@zeno.com', '$password_hash', 'admin', 'active')");
    echo "✅ Admin user created (admin@zeno.com / password)<br>";
    
    echo "<h2 style='color: green;'>✅ DATABASE FIXED SUCCESSFULLY!</h2>";
    echo "<a href='login.php'>Click here to login</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>