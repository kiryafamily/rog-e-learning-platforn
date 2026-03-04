<?php
// render-info.php - Check Render's PHP environment
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Render Environment Info</h1>";

echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . "</p>";

echo "<h2>Loaded Extensions</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<h2>PDO Drivers</h2>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

echo "<h2>Include Path</h2>";
echo "<p>" . get_include_path() . "</p>";

echo "<h2>Document Root</h2>";
echo "<p>" . $_SERVER['DOCUMENT_ROOT'] . "</p>";

echo "<h2>Current Directory</h2>";
echo "<p>" . __DIR__ . "</p>";

echo "<h2>Session Save Path</h2>";
echo "<p>" . session_save_path() . "</p>";
echo "<p>Writable: " . (is_writable(session_save_path() ?: '/tmp') ? 'Yes' : 'No') . "</p>";

echo "<h2>File Permissions</h2>";
$files = [
    'includes/config.php',
    'includes/functions.php',
    'includes/auth.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    echo "<p>$file: " . (file_exists($fullPath) ? '✅ Exists' : '❌ Missing') . "</p>";
    if (file_exists($fullPath)) {
        echo "<p>&nbsp;&nbsp;Readable: " . (is_readable($fullPath) ? '✅' : '❌') . "</p>";
    }
}

echo "<h2>POST Data Test</h2>";
echo "<form method='POST'>";
echo "<input type='text' name='test' value='hello'>";
echo "<button type='submit'>Test POST</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color:green'>✅ POST data received: " . ($_POST['test'] ?? 'none') . "</p>";
}

echo "<h2>Session Test</h2>";
session_start();
$_SESSION['test'] = 'Session works!';
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session data: " . ($_SESSION['test'] ?? 'none') . "</p>";