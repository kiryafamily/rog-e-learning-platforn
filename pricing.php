<?php
// pricing.php - Complete Pricing Page with Smart Buttons
// This page displays the pricing options for the subscription plans, highlighting the most popular termly plan. It checks if the user is logged in to determine whether to direct them to the checkout page or registration page when they click on a plan. The design is modern and visually appealing, with clear calls to action and detailed feature lists for each plan. Additionally, it includes a family discount section and an FAQ to address common questions about the subscription plans.
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session to check login status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - RAYS OF GRACE Junior School</title>
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
        .navbar {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(75,28,60,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .logo span {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4B1C3C;
        }

        .logo span small {
            display: block;
            font-size: 0.7rem;
            color: #FFB800;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 25px;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: #4B1C3C;
        }

        .btn-outline {
            border: 2px solid #4B1C3C;
            padding: 8px 20px;
            border-radius: 5px;
            color: #4B1C3C;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        /* Hero Section */
        .pricing-hero {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .pricing-hero h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .pricing-hero h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .pricing-hero p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Pricing Grid */
        .pricing-section {
            padding: 60px 0;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            border: 1px solid rgba(75,28,60,0.1);
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(75,28,60,0.15);
        }

        .pricing-card.popular {
            border: 2px solid #FFB800;
            transform: scale(1.05);
            z-index: 2;
        }

        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #FFB800;
            color: #4B1C3C;
            padding: 5px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .card-header {
            background: #4B1C3C;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .card-header h3 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #FFB800;
        }

        .price span {
            font-size: 1rem;
            font-weight: 400;
            color: rgba(255,255,255,0.8);
        }

        .card-body {
            padding: 30px 25px;
        }

        .features-list {
            list-style: none;
            margin-bottom: 25px;
        }

        .features-list li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .features-list i {
            color: #FFB800;
            width: 20px;
        }

        .features-list .disabled {
            color: #ccc;
        }

        .features-list .disabled i {
            color: #ccc;
        }

        .btn-plan {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s ease;
            text-decoration: none;
        }

        .btn-plan:hover {
            background: #2F1224;
        }

        .btn-plan i {
            color: #FFB800;
        }

        .btn-plan.popular {
            background: #FFB800;
            color: #4B1C3C;
        }

        .btn-plan.popular i {
            color: #4B1C3C;
        }

        .btn-plan.popular:hover {
            background: #D99B00;
        }

        /* Family Discount Section */
        .discount-section {
            background: white;
            border-radius: 10px;
            padding: 40px;
            margin-top: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,184,0,0.2);
        }

        .discount-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            align-items: center;
        }

        .discount-icon {
            text-align: center;
        }

        .discount-icon i {
            font-size: 4rem;
            color: #FFB800;
            margin-bottom: 15px;
        }

        .discount-icon h3 {
            color: #4B1C3C;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .discount-icon p {
            color: #666;
        }

        .discount-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .feature-item {
            text-align: center;
            padding: 20px;
            background: #f8f4f8;
            border-radius: 8px;
        }

        .feature-item i {
            font-size: 2rem;
            color: #FFB800;
            margin-bottom: 10px;
        }

        .feature-item h4 {
            color: #4B1C3C;
            margin-bottom: 5px;
        }

        .feature-item p {
            color: #666;
            font-size: 0.9rem;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 60px;
        }

        .faq-section h2 {
            color: #4B1C3C;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2rem;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .faq-item {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .faq-item h4 {
            color: #4B1C3C;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .faq-item h4 i {
            color: #FFB800;
        }

        .faq-item p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Guarantee Badge */
        .guarantee-badge {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background: #E8F5E9;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }

        .guarantee-badge i {
            font-size: 2rem;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .guarantee-badge h4 {
            color: #2E7D32;
            margin-bottom: 5px;
        }

        .guarantee-badge p {
            color: #666;
        }

        /* Footer */
        .footer {
            background: #1a0d14;
            color: white;
            padding: 60px 0 20px;
            margin-top: 60px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .footer-logo img {
            height: 40px;
            width: auto;
        }

        .footer-logo span {
            color: white;
            font-weight: 600;
        }

        .footer-about p {
            color: #999;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: #999;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: #FFB800;
        }

        .footer h4 {
            color: #FFB800;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .footer ul {
            list-style: none;
        }

        .footer li {
            margin-bottom: 10px;
        }

        .footer a {
            color: #999;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #FFB800;
        }

        .footer-contact p {
            color: #999;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .footer-contact i {
            color: #FFB800;
            width: 20px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #666;
            font-size: 0.85rem;
        }

        @media (max-width: 992px) {
            .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .discount-grid {
                grid-template-columns: 1fr;
            }
            
            .discount-features {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .pricing-card.popular {
                transform: scale(1);
            }
            
            .pricing-card.popular:hover {
                transform: translateY(-5px);
            }
            
            .discount-features {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo.png" alt="RAYS OF GRACE Junior School">
                <span>
                    RAYS OF GRACE
                    <small>Junior School</small>
                </span>
            </div>
            
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#classes">Classes</a></li>
                <li><a href="index.php#subjects">Subjects</a></li>
                <li><a href="pricing.php" class="active">Pricing</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.php" class="btn-outline">Login</a></li>
                <li><a href="register.php" class="btn-primary">Subscribe</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pricing-hero">
        <div class="container">
            <h1><i class="fas fa-tags"></i> Simple, Transparent Pricing</h1>
            <p>Choose the perfect plan for your child's education. No hidden fees, cancel anytime.</p>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
        <div class="container">
            <div class="pricing-grid">
                <!-- Monthly Plan -->               
                <div class="pricing-card">
                    <div class="card-header">
                        <h3>Monthly</h3>
                        <div class="price">UGX 100,000 <span>/month</span></div>
                    </div>
                    <div class="card-body">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Full access all classes</li>
                            <li><i class="fas fa-check"></i> All subjects included</li>
                            <li><i class="fas fa-check"></i> Video lessons</li>
                            <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                            <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                            <li><i class="fas fa-check"></i> Cancel anytime</li>
                            <li class="disabled"><i class="fas fa-times"></i> Family discount</li>
                        </ul>
                        <?php if ($isLoggedIn): ?>
                            <a href="checkout.php?plan=monthly" class="btn-plan">
                                <i class="fas fa-rocket"></i> Choose Monthly
                            </a>
                        <?php else: ?>
                            <a href="register.php?plan=monthly" class="btn-plan">
                                <i class="fas fa-rocket"></i> Choose Monthly
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Termly Plan (Popular) -->
                <div class="pricing-card popular">
                    <div class="popular-badge">Most Popular</div>
                    <div class="card-header">
                        <h3>Termly</h3>
                        <div class="price">UGX 500,000 <span>/term</span></div>
                    </div>
                    <div class="card-body">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Full access all classes</li>
                            <li><i class="fas fa-check"></i> All subjects included</li>
                            <li><i class="fas fa-check"></i> Video lessons</li>
                            <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                            <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                            <li><i class="fas fa-check"></i> Progress tracking</li>
                            <li><i class="fas fa-check"></i> <strong>20% family discount</strong></li>
                        </ul>
                        <?php if ($isLoggedIn): ?>
                            <a href="checkout.php?plan=termly" class="btn-plan popular">
                                <i class="fas fa-crown"></i> Choose Termly
                            </a>
                        <?php else: ?>
                            <a href="register.php?plan=termly" class="btn-plan popular">
                                <i class="fas fa-crown"></i> Choose Termly
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Yearly Plan -->
                <div class="pricing-card">
                    <div class="card-header">
                        <h3>Yearly</h3>
                        <div class="price">UGX 1,500,000 <span>/year</span></div>
                    </div>
                    <div class="card-body">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Full access all classes</li>
                            <li><i class="fas fa-check"></i> All subjects included</li>
                            <li><i class="fas fa-check"></i> Video lessons</li>
                            <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                            <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                            <li><i class="fas fa-check"></i> Progress tracking</li>
                            <li><i class="fas fa-check"></i> <strong>20% family discount</strong></li>
                            <li><i class="fas fa-check"></i> <strong>2 months free</strong></li>
                        </ul>
                        <?php if ($isLoggedIn): ?>
                            <a href="checkout.php?plan=yearly" class="btn-plan">
                                <i class="fas fa-star"></i> Best Value
                            </a>
                        <?php else: ?>
                            <a href="register.php?plan=yearly" class="btn-plan">
                                <i class="fas fa-star"></i> Best Value
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Family Discount Section -->
            <div class="discount-section">
                <div class="discount-grid">
                    <div class="discount-icon">
                        <i class="fas fa-users"></i>
                        <h3>Family Discount</h3>
                        <p>Save 20% on termly & yearly plans</p>
                    </div>
                    <div class="discount-features">
                        <div class="feature-item">
                            <i class="fas fa-child"></i>
                            <h4>Multiple Children</h4>
                            <p>Add up to 4 children</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-percent"></i>
                            <h4>20% OFF</h4>
                            <p>On termly and yearly plans</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-code"></i>
                            <h4>Family Code</h4>
                            <p>Use same family code</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> Can I switch plans later?</h4>
                        <p>Yes, you can upgrade or downgrade your plan at any time. Changes will be applied to your next billing cycle.</p>
                    </div>
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> How does family discount work?</h4>
                        <p>When registering multiple children, use the same family code to automatically get 20% off on termly and yearly plans.</p>
                    </div>
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> What payment methods do you accept?</h4>
                        <p>We accept MTN Mobile Money, Airtel Money, and all major credit/debit cards via Stripe.</p>
                    </div>
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> Is there a free trial?</h4>
                        <p>Yes! You can access sample lessons for free. Visit our <a href="trial.php" style="color: #4B1C3C; font-weight: 500;">Free Trial page</a> to get started.</p>
                    </div>
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> Can I cancel anytime?</h4>
                        <p>Yes, you can cancel your subscription at any time from your account settings. No questions asked.</p>
                    </div>
                    <div class="faq-item">
                        <h4><i class="fas fa-question-circle"></i> Do you offer refunds?</h4>
                        <p>We offer a 7-day money-back guarantee. If you're not satisfied, contact us within 7 days for a full refund.</p>
                    </div>
                </div>
            </div>

            <!-- Guarantee Badge -->
            <div class="guarantee-badge">
                <i class="fas fa-shield-alt"></i>
                <h4>7-Day Money-Back Guarantee</h4>
                <p>Not satisfied with your purchase? Get a full refund within 7 days, no questions asked.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.png" alt="RAYS OF GRACE">
                        <span>RAYS OF GRACE</span>
                    </div>
                    <p>Knowledge changing lives forever. Providing quality digital education for Primary students in Uganda and beyond.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div>
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#classes">Classes</a></li>
                        <li><a href="index.php#subjects">Subjects</a></li>
                        <li><a href="pricing.php">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="#">Terms of Use</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +256 XXX XXXXXX</p>
                    <p><i class="fas fa-envelope"></i> info@raysofgrace.ac.ug</p>
                    <p><i class="fas fa-map-marker-alt"></i> Kampala, Uganda</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 RAYS OF GRACE Junior School. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    </script>
</body>
</html>