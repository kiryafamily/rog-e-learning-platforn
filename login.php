<?php
// login.php - CLEAN PRODUCTION VERSION WITH HORIZONTAL NAV
// NO DEBUGGING ECHOES - SILENT EXECUTION
// This page handles user login, including form display and processing. It checks if the user is already logged in and redirects them to the dashboard if so. When the login form is submitted, it validates the credentials against the database and sets session variables accordingly. The design is simple and focused on usability, with clear error messages for failed login attempts and a link to the registration page for new users.

// Start output buffering at the VERY TOP to prevent header errors
ob_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify connection is still alive
    try {
        $pdo->query("SELECT 1")->fetch();
    } catch (PDOException $e) {
        if ($e->getCode() == 2006) {
            // Try to reconnect
            require_once 'includes/config.php'; // Reload config to reconnect
        }
    }
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($pdo, $email, $password);
    
    if ($result['success']) {
        if ($result['role'] === 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}

// Clear any output buffer before sending HTML
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login | ROGELE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        :root {
            --purple: #4B1C3C;
            --purple-dark: #2F1224;
            --gold: #FFB800;
            --white: #FFFFFF;
            --gray-light: #F5F5F5;
            --gray: #666666;
            --shadow: 0 2px 8px rgba(75, 28, 60, 0.08);
        }

        body {
            background: var(--gray-light);
            min-height: 100vh;
        }

        /* ===== HORIZONTAL NAVIGATION - ALWAYS HORIZONTAL ===== */
        .navbar {
            background: var(--white);
            padding: 12px 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .navbar .container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .navbar a:first-child {
            display: block;
            line-height: 0;
            flex-shrink: 0;
        }

        .navbar img {
            height: 45px;
            width: auto;
            transition: all 0.3s ease;
        }

        .navbar img:hover {
            transform: scale(1.05);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--purple);
            color: var(--purple);
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-outline:hover {
            background: var(--purple);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-outline i {
            font-size: 1rem;
        }

        /* ===== AUTH SECTION ===== */
        .auth-section {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(75, 28, 60, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%);
        }
        
        .auth-box {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(75, 28, 60, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-header h2 {
            color: white;
            margin: 0;
            font-size: 2rem;
        }
        
        .auth-header p {
            color: rgba(255, 255, 255, 0.9);
            margin-top: 10px;
        }
        
        .auth-form {
            padding: 30px;
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
            margin-right: 5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E0E0E0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FFB800;
            box-shadow: 0 0 0 3px rgba(255, 184, 0, 0.1);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            color: var(--gray);
        }
        
        .checkbox input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
        }
        
        .forgot-link {
            color: #4B1C3C;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-link:hover {
            color: #FFB800;
        }
        
        .btn-primary {
            background: var(--purple);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-block {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
        }
        
        .auth-footer {
            padding: 20px 30px;
            text-align: center;
            background: #F5F5F5;
            border-top: 1px solid #E0E0E0;
        }
        
        .auth-footer a {
            color: #4B1C3C;
            font-weight: 600;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            color: #FFB800;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 30px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #FEE;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #EFE;
            color: #090;
            border: 1px solid #cfc;
        }
        
        .family-discount-note {
            padding: 15px 30px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .family-discount-note i {
            font-size: 1.5rem;
            color: #FFB800;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Tablets (768px and below) */
        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
            }
            
            .navbar img {
                height: 38px;
            }
            
            .btn-outline {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .auth-header h2 {
                font-size: 1.8rem;
            }
        }

        /* Mobile Phones (480px and below) */
        @media (max-width: 480px) {
            /* ===== HORIZONTAL NAVIGATION - STAYS HORIZONTAL ===== */
            .navbar {
                padding: 8px 12px;
            }
            
            .navbar .container {
                flex-direction: row;        /* Keep horizontal */
                align-items: center;
                justify-content: space-between;
                flex-wrap: nowrap;          /* Prevent wrapping */
                gap: 8px;
            }
            
            .navbar img {
                height: 32px;               /* Smaller logo */
            }
            
            .btn-outline {
                padding: 6px 12px;
                font-size: 0.8rem;
                white-space: nowrap;
                gap: 4px;
            }
            
            .btn-outline i {
                font-size: 0.9rem;
            }
            
            .btn-outline span {
                display: inline;             /* Keep text visible */
            }
            
            /* Auth box adjustments */
            .auth-section {
                padding: 20px 15px;
            }
            
            .auth-header {
                padding: 25px 20px;
            }
            
            .auth-header h2 {
                font-size: 1.5rem;
            }
            
            .auth-form {
                padding: 25px 20px;
            }
            
            .form-group input {
                padding: 12px 14px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-block {
                padding: 12px;
                font-size: 1rem;
            }
            
            .auth-footer {
                padding: 15px 20px;
            }
            
            .family-discount-note {
                padding: 15px 20px 25px;
            }
        }

        /* Small Mobile (360px and below) */
        @media (max-width: 360px) {
            /* Navigation stays horizontal but more compact */
            .navbar {
                padding: 6px 10px;
            }
            
            .navbar .container {
                gap: 4px;
            }
            
            .navbar img {
                height: 28px;
            }
            
            .btn-outline {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            .btn-outline i {
                font-size: 0.8rem;
            }
            
            .auth-header h2 {
                font-size: 1.3rem;
            }
            
            .auth-header p {
                font-size: 0.9rem;
            }
        }

        /* Landscape Mode */
        @media (max-width: 768px) and (orientation: landscape) {
            .auth-section {
                min-height: auto;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation - Always Horizontal -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php">
                <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-home"></i> <span>Back to Home</span>
            </a>
        </div>
    </nav>

    <!-- Login Form Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-box">
                <div class="auth-header">
                    <h2>Welcome Back!</h2>
                    <p>Login to access your learning dashboard</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="your@email.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            placeholder="Enter your password"
                        >
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Subscribe Now</a></p>
                </div>
                
                <!-- Family Discount Notice -->
                <div class="family-discount-note">
                    <i class="fas fa-users"></i>
                    <p><strong>Family Discount Available:</strong> 20% off for multiple children</p>
                </div>
            </div>
        </div>
    </section>
    
    <script src="js/navbar.js"></script>
</body>
</html>
<?php
// End output buffering and send output
ob_end_flush();
?>