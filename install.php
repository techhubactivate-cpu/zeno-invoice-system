<?php
$host = getenv('DB_HOST') ?: 'mysql-34e81043-techhubactivate-0ca2.j.aivencloud.com';
$port = getenv('DB_PORT') ?: '17010';
$dbname = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
$password = getenv('DB_PASSWORD');

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    country VARCHAR(100),
    account_type VARCHAR(50),
    account_plan VARCHAR(20) DEFAULT 'free',
    stripe_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'pending',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

if (mysqli_multi_query($conn, $sql)) {
    echo "Tables created successfully! <a href='login.php'>Go to Login</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
