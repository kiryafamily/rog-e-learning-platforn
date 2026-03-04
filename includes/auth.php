<?php
// Minimal auth.php for testing
error_log("=== MINIMAL AUTH.PHP LOADED ===");

require_once 'config.php';
require_once 'functions.php';

echo "<!-- auth.php loaded successfully -->";

function registerUser($pdo, $data) {
    return ['success' => false, 'message' => 'Registration disabled'];
}

function loginUser($pdo, $email, $password) {
    return ['success' => false, 'message' => 'Login disabled'];
}

function logoutUser() {
    $_SESSION = array();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out'];
}

// Stub functions for others
function changePassword($pdo, $userId, $currentPassword, $newPassword) {
    return ['success' => false, 'message' => 'Function disabled'];
}

function resetPassword($pdo, $email) {
    return ['success' => false, 'message' => 'Function disabled'];
}

function updateProfile($pdo, $userId, $data) {
    return ['success' => false, 'message' => 'Function disabled'];
}
?>