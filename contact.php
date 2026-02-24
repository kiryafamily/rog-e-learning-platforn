<?php
// contact.php - CLEANED VERSION
session_start(); // Must be at the very top
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
                <li><a href="login.php" class="btn btn-outline">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Subscribe</a></li>
            </ul>
        </div>
    </nav>

    <!-- Display Success/Error Messages - Placed ONCE after navigation -->
    <?php if (isset($_SESSION['contact_success'])): ?>
        <div class="alert alert-success" style="max-width: 1200px; margin: 20px auto; padding: 15px 20px; background: #E8F5E9; border-left: 4px solid #4CAF50; border-radius: 5px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 24px;"></i>
            <div>
                <strong style="color: #2E7D32;">Success!</strong>
                <p style="color: #2E7D32; margin: 0;"><?php echo $_SESSION['contact_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['contact_success'], $_SESSION['contact_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['contact_error'])): ?>
        <div class="alert alert-error" style="max-width: 1200px; margin: 20px auto; padding: 15px 20px; background: #FFEBEE; border-left: 4px solid #f44336; border-radius: 5px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="color: #f44336; font-size: 24px;"></i>
            <div>
                <strong style="color: #C62828;">Error!</strong>
                <p style="color: #C62828; margin: 0;"><?php echo $_SESSION['contact_error']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['contact_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['contact_errors'])): ?>
        <div class="alert alert-error" style="max-width: 1200px; margin: 20px auto; padding: 15px 20px; background: #FFEBEE; border-left: 4px solid #f44336; border-radius: 5px;">
            <i class="fas fa-exclamation-triangle" style="color: #f44336; font-size: 24px; margin-right: 10px;"></i>
            <strong style="color: #C62828;">Please fix the following errors:</strong>
            <ul style="margin-top: 10px; color: #C62828;">
                <?php foreach ($_SESSION['contact_errors'] as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['contact_errors']); ?>
    <?php endif; ?>

    <div class="contact-container">
        <div class="contact-header">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>

        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <div class="info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Visit Us</h3>
                    <p>Kampala, Uganda</p>
                </div>
                
                <div class="info-card">
                    <i class="fas fa-phone"></i>
                    <h3>Call Us</h3>
                    <p>+256 XXX XXXXXX</p>
                </div>
                
                <div class="info-card">
                    <i class="fas fa-envelope"></i>
                    <h3>Email Us</h3>
                    <p>info@raysofgrace.ac.ug</p>
                </div>
                
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h3>Working Hours</h3>
                    <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                    <p>Saturday: 9:00 AM - 1:00 PM</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-container">
                <h2>Send a Message</h2>
                <form action="send-message.php" method="POST" class="contact-form">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Enter your full name"
                               value="<?php echo isset($_SESSION['contact_form_data']['name']) ? htmlspecialchars($_SESSION['contact_form_data']['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="Enter your email"
                               value="<?php echo isset($_SESSION['contact_form_data']['email']) ? htmlspecialchars($_SESSION['contact_form_data']['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Enter your phone number (optional)"
                               value="<?php echo isset($_SESSION['contact_form_data']['phone']) ? htmlspecialchars($_SESSION['contact_form_data']['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required 
                               placeholder="What is this about?"
                               value="<?php echo isset($_SESSION['contact_form_data']['subject']) ? htmlspecialchars($_SESSION['contact_form_data']['subject']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required 
                                  placeholder="Type your message here..."><?php echo isset($_SESSION['contact_form_data']['message']) ? htmlspecialchars($_SESSION['contact_form_data']['message']) : ''; ?></textarea>
                    </div>
                    
                    <?php 
                    // Clear form data after displaying
                    unset($_SESSION['contact_form_data']); 
                    ?>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-section">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.753623456789!2d32.582519!3d0.313611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMMKwMTgnNDkuMCJOIDMywrAzNCc1Ny4xIkU!5e0!3m2!1sen!2sug!4v1234567890" 
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <img src="images/logo.png" alt="RAYS OF GRACE" class="footer-logo">
                    <p>Knowledge changing lives forever. Providing quality digital education for Primary students in Uganda and beyond.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#classes">Classes</a></li>
                        <li><a href="index.php#subjects">Subjects</a></li>
                        <li><a href="index.php#pricing">Pricing</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Help Center</a></li>
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

    <style>
    .contact-container {
        max-width: 1200px;
        margin: 50px auto;
        padding: 0 20px;
    }
    
    .contact-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .contact-header h1 {
        color: #4B1C3C;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .contact-header p {
        color: #666;
        font-size: 1.1rem;
    }
    
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 40px;
    }
    
    .contact-info {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .info-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(75,28,60,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(75,28,60,0.15);
    }
    
    .info-card i {
        font-size: 2.5rem;
        color: #FFB800;
        margin-bottom: 15px;
    }
    
    .info-card h3 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .info-card p {
        color: #666;
        margin: 5px 0;
    }
    
    .contact-form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(75,28,60,0.1);
    }
    
    .contact-form-container h2 {
        color: #4B1C3C;
        margin-bottom: 30px;
    }
    
    .contact-form .form-group {
        margin-bottom: 20px;
    }
    
    .contact-form label {
        display: block;
        margin-bottom: 5px;
        color: #4B1C3C;
        font-weight: 500;
    }
    
    .contact-form input,
    .contact-form textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 5px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .contact-form input:focus,
    .contact-form textarea:focus {
        outline: none;
        border-color: #FFB800;
        box-shadow: 0 0 0 3px rgba(255,184,0,0.1);
    }
    
    .map-section {
        margin-top: 50px;
    }
    
    @media (max-width: 768px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Alert Styles */
    .alert {
        margin: 20px auto;
        padding: 15px 20px;
        border-radius: 5px;
        max-width: 1200px;
    }
    
    .alert-success {
        background: #E8F5E9;
        border-left: 4px solid #4CAF50;
    }
    
    .alert-error {
        background: #FFEBEE;
        border-left: 4px solid #f44336;
    }
    </style>

    <script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu')?.addEventListener('click', function() {
        document.querySelector('.nav-menu').classList.toggle('active');
        const icon = this.querySelector('i');
        if (icon.classList.contains('fa-bars')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
    </script>
</body>
</html>