<?php
// dashboard.php - STUNNING REDESIGN
// This page serves as the main dashboard for logged-in users, providing an overview of their learning progress, subscription status, and quick access to lessons and activities. It includes a personalized welcome message, a subscription banner for users without an active subscription, and various sections such as "Continue Learning", "Quick Access", and "Recent Activity". The design is modern and user-friendly, with a focus on providing relevant information at a glance and easy navigation to key features of the platform.
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$subscription = getUserSubscription($pdo, $user['id']);
$hasAccess = hasAccess($pdo, $user['id']);

// Get user's children if parent account
$children = [];
if ($user['role'] === 'parent') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE family_id = ? AND role = 'student'");
    $stmt->execute([$user['family_id']]);
    $children = $stmt->fetchAll();
}

// Get in-progress lessons
$stmt = $pdo->prepare("
    SELECT l.*, p.status, p.last_accessed, p.progress 
    FROM progress p
    JOIN lessons l ON p.lesson_id = l.id
    WHERE p.user_id = ? AND p.status = 'in_progress'
    ORDER BY p.last_accessed DESC
    LIMIT 3
");
$stmt->execute([$user['id']]);
$inProgress = $stmt->fetchAll();

// Get completed lessons count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM progress 
    WHERE user_id = ? AND status = 'completed'
");
$stmt->execute([$user['id']]);
$completedCount = $stmt->fetch()['count'];

// Get quiz scores
$stmt = $pdo->prepare("
    SELECT AVG(percentage) as avg_score 
    FROM quiz_results 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$avgScore = round($stmt->fetch()['avg_score'] ?? 0);

// Get recent activity
$stmt = $pdo->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user['id']]);
$activities = $stmt->fetchAll();

// Get total lessons count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM lessons 
    WHERE class = ? OR class IS NULL
");
$stmt->execute([$user['class'] ?? 'P1']);
$totalLessons = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RAYS OF GRACE</title>
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
        
        /* Top Navigation */
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
            height: 45px;
            width: auto;
            border-radius: 8px;
        }
        
        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4B1C3C;
        }
        
        .logo-area small {
            display: block;
            font-size: 0.7rem;
            color: #FFB800;
            letter-spacing: 1px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-greeting {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f4f8;
            padding: 8px 20px;
            border-radius: 50px;
            color: #4B1C3C;
            font-weight: 500;
        }
        
        .user-greeting i {
            color: #FFB800;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background: #4B1C3C;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .dropbtn:hover {
            background: #2F1224;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1;
            margin-top: 10px;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .dropdown-content a:hover {
            background: #f8f4f8;
            color: #4B1C3C;
        }
        
        .dropdown-content a i {
            width: 20px;
            color: #FFB800;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        /* Main Container */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        /* Sidebar */
        .dashboard-sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 25px 0;
        }
        
        .sidebar-header {
            padding: 0 25px 20px 25px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sidebar-header h3 {
            color: #4B1C3C;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            color: #FFB800;
            font-weight: 600;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 25px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: #f8f4f8;
            color: #4B1C3C;
            border-left-color: #FFB800;
        }
        
        .sidebar-menu li.active a {
            background: linear-gradient(90deg, rgba(75,28,60,0.1) 0%, rgba(255,184,0,0.05) 100%);
            color: #4B1C3C;
            border-left-color: #FFB800;
            font-weight: 500;
        }
        
        .sidebar-menu i {
            width: 20px;
            color: #FFB800;
        }
        
        /* Main Content */
        .dashboard-main {
            flex: 1;
            padding: 30px;
        }
        
        /* Subscription Banner */
        .subscription-banner {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(75,28,60,0.2);
        }
        
        .banner-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .banner-content i {
            font-size: 2.5rem;
            color: #FFB800;
        }
        
        .banner-text h3 {
            color: white;
            margin-bottom: 5px;
        }
        
        .banner-text p {
            color: rgba(255,255,255,0.9);
        }
        
        .btn-banner {
            background: #FFB800;
            color: #4B1C3C;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-banner:hover {
            background: white;
            transform: translateY(-2px);
        }
        
        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h1 {
            color: #4B1C3C;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .welcome-text p {
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .welcome-text i {
            color: #FFB800;
        }
        
        .streak-badge {
            background: linear-gradient(135deg, #FFB800 0%, #D99B00 100%);
            color: #4B1C3C;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .streak-badge i {
            font-size: 1.5rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(75,28,60,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,184,0,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon i {
            font-size: 2rem;
            color: #FFB800;
        }
        
        .stat-details {
            flex: 1;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4B1C3C;
            line-height: 1.2;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .stat-progress {
            margin-top: 8px;
            height: 5px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        
        .stat-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4B1C3C, #FFB800);
            border-radius: 5px;
        }
        
        /* Section Title */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title h2 {
            color: #4B1C3C;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title h2 i {
            color: #FFB800;
        }
        
        .view-all {
            color: #4B1C3C;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            color: #FFB800;
            gap: 8px;
        }
        
        /* Continue Learning Grid */
        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .lesson-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .lesson-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(75,28,60,0.15);
        }
        
        .lesson-thumb {
            height: 140px;
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lesson-thumb i {
            font-size: 3rem;
            color: #FFB800;
            opacity: 0.8;
        }
        
        .class-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #FFB800;
            color: #4B1C3C;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .lesson-info {
            padding: 20px;
        }
        
        .lesson-info h3 {
            color: #4B1C3C;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .lesson-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .progress-container {
            margin-bottom: 15px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4B1C3C, #FFB800);
            border-radius: 3px;
        }
        
        .continue-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #4B1C3C;
            text-decoration: none;
            font-weight: 500;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }
        
        .continue-btn:hover {
            color: #FFB800;
        }
        
        .continue-btn i {
            transition: transform 0.3s ease;
        }
        
        .continue-btn:hover i {
            transform: translateX(5px);
        }
        
        /* Quick Access Tiles */
        .class-tiles {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .class-tile {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #4B1C3C;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .class-tile:hover {
            background: #4B1C3C;
            color: white;
            transform: translateY(-3px);
            border-color: #FFB800;
        }
        
        .class-tile i {
            display: block;
            font-size: 1.5rem;
            color: #FFB800;
            margin-bottom: 8px;
        }
        
        .class-tile:hover i {
            color: white;
        }
        
        /* Recent Activity */
        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .activity-timeline {
            margin-top: 20px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            font-size: 1.2rem;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-action {
            color: #4B1C3C;
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.8rem;
        }
        
        .no-activity {
            text-align: center;
            color: #999;
            padding: 30px;
        }
        
        /* Family Section */
        .family-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .family-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .family-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f4f8;
            border-radius: 10px;
        }
        
        .family-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .family-info {
            flex: 1;
        }
        
        .family-info h4 {
            color: #4B1C3C;
            margin-bottom: 3px;
        }
        
        .family-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .family-progress {
            font-size: 0.8rem;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .lessons-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .class-tiles {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .lessons-grid {
                grid-template-columns: 1fr;
            }
            
            .class-tiles {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .subscription-banner {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="dashboard-nav">
        <div class="logo-area">
            <img src="images/logo.png" alt="RAYS OF GRACE">
            <div>
                <span>RAYS OF GRACE</span>
                <small>Learning Portal</small>
            </div>
        </div>
        
        <div class="user-menu">
            <div class="user-greeting">
                <i class="fas fa-user-circle"></i>
                <span><?php echo explode(' ', $user['fullname'])[0]; ?></span>
            </div>
            
            <div class="dropdown">
                <button class="dropbtn">
                    <i class="fas fa-cog"></i> Menu
                </button>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <h3>Menu</h3>
                <p>Dashboard</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="lessons.php">
                        <i class="fas fa-book-open"></i>
                        <span>My Lessons</span>
                    </a>
                </li>
                <li>
                    <a href="quizzes.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Quizzes</span>
                    </a>
                </li>
                <li>
                    <a href="progress.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress Report</span>
                    </a>
                </li>
                <li>
                    <a href="downloads.php">
                        <i class="fas fa-download"></i>
                        <span>Downloads</span>
                    </a>
                </li>
                <li>
                    <a href="help.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Subscription Banner -->
            <?php if (!$hasAccess): ?>
            <div class="subscription-banner">
                <div class="banner-content">
                    <i class="fas fa-crown"></i>
                    <div class="banner-text">
                        <h3>No Active Subscription</h3>
                        <p>Subscribe now to access all <?php echo $totalLessons; ?> lessons and features</p>
                    </div>
                </div>
                <a href="pricing.php" class="btn-banner">
                    <i class="fas fa-rocket"></i> View Plans
                </a>
            </div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo explode(' ', $user['fullname'])[0]; ?>! 👋</h1>
                    <p><i class="fas fa-calendar-check"></i> Continue your learning journey</p>
                </div>
                <div class="streak-badge">
                    <i class="fas fa-fire"></i>
                    <span>5 Day Streak!</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $completedCount; ?></div>
                        <div class="stat-label">Lessons Completed</div>
                        <div class="stat-progress">
                            <div class="stat-progress-bar" style="width: <?php echo $totalLessons > 0 ? ($completedCount/$totalLessons)*100 : 0; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo count($inProgress); ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $avgScore; ?>%</div>
                        <div class="stat-label">Average Quiz Score</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $subscription ? round((strtotime($subscription['end_date'])-time())/86400) : 0; ?></div>
                        <div class="stat-label">Days Remaining</div>
                    </div>
                </div>
            </div>

            <!-- Continue Learning -->
            <?php if (!empty($inProgress)): ?>
            <div class="section-title">
                <h2><i class="fas fa-play-circle"></i> Continue Learning</h2>
                <a href="lessons.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="lessons-grid">
                <?php foreach ($inProgress as $lesson): ?>
                <div class="lesson-card">
                    <div class="lesson-thumb">
                        <i class="fas fa-play-circle"></i>
                        <span class="class-badge"><?php echo $lesson['class']; ?></span>
                    </div>
                    <div class="lesson-info">
                        <h3><?php echo htmlspecialchars($lesson['topic']); ?></h3>
                        <p><?php echo $lesson['subject']; ?></p>
                        
                        <div class="progress-container">
                            <div class="progress-header">
                                <span>Progress</span>
                                <span><?php echo $lesson['progress'] ?? 0; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $lesson['progress'] ?? 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="continue-btn">
                            <span>Continue</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Quick Access by Class -->
            <div class="section-title">
                <h2><i class="fas fa-graduation-cap"></i> Quick Access by Class</h2>
            </div>

            <div class="class-tiles">
                <?php 
                $classes = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];
                foreach ($classes as $class): 
                ?>
                <a href="lessons.php?class=<?php echo $class; ?>" class="class-tile">
                    <i class="fas fa-book"></i>
                    <span><?php echo $class; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Recent Activity -->
            <div class="activity-card">
                <div class="section-title">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                
                <div class="activity-timeline">
                    <?php if (empty($activities)): ?>
                        <div class="no-activity">
                            <i class="fas fa-clock" style="font-size: 3rem; color: #FFB800; margin-bottom: 10px;"></i>
                            <p>No recent activity. Start learning!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?php 
                                    echo strpos($activity['action'], 'lesson') !== false ? 'fa-book' : 
                                        (strpos($activity['action'], 'quiz') !== false ? 'fa-question' : 
                                        (strpos($activity['action'], 'login') !== false ? 'fa-sign-in-alt' : 'fa-circle')); 
                                ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-action"><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></div>
                                <div class="activity-time"><?php echo timeAgo($activity['created_at']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Family Members (if parent) -->
            <?php if (!empty($children)): ?>
            <div class="family-section">
                <div class="section-title">
                    <h2><i class="fas fa-users"></i> Family Members</h2>
                </div>
                
                <div class="family-grid">
                    <?php foreach ($children as $child): ?>
                    <div class="family-card">
                        <div class="family-avatar">
                            <?php echo strtoupper(substr($child['fullname'], 0, 1)); ?>
                        </div>
                        <div class="family-info">
                            <h4><?php echo htmlspecialchars($child['fullname']); ?></h4>
                            <p><?php echo $child['class']; ?> • <?php echo ucfirst($child['role']); ?></p>
                            <div class="family-progress">
                                <i class="fas fa-chart-line"></i>
                                <span><?php echo calculateProgress($pdo, $child['id'], $child['class']); ?>% complete</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
        <script src="js/navbar.js"></script>
</body>
</html>