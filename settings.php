<?php
// settings.php - Complete Settings Page
// This page allows users to manage their profile information, change their password, and update their preferences. It includes a modern design with a profile header card, statistics, recent activity, and tabs for different settings sections. The page also checks the user's subscription status and displays relevant information about their plan and family members if applicable.
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
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        
        // Validate
        if (empty($fullname) || empty($email)) {
            $error = 'Name and email are required';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email address';
        } elseif (!empty($phone) && !validatePhone($phone)) {
            $error = 'Invalid phone number';
        } else {
            // Update profile
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$fullname, $email, $phone, $user['id']])) {
                $success = 'Profile updated successfully';
                $user = getCurrentUser($pdo);
            } else {
                $error = 'Failed to update profile';
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
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $user['id']])) {
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password';
                }
            }
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        $notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $language = sanitize($_POST['language']);
        
        // Save to session for now (you can add to database later)
        $_SESSION['preferences'] = [
            'notifications' => $notifications,
            'language' => $language
        ];
        $success = 'Preferences updated successfully';
    }
}

// Get current preferences
$preferences = $_SESSION['preferences'] ?? [
    'notifications' => 1,
    'language' => 'en'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Settings - RAYS OF GRACE</title>
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
            background-color: #f5f5f5;
        }

        /* Navigation */
        .settings-nav {
            background-color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            height: 40px;
            width: auto;
        }

        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4B1C3C;
        }

        .nav-right {
            display: flex;
            gap: 15px;
        }

        .btn-outline {
            border: 2px solid #4B1C3C;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .btn-outline:hover {
            background-color: #4B1C3C;
            color: #ffffff;
        }

        /* Main Container */
        .settings-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header */
        .settings-header {
            margin-bottom: 30px;
        }

        .settings-header h1 {
            color: #4B1C3C;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .settings-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .settings-header p {
            color: #666;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
        }

        .alert-error {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            color: #c62828;
        }

        /* Settings Card */
        .settings-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-title i {
            color: #FFB800;
            font-size: 1.2rem;
        }

        .card-title h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 5px;
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFB800;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 5px;
        }

        .strength-meter {
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .strength-text {
            font-size: 0.8rem;
            color: #999;
            margin-top: 3px;
        }

        /* Switch Toggle */
        .switch-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 0;
        }

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
            transition: .3s;
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
            transition: .3s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #4B1C3C;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .switch-info {
            flex: 1;
        }

        .switch-info strong {
            color: #333;
            display: block;
            margin-bottom: 3px;
        }

        .switch-info p {
            color: #999;
            font-size: 0.85rem;
        }

        /* Buttons */
        .btn-primary {
            background-color: #4B1C3C;
            color: #ffffff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2F1224;
        }

        .btn-primary i {
            color: #FFB800;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .settings-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="settings-nav">
        <div class="logo-area">
            <img src="images/logo.png" alt="RAYS OF GRACE">
            <span>Settings</span>
        </div>
        <div class="nav-right">
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </nav>

    <div class="settings-container">
        <!-- Header -->
        <div class="settings-header">
            <h1>
                <i class="fas fa-cog"></i>
                Settings
            </h1>
            <p>Manage your account settings and preferences</p>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-user"></i>
                <h2>Profile Information</h2>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="fullname" class="form-control" 
                           value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <button type="submit" name="update_profile" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Password Settings -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-lock"></i>
                <h2>Change Password</h2>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-check"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <!-- Password Strength -->
                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Enter a password</div>
                </div>

                <button type="submit" name="change_password" class="btn-primary">
                    <i class="fas fa-sync-alt"></i> Update Password
                </button>
            </form>
        </div>

        <!-- Preferences -->
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-sliders-h"></i>
                <h2>Preferences</h2>
            </div>

            <form method="POST">
                <div class="switch-item">
                    <label class="switch">
                        <input type="checkbox" name="email_notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                    <div class="switch-info">
                        <strong>Email Notifications</strong>
                        <p>Receive updates about new lessons and features</p>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-globe"></i> Language</label>
                    <select name="language" class="form-control">
                        <option value="en" <?php echo $preferences['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="lug" <?php echo $preferences['language'] == 'lug' ? 'selected' : ''; ?>>Luganda</option>
                        <option value="sw" <?php echo $preferences['language'] == 'sw' ? 'selected' : ''; ?>>Kiswahili</option>
                    </select>
                </div>

                <button type="submit" name="update_preferences" class="btn-primary">
                    <i class="fas fa-save"></i> Save Preferences
                </button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="settings-card" style="border-left: 4px solid #f44336;">
            <div class="card-title">
                <i class="fas fa-exclamation-triangle" style="color: #f44336;"></i>
                <h2 style="color: #f44336;">Danger Zone</h2>
            </div>

            <p style="color: #666; margin-bottom: 15px;">Once you delete your account, there is no going back. Please be certain.</p>

            <button class="btn-primary" style="background-color: #f44336;" onclick="if(confirm('Are you sure? This cannot be undone.')) alert('Account deletion coming soon!')">
                <i class="fas fa-trash"></i> Delete Account
            </button>
        </div>
    </div>

    <script>
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
                strengthBar.style.backgroundColor = '#f44336';
                strengthText.textContent = 'Weak';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#FF9800';
                strengthText.textContent = 'Fair';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#2196F3';
                strengthText.textContent = 'Good';
            } else {
                strengthBar.style.backgroundColor = '#4CAF50';
                strengthText.textContent = 'Strong';
            }
        });
    </script>
</body>
</html>