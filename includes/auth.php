<?php
// includes/auth.php
// Authentication functions for RAYS OF GRACE Junior School

// Add this debug line
error_log("auth.php loaded - session status: " . session_status());

require_once 'config.php';
require_once 'functions.php';

/**
 * Register a new user
 */
function registerUser($pdo, $data) {
    // Validate required fields
    $required = ['fullname', 'email', 'phone', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => ucfirst($field) . ' is required'];
        }
    }
    
    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Validate phone
    if (!validatePhone($data['phone'])) {
        return ['success' => false, 'message' => 'Invalid phone number. Use Ugandan format'];
    }
    
    // Check password match
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Check password strength
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Check if phone already exists
    $phone = formatPhone($data['phone']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Phone number already registered'];
    }
    
    // Handle family discount
    $familyId = null;
    if (!empty($data['family_code'])) {
        $stmt = $pdo->prepare("SELECT family_id FROM users WHERE family_code = ?");
        $stmt->execute([$data['family_code']]);
        $familyId = $stmt->fetchColumn();
    }
    
    // Generate unique family code if none exists
    if (!$familyId) {
        $familyId = 'FAM' . time() . rand(100, 999);
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                fullname, email, phone, password, family_id, 
                role, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'user', 'active', NOW())
        ");
        
        $stmt->execute([
            sanitize($data['fullname']),
            $data['email'],
            $phone,
            $hashedPassword,
            $familyId
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Log activity
        logActivity($pdo, $userId, 'registration', 'New user registered');
        
        // Send welcome email
        $subject = "Welcome to " . SITE_NAME;
        $message = "Dear " . $data['fullname'] . ",\n\n";
        $message .= "Welcome to " . SITE_NAME . "! Your account has been created successfully.\n";
        $message .= "You can now subscribe to access our lessons.\n\n";
        $message .= "Best regards,\n" . SITE_NAME . " Team";
        
        sendEmail($data['email'], $subject, $message);
        
        return [
            'success' => true, 
            'message' => 'Registration successful! Please login.',
            'user_id' => $userId
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 */
function loginUser($pdo, $email, $password) {
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }
    
    // Get user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active. Contact support.'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['family_id'] = $user['family_id'];
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log activity
    logActivity($pdo, $user['id'], 'login', 'User logged in');
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'role' => $user['role']
    ];
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

/**
 * Change password
 */
function changePassword($pdo, $userId, $currentPassword, $newPassword) {
    // Get user
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters'];
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    // Log activity
    logActivity($pdo, $userId, 'password_change', 'Password changed');
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}

/**
 * Reset password (forgot password)
 */
function resetPassword($pdo, $email) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email not found'];
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save token
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expires]);
    
    // Send reset email
    $resetLink = SITE_URL . "/reset-password.php?token=" . $token;
    
    $subject = "Password Reset - " . SITE_NAME;
    $message = "Dear " . $user['fullname'] . ",\n\n";
    $message .= "You requested to reset your password. Click the link below:\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link expires in 1 hour.\n";
    $message .= "If you didn't request this, ignore this email.\n\n";
    $message .= "Best regards,\n" . SITE_NAME . " Team";
    
    sendEmail($email, $subject, $message);
    
    return ['success' => true, 'message' => 'Password reset link sent to your email'];
}

/**
 * Update profile
 */
function updateProfile($pdo, $userId, $data) {
    $updates = [];
    $params = [];
    
    // Update fullname if provided
    if (!empty($data['fullname'])) {
        $updates[] = "fullname = ?";
        $params[] = sanitize($data['fullname']);
    }
    
    // Update phone if provided
    if (!empty($data['phone'])) {
        if (!validatePhone($data['phone'])) {
            return ['success' => false, 'message' => 'Invalid phone number'];
        }
        $updates[] = "phone = ?";
        $params[] = formatPhone($data['phone']);
    }
    
    if (empty($updates)) {
        return ['success' => false, 'message' => 'No data to update'];
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        logActivity($pdo, $userId, 'profile_update', 'Profile updated');
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Update failed'];
    }
}
?>