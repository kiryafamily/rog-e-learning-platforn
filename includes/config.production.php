<?php
// Production configuration - NO FALLBACK TO LOCALHOST!

// ONLY use environment variables
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST'));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'raysofgrace_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER'));
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS'));
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: 4000);

// Production error settings
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');