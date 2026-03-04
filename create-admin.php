<?php
// create-admin.php - Place this in your raysofgrace folder
// Run it once, then DELETE IT immediately!
// This script creates a default admin user with the email

// Connect to database
$host = 'localhost';
$dbname = 'raysofgrace_db';
$username = 'root';
$password = ''; // XAMPP default is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Hash the password
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Insert admin user
    $sql = "INSERT INTO users (fullname, email, phone, password, role, status, created_at) 
            VALUES ('School Administrator', 'admin@raysofgrace.ac.ug', '256700000000', ?, 'admin', 'active', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashedPassword]);
    
    echo "✅ Admin user created successfully!<br>";
    echo "Email: admin@raysofgrace.ac.ug<br>";
    echo "Password: admin123<br>";
    echo "<br><strong>⚠️ IMPORTANT: Delete this file now!</strong>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>