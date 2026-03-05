<?php
// quizzes.php - Quiz Listing Page
// This page lists all available quizzes for the user, showing their progress and allowing them to start or review quizzes. It checks if the user is logged in and retrieves quiz data from the database, including the number of questions, last score, and last taken date. The design is modern and visually appealing, with clear calls to action for starting quizzes and reviewing results.
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - RAYS OF GRACE</title>
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

        /* Navigation */
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

        .nav-right {
            display: flex;
            gap: 15px;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #4B1C3C;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }

        /* Main Container */
        .quizzes-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .quizzes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .quizzes-header h1 {
            color: #4B1C3C;
            font-size: 2.2rem;
        }

        .quizzes-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .back-link {
            color: #4B1C3C;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .back-link:hover {
            color: #FFB800;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(75,28,60,0.15);
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

        .stat-info h3 {
            color: #4B1C3C;
            font-size: 1.8rem;
            line-height: 1.2;
        }

        .stat-info p {
            color: #666;
        }

        /* Quiz Grid */
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .quiz-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(255,184,0,0.1);
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(75,28,60,0.15);
            border-color: #FFB800;
        }

        .quiz-header {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            padding: 20px;
            color: white;
            position: relative;
        }

        .quiz-class {
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

        .quiz-header h3 {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 5px;
            padding-right: 60px;
        }

        .quiz-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quiz-header i {
            color: #FFB800;
        }

        .quiz-body {
            padding: 20px;
        }

        .quiz-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #666;
            font-size: 0.95rem;
        }

        .quiz-info i {
            color: #FFB800;
            width: 20px;
        }

        .last-score {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
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

        .quiz-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-quiz {
            background: #4B1C3C;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-quiz:hover {
            background: #2F1224;
            transform: translateX(5px);
        }

        .btn-quiz i {
            color: #FFB800;
        }

        .btn-quiz:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .no-quizzes {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 15px;
            grid-column: 1 / -1;
        }

        .no-quizzes i {
            font-size: 4rem;
            color: #4B1C3C;
            opacity: 0.3;
            margin-bottom: 15px;
        }

        .no-quizzes h3 {
            color: #4B1C3C;
            margin-bottom: 10px;
        }

        .no-quizzes p {
            color: #666;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quiz-grid {
                grid-template-columns: 1fr;
            }
            
            .quizzes-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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

    <div class="quizzes-container">
        <!-- Header -->
        <div class="quizzes-header">
            <h1>
                <i class="fas fa-question-circle"></i>
                Quiz Center
            </h1>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                <a href="lessons.php" class="btn-outline" style="display: inline-block; margin-top: 20px;">
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
                                <div style="color: #999; font-size: 0.85rem; margin-bottom: 10px;">
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
                                    <a href="quiz-results.php?lesson=<?php echo $quiz['id']; ?>" style="color: #4B1C3C;">
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