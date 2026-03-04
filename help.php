<?php
// help.php - Professional Help Center
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);

// Handle support ticket submission
$ticketSuccess = '';
$ticketError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = sanitize($_POST['subject']);
    $category = sanitize($_POST['category']);
    $message = sanitize($_POST['message']);
    $priority = sanitize($_POST['priority']);
    
    if (empty($subject) || empty($message)) {
        $ticketError = 'Please fill in all required fields';
    } else {
        // Save ticket to database (you'll need to create this table)
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (user_id, subject, category, message, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'open', NOW())
        ");
        
        if ($stmt->execute([$user['id'], $subject, $category, $message, $priority])) {
            $ticketSuccess = 'Your support ticket has been submitted successfully. We\'ll respond within 24 hours.';
            
            // Send notification email to admin
            $adminEmail = "admin@raysofgrace.ac.ug";
            $emailSubject = "New Support Ticket: $subject";
            $emailBody = "
                <h2>New Support Ticket</h2>
                <p><strong>User:</strong> {$user['fullname']}</p>
                <p><strong>Email:</strong> {$user['email']}</p>
                <p><strong>Category:</strong> $category</p>
                <p><strong>Priority:</strong> $priority</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
            ";
            // sendEmail($adminEmail, $emailSubject, $emailBody);
        } else {
            $ticketError = 'Failed to submit ticket. Please try again.';
        }
    }
}

// Get user's recent tickets
$stmt = $pdo->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recentTickets = $stmt->fetchAll();

// Get FAQ categories
$faqs = [
    'account' => [
        ['q' => 'How do I reset my password?', 'a' => 'Go to the login page and click "Forgot Password". Follow the instructions sent to your email.'],
        ['q' => 'How do I update my profile information?', 'a' => 'Click on your name in the top right corner, select "Profile", and update your information there.'],
        ['q' => 'Can I change my email address?', 'a' => 'Yes, you can update your email in the Profile section under "Personal Info".'],
    ],
    'subscription' => [
        ['q' => 'How do I subscribe to a plan?', 'a' => 'Click on "Subscribe" in the navigation bar or visit the Pricing page to choose a plan.'],
        ['q' => 'What payment methods do you accept?', 'a' => 'We accept MTN Mobile Money, Airtel Money, and Visa/Mastercard.'],
        ['q' => 'How does the family discount work?', 'a' => 'When you register multiple children, use the same family code to get 20% off on termly and yearly plans.'],
    ],
    'technical' => [
        ['q' => 'Videos are not playing. What should I do?', 'a' => 'Try refreshing the page, clearing your browser cache, or using a different browser.'],
        ['q' => 'How do I access lessons offline?', 'a' => 'Click the "Save for Offline" button on any lesson to download it for offline viewing.'],
        ['q' => 'My progress is not saving. What\'s wrong?', 'a' => 'Make sure you\'re logged in and have a stable internet connection. Progress saves automatically.'],
    ],
    'billing' => [
        ['q' => 'How do I get a receipt?', 'a' => 'Receipts are sent to your email after each payment. You can also view them in your transaction history.'],
        ['q' => 'Can I get a refund?', 'a' => 'We offer a 7-day money-back guarantee. Contact support within 7 days of purchase for a full refund.'],
        ['q' => 'How do I cancel my subscription?', 'a' => 'Go to Settings → Subscription and click "Cancel Subscription". Your access will continue until the end of the billing period.'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - RAYS OF GRACE</title>
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

        /* Navigation */
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
            height: 40px;
            width: auto;
        }

        .logo-area span {
            font-size: 1.2rem;
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
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }

        /* Main Container */
        .help-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .help-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .help-header h1 {
            color: #4B1C3C;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .help-header h1 i {
            color: #FFB800;
        }

        .help-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .search-box {
            max-width: 500px;
            margin: 30px auto;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 15px 20px;
            padding-left: 50px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #FFB800;
            box-shadow: 0 0 0 3px rgba(255,184,0,0.1);
        }

        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #FFB800;
            font-size: 1.2rem;
        }

        /* Quick Help Cards */
        .quick-help {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }

        .help-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(75,28,60,0.1);
            border-color: #FFB800;
        }

        .help-card i {
            font-size: 2rem;
            color: #FFB800;
            margin-bottom: 15px;
        }

        .help-card h3 {
            color: #4B1C3C;
            margin-bottom: 5px;
        }

        .help-card p {
            color: #666;
            font-size: 0.9rem;
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .section-title i {
            font-size: 1.5rem;
            color: #FFB800;
        }

        .section-title h2 {
            color: #4B1C3C;
            font-size: 1.5rem;
        }

        .faq-categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .faq-category {
            background: #f8f4f8;
            border-radius: 10px;
            padding: 20px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFB800;
        }

        .category-header i {
            color: #FFB800;
        }

        .category-header h3 {
            color: #4B1C3C;
        }

        .faq-list {
            list-style: none;
        }

        .faq-item {
            margin-bottom: 15px;
        }

        .faq-question {
            color: #4B1C3C;
            font-weight: 500;
            cursor: pointer;
            padding: 10px;
            background: white;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-question:hover {
            background: #4B1C3C;
            color: white;
        }

        .faq-question:hover i {
            color: #FFB800;
        }

        .faq-question i {
            color: #FFB800;
            transition: color 0.3s ease;
        }

        .faq-answer {
            display: none;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
            margin-top: 5px;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .faq-answer.active {
            display: block;
        }

        /* Support Ticket Form */
        .ticket-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .ticket-form {
            max-width: 600px;
            margin: 20px auto 0;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFB800;
            box-shadow: 0 0 0 3px rgba(255,184,0,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
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

        /* Recent Tickets */
        .tickets-list {
            margin-top: 20px;
        }

        .ticket-item {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .ticket-status {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open {
            background: #FFEBEE;
            color: #f44336;
        }

        .status-in-progress {
            background: #FFF3E0;
            color: #FF9800;
        }

        .status-resolved {
            background: #E8F5E9;
            color: #4CAF50;
        }

        /* Contact Options */
        .contact-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .contact-card {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255,184,0,0.2);
        }

        .contact-card i {
            font-size: 2rem;
            color: #FFB800;
            margin-bottom: 10px;
        }

        .contact-card h4 {
            color: #4B1C3C;
            margin-bottom: 5px;
        }

        .contact-card p {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .quick-help,
            .faq-categories,
            .contact-options {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="logo-area">
            <img src="images/logo.png" alt="RAYS OF GRACE">
            <span>Help Center</span>
        </div>
        <div class="nav-right">
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </nav>

    <div class="help-container">
        <!-- Header -->
        <div class="help-header">
            <h1><i class="fas fa-headset"></i> How Can We Help?</h1>
            <p>Find answers to common questions or get in touch with our support team</p>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="helpSearch" placeholder="Search for answers...">
        </div>

        <!-- Quick Help Cards -->
        <div class="quick-help">
            <div class="help-card" onclick="document.getElementById('faq-account').scrollIntoView({behavior: 'smooth'})">
                <i class="fas fa-user-circle"></i>
                <h3>Account</h3>
                <p>Login, profile, password</p>
            </div>
            <div class="help-card" onclick="document.getElementById('faq-subscription').scrollIntoView({behavior: 'smooth'})">
                <i class="fas fa-credit-card"></i>
                <h3>Subscription</h3>
                <p>Plans, payments, discounts</p>
            </div>
            <div class="help-card" onclick="document.getElementById('faq-technical').scrollIntoView({behavior: 'smooth'})">
                <i class="fas fa-laptop"></i>
                <h3>Technical</h3>
                <p>Videos, offline access</p>
            </div>
            <div class="help-card" onclick="document.getElementById('faq-billing').scrollIntoView({behavior: 'smooth'})">
                <i class="fas fa-file-invoice"></i>
                <h3>Billing</h3>
                <p>Receipts, refunds</p>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <div class="section-title">
                <i class="fas fa-question-circle"></i>
                <h2>Frequently Asked Questions</h2>
            </div>

            <div class="faq-categories">
                <!-- Account FAQs -->
                <div class="faq-category" id="faq-account">
                    <div class="category-header">
                        <i class="fas fa-user-circle"></i>
                        <h3>Account</h3>
                    </div>
                    <div class="faq-list">
                        <?php foreach ($faqs['account'] as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleAnswer(this)">
                                <i class="fas fa-chevron-right"></i>
                                <?php echo $faq['q']; ?>
                            </div>
                            <div class="faq-answer">
                                <?php echo $faq['a']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Subscription FAQs -->
                <div class="faq-category" id="faq-subscription">
                    <div class="category-header">
                        <i class="fas fa-credit-card"></i>
                        <h3>Subscription</h3>
                    </div>
                    <div class="faq-list">
                        <?php foreach ($faqs['subscription'] as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleAnswer(this)">
                                <i class="fas fa-chevron-right"></i>
                                <?php echo $faq['q']; ?>
                            </div>
                            <div class="faq-answer">
                                <?php echo $faq['a']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Technical FAQs -->
                <div class="faq-category" id="faq-technical">
                    <div class="category-header">
                        <i class="fas fa-laptop"></i>
                        <h3>Technical</h3>
                    </div>
                    <div class="faq-list">
                        <?php foreach ($faqs['technical'] as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleAnswer(this)">
                                <i class="fas fa-chevron-right"></i>
                                <?php echo $faq['q']; ?>
                            </div>
                            <div class="faq-answer">
                                <?php echo $faq['a']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Billing FAQs -->
                <div class="faq-category" id="faq-billing">
                    <div class="category-header">
                        <i class="fas fa-file-invoice"></i>
                        <h3>Billing</h3>
                    </div>
                    <div class="faq-list">
                        <?php foreach ($faqs['billing'] as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleAnswer(this)">
                                <i class="fas fa-chevron-right"></i>
                                <?php echo $faq['q']; ?>
                            </div>
                            <div class="faq-answer">
                                <?php echo $faq['a']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Ticket Form -->
        <div class="ticket-section">
            <div class="section-title">
                <i class="fas fa-ticket-alt"></i>
                <h2>Submit a Support Ticket</h2>
            </div>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">Can't find what you're looking for? Create a support ticket and we'll help you out.</p>

            <?php if ($ticketSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $ticketSuccess; ?>
                </div>
            <?php endif; ?>

            <?php if ($ticketError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $ticketError; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="ticket-form">
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required placeholder="Brief summary of your issue">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select category</option>
                            <option value="account">Account</option>
                            <option value="subscription">Subscription</option>
                            <option value="technical">Technical</option>
                            <option value="billing">Billing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="5" required placeholder="Describe your issue in detail..."></textarea>
                </div>

                <button type="submit" name="submit_ticket" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Ticket
                </button>
            </form>
        </div>

        <!-- Recent Tickets -->
        <?php if (!empty($recentTickets)): ?>
        <div class="ticket-section">
            <div class="section-title">
                <i class="fas fa-history"></i>
                <h2>Your Recent Tickets</h2>
            </div>

            <div class="tickets-list">
                <?php foreach ($recentTickets as $ticket): ?>
                <div class="ticket-item">
                    <i class="fas fa-ticket-alt" style="color: #FFB800;"></i>
                    <div style="flex: 1;">
                        <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                        <p style="color: #666; font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></p>
                    </div>
                    <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                        <?php echo ucfirst($ticket['status']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Options -->
        <div class="ticket-section">
            <div class="section-title">
                <i class="fas fa-phone-alt"></i>
                <h2>Other Ways to Reach Us</h2>
            </div>

            <div class="contact-options">
                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email</h4>
                    <p>support@raysofgrace.ac.ug</p>
                    <small style="color: #999;">24-48 hour response</small>
                </div>
                <div class="contact-card">
                    <i class="fas fa-phone"></i>
                    <h4>Phone</h4>
                    <p>+256 XXX XXXXXX</p>
                    <small style="color: #999;">Mon-Fri, 8am-5pm</small>
                </div>
                <div class="contact-card">
                    <i class="fas fa-comment"></i>
                    <h4>Live Chat</h4>
                    <p>Coming Soon</p>
                    <small style="color: #999;">Instant support</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle FAQ answers
        function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            // Close other answers
            document.querySelectorAll('.faq-answer.active').forEach(el => {
                if (el !== answer) {
                    el.classList.remove('active');
                    el.previousElementSibling.querySelector('i').style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current answer
            answer.classList.toggle('active');
            if (answer.classList.contains('active')) {
                icon.style.transform = 'rotate(90deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Search functionality
        document.getElementById('helpSearch').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question').textContent.toLowerCase();
                if (question.includes(searchTerm) || searchTerm === '') {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>