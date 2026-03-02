<?php
// Production configuration are ready

// Only define if not already defined
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'raysofgrace_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL') ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
if (!defined('SITE_NAME')) define('SITE_NAME', 'RAYS OF GRACE Junior School');
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Production error settings
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');