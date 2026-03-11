<?php
// quizzes.php - FULLY RESPONSIVE VERSION WITH HORIZONTAL NAV
// Quiz Listing Page

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

// Get all lessons that have quizzes
$stmt = $pdo->prepare("
    SELECT DISTINCT l.id, l.class, l.subject, l.topic, 
           (SELECT COUNT(*) FROM quiz_questions WHERE lesson_id = l.id) as question_count,
           (SELECT percentage FROM quiz_results WHERE lesson_id = l.id AND user_id = ? ORDER BY created_at DESC LIMIT 1) as last_score,
           (SELECT created_at FROM quiz_results WHERE lesson_id = l.id AND user_id = ? ORDER BY created_at DESC LIMIT 1) as last_taken
    FROM lessons l
    INNER JOIN quiz_questions q ON l.id = q.lesson_id
    WHERE l.status = 'published'
    ORDER BY l.class, l.subject
");
$stmt->execute([$user['id'], $user['id']]);
$quizzes = $stmt->fetchAll();

// Get quiz statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT lesson_id) as total_quizzes,
        COUNT(*) as total_attempts,
        AVG(percentage) as avg_score
    FROM quiz_results
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return date('M j', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Quizzes | ROGELE</title>
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
            background: var(--white);
            padding: 12px 20px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .dashboard-nav .container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .logo img {
            height: 45px;
            width: auto;
            transition: var(--transition);
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .btn-dashboard {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--purple);
            color: var(--purple);
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            cursor: pointer;
        }

        .btn-dashboard:hover {
            background: var(--purple);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-dashboard i {
            font-size: 1rem;
        }

        /* ===== MAIN CONTAINER ===== */
        .quizzes-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* ===== HEADER ===== */
        .quizzes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .quizzes-header h1 {
            color: var(--purple);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quizzes-header h1 i {
            color: var(--gold);
        }

        .back-link {
            color: var(--purple);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--gold);
            transform: translateX(-3px);
        }

        /* ===== STATS GRID - FULLY RESPONSIVE ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: rgba(255,184,0,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: var(--gold);
        }

        .stat-info h3 {
            color: var(--purple);
            font-size: 1.6rem;
            line-height: 1.2;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.85rem;
        }

        /* ===== QUIZ GRID ===== */
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .quiz-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(255,184,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .quiz-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--gold);
        }

        .quiz-header {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            padding: 18px;
            color: var(--white);
            position: relative;
        }

        .quiz-class {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--gold);
            color: var(--purple);
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .quiz-header h3 {
            color: var(--white);
            font-size: 1.1rem;
            margin-bottom: 5px;
            padding-right: 60px;
            line-height: 1.4;
        }

        .quiz-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quiz-header i {
            color: var(--gold);
        }

        .quiz-body {
            padding: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .quiz-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            color: var(--gray);
            font-size: 0.9rem;
            flex-wrap: wrap;
            gap: 8px;
        }

        .quiz-info i {
            color: var(--gold);
            width: 18px;
        }

        .last-score {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .score-high {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .score-medium {
            background: #FFF3E0;
            color: #FF9800;
        }

        .score-low {
            background: #FFEBEE;
            color: #f44336;
        }

        .last-taken {
            color: #999;
            font-size: 0.8rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .last-taken i {
            color: var(--gold);
        }

        .quiz-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--gray-light);
        }

        .btn-quiz {
            background: var(--purple);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .btn-quiz:hover {
            background: var(--purple-dark);
            transform: translateX(3px);
        }

        .btn-quiz i {
            color: var(--gold);
            font-size: 0.85rem;
        }

        .btn-quiz:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .chart-link {
            color: var(--purple);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .chart-link:hover {
            color: var(--gold);
            transform: scale(1.1);
        }

        /* ===== NO QUIZZES STATE ===== */
        .no-quizzes {
            text-align: center;
            padding: 50px 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            grid-column: 1 / -1;
        }

        .no-quizzes i {
            font-size: 3.5rem;
            color: var(--purple);
            opacity: 0.3;
            margin-bottom: 15px;
        }

        .no-quizzes h3 {
            color: var(--purple);
            margin-bottom: 8px;
            font-size: 1.3rem;
        }

        .no-quizzes p {
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .btn-browse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--purple);
            color: var(--white);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-browse:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Large Tablets (1024px and below) */
        @media (max-width: 1024px) {
            .quiz-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        /* Tablets (768px and below) */
        @media (max-width: 768px) {
            .dashboard-nav {
                padding: 10px 15px;
            }
            
            .logo img {
                height: 38px;
            }
            
            .btn-dashboard {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .stats-grid {
                gap: 15px;
            }
            
            .quizzes-header {
                margin-bottom: 20px;
            }
            
            .quizzes-header h1 {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                width: 45px;
                height: 45px;
            }
            
            .stat-icon i {
                font-size: 1.5rem;
            }
            
            .stat-info h3 {
                font-size: 1.4rem;
            }
        }

        /* Mobile Phones (480px and below) */
        @media (max-width: 480px) {
            /* ===== HORIZONTAL NAVIGATION - STAYS HORIZONTAL ===== */
            .dashboard-nav {
                padding: 8px 12px;
            }
            
            .dashboard-nav .container {
                flex-direction: row;        /* Keep horizontal */
                align-items: center;
                justify-content: space-between;
                flex-wrap: nowrap;          /* Prevent wrapping */
                gap: 8px;
            }
            
            .logo img {
                height: 32px;               /* Smaller logo */
            }
            
            .btn-dashboard {
                padding: 6px 12px;
                font-size: 0.8rem;
                white-space: nowrap;
                gap: 4px;
            }
            
            /* Show icon always, keep text visible */
            .btn-dashboard i {
                font-size: 0.9rem;
            }
            
            .btn-dashboard span {
                display: inline;             /* Keep text visible */
            }
            
            /* Stats grid becomes 1 column */
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quizzes-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quizzes-header h1 {
                font-size: 1.4rem;
            }
            
            .back-link {
                font-size: 0.85rem;
            }
            
            .quiz-grid {
                grid-template-columns: 1fr;
            }
            
            .quiz-header h3 {
                font-size: 1rem;
            }
            
            .quiz-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .last-score {
                align-self: flex-start;
            }
            
            .btn-quiz {
                padding: 8px 14px;
                font-size: 0.85rem;
            }
            
            .no-quizzes {
                padding: 40px 15px;
            }
            
            .no-quizzes i {
                font-size: 3rem;
            }
            
            .no-quizzes h3 {
                font-size: 1.2rem;
            }
        }

        /* Small Mobile (360px and below) */
        @media (max-width: 360px) {
            /* Navigation stays horizontal but more compact */
            .dashboard-nav .container {
                gap: 4px;
            }
            
            .logo img {
                height: 28px;
            }
            
            .btn-dashboard {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            .btn-dashboard i {
                font-size: 0.8rem;
            }
            
            .quiz-header h3 {
                font-size: 0.95rem;
            }
            
            .quiz-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .btn-quiz {
                width: 100%;
                justify-content: center;
            }
        }

        /* Landscape Mode */
        @media (max-width: 768px) and (orientation: landscape) {
            .dashboard-nav .container {
                flex-direction: row;
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .quiz-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation - Always Horizontal -->
    <nav class="dashboard-nav">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="images/logo-3.png" alt="RAYS OF GRACE">
            </a>
            
            <div class="nav-right">
                <a href="dashboard.php" class="btn-dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="quizzes-container">
        <!-- Header -->
        <div class="quizzes-header">
            <h1>
                <i class="fas fa-question-circle"></i>
                Quiz Center
            </h1>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-right"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($quizzes); ?></h3>
                    <p>Available Quizzes</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_attempts'] ?? 0; ?></h3>
                    <p>Quizzes Taken</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo round($stats['avg_score'] ?? 0); ?>%</h3>
                    <p>Average Score</p>
                </div>
            </div>
        </div>

        <!-- Quiz Grid -->
        <?php if (empty($quizzes)): ?>
            <div class="no-quizzes">
                <i class="fas fa-question-circle"></i>
                <h3>No Quizzes Available</h3>
                <p>Check back later for new quizzes!</p>
                <a href="lessons.php" class="btn-browse">
                    <i class="fas fa-book"></i> Browse Lessons
                </a>
            </div>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <span class="quiz-class"><?php echo $quiz['class']; ?></span>
                            <h3><?php echo htmlspecialchars($quiz['topic']); ?></h3>
                            <p><i class="fas fa-book"></i> <?php echo $quiz['subject']; ?></p>
                        </div>
                        
                        <div class="quiz-body">
                            <div class="quiz-info">
                                <span><i class="fas fa-question-circle"></i> <?php echo $quiz['question_count']; ?> Questions</span>
                                <?php if ($quiz['last_score']): ?>
                                    <span class="last-score <?php 
                                        echo $quiz['last_score'] >= 70 ? 'score-high' : 
                                            ($quiz['last_score'] >= 50 ? 'score-medium' : 'score-low'); 
                                    ?>">
                                        Last: <?php echo $quiz['last_score']; ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($quiz['last_taken']): ?>
                                <div class="last-taken">
                                    <i class="far fa-clock"></i> Last taken: <?php echo timeAgo($quiz['last_taken']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="quiz-footer">
                                <?php if ($hasAccess): ?>
                                    <a href="quiz.php?lesson=<?php echo $quiz['id']; ?>" class="btn-quiz">
                                        <i class="fas fa-play"></i> Start Quiz
                                    </a>
                                <?php else: ?>
                                    <button class="btn-quiz" disabled>
                                        <i class="fas fa-lock"></i> Subscribe
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($quiz['last_score']): ?>
                                    <a href="quiz-results.php?lesson=<?php echo $quiz['id']; ?>" class="chart-link" title="View Results">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>