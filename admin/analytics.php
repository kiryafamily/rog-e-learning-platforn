<?php
// admin/analytics.php
// Comprehensive analytics dashboard for school administrators

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);

// Date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get revenue data
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as transactions,
        SUM(amount) as revenue
    FROM transactions
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetchAll();

// Get user growth
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_users
    FROM users
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$start_date, $end_date]);
$user_growth = $stmt->fetchAll();

// Get lesson popularity
$stmt = $pdo->query("
    SELECT 
        l.class,
        l.subject,
        l.topic,
        COUNT(p.id) as views,
        COUNT(DISTINCT p.user_id) as unique_students
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id
    WHERE l.status = 'published'
    GROUP BY l.id
    ORDER BY views DESC
    LIMIT 20
");
$popular_lessons = $stmt->fetchAll();

// Get subscription distribution
$stmt = $pdo->query("
    SELECT 
        plan,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
    FROM subscriptions
    GROUP BY plan
");
$subscriptions = $stmt->fetchAll();

// Get class performance
$stmt = $pdo->query("
    SELECT 
        u.class,
        COUNT(DISTINCT u.id) as students,
        COUNT(DISTINCT p.lesson_id) as lessons_started,
        COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.lesson_id END) as lessons_completed,
        AVG(qr.percentage) as avg_quiz_score
    FROM users u
    LEFT JOIN progress p ON u.id = p.user_id
    LEFT JOIN quiz_results qr ON u.id = qr.user_id
    WHERE u.role = 'student'
    GROUP BY u.class
");
$class_performance = $stmt->fetchAll();

// Get device analytics
$stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN ip_address LIKE '%.%' THEN 'Web'
            ELSE 'Mobile'
        END as device_type,
        COUNT(*) as count
    FROM activity_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY device_type
");
$devices = $stmt->fetchAll();

// Calculate totals
$total_revenue = array_sum(array_column($revenue_data, 'revenue'));
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_subs = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND end_date > NOW()")->fetchColumn();
$total_views = $pdo->query("SELECT COUNT(*) FROM progress")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="../images/logo.png" alt="RAYS OF GRACE">
                    <span>Analytics</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="index.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="analytics-container">
        <!-- Header -->
        <div class="analytics-header">
            <h1>School Analytics</h1>
            <div class="date-range">
                <form method="GET" action="">
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(75, 28, 60, 0.1);">
                    <i class="fas fa-users" style="color: #4B1C3C;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo number_format($total_users); ?></span>
                    <span class="metric-label">Total Users</span>
                </div>
                <div class="metric-trend positive">
                    <i class="fas fa-arrow-up"></i> 12% vs last month
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(76, 175, 80, 0.1);">
                    <i class="fas fa-star" style="color: #4CAF50;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo number_format($active_subs); ?></span>
                    <span class="metric-label">Active Subscriptions</span>
                </div>
                <div class="metric-trend positive">
                    <i class="fas fa-arrow-up"></i> 8% this week
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(255, 184, 0, 0.1);">
                    <i class="fas fa-money-bill" style="color: #FFB800;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value">UGX <?php echo number_format($total_revenue); ?></span>
                    <span class="metric-label">Revenue (30 days)</span>
                </div>
                <div class="metric-trend positive">
                    <i class="fas fa-arrow-up"></i> 25% increase
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(33, 150, 243, 0.1);">
                    <i class="fas fa-eye" style="color: #2196F3;"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-value"><?php echo number_format($total_views); ?></span>
                    <span class="metric-label">Total Lesson Views</span>
                </div>
                <div class="metric-trend positive">
                    <i class="fas fa-arrow-up"></i> 1.2k avg daily
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-grid">
            <div class="chart-card full-width">
                <h3><i class="fas fa-chart-line"></i> Revenue Trend</h3>
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-user-plus"></i> User Growth</h3>
                <canvas id="userChart" height="250"></canvas>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Subscription Distribution</h3>
                <canvas id="subscriptionChart" height="250"></canvas>
            </div>
        </div>

        <!-- Class Performance -->
        <div class="performance-section">
            <h3><i class="fas fa-chart-bar"></i> Class Performance</h3>
            <div class="performance-table">
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Students</th>
                            <th>Lessons Started</th>
                            <th>Completion Rate</th>
                            <th>Avg Quiz Score</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_performance as $class): 
                            $completion_rate = $class['lessons_started'] > 0 
                                ? round(($class['lessons_completed'] / $class['lessons_started']) * 100) 
                                : 0;
                            $performance_class = $completion_rate >= 70 ? 'excellent' : ($completion_rate >= 50 ? 'good' : 'needs-work');
                        ?>
                        <tr>
                            <td><strong><?php echo $class['class']; ?></strong></td>
                            <td><?php echo $class['students']; ?></td>
                            <td><?php echo $class['lessons_started']; ?></td>
                            <td><?php echo $completion_rate; ?>%</td>
                            <td><?php echo round($class['avg_quiz_score'] ?? 0); ?>%</td>
                            <td>
                                <div class="performance-bar">
                                    <div class="bar-fill <?php echo $performance_class; ?>" 
                                         style="width: <?php echo $completion_rate; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Popular Lessons -->
        <div class="popular-section">
            <h3><i class="fas fa-fire"></i> Most Popular Lessons</h3>
            <div class="popular-grid">
                <?php foreach ($popular_lessons as $index => $lesson): ?>
                <div class="popular-card">
                    <div class="rank">#<?php echo $index + 1; ?></div>
                    <div class="popular-info">
                        <h4><?php echo htmlspecialchars($lesson['topic']); ?></h4>
                        <p><?php echo $lesson['class']; ?> • <?php echo $lesson['subject']; ?></p>
                        <div class="stats">
                            <span><i class="fas fa-eye"></i> <?php echo number_format($lesson['views']); ?> views</span>
                            <span><i class="fas fa-users"></i> <?php echo $lesson['unique_students']; ?> students</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Device Analytics -->
        <div class="device-section">
            <h3><i class="fas fa-mobile-alt"></i> Device Usage (Last 7 Days)</h3>
            <div class="device-grid">
                <?php 
                $total_devices = array_sum(array_column($devices, 'count'));
                foreach ($devices as $device): 
                    $percentage = round(($device['count'] / $total_devices) * 100);
                ?>
                <div class="device-item">
                    <i class="fas <?php echo $device['device_type'] === 'Web' ? 'fa-laptop' : 'fa-mobile-alt'; ?>"></i>
                    <span class="device-name"><?php echo $device['device_type']; ?></span>
                    <span class="device-percentage"><?php echo $percentage; ?>%</span>
                    <div class="device-bar">
                        <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h3>Export Reports</h3>
            <div class="export-buttons">
                <button class="btn btn-outline" onclick="exportReport('revenue')">
                    <i class="fas fa-file-invoice"></i> Revenue Report
                </button>
                <button class="btn btn-outline" onclick="exportReport('users')">
                    <i class="fas fa-file-alt"></i> User Report
                </button>
                <button class="btn btn-outline" onclick="exportReport('lessons')">
                    <i class="fas fa-file-pdf"></i> Lesson Analytics
                </button>
                <button class="btn btn-outline" onclick="exportReport('full')">
                    <i class="fas fa-file-excel"></i> Full Export (Excel)
                </button>
            </div>
        </div>
    </div>

    <style>
    .analytics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .analytics-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .analytics-header h1 {
        color: #4B1C3C;
    }
    
    .date-range form {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .date-range input {
        padding: 8px 12px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
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
        position: relative;
    }
    
    .metric-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
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
    }
    
    .metric-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .metric-trend {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 3px;
    }
    
    .metric-trend.positive {
        background: #E8F5E9;
        color: #4CAF50;
    }
    
    .metric-trend.negative {
        background: #FFEBEE;
        color: #f44336;
    }
    
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .chart-card.full-width {
        grid-column: 1 / -1;
    }
    
    .chart-card h3 {
        color: #4B1C3C;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .chart-card h3 i {
        color: #FFB800;
    }
    
    .performance-section,
    .popular-section,
    .device-section,
    .export-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .performance-section h3,
    .popular-section h3,
    .device-section h3,
    .export-section h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .performance-section h3 i,
    .popular-section h3 i,
    .device-section h3 i,
    .export-section h3 i {
        color: #FFB800;
    }
    
    .performance-table {
        overflow-x: auto;
    }
    
    .performance-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .performance-table th {
        text-align: left;
        padding: 12px;
        background: #F5F5F5;
        color: #4B1C3C;
    }
    
    .performance-table td {
        padding: 12px;
        border-bottom: 1px solid #F0F0F0;
    }
    
    .performance-bar {
        width: 150px;
        height: 8px;
        background: #F0F0F0;
        border-radius: 4px;
    }
    
    .bar-fill {
        height: 100%;
        border-radius: 4px;
    }
    
    .bar-fill.excellent {
        background: #4CAF50;
    }
    
    .bar-fill.good {
        background: #FFB800;
    }
    
    .bar-fill.needs-work {
        background: #f44336;
    }
    
    .popular-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }
    
    .popular-card {
        background: #F9F9F9;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .rank {
        width: 40px;
        height: 40px;
        background: #4B1C3C;
        color: white;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    
    .popular-info {
        flex: 1;
    }
    
    .popular-info h4 {
        color: #333;
        margin-bottom: 3px;
        font-size: 0.95rem;
    }
    
    .popular-info p {
        color: #666;
        font-size: 0.8rem;
        margin-bottom: 5px;
    }
    
    .stats {
        display: flex;
        gap: 10px;
        font-size: 0.8rem;
        color: #999;
    }
    
    .stats i {
        margin-right: 3px;
        color: #FFB800;
    }
    
    .device-grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .device-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .device-item i {
        width: 30px;
        font-size: 1.2rem;
        color: #4B1C3C;
    }
    
    .device-name {
        width: 100px;
        font-weight: 500;
    }
    
    .device-percentage {
        width: 50px;
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .device-bar {
        flex: 1;
        height: 8px;
        background: #F0F0F0;
        border-radius: 4px;
    }
    
    .export-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 1200px) {
        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .analytics-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .date-range form {
            flex-direction: column;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .popular-grid {
            grid-template-columns: 1fr;
        }
        
        .export-buttons {
            flex-direction: column;
        }
    }
    </style>

    <script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($revenue_data, 'date')); ?>,
            datasets: [{
                label: 'Revenue (UGX)',
                data: <?php echo json_encode(array_column($revenue_data, 'revenue')); ?>,
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
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'UGX ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // User Growth Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($user_growth, 'date')); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode(array_column($user_growth, 'new_users')); ?>,
                backgroundColor: '#4B1C3C',
                borderRadius: 5
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

    // Subscription Chart
    const subCtx = document.getElementById('subscriptionChart').getContext('2d');
    new Chart(subCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($subscriptions, 'plan')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($subscriptions, 'count')); ?>,
                backgroundColor: ['#4B1C3C', '#FFB800', '#4CAF50'],
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

    function exportReport(type) {
        window.location.href = `export-report.php?type=${type}&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>`;
    }
    </script>
</body>
</html>