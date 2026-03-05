<?php
// index.php – RAYS OF GRACE Junior School Landing Page
// Fully responsive, with working mobile menu and tabs
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAYS OF GRACE Junior School – Knowledge Changing Lives Forever</title>
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Stylesheet (consolidated) -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- ===== STUNNING NAVIGATION ===== -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
            </div>
            
            <div class="mobile-menu" id="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            
            <ul class="nav-menu" id="nav-menu">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#classes">Classes</a></li>
                <li><a href="#subjects">Subjects</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.php" class="btn btn-outline">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Subscribe</a></li>
            </ul>
        </div>
    </nav>

    <!-- ===== HERO SECTION ===== -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>
                    <span class="block">Knowledge Changing</span>
                    <span class="gradient-text">Lives Forever</span>
                </h1>
                <p class="hero-subtitle">Access quality education from P.1 to P.7. Complete lessons, video tutorials, and workbooks anywhere, anytime.</p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <i class="fas fa-book-open stat-icon-small"></i>
                        <div>
                            <span class="stat-number">7</span>
                            <span class="stat-label">Classes</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-subject stat-icon-small"></i>
                        <div>
                            <span class="stat-number">8+</span>
                            <span class="stat-label">Subjects</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-video stat-icon-small"></i>
                        <div>
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Lessons</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-rocket"></i> Start Learning
                    </a>
                    <a href="#free-trial" class="btn btn-outline btn-large">
                        <i class="fas fa-play-circle"></i> Free Trial
                    </a>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="image-wrapper" style="background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(75,28,60,0.3);">
                    <img src="images/logo.png" alt="RAYS OF GRACE Junior School" style="width: 100%; max-width: 400px; display: block; margin: 0 auto;">
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES SECTION ===== -->
    <section class="features">
        <div class="container">
            <h2>Why Choose <span class="gold-text">RAYS OF GRACE</span></h2>
            <p class="section-subtitle">Everything your child needs to succeed in primary education</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-video"></i></div>
                    <h3>Video Lessons</h3>
                    <p>Engaging video explanations for every topic by experienced teachers</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-download"></i></div>
                    <h3>Offline Access</h3>
                    <p>Download lessons and learn even without internet connection</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-question-circle"></i></div>
                    <h3>Interactive Quizzes</h3>
                    <p>Test your understanding with auto-graded quizzes and instant feedback</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>Family Discount</h3>
                    <p>20% off for multiple children from the same family</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                    <h3>Workbook PDFs</h3>
                    <p>Complete digitized workbooks for all subjects</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Progress Tracking</h3>
                    <p>Monitor your child's learning journey with detailed analytics</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Mobile Friendly</h3>
                    <p>Learn on any device – phone, tablet, or computer</p>
                    <div class="feature-hover-effect"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h3>Learn Anytime</h3>
                    <p>24/7 access to all lessons and materials</p>
                    <div class="feature-hover-effect"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CLASSES SECTION ===== -->
    <section id="classes" class="classes">
        <div class="container">
            <h2><span>Our Classes</span></h2>
            <p class="section-subtitle">Complete curriculum coverage from Primary 1 to Primary 7</p>
            
            <div class="class-grid">
                <div class="class-card">
                    <div class="class-header">
                        <h3>Lower Primary</h3>
                        <p>P.1 – P.3</p>
                    </div>
                    <div class="class-body">
                        <ul>
                            <li><i class="fas fa-book"></i> Literacy 1A</li>
                            <li><i class="fas fa-book"></i> Literacy 1B</li>
                            <li><i class="fas fa-calculator"></i> Mathematics</li>
                            <li><i class="fas fa-pencil-alt"></i> Reading</li>
                            <li><i class="fas fa-pencil-alt"></i> Writing</li>
                            <li><i class="fas fa-language"></i> English Language</li>
                            <li><i class="fas fa-church"></i> Religious Education</li>
                        </ul>
                        <a href="lessons.php?class=p1-p3" class="view-lessons">View Lessons <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="class-card">
                    <div class="class-header">
                        <h3>Upper Primary</h3>
                        <p>P.4 – P.7</p>
                    </div>
                    <div class="class-body">
                        <ul>
                            <li><i class="fas fa-language"></i> Kiswahili</li>
                            <li><i class="fas fa-language"></i> English Language</li>
                            <li><i class="fas fa-church"></i> Religious Education</li>
                            <li><i class="fas fa-calculator"></i> Mathematics</li>
                            <li><i class="fas fa-flask"></i> Integrated Science</li>
                            <li><i class="fas fa-globe-africa"></i> Social Studies</li>
                        </ul>
                        <a href="lessons.php?class=p4-p7" class="view-lessons">View Lessons <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SUBJECTS SECTION WITH TABS ===== -->
    <section id="subjects" class="subjects">
        <div class="container">
            <h2><span>Our Subjects</span></h2>
            <p class="section-subtitle">Comprehensive coverage across all classes</p>
            
            <div class="subject-tabs">
                <button class="tab-btn active" id="tab-lower">Lower Primary (P.1-P.3)</button>
                <button class="tab-btn" id="tab-upper">Upper Primary (P.4-P.7)</button>
            </div>
            
            <!-- Lower Primary Subjects -->
            <div id="lower-subjects" class="subject-grid active">
                <div class="subject-item">
                    <i class="fas fa-book"></i>
                    <h4>Literacy 1A</h4>
                    <p>Reading, writing, and comprehension</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-book"></i>
                    <h4>Literacy 1B</h4>
                    <p>Grammar and communication</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-calculator"></i>
                    <h4>Mathematics</h4>
                    <p>Numbers, shapes, and measurements</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-pencil-alt"></i>
                    <h4>Reading</h4>
                    <p>Phonics and comprehension</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-pencil-alt"></i>
                    <h4>Writing</h4>
                    <p>Handwriting and composition</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-language"></i>
                    <h4>English Language</h4>
                    <p>Grammar and vocabulary</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-church"></i>
                    <h4>Religious Education</h4>
                    <p>Bible stories and moral values</p>
                </div>
            </div>
            
            <!-- Upper Primary Subjects -->
            <div id="upper-subjects" class="subject-grid">
                <div class="subject-item">
                    <i class="fas fa-language"></i>
                    <h4>Kiswahili</h4>
                    <p>Language and literature</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-language"></i>
                    <h4>English Language</h4>
                    <p>Advanced grammar and composition</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-church"></i>
                    <h4>Religious Education</h4>
                    <p>Christian living and values</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-calculator"></i>
                    <h4>Mathematics</h4>
                    <p>Algebra, geometry, and more</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-flask"></i>
                    <h4>Integrated Science</h4>
                    <p>Biology, chemistry, physics</p>
                </div>
                <div class="subject-item">
                    <i class="fas fa-globe-africa"></i>
                    <h4>Social Studies</h4>
                    <p>History, geography, civics</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PRICING SECTION ===== -->
    <section id="pricing" class="pricing">
        <div class="container">
            <h2><span>Simple Pricing</span></h2>
            <p class="section-subtitle">Choose the plan that works for your family</p>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3>Monthly</h3>
                    <div class="price">
                        <span class="currency">UGX</span>
                        <span class="amount">100,000</span>
                        <span class="period">/month</span>
                    </div>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Full access all classes</li>
                        <li><i class="fas fa-check"></i> All subjects included</li>
                        <li><i class="fas fa-check"></i> Video lessons</li>
                        <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                        <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                        <li><i class="fas fa-check"></i> Cancel anytime</li>
                    </ul>
                    <a href="register.php?plan=monthly" class="btn btn-outline btn-block">Choose Monthly</a>
                </div>
                
                <div class="pricing-card popular">
                    <div class="popular-badge">Best Value</div>
                    <h3>Termly</h3>
                    <div class="price">
                        <span class="currency">UGX</span>
                        <span class="amount">500,000</span>
                        <span class="period">/term</span>
                    </div>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Full access all classes</li>
                        <li><i class="fas fa-check"></i> All subjects included</li>
                        <li><i class="fas fa-check"></i> Video lessons</li>
                        <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                        <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                        <li><i class="fas fa-check"></i> Progress tracking</li>
                        <li><i class="fas fa-check"></i> <strong>20% family discount</strong></li>
                    </ul>
                    <a href="register.php?plan=termly" class="btn btn-primary btn-block">Choose Termly</a>
                </div>
                
                <div class="pricing-card">
                    <h3>Yearly</h3>
                    <div class="price">
                        <span class="currency">UGX</span>
                        <span class="amount">1,500,000</span>
                        <span class="period">/year</span>
                    </div>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Full access all classes</li>
                        <li><i class="fas fa-check"></i> All subjects included</li>
                        <li><i class="fas fa-check"></i> Video lessons</li>
                        <li><i class="fas fa-check"></i> Downloadable PDFs</li>
                        <li><i class="fas fa-check"></i> Quizzes & assessments</li>
                        <li><i class="fas fa-check"></i> Progress tracking</li>
                        <li><i class="fas fa-check"></i> 20% family discount</li>
                        <li><i class="fas fa-check"></i> 2 months free</li>
                    </ul>
                    <a href="register.php?plan=yearly" class="btn btn-outline btn-block">Choose Yearly</a>
                </div>
            </div>
            
            <div class="discount-note">
                <i class="fas fa-users"></i>
                <p><strong>Family Discount:</strong> 20% off for multiple children from the same family. Applies to Termly and Yearly plans.</p>
            </div>
        </div>
    </section>

    <!-- ===== FREE TRIAL SECTION ===== -->
    <section id="free-trial" class="trial">
        <div class="container">
            <h2><span>Try For Free</span></h2>
            <p class="section-subtitle" style="color: rgba(255,255,255,0.9);">Access sample lessons before you subscribe</p>
            
            <div class="trial-grid">
                <div class="trial-card">
                    <i class="fas fa-leaf"></i>
                    <h3>P.4 Science</h3>
                    <p>Plants and photosynthesis</p>
                    <a href="#" class="trial-btn">Watch Free <i class="fas fa-play"></i></a>
                </div>
                <div class="trial-card">
                    <i class="fas fa-calculator"></i>
                    <h3>P.5 Maths</h3>
                    <p>Fractions and decimals</p>
                    <a href="#" class="trial-btn">Watch Free <i class="fas fa-play"></i></a>
                </div>
                <div class="trial-card">
                    <i class="fas fa-globe-africa"></i>
                    <h3>P.7 Social Studies</h3>
                    <p>Map reading skills</p>
                    <a href="#" class="trial-btn">Watch Free <i class="fas fa-play"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-logo">
                    <img src="images/logo-2.png" alt="RAYS OF GRACE">
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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#classes">Classes</a></li>
                        <li><a href="#subjects">Subjects</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="#">Terms of Use</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contact Info</h4>
                    <ul>
                        <li><i class="fas fa-phone"></i> +256 778 086 883</li>
                        <li><i class="fas fa-envelope"></i> info@raysofgrace.ac.ug</li>
                        <li><i class="fas fa-map-marker-alt"></i> Kampala, Uganda</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Rays of Grace | All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/navbar.js"></script>
    <script>
        // Subject tabs functionality (if not already handled externally)
        document.addEventListener('DOMContentLoaded', function() {
            const tabLower = document.getElementById('tab-lower');
            const tabUpper = document.getElementById('tab-upper');
            const lowerSubjects = document.getElementById('lower-subjects');
            const upperSubjects = document.getElementById('upper-subjects');

            if (tabLower && tabUpper) {
                tabLower.addEventListener('click', function() {
                    tabLower.classList.add('active');
                    tabUpper.classList.remove('active');
                    lowerSubjects.classList.add('active');
                    upperSubjects.classList.remove('active');
                });

                tabUpper.addEventListener('click', function() {
                    tabUpper.classList.add('active');
                    tabLower.classList.remove('active');
                    upperSubjects.classList.add('active');
                    lowerSubjects.classList.remove('active');
                });
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                        // Close mobile menu if open
                        const navMenu = document.getElementById('nav-menu');
                        const mobileMenu = document.getElementById('mobile-menu');
                        if (navMenu && navMenu.classList.contains('active')) {
                            navMenu.classList.remove('active');
                            mobileMenu.classList.remove('active');
                            const icon = mobileMenu.querySelector('i');
                            icon.className = 'fas fa-bars';
                        }
                    }
                });
            });
        });
    </script>
    <!-- Optional service worker registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/offline-worker.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.error('Service Worker registration failed:', err));
            });
        }
    </script>
</body>
</html>