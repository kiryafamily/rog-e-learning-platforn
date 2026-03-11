<?php
// payment-confirm.php
// Payment confirmation and status check
// This page displays the status of a payment transaction after the user has initiated a Mobile Money payment. It checks the transaction status in the database and shows appropriate messages for successful, pending, or failed payments. For pending payments, it provides instructions for the user to complete the payment and automatically refreshes to check for updates. The design is clean and informative, guiding users through the next steps based on their payment status.

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$txnId = $_GET['txn'] ?? '';
$user = getCurrentUser($pdo);

// Get transaction details
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE transaction_id = ? AND user_id = ?
");
$stmt->execute([$txnId, $user['id']]);
$transaction = $stmt->fetch();

if (!$transaction) {
    header('Location: dashboard.php');
    exit;
}

// Check payment status (in production, you'd poll your payment gateway)
$status = $transaction['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation | ROGELE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-box">
            <?php if ($status === 'completed'): ?>
                <div class="success-animation">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Payment Successful!</h1>
                <p>Thank you for subscribing to RAYS OF GRACE Junior School.</p>
                
                <div class="transaction-details">
                    <p><strong>Transaction ID:</strong> <?php echo $txnId; ?></p>
                    <p><strong>Amount:</strong> UGX <?php echo number_format($transaction['amount']); ?></p>
                    <p><strong>Plan:</strong> <?php echo ucfirst($transaction['plan']); ?></p>
                </div>
                
                <p>Your account is now active. You can start learning immediately!</p>
                
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Go to Dashboard
                </a>
                
            <?php elseif ($status === 'pending'): ?>
                <div class="pending-animation">
                    <i class="fas fa-clock"></i>
                </div>
                <h1>Payment Pending</h1>
                <p>Please check your phone and enter your Mobile Money PIN to complete the payment.</p>
                
                <div class="transaction-details">
                    <p><strong>Transaction ID:</strong> <?php echo $txnId; ?></p>
                    <p><strong>Amount:</strong> UGX <?php echo number_format($transaction['amount']); ?></p>
                </div>
                
                <div class="payment-instructions">
                    <h4><i class="fas fa-info-circle"></i> Next Steps:</h4>
                    <ol>
                        <li>Check your phone for a payment request</li>
                        <li>Enter your Mobile Money PIN</li>
                        <li>Wait for confirmation SMS</li>
                        <li>This page will update automatically</li>
                    </ol>
                </div>
                
                <div class="action-buttons">
                    <button onclick="checkStatus()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                        Check Status
                    </button>
                    <a href="dashboard.php" class="btn btn-outline">
                        Go to Dashboard
                    </a>
                </div>
                
            <?php else: ?>
                <div class="error-animation">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h1>Payment Failed</h1>
                <p>We couldn't process your payment. Please try again.</p>
                
                <a href="payment.php?plan=<?php echo $transaction['plan']; ?>" class="btn btn-primary">
                    Try Again
                </a>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .confirmation-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
        padding: 20px;
    }
    
    .confirmation-box {
        background: white;
        border-radius: 10px;
        padding: 40px;
        max-width: 500px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .success-animation i {
        font-size: 80px;
        color: #4CAF50;
        margin-bottom: 20px;
    }
    
    .pending-animation i {
        font-size: 80px;
        color: #FFB800;
        margin-bottom: 20px;
    }
    
    .error-animation i {
        font-size: 80px;
        color: #f44336;
        margin-bottom: 20px;
    }
    
    h1 {
        color: #4B1C3C;
        margin-bottom: 15px;
    }
    
    .transaction-details {
        background: #F5F5F5;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
        text-align: left;
    }
    
    .transaction-details p {
        margin: 5px 0;
        color: #666;
    }
    
    .payment-instructions {
        background: #F8F4E8;
        padding: 20px;
        border-radius: 5px;
        margin: 20px 0;
        text-align: left;
    }
    
    .payment-instructions h4 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .payment-instructions ol {
        margin-left: 20px;
        color: #666;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .action-buttons .btn {
        flex: 1;
    }
    </style>

    <script>
    function checkStatus() {
        location.reload();
    }
    
    // Auto-refresh every 5 seconds for pending payments
    <?php if ($status === 'pending'): ?>
    setTimeout(function() {
        location.reload();
    }, 5000);
    <?php endif; ?>
    </script>
</body>
</html>