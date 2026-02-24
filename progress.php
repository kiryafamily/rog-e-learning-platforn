<?php
// progress.php
// Comprehensive progress tracking with visual analytics

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$viewAs = $_GET['student'] ?? $user['id'];

// Check if viewing as parent
if ($viewAs != $user['id'] && $user['role'] === 'parent') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND family_id = ?");
    $stmt->execute([$viewAs, $user['family_id']]);
    $student = $stmt->fetch();
    if (!$student) {
        $viewAs = $user['id'];
    }
}

// Get overall statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT p.lesson_id) as started_lessons,
        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
        COALESCE(AVG(p.progress), 0) as avg_progress
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
    WHERE l.class = (SELECT class FROM users WHERE id = ?)
");
$stmt->execute([$viewAs, $viewAs]);
$stats = $stmt->fetch();

// Get subject-wise progress
$stmt = $pdo->prepare("
    SELECT 
        l.subject,
        COUNT(DISTINCT l.id) as total,
        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
        COALESCE(AVG(p.progress), 0) as avg_progress
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
    WHERE l.class = (SELECT class FROM users WHERE ?)
    GROUP BY l.subject
");
$stmt->execute([$viewAs, $viewAs]);
$subjectProgress = $stmt->fetchAll();

// Get weekly progress (last 8 weeks)
$weeklyData = [];
for ($i = 7; $i >= 0; $i--) {
    $weekStart = date('Y-m-d', strtotime("-$i weeks monday"));
    $weekEnd = date('Y-m-d', strtotime("-$i weeks sunday"));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as completed
        FROM progress p
        JOIN lessons l ON p.lesson_id = l.id
        WHERE p.user_id = ? 
        AND p.status = 'completed'
        AND DATE(p.completed_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$viewAs, $weekStart, $weekEnd]);
    $weeklyData[] = [
        'week' => "Week " . (8 - $i),
        'completed' => $stmt->fetch()['completed']
    ];
}

// Get quiz performance
$stmt = $pdo->prepare("
    SELECT 
        qr.*,
        l.topic,
        l.subject
    FROM quiz_results qr
    JOIN lessons l ON qr.lesson_id = l.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC
    LIMIT 10
");
$stmt->execute([$viewAs]);
$recentQuizzes = $stmt->fetchAll();

// Get time spent (estimated)
$stmt = $pdo->prepare("
    SELECT SUM(TIMESTAMPDIFF(MINUTE, last_accessed, NOW())) as total_minutes
    FROM progress
    WHERE user_id = ? AND last_accessed IS NOT NULL
");
$stmt->execute([$viewAs]);
$totalMinutes = $stmt->fetch()['total_minutes'] ?? 0;
$hoursSpent = round($totalMinutes / 60, 1);

// Get streak (consecutive days)
$stmt = $pdo->prepare("
    SELECT DATE(last_accessed) as access_date
    FROM progress
    WHERE user_id = ?
    GROUP BY DATE(last_accessed)
    ORDER BY access_date DESC
");
$stmt->execute([$viewAs]);
$accessDates = $stmt->fetchAll();

$streak = 0;
$currentDate = date('Y-m-d');
foreach ($accessDates as $index => $date) {
    if ($date['access_date'] == $currentDate) {
        $streak++;
        $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
    } else {
        break;
    }
}

// Get recommendations based on weak areas
$weakAreas = [];
foreach ($subjectProgress as $subject) {
    if ($subject['avg_progress'] < 50) {
        $weakAreas[] = $subject['subject'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Progress - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="images/logo.png" alt="RAYS OF GRACE">
                    <span>Learning Progress</span>
                </div>
            </div>
            <div class="nav-right">
                <?php if ($user['role'] === 'parent' && $viewAs != $user['id']): ?>
                <a href="progress.php" class="btn btn-outline btn-small">
                    <i class="fas fa-user"></i> My Progress
                </a>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="progress-container">
        <!-- Student Selector (for parents) -->
        <?php if ($user['role'] === 'parent'): 
            $stmt = $pdo->prepare("SELECT * FROM users WHERE family_id = ? AND role = 'student'");
            $stmt->execute([$user['family_id']]);
            $students = $stmt->fetchAll();
        ?>
        <div class="student-selector">
            <span><i class="fas fa-users"></i> Viewing progress for:</span>
            <select onchange="location.href='progress.php?student=' + this.value">
                <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['id']; ?>" <?php echo $viewAs == $student['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($student['fullname']); ?> (<?php echo $student['class']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-progress">
            <h1>Your Learning Journey</h1>
            <p class="streak-info">
                <i class="fas fa-fire" style="color: #FFB800;"></i>
                <strong><?php echo $streak; ?> day streak!</strong> Keep it up!
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(75, 28, 60, 0.1);">
                    <i class="fas fa-check-circle" style="color: #4B1C3C;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo $stats['completed_lessons']; ?>/<?php echo $stats['total_lessons']; ?></span>
                    <span class="metric-label">Lessons Completed</span>
                    <div class="mini-progress">
                        <div class="mini-bar" style="width: <?php echo ($stats['completed_lessons'] / max($stats['total_lessons'], 1)) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(255, 184, 0, 0.1);">
                    <i class="fas fa-clock" style="color: #FFB800;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo $hoursSpent; ?> hrs</span>
                    <span class="metric-label">Time Spent Learning</span>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(76, 175, 80, 0.1);">
                    <i class="fas fa-star" style="color: #4CAF50;"></i>
                </div>
                <div class="metric-content">
                    <?php
                    $avgQuiz = 0;
                    if (!empty($recentQuizzes)) {
                        $sum = array_sum(array_column($recentQuizzes, 'percentage'));
                        $avgQuiz = round($sum / count($recentQuizzes));
                    }
                    ?>
                    <span class="metric-value"><?php echo $avgQuiz; ?>%</span>
                    <span class="metric-label">Average Quiz Score</span>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(33, 150, 243, 0.1);">
                    <i class="fas fa-tachometer-alt" style="color: #2196F3;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo round($stats['avg_progress']); ?>%</span>
                    <span class="metric-label">Overall Progress</span>
                </div>
            </div>
        </div>

        <!-- Progress Charts -->
        <div class="charts-grid">
            <!-- Subject Progress Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Progress by Subject</h3>
                <canvas id="subjectChart" width="400" height="300"></canvas>
            </div>
            
            <!-- Weekly Progress Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Weekly Activity</h3>
                <canvas id="weeklyChart" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Subject Progress Details -->
        <div class="subject-details">
            <h3><i class="fas fa-list"></i> Subject Breakdown</h3>
            <div class="subject-table">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Progress</th>
                            <th>Completed</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectProgress as $subject): 
                            $progressPercent = round($subject['avg_progress']);
                            $status = $progressPercent >= 80 ? 'Excellent' : ($progressPercent >= 50 ? 'Good' : 'Needs Work');
                            $statusColor = $progressPercent >= 80 ? '#4CAF50' : ($progressPercent >= 50 ? '#FFB800' : '#f44336');
                        ?>
                        <tr>
                            <td><strong><?php echo $subject['subject']; ?></strong></td>
                            <td>
                                <div class="table-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%; background: <?php echo $statusColor; ?>"></div>
                                    </div>
                                    <span><?php echo $progressPercent; ?>%</span>
                                </div>
                            </td>
                            <td><?php echo $subject['completed']; ?></td>
                            <td><?php echo $subject['total']; ?></td>
                            <td style="color: <?php echo $statusColor; ?>"><?php echo $status; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Quiz Results -->
        <div class="recent-quizzes">
            <h3><i class="fas fa-question-circle"></i> Recent Quiz Performance</h3>
            <div class="quiz-timeline">
                <?php if (empty($recentQuizzes)): ?>
                    <p class="no-data">No quizzes taken yet. Start learning and test your knowledge!</p>
                <?php else: ?>
                    <?php foreach ($recentQuizzes as $quiz): ?>
                    <div class="quiz-item">
                        <div class="quiz-score <?php echo $quiz['percentage'] >= 50 ? 'passed' : 'failed'; ?>">
                            <?php echo round($quiz['percentage']); ?>%
                        </div>
                        <div class="quiz-info">
                            <h4><?php echo htmlspecialchars($quiz['topic']); ?></h4>
                            <p><?php echo $quiz['subject']; ?> • <?php echo date('d M Y', strtotime($quiz['created_at'])); ?></p>
                        </div>
                        <div class="quiz-detail">
                            <span><?php echo $quiz['score']; ?>/<?php echo $quiz['total']; ?> correct</span>
                            <a href="quiz-results.php?id=<?php echo $quiz['id']; ?>" class="btn-text">
                                Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recommendations -->
        <?php if (!empty($weakAreas)): ?>
        <div class="recommendations">
            <h3><i class="fas fa-lightbulb"></i> Recommended for You</h3>
            <div class="rec-cards">
                <?php foreach ($weakAreas as $area): 
                    // Get a lesson from this subject
                    $stmt = $pdo->prepare("
                        SELECT * FROM lessons 
                        WHERE class = (SELECT class FROM users WHERE id = ?) 
                        AND subject = ?
                        ORDER BY RAND() LIMIT 1
                    ");
                    $stmt->execute([$viewAs, $area]);
                    $lesson = $stmt->fetch();
                    if ($lesson):
                ?>
                <div class="rec-card">
                    <div class="rec-header">
                        <span class="rec-subject"><?php echo $area; ?></span>
                        <span class="rec-priority">Needs Attention</span>
                    </div>
                    <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                    <p>Week <?php echo $lesson['week']; ?> • <?php echo $lesson['duration']; ?></p>
                    <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary btn-small">
                        Review Now
                    </a>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Achievement Badges -->
        <div class="achievements">
            <h3><i class="fas fa-trophy"></i> Achievements</h3>
            <div class="badges-grid">
                <?php
                $badges = [
                    [
                        'icon' => 'fa-rocket',
                        'name' => 'First Lesson',
                        'description' => 'Completed your first lesson',
                        'earned' => $stats['completed_lessons'] > 0
                    ],
                    [
                        'icon' => 'fa-star',
                        'name' => 'Quick Learner',
                        'description' => 'Completed 10 lessons',
                        'earned' => $stats['completed_lessons'] >= 10
                    ],
                    [
                        'icon' => 'fa-brain',
                        'name' => 'Quiz Master',
                        'description' => 'Scored 100% on a quiz',
                        'earned' => in_array(100, array_column($recentQuizzes, 'percentage'))
                    ],
                    [
                        'icon' => 'fa-fire',
                        'name' => 'On Fire',
                        'description' => '7-day learning streak',
                        'earned' => $streak >= 7
                    ],
                    [
                        'icon' => 'fa-clock',
                        'name' => 'Dedicated',
                        'description' => 'Spent 10+ hours learning',
                        'earned' => $hoursSpent >= 10
                    ],
                    [
                        'icon' => 'fa-trophy',
                        'name' => 'All-Star',
                        'description' => 'Completed all lessons',
                        'earned' => $stats['completed_lessons'] == $stats['total_lessons']
                    ]
                ];
                ?>
                
                <?php foreach ($badges as $badge): ?>
                <div class="badge-card <?php echo $badge['earned'] ? 'earned' : 'locked'; ?>">
                    <div class="badge-icon">
                        <i class="fas <?php echo $badge['icon']; ?>"></i>
                    </div>
                    <h4><?php echo $badge['name']; ?></h4>
                    <p><?php echo $badge['description']; ?></p>
                    <?php if (!$badge['earned']): ?>
                    <span class="badge-lock"><i class="fas fa-lock"></i> Locked</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h3>Export Your Progress</h3>
            <div class="export-buttons">
                <button class="btn btn-outline" onclick="exportProgress('pdf')">
                    <i class="fas fa-file-pdf"></i> Download PDF Report
                </button>
                <button class="btn btn-outline" onclick="exportProgress('csv')">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </button>
                <button class="btn btn-outline" onclick="printProgress()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>

    <style>
    .progress-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .student-selector {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .student-selector select {
        padding: 8px 15px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
        font-size: 1rem;
        color: #4B1C3C;
        font-weight: 500;
    }
    
    .welcome-progress {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .welcome-progress h1 {
        color: #4B1C3C;
        margin: 0;
    }
    
    .streak-info {
        background: white;
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .metric-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .metric-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .metric-icon i {
        font-size: 1.5rem;
    }
    
    .metric-content {
        flex: 1;
    }
    
    .metric-value {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        line-height: 1.2;
    }
    
    .metric-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .mini-progress {
        height: 4px;
        background: #F0F0F0;
        border-radius: 2px;
        margin-top: 8px;
    }
    
    .mini-bar {
        height: 100%;
        background: #4B1C3C;
        border-radius: 2px;
    }
    
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .chart-card h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chart-card h3 i {
        color: #FFB800;
    }
    
    .subject-details,
    .recent-quizzes,
    .recommendations,
    .achievements,
    .export-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .subject-details h3,
    .recent-quizzes h3,
    .recommendations h3,
    .achievements h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .subject-details h3 i,
    .recent-quizzes h3 i,
    .recommendations h3 i,
    .achievements h3 i {
        color: #FFB800;
    }
    
    .subject-table {
        overflow-x: auto;
    }
    
    .subject-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .subject-table th {
        text-align: left;
        padding: 12px;
        background: #F5F5F5;
        color: #4B1C3C;
        font-weight: 600;
    }
    
    .subject-table td {
        padding: 12px;
        border-bottom: 1px solid #F0F0F0;
    }
    
    .table-progress {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .table-progress .progress-bar {
        flex: 1;
        height: 8px;
        background: #F0F0F0;
        border-radius: 4px;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 4px;
    }
    
    .quiz-timeline {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .quiz-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 15px;
        background: #F9F9F9;
        border-radius: 5px;
    }
    
    .quiz-score {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    .quiz-score.passed {
        background: #E8F5E9;
        color: #4CAF50;
    }
    
    .quiz-score.failed {
        background: #FFEBEE;
        color: #f44336;
    }
    
    .quiz-info {
        flex: 1;
    }
    
    .quiz-info h4 {
        margin-bottom: 3px;
        color: #333;
    }
    
    .quiz-info p {
        color: #999;
        font-size: 0.9rem;
    }
    
    .quiz-detail {
        text-align: right;
    }
    
    .quiz-detail span {
        display: block;
        color: #666;
        margin-bottom: 5px;
    }
    
    .rec-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .rec-card {
        background: #F9F9F9;
        padding: 20px;
        border-radius: 5px;
        border-left: 4px solid #f44336;
    }
    
    .rec-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .rec-subject {
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .rec-priority {
        background: #f44336;
        color: white;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
    }
    
    .badges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    
    .badge-card {
        text-align: center;
        padding: 20px;
        background: #F9F9F9;
        border-radius: 10px;
        position: relative;
    }
    
    .badge-card.earned .badge-icon {
        background: #FFB800;
        color: #4B1C3C;
    }
    
    .badge-card.locked {
        opacity: 0.6;
    }
    
    .badge-card.locked .badge-icon {
        background: #E0E0E0;
        color: #999;
    }
    
    .badge-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .badge-card h4 {
        color: #333;
        margin-bottom: 5px;
        font-size: 1rem;
    }
    
    .badge-card p {
        color: #666;
        font-size: 0.8rem;
        margin-bottom: 10px;
    }
    
    .badge-lock {
        color: #999;
        font-size: 0.8rem;
    }
    
    .export-buttons {
        display: flex;
        gap: 15px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .no-data {
        color: #999;
        text-align: center;
        padding: 30px;
    }
    
    @media (max-width: 768px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
        
        .welcome-progress {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .quiz-item {
            flex-direction: column;
            text-align: center;
        }
        
        .quiz-detail {
            text-align: center;
        }
        
        .export-buttons {
            flex-direction: column;
        }
    }
    </style>

    <script>
    // Subject Progress Chart
    const subjectCtx = document.getElementById('subjectChart').getContext('2d');
    new Chart(subjectCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($subjectProgress, 'subject')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($subjectProgress, 'avg_progress')); ?>,
                backgroundColor: [
                    '#4B1C3C',
                    '#FFB800',
                    '#4CAF50',
                    '#2196F3',
                    '#f44336',
                    '#9C27B0'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Weekly Progress Chart
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($weeklyData, 'week')); ?>,
            datasets: [{
                label: 'Lessons Completed',
                data: <?php echo json_encode(array_column($weeklyData, 'completed')); ?>,
                borderColor: '#4B1C3C',
                backgroundColor: 'rgba(75, 28, 60, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    function exportProgress(format) {
        if (format === 'pdf') {
            window.location.href = 'export-progress.php?format=pdf&student=<?php echo $viewAs; ?>';
        } else if (format === 'csv') {
            window.location.href = 'export-progress.php?format=csv&student=<?php echo $viewAs; ?>';
        }
    }
    
    function printProgress() {
        window.print();
    }
    </script>
</body>
</html>