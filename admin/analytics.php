<?php
// admin/analytics.php - Complete Analytics Dashboard
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);

// Get date range from request (default: last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// ============================================
// USER STATISTICS
// ============================================

// Total users by role
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers,
        SUM(CASE WHEN role = 'parent' THEN 1 ELSE 0 END) as parents,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students
    FROM users
");
$user_stats = $stmt->fetch();

// User growth over time
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_users
    FROM users
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$user_growth = $stmt->fetchAll();

// ============================================
// LESSON STATISTICS
// ============================================

// Lesson stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_lessons,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
        COUNT(DISTINCT class) as classes,
        COUNT(DISTINCT subject) as subjects
    FROM lessons
");
$lesson_stats = $stmt->fetch();

// Most popular lessons
$stmt = $pdo->query("
    SELECT 
        l.id,
        l.topic,
        l.class,
        l.subject,
        COUNT(p.id) as views,
        COUNT(DISTINCT p.user_id) as unique_students
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id
    WHERE l.status = 'published'
    GROUP BY l.id
    ORDER BY views DESC
    LIMIT 10
");
$popular_lessons = $stmt->fetchAll();

// Lessons by class
$stmt = $pdo->query("
    SELECT 
        class,
        COUNT(*) as count
    FROM lessons
    GROUP BY class
    ORDER BY class
");
$lessons_by_class = $stmt->fetchAll();

// ============================================
// SUBSCRIPTION STATISTICS
// ============================================

// Active subscriptions
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN plan = 'monthly' THEN 1 ELSE 0 END) as monthly,
        SUM(CASE WHEN plan = 'termly' THEN 1 ELSE 0 END) as termly,
        SUM(CASE WHEN plan = 'yearly' THEN 1 ELSE 0 END) as yearly
    FROM subscriptions
    WHERE status = 'active' AND end_date > NOW()
");
$sub_stats = $stmt->fetch();

// Subscription revenue
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(amount) as revenue
    FROM transactions
    WHERE status = 'completed' 
    AND created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$revenue_data = $stmt->fetchAll();

// Total revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total'];

// ============================================
// QUIZ STATISTICS
// ============================================

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_attempts,
        COUNT(DISTINCT user_id) as students_took_quizzes,
        AVG(percentage) as avg_score
    FROM quiz_results
");
$quiz_stats = $stmt->fetch();

// ============================================
// SUPPORT TICKETS
// ============================================

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM support_tickets
");
$ticket_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
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
        }

        .nav-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: #FFB800;
            color: #4B1C3C;
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
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
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
        }

        .sidebar-menu i {
            width: 20px;
            color: #FFB800;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            padding: 30px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            color: #4B1C3C;
            font-size: 2rem;
        }

        .page-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        /* Date Range */
        .date-range {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .date-range label {
            color: #4B1C3C;
            font-weight: 500;
        }

        .date-range input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .date-range button {
            background: #4B1C3C;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .date-range button:hover {
            background: #2F1224;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
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
            color: #4B1C3C;
            line-height: 1.2;
        }

        .stat-info p {
            color: #666;
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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

        /* Tables */
        .data-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .data-table h3 {
            color: #4B1C3C;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-table h3 i {
            color: #FFB800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #f8f4f8;
            color: #4B1C3C;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .views-badge {
            background: #4B1C3C;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 1200px) {
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-range {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="admin-topnav">
        <div class="logo-area">
            <img src="../images/logo.png" alt="RAYS OF GRACE">
            <span>Analytics Dashboard</span>
        </div>
        
        <div class="admin-profile">
            <span class="admin-badge">
                <i class="fas fa-shield-alt"></i> <?php echo explode(' ', $user['fullname'])[0]; ?>
            </span>
            <a href="../logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="lessons.php"><i class="fas fa-book"></i> Lessons</a></li>
                <li><a href="upload-lesson.php"><i class="fas fa-upload"></i> Upload Lesson</a></li>
                <li><a href="transactions.php"><i class="fas fa-credit-card"></i> Transactions</a></li>
                <li class="active"><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                
                <!-- Date Range Filter -->
                <form method="GET" class="date-range">
                    <label>From:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    <label>To:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit">Apply</button>
                </form>
            </div>

            <!-- Key Metrics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $user_stats['total']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $lesson_stats['total_lessons']; ?></h3>
                        <p>Total Lessons</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $sub_stats['total'] ?? 0; ?></h3>
                        <p>Active Subscriptions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>UGX <?php echo number_format($total_revenue); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <!-- User Growth Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-user-plus"></i> User Growth</h3>
                    </div>
                    <canvas id="userChart" height="200"></canvas>
                </div>

                <!-- Revenue Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Revenue Trend</h3>
                    </div>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>

            <!-- User Distribution -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Users by Role</h3>
                    </div>
                    <canvas id="userRoleChart" height="200"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Subscription Plans</h3>
                    </div>
                    <canvas id="subscriptionChart" height="200"></canvas>
                </div>
            </div>

            <!-- Popular Lessons Table -->
            <div class="data-table">
                <h3><i class="fas fa-fire"></i> Most Popular Lessons</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Lesson</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Views</th>
                            <th>Unique Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_lessons as $lesson): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lesson['topic']); ?></td>
                            <td><?php echo $lesson['class']; ?></td>
                            <td><?php echo $lesson['subject']; ?></td>
                            <td><span class="views-badge"><?php echo $lesson['views']; ?></span></td>
                            <td><?php echo $lesson['unique_students']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Stats Row -->
            <div class="stats-grid" style="margin-top: 30px;">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo round($quiz_stats['avg_score'] ?? 0); ?>%</h3>
                        <p>Avg Quiz Score</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $ticket_stats['open'] ?? 0; ?></h3>
                        <p>Open Tickets</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $lesson_stats['published']; ?></h3>
                        <p>Published Lessons</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $lesson_stats['drafts']; ?></h3>
                        <p>Draft Lessons</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // User Growth Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?php 
                    $dates = array_column($user_growth, 'date');
                    $labels = array_map(function($date) {
                        return date('M d', strtotime($date));
                    }, $dates);
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($user_growth, 'new_users')); ?>,
                    borderColor: '#4B1C3C',
                    backgroundColor: 'rgba(75, 28, 60, 0.1)',
                    borderWidth: 2,
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

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $dates = array_column($revenue_data, 'date');
                    $labels = array_map(function($date) {
                        return date('M d', strtotime($date));
                    }, $dates);
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'Revenue (UGX)',
                    data: <?php echo json_encode(array_column($revenue_data, 'revenue')); ?>,
                    backgroundColor: '#FFB800',
                    borderRadius: 5
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
                        ticks: {
                            callback: function(value) {
                                return 'UGX ' + value;
                            }
                        }
                    }
                }
            }
        });

        // User Role Chart
        const roleCtx = document.getElementById('userRoleChart').getContext('2d');
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Parents', 'Teachers', 'Admins'],
                datasets: [{
                    data: [
                        <?php echo $user_stats['students']; ?>,
                        <?php echo $user_stats['parents']; ?>,
                        <?php echo $user_stats['teachers']; ?>,
                        <?php echo $user_stats['admins']; ?>
                    ],
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Subscription Chart
        const subCtx = document.getElementById('subscriptionChart').getContext('2d');
        new Chart(subCtx, {
            type: 'doughnut',
            data: {
                labels: ['Monthly', 'Termly', 'Yearly'],
                datasets: [{
                    data: [
                        <?php echo $sub_stats['monthly'] ?? 0; ?>,
                        <?php echo $sub_stats['termly'] ?? 0; ?>,
                        <?php echo $sub_stats['yearly'] ?? 0; ?>
                    ],
                    backgroundColor: ['#FFB800', '#4B1C3C', '#4CAF50'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>