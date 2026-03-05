<?php
// profile.php - STUNNING REDESIGN
// User profile management with settings and preferences
// This page allows users to view and edit their profile information, manage their subscription, and update their preferences. It includes a modern design with a profile header card, statistics, recent activity, and tabs for different settings sections. The page also checks the user's subscription status and displays relevant information about their plan and family members if applicable.

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = sanitize($_POST['fullname']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        
        // Validate
        if (empty($fullname) || empty($phone) || empty($email)) {
            $error = 'All fields are required';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email address';
        } elseif (!validatePhone($phone)) {
            $error = 'Invalid phone number';
        } else {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email already in use by another account';
            } else {
                // Update profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET fullname = ?, phone = ?, email = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                if ($stmt->execute([$fullname, $phone, $email, $user['id']])) {
                    $success = 'Profile updated successfully';
                    $_SESSION['user_name'] = $fullname;
                    $user = getCurrentUser($pdo); // Refresh user data
                } else {
                    $error = 'Failed to update profile';
                }
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'All password fields are required';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match';
        } elseif (strlen($new) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            // Verify current password
            if (!password_verify($current, $user['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $user['id']])) {
                    $success = 'Password changed successfully';
                    logActivity($pdo, $user['id'], 'password_change', 'Password changed');
                } else {
                    $error = 'Failed to change password';
                }
            }
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        $notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_alerts = isset($_POST['sms_alerts']) ? 1 : 0;
        $language = sanitize($_POST['language'] ?? 'en');
        
        // Save preferences (you'd need a user_preferences table)
        $_SESSION['preferences'] = [
            'notifications' => $notifications,
            'sms_alerts' => $sms_alerts,
            'language' => $language
        ];
        $success = 'Preferences updated successfully';
    }
}

// Get subscription info
$stmt = $pdo->prepare("
    SELECT * FROM subscriptions 
    WHERE user_id = ? AND status = 'active' 
    ORDER BY end_date DESC LIMIT 1
");
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch();

// Get family members
$familyMembers = [];
if ($user['family_id']) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE family_id = ? AND id != ?
        ORDER BY role, fullname
    ");
    $stmt->execute([$user['family_id'], $user['id']]);
    $familyMembers = $stmt->fetchAll();
}

// Get recent activity
$stmt = $pdo->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$activities = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT lesson_id) as lessons_started,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as lessons_completed,
        (SELECT COUNT(*) FROM quiz_results WHERE user_id = ?) as quizzes_taken
    FROM progress
    WHERE user_id = ?
");
$stmt->execute([$user['id'], $user['id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8f4f8;
        }
        
        /* Top Navigation */
        .dashboard-nav {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(75,28,60,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-area img {
            height: 45px;
            width: auto;
            border-radius: 8px;
        }
        
        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4B1C3C;
        }
        
        .logo-area small {
            display: block;
            font-size: 0.7rem;
            color: #FFB800;
            letter-spacing: 1px;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #4B1C3C;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }
        
        .btn-outline i {
            color: #FFB800;
        }
        
        /* Main Container */
        .profile-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        /* Profile Header Card */
        .profile-header-card {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 15px 35px rgba(75,28,60,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,184,0,0.1);
            border-radius: 50%;
        }
        
        .profile-header-card::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: rgba(255,184,0,0.05);
            border-radius: 50%;
        }
        
        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 40px;
            position: relative;
            z-index: 2;
            flex-wrap: wrap;
        }
        
        .profile-avatar-wrapper {
            position: relative;
        }
        
        .profile-avatar {
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, #FFB800 0%, #D99B00 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: 700;
            color: #4B1C3C;
            border: 4px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .avatar-edit-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4B1C3C;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .avatar-edit-btn:hover {
            background: #FFB800;
            color: white;
            transform: scale(1.1);
        }
        
        .profile-title-section {
            flex: 1;
        }
        
        .profile-title-section h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 5px;
        }
        
        .profile-role-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .role-tag {
            background: #FFB800;
            color: #4B1C3C;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .member-since {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .subscription-status {
            margin-top: 15px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge.active {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        .status-badge.inactive {
            background: rgba(244,67,54,0.2);
            color: #f44336;
            border: 1px solid #f44336;
        }
        
        .plan-badge {
            background: rgba(255,255,255,0.1);
            padding: 8px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            border: 1px solid #FFB800;
        }
        
        .plan-badge i {
            color: #FFB800;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(75,28,60,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,184,0,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon i {
            font-size: 2rem;
            color: #FFB800;
        }
        
        .stat-details {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4B1C3C;
            line-height: 1.2;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }
        
        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
            color: #C62828;
        }
        
        /* Tabs */
        .tabs-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .tabs-header {
            display: flex;
            background: #f8f4f8;
            border-bottom: 1px solid #e0e0e0;
            overflow-x: auto;
            padding: 5px;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            border-radius: 10px;
        }
        
        .tab-btn i {
            color: #FFB800;
        }
        
        .tab-btn:hover {
            color: #4B1C3C;
            background: rgba(75,28,60,0.05);
        }
        
        .tab-btn.active {
            background: #4B1C3C;
            color: white;
        }
        
        .tab-btn.active i {
            color: white;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        /* Forms */
        .form-container {
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4B1C3C;
        }
        
        .form-group label i {
            color: #FFB800;
            margin-right: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FFB800;
            box-shadow: 0 0 0 3px rgba(255,184,0,0.1);
        }
        
        .form-control[readonly] {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .input-group .form-control {
            flex: 1;
        }
        
        .btn-copy {
            padding: 12px 20px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            color: #4B1C3C;
            transition: all 0.3s ease;
        }
        
        .btn-copy:hover {
            background: #4B1C3C;
            color: white;
            border-color: #4B1C3C;
        }
        
        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75,28,60,0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #4B1C3C;
            color: #4B1C3C;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }
        
        /* Password Strength */
        .password-strength {
            margin: 15px 0;
        }
        
        .strength-meter {
            height: 5px;
            background: #f0f0f0;
            border-radius: 3px;
            margin-bottom: 5px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Security Section */
        .security-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .security-section h3 {
            color: #4B1C3C;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-section h3 i {
            color: #FFB800;
        }
        
        .session-item {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0;
        }
        
        .session-item.current {
            border-left: 4px solid #4CAF50;
        }
        
        .session-item i {
            font-size: 1.5rem;
            color: #4B1C3C;
        }
        
        /* Preferences */
        .preference-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .preference-item:last-child {
            border-bottom: none;
        }
        
        /* Switch Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #4B1C3C;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Radio Group */
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        /* Family Section */
        .family-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .family-header h3 {
            color: #4B1C3C;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .family-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .family-card {
            background: #f8f4f8;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            gap: 15px;
            transition: all 0.3s ease;
        }
        
        .family-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(75,28,60,0.1);
        }
        
        .family-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .family-info {
            flex: 1;
        }
        
        .family-info h4 {
            color: #4B1C3C;
            margin-bottom: 3px;
        }
        
        .family-role {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        
        .family-email {
            color: #999;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        
        .family-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }
        
        .progress-bar {
            flex: 1;
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4B1C3C, #FFB800);
            border-radius: 3px;
        }
        
        .progress-value {
            font-size: 0.9rem;
            color: #4B1C3C;
            font-weight: 500;
        }
        
        .view-link {
            color: #4B1C3C;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .view-link:hover {
            color: #FFB800;
            gap: 8px;
        }
        
        .no-family {
            text-align: center;
            padding: 50px;
            background: #f8f4f8;
            border-radius: 12px;
        }
        
        .no-family i {
            font-size: 3rem;
            color: #4B1C3C;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .family-code-box {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .family-code-box code {
            font-size: 1.2rem;
            color: #4B1C3C;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, #FFB800 0%, #D99B00 100%);
            color: #4B1C3C;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Activity Timeline */
        .timeline {
            margin: 20px 0;
        }
        
        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .timeline-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-action {
            font-weight: 500;
            color: #4B1C3C;
            margin-bottom: 3px;
        }
        
        .timeline-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        
        .timeline-time {
            color: #999;
            font-size: 0.8rem;
        }
        
        /* Export Section */
        .export-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .export-section h3 {
            color: #4B1C3C;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-section h3 i {
            color: #FFB800;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            max-width: 450px;
            width: 90%;
            padding: 30px;
            border-radius: 15px;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #4B1C3C;
        }
        
        .modal h2 {
            color: #4B1C3C;
            margin-bottom: 10px;
        }
        
        .modal p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            
            .profile-role-badge {
                justify-content: center;
            }
            
            .subscription-status {
                justify-content: center;
            }
            
            .tabs-header {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                flex: 1;
            }
            
            .family-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="logo-area">
            <img src="images/logo-3.png" alt="RAYS OF GRACE">
        </div>
        <div class="nav-right">
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </nav>

    <div class="profile-wrapper">
        <!-- Profile Header -->
        <div class="profile-header-card">
            <div class="profile-header-content">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                    </div>
                    <button class="avatar-edit-btn" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="avatarInput" style="display: none;" accept="image/*">
                </div>
                
                <div class="profile-title-section">
                    <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
                    
                    <div class="profile-role-badge">
                        <span class="role-tag">
                            <i class="fas fa-user-tag"></i> <?php echo ucfirst($user['role']); ?>
                        </span>
                        <?php if ($user['class']): ?>
                        <span class="role-tag">
                            <i class="fas fa-graduation-cap"></i> Class <?php echo $user['class']; ?>
                        </span>
                        <?php endif; ?>
                        <span class="member-since">
                            <i class="far fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="subscription-status">
                        <?php if ($subscription): ?>
                        <span class="status-badge active">
                            <i class="fas fa-check-circle"></i> Active Subscriber
                        </span>
                        <span class="plan-badge">
                            <i class="fas fa-crown"></i> <?php echo ucfirst($subscription['plan']); ?> Plan
                        </span>
                        <?php else: ?>
                        <span class="status-badge inactive">
                            <i class="fas fa-exclamation-triangle"></i> No Active Subscription
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-number"><?php echo $stats['lessons_started'] ?? 0; ?></div>
                    <div class="stat-label">Lessons Started</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-number"><?php echo $stats['lessons_completed'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-number"><?php echo $stats['quizzes_taken'] ?? 0; ?></div>
                    <div class="stat-label">Quizzes Taken</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-number"><?php echo count($familyMembers); ?></div>
                    <div class="stat-label">Family Members</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle fa-2x"></i>
            <div>
                <strong>Success!</strong> <?php echo $success; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle fa-2x"></i>
            <div>
                <strong>Error!</strong> <?php echo $error; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs Section -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab('info')">
                    <i class="fas fa-user"></i> Personal Info
                </button>
                <button class="tab-btn" onclick="switchTab('security')">
                    <i class="fas fa-lock"></i> Security
                </button>
                <button class="tab-btn" onclick="switchTab('preferences')">
                    <i class="fas fa-sliders-h"></i> Preferences
                </button>
                <button class="tab-btn" onclick="switchTab('family')">
                    <i class="fas fa-users"></i> Family
                </button>
                <button class="tab-btn" onclick="switchTab('activity')">
                    <i class="fas fa-history"></i> Activity
                </button>
            </div>

            <div class="tab-content">
                <!-- Personal Info Tab -->
                <div id="info-tab" class="tab-pane active">
                    <form method="POST" action="" class="form-container">
                        <div class="form-group">
                            <label for="fullname">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="fullname" name="fullname" class="form-control"
                                   value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-id-card"></i> Family Code
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       value="<?php echo $user['family_id'] ?? 'Not in a family'; ?>" readonly>
                                <?php if ($user['family_id']): ?>
                                <button type="button" class="btn-copy" onclick="copyFamilyCode()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="security-tab" class="tab-pane">
                    <form method="POST" action="" class="form-container">
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-lock"></i> Current Password
                            </label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-key"></i> New Password
                            </label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" required>
                            <small class="strength-text">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-check-circle"></i> Confirm New Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Enter a password</span>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-sync-alt"></i> Change Password
                        </button>
                    </form>
                    
                    <div class="security-section">
                        <h3><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h3>
                        <p style="color: #666; margin-bottom: 15px;">Enhance your account security with 2FA</p>
                        <button class="btn-outline" onclick="enable2FA()">
                            <i class="fas fa-mobile-alt"></i> Enable 2FA
                        </button>
                    </div>
                    
                    <div class="security-section">
                        <h3><i class="fas fa-laptop"></i> Active Sessions</h3>
                        <div class="session-item current">
                            <i class="fas fa-laptop"></i>
                            <div>
                                <strong>Current Device</strong>
                                <p style="color: #666;">Chrome on Windows • <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'; ?></p>
                            </div>
                            <span style="margin-left: auto; background: #4CAF50; color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem;">Active Now</span>
                        </div>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div id="preferences-tab" class="tab-pane">
                    <form method="POST" action="" class="form-container">
                        <h3 style="color: #4B1C3C; margin-bottom: 20px;">Notification Settings</h3>
                        
                        <div class="preference-item">
                            <label class="switch">
                                <input type="checkbox" name="email_notifications" checked>
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong>Email Notifications</strong>
                                <p style="color: #666;">Receive updates about new lessons and features</p>
                            </div>
                        </div>
                        
                        <div class="preference-item">
                            <label class="switch">
                                <input type="checkbox" name="sms_alerts">
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong>SMS Alerts</strong>
                                <p style="color: #666;">Get SMS notifications for important updates</p>
                            </div>
                        </div>
                        
                        <h3 style="color: #4B1C3C; margin: 30px 0 20px;">Learning Preferences</h3>
                        
                        <div class="form-group">
                            <label for="language">Preferred Language</label>
                            <select id="language" name="language" class="form-control">
                                <option value="en">English</option>
                                <option value="lug">Luganda</option>
                                <option value="sw">Kiswahili</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Video Quality</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="video_quality" value="auto" checked> Auto
                                </label>
                                <label>
                                    <input type="radio" name="video_quality" value="high"> High
                                </label>
                                <label>
                                    <input type="radio" name="video_quality" value="low"> Low
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_preferences" class="btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </form>
                </div>

                <!-- Family Tab -->
                <div id="family-tab" class="tab-pane">
                    <div class="family-header">
                        <h3><i class="fas fa-users"></i> Family Members</h3>
                        <?php if ($user['role'] === 'parent'): ?>
                        <button class="btn-primary" onclick="showModal('familyModal')">
                            <i class="fas fa-plus"></i> Add Child
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($familyMembers)): ?>
                    <div class="no-family">
                        <i class="fas fa-users"></i>
                        <h4>No Family Members Yet</h4>
                        <p style="color: #666;">Share your family code to get 20% discount</p>
                        <div class="family-code-box">
                            <code><?php echo $user['family_id'] ?? 'Generate a family code'; ?></code>
                            <button class="btn-copy" onclick="copyFamilyCode()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="family-grid">
                        <?php foreach ($familyMembers as $member): ?>
                        <div class="family-card">
                            <div class="family-avatar">
                                <?php echo strtoupper(substr($member['fullname'], 0, 1)); ?>
                            </div>
                            <div class="family-info">
                                <h4><?php echo htmlspecialchars($member['fullname']); ?></h4>
                                <div class="family-role">
                                    <i class="fas fa-user-tag" style="color: #FFB800;"></i>
                                    <?php echo ucfirst($member['role']); ?> • Class <?php echo $member['class']; ?>
                                </div>
                                <div class="family-email">
                                    <i class="fas fa-envelope" style="color: #FFB800;"></i>
                                    <?php echo $member['email']; ?>
                                </div>
                                
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(*) as completed 
                                    FROM progress 
                                    WHERE user_id = ? AND status = 'completed'
                                ");
                                $stmt->execute([$member['id']]);
                                $completed = $stmt->fetch()['completed'];
                                $totalLessons = 20; // You might want to calculate this properly
                                $progressPercent = min(($completed / max($totalLessons, 1)) * 100, 100);
                                ?>
                                
                                <div class="family-progress">
                                    <span style="font-size: 0.9rem; color: #666;">Progress:</span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
                                    </div>
                                    <span class="progress-value"><?php echo $completed; ?> lessons</span>
                                </div>
                                
                                <a href="progress.php?student=<?php echo $member['id']; ?>" class="view-link">
                                    View Progress <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="discount-badge" style="margin-top: 20px;">
                        <i class="fas fa-tag fa-2x"></i>
                        <div>
                            <strong>Family Discount: 20% OFF</strong>
                            <p style="margin-top: 5px; opacity: 0.9;">Add family members and save on subscriptions</p>
                        </div>
                    </div>
                </div>

                <!-- Activity Tab -->
                <div id="activity-tab" class="tab-pane">
                    <h3 style="color: #4B1C3C; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-history" style="color: #FFB800;"></i> Recent Activity
                    </h3>
                    
                    <div class="timeline">
                        <?php if (empty($activities)): ?>
                        <div class="no-family">
                            <i class="fas fa-clock"></i>
                            <p>No recent activity</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <i class="fas <?php 
                                        echo strpos($activity['action'], 'login') !== false ? 'fa-sign-in-alt' : 
                                            (strpos($activity['action'], 'lesson') !== false ? 'fa-book' : 
                                            (strpos($activity['action'], 'quiz') !== false ? 'fa-question' : 'fa-circle')); 
                                    ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-action">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                    </div>
                                    <?php if ($activity['details']): ?>
                                    <div class="timeline-details">
                                        <?php echo htmlspecialchars($activity['details']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="timeline-time">
                                        <i class="far fa-clock" style="margin-right: 3px;"></i>
                                        <?php echo timeAgo($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="export-section">
                        <h3><i class="fas fa-download"></i> Export Your Data</h3>
                        <p style="color: #666; margin-bottom: 15px;">Download a copy of your personal data</p>
                        <button class="btn-outline" onclick="exportData()">
                            <i class="fas fa-download"></i> Request Data Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Family Member Modal -->
    <div id="familyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('familyModal')">&times;</span>
            <h2>Add Family Member</h2>
            <p>Add a child to your family account for 20% discount</p>
            
            <form method="POST" action="add-family-member.php">
                <div class="form-group">
                    <label>Child's Full Name</label>
                    <input type="text" name="child_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="child_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Class</label>
                    <select name="child_class" class="form-control" required>
                        <option value="">Select Class</option>
                        <?php foreach (getClasses() as $class): ?>
                        <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%;">
                    Add Family Member
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }
        
        function copyFamilyCode() {
            const code = '<?php echo $user['family_id']; ?>';
            navigator.clipboard.writeText(code).then(() => {
                alert('✅ Family code copied to clipboard!');
            }).catch(() => {
                alert('❌ Failed to copy. Please select and copy manually.');
            });
        }
        
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Password strength checker
        document.getElementById('new_password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.background = '#f44336';
                strengthText.textContent = 'Weak';
            } else if (strength <= 50) {
                strengthBar.style.background = '#FFB800';
                strengthText.textContent = 'Fair';
            } else if (strength <= 75) {
                strengthBar.style.background = '#2196F3';
                strengthText.textContent = 'Good';
            } else {
                strengthBar.style.background = '#4CAF50';
                strengthText.textContent = 'Strong';
            }
        });
        
        function exportData() {
            window.location.href = 'export-user-data.php';
        }
        
        function enable2FA() {
            alert('Two-factor authentication will be available soon!');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>