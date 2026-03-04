<?php
// quiz.php
// Interactive quiz taking system
// This page allows users to take quizzes associated with lessons. It checks if the user has access to the quiz based on their subscription and whether the lesson is free. The page retrieves quiz questions from the database, presents them in an interactive format, and handles quiz submission by calculating the score, saving results, and providing feedback. The design is user-friendly and encourages learning through immediate feedback and review of answers.

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$hasAccess = hasAccess($pdo, $user['id']);
$lessonId = $_GET['lesson'] ?? 0;

// Get lesson details
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: lessons.php');
    exit;
}

// Check access
if (!$hasAccess && !$lesson['is_free']) {
    header('Location: lesson-view.php?id=' . $lessonId);
    exit;
}

// Get quiz questions
$stmt = $pdo->prepare("
    SELECT * FROM quiz_questions 
    WHERE lesson_id = ? 
    ORDER BY display_order
");
$stmt->execute([$lessonId]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    header('Location: lesson-view.php?id=' . $lessonId);
    exit;
}

// Handle quiz submission
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
    $answers = $_POST['answers'];
    $score = 0;
    $total = count($questions);
    
    foreach ($questions as $q) {
        if (isset($answers[$q['id']]) && $answers[$q['id']] === $q['correct_answer']) {
            $score++;
        }
    }
    
    $percentage = ($score / $total) * 100;
    
    // Save results
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (user_id, lesson_id, score, total, percentage, answers, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        $lessonId,
        $score,
        $total,
        $percentage,
        json_encode($answers)
    ]);
    
    $result = [
        'score' => $score,
        'total' => $total,
        'percentage' => $percentage,
        'passed' => $percentage >= 50
    ];
    
    // Log activity
    logActivity($pdo, $user['id'], 'complete_quiz', "Quiz score: $percentage% on lesson: {$lesson['topic']}");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?php echo htmlspecialchars($lesson['topic']); ?> - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="quiz-nav">
        <div class="container">
            <div class="nav-left">
                <a href="lesson-view.php?id=<?php echo $lessonId; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Lesson
                </a>
                <span class="quiz-title">Quiz: <?php echo htmlspecialchars($lesson['topic']); ?></span>
            </div>
            <div class="nav-right">
                <span class="timer" id="timer"><i class="far fa-clock"></i> 00:00</span>
            </div>
        </div>
    </nav>

    <div class="quiz-container">
        <?php if ($result): ?>
            <!-- Quiz Results -->
            <div class="quiz-results">
                <div class="result-card <?php echo $result['passed'] ? 'passed' : 'failed'; ?>">
                    <div class="result-icon">
                        <i class="fas <?php echo $result['passed'] ? 'fa-trophy' : 'fa-redo-alt'; ?>"></i>
                    </div>
                    
                    <h2><?php echo $result['passed'] ? 'Congratulations!' : 'Keep Practicing!'; ?></h2>
                    
                    <div class="score-circle">
                        <span class="score-number"><?php echo $result['percentage']; ?>%</span>
                    </div>
                    
                    <p class="score-details">
                        You scored <?php echo $result['score']; ?> out of <?php echo $result['total']; ?> questions correctly.
                    </p>
                    
                    <?php if ($result['passed']): ?>
                        <p class="success-message">Great job! You've mastered this lesson.</p>
                    <?php else: ?>
                        <p class="fail-message">Don't worry! Review the lesson and try again.</p>
                    <?php endif; ?>
                    
                    <div class="result-actions">
                        <a href="lesson-view.php?id=<?php echo $lessonId; ?>" class="btn btn-primary">
                            <i class="fas fa-book-open"></i> Review Lesson
                        </a>
                        <a href="quiz.php?lesson=<?php echo $lessonId; ?>" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                    </div>
                    
                    <!-- Review Answers -->
                    <div class="answers-review">
                        <h3>Review Your Answers</h3>
                        <?php foreach ($questions as $index => $q): 
                            $userAnswer = $_POST['answers'][$q['id']] ?? '';
                            $isCorrect = $userAnswer === $q['correct_answer'];
                        ?>
                        <div class="review-item <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                            <div class="review-question">
                                <span class="question-number">Question <?php echo $index + 1; ?></span>
                                <span class="result-badge">
                                    <i class="fas <?php echo $isCorrect ? 'fa-check' : 'fa-times'; ?>"></i>
                                </span>
                            </div>
                            <p class="question-text"><?php echo htmlspecialchars($q['question']); ?></p>
                            
                            <div class="review-answers">
                                <p><strong>Your answer:</strong> <?php echo $q['option_'.$userAnswer] ?? 'Not answered'; ?></p>
                                <p><strong>Correct answer:</strong> <?php echo $q['option_'.$q['correct_answer']]; ?></p>
                            </div>
                            
                            <?php if (!empty($q['explanation'])): ?>
                            <div class="explanation">
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($q['explanation']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Quiz Form -->
            <form method="POST" action="" class="quiz-form" id="quizForm">
                <div class="quiz-progress">
                    <div class="progress-text">
                        <span id="current-question">1</span>/<span id="total-questions"><?php echo count($questions); ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: 0%"></div>
                    </div>
                </div>

                <?php foreach ($questions as $index => $q): ?>
                <div class="question-card" data-question="<?php echo $index + 1; ?>" 
                     <?php if ($index > 0) echo 'style="display: none;"'; ?>>
                    <h3>Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></h3>
                    <p class="question"><?php echo htmlspecialchars($q['question']); ?></p>
                    
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" required>
                            <span class="option-letter">A</span>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_a']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B" required>
                            <span class="option-letter">B</span>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_b']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C" required>
                            <span class="option-letter">C</span>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_c']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D" required>
                            <span class="option-letter">D</span>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_d']); ?></span>
                        </label>
                    </div>
                    
                    <div class="question-navigation">
                        <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-outline" onclick="prevQuestion()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($index < count($questions) - 1): ?>
                        <button type="button" class="btn btn-primary" onclick="nextQuestion()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-primary" id="submitQuiz">
                            Submit Quiz <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
        <?php endif; ?>
    </div>

    <style>
    .quiz-nav {
        background: white;
        padding: 15px 0;
        border-bottom: 1px solid #E0E0E0;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    .quiz-nav .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .quiz-title {
        font-weight: 500;
        color: #4B1C3C;
    }
    
    .timer {
        background: #F5F5F5;
        padding: 5px 15px;
        border-radius: 20px;
        color: #4B1C3C;
        font-weight: 500;
    }
    
    .quiz-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .quiz-progress {
        margin-bottom: 30px;
    }
    
    .progress-text {
        text-align: right;
        color: #4B1C3C;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .question-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .question-card h3 {
        color: #4B1C3C;
        margin-bottom: 15px;
        font-size: 1rem;
    }
    
    .question {
        font-size: 1.2rem;
        margin-bottom: 30px;
        padding: 15px;
        background: #F9F9F9;
        border-radius: 5px;
        border-left: 4px solid #FFB800;
    }
    
    .options {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .option {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #F9F9F9;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .option:hover {
        background: #F0F0F0;
        border-color: #FFB800;
    }
    
    .option input[type="radio"] {
        display: none;
    }
    
    .option input[type="radio"]:checked + .option-letter {
        background: #4B1C3C;
        color: white;
    }
    
    .option input[type="radio"]:checked ~ .option-text {
        font-weight: 500;
    }
    
    .option-letter {
        width: 30px;
        height: 30px;
        background: white;
        border: 2px solid #4B1C3C;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 15px;
        color: #4B1C3C;
    }
    
    .option-text {
        flex: 1;
        color: #333;
    }
    
    .question-navigation {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
    
    .quiz-results {
        animation: slideIn 0.5s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .result-card {
        background: white;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .result-card.passed .result-icon i {
        color: #4CAF50;
    }
    
    .result-card.failed .result-icon i {
        color: #f44336;
    }
    
    .result-icon i {
        font-size: 4rem;
        margin-bottom: 20px;
    }
    
    .score-circle {
        width: 150px;
        height: 150px;
        margin: 20px auto;
        border-radius: 50%;
        background: conic-gradient(#4B1C3C 0deg, #E0E0E0 0deg);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .score-number {
        width: 130px;
        height: 130px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: #4B1C3C;
    }
    
    .score-details {
        font-size: 1.2rem;
        color: #666;
        margin: 20px 0;
    }
    
    .success-message {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .fail-message {
        color: #f44336;
        font-weight: 500;
    }
    
    .result-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 30px 0;
    }
    
    .answers-review {
        text-align: left;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px solid #E0E0E0;
    }
    
    .answers-review h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
    }
    
    .review-item {
        padding: 20px;
        background: #F9F9F9;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    
    .review-item.correct {
        border-left: 4px solid #4CAF50;
    }
    
    .review-item.incorrect {
        border-left: 4px solid #f44336;
    }
    
    .review-question {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .question-number {
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .result-badge i {
        font-size: 1.2rem;
    }
    
    .review-answers {
        margin: 10px 0;
        padding: 10px;
        background: white;
        border-radius: 5px;
    }
    
    .review-answers p {
        margin: 5px 0;
        color: #666;
    }
    
    .explanation {
        background: #FFF3E0;
        padding: 10px;
        border-radius: 5px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .explanation i {
        color: #FFB800;
    }
    
    @media (max-width: 768px) {
        .quiz-container {
            padding: 15px;
        }
        
        .question-card {
            padding: 20px;
        }
        
        .result-actions {
            flex-direction: column;
        }
        
        .option {
            padding: 12px;
        }
    }
    </style>

    <script>
    let currentQuestion = 1;
    const totalQuestions = <?php echo count($questions); ?>;
    let timerInterval;
    let seconds = 0;
    
    // Timer functionality
    function startTimer() {
        timerInterval = setInterval(() => {
            seconds++;
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            document.getElementById('timer').innerHTML = 
                `<i class="far fa-clock"></i> ${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    startTimer();
    
    function nextQuestion() {
        // Validate current question
        const currentCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
        const selected = currentCard.querySelector('input[type="radio"]:checked');
        
        if (!selected) {
            alert('Please select an answer before proceeding.');
            return;
        }
        
        // Hide current question
        currentCard.style.display = 'none';
        
        // Show next question
        currentQuestion++;
        const nextCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
        nextCard.style.display = 'block';
        
        // Update progress
        updateProgress();
    }
    
    function prevQuestion() {
        // Hide current question
        const currentCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
        currentCard.style.display = 'none';
        
        // Show previous question
        currentQuestion--;
        const prevCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
        prevCard.style.display = 'block';
        
        // Update progress
        updateProgress();
    }
    
    function updateProgress() {
        // Update question number display
        document.getElementById('current-question').textContent = currentQuestion;
        
        // Update progress bar
        const progress = (currentQuestion / totalQuestions) * 100;
        document.querySelector('.quiz-progress .progress').style.width = progress + '%';
    }
    
    // Confirm submission
    document.getElementById('quizForm')?.addEventListener('submit', function(e) {
        // Check if all questions are answered
        const unanswered = [];
        <?php foreach ($questions as $q): ?>
            if (!document.querySelector(`input[name="answers[<?php echo $q['id']; ?>]"]:checked`)) {
                unanswered.push(<?php echo $q['id']; ?>);
            }
        <?php endforeach; ?>
        
        if (unanswered.length > 0) {
            e.preventDefault();
            alert(`Please answer all questions before submitting. You have ${unanswered.length} unanswered question(s).`);
            return;
        }
        
        if (!confirm('Are you sure you want to submit your quiz?')) {
            e.preventDefault();
        } else {
            clearInterval(timerInterval);
        }
    });
    </script>
</body>
</html>