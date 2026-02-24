<?php
// lesson-view.php - FIXED VERSION
// Individual lesson viewer with video and PDF download

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
$lessonId = $_GET['id'] ?? 0;

// Get lesson details
$stmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT COUNT(*) FROM quiz_questions WHERE lesson_id = l.id) as quiz_count
    FROM lessons l 
    WHERE l.id = ?
");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: lessons.php');
    exit;
}

// Check if user has access to this specific lesson
$canView = $hasAccess || $lesson['is_free'];

// Track progress
if ($canView) {
    // Check if progress exists
    $stmt = $pdo->prepare("
        SELECT * FROM progress 
        WHERE user_id = ? AND lesson_id = ?
    ");
    $stmt->execute([$user['id'], $lessonId]);
    $progress = $stmt->fetch();
    
    if (!$progress) {
        // Create new progress record
        $stmt = $pdo->prepare("
            INSERT INTO progress (user_id, lesson_id, status, last_accessed) 
            VALUES (?, ?, 'in_progress', NOW())
        ");
        $stmt->execute([$user['id'], $lessonId]);
    } else {
        // Update last accessed
        $stmt = $pdo->prepare("
            UPDATE progress 
            SET last_accessed = NOW() 
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$user['id'], $lessonId]);
    }
}

// Get next and previous lessons
$stmt = $pdo->prepare("
    SELECT id, topic FROM lessons 
    WHERE class = ? AND subject = ? AND week = ? AND display_order < ?
    ORDER BY display_order DESC LIMIT 1
");
$stmt->execute([$lesson['class'], $lesson['subject'], $lesson['week'], $lesson['display_order']]);
$prevLesson = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT id, topic FROM lessons 
    WHERE class = ? AND subject = ? AND week = ? AND display_order > ?
    ORDER BY display_order ASC LIMIT 1
");
$stmt->execute([$lesson['class'], $lesson['subject'], $lesson['week'], $lesson['display_order']]);
$nextLesson = $stmt->fetch();

// Log activity
logActivity($pdo, $user['id'], 'view_lesson', "Viewed lesson: {$lesson['topic']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['topic']); ?> - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="lesson-nav">
        <div class="container">
            <div class="nav-left">
                <a href="lessons.php?class=<?php echo $lesson['class']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Lessons
                </a>
                <span class="lesson-location">
                    <?php echo $lesson['class']; ?> | <?php echo $lesson['subject']; ?> | Week <?php echo $lesson['week']; ?>
                </span>
            </div>
            <div class="nav-right">
                <a href="dashboard.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <?php if (!$canView): ?>
        <!-- Paywall for locked content -->
        <div class="paywall-overlay">
            <div class="paywall-box">
                <i class="fas fa-lock fa-3x" style="color: #FFB800;"></i>
                <h2>This Lesson is Locked</h2>
                <p>Subscribe to access this and all other lessons</p>
                <div class="price-tag">
                    <span class="currency">UGX</span>
                    <span class="amount">100,000</span>
                    <span class="period">/month</span>
                </div>
                <a href="pricing.php" class="btn btn-primary btn-large">View Subscription Plans</a>
                <a href="lessons.php" class="btn btn-outline">Browse Free Lessons</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Lesson Content -->
        <div class="lesson-container">
            <div class="lesson-header">
                <h1><?php echo htmlspecialchars($lesson['topic']); ?></h1>
                <div class="lesson-meta">
                    <span class="badge class"><?php echo $lesson['class']; ?></span>
                    <span class="badge subject"><?php echo $lesson['subject']; ?></span>
                    <span class="badge week">Week <?php echo $lesson['week']; ?></span>
                </div>
            </div>

            <div class="lesson-content-grid">
                <!-- Main Content Area -->
                <div class="lesson-main">
                    <!-- Video Player - FIXED VERSION -->
                    <div class="video-player">
                        <?php 
                        // Check if video exists in database
                        if (!empty($lesson['video_url'])): ?>
                            <video controls class="lesson-video" poster="<?php echo $lesson['thumbnail'] ?? ''; ?>">
                                <source src="<?php echo $lesson['video_url']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif (!empty($lesson['video_path'])): 
                            // CORRECTED PATH - going from root directory
                            $video_path = 'uploads/videos/' . $lesson['video_path'];
                        ?>
                            <video controls class="lesson-video" poster="<?php echo $lesson['thumbnail'] ?? ''; ?>" style="width: 100%; background: #000;">
                                <source src="<?php echo $video_path; ?>" type="video/mp4">
                                <source src="<?php echo $video_path; ?>" type="video/webm">
                                <source src="<?php echo $video_path; ?>" type="video/ogg">
                                Your browser does not support the video tag.
                            </video>
                            <!-- Download option -->
                            <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-info-circle" style="color: #4B1C3C;"></i> Video available for download</span>
                                <a href="<?php echo $video_path; ?>" download class="btn btn-outline btn-small">
                                    <i class="fas fa-download"></i> Download Video
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Beautiful placeholder with Font Awesome icons -->
                            <div class="video-placeholder" style="background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); aspect-ratio: 16/9; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; border-radius: 10px;">
                                <i class="fas fa-video fa-4x" style="color: #FFB800; margin-bottom: 20px;"></i>
                                <h3 style="color: white; font-size: 24px; margin-bottom: 10px;">Video Lesson Coming Soon</h3>
                                <p style="color: rgba(255,255,255,0.8);">The teacher is preparing this video lesson.</p>
                                <div style="margin-top: 20px;">
                                    <i class="fas fa-clock" style="color: #FFB800;"></i>
                                    <span style="color: #FFB800;"> Check back later</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Lesson Content Tabs -->
                    <div class="lesson-tabs">
                        <div class="tab-buttons">
                            <button class="tab-btn active" onclick="switchTab('notes')">
                                <i class="fas fa-book-open"></i> Lesson Notes
                            </button>
                            <button class="tab-btn" onclick="switchTab('transcript')">
                                <i class="fas fa-align-left"></i> Transcript
                            </button>
                            <?php if ($lesson['quiz_count'] > 0): ?>
                            <button class="tab-btn" onclick="switchTab('quiz')">
                                <i class="fas fa-question-circle"></i> Quiz (<?php echo $lesson['quiz_count']; ?>)
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="tab-content">
                            <!-- Notes Tab -->
                            <div id="notes-tab" class="tab-pane active">
                                <div class="lesson-notes">
                                    <?php if (!empty($lesson['description'])): ?>
                                        <h3><i class="fas fa-info-circle" style="color: #FFB800; margin-right: 10px;"></i>Lesson Overview</h3>
                                        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; border-left: 4px solid #FFB800;">
                                            <p><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Key Points -->
                                    <h3><i class="fas fa-star" style="color: #FFB800; margin-right: 10px;"></i>Key Learning Points</h3>
                                    <ul class="key-points" style="list-style: none; padding: 0;">
                                        <li style="margin: 10px 0; display: flex; align-items: center; gap: 10px;">
                                            <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 20px;"></i>
                                            <span>Understanding the main concepts</span>
                                        </li>
                                        <li style="margin: 10px 0; display: flex; align-items: center; gap: 10px;">
                                            <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 20px;"></i>
                                            <span>Practical applications</span>
                                        </li>
                                        <li style="margin: 10px 0; display: flex; align-items: center; gap: 10px;">
                                            <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 20px;"></i>
                                            <span>Common examples and exercises</span>
                                        </li>
                                    </ul>
                                    
                                    <!-- Download Materials -->
                                    <div class="download-materials" style="background: #f0f0f0; padding: 20px; border-radius: 10px; margin-top: 20px;">
                                        <h3><i class="fas fa-download" style="color: #FFB800; margin-right: 10px;"></i>Lesson Materials</h3>
                                        <div class="download-buttons" style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                                            <?php if (!empty($lesson['pdf_url']) || !empty($lesson['pdf_path'])): ?>
                                            <a href="<?php echo $lesson['pdf_url'] ?? 'uploads/pdfs/'.$lesson['pdf_path']; ?>" 
                                               class="btn btn-primary" download style="display: inline-flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-file-pdf"></i> Download Workbook (PDF)
                                            </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline" onclick="saveForOffline()" style="display: inline-flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-cloud-download-alt"></i> Save for Offline
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transcript Tab -->
                            <div id="transcript-tab" class="tab-pane">
                                <div class="lesson-transcript" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-closed-captioning fa-3x" style="color: #FFB800; margin-bottom: 15px;"></i>
                                    <h3 style="color: #4B1C3C;">Video Transcript</h3>
                                    <p style="color: #666;">The transcript for this lesson will be available soon.</p>
                                </div>
                            </div>

                            <!-- Quiz Tab -->
                            <?php if ($lesson['quiz_count'] > 0): ?>
                            <div id="quiz-tab" class="tab-pane">
                                <div class="quiz-preview" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-question-circle fa-3x" style="color: #FFB800; margin-bottom: 15px;"></i>
                                    <h3 style="color: #4B1C3C;">Test Your Understanding</h3>
                                    <p style="color: #666; margin-bottom: 20px;">This lesson has <strong><?php echo $lesson['quiz_count']; ?></strong> quiz questions.</p>
                                    
                                    <!-- Previous Quiz Results -->
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT * FROM quiz_results 
                                        WHERE user_id = ? AND lesson_id = ? 
                                        ORDER BY created_at DESC LIMIT 1
                                    ");
                                    $stmt->execute([$user['id'], $lessonId]);
                                    $lastResult = $stmt->fetch();
                                    ?>
                                    
                                    <?php if ($lastResult): ?>
                                    <div class="previous-result" style="background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0;">
                                        <h4 style="color: #4B1C3C; margin-bottom: 10px;">Your Last Score</h4>
                                        <div class="score-display">
                                            <span class="score" style="font-size: 36px; font-weight: 700; color: <?php echo $lastResult['percentage'] >= 50 ? '#4CAF50' : '#f44336'; ?>;">
                                                <?php echo $lastResult['percentage']; ?>%
                                            </span>
                                            <span class="details" style="color: #666;">(<?php echo $lastResult['score']; ?>/<?php echo $lastResult['total']; ?>)</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <a href="quiz.php?lesson=<?php echo $lessonId; ?>" class="btn btn-primary btn-large" style="display: inline-flex; align-items: center; gap: 10px; padding: 15px 30px;">
                                        <i class="fas fa-play"></i> Start Quiz
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lesson-sidebar">
                    <!-- Progress Tracker -->
                    <div class="progress-card">
                        <h3><i class="fas fa-chart-line" style="color: #FFB800; margin-right: 5px;"></i> Your Progress</h3>
                        <div class="progress-circle" style="width: 120px; height: 120px; margin: 15px auto; position: relative;">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#E0E0E0" stroke-width="12"/>
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#4B1C3C" stroke-width="12" 
                                        stroke-dasharray="339.3" 
                                        stroke-dashoffset="<?php echo 339.3 - (339.3 * ($progress['progress'] ?? 0) / 100); ?>" 
                                        stroke-linecap="round" transform="rotate(-90 60 60)"/>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <span style="font-size: 24px; font-weight: 700; color: #4B1C3C;"><?php echo $progress['progress'] ?? 0; ?>%</span>
                            </div>
                        </div>
                        <button class="btn btn-outline btn-block" onclick="markComplete()" style="width: 100%;">
                            <i class="fas fa-check"></i> Mark as Complete
                        </button>
                    </div>

                    <!-- Lesson Navigation -->
                    <div class="lesson-navigation">
                        <h3><i class="fas fa-arrows-alt-h" style="color: #FFB800; margin-right: 5px;"></i> Lesson Navigation</h3>
                        <div class="nav-buttons">
                            <?php if ($prevLesson): ?>
                            <a href="lesson-view.php?id=<?php echo $prevLesson['id']; ?>" class="nav-btn prev" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f5f5f5; border-radius: 5px; text-decoration: none; color: #333;">
                                <i class="fas fa-arrow-left" style="color: #FFB800;"></i>
                                <div style="flex: 1;">
                                    <small style="display: block; color: #666;">Previous</small>
                                    <strong><?php echo htmlspecialchars(substr($prevLesson['topic'], 0, 30)) . '...'; ?></strong>
                                </div>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($nextLesson): ?>
                            <a href="lesson-view.php?id=<?php echo $nextLesson['id']; ?>" class="nav-btn next" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f5f5f5; border-radius: 5px; text-decoration: none; color: #333; margin-top: 10px;">
                                <div style="flex: 1;">
                                    <small style="display: block; color: #666;">Next</small>
                                    <strong><?php echo htmlspecialchars(substr($nextLesson['topic'], 0, 30)) . '...'; ?></strong>
                                </div>
                                <i class="fas fa-arrow-right" style="color: #FFB800;"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Related Resources -->
                    <div class="related-resources">
                        <h3><i class="fas fa-link" style="color: #FFB800; margin-right: 5px;"></i> Related Resources</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin: 10px 0;">
                                <a href="#" style="display: flex; align-items: center; gap: 10px; color: #666; text-decoration: none;">
                                    <i class="fas fa-file-pdf" style="color: #f44336; width: 20px;"></i>
                                    <span>Practice Worksheet</span>
                                </a>
                            </li>
                            <li style="margin: 10px 0;">
                                <a href="#" style="display: flex; align-items: center; gap: 10px; color: #666; text-decoration: none;">
                                    <i class="fas fa-video" style="color: #2196F3; width: 20px;"></i>
                                    <span>Additional Video</span>
                                </a>
                            </li>
                            <li style="margin: 10px 0;">
                                <a href="#" style="display: flex; align-items: center; gap: 10px; color: #666; text-decoration: none;">
                                    <i class="fas fa-link" style="color: #4CAF50; width: 20px;"></i>
                                    <span>External Reading</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Need Help? -->
                    <div class="help-card" style="background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                        <i class="fas fa-question-circle fa-2x" style="color: #FFB800; margin-bottom: 10px;"></i>
                        <h4 style="color: white; margin-bottom: 5px;">Need Help?</h4>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 15px;">Contact your teacher or view FAQs</p>
                        <a href="help.php" style="color: #FFB800; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            Get Support <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <style>
    /* Keep your existing styles - they're good */
    .lesson-nav {
        background: white;
        padding: 15px 0;
        border-bottom: 1px solid #E0E0E0;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    .lesson-nav .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .nav-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .back-btn {
        color: #4B1C3C;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 500;
    }
    
    .back-btn:hover {
        color: #FFB800;
    }
    
    .lesson-location {
        color: #666;
        font-size: 0.9rem;
    }
    
    .lesson-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .lesson-header {
        margin-bottom: 30px;
    }
    
    .lesson-header h1 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .lesson-meta {
        display: flex;
        gap: 10px;
    }
    
    .badge {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .badge.class {
        background: #4B1C3C;
        color: white;
    }
    
    .badge.subject {
        background: #FFB800;
        color: #4B1C3C;
    }
    
    .badge.week {
        background: #E0E0E0;
        color: #666;
    }
    
    .lesson-content-grid {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 30px;
    }
    
    .video-player {
        background: #000;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .lesson-video {
        width: 100%;
        aspect-ratio: 16/9;
        display: block;
    }
    
    .lesson-tabs {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .tab-buttons {
        display: flex;
        border-bottom: 1px solid #E0E0E0;
        background: #F9F9F9;
    }
    
    .tab-btn {
        padding: 15px 25px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tab-btn i {
        color: #FFB800;
    }
    
    .tab-btn:hover {
        background: white;
        color: #4B1C3C;
    }
    
    .tab-btn.active {
        background: white;
        color: #4B1C3C;
        border-bottom: 2px solid #4B1C3C;
    }
    
    .tab-content {
        padding: 25px;
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    @media (max-width: 768px) {
        .lesson-content-grid {
            grid-template-columns: 1fr;
        }
        
        .tab-buttons {
            flex-wrap: wrap;
        }
        
        .tab-btn {
            flex: 1;
            padding: 10px;
            font-size: 0.9rem;
        }
    }
    </style>

    <script>
    function switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        document.getElementById(tab + '-tab').classList.add('active');
    }
    
    function markComplete() {
        fetch('api/update-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                lesson_id: <?php echo $lessonId; ?>,
                progress: 100,
                status: 'completed'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Lesson marked as complete!');
                location.reload();
            }
        })
        .catch(error => {
            alert('⚠️ Could not update progress. Please try again.');
        });
    }
    
    function saveForOffline() {
        if ('serviceWorker' in navigator && 'caches' in window) {
            alert('📥 Saving lesson for offline access...');
            
            caches.open('lessons-v1').then(cache => {
                cache.addAll([
                    'lesson-view.php?id=<?php echo $lessonId; ?>',
                    '<?php echo !empty($lesson['video_path']) ? 'uploads/videos/'.$lesson['video_path'] : ''; ?>',
                    '<?php echo !empty($lesson['pdf_path']) ? 'uploads/pdfs/'.$lesson['pdf_path'] : ''; ?>'
                ].filter(url => url));
            }).then(() => {
                alert('✅ Lesson saved! You can now access it offline.');
            }).catch(() => {
                alert('❌ Failed to save offline. Check your connection.');
            });
        } else {
            alert('Your browser does not support offline access. Try using Chrome or Firefox.');
        }
    }
    </script>
</body>
</html>