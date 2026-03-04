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
// DATABASE CONNECTION WITH DETAILED DEBUGGING
// ============================================
try {
    // Validate that required constants are defined
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        throw new Exception('Database configuration constants are not defined');
    }
    
    // Log connection parameters (temporarily)
    error_log("===== DB CONNECTION ATTEMPT =====");
    error_log("Host: " . DB_HOST);
    error_log("Port: " . (defined('DB_PORT') ? DB_PORT : 'not defined'));
    error_log("Database: " . DB_NAME);
    error_log("User: " . DB_USER);
    error_log("Environment: " . ($is_local ? 'LOCAL' : 'PRODUCTION'));
    
    // Build DSN with port if available
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    if (defined('DB_PORT') && DB_PORT) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }
    
    error_log("DSN: " . $dsn);
    
    // Connection options
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // TEMPORARILY use EXCEPTION for debugging
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    // For production (TiDB Cloud)
    if (!$is_local) {
        // Skip SSL verification for now to test
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        $options[PDO::MYSQL_ATTR_SSL_CA] = '';
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    error_log("✅ Database connection successful!");
    
} catch (PDOException $e) {
    error_log("❌ PDOException: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    
    // Show the error temporarily for debugging
    die("Database error: " . $e->getMessage() . " (Check Render logs for details)");
    
} catch (Exception $e) {
    error_log("❌ Exception: " . $e->getMessage());
    die("Configuration error: " . $e->getMessage());
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