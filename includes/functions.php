<?php
// includes/functions.php
// Core functions for RAYS OF GRACE Junior School

require_once 'config.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Ugandan format)
 */
function validatePhone($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Ugandan number (starts with 256 or 0)
    if (preg_match('/^(0|256)[0-9]{9}$/', $phone)) {
        return true;
    }
    return false;
}

/**
 * Format phone number to international format
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '256' . substr($phone, 1);
    }
    return $phone;
}

/**
 * Get all classes
 */
function getClasses() {
    return [
        'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'
    ];
}

/**
 * Get subjects by class
 */
function getSubjects($class = null) {
    $subjects = [
        'lower' => [
            'Literacy 1A',
            'Literacy 1B',
            'Mathematics',
            'Reading',
            'Writing',
            'English Language',
            'Religious Education'
        ],
        'upper' => [
            'Kiswahili',
            'English Language',
            'Religious Education',
            'Mathematics',
            'Integrated Science',
            'Social Studies'
        ]
    ];
    
    if ($class) {
        if (in_array($class, ['P1', 'P2', 'P3'])) {
            return $subjects['lower'];
        } else {
            return $subjects['upper'];
        }
    }
    
    return $subjects;
}

/**
 * Get lessons for a specific class and subject
 */
function getLessons($pdo, $class, $subject = null, $limit = null) {
    $sql = "SELECT * FROM lessons WHERE class = :class";
    $params = ['class' => $class];
    
    if ($subject) {
        $sql .= " AND subject = :subject";
        $params['subject'] = $subject;
    }
    
    $sql .= " ORDER BY week, topic";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $params['limit'] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        if ($key === 'limit') {
            $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":$key", $value);
        }
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get user's subscription status
 */
function getUserSubscription($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        AND status = 'active' 
        AND end_date > NOW()
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Check if user has access to content
 */
function hasAccess($pdo, $userId) {
    $subscription = getUserSubscription($pdo, $userId);
    return $subscription ? true : false;
}

/**
 * Log user activity
 */
function logActivity($pdo, $userId, $action, $details = null) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

/**
 * Generate unique transaction ID
 */
function generateTransactionId() {
    return 'TXN' . time() . rand(1000, 9999);
}

/**
 * Send SMS notification (for payment confirmations)
 */
function sendSMS($phone, $message) {
    // Integrate with your SMS provider here
    // This is a placeholder - you'll need to add your SMS API
    $phone = formatPhone($phone);
    
    // Example with Africa's Talking or other provider
    // $sms = new AfricasTalking\SDK\SMS();
    // $sms->send($phone, $message);
    
    return true;
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message) {
    // Integrate with your email service here
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SITE_NAME . " <noreply@raysofgrace.ac.ug>" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Calculate progress for a student
 */
function calculateProgress($pdo, $userId, $class) {
    // Get total lessons for the class
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lessons WHERE class = ?");
    $stmt->execute([$class]);
    $total = $stmt->fetch()['total'];
    
    // Get completed lessons
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as completed 
        FROM progress 
        WHERE user_id = ? 
        AND completed = 1
    ");
    $stmt->execute([$userId]);
    $completed = $stmt->fetch()['completed'];
    
    if ($total == 0) return 0;
    
    return round(($completed / $total) * 100);
}

/**
 * Get quiz questions
 */
function getQuizQuestions($pdo, $lessonId, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT * FROM quiz_questions 
        WHERE lesson_id = ? 
        ORDER BY RAND() 
        LIMIT ?
    ");
    $stmt->execute([$lessonId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Grade quiz and save results
 */
function gradeQuiz($pdo, $userId, $lessonId, $answers) {
    $score = 0;
    $total = count($answers);
    
    foreach ($answers as $questionId => $userAnswer) {
        $stmt = $pdo->prepare("
            SELECT correct_answer FROM quiz_questions 
            WHERE id = ?
        ");
        $stmt->execute([$questionId]);
        $correct = $stmt->fetch()['correct_answer'];
        
        if ($userAnswer == $correct) {
            $score++;
        }
    }
    
    $percentage = ($score / $total) * 100;
    
    // Save quiz result
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (user_id, lesson_id, score, total, percentage, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $lessonId, $score, $total, $percentage]);
    
    return [
        'score' => $score,
        'total' => $total,
        'percentage' => $percentage,
        'passed' => $percentage >= 50
    ];
}

/**
 * Get family members (for discount)
 */
function getFamilyMembers($pdo, $familyId) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE family_id = ? 
        AND id != ? 
        AND status = 'active'
    ");
    $stmt->execute([$familyId, $_SESSION['user_id']]);
    return $stmt->fetchAll();
}

/**
 * Create backup of database
 */
function backupDatabase() {
    $backupDir = __DIR__ . '/../backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    
    $filename = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    $command = sprintf(
        'mysqldump -u %s -p%s %s > %s',
        DB_USER,
        DB_PASS,
        DB_NAME,
        $filename
    );
    
    system($command);
    
    return $filename;
}
?>