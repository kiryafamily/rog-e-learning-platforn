<?php

// trial.php - Redesigned Elegant Version
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get sample free lessons from database
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE is_free = 1 AND status = 'published' LIMIT 3");
$stmt->execute();
$freeLessons = $stmt->fetchAll();

// If no free lessons in DB, use defaults
if (empty($freeLessons)) {
    $freeLessons = [
        ['class' => 'P.4', 'subject' => 'Science', 'topic' => 'The Human Body - Skeleton', 'description' => 'Learn about the 206 bones and their functions', 'icon' => 'fa-brain'],
        ['class' => 'P.5', 'subject' => 'Mathematics', 'topic' => 'Fractions - Addition & Subtraction', 'description' => 'Master adding and subtracting fractions', 'icon' => 'fa-calculator'],
        ['class' => 'P.7', 'subject' => 'Science', 'topic' => 'Electricity - How It Reaches Our Homes', 'description' => 'Understand hydroelectricity and transmission', 'icon' => 'fa-bolt']
    ];
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Trial - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #faf9fa;
            color: #333;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(75,28,60,0.08);
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
            height: 35px; /* Smaller logo */
            width: auto;
            border-radius: 5px;
        }

        .logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: #4B1C3C;
            line-height: 1.2;
        }

        .logo-text small {
            display: block;
            font-size: 0.65rem;
            color: #FFB800;
            letter-spacing: 0.5px;
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
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover {
            color: #4B1C3C;
        }

        .btn-outline {
            border: 2px solid #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            color: #4B1C3C;
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
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75,28,60,0.3);
        }

        /* Hero Section */
        .trial-hero {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            padding: 60px 0 40px;
            text-align: center;
            border-bottom: 1px solid rgba(255,184,0,0.2);
        }

        .trial-hero h1 {
            color: #4B1C3C;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .trial-hero h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .trial-hero p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Content */
        .trial-main {
            padding: 50px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            color: #4B1C3C;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .section-title p {
            color: #666;
        }

        /* Lesson Cards */
        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1000px;
            margin: 0 auto 50px;
        }

        .lesson-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            border: 1px solid rgba(75,28,60,0.1);
        }

        .lesson-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(75,28,60,0.1);
            border-color: #FFB800;
        }

        .card-badge {
            background: #FFB800;
            color: #4B1C3C;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 50px;
            display: inline-block;
            margin: 15px 0 0 15px;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 15px 15px;
        }

        .card-icon i {
            font-size: 1.8rem;
            color: #FFB800;
        }

        .card-content {
            padding: 0 15px 20px;
        }

        .card-content h3 {
            color: #4B1C3C;
            font-size: 1.2rem;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .card-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .class-tag {
            background: #f0e8f0;
            color: #4B1C3C;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .subject-tag {
            background: #4B1C3C;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .card-content p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .btn-watch {
            background: transparent;
            border: 1px solid #4B1C3C;
            color: #4B1C3C;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-watch:hover {
            background: #4B1C3C;
            color: white;
        }

        .btn-watch i {
            font-size: 0.8rem;
            color: #FFB800;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid rgba(255,184,0,0.2);
        }

        .cta-section h3 {
            color: #4B1C3C;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .cta-section p {
            color: #666;
            margin-bottom: 25px;
        }

        .btn-subscribe {
            background: #4B1C3C;
            color: white;
            padding: 12px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn-subscribe:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(75,28,60,0.3);
        }

        .btn-subscribe i {
            color: #FFB800;
        }

        .guarantee {
            margin-top: 15px;
            font-size: 0.85rem;
            color: #999;
        }

        .guarantee i {
            color: #FFB800;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .modal-header {
            padding: 15px 20px;
            background: #4B1C3C;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: white;
            font-size: 1.1rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #FFB800;
        }

        .modal-body {
            padding: 30px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-placeholder {
            text-align: center;
            color: #666;
        }

        .video-placeholder i {
            font-size: 3rem;
            color: #FFB800;
            margin-bottom: 15px;
        }

        /* Footer */
        .footer {
            background: #1a0d14;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .footer-logo img {
            height: 35px;
            width: auto;
        }

        .footer-logo span {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
        }

        .footer-about p {
            color: #999;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .social-links {
            display: flex;
            gap: 10px;
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
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .footer ul {
            list-style: none;
        }

        .footer li {
            margin-bottom: 8px;
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
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-contact i {
            color: #FFB800;
            width: 16px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #666;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .lessons-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .trial-hero h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="images/logo.png" alt="RAYS OF GRACE">
                <div class="logo-text">
                    RAYS OF GRACE
                    <small>Junior School</small>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#classes">Classes</a></li>
                <li><a href="index.php#subjects">Subjects</a></li>
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.php" class="btn-outline">Login</a></li>
                <li><a href="register.php" class="btn-primary">Subscribe</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="trial-hero">
        <div class="container">
            <h1><i class="fas fa-gift"></i> Try Free for 7 Days</h1>
            <p>Experience our quality lessons before you commit. No credit card required.</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="trial-main">
        <div class="container">
            <div class="section-title">
                <h2>Sample Lessons</h2>
                <p>Here are some free lessons to get you started</p>
            </div>

            <div class="lessons-grid">
                <?php foreach ($freeLessons as $lesson): ?>
                <div class="lesson-card">
                    <span class="card-badge">FREE</span>
                    <div class="card-icon">
                        <i class="fas <?php echo $lesson['icon'] ?? 'fa-book-open'; ?>"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($lesson['topic']); ?></h3>
                        <div class="card-meta">
                            <span class="class-tag"><?php echo $lesson['class']; ?></span>
                            <span class="subject-tag"><?php echo $lesson['subject']; ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($lesson['description']); ?></p>
                        <button class="btn-watch" onclick="showLesson('<?php echo $lesson['topic']; ?>')">
                            <i class="fas fa-play"></i> Watch Preview
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <h3>Ready to Start Learning?</h3>
                <p>Get full access to all lessons, quizzes, and progress tracking</p>
                <a href="register.php" class="btn-subscribe">
                    <i class="fas fa-rocket"></i> Subscribe Now
                </a>
                <div class="guarantee">
                    <i class="fas fa-check-circle"></i> 7-day money-back guarantee
                </div>
            </div>
        </div>
    </main>

    <!-- Video Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Lesson Preview</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="video-placeholder">
                    <i class="fas fa-video"></i>
                    <p>Video preview coming soon!</p>
                </div>
            </div>
        </div>
    </div>

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

   <script>
    // Store lesson data for better modal content
    const lessons = <?php echo json_encode($freeLessons); ?>;

    function showLesson(topic) {
        // Find the lesson data
        const lesson = lessons.find(l => l.topic === topic);
        
        // Update modal title
        document.getElementById('modalTitle').textContent = topic + ' - Preview';
        
        // Update modal body with more details
        const modalBody = document.getElementById('modalBody');
        if (lesson) {
            modalBody.innerHTML = `
                <div style="text-align: center;">
                    <i class="fas fa-video" style="font-size: 4rem; color: #FFB800; margin-bottom: 20px;"></i>
                    <h3 style="color: #4B1C3C; margin-bottom: 10px;">${lesson.topic}</h3>
                    <p style="color: #666; margin-bottom: 15px;">
                        <span style="background: #f0e8f0; padding: 3px 10px; border-radius: 4px;">${lesson.class}</span>
                        <span style="background: #4B1C3C; color: white; padding: 3px 10px; border-radius: 4px; margin-left: 5px;">${lesson.subject}</span>
                    </p>
                    <p style="color: #666; margin-bottom: 20px;">${lesson.description}</p>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px;">
                        <i class="fas fa-info-circle" style="color: #FFB800;"></i>
                        <p style="color: #666; margin-top: 5px;">This is a sample preview. Subscribe to access the complete video lesson.</p>
                    </div>
                </div>
            `;
        } else {
            modalBody.innerHTML = `
                <div class="video-placeholder">
                    <i class="fas fa-video"></i>
                    <p>Video preview coming soon!</p>
                </div>
            `;
        }
        
        // Show the modal
        document.getElementById('videoModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('videoModal').classList.remove('active');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('videoModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</body>
</html>