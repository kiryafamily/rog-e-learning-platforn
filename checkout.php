<?php
// checkout.php - Checkout Page for Logged-in Users
// This page allows logged-in users to select a subscription plan, choose a payment method, and proceed to the payment process. It also checks if the user already has an active subscription and displays appropriate messages. The page is designed to be user-friendly and secure, guiding users through the checkout process with clear instructions and feedback.
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$plan = $_GET['plan'] ?? 'monthly';

// Define prices
$prices = [
    'monthly' => 100000,
    'termly' => 500000,
    'yearly' => 1500000
];

$amount = $prices[$plan] ?? 100000;

// Check if user already has active subscription
$hasActiveSubscription = hasAccess($pdo, $user['id']);

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($payment_method)) {
        $error = 'Please select a payment method';
    } else {
        // Redirect to payment page
        header("Location: payment.php?plan=$plan&method=$payment_method");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .checkout-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .plan-summary {
            background: #f8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #FFB800;
        }

        .plan-summary h3 {
            color: #4B1C3C;
            margin-bottom: 10px;
        }

        .plan-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .plan-detail:last-child {
            border-bottom: none;
        }

        .plan-label {
            color: #666;
        }

        .plan-value {
            font-weight: 600;
            color: #4B1C3C;
        }

        .price-total {
            font-size: 1.5rem;
            color: #FFB800;
            font-weight: 700;
        }

        .payment-methods {
            margin: 30px 0;
        }

        .payment-methods h3 {
            color: #4B1C3C;
            margin-bottom: 15px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #FFB800;
        }

        .payment-option input[type="radio"] {
            margin-right: 15px;
            width: 18px;
            height: 18px;
        }

        .payment-option i {
            font-size: 1.5rem;
            margin-right: 10px;
        }

        .payment-option .mtn { color: #ffcc00; }
        .payment-option .airtel { color: #ed1c24; }
        .payment-option .card { color: #1a1f71; }

        .btn-checkout {
            background: #4B1C3C;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s ease;
        }

        .btn-checkout:hover {
            background: #2F1224;
        }

        .btn-checkout i {
            color: #FFB800;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
            color: #C62828;
        }

        .alert-warning {
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            color: #F57C00;
        }

        .secure-badge {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .secure-badge i {
            color: #4CAF50;
            margin-right: 5px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4B1C3C;
            text-decoration: none;
        }

        .back-link i {
            color: #FFB800;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <a href="pricing.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Pricing
        </a>

        <div class="checkout-card">
            <h1 style="color: #4B1C3C; margin-bottom: 30px;">
                <i class="fas fa-shopping-cart" style="color: #FFB800;"></i> 
                Complete Your Purchase
            </h1>

            <?php if ($hasActiveSubscription): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    You already have an active subscription. 
                    <a href="dashboard.php" style="color: #F57C00; font-weight: 600;">Go to Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Plan Summary -->
            <div class="plan-summary">
                <h3><i class="fas fa-file-invoice"></i> Order Summary</h3>
                <div class="plan-detail">
                    <span class="plan-label">Plan:</span>
                    <span class="plan-value"><?php echo ucfirst($plan); ?> Subscription</span>
                </div>
                <div class="plan-detail">
                    <span class="plan-label">Price:</span>
                    <span class="plan-value price-total">UGX <?php echo number_format($amount); ?></span>
                </div>
                <div class="plan-detail">
                    <span class="plan-label">Billing:</span>
                    <span class="plan-value">One-time payment</span>
                </div>
            </div>

            <!-- User Info -->
            <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <p><strong>Account:</strong> <?php echo htmlspecialchars($user['fullname']); ?> (<?php echo $user['email']; ?>)</p>
            </div>

            <form method="POST">
                <!-- Payment Methods -->
                <div class="payment-methods">
                    <h3><i class="fas fa-credit-card"></i> Select Payment Method</h3>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="mtn" required>
                        <i class="fas fa-mobile-alt mtn"></i>
                        <div>
                            <strong>MTN Mobile Money</strong>
                            <p style="color: #666; font-size: 0.9rem;">Pay using MTN Mobile Money</p>
                        </div>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="airtel" required>
                        <i class="fas fa-mobile-alt airtel"></i>
                        <div>
                            <strong>Airtel Money</strong>
                            <p style="color: #666; font-size: 0.9rem;">Pay using Airtel Money</p>
                        </div>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="card" required>
                        <i class="fas fa-credit-card card"></i>
                        <div>
                            <strong>Credit / Debit Card</strong>
                            <p style="color: #666; font-size: 0.9rem;">Visa, Mastercard, etc.</p>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn-checkout">
                    <i class="fas fa-lock"></i> Proceed to Payment
                </button>
            </form>

            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i> 
                Secure payment processing. Your information is encrypted.
            </div>
        </div>
    </div>
</body>
</html>