<?php
// progress.php - Clean & Simple Version
// This page displays the user's learning progress, including overall statistics, subject-wise progress, recent activity, and quiz performance. It checks if the user is logged in and retrieves the necessary data from the database to present a comprehensive progress report. The design is clean and focused on key metrics to help users track their learning journey effectively.
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$hasAccess = hasAccess($pdo, $user['id']);

// Get selected student (for parents)
$viewUserId = $user['id'];
$viewUser = $user;

if ($user['role'] === 'parent' && isset($_GET['student'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND family_id = ?");
    $stmt->execute([$_GET['student'], $user['family_id']]);
    $student = $stmt->fetch();
    
    if ($student) {
        $viewUserId = $student['id'];
        $viewUser = $student;
    }
}

// Get overall statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN l.id END) as completed_lessons,
        COALESCE(AVG(p.progress), 0) as avg_progress,
        COUNT(DISTINCT l.subject) as total_subjects,
        COUNT(DISTINCT CASE WHEN p.status = 'in_progress' THEN l.id END) as in_progress,
        COUNT(DISTINCT CASE WHEN p.status = 'not_started' OR p.status IS NULL THEN l.id END) as not_started
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
    WHERE l.class = (SELECT class FROM users WHERE id = ?)
");
$stmt->execute([$viewUserId, $viewUserId]);
$overallStats = $stmt->fetch();

// Get subject-wise progress
$stmt = $pdo->prepare("
    SELECT 
        l.subject,
        COUNT(DISTINCT l.id) as total,
        COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN l.id END) as completed,
        COALESCE(AVG(p.progress), 0) as avg_progress,
        MAX(p.last_accessed) as last_accessed
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
    WHERE l.class = (SELECT class FROM users WHERE id = ?)
    GROUP BY l.subject
    ORDER BY avg_progress DESC
");
$stmt->execute([$viewUserId, $viewUserId]);
$subjectProgress = $stmt->fetchAll();

// Get weekly activity for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE(last_accessed) as date,
        COUNT(*) as activities,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM progress
    WHERE user_id = ? 
    AND last_accessed >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(last_accessed)
    ORDER BY date
");
$stmt->execute([$viewUserId]);
$weeklyActivity = $stmt->fetchAll();

// Get recent completed lessons
$stmt = $pdo->prepare("
    SELECT l.*, p.completed_at, p.progress
    FROM progress p
    JOIN lessons l ON p.lesson_id = l.id
    WHERE p.user_id = ? AND p.status = 'completed'
    ORDER BY p.completed_at DESC
    LIMIT 5
");
$stmt->execute([$viewUserId]);
$recentCompleted = $stmt->fetchAll();

// Get in-progress lessons
$stmt = $pdo->prepare("
    SELECT l.*, p.progress, p.last_accessed
    FROM progress p
    JOIN lessons l ON p.lesson_id = l.id
    WHERE p.user_id = ? AND p.status = 'in_progress'
    ORDER BY p.last_accessed DESC
    LIMIT 5
");
$stmt->execute([$viewUserId]);
$inProgressLessons = $stmt->fetchAll();

// Get quiz performance
$stmt = $pdo->prepare("
    SELECT 
        qr.*,
        l.topic,
        l.subject,
        l.class
    FROM quiz_results qr
    JOIN lessons l ON qr.lesson_id = l.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC
    LIMIT 5
");
$stmt->execute([$viewUserId]);
$quizResults = $stmt->fetchAll();

// Calculate average quiz score
$avgQuizScore = 0;
if (!empty($quizResults)) {
    $total = 0;
    foreach ($quizResults as $q) {
        $total += $q['percentage'];
    }
    $avgQuizScore = round($total / count($quizResults));
}

// Get learning streak
$stmt = $pdo->prepare("
    SELECT DISTINCT DATE(last_accessed) as activity_date
    FROM progress
    WHERE user_id = ?
    ORDER BY activity_date DESC
");
$stmt->execute([$viewUserId]);
$activityDates = $stmt->fetchAll();

$streak = 0;
$currentDate = date('Y-m-d');

foreach ($activityDates as $index => $date) {
    if ($date['activity_date'] == $currentDate) {
        $streak++;
        $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
    } else {
        break;
    }
}

// Calculate total learning time (estimated)
$stmt = $pdo->prepare("
    SELECT SUM(TIMESTAMPDIFF(MINUTE, last_accessed, NOW())) as total_minutes
    FROM progress
    WHERE user_id = ? AND last_accessed IS NOT NULL
    LIMIT 100
");
$stmt->execute([$viewUserId]);
$totalMinutes = $stmt->fetch()['total_minutes'] ?? 0;
$totalHours = round($totalMinutes / 60, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Report - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        /* Navigation */
        .dashboard-nav {
            background-color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            font-size: 1.3rem;
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
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .btn-outline:hover {
            background-color: #4B1C3C;
            color: #ffffff;
        }

        /* Main Container */
        .progress-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            color: #4B1C3C;
            font-size: 1.8rem;
        }

        .page-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .student-selector {
            background-color: #f8f8f8;
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-selector select {
            border: none;
            background: transparent;
            padding: 5px;
            font-size: 0.95rem;
            color: #4B1C3C;
            font-weight: 500;
            outline: none;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: #f0e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: #FFB800;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #4B1C3C;
            line-height: 1.2;
        }

        .stat-info p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .chart-header h3 {
            color: #4B1C3C;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-header i {
            color: #FFB800;
        }

        /* Section Styles */
        .section-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #FFB800;
        }

        /* Subject Grid */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .subject-item {
            background-color: #f8f8f8;
            border-radius: 8px;
            padding: 15px;
        }

        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .subject-name {
            font-weight: 600;
            color: #4B1C3C;
        }

        .subject-percentage {
            color: #FFB800;
            font-weight: 600;
        }

        .progress-bar {
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            background-color: #4B1C3C;
            border-radius: 3px;
        }

        .subject-stats {
            display: flex;
            justify-content: space-between;
            color: #999;
            font-size: 0.85rem;
        }

        /* Lessons Grid */
        .lessons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .lesson-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .lesson-item:last-child {
            border-bottom: none;
        }

        .lesson-icon {
            width: 40px;
            height: 40px;
            background-color: #f0e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lesson-icon i {
            color: #FFB800;
        }

        .lesson-info {
            flex: 1;
        }

        .lesson-info h4 {
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .lesson-info p {
            color: #999;
            font-size: 0.8rem;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.completed {
            background-color: #e8f5e9;
            color: #4CAF50;
        }

        .badge.progress {
            background-color: #fff3e0;
            color: #FF9800;
        }

        /* Quiz Grid */
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .quiz-item {
            background-color: #f8f8f8;
            border-radius: 8px;
            padding: 15px;
        }

        .quiz-score {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4B1C3C;
            margin-bottom: 5px;
        }

        .quiz-score small {
            font-size: 0.85rem;
            color: #999;
            font-weight: normal;
        }

        .quiz-date {
            color: #999;
            font-size: 0.75rem;
            margin-top: 5px;
        }

        /* Download Button */
        .download-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #4B1C3C;
            color: #ffffff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .download-btn:hover {
            background-color: #2F1224;
            transform: scale(1.1);
        }

        .download-btn i {
            font-size: 1.2rem;
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .no-data i {
            font-size: 2.5rem;
            color: #FFB800;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .lessons-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="logo-area">
            <img src="images/logo-3.png" alt="RAYS OF GRACE">
        </div>
        <div class="nav-right">
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </nav>

    <div class="progress-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Learning Progress
            </h1>
            
            <?php if ($user['role'] === 'parent'): ?>
            <div class="student-selector">
                <i class="fas fa-user-graduate" style="color: #FFB800;"></i>
                <select onchange="location.href='?student=' + this.value">
                    <option value="<?php echo $user['id']; ?>">My Progress</option>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE family_id = ? AND role = 'student'");
                    $stmt->execute([$user['family_id']]);
                    $students = $stmt->fetchAll();
                    foreach ($students as $student):
                    ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo $viewUserId == $student['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['fullname']); ?> (<?php echo $student['class']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $overallStats['completed_lessons'] ?? 0; ?>/<?php echo $overallStats['total_lessons'] ?? 0; ?></h3>
                    <p>Lessons Completed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $avgQuizScore; ?>%</h3>
                    <p>Average Quiz Score</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $streak; ?></h3>
                    <p>Day Streak</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalHours; ?>h</h3>
                    <p>Learning Time</p>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-week"></i> Last 30 Days Activity</h3>
                </div>
                <canvas id="activityChart" height="200"></canvas>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Overall Progress</h3>
                </div>
                <canvas id="progressChart" height="200"></canvas>
            </div>
        </div>

        <!-- Subject Progress -->
        <div class="section-card">
            <div class="section-title">
                <h2><i class="fas fa-book"></i> Subject Progress</h2>
                <span style="color: #999;"><?php echo count($subjectProgress); ?> subjects</span>
            </div>

            <?php if (empty($subjectProgress)): ?>
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <p>No progress data yet. Start learning to see your progress!</p>
                </div>
            <?php else: ?>
                <div class="subject-grid">
                    <?php foreach ($subjectProgress as $subject): ?>
                    <div class="subject-item">
                        <div class="subject-header">
                            <span class="subject-name"><?php echo $subject['subject']; ?></span>
                            <span class="subject-percentage"><?php echo round($subject['avg_progress']); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $subject['avg_progress']; ?>%"></div>
                        </div>
                        <div class="subject-stats">
                            <span><?php echo $subject['completed']; ?>/<?php echo $subject['total']; ?> lessons</span>
                            <?php if ($subject['last_accessed']): ?>
                            <span><?php echo timeAgo($subject['last_accessed']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lessons in Progress & Recent Completions -->
        <div class="lessons-grid">
            <!-- In Progress -->
            <div class="section-card">
                <div class="section-title">
                    <h2><i class="fas fa-spinner"></i> In Progress</h2>
                    <span style="color: #FF9800;"><?php echo count($inProgressLessons); ?></span>
                </div>

                <?php if (empty($inProgressLessons)): ?>
                    <div class="no-data">
                        <i class="fas fa-play-circle"></i>
                        <p>No lessons in progress</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($inProgressLessons as $lesson): ?>
                    <div class="lesson-item">
                        <div class="lesson-icon">
                            <i class="fas fa-play"></i>
                        </div>
                        <div class="lesson-info">
                            <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                            <p><?php echo $lesson['class']; ?> • <?php echo $lesson['subject']; ?></p>
                        </div>
                        <span class="badge progress"><?php echo $lesson['progress']; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recently Completed -->
            <div class="section-card">
                <div class="section-title">
                    <h2><i class="fas fa-check-circle"></i> Completed</h2>
                    <span style="color: #4CAF50;"><?php echo count($recentCompleted); ?></span>
                </div>

                <?php if (empty($recentCompleted)): ?>
                    <div class="no-data">
                        <i class="fas fa-trophy"></i>
                        <p>No completed lessons yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentCompleted as $lesson): ?>
                    <div class="lesson-item">
                        <div class="lesson-icon">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                        </div>
                        <div class="lesson-info">
                            <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                            <p><?php echo $lesson['class']; ?> • <?php echo $lesson['subject']; ?></p>
                        </div>
                        <span class="badge completed">Done</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quiz Results -->
        <div class="section-card">
            <div class="section-title">
                <h2><i class="fas fa-question-circle"></i> Recent Quizzes</h2>
                <span style="color: #FFB800;">Avg: <?php echo $avgQuizScore; ?>%</span>
            </div>

            <?php if (empty($quizResults)): ?>
                <div class="no-data">
                    <i class="fas fa-question"></i>
                    <p>No quizzes taken yet</p>
                </div>
            <?php else: ?>
                <div class="quiz-grid">
                    <?php foreach ($quizResults as $quiz): ?>
                    <div class="quiz-item">
                        <div class="quiz-score">
                            <?php echo round($quiz['percentage']); ?>%
                            <small>/<?php echo $quiz['total']; ?></small>
                        </div>
                        <h4 style="color: #4B1C3C; font-size: 0.95rem;"><?php echo htmlspecialchars($quiz['topic']); ?></h4>
                        <p style="color: #666; font-size: 0.8rem;"><?php echo $quiz['class']; ?> • <?php echo $quiz['subject']; ?></p>
                        <div class="quiz-date">
                            <i class="far fa-clock"></i> <?php echo timeAgo($quiz['created_at']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Download Button -->
    <div class="download-btn" onclick="downloadReport()" title="Download Report">
        <i class="fas fa-download"></i>
    </div>

    <script>
        // Activity Chart
        const ctx1 = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php 
                    $dates = array_column($weeklyActivity, 'date');
                    $labels = array_map(function($date) {
                        return date('M d', strtotime($date));
                    }, $dates);
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'Activities',
                    data: <?php echo json_encode(array_column($weeklyActivity, 'activities')); ?>,
                    borderColor: '#4B1C3C',
                    backgroundColor: 'rgba(75, 28, 60, 0.05)',
                    borderWidth: 2,
                    pointBackgroundColor: '#FFB800',
                    pointBorderColor: '#ffffff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Progress Chart
        const ctx2 = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [
                        <?php echo $overallStats['completed_lessons'] ?? 0; ?>,
                        <?php echo $overallStats['in_progress'] ?? 0; ?>,
                        <?php echo $overallStats['not_started'] ?? 0; ?>
                    ],
                    backgroundColor: ['#4CAF50', '#FF9800', '#e0e0e0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        function downloadReport() {
            alert('Download feature coming soon!');
        }
    </script>
</body>
</html>