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

// ============================================
// LOAD ENVIRONMENT-SPECIFIC DATABASE CREDENTIALS
// ============================================
if ($is_local) {
    // LOCAL ENVIRONMENT - Load local config
    require_once __DIR__ . '/config.local.php';
} else {
    // PRODUCTION ENVIRONMENT - Load production config
    require_once __DIR__ . '/config.production.php';
}

// ============================================
// SITE CONFIGURATION (Same for all environments)
// ============================================
define('SITE_NAME', 'RAYS OF GRACE Junior School');
define('SITE_MOTTO', 'Knowledge Changing Lives Forever');
define('SITE_URL', $is_local ? 'http://localhost/rog-e-learning-platform' : (getenv('SITE_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));

// Payment configuration
define('MTN_NUMBER', '256XXXXXXXXX');
define('AIRTEL_NUMBER', '256XXXXXXXXX');

// Subscription prices (UGX)
define('PRICE_MONTHLY', 100000);
define('PRICE_TERMLY', 500000);
define('PRICE_YEARLY', 1500000);
define('FAMILY_DISCOUNT', 0.20);

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    // Validate required constants
    if (!defined('DB_HOST') || empty(DB_HOST)) {
        throw new Exception('DB_HOST is not defined or empty');
    }
    if (!defined('DB_NAME') || empty(DB_NAME)) {
        throw new Exception('DB_NAME is not defined or empty');
    }
    if (!defined('DB_USER') || empty(DB_USER)) {
        throw new Exception('DB_USER is not defined or empty');
    }

    // Build DSN with port if available
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Add port if defined and not default
    if (defined('DB_PORT') && DB_PORT != 3306) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }

    // Log connection attempt (without password)
    error_log("Attempting database connection to host: " . DB_HOST . ", database: " . DB_NAME);

    // Connection options with SSL for TiDB Cloud
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
        // SSL is REQUIRED for TiDB Cloud
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Test the connection
    $pdo->query("SELECT 1");
    error_log("Database connection successful to: " . DB_HOST);
    
} catch (PDOException $e) {
    // Log the detailed error
    error_log("DATABASE CONNECTION FAILED: " . $e->getMessage());
    error_log("DSN: " . ($dsn ?? 'Not built'));
    error_log("Host: " . (defined('DB_HOST') ? DB_HOST : 'undefined'));
    error_log("Port: " . (defined('DB_PORT') ? DB_PORT : '3306'));
    error_log("Database: " . (defined('DB_NAME') ? DB_NAME : 'undefined'));
    error_log("User: " . (defined('DB_USER') ? DB_USER : 'undefined'));
    
    // User-friendly message
    if (isset($is_local) && $is_local) {
        die("Connection failed: " . $e->getMessage());
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
} catch (Exception $e) {
    error_log("CONFIGURATION ERROR: " . $e->getMessage());
    die($is_local ? $e->getMessage() : "Configuration error. Please contact support.");
}

// ============================================
// SESSION MANAGEMENT
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

function formatCurrency($amount) {
    return 'UGX ' . number_format($amount, 0);
}

function calculateFamilyDiscount($numberOfChildren, $basePrice) {
    if ($numberOfChildren >= 2) {
        return $basePrice * (1 - FAMILY_DISCOUNT);
    }
    return $basePrice;
}
?>