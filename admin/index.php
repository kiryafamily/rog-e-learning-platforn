<?php
// admin/index.php - STUNNING REDESIGN
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active' AND end_date > NOW()");
$stats['active_subscriptions'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM lessons WHERE status = 'published'");
$stats['total_lessons'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW())");
$stats['revenue_month'] = $stmt->fetch()['total'];

// Get recent users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Get recent lessons
$stmt = $pdo->query("SELECT * FROM lessons ORDER BY created_at DESC LIMIT 5");
$recent_lessons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f4f4f9;
        }
        
        /* Top Navigation */
        .admin-topnav {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 15px rgba(75,28,60,0.3);
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
            background: white;
            border-radius: 8px;
            padding: 5px;
        }
        
        .logo-area span {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }
        
        .logo-area small {
            display: block;
            font-size: 0.7rem;
            color: #FFB800;
            letter-spacing: 1px;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-badge {
            background: #FFB800;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-badge i {
            font-size: 1rem;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .nav-btn:hover {
            background: #FFB800;
            color: #4B1C3C;
            border-color: #FFB800;
        }
        
        /* Main Container */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sidebar-header h3 {
            color: #4B1C3C;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            color: #FFB800;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 25px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: #f8f4f8;
            color: #4B1C3C;
            border-left-color: #FFB800;
        }
        
        .sidebar-menu li.active a {
            background: linear-gradient(90deg, rgba(75,28,60,0.1) 0%, rgba(255,184,0,0.05) 100%);
            color: #4B1C3C;
            border-left-color: #FFB800;
            font-weight: 500;
        }
        
        .sidebar-menu i {
            width: 20px;
            color: #FFB800;
        }
        
        /* Main Content */
        .admin-content {
            flex: 1;
            padding: 30px;
            background: #f4f4f9;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(75,28,60,0.2);
        }
        
        .welcome-text h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .welcome-text p {
            color: #FFB800;
            font-size: 1.1rem;
        }
        
        .date-box {
            background: rgba(255,255,255,0.15);
            padding: 12px 25px;
            border-radius: 50px;
            border: 1px solid #FFB800;
        }
        
        .date-box i {
            color: #FFB800;
            margin-right: 8px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(75,28,60,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4B1C3C, #FFB800);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stat-icon i {
            font-size: 1.8rem;
            color: #FFB800;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4B1C3C;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .stat-trend {
            font-size: 0.85rem;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-trend i {
            font-size: 0.8rem;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .section-title i {
            font-size: 1.5rem;
            color: #FFB800;
        }
        
        .section-title h2 {
            color: #4B1C3C;
            font-size: 1.3rem;
            margin: 0;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }
        
        .action-btn {
            background: #f8f4f8;
            padding: 20px 15px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #4B1C3C;
            transform: translateY(-3px);
        }
        
        .action-btn:hover i,
        .action-btn:hover span {
            color: white;
        }
        
        .action-btn i {
            font-size: 2rem;
            color: #FFB800;
            margin-bottom: 10px;
        }
        
        .action-btn span {
            display: block;
            color: #4B1C3C;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Recent Activity Grid */
        .activity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-header h3 {
            color: #4B1C3C;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header h3 i {
            color: #FFB800;
        }
        
        .view-link {
            color: #4B1C3C;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-list, .lesson-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .user-item, .lesson-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f4f8;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .user-item:hover, .lesson-item:hover {
            background: #f0e8f0;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-info h4 {
            color: #4B1C3C;
            margin-bottom: 3px;
            font-size: 1rem;
        }
        
        .user-info p {
            color: #666;
            font-size: 0.85rem;
        }
        
        .user-date {
            color: #999;
            font-size: 0.8rem;
        }
        
        .lesson-info {
            flex: 1;
        }
        
        .lesson-info h4 {
            color: #4B1C3C;
            margin-bottom: 3px;
            font-size: 1rem;
        }
        
        .lesson-info p {
            color: #666;
            font-size: 0.85rem;
        }
        
        .lesson-class {
            background: #FFB800;
            color: #4B1C3C;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* System Health */
        .health-grid {
            background: white;
            padding: 25px;
            border-radius: 15px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .health-item {
            padding: 15px;
            background: #f8f4f8;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .health-item.ok i {
            color: #4CAF50;
        }
        
        .health-item.warning i {
            color: #f44336;
        }
        
        .health-item i {
            font-size: 1.5rem;
        }
        
        .health-info {
            flex: 1;
        }
        
        .health-info strong {
            display: block;
            color: #4B1C3C;
            font-size: 0.9rem;
        }
        
        .health-info span {
            font-size: 0.8rem;
            color: #666;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .activity-grid {
                grid-template-columns: 1fr;
            }
            
            .health-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .health-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="admin-topnav">
        <div class="logo-area">
            <img src="../images/logo.png" alt="RAYS OF GRACE">
            <div>
                <span>RAYS OF GRACE</span>
                <small>Administrator Panel</small>
            </div>
        </div>
        
        <div class="admin-profile">
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i>
                <?php echo explode(' ', $user['fullname'])[0]; ?>
            </div>
            <div class="nav-buttons">
                <a href="../dashboard.php" class="nav-btn">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="../logout.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h3>Menu</h3>
                <p>Control Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="lessons.php">
                        <i class="fas fa-book"></i>
                        <span>Lessons</span>
                    </a>
                </li>
                <li>
                    <a href="upload-lesson.php">
                        <i class="fas fa-upload"></i>
                        <span>Upload Lesson</span>
                    </a>
                </li>
                <li>
                    <a href="transactions.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                <li>
                    <a href="analytics.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="backup.php">
                        <i class="fas fa-database"></i>
                        <span>Backup</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo explode(' ', $user['fullname'])[0]; ?>!</h1>
                    <p><i class="fas fa-magic" style="color: #FFB800;"></i> Everything is running smoothly</p>
                </div>
                <div class="date-box">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> +12% this month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_subscriptions']; ?></div>
                    <div class="stat-label">Active Subscriptions</div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> +8% this week
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_lessons']; ?></div>
                    <div class="stat-label">Total Lessons</div>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i> Updated recently
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">UGX <?php echo number_format($stats['revenue_month']); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i> +25% vs last month
                    </div>
                </div>
            </div>

   <!-- Quick Actions -->
<div class="quick-actions">
    <div class="section-title">
        <i class="fas fa-bolt"></i>
        <h2>Quick Actions</h2>
    </div>
    
    <div class="action-grid">
        <a href="upload-lesson.php" class="action-btn">
            <i class="fas fa-upload"></i>
            <span>Upload Lesson</span>
        </a>
        <a href="users.php?action=new" class="action-btn">
            <i class="fas fa-user-plus"></i>
            <span>Add User</span>
        </a>
        <a href="backup.php" class="action-btn">
            <i class="fas fa-database"></i>
            <span>Backup Now</span>
        </a>
        <a href="analytics.php" class="action-btn">
            <i class="fas fa-chart-pie"></i>
            <span>Analytics</span>
        </a>
        <a href="settings.php" class="action-btn">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="messages.php" class="action-btn">
            <i class="fas fa-envelope"></i>
            <span>View Messages</span>
        </a>
    </div>
</div>
            <!-- Recent Activity -->
            <div class="activity-grid">
                <!-- Recent Users -->
                <div class="activity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                        <a href="users.php" class="view-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="user-list">
                        <?php foreach ($recent_users as $recent): ?>
                        <div class="user-item">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($recent['fullname'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($recent['fullname']); ?></h4>
                                <p><?php echo $recent['email']; ?></p>
                            </div>
                            <div class="user-date">
                                <?php echo timeAgo($recent['created_at']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Lessons -->
                <div class="activity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Recent Lessons</h3>
                        <a href="lessons.php" class="view-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="lesson-list">
                        <?php foreach ($recent_lessons as $lesson): ?>
                        <div class="lesson-item">
                            <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-play-circle" style="color: #FFB800; font-size: 1.5rem;"></i>
                            </div>
                            <div class="lesson-info">
                                <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                                <p><?php echo $lesson['class']; ?> - <?php echo $lesson['subject']; ?></p>
                            </div>
                            <div class="lesson-class">
                                Week <?php echo $lesson['week']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="health-grid">
                <div class="health-item ok">
                    <i class="fas fa-database"></i>
                    <div class="health-info">
                        <strong>Database</strong>
                        <span>Connected</span>
                    </div>
                </div>
                <div class="health-item ok">
                    <i class="fas fa-hdd"></i>
                    <div class="health-info">
                        <strong>Storage</strong>
                        <span>45% used</span>
                    </div>
                </div>
                <div class="health-item warning">
                    <i class="fas fa-shield-alt"></i>
                    <div class="health-info">
                        <strong>Backup</strong>
                        <span>3 days ago</span>
                    </div>
                </div>
                <div class="health-item ok">
                    <i class="fas fa-lock"></i>
                    <div class="health-info">
                        <strong>SSL</strong>
                        <span>Valid</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add any JavaScript for interactivity here
        console.log('Admin panel loaded');
    </script>
</body>
</html>