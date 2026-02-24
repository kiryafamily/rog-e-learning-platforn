<?php
// includes/config.php
// Database configuration for RAYS OF GRACE Junior School

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'raysofgrace_db');
define('DB_USER', 'root'); // Change this to your database username
define('DB_PASS', ''); // Change this to your database password

// Site configuration
define('SITE_NAME', 'RAYS OF GRACE Junior School');
define('SITE_URL', 'https://www.raysofgrace.ac.ug');
define('SITE_MOTTO', 'Knowledge Changing Lives Forever');

// Payment configuration (Mobile Money)
define('MTN_NUMBER', '256XXXXXXXXX'); // Replace with your MTN business number
define('AIRTEL_NUMBER', '256XXXXXXXXX'); // Replace with your Airtel business number

// Subscription prices (UGX)
define('PRICE_MONTHLY', 100000);
define('PRICE_TERMLY', 500000);
define('PRICE_YEARLY', 1500000);
define('FAMILY_DISCOUNT', 0.20); // 20% discount

// Establish database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Helper function to get current user
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Helper function to format currency
function formatCurrency($amount) {
    return 'UGX ' . number_format($amount, 0);
}

// Helper function to calculate family discount
function calculateFamilyDiscount($numberOfChildren, $basePrice) {
    if ($numberOfChildren >= 2) {
        return $basePrice * (1 - FAMILY_DISCOUNT);
    }
    return $basePrice;
}
?>