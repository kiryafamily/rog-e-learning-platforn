<?php
// login.php
// Login page for RAYS OF GRACE Junior School
// login.php - Add this at the VERY TOP
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';

echo "Step 1: Config loaded<br>";

require_once 'includes/functions.php';
echo "Step 2: Functions loaded<br>";

// Check if sanitize function exists
if (function_exists('sanitize')) {
    echo "Step 3: sanitize() function exists<br>";
} else {
    echo "Step 3: ERROR - sanitize() function does NOT exist!<br>";
    exit;
}

require_once 'includes/auth.php';
echo "Step 4: Auth loaded<br>";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo.png" alt="RAYS OF GRACE Junior School">
                <span>
                    RAYS OF GRACE
                    <small>Junior School</small>
                </span>
            </div>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Home</a>
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
                        <i class="fas fa-sign-in-alt"></i>
                        Login
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

    <style>
    /* Auth page specific styles */
    .auth-section {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 0;
        background: linear-gradient(135deg, rgba(75, 28, 60, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%);
    }
    
    .auth-box {
        max-width: 450px;
        margin: 0 auto;
        background: white;
        border-radius: 10px;
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
    
    .auth-header h2:after {
        background: #FFB800;
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
        padding: 12px 15px;
        border: 2px solid #E0E0E0;
        border-radius: 5px;
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
        margin-bottom: 20px;
    }
    
    .checkbox {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
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
        border-radius: 5px;
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
    </style>
</body>
</html>