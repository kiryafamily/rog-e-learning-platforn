<?php
// quiz-results.php
// Detailed quiz results with analytics and feedback

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$resultId = $_GET['id'] ?? 0;

// Get quiz result details
$stmt = $pdo->prepare("
    SELECT qr.*, l.topic as lesson_topic, l.subject, l.class,
           u.fullname
    FROM quiz_results qr
    JOIN lessons l ON qr.lesson_id = l.id
    JOIN users u ON qr.user_id = u.id
    WHERE qr.id = ? AND qr.user_id = ?
");
$stmt->execute([$resultId, $user['id']]);
$result = $stmt->fetch();

if (!$result) {
    header('Location: progress.php');
    exit;
}

// Get detailed question breakdown
$answers = json_decode($result['answers'], true);

$stmt = $pdo->prepare("
    SELECT * FROM quiz_questions 
    WHERE lesson_id = ? 
    ORDER BY display_order
");
$stmt->execute([$result['lesson_id']]);
$questions = $stmt->fetchAll();

// Calculate strengths and weaknesses
$strengths = [];
$weaknesses = [];
$topicPerformance = [];

foreach ($questions as $q) {
    $isCorrect = isset($answers[$q['id']]) && $answers[$q['id']] === $q['correct_answer'];
    
    // Categorize by topic (you'd need a topic field in questions table)
    $topic = 'General';
    
    if (!isset($topicPerformance[$topic])) {
        $topicPerformance[$topic] = ['correct' => 0, 'total' => 0];
    }
    $topicPerformance[$topic]['total']++;
    if ($isCorrect) {
        $topicPerformance[$topic]['correct']++;
    }
}

// Get recommendations
$recommendations = [];
if ($result['percentage'] < 50) {
    $recommendations[] = [
        'type' => 'review',
        'title' => 'Review the Lesson',
        'description' => 'Watch the video lesson again and take notes',
        'action' => 'lesson-view.php?id=' . $result['lesson_id'],
        'action_text' => 'Review Lesson'
    ];
}

$stmt = $pdo->prepare("
    SELECT * FROM lessons 
    WHERE class = ? AND subject = ? AND id != ?
    ORDER BY RAND() LIMIT 3
");
$stmt->execute([$result['class'], $result['subject'], $result['lesson_id']]);
$relatedLessons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($result['lesson_topic']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="results-nav">
        <div class="container">
            <div class="nav-left">
                <a href="progress.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Progress
                </a>
            </div>
            <div class="nav-right">
                <span class="date"><?php echo date('F j, Y', strtotime($result['created_at'])); ?></span>
            </div>
        </div>
    </nav>

    <div class="results-container">
        <!-- Header with Score -->
        <div class="results-header">
            <div class="score-overview">
                <div class="score-circle <?php echo $result['percentage'] >= 50 ? 'passed' : 'failed'; ?>">
                    <span class="score-number"><?php echo round($result['percentage']); ?>%</span>
                </div>
                <div class="score-details">
                    <h1><?php echo htmlspecialchars($result['lesson_topic']); ?></h1>
                    <p class="subject-info"><?php echo $result['class']; ?> | <?php echo $result['subject']; ?></p>
                    <div class="score-stats">
                        <div class="stat">
                            <span class="label">Correct Answers</span>
                            <span class="value correct"><?php echo $result['score']; ?>/<?php echo $result['total']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Time Taken</span>
                            <span class="value"><?php echo $_SESSION['quiz_time'] ?? '15:30'; ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Status</span>
                            <span class="value status <?php echo $result['percentage'] >= 50 ? 'passed' : 'failed'; ?>">
                                <?php echo $result['percentage'] >= 50 ? 'PASSED' : 'NEEDS IMPROVEMENT'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Analytics -->
        <div class="analytics-grid">
            <!-- Performance by Topic -->
            <div class="analytics-card">
                <h3><i class="fas fa-chart-pie"></i> Performance by Topic</h3>
                <canvas id="topicChart" width="400" height="300"></canvas>
            </div>

            <!-- Strengths & Weaknesses -->
            <div class="analytics-card">
                <h3><i class="fas fa-star"></i> Strengths & Weaknesses</h3>
                <div class="strengths-list">
                    <?php foreach ($topicPerformance as $topic => $perf): 
                        $score = ($perf['correct'] / $perf['total']) * 100;
                        $isStrength = $score >= 70;
                    ?>
                    <div class="topic-item <?php echo $isStrength ? 'strength' : 'weakness'; ?>">
                        <div class="topic-header">
                            <span class="topic-name"><?php echo $topic; ?></span>
                            <span class="topic-score"><?php echo round($score); ?>%</span>
                        </div>
                        <div class="topic-bar">
                            <div class="bar-fill" style="width: <?php echo $score; ?>%"></div>
                        </div>
                        <span class="topic-label">
                            <i class="fas <?php echo $isStrength ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <?php echo $isStrength ? 'Strength' : 'Needs Practice'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Question Review -->
        <div class="review-section">
            <h2><i class="fas fa-clipboard-list"></i> Question Review</h2>
            
            <?php foreach ($questions as $index => $q): 
                $userAnswer = $answers[$q['id']] ?? '';
                $isCorrect = $userAnswer === $q['correct_answer'];
            ?>
            <div class="review-card <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                <div class="review-header">
                    <span class="question-number">Question <?php echo $index + 1; ?></span>
                    <span class="result-badge <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                        <i class="fas <?php echo $isCorrect ? 'fa-check' : 'fa-times'; ?>"></i>
                        <?php echo $isCorrect ? 'Correct' : 'Incorrect'; ?>
                    </span>
                </div>
                
                <p class="question-text"><?php echo htmlspecialchars($q['question']); ?></p>
                
                <div class="answers-grid">
                    <div class="user-answer">
                        <span class="label">Your Answer:</span>
                        <span class="answer <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                            <?php echo $q['option_' . $userAnswer] ?? 'Not answered'; ?>
                        </span>
                    </div>
                    <?php if (!$isCorrect): ?>
                    <div class="correct-answer">
                        <span class="label">Correct Answer:</span>
                        <span class="answer correct">
                            <?php echo $q['option_' . $q['correct_answer']]; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($q['explanation'])): ?>
                <div class="explanation">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong>Explanation:</strong>
                        <p><?php echo htmlspecialchars($q['explanation']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recommendations -->
        <div class="recommendations-section">
            <h2><i class="fas fa-lightbulb"></i> Personalized Recommendations</h2>
            
            <div class="recommendations-grid">
                <?php foreach ($recommendations as $rec): ?>
                <div class="recommendation-card <?php echo $rec['type']; ?>">
                    <i class="fas <?php 
                        echo $rec['type'] === 'review' ? 'fa-book-open' : 
                            ($rec['type'] === 'practice' ? 'fa-pencil-alt' : 'fa-video'); 
                    ?>"></i>
                    <h4><?php echo $rec['title']; ?></h4>
                    <p><?php echo $rec['description']; ?></p>
                    <a href="<?php echo $rec['action']; ?>" class="btn btn-primary btn-small">
                        <?php echo $rec['action_text']; ?>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($relatedLessons as $lesson): ?>
                <div class="recommendation-card related">
                    <i class="fas fa-book"></i>
                    <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                    <p><?php echo $lesson['class']; ?> | Week <?php echo $lesson['week']; ?></p>
                    <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-outline btn-small">
                        View Lesson
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Share Achievement -->
        <div class="share-section">
            <h3>Share Your Achievement</h3>
            <div class="share-buttons">
                <button class="share-btn facebook" onclick="share('facebook')">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
                <button class="share-btn twitter" onclick="share('twitter')">
                    <i class="fab fa-twitter"></i> Twitter
                </button>
                <button class="share-btn whatsapp" onclick="share('whatsapp')">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="share-btn download" onclick="downloadCertificate()">
                    <i class="fas fa-download"></i> Download Certificate
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="results-actions">
            <a href="quiz.php?lesson=<?php echo $result['lesson_id']; ?>" class="btn btn-primary">
                <i class="fas fa-redo"></i> Retake Quiz
            </a>
            <a href="lesson-view.php?id=<?php echo $result['lesson_id']; ?>" class="btn btn-outline">
                <i class="fas fa-book-open"></i> Review Lesson
            </a>
            <a href="progress.php" class="btn btn-outline">
                <i class="fas fa-chart-line"></i> View All Progress
            </a>
        </div>
    </div>

    <style>
    .results-nav {
        background: white;
        padding: 15px 0;
        border-bottom: 1px solid #E0E0E0;
    }
    
    .results-nav .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .results-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .results-header {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .score-overview {
        display: flex;
        gap: 40px;
        align-items: center;
    }
    
    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .score-circle.passed {
        background: conic-gradient(#4CAF50 0deg, #E0E0E0 <?php echo $result['percentage'] * 3.6; ?>deg);
    }
    
    .score-circle.failed {
        background: conic-gradient(#f44336 0deg, #E0E0E0 <?php echo $result['percentage'] * 3.6; ?>deg);
    }
    
    .score-number {
        width: 130px;
        height: 130px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: #4B1C3C;
    }
    
    .score-details h1 {
        color: #4B1C3C;
        margin-bottom: 5px;
    }
    
    .subject-info {
        color: #666;
        margin-bottom: 20px;
    }
    
    .score-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    
    .stat {
        display: flex;
        flex-direction: column;
    }
    
    .stat .label {
        font-size: 0.9rem;
        color: #999;
        margin-bottom: 5px;
    }
    
    .stat .value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .stat .value.correct {
        color: #4CAF50;
    }
    
    .stat .value.status.passed {
        color: #4CAF50;
    }
    
    .stat .value.status.failed {
        color: #f44336;
    }
    
    .analytics-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .analytics-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .analytics-card h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .analytics-card h3 i {
        color: #FFB800;
    }
    
    .strengths-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .topic-item {
        padding: 10px;
        background: #F9F9F9;
        border-radius: 5px;
    }
    
    .topic-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    
    .topic-name {
        font-weight: 500;
        color: #333;
    }
    
    .topic-score {
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .topic-bar {
        height: 8px;
        background: #E0E0E0;
        border-radius: 4px;
        margin: 5px 0;
    }
    
    .bar-fill {
        height: 100%;
        background: #4B1C3C;
        border-radius: 4px;
    }
    
    .topic-label {
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .topic-item.strength .topic-label {
        color: #4CAF50;
    }
    
    .topic-item.weakness .topic-label {
        color: #f44336;
    }
    
    .review-section {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .review-section h2 {
        color: #4B1C3C;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .review-section h2 i {
        color: #FFB800;
    }
    
    .review-card {
        background: #F9F9F9;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid transparent;
    }
    
    .review-card.correct {
        border-left-color: #4CAF50;
    }
    
    .review-card.incorrect {
        border-left-color: #f44336;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .question-number {
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .result-badge {
        padding: 3px 10px;
        border-radius: 3px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .result-badge.correct {
        background: #E8F5E9;
        color: #4CAF50;
    }
    
    .result-badge.incorrect {
        background: #FFEBEE;
        color: #f44336;
    }
    
    .question-text {
        font-size: 1.1rem;
        color: #333;
        margin-bottom: 15px;
    }
    
    .answers-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .user-answer .label,
    .correct-answer .label {
        font-size: 0.9rem;
        color: #999;
        margin-right: 10px;
    }
    
    .answer {
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: 500;
    }
    
    .answer.correct {
        background: #E8F5E9;
        color: #4CAF50;
    }
    
    .answer.incorrect {
        background: #FFEBEE;
        color: #f44336;
    }
    
    .explanation {
        background: #FFF3E0;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    
    .explanation i {
        color: #FFB800;
        font-size: 1.2rem;
    }
    
    .recommendations-section {
        margin-bottom: 30px;
    }
    
    .recommendations-section h2 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .recommendations-section h2 i {
        color: #FFB800;
    }
    
    .recommendations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .recommendation-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .recommendation-card.review {
        border-top: 4px solid #2196F3;
    }
    
    .recommendation-card.practice {
        border-top: 4px solid #FF9800;
    }
    
    .recommendation-card.related {
        border-top: 4px solid #4B1C3C;
    }
    
    .recommendation-card i {
        font-size: 2rem;
        color: #FFB800;
        margin-bottom: 10px;
    }
    
    .recommendation-card h4 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .recommendation-card p {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .share-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .share-section h3 {
        color: #4B1C3C;
        margin-bottom: 15px;
    }
    
    .share-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .share-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .share-btn.facebook {
        background: #3b5998;
        color: white;
    }
    
    .share-btn.twitter {
        background: #1da1f2;
        color: white;
    }
    
    .share-btn.whatsapp {
        background: #25d366;
        color: white;
    }
    
    .share-btn.download {
        background: #4B1C3C;
        color: white;
    }
    
    .share-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    
    .results-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .score-overview {
            flex-direction: column;
            text-align: center;
        }
        
        .score-stats {
            grid-template-columns: 1fr;
        }
        
        .analytics-grid {
            grid-template-columns: 1fr;
        }
        
        .answers-grid {
            grid-template-columns: 1fr;
        }
        
        .results-actions {
            flex-direction: column;
        }
    }
    </style>

    <script>
    // Performance Chart
    const ctx = document.getElementById('topicChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Correct', 'Incorrect'],
            datasets: [{
                data: [<?php echo $result['score']; ?>, <?php echo $result['total'] - $result['score']; ?>],
                backgroundColor: ['#4CAF50', '#f44336'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    function share(platform) {
        const text = encodeURIComponent(`I scored <?php echo $result['percentage']; ?>% on the "<?php echo $result['lesson_topic']; ?>" quiz at RAYS OF GRACE Junior School!`);
        const url = encodeURIComponent(window.location.href);
        
        let shareUrl = '';
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${text}%20${url}`;
                break;
        }
        
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
    
    function downloadCertificate() {
        // Redirect to certificate generation
        window.location.href = 'certificate.php?result=<?php echo $resultId; ?>';
    }
    </script>
</body>
</html>