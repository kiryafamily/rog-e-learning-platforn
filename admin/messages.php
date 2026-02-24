<?php
// admin/messages.php - View contact messages
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Mark message as read
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_GET['read']]);
    header('Location: messages.php');
    exit;
}

// Delete message
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: messages.php');
    exit;
}

// Get all messages
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .message-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #FFB800;
        }
        
        .message-card.unread {
            border-left-color: #f44336;
            background: #FFF3E0;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .message-header h3 {
            color: #4B1C3C;
            margin: 0;
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge.unread {
            background: #f44336;
            color: white;
        }
        
        .badge.read {
            background: #4CAF50;
            color: white;
        }
        
        .message-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .info-item i {
            color: #FFB800;
            width: 20px;
        }
        
        .message-content {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            line-height: 1.6;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .no-messages {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
        }
        
        .no-messages i {
            font-size: 4rem;
            color: #4B1C3C;
            opacity: 0.3;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="../images/logo.jpg" alt="RAYS OF GRACE">
                    <span>Contact Messages</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="index.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="messages-container">
        <h1 style="color: #4B1C3C; margin-bottom: 30px;">
            <i class="fas fa-envelope" style="color: #FFB800;"></i> 
            Contact Messages (<?php echo count($messages); ?>)
        </h1>
        
        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <i class="fas fa-inbox"></i>
                <h3>No Messages Yet</h3>
                <p style="color: #666;">When someone contacts you through the website, messages will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                    <div class="message-header">
                        <h3><?php echo htmlspecialchars($msg['subject']); ?></h3>
                        <span class="badge <?php echo $msg['is_read'] ? 'read' : 'unread'; ?>">
                            <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                        </span>
                    </div>
                    
                    <div class="message-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($msg['name']); ?>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo $msg['email']; ?>"><?php echo $msg['email']; ?></a>
                        </div>
                        <?php if (!empty($msg['phone'])): ?>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo $msg['phone']; ?>"><?php echo $msg['phone']; ?></a>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <?php echo date('F j, Y, g:i a', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    
                    <div class="message-actions">
                        <?php if (!$msg['is_read']): ?>
                            <a href="?read=<?php echo $msg['id']; ?>" class="btn btn-primary btn-small">
                                <i class="fas fa-check"></i> Mark as Read
                            </a>
                        <?php endif; ?>
                        <a href="mailto:<?php echo $msg['email']; ?>?subject=Re: <?php echo urlencode($msg['subject']); ?>" class="btn btn-outline btn-small">
                            <i class="fas fa-reply"></i> Reply
                        </a>
                        <a href="?delete=<?php echo $msg['id']; ?>" class="btn btn-outline btn-small" style="border-color: #f44336; color: #f44336;" onclick="return confirm('Delete this message?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>