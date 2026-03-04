<?php
// test-functions.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing auth.php Functions</h1>";

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

echo "<p>✓ All files loaded</p>";

// Test each function without executing them
$functions = [
    'registerUser',
    'loginUser', 
    'logoutUser',
    'changePassword',
    'resetPassword',
    'updateProfile'
];

echo "<h2>Checking if functions exist:</h2>";
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p style='color:green'>✅ $func() exists</p>";
    } else {
        echo "<p style='color:red'>❌ $func() does NOT exist</p>";
    }
}

// Try to call a harmless function
echo "<h2>Testing logoutUser() (should not execute anything):</h2>";
try {
    $result = logoutUser();
    echo "<p style='color:green'>✅ logoutUser() executed successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ logoutUser() failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Testing complete!</h2>";