<?php
// register.php - Add this at the top after session_start()
$selectedPlan = $_GET['plan'] ?? 'monthly';
// register.php
// Registration page with family discount for RAYS OF GRACE Junior School
// This page allows new users to create an account and subscribe to the digital learning platform. It includes a registration form that collects user information, applies a family discount if a valid family code is provided, and redirects users to the payment page after successful registration. The design is modern and user-friendly, with clear instructions and feedback for users throughout the registration process.

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$selectedPlan = $_GET['plan'] ?? 'monthly';
$familyDiscountApplied = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fullname' => $_POST['fullname'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'family_code' => $_POST['family_code'] ?? '',
        'plan' => $_POST['plan'] ?? 'monthly'
    ];
    
    $result = registerUser($pdo, $data);
    
    if ($result['success']) {
        $success = 'Registration successful! Please check your email and login.';
        // Store selected plan in session for payment
        $_SESSION['pending_subscription'] = [
            'user_id' => $result['user_id'],
            'plan' => $data['plan']
        ];
        // Redirect to payment page
        header('Location: payment.php?plan=' . $data['plan']);
        exit;
    } else {
        $error = $result['message'];
    }
}

// Check if family code was provided (for discount)
if (isset($_GET['family'])) {
    $stmt = $pdo->prepare("SELECT family_id FROM users WHERE family_code = ?");
    $stmt->execute([$_GET['family']]);
    if ($stmt->fetch()) {
        $familyDiscountApplied = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
            </div>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="register-section">
        <div class="container">
            <div class="register-grid">
                <!-- Registration Form -->
                <div class="register-box">
                    <div class="register-header">
                        <h2>Create Account</h2>
                        <p>Join RAYS OF GRACE digital learning platform</p>
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
                    
                    <form method="POST" action="" class="register-form" id="registerForm">
                        <input type="hidden" name="plan" value="<?php echo $selectedPlan; ?>">
                        <input type="hidden" name="plan" value="<?php echo htmlspecialchars($selectedPlan); ?>">
                        
                        <!-- Full Name -->
                        <div class="form-group">
                            <label for="fullname">
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="fullname" 
                                name="fullname" 
                                required 
                                placeholder="Enter your full name"
                                value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>"
                            >
                        </div>
                        
                        <!-- Email -->
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
                        
                        <!-- Phone -->
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number (Ugandan)
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                required 
                                placeholder="e.g., 0772XXXXXX or 256772XXXXXX"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            >
                            <small class="form-text">We'll send payment confirmation via SMS</small>
                        </div>
                        
                        <!-- Family Code (for discount) -->
                        <div class="form-group">
                            <label for="family_code">
                                <i class="fas fa-users"></i>
                                Family Code (Optional - for 20% discount)
                            </label>
                            <input 
                                type="text" 
                                id="family_code" 
                                name="family_code" 
                                placeholder="Enter family code if you have one"
                                value="<?php echo htmlspecialchars($_GET['family'] ?? ($_POST['family_code'] ?? '')); ?>"
                            >
                            <?php if ($familyDiscountApplied): ?>
                                <small class="form-text success">
                                    <i class="fas fa-check-circle"></i>
                                    Family discount will be applied!
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Password -->
                        <div class="form-row">
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
                                    placeholder="Min. 6 characters"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i>
                                    Confirm Password
                                </label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required 
                                    placeholder="Re-enter password"
                                >
                            </div>
                        </div>
                        
                        <!-- Terms -->
                        <div class="form-group terms">
                            <label class="checkbox">
                                <input type="checkbox" name="terms" required>
                                <span>
                                    I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                                    <a href="#" target="_blank">Privacy Policy</a>
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-credit-card"></i>
                            Continue to Payment
                        </button>
                    </form>
                    
                    <div class="register-footer">
                        <p>Already have an account? <a href="login.php">Login</a></p>
                    </div>
                </div>
                
                <!-- Plan Summary -->
                <div class="plan-summary">
                    <h3>Your Selected Plan</h3>
                    
                    <div class="plan-card <?php echo $selectedPlan; ?>">
                        <?php if ($selectedPlan === 'termly'): ?>
                            <div class="popular-badge">Best Value</div>
                        <?php endif; ?>
                        
                        <h4>
                            <?php 
                            echo ucfirst($selectedPlan); 
                            echo $selectedPlan === 'yearly' ? ' (Save 2 months)' : '';
                            ?>
                        </h4>
                        
                        <div class="plan-price">
                            <?php
                            $prices = [
                                'monthly' => 100000,
                                'termly' => 500000,
                                'yearly' => 1500000
                            ];
                            $basePrice = $prices[$selectedPlan];
                            $finalPrice = $basePrice;
                            
                            if ($familyDiscountApplied) {
                                $finalPrice = $basePrice * 0.8; // 20% off
                            }
                            ?>
                            <span class="currency">UGX</span>
                            <span class="amount"><?php echo number_format($finalPrice); ?></span>
                            <span class="period">/<?php echo $selectedPlan === 'monthly' ? 'mo' : ($selectedPlan === 'termly' ? 'term' : 'year'); ?></span>
                        </div>
                        
                        <?php if ($familyDiscountApplied): ?>
                            <div class="discount-badge">
                                <i class="fas fa-tag"></i>
                                Family Discount Applied (20% OFF)
                                <br>
                                <small>Was: UGX <?php echo number_format($basePrice); ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Full access P.1 - P.7</li>
                            <li><i class="fas fa-check"></i> All subjects included</li>
                            <li><i class="fas fa-check"></i> Video lessons</li>
                            <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                            <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                            <li><i class="fas fa-check"></i> Progress tracking</li>
                            <?php if ($selectedPlan !== 'monthly'): ?>
                                <li><i class="fas fa-check"></i> Family discount eligible</li>
                            <?php endif; ?>
                            <?php if ($selectedPlan === 'yearly'): ?>
                                <li><i class="fas fa-check"></i> 2 months FREE</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <h4>We Accept:</h4>
                        <div class="payment-icons">
                            <span class="payment-icon">
                                <i class="fas fa-mobile-alt"></i>
                                MTN Mobile Money
                            </span>
                            <span class="payment-icon">
                                <i class="fas fa-mobile-alt"></i>
                                Airtel Money
                            </span>
                            <span class="payment-icon">
                                <i class="fas fa-credit-card"></i>
                                Visa/Mastercard
                            </span>
                        </div>
                    </div>
                    
                    <!-- Guarantee -->
                    <div class="guarantee">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>100% Secure Payment</strong>
                            <p>Your payment information is encrypted and secure</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
    /* Registration page specific styles */
    .register-section {
        padding: 60px 0;
        background: linear-gradient(135deg, rgba(75, 28, 60, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%);
        min-height: calc(100vh - 80px);
    }
    
    .register-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .register-box {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(75, 28, 60, 0.1);
        overflow: hidden;
    }
    
    .register-header {
        background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .register-header h2 {
        color: white;
        margin: 0;
        font-size: 2rem;
    }
    
    .register-header h2:after {
        background: #FFB800;
    }
    
    .register-header p {
        color: rgba(255, 255, 255, 0.9);
        margin-top: 10px;
    }
    
    .register-form {
        padding: 30px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
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
    
    .form-text {
        display: block;
        margin-top: 5px;
        font-size: 0.85rem;
        color: #666;
    }
    
    .form-text.success {
        color: #090;
    }
    
    .terms {
        margin: 25px 0;
    }
    
    .checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    
    .checkbox input[type="checkbox"] {
        width: auto;
        margin-right: 5px;
    }
    
    .checkbox a {
        color: #4B1C3C;
        text-decoration: none;
        font-weight: 500;
    }
    
    .checkbox a:hover {
        color: #FFB800;
    }
    
    .btn-block {
        width: 100%;
        padding: 14px;
        font-size: 1.1rem;
    }
    
    .register-footer {
        padding: 20px 30px;
        text-align: center;
        background: #F5F5F5;
        border-top: 1px solid #E0E0E0;
    }
    
    .register-footer a {
        color: #4B1C3C;
        font-weight: 600;
        text-decoration: none;
    }
    
    .register-footer a:hover {
        color: #FFB800;
    }
    
    /* Plan Summary */
    .plan-summary {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(75, 28, 60, 0.1);
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    
    .plan-summary h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .plan-card {
        background: linear-gradient(135deg, #F8F8F8 0%, #FFFFFF 100%);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        border: 2px solid #E0E0E0;
        position: relative;
    }
    
    .plan-card.termly {
        border-color: #FFB800;
    }
    
    .plan-card h4 {
        color: #4B1C3C;
        font-size: 1.3rem;
        margin-bottom: 15px;
    }
    
    .plan-price {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .plan-price .currency {
        font-size: 1rem;
        color: #666;
        vertical-align: super;
    }
    
    .plan-price .amount {
        font-size: 2.5rem;
        font-weight: 700;
        color: #4B1C3C;
        line-height: 1;
    }
    
    .plan-price .period {
        color: #666;
        font-size: 0.9rem;
    }
    
    .discount-badge {
        background: #FFB800;
        color: #4B1C3C;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .discount-badge small {
        display: block;
        font-weight: 400;
        opacity: 0.8;
    }
    
    .plan-features {
        list-style: none;
    }
    
    .plan-features li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #666;
    }
    
    .plan-features i {
        color: #4B1C3C;
        width: 20px;
    }
    
    .payment-methods {
        margin-bottom: 25px;
    }
    
    .payment-methods h4 {
        color: #4B1C3C;
        margin-bottom: 15px;
        font-size: 1rem;
    }
    
    .payment-icons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .payment-icon {
        background: #F5F5F5;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
        color: #4B1C3C;
    }
    
    .payment-icon i {
        color: #FFB800;
    }
    
    .guarantee {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #F5F5F5;
        border-radius: 5px;
    }
    
    .guarantee i {
        font-size: 2rem;
        color: #4B1C3C;
    }
    
    .guarantee strong {
        display: block;
        color: #4B1C3C;
    }
    
    .guarantee p {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
    }
    
    /* Responsive */
    @media (max-width: 968px) {
        .register-grid {
            grid-template-columns: 1fr;
        }
        
        .plan-summary {
            position: static;
        }
    }
    
    @media (max-width: 576px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    // Password match validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
        }
    });
    
    // Phone number formatting
    document.getElementById('phone').addEventListener('blur', function(e) {
        let phone = e.target.value.replace(/[^0-9]/g, '');
        if (phone.length === 9) {
            phone = '0' + phone;
        }
        e.target.value = phone;
    });
    </script>
</body>
</html>