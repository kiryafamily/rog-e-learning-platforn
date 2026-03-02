<?php
// includes/config.php - DEBUG VERSION
// This will show ALL errors

// Turn on all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

echo "<h2>Config.php Debug Output</h2>";

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    echo "<p style='color:orange'>Config already loaded, returning</p>";
    return;
}
define('CONFIG_LOADED', true);

echo "<p>CONFIG_LOADED defined</p>";

// Detect environment
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
echo "<p>Environment: " . ($is_local ? 'LOCAL' : 'PRODUCTION') . "</p>";
echo "<p>SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "</p>";

// ============================================
// LOAD ENVIRONMENT-SPECIFIC DATABASE CREDENTIALS
// ============================================
echo "<p>Loading environment config...</p>";

if ($is_local) {
    echo "<p>Loading config.local.php</p>";
    require_once __DIR__ . '/config.local.php';
} else {
    echo "<p>Loading config.production.php</p>";
    require_once __DIR__ . '/config.production.php';
}

echo "<p>Environment config loaded</p>";

// ============================================
// SITE CONFIGURATION
// ============================================
echo "<p>Defining site constants...</p>";
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

echo "<p>Site constants defined</p>";

// ============================================
// DATABASE CONNECTION
// ============================================
echo "<p>Checking database constants...</p>";

if (!defined('DB_HOST')) echo "<p style='color:red'>❌ DB_HOST not defined!</p>";
else echo "<p>✅ DB_HOST = " . DB_HOST . "</p>";

if (!defined('DB_NAME')) echo "<p style='color:red'>❌ DB_NAME not defined!</p>";
else echo "<p>✅ DB_NAME = " . DB_NAME . "</p>";

if (!defined('DB_USER')) echo "<p style='color:red'>❌ DB_USER not defined!</p>";
else echo "<p>✅ DB_USER = " . DB_USER . "</p>";

if (!defined('DB_PASS')) echo "<p style='color:red'>❌ DB_PASS not defined!</p>";
else echo "<p>✅ DB_PASS = " . (DB_PASS ? '[SET]' : '[EMPTY]') . "</p>";

if (!defined('DB_PORT')) echo "<p style='color:red'>❌ DB_PORT not defined!</p>";
else echo "<p>✅ DB_PORT = " . DB_PORT . "</p>";

try {
    echo "<p>Attempting database connection...</p>";
    
    // Validate that required constants are defined
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        throw new Exception('Database configuration constants are not defined');
    }
    
    // Build DSN with port if available
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    if (defined('DB_PORT') && DB_PORT) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }
    
    echo "<p>DSN: " . htmlspecialchars($dsn) . "</p>";
    
    // Connection options
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    // Add SSL for production
    if (!$is_local) {
        echo "<p>Adding SSL options for production...</p>";
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        $options[PDO::MYSQL_ATTR_SSL_CA] = ''; // Use default CA
    }
    
    echo "<p>Creating PDO connection...</p>";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color:green'>✅ DATABASE CONNECTION SUCCESSFUL!</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>✅ Query successful!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ PDOException: " . $e->getMessage() . "</p>";
    echo "<p>Error code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    die();
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Exception: " . $e->getMessage() . "</p>";
    die();
}

echo "<p>Config.php execution completed</p>";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p>Session started</p>";
}

// Helper functions (abbreviated for debugging)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
echo "<p>Helper functions defined</p>";
echo "<h3>Config.php loaded successfully!</h3>";
?>