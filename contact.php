<?php
// contact.php - PROFESSIONAL REDESIGN
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | ROGELE</title>
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
            background: #faf9fa;
            color: #333;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 10px 0;
            box-shadow: 0 2px 15px rgba(75,28,60,0.08);
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

        .nav-menu a:hover {
            color: #4B1C3C;
        }

        .nav-menu a.active {
            color: #4B1C3C;
            font-weight: 600;
            position: relative;
        }

        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #FFB800;
        }

        .btn-outline {
            border: 2px solid #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            color: #4B1C3C;
            text-decoration: none;
            font-weight: 600;
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
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75,28,60,0.3);
        }

        /* Mobile Menu */
        /* .mobile-menu {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: #4B1C3C;
        } */

        /* Hero Section */
        .contact-hero {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            padding: 60px 0;
            text-align: center;
            border-bottom: 1px solid rgba(255,184,0,0.2);
        }

        .contact-hero h1 {
            color: #4B1C3C;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .contact-hero h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .contact-hero p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Alerts */
        .alert-container {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 0 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
        }

        .alert-success i {
            color: #4CAF50;
            font-size: 1.5rem;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
        }

        .alert-error i {
            color: #f44336;
            font-size: 1.5rem;
        }

        /* Main Content */
        .contact-main {
            padding: 60px 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Info Cards */
        .info-cards {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(75,28,60,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,184,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(75,28,60,0.1);
            border-color: #FFB800;
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,184,0,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-icon i {
            font-size: 2rem;
            color: #FFB800;
        }

        .info-content h3 {
            color: #4B1C3C;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .info-content p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Form Container */
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(75,28,60,0.08);
            border: 1px solid rgba(255,184,0,0.1);
        }

        .form-container h2 {
            color: #4B1C3C;
            font-size: 1.8rem;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 10px;
        }

        .form-container h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #FFB800;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4B1C3C;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #faf9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFB800;
            box-shadow: 0 0 0 4px rgba(255,184,0,0.1);
            background: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            background: #4B1C3C;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .btn-submit:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(75,28,60,0.3);
        }

        .btn-submit i {
            color: #FFB800;
        }

        /* Map Section */
        .map-section {
            margin-top: 60px;
            padding: 0 20px;
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,184,0,0.2);
        }

        .map-container iframe {
            display: block;
            width: 100%;
            height: 400px;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid #333;
            color: #666;
            font-size: 0.85rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            /* .mobile-menu {
                display: block;
            } */
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                border-radius: 0 0 10px 10px;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .contact-hero h1 {
                font-size: 2rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .info-card {
                padding: 20px;
            }
            
            .form-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php" style="display: block; line-height: 0;">
                    <a href="index.php" style="display: block; line-height: 0;">
                        <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
                    </a>
                </a>
            </div>
            
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#classes">Classes</a></li>
                <li><a href="index.php#subjects">Subjects</a></li>
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
                <li><a href="login.php" class="btn-outline">Login</a></li>
                <li><a href="register.php" class="btn-primary">Subscribe</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1><i class="fas fa-headset"></i> Get in Touch</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond within 24 hours.</p>
        </div>
    </section>

    <!-- Alerts -->
    <div class="alert-container">
        <?php if (isset($_SESSION['contact_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong style="color: #2E7D32;">Message Sent!</strong>
                    <p style="color: #2E7D32; margin: 0;"><?php echo $_SESSION['contact_message']; ?></p>
                </div>
            </div>
            <?php unset($_SESSION['contact_success'], $_SESSION['contact_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['contact_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong style="color: #C62828;">Error!</strong>
                    <p style="color: #C62828; margin: 0;"><?php echo $_SESSION['contact_error']; ?></p>
                </div>
            </div>
            <?php unset($_SESSION['contact_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['contact_errors'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong style="color: #C62828;">Please fix the following errors:</strong>
                    <ul style="margin-top: 5px; color: #C62828;">
                        <?php foreach ($_SESSION['contact_errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php unset($_SESSION['contact_errors']); ?>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <section class="contact-main">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Visit Us</h3>
                        <p>Kampala, Uganda</p>
                        <p style="font-size: 0.85rem; color: #999;">Find us in the heart of Kampala</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Call Us</h3>
                        <p>+256 778 086 883</p>
                        <p style="font-size: 0.85rem; color: #999;">Mon-Fri, 8am-5pm</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Us</h3>
                        <p>info@raysofgrace.ac.ug</p>
                        <p style="font-size: 0.85rem; color: #999;">24-48 hour response</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Mon-Fri: 8:00 AM - 5:00 PM</p>
                        <p>Saturday: 9:00 AM - 1:00 PM</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="form-container">
                <h2>Send a Message</h2>
                
                <form action="send-message.php" method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Your Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="Enter your full name"
                               value="<?php echo isset($_SESSION['contact_form_data']['name']) ? htmlspecialchars($_SESSION['contact_form_data']['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" name="email" class="form-control" required 
                               placeholder="Enter your email"
                               value="<?php echo isset($_SESSION['contact_form_data']['email']) ? htmlspecialchars($_SESSION['contact_form_data']['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" class="form-control" 
                               placeholder="Enter your phone number (optional)"
                               value="<?php echo isset($_SESSION['contact_form_data']['phone']) ? htmlspecialchars($_SESSION['contact_form_data']['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Subject *</label>
                        <input type="text" name="subject" class="form-control" required 
                               placeholder="What is this about?"
                               value="<?php echo isset($_SESSION['contact_form_data']['subject']) ? htmlspecialchars($_SESSION['contact_form_data']['subject']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Message *</label>
                        <textarea name="message" class="form-control" rows="5" required 
                                  placeholder="Type your message here..."><?php echo isset($_SESSION['contact_form_data']['message']) ? htmlspecialchars($_SESSION['contact_form_data']['message']) : ''; ?></textarea>
                    </div>
                    
                    <?php unset($_SESSION['contact_form_data']); ?>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.753623456789!2d32.582519!3d0.313611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMMKwMTgnNDkuMCJOIDMywrAzNCc1Ny4xIkU!5e0!3m2!1sen!2sug!4v1234567890" 
                    allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <img src="images/logo-2.png" alt="RAYS OF GRACE">
                </div>
                <p>Knowledge changing lives forever</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/profile.php?id=100057146993995" target="_blank" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="https://x.com/raysofgracejr" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/raysofgraceacademyuganda" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/256778086883" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
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
                <p><i class="fas fa-phone"></i> +256 778 086 883</p>
                <p><i class="fas fa-envelope"></i> info@raysofgrace.ac.ug</p>
                <p><i class="fas fa-map-marker-alt"></i> Kampala, Uganda</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Rays of Grace | All Rights Reserved</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        // This script toggles the visibility of the mobile navigation menu when the menu icon is clicked. It also changes the icon from a hamburger (fa-bars) to a close (fa-times) icon when the menu is active, providing visual feedback to users. Additionally, it includes functionality to close the menu when clicking outside of it or when clicking on a navigation link, enhancing the user experience on mobile devices.
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>