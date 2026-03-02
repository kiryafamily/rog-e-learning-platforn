<?php
// Only define if not already defined
if(!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if(!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if(!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if(!defined('DB_NAME')) {
    define('DB_NAME', 'raysofgrace_db');
}

// Error reporting
if(!defined('ERROR_REPORTING')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>