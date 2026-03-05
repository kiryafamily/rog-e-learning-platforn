<?php
// payment.php
// Payment processing for MTN Mobile Money, Airtel Money, and Cards
// This page allows users to select a subscription plan, choose a payment method, and complete their payment. It handles both Mobile Money and card payments, applying family discounts where applicable. The page is designed to be user-friendly and secure, guiding users through the payment process with clear instructions and feedback.

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'payment.php?plan=' . ($_GET['plan'] ?? 'monthly');
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$plan = $_GET['plan'] ?? $_POST['plan'] ?? 'monthly';
$error = '';
$success = '';

// Define prices
$prices = [
    'monthly' => 100000,
    'termly' => 500000,
    'yearly' => 1500000
];

$basePrice = $prices[$plan] ?? 100000;
$finalPrice = $basePrice;

// Check for family discount
$familyMembers = 0;
if ($user['family_id']) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE family_id = ? AND status = 'active'");
    $stmt->execute([$user['family_id']]);
    $familyMembers = $stmt->fetch()['count'];
    
    if ($familyMembers >= 2 && $plan !== 'monthly') {
        $finalPrice = $basePrice * (1 - FAMILY_DISCOUNT);
        $discountApplied = true;
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $transactionId = generateTransactionId();
    
    // Validate payment method
    if (!in_array($paymentMethod, ['mtn', 'airtel', 'card'])) {
        $error = 'Please select a payment method';
    } else {
        // Process based on payment method
        switch ($paymentMethod) {
            case 'mtn':
            case 'airtel':
                $phone = $_POST['phone'] ?? '';
                if (!validatePhone($phone)) {
                    $error = 'Please enter a valid phone number';
                } else {
                    // Initiate Mobile Money payment
                    $result = initiateMobileMoneyPayment(
                        $pdo,
                        $user['id'],
                        $finalPrice,
                        $phone,
                        $paymentMethod,
                        $plan,
                        $transactionId
                    );
                    
                    if ($result['success']) {
                        $_SESSION['pending_transaction'] = $transactionId;
                        header('Location: payment-confirm.php?txn=' . $transactionId);
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'card':
                // Process card payment (Stripe)
                $cardNumber = $_POST['card_number'] ?? '';
                $expiry = $_POST['expiry'] ?? '';
                $cvv = $_POST['cvv'] ?? '';
                
                $result = processCardPayment(
                    $pdo,
                    $user['id'],
                    $finalPrice,
                    $cardNumber,
                    $expiry,
                    $cvv,
                    $plan,
                    $transactionId
                );
                
                if ($result['success']) {
                    header('Location: payment-success.php?txn=' . $transactionId);
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

/**
 * Initiate Mobile Money payment
 */
function initiateMobileMoneyPayment($pdo, $userId, $amount, $phone, $provider, $plan, $txnId) {
    $phone = formatPhone($phone);
    
    // Determine provider number
    $providerNumber = ($provider === 'mtn') ? MTN_NUMBER : AIRTEL_NUMBER;
    
    // Here you would integrate with actual Mobile Money API
    // This is a placeholder - you'll need to add your payment gateway
    
    // For demonstration, we'll simulate a successful payment
    try {
        // Save transaction record
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                transaction_id, user_id, amount, phone, provider, 
                plan, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$txnId, $userId, $amount, $phone, $provider, $plan]);
        
        // Send payment request to customer's phone
        $message = "You are about to pay UGX " . number_format($amount) . " to " . SITE_NAME . ". Enter PIN to confirm.";
        sendSMS($phone, $message);
        
        return ['success' => true, 'message' => 'Payment request sent to your phone'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Payment failed. Please try again.'];
    }
}

/**
 * Process card payment (Stripe)
 */
function processCardPayment($pdo, $userId, $amount, $cardNumber, $expiry, $cvv, $plan, $txnId) {
    // Here you would integrate with Stripe API
    // This is a placeholder
    
    try {
        // Save transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                transaction_id, user_id, amount, provider, 
                plan, status, created_at
            ) VALUES (?, ?, ?, 'card', ?, 'completed', NOW())
        ");
        $stmt->execute([$txnId, $userId, $amount, $plan]);
        
        // Activate subscription
        activateSubscription($pdo, $userId, $plan, $txnId);
        
        return ['success' => true, 'message' => 'Payment successful'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Card payment failed'];
    }
}

/**
 * Activate subscription after successful payment
 */
function activateSubscription($pdo, $userId, $plan, $txnId) {
    // Calculate dates
    $startDate = date('Y-m-d H:i:s');
    
    switch ($plan) {
        case 'monthly':
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
            break;
        case 'termly':
            $endDate = date('Y-m-d H:i:s', strtotime('+3 months'));
            break;
        case 'yearly':
            $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));
            break;
        default:
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
    }
    
    // Save subscription
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (
            user_id, plan, start_date, end_date, status, transaction_id, created_at
        ) VALUES (?, ?, ?, ?, 'active', ?, NOW())
    ");
    $stmt->execute([$userId, $plan, $startDate, $endDate, $txnId]);
    
    // Update transaction status
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE transaction_id = ?");
    $stmt->execute([$txnId]);
    
    // Log activity
    logActivity($pdo, $userId, 'subscription_activated', "Plan: $plan");
    
    // Send confirmation
    sendSubscriptionConfirmation($pdo, $userId, $plan);
}

/**
 * Send subscription confirmation
 */
function sendSubscriptionConfirmation($pdo, $userId, $plan) {
    $user = getCurrentUser($pdo);
    
    // SMS confirmation
    $message = "Thank you for subscribing to " . SITE_NAME . "! Your $plan plan is now active. Welcome to the RAYS OF GRACE family!";
    sendSMS($user['phone'], $message);
    
    // Email confirmation
    $subject = "Subscription Confirmed - " . SITE_NAME;
    $body = "
        <h2>Welcome to RAYS OF GRACE Junior School!</h2>
        <p>Dear {$user['fullname']},</p>
        <p>Your <strong>$plan</strong> subscription is now active.</p>
        <p>You can now access all lessons, videos, and quizzes.</p>
        <p><a href='" . SITE_URL . "/dashboard.php'>Click here to start learning</a></p>
        <p>Best regards,<br>RAYS OF GRACE Team</p>
    ";
    sendEmail($user['email'], $subject, $body);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo.png" alt="RAYS OF GRACE Junior School">
                <span>
                    RAYS OF GRACE
                    <small>Junior School</small>
                </span>
            </div>
            <div>
                <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['fullname']); ?></span>
            </div>
        </div>
    </nav>

    <section class="payment-section">
        <div class="container">
            <div class="payment-grid">
                <!-- Payment Summary -->
                <div class="payment-summary">
                    <h2>Payment Summary</h2>
                    
                    <div class="summary-card">
                        <h3><?php echo ucfirst($plan); ?> Plan</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>UGX <?php echo number_format($basePrice); ?></span>
                        </div>
                        
                        <?php if (isset($discountApplied)): ?>
                            <div class="summary-row discount">
                                <span>Family Discount (20%):</span>
                                <span>-UGX <?php echo number_format($basePrice - $finalPrice); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>UGX <?php echo number_format($finalPrice); ?></span>
                        </div>
                        
                        <div class="whats-included">
                            <h4>What's included:</h4>
                            <ul>
                                <li><i class="fas fa-check"></i> Full access to P.1-P.7</li>
                                <li><i class="fas fa-check"></i> All subjects</li>
                                <li><i class="fas fa-check"></i> Video lessons</li>
                                <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                                <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                                <li><i class="fas fa-check"></i> Progress tracking</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <div class="payment-form-container">
                    <h2>Select Payment Method</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="payment-methods-tabs">
                        <button class="payment-tab active" onclick="showPaymentMethod('mobile')">
                            <i class="fas fa-mobile-alt"></i>
                            Mobile Money
                        </button>
                        <button class="payment-tab" onclick="showPaymentMethod('card')">
                            <i class="fas fa-credit-card"></i>
                            Card Payment
                        </button>
                    </div>
                    
                    <!-- Mobile Money Form -->
                    <div id="mobile-payment" class="payment-method active">
                        <form method="POST" action="" class="payment-form">
                            <input type="hidden" name="plan" value="<?php echo $plan; ?>">
                            
                            <div class="form-group">
                                <label>Select Mobile Money Provider</label>
                                <div class="provider-options">
                                    <label class="provider-option">
                                        <input type="radio" name="payment_method" value="mtn" required>
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>MTN Mobile Money</span>
                                    </label>
                                    <label class="provider-option">
                                        <input type="radio" name="payment_method" value="airtel" required>
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>Airtel Money</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i>
                                    Mobile Money Number
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    required 
                                    placeholder="e.g., 0772XXXXXX"
                                    value="<?php echo htmlspecialchars($user['phone']); ?>"
                                >
                                <small>Enter the number registered with Mobile Money</small>
                            </div>
                            
                            <div class="payment-instructions">
                                <h4><i class="fas fa-info-circle"></i> How it works:</h4>
                                <ol>
                                    <li>Enter your Mobile Money number</li>
                                    <li>Click "Pay Now"</li>
                                    <li>Check your phone for payment request</li>
                                    <li>Enter your PIN to confirm</li>
                                    <li>You'll be redirected to your dashboard</li>
                                </ol>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-large">
                                <i class="fas fa-lock"></i>
                                Pay UGX <?php echo number_format($finalPrice); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Card Payment Form -->
                    <div id="card-payment" class="payment-method">
                        <form method="POST" action="" class="payment-form" id="cardForm">
                            <input type="hidden" name="plan" value="<?php echo $plan; ?>">
                            <input type="hidden" name="payment_method" value="card">
                            
                            <div class="form-group">
                                <label for="card_number">
                                    <i class="fas fa-credit-card"></i>
                                    Card Number
                                </label>
                                <input 
                                    type="text" 
                                    id="card_number" 
                                    name="card_number" 
                                    required 
                                    placeholder="1234 5678 9012 3456"
                                    maxlength="19"
                                >
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiry">Expiry Date</label>
                                    <input 
                                        type="text" 
                                        id="expiry" 
                                        name="expiry" 
                                        required 
                                        placeholder="MM/YY"
                                        maxlength="5"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input 
                                        type="text" 
                                        id="cvv" 
                                        name="cvv" 
                                        required 
                                        placeholder="123"
                                        maxlength="3"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="card_name">
                                    <i class="fas fa-user"></i>
                                    Name on Card
                                </label>
                                <input 
                                    type="text" 
                                    id="card_name" 
                                    name="card_name" 
                                    required 
                                    placeholder="As shown on card"
                                    value="<?php echo htmlspecialchars($user['fullname']); ?>"
                                >
                            </div>
                            
                            <div class="secure-badge">
                                <i class="fas fa-shield-alt"></i>
                                <span>Secured by Stripe. Your card details are encrypted.</span>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-large">
                                <i class="fas fa-lock"></i>
                                Pay UGX <?php echo number_format($finalPrice); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Money-back guarantee -->
                    <div class="guarantee-badge">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>7-Day Money-Back Guarantee</strong>
                            <p>Not satisfied? Get a full refund within 7 days.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
    .payment-section {
        padding: 60px 0;
        background: linear-gradient(135deg, rgba(75, 28, 60, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%);
        min-height: calc(100vh - 80px);
    }
    
    .payment-grid {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 30px;
        max-width: 1100px;
        margin: 0 auto;
    }
    
    .payment-summary {
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    
    .payment-summary h2 {
        text-align: left;
        margin-bottom: 20px;
    }
    
    .summary-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .summary-card h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFB800;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        color: #666;
    }
    
    .summary-row.discount {
        color: #4CAF50;
    }
    
    .summary-row.total {
        font-size: 1.3rem;
        font-weight: 700;
        color: #4B1C3C;
        border-top: 2px solid #E0E0E0;
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .whats-included {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #E0E0E0;
    }
    
    .whats-included h4 {
        color: #4B1C3C;
        margin-bottom: 15px;
    }
    
    .whats-included ul {
        list-style: none;
    }
    
    .whats-included li {
        padding: 5px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #666;
    }
    
    .whats-included i {
        color: #FFB800;
    }
    
    .payment-form-container {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .payment-methods-tabs {
        display: flex;
        gap: 10px;
        margin: 20px 0;
    }
    
    .payment-tab {
        flex: 1;
        padding: 15px;
        background: #F5F5F5;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .payment-tab i {
        margin-right: 8px;
    }
    
    .payment-tab.active {
        background: #4B1C3C;
        color: white;
    }
    
    .payment-method {
        display: none;
    }
    
    .payment-method.active {
        display: block;
    }
    
    .provider-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 10px;
    }
    
    .provider-option {
        background: #F5F5F5;
        border: 2px solid #E0E0E0;
        border-radius: 5px;
        padding: 15px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }
    
    .provider-option:hover {
        border-color: #FFB800;
    }
    
    .provider-option input[type="radio"] {
        width: auto;
        margin-right: 5px;
    }
    
    .provider-option i {
        font-size: 1.5rem;
        color: #4B1C3C;
    }
    
    .payment-instructions {
        background: #F8F4E8;
        border-left: 4px solid #FFB800;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }
    
    .payment-instructions h4 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .payment-instructions ol {
        margin-left: 20px;
        color: #666;
    }
    
    .payment-instructions li {
        margin: 5px 0;
    }
    
    .secure-badge {
        background: #E8F4E8;
        padding: 10px;
        border-radius: 5px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #4CAF50;
    }
    
    .guarantee-badge {
        margin-top: 20px;
        padding: 15px;
        background: #F5F5F5;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .guarantee-badge i {
        font-size: 2rem;
        color: #4B1C3C;
    }
    
    .guarantee-badge strong {
        color: #4B1C3C;
    }
    
    .user-greeting {
        color: #4B1C3C;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .payment-grid {
            grid-template-columns: 1fr;
        }
        
        .provider-options {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    function showPaymentMethod(method) {
        // Update tabs
        document.querySelectorAll('.payment-tab').forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        // Show correct form
        document.getElementById('mobile-payment').classList.remove('active');
        document.getElementById('card-payment').classList.remove('active');
        
        if (method === 'mobile') {
            document.getElementById('mobile-payment').classList.add('active');
        } else {
            document.getElementById('card-payment').classList.add('active');
        }
    }
    
    // Card number formatting
    document.getElementById('card_number')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formatted = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formatted += ' ';
            }
            formatted += value[i];
        }
        
        e.target.value = formatted;
    });
    
    // Expiry formatting
    document.getElementById('expiry')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        e.target.value = value;
    });
    
    // CVV only numbers
    document.getElementById('cvv')?.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    </script>
</body>
</html>