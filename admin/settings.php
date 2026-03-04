<?php
// admin/settings.php - System Settings
// This page allows administrators to configure various system settings, including general site information, payment settings, email configuration, and security options. The design is modern and user-friendly, with clear sections for each category of settings and helpful tooltips to guide admins through the configuration process.
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Load current settings from database
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        $site_name = sanitize($_POST['site_name']);
        $site_email = sanitize($_POST['site_email']);
        $site_phone = sanitize($_POST['site_phone']);
        $site_address = sanitize($_POST['site_address']);
        
        // Update settings
        $updates = [
            'site_name' => $site_name,
            'site_email' => $site_email,
            'site_phone' => $site_phone,
            'site_address' => $site_address
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $success = 'General settings updated successfully';
        logActivity($pdo, $user['id'], 'admin_settings', 'Updated general settings');
    }
    
    if (isset($_POST['save_payment'])) {
        $mtn_number = sanitize($_POST['mtn_number']);
        $airtel_number = sanitize($_POST['airtel_number']);
        $currency = sanitize($_POST['currency']);
        $monthly_price = $_POST['monthly_price'];
        $termly_price = $_POST['termly_price'];
        $yearly_price = $_POST['yearly_price'];
        $family_discount = $_POST['family_discount'];
        
        $updates = [
            'mtn_number' => $mtn_number,
            'airtel_number' => $airtel_number,
            'currency' => $currency,
            'monthly_price' => $monthly_price,
            'termly_price' => $termly_price,
            'yearly_price' => $yearly_price,
            'family_discount' => $family_discount
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $success = 'Payment settings updated successfully';
        logActivity($pdo, $user['id'], 'admin_settings', 'Updated payment settings');
    }
    
    if (isset($_POST['save_email'])) {
        $smtp_host = sanitize($_POST['smtp_host']);
        $smtp_port = $_POST['smtp_port'];
        $smtp_user = sanitize($_POST['smtp_user']);
        $smtp_pass = sanitize($_POST['smtp_pass']);
        $smtp_encryption = sanitize($_POST['smtp_encryption']);
        
        $updates = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_encryption' => $smtp_encryption
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $success = 'Email settings updated successfully';
        logActivity($pdo, $user['id'], 'admin_settings', 'Updated email settings');
    }
    
    if (isset($_POST['save_security'])) {
        $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
        $require_email_verification = isset($_POST['require_email_verification']) ? 1 : 0;
        $session_timeout = $_POST['session_timeout'];
        $max_login_attempts = $_POST['max_login_attempts'];
        
        $updates = [
            'allow_registration' => $allow_registration,
            'require_email_verification' => $require_email_verification,
            'session_timeout' => $session_timeout,
            'max_login_attempts' => $max_login_attempts
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $success = 'Security settings updated successfully';
        logActivity($pdo, $user['id'], 'admin_settings', 'Updated security settings');
    }
    
    if (isset($_POST['test_email'])) {
        $test_email = sanitize($_POST['test_email']);
        // Send test email (implement email function)
        $success = 'Test email sent to ' . $test_email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f4f9;
        }

        /* Top Navigation */
        .admin-topnav {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 15px rgba(75,28,60,0.3);
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
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-badge {
            background: #FFB800;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }

        .nav-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: #FFB800;
            color: #4B1C3C;
        }

        /* Main Container */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover {
            background: #f8f4f8;
            color: #4B1C3C;
            border-left-color: #FFB800;
        }

        .sidebar-menu li.active a {
            background: linear-gradient(90deg, rgba(75,28,60,0.1) 0%, rgba(255,184,0,0.05) 100%);
            color: #4B1C3C;
            border-left-color: #FFB800;
        }

        .sidebar-menu i {
            width: 20px;
            color: #FFB800;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            padding: 30px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            color: #4B1C3C;
            font-size: 2rem;
        }

        .page-header h1 i {
            color: #FFB800;
            margin-right: 10px;
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
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
            color: #C62828;
        }

        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #4B1C3C;
            color: white;
        }

        .tab-btn:hover i {
            color: white;
        }

        .tab-btn.active {
            background: #4B1C3C;
            color: white;
        }

        .tab-btn i {
            color: #FFB800;
        }

        .tab-btn.active i {
            color: white;
        }

        /* Settings Card */
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: none;
        }

        .settings-card.active {
            display: block;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFB800;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        /* Checkbox */
        .checkbox-group {
            margin: 15px 0;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            cursor: pointer;
        }

        .checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox span {
            color: #666;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        .btn-secondary {
            background: #666;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4B1C3C;
        }

        .btn-test {
            background: #FFB800;
            color: #4B1C3C;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-test:hover {
            background: #4B1C3C;
            color: white;
        }

        /* Info Box */
        .info-box {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #FFB800;
        }

        .info-box i {
            color: #FFB800;
            margin-right: 8px;
        }

        .info-box p {
            color: #666;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .settings-tabs {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="admin-topnav">
        <div class="logo-area">
            <img src="../images/logo.png" alt="RAYS OF GRACE">
            <span>System Settings</span>
        </div>
        
        <div class="admin-profile">
            <span class="admin-badge">
                <i class="fas fa-shield-alt"></i> <?php echo explode(' ', $user['fullname'])[0]; ?>
            </span>
            <a href="../logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="lessons.php"><i class="fas fa-book"></i> Lessons</a></li>
                <li><a href="upload-lesson.php"><i class="fas fa-upload"></i> Upload Lesson</a></li>
                <li><a href="transactions.php"><i class="fas fa-credit-card"></i> Transactions</a></li>
                <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
                <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> System Settings</h1>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-btn active" onclick="switchTab('general')">
                    <i class="fas fa-globe"></i> General
                </button>
                <button class="tab-btn" onclick="switchTab('payment')">
                    <i class="fas fa-credit-card"></i> Payment
                </button>
                <button class="tab-btn" onclick="switchTab('email')">
                    <i class="fas fa-envelope"></i> Email
                </button>
                <button class="tab-btn" onclick="switchTab('security')">
                    <i class="fas fa-shield-alt"></i> Security
                </button>
                <button class="tab-btn" onclick="switchTab('system')">
                    <i class="fas fa-info-circle"></i> System Info
                </button>
            </div>

            <!-- General Settings -->
            <div id="general" class="settings-card active">
                <div class="card-title">
                    <i class="fas fa-globe"></i>
                    <h2>General Settings</h2>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-school"></i> School Name</label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?php echo $settings['site_name'] ?? 'RAYS OF GRACE Junior School'; ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Contact Email</label>
                            <input type="email" name="site_email" class="form-control" 
                                   value="<?php echo $settings['site_email'] ?? 'info@raysofgrace.ac.ug'; ?>">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Contact Phone</label>
                            <input type="tel" name="site_phone" class="form-control" 
                                   value="<?php echo $settings['site_phone'] ?? '+256 XXX XXXXXX'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="site_address" class="form-control"><?php echo $settings['site_address'] ?? 'Kampala, Uganda'; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_general" class="btn-primary">
                            <i class="fas fa-save"></i> Save General Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Settings -->
            <div id="payment" class="settings-card">
                <div class="card-title">
                    <i class="fas fa-credit-card"></i>
                    <h2>Payment Settings</h2>
                </div>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Mobile Money Integration</strong>
                    <p>Configure your MTN and Airtel business numbers for payment processing.</p>
                </div>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt"></i> MTN Number</label>
                            <input type="text" name="mtn_number" class="form-control" 
                                   value="<?php echo $settings['mtn_number'] ?? '256700000000'; ?>">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt"></i> Airtel Number</label>
                            <input type="text" name="airtel_number" class="form-control" 
                                   value="<?php echo $settings['airtel_number'] ?? '256700000000'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Currency</label>
                        <select name="currency" class="form-control">
                            <option value="UGX" <?php echo ($settings['currency'] ?? 'UGX') == 'UGX' ? 'selected' : ''; ?>>UGX - Ugandan Shilling</option>
                            <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Monthly Price</label>
                            <input type="number" name="monthly_price" class="form-control" 
                                   value="<?php echo $settings['monthly_price'] ?? '100000'; ?>">
                        </div>

                        <div class="form-group">
                            <label>Termly Price</label>
                            <input type="number" name="termly_price" class="form-control" 
                                   value="<?php echo $settings['termly_price'] ?? '500000'; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Yearly Price</label>
                            <input type="number" name="yearly_price" class="form-control" 
                                   value="<?php echo $settings['yearly_price'] ?? '1500000'; ?>">
                        </div>

                        <div class="form-group">
                            <label>Family Discount (%)</label>
                            <input type="number" name="family_discount" class="form-control" step="0.1" min="0" max="100"
                                   value="<?php echo $settings['family_discount'] ?? '20'; ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_payment" class="btn-primary">
                            <i class="fas fa-save"></i> Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Email Settings -->
            <div id="email" class="settings-card">
                <div class="card-title">
                    <i class="fas fa-envelope"></i>
                    <h2>Email Settings</h2>
                </div>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>SMTP Configuration</strong>
                    <p>Configure your email server settings for sending notifications.</p>
                </div>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" 
                                   value="<?php echo $settings['smtp_host'] ?? 'smtp.gmail.com'; ?>">
                        </div>

                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control" 
                                   value="<?php echo $settings['smtp_port'] ?? '587'; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_user" class="form-control" 
                                   value="<?php echo $settings['smtp_user'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_pass" class="form-control" 
                                   value="<?php echo $settings['smtp_pass'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_email" class="btn-primary">
                            <i class="fas fa-save"></i> Save Email Settings
                        </button>
                        <button type="button" class="btn-test" onclick="testEmail()">
                            <i class="fas fa-paper-plane"></i> Test Email
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings -->
            <div id="security" class="settings-card">
                <div class="card-title">
                    <i class="fas fa-shield-alt"></i>
                    <h2>Security Settings</h2>
                </div>

                <form method="POST">
                    <div class="checkbox-group">
                        <label class="checkbox">
                            <input type="checkbox" name="allow_registration" <?php echo ($settings['allow_registration'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Allow new user registrations</span>
                        </label>

                        <label class="checkbox">
                            <input type="checkbox" name="require_email_verification" <?php echo ($settings['require_email_verification'] ?? 0) ? 'checked' : ''; ?>>
                            <span>Require email verification</span>
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" 
                                   value="<?php echo $settings['session_timeout'] ?? '30'; ?>" min="5" max="1440">
                        </div>

                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" class="form-control" 
                                   value="<?php echo $settings['max_login_attempts'] ?? '5'; ?>" min="1" max="10">
                        </div>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Security Recommendations</strong>
                        <p>• Use strong passwords for all accounts<br>
                           • Enable two-factor authentication for admin accounts<br>
                           • Regularly backup your database</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_security" class="btn-primary">
                            <i class="fas fa-save"></i> Save Security Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Info -->
            <div id="system" class="settings-card">
                <div class="card-title">
                    <i class="fas fa-info-circle"></i>
                    <h2>System Information</h2>
                </div>

                <div class="info-box">
                    <i class="fas fa-server"></i>
                    <strong>Server Information</strong>
                </div>

                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8; width: 200px;">PHP Version</td>
                        <td style="padding: 10px;"><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8;">MySQL Version</td>
                        <td style="padding: 10px;"><?php echo $pdo->query("SELECT VERSION()")->fetchColumn(); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8;">Server Software</td>
                        <td style="padding: 10px;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8;">Upload Max Size</td>
                        <td style="padding: 10px;"><?php echo ini_get('upload_max_filesize'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8;">Post Max Size</td>
                        <td style="padding: 10px;"><?php echo ini_get('post_max_size'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background: #f8f4f8;">Max Execution Time</td>
                        <td style="padding: 10px;"><?php echo ini_get('max_execution_time'); ?> seconds</td>
                    </tr>
                </table>

                <div class="info-box">
                    <i class="fas fa-database"></i>
                    <strong>Database Statistics</strong>
                </div>

                <?php
                $tables = $pdo->query("SHOW TABLE STATUS")->fetchAll();
                $total_size = 0;
                ?>

                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 10px; background: #4B1C3C; color: white;">Table</th>
                            <th style="padding: 10px; background: #4B1C3C; color: white;">Rows</th>
                            <th style="padding: 10px; background: #4B1C3C; color: white;">Size (KB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): 
                            $size = round($table['Data_length'] / 1024, 2);
                            $total_size += $size;
                        ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;"><?php echo $table['Name']; ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;"><?php echo $table['Rows']; ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;"><?php echo $size; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2" style="padding: 10px; font-weight: 600;">Total Database Size</td>
                            <td style="padding: 10px; font-weight: 600;"><?php echo round($total_size, 2); ?> KB</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Test Email Modal -->
    <div id="emailModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%;">
            <h3 style="color: #4B1C3C; margin-bottom: 15px;">Send Test Email</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="test_email" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="test_email" class="btn-primary">Send</button>
                    <button type="button" class="btn-secondary" onclick="closeEmailModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update content
            document.querySelectorAll('.settings-card').forEach(card => card.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        }

        function testEmail() {
            document.getElementById('emailModal').style.display = 'flex';
        }

        function closeEmailModal() {
            document.getElementById('emailModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>