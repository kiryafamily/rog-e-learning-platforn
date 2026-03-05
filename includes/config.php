<?php
// includes/config.php
// Main configuration - Auto-detects environment

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Detect environment FIRST
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

if ($is_local) {
    // LOCAL ENVIRONMENT - Load local config
    require_once __DIR__ . '/config.local.php';
} else {
    // PRODUCTION ENVIRONMENT - Load production config
    require_once __DIR__ . '/config.production.php';
}

// Site configuration (these are safe to define here)
// define('SITE_NAME', 'RAYS OF GRACE Junior School');
define('SITE_MOTTO', 'Knowledge Changing Lives Forever');

// Payment configuration
define('MTN_NUMBER', '256XXXXXXXXX'); // Replace with your MTN business number
define('AIRTEL_NUMBER', '256XXXXXXXXX'); // Replace with your Airtel business number

// Subscription prices (UGX)
define('PRICE_MONTHLY', 100000);
define('PRICE_TERMLY', 500000);
define('PRICE_YEARLY', 1500000);
define('FAMILY_DISCOUNT', 0.20); // 20% discount

// Establish database connection using credentials from loaded config
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
    // Log error but don't expose details in production
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
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