<?php
require_once 'db_connect.php';

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Check if admin user exists
$checkAdmin = "SELECT id FROM users WHERE username = 'admin'";
$result = $conn->query($checkAdmin);

if ($result && $result->num_rows == 0) {
    // Create default admin user
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $admin_username, $admin_password);
    
    if ($stmt->execute()) {
        echo "Default admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<p>Please change this password after first login!</p>";
    } else {
        echo "Error creating default admin user: " . $stmt->error . "<br>";
    }
}

echo "<p><a href='login.php'>Go to login page</a></p>";

$conn->close();
?>