<?php
// lessons.php
// Browse and view lessons by class and subject
// This page allows users to browse available lessons based on their class and subject. It checks the user's subscription status to determine which lessons they can access (free vs. premium). The page features a sidebar for class and subject selection, a main area displaying lesson cards with details, and prompts for users without access to subscribe for full content. Admin users have additional options to manage lessons directly from this page.
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons - RAYS OF GRACE Junior School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="images/logo.png" alt="RAYS OF GRACE">
                    <span>Lessons</span>
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
                <h3><i class="fas fa-graduation-cap" style="color: #FFB800;"></i> Select Class</h3>
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
                <h3><i class="fas fa-book-open" style="color: #FFB800;"></i> Subjects</h3>
                <?php foreach ($allSubjects as $subject): ?>
                <a href="?class=<?php echo $selectedClass; ?>&subject=<?php echo urlencode($subject); ?>" 
                   class="subject-item <?php echo $selectedSubject === $subject ? 'active' : ''; ?>">
                    <span class="subject-name"><?php echo $subject; ?></span>
                    <span class="lesson-count"><?php echo $lessonCounts[$subject] ?? 0; ?> lessons</span>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (!$hasAccess): ?>
            <div class="upgrade-prompt">
                <i class="fas fa-crown fa-2x" style="color: #FFB800;"></i>
                <h4>Unlock All Lessons</h4>
                <p>Subscribe to access all <?php echo array_sum($lessonCounts); ?> lessons</p>
                <a href="pricing.php" class="btn btn-primary btn-block">View Plans</a>
            </div>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <div class="admin-quick-links" style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
                <h4><i class="fas fa-tools" style="color: #4B1C3C;"></i> Admin</h4>
                <a href="admin/upload-lesson.php" style="display: block; padding: 8px; color: #4B1C3C; text-decoration: none;">
                    <i class="fas fa-upload"></i> Upload New Lesson
                </a>
                <a href="admin/lessons.php" style="display: block; padding: 8px; color: #4B1C3C; text-decoration: none;">
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
                            <span style="color: #FFB800;">- <?php echo $selectedSubject; ?></span>
                        <?php else: ?>
                            <span style="color: #FFB800;">- All Subjects</span>
                        <?php endif; ?>
                    </h1>
                    <p style="color: #666; margin-top: 5px;">
                        <i class="fas fa-book" style="color: #FFB800;"></i> 
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
                    <i class="fas fa-eye fa-2x" style="color: #FF9800;"></i>
                    <div>
                        <strong style="font-size: 18px;">Preview Mode</strong>
                        <p>You can view sample lessons. Subscribe for full access to all <?php echo array_sum($lessonCounts); ?> lessons.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($lessons)): ?>
                <div class="no-lessons">
                    <i class="fas fa-books fa-4x" style="color: #4B1C3C; opacity: 0.5; margin-bottom: 20px;"></i>
                    <h3 style="color: #4B1C3C; font-size: 24px; margin-bottom: 10px;">No Lessons Available</h3>
                    <p style="color: #666; font-size: 16px;">Lessons for <?php echo $selectedClass; ?> are being prepared.</p>
                    <?php if (isAdmin()): ?>
                        <a href="admin/upload-lesson.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-upload"></i> Upload First Lesson
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="lessons-grid">
                    <?php foreach ($lessons as $index => $lesson): ?>
                    <div class="lesson-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin-bottom: 20px; transition: all 0.3s ease; <?php echo (!$hasAccess && !$lesson['is_free'] && $index > 0) ? 'opacity: 0.8;' : ''; ?>">
                        <div style="display: flex; flex-wrap: wrap;">
                            <!-- Thumbnail -->
                            <div style="width: 200px; background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); display: flex; align-items: center; justify-content: center; padding: 20px;">
                                <i class="fas fa-play-circle fa-4x" style="color: #FFB800;"></i>
                            </div>
                            
                            <!-- Content -->
                            <div style="flex: 1; padding: 20px;">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                                    <span style="background: #4B1C3C; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-tag" style="margin-right: 5px;"></i> <?php echo $lesson['class']; ?>
                                    </span>
                                    <span style="background: #FFB800; color: #4B1C3C; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-book-open"></i> <?php echo $lesson['subject']; ?>
                                    </span>
                                    <span style="background: #E0E0E0; color: #666; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                        <i class="far fa-calendar"></i> Week <?php echo $lesson['week']; ?>
                                    </span>
                                    <?php if ($lesson['is_free']): ?>
                                    <span style="background: #4CAF50; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                        <i class="fas fa-star"></i> FREE
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 style="color: #4B1C3C; font-size: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($lesson['topic']); ?></h3>
                                
                                <p style="color: #666; margin-bottom: 15px; line-height: 1.6;">
                                    <?php echo htmlspecialchars(substr($lesson['description'] ?? 'No description available', 0, 150)); ?>...
                                </p>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: #999;">
                                        <i class="far fa-clock"></i> <?php echo $lesson['duration'] ?? '30 min'; ?>
                                    </span>
                                    
                                    <?php if ($hasAccess || $lesson['is_free'] || $index === 0): ?>
                                        <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-play"></i> Start Lesson
                                        </a>
                                    <?php else: ?>
                                        <button onclick="alert('Please subscribe to access this lesson')" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-lock"></i> Subscribe to Access
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Premium Badge for locked lessons -->
                            <?php if (!$hasAccess && !$lesson['is_free'] && $index > 0): ?>
                            <div style="position: absolute; top: 10px; right: 10px; background: rgba(244,67,54,0.9); color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-crown"></i> Premium
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Weekly Navigation -->
            <?php if (!empty($lessons) && $hasAccess): ?>
            <div class="weekly-nav" style="background: white; padding: 20px; border-radius: 10px; margin-top: 30px;">
                <h3 style="color: #4B1C3C; margin-bottom: 15px;"><i class="fas fa-calendar-week" style="color: #FFB800;"></i> Jump to Week</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php 
                    $weeks = array_unique(array_column($lessons, 'week'));
                    sort($weeks);
                    foreach ($weeks as $week): 
                    ?>
                    <a href="#week-<?php echo $week; ?>" style="padding: 8px 15px; background: #f5f5f5; border-radius: 5px; text-decoration: none; color: #666; transition: all 0.3s ease;">
                        Week <?php echo $week; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
    .lessons-container {
        display: flex;
        min-height: calc(100vh - 70px);
        background: #F5F5F5;
    }
    
    .lessons-sidebar {
        width: 300px;
        background: white;
        border-right: 1px solid #E0E0E0;
        padding: 20px;
        overflow-y: auto;
    }
    
    .class-selector {
        margin-bottom: 30px;
    }
    
    .class-selector h3 {
        color: #4B1C3C;
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
        background: #F5F5F5;
        border-radius: 5px;
        text-align: center;
        text-decoration: none;
        color: #666;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .class-btn:hover,
    .class-btn.active {
        background: #4B1C3C;
        color: white;
    }
    
    .subject-list {
        margin-bottom: 30px;
    }
    
    .subject-list h3 {
        color: #4B1C3C;
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
        border-radius: 5px;
        transition: all 0.3s ease;
        margin-bottom: 5px;
    }
    
    .subject-item:hover {
        background: #F5F5F5;
    }
    
    .subject-item.active {
        background: #4B1C3C;
    }
    
    .subject-item.active .subject-name {
        color: white;
    }
    
    .subject-item.active .lesson-count {
        color: #FFB800;
    }
    
    .subject-name {
        color: #333;
        font-weight: 500;
    }
    
    .lesson-count {
        color: #999;
        font-size: 0.9rem;
    }
    
    .upgrade-prompt {
        background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .upgrade-prompt h4 {
        color: white;
        margin-bottom: 10px;
    }
    
    .upgrade-prompt p {
        color: rgba(255,255,255,0.9);
        margin-bottom: 15px;
    }
    
    .lessons-main {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
    }
    
    .lessons-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .lessons-header h1 {
        color: #4B1C3C;
        margin: 0;
    }
    
    .sort-select {
        padding: 8px 15px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
        background: white;
    }
    
    .preview-notice {
        background: #FFF3E0;
        border-left: 4px solid #FF9800;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .no-lessons {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
    }
    
    .lessons-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .lesson-card {
        position: relative;
        transition: transform 0.3s ease;
    }
    
    .lesson-card:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 20px rgba(75,28,60,0.2);
    }
    
    .btn-primary {
        background: #4B1C3C;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background: #2F1224;
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid #4B1C3C;
        color: #4B1C3C;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-outline:hover {
        background: #4B1C3C;
        color: white;
    }
    
    @media (max-width: 768px) {
        .lessons-container {
            flex-direction: column;
        }
        
        .lessons-sidebar {
            width: 100%;
        }
        
        .class-buttons {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .lesson-card > div {
            flex-direction: column;
        }
        
        .lesson-card > div > div:first-child {
            width: 100%;
        }
        
        .lessons-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
    </style>

    <script>
    function sortLessons(value) {
        // Implement sorting logic
        console.log('Sorting by:', value);
        // You can add AJAX to reload sorted lessons
    }
    
    // Highlight current week on scroll
    window.addEventListener('scroll', function() {
        // Implementation for week highlighting
    });
    </script>
</body>
</html>