<?php
// test-includes.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Include Files</h1>";

try {
    echo "<p>Testing config.php...</p>";
    require_once 'includes/config.php';
    echo "<p style='color:green'>✓ config.php loaded</p>";
    
    echo "<p>Testing functions.php...</p>";
    require_once 'includes/functions.php';
    echo "<p style='color:green'>✓ functions.php loaded</p>";
    
    echo "<p>Testing sanitize() function...</p>";
    if (function_exists('sanitize')) {
        echo "<p style='color:green'>✓ sanitize() exists</p>";
        $test = sanitize("Test <script>alert('xss')</script>");
        echo "<p>Test result: " . htmlspecialchars($test) . "</p>";
    } else {
        echo "<p style='color:red'>✗ sanitize() does NOT exist!</p>";
    }
    
    echo "<p>Testing auth.php...</p>";
    require_once 'includes/auth.php';
    echo "<p style='color:green'>✓ auth.php loaded</p>";
    
    echo "<p style='color:green; font-size:20px;'>All files loaded successfully!</p>";
    
} catch (Error $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>EXCEPTION: " . $e->getMessage() . "</p>";
}