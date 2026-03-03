<?php
// Production configuration

// Enable error logging but hide display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// Only define if not already defined
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: '');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: 4000);
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// Log the database configuration (without password)
error_log("Production config loaded - DB_HOST: " . DB_HOST . ", DB_NAME: " . DB_NAME . ", DB_USER: " . DB_USER);
?>