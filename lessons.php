<?php
// lessons.php - FULLY RESPONSIVE VERSION
// Browse and view lessons by class and subject

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'lessons.php';
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$hasAccess = hasAccess($pdo, $user['id']);

// Get selected class and subject
$selectedClass = $_GET['class'] ?? 'P1';
$selectedSubject = $_GET['subject'] ?? '';

// Get all classes
$classes = getClasses();

// Get subjects for selected class
$allSubjects = getSubjects($selectedClass);

// Get lessons for selected class and subject
$lessons = [];
$lessonCounts = [];

// Get lesson count by subject
foreach ($allSubjects as $subject) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lessons WHERE class = ? AND subject = ?");
    $stmt->execute([$selectedClass, $subject]);
    $lessonCounts[$subject] = $stmt->fetch()['count'];
}

// Get lessons (if user has access or show free/preview)
$sql = "SELECT * FROM lessons WHERE class = ?";
$params = [$selectedClass];

if ($selectedSubject) {
    $sql .= " AND subject = ?";
    $params[] = $selectedSubject;
}

$sql .= " ORDER BY week, id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allLessons = $stmt->fetchAll();

// Filter lessons based on access
if ($hasAccess) {
    // User has subscription - show all lessons
    $lessons = $allLessons;
} else {
    // No subscription - show only free lessons OR first lesson of each subject
    $seenSubjects = [];
    foreach ($allLessons as $lesson) {
        // Show if lesson is free OR it's the first of its subject
        if ($lesson['is_free'] || !in_array($lesson['subject'], $seenSubjects)) {
            $lessons[] = $lesson;
            $seenSubjects[] = $lesson['subject'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Lessons | ROGELE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --purple: #4B1C3C;
            --purple-dark: #2F1224;
            --purple-light: #6A2B52;
            --gold: #FFB800;
            --gold-dark: #D99B00;
            --white: #FFFFFF;
            --gray-light: #F5F5F5;
            --gray: #666666;
            --gray-dark: #333333;
            --shadow-sm: 0 2px 8px rgba(75, 28, 60, 0.1);
            --shadow-md: 0 4px 12px rgba(75, 28, 60, 0.15);
            --shadow-lg: 0 8px 24px rgba(75, 28, 60, 0.2);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--gray-light);
            min-height: 100vh;
        }

        /* ===== HORIZONTAL NAVIGATION - ALWAYS HORIZONTAL ===== */
        .dashboard-nav {
            background: white;
            padding: 12px 20px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dashboard-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            gap: 15px;
        }

        .nav-left .logo {
            display: flex;
            align-items: center;
        }

        .nav-left .logo a {
            display: block;
            line-height: 0;
        }

        .nav-left .logo img {
            height: 40px;
            width: auto;
            transition: var(--transition);
        }

        .nav-left .logo img:hover {
            transform: scale(1.05);
        }

        .nav-right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 50px;
            white-space: nowrap;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--purple);
            color: var(--purple);
        }

        .btn-outline:hover {
            background: var(--purple);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary {
            background: var(--purple);
            color: white;
            border: 2px solid var(--purple);
        }

        .btn-primary:hover {
            background: var(--purple-dark);
            border-color: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        /* Main Container */
        .lessons-container {
            display: flex;
            min-height: calc(100vh - 70px);
            background: var(--gray-light);
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        /* ===== SIDEBAR - FULLY RESPONSIVE ===== */
        .lessons-sidebar {
            width: 300px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
            position: sticky;
            top: 90px;
            transition: var(--transition);
        }

        .class-selector {
            margin-bottom: 25px;
        }

        .class-selector h3 {
            color: var(--purple);
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .class-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .class-btn {
            padding: 10px 5px;
            background: var(--gray-light);
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .class-btn:hover,
        .class-btn.active {
            background: var(--purple);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .subject-list {
            margin-bottom: 25px;
        }

        .subject-list h3 {
            color: var(--purple);
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subject-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            margin-bottom: 5px;
        }

        .subject-item:hover {
            background: var(--gray-light);
            transform: translateX(5px);
        }

        .subject-item.active {
            background: var(--purple);
        }

        .subject-item.active .subject-name {
            color: white;
        }

        .subject-item.active .lesson-count {
            color: var(--gold);
        }

        .subject-name {
            color: var(--gray-dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .lesson-count {
            color: #999;
            font-size: 0.8rem;
            background: rgba(0,0,0,0.05);
            padding: 3px 8px;
            border-radius: 20px;
        }

        .subject-item.active .lesson-count {
            background: rgba(255,255,255,0.2);
        }

        .upgrade-prompt {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-top: 20px;
        }

        .upgrade-prompt h4 {
            color: white;
            margin: 10px 0 5px;
        }

        .upgrade-prompt p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .btn-block {
            width: 100%;
            padding: 12px;
            background: var(--gold);
            color: var(--purple);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .btn-block:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .admin-quick-links {
            margin-top: 20px;
            padding: 15px;
            background: var(--gray-light);
            border-radius: 10px;
        }

        .admin-quick-links h4 {
            color: var(--purple);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-quick-links a {
            display: block;
            padding: 8px;
            color: var(--purple);
            text-decoration: none;
            transition: var(--transition);
            border-radius: 5px;
        }

        .admin-quick-links a:hover {
            background: white;
            transform: translateX(5px);
        }

        /* ===== MAIN CONTENT ===== */
        .lessons-main {
            flex: 1;
            min-width: 0;
        }

        .lessons-header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .lessons-header h1 {
            color: var(--purple);
            font-size: 1.5rem;
            margin: 0;
            line-height: 1.3;
        }

        .lessons-header p {
            color: var(--gray);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-actions .sort-select {
            padding: 10px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            background: white;
            color: var(--gray-dark);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .preview-notice {
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .preview-notice i {
            color: #FF9800;
            font-size: 1.8rem;
        }

        .preview-notice strong {
            color: var(--purple);
            display: block;
            margin-bottom: 3px;
        }

        .preview-notice p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .no-lessons {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
        }

        .no-lessons i {
            font-size: 4rem;
            color: var(--purple);
            opacity: 0.3;
            margin-bottom: 15px;
        }

        .no-lessons h3 {
            color: var(--purple);
            margin-bottom: 10px;
        }

        .no-lessons p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* ===== LESSONS GRID ===== */
        .lessons-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .lesson-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
        }

        .lesson-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .lesson-card-content {
            display: flex;
            flex-wrap: wrap;
        }

        .lesson-thumb {
            width: 200px;
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .lesson-thumb i {
            font-size: 3rem;
            color: var(--gold);
        }

        .lesson-details {
            flex: 1;
            padding: 20px;
            min-width: 250px;
        }

        .lesson-tags {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .tag {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .tag.class {
            background: var(--purple);
            color: white;
        }

        .tag.subject {
            background: var(--gold);
            color: var(--purple);
        }

        .tag.week {
            background: #E0E0E0;
            color: var(--gray);
        }

        .tag.free {
            background: #4CAF50;
            color: white;
        }

        .lesson-title {
            color: var(--purple);
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .lesson-description {
            color: var(--gray);
            margin-bottom: 15px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .lesson-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .duration {
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .lesson-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--purple);
            color: white;
        }

        .btn-primary:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--purple);
            color: var(--purple);
        }

        .btn-outline:hover {
            background: var(--purple);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .premium-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(244,67,54,0.9);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 2;
        }

        /* Weekly Navigation */
        .weekly-nav {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
            box-shadow: var(--shadow-sm);
        }

        .weekly-nav h3 {
            color: var(--purple);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .week-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .week-link {
            padding: 8px 15px;
            background: var(--gray-light);
            border-radius: 50px;
            text-decoration: none;
            color: var(--gray);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .week-link:hover {
            background: var(--purple);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Large Tablets (1024px and below) */
        @media (max-width: 1024px) {
            .lessons-container {
                padding: 15px;
                gap: 15px;
            }
            
            .lessons-sidebar {
                width: 260px;
            }
            
            .class-buttons {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Tablets (768px and below) */
        @media (max-width: 768px) {
            .lessons-container {
                flex-direction: column;
                padding: 15px;
            }
            
            .lessons-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 15px;
            }
            
            .class-buttons {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .lesson-thumb {
                width: 100%;
                padding: 30px;
            }
            
            .lesson-card-content {
                flex-direction: column;
            }
            
            .lessons-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .sort-select {
                width: 100%;
            }
            
            .preview-notice {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Mobile Phones (480px and below) */
        @media (max-width: 480px) {
            /* ===== HORIZONTAL NAVIGATION - STAYS HORIZONTAL ===== */
            .dashboard-nav .container {
                flex-direction: row;  /* Keep horizontal */
                align-items: center;
                justify-content: space-between;
                flex-wrap: nowrap;    /* Prevent wrapping */
                gap: 8px;
            }
            
            .nav-left .logo img {
                height: 32px;  /* Smaller logo on mobile */
            }
            
            .nav-right {
                flex-direction: row;  /* Keep buttons horizontal */
                gap: 6px;
            }
            
            .btn-small {
                padding: 6px 12px;
                font-size: 0.8rem;
                white-space: nowrap;
            }
            
            .btn-small i {
                margin-right: 4px;
            }
            
            /* Rest of your mobile styles */
            .class-buttons {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .lesson-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .lesson-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .lesson-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .lesson-tags {
                justify-content: flex-start;
            }
            
            .lesson-title {
                font-size: 1.1rem;
            }
            
            .weekly-nav .week-links {
                justify-content: center;
            }
            
            .premium-badge {
                top: 5px;
                right: 5px;
                padding: 3px 10px;
                font-size: 0.7rem;
            }
        }

        /* Small Mobile (360px and below) */
        @media (max-width: 360px) {
            /* Navigation stays horizontal but more compact */
            .dashboard-nav .container {
                gap: 4px;
            }
            
            .btn-small {
                padding: 5px 8px;
                font-size: 0.75rem;
            }
            
            .btn-small i {
                margin-right: 2px;
                font-size: 0.8rem;
            }
            
            .class-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .lesson-tags {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .tag {
                width: fit-content;
            }
            
            .lessons-header h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation - Always Horizontal -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <a href="index.php">
                        <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
                    </a>
                </div>
            </div>
            <div class="nav-right">
                <a href="dashboard.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <?php if (isAdmin()): ?>
                <a href="admin/upload-lesson.php" class="btn btn-primary btn-small">
                    <i class="fas fa-upload"></i> Upload
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="lessons-container">
        <!-- Sidebar - Class & Subject Selection -->
        <aside class="lessons-sidebar">
            <div class="class-selector">
                <h3><i class="fas fa-graduation-cap"></i> Select Class</h3>
                <div class="class-buttons">
                    <?php foreach ($classes as $class): ?>
                    <a href="?class=<?php echo $class; ?><?php echo $selectedSubject ? '&subject='.urlencode($selectedSubject) : ''; ?>" 
                       class="class-btn <?php echo $selectedClass === $class ? 'active' : ''; ?>">
                        <?php echo $class; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="subject-list">
                <h3><i class="fas fa-book-open"></i> Subjects</h3>
                <?php foreach ($allSubjects as $subject): ?>
                <a href="?class=<?php echo $selectedClass; ?>&subject=<?php echo urlencode($subject); ?>" 
                   class="subject-item <?php echo $selectedSubject === $subject ? 'active' : ''; ?>">
                    <span class="subject-name"><?php echo $subject; ?></span>
                    <span class="lesson-count"><?php echo $lessonCounts[$subject] ?? 0; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (!$hasAccess): ?>
            <div class="upgrade-prompt">
                <i class="fas fa-crown fa-2x"></i>
                <h4>Unlock All Lessons</h4>
                <p>Subscribe to access all <?php echo array_sum($lessonCounts); ?> lessons</p>
                <a href="pricing.php" class="btn-block">View Plans</a>
            </div>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <div class="admin-quick-links">
                <h4><i class="fas fa-tools"></i> Admin</h4>
                <a href="admin/upload-lesson.php">
                    <i class="fas fa-upload"></i> Upload New Lesson
                </a>
                <a href="admin/lessons.php">
                    <i class="fas fa-edit"></i> Manage All Lessons
                </a>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Content - Lessons List -->
        <main class="lessons-main">
            <div class="lessons-header">
                <div>
                    <h1>
                        <?php echo $selectedClass; ?> 
                        <?php if ($selectedSubject): ?>
                            <span style="color: var(--gold);">- <?php echo $selectedSubject; ?></span>
                        <?php else: ?>
                            <span style="color: var(--gold);">- All Subjects</span>
                        <?php endif; ?>
                    </h1>
                    <p>
                        <i class="fas fa-book"></i> 
                        <?php echo count($lessons); ?> lessons available
                    </p>
                </div>
                <div class="header-actions">
                    <select class="sort-select" onchange="sortLessons(this.value)">
                        <option value="week">Sort by Week</option>
                        <option value="topic">Sort by Topic</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>
            </div>

            <?php if (!$hasAccess): ?>
                <div class="preview-notice">
                    <i class="fas fa-eye"></i>
                    <div>
                        <strong>Preview Mode</strong>
                        <p>You can view sample lessons. Subscribe for full access to all <?php echo array_sum($lessonCounts); ?> lessons.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($lessons)): ?>
                <div class="no-lessons">
                    <i class="fas fa-books"></i>
                    <h3>No Lessons Available</h3>
                    <p>Lessons for <?php echo $selectedClass; ?> are being prepared.</p>
                    <?php if (isAdmin()): ?>
                        <a href="admin/upload-lesson.php" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload First Lesson
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="lessons-grid">
                    <?php foreach ($lessons as $index => $lesson): ?>
                    <div class="lesson-card">
                        <?php if (!$hasAccess && !$lesson['is_free'] && $index > 0): ?>
                        <div class="premium-badge">
                            <i class="fas fa-crown"></i> Premium
                        </div>
                        <?php endif; ?>
                        
                        <div class="lesson-card-content">
                            <!-- Thumbnail -->
                            <div class="lesson-thumb">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            
                            <!-- Content -->
                            <div class="lesson-details">
                                <div class="lesson-tags">
                                    <span class="tag class">
                                        <i class="fas fa-tag"></i> <?php echo $lesson['class']; ?>
                                    </span>
                                    <span class="tag subject">
                                        <i class="fas fa-book-open"></i> <?php echo $lesson['subject']; ?>
                                    </span>
                                    <span class="tag week">
                                        <i class="far fa-calendar"></i> Week <?php echo $lesson['week']; ?>
                                    </span>
                                    <?php if ($lesson['is_free']): ?>
                                    <span class="tag free">
                                        <i class="fas fa-star"></i> FREE
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="lesson-title"><?php echo htmlspecialchars($lesson['topic']); ?></h3>
                                
                                <p class="lesson-description">
                                    <?php echo htmlspecialchars(substr($lesson['description'] ?? 'No description available', 0, 150)); ?>...
                                </p>
                                
                                <div class="lesson-footer">
                                    <span class="duration">
                                        <i class="far fa-clock"></i> <?php echo $lesson['duration'] ?? '30 min'; ?>
                                    </span>
                                    
                                    <div class="lesson-actions">
                                        <?php if ($hasAccess || $lesson['is_free'] || $index === 0): ?>
                                            <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-play"></i> Start Lesson
                                            </a>
                                        <?php else: ?>
                                            <button onclick="alert('Please subscribe to access this lesson')" class="btn btn-outline">
                                                <i class="fas fa-lock"></i> Subscribe
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Weekly Navigation -->
            <?php if (!empty($lessons) && $hasAccess): ?>
            <div class="weekly-nav">
                <h3><i class="fas fa-calendar-week"></i> Jump to Week</h3>
                <div class="week-links">
                    <?php 
                    $weeks = array_unique(array_column($lessons, 'week'));
                    sort($weeks);
                    foreach ($weeks as $week): 
                    ?>
                    <a href="#week-<?php echo $week; ?>" class="week-link">
                        Week <?php echo $week; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function sortLessons(value) {
        console.log('Sorting by:', value);
        // Implement sorting logic here
        // You can add AJAX to reload sorted lessons
    }
    
    // Highlight current week on scroll (optional)
    window.addEventListener('scroll', function() {
        // Implementation for week highlighting
    });
    </script>
    <script src="js/navbar.js"></script>
</body>
</html>