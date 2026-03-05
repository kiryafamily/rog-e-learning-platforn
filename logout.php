<?php
// logout.php - Simple logout script

// Start session
// This script logs the user out by destroying the session and redirecting to the homepage. It ensures that all session data is cleared and the session cookie is removed for security.
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit;
?>