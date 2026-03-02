<?php
// test.php - Simple test page
echo "<h1>Test Page</h1>";
echo "<p>If you can see this, basic PHP is working.</p>";

// Try to load config
echo "<h2>Loading config.php...</h2>";
require_once 'includes/config.php';
echo "<p>Config loaded successfully!</p>";
?>