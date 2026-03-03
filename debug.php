<?php
// debug.php - Debug information page
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Debug Information</h1>";

// Environment detection
echo "<h2>Environment</h2>";
echo "<pre>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set') . "\n";
echo "</pre>";

// Environment variables (without passwords)
echo "<h2>Environment Variables</h2>";
echo "<pre>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? '[SET]' : 'NOT SET') . "\n";
echo "</pre>";

// Test loading config
echo "<h2>Loading Config</h2>";
try {
    require_once 'includes/config.php';
    echo "<p style='color:green'>✓ config.php loaded successfully</p>";
    
    echo "<h3>Defined Constants</h3>";
    echo "<pre>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
    echo "DB_PORT: " . (defined('DB_PORT') ? DB_PORT : 'NOT DEFINED') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
    echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "\n";
    echo "</pre>";
    
    // Test database connection
    echo "<h3>Database Connection Test</h3>";
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<p style='color:green'>✓ Database connected successfully</p>";
            
            // Show tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tables in database: " . implode(', ', $tables) . "</p>";
            
            // Count users
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "<p>Users in database: " . $count . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Database query failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ PDO object not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Failed to load config: " . $e->getMessage() . "</p>";
}

// PHP Info
echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions:</p>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";
?>