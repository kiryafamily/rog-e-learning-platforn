<?php
// admin/transactions.php - Transaction Management
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Handle refund
if (isset($_POST['refund_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'refunded' WHERE id = ?");
    if ($stmt->execute([$transaction_id])) {
        $success = 'Transaction refunded successfully';
        logActivity($pdo, $user['id'], 'admin_refund', "Refunded transaction ID: $transaction_id");
    } else {
        $error = 'Failed to refund transaction';
    }
}

// Handle delete
if (isset($_POST['delete_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    if ($stmt->execute([$transaction_id])) {
        $success = 'Transaction deleted successfully';
        logActivity($pdo, $user['id'], 'admin_delete_transaction', "Deleted transaction ID: $transaction_id");
    } else {
        $error = 'Failed to delete transaction';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$provider_filter = $_GET['provider'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT t.*, u.fullname, u.email 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($provider_filter !== 'all') {
    $sql .= " AND t.provider = ?";
    $params[] = $provider_filter;
}

if (!empty($date_from) && !empty($date_to)) {
    $sql .= " AND DATE(t.created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
}

if (!empty($search)) {
    $sql .= " AND (t.transaction_id LIKE ? OR u.fullname LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals
$total_amount = 0;
$total_completed = 0;
$total_pending = 0;
$total_failed = 0;
$total_refunded = 0;

foreach ($transactions as $t) {
    $total_amount += $t['amount'];
    switch ($t['status']) {
        case 'completed':
            $total_completed += $t['amount'];
            break;
        case 'pending':
            $total_pending += $t['amount'];
            break;
        case 'failed':
            $total_failed += $t['amount'];
            break;
        case 'refunded':
            $total_refunded += $t['amount'];
            break;
    }
}

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded_count,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
    FROM transactions
");
$stats = $stmt->fetch();

// Get monthly revenue for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount) as revenue
    FROM transactions
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$monthly_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - RAYS OF GRACE</title>
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

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
            color: #C62828;
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
            font-size: 1.5rem;
            color: #4B1C3C;
            line-height: 1.2;
        }

        .stat-info p {
            color: #666;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
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

        /* Filters */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            color: #4B1C3C;
            font-weight: 500;
        }

        .filter-select {
            padding: 8px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            min-width: 150px;
        }

        .filter-input {
            padding: 8px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .search-box {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 5px 15px;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            padding: 8px 0;
        }

        .search-box button {
            background: none;
            border: none;
            color: #FFB800;
            cursor: pointer;
        }

        .btn-filter {
            background: #4B1C3C;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-filter:hover {
            background: #2F1224;
        }

        /* Transactions Table */
        .transactions-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #4B1C3C;
            color: white;
            padding: 15px;
            text-align: left;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f4f8;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .status-pending {
            background: #FFF3E0;
            color: #FF9800;
        }

        .status-failed {
            background: #FFEBEE;
            color: #f44336;
        }

        .status-refunded {
            background: #E0E0E0;
            color: #666;
        }

        .provider-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .provider-mtn {
            background: #ffcc00;
            color: #000;
        }

        .provider-airtel {
            background: #ed1c24;
            color: white;
        }

        .provider-card {
            background: #1a1f71;
            color: white;
        }

        .amount {
            font-weight: 600;
            color: #4B1C3C;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            transition: transform 0.2s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .btn-view { background: #2196F3; }
        .btn-refund { background: #FF9800; }
        .btn-delete { background: #f44336; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            overflow: hidden;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: #4B1C3C;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: white;
        }

        .close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            width: 120px;
            font-weight: 600;
            color: #4B1C3C;
        }

        .detail-value {
            flex: 1;
            color: #666;
        }

        .detail-value.warning {
            color: #FF9800;
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .filter-input {
                width: 100%;
            }
            
            .transactions-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="admin-topnav">
        <div class="logo-area">
            <img src="../images/logo.png" alt="RAYS OF GRACE">
            <span>Transaction Management</span>
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
                <li class="active"><a href="transactions.php"><i class="fas fa-credit-card"></i> Transactions</a></li>
                <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> Transaction Management</h1>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_transactions'] ?? 0; ?></h3>
                        <p>Total Transactions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_count'] ?? 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>UGX <?php echo number_format($stats['completed_amount'] ?? 0); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Monthly Revenue</h3>
                </div>
                <canvas id="revenueChart" height="100"></canvas>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters-section">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status" class="filter-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Provider:</label>
                    <select name="provider" class="filter-select">
                        <option value="all" <?php echo $provider_filter == 'all' ? 'selected' : ''; ?>>All Providers</option>
                        <option value="mtn" <?php echo $provider_filter == 'mtn' ? 'selected' : ''; ?>>MTN</option>
                        <option value="airtel" <?php echo $provider_filter == 'airtel' ? 'selected' : ''; ?>>Airtel</option>
                        <option value="card" <?php echo $provider_filter == 'card' ? 'selected' : ''; ?>>Card</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>From:</label>
                    <input type="date" name="date_from" class="filter-input" value="<?php echo $date_from; ?>">
                </div>

                <div class="filter-group">
                    <label>To:</label>
                    <input type="date" name="date_to" class="filter-input" value="<?php echo $date_to; ?>">
                </div>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name, email or transaction ID..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </div>
            </form>

            <!-- Transactions Table -->
            <div class="transactions-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Provider</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-credit-card" style="font-size: 3rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No transactions found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td>
                                    <small><?php echo $t['transaction_id']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['fullname']); ?></strong>
                                    <br>
                                    <small style="color: #999;"><?php echo $t['email']; ?></small>
                                </td>
                                <td class="amount">UGX <?php echo number_format($t['amount']); ?></td>
                                <td>
                                    <span class="provider-badge provider-<?php echo $t['provider']; ?>">
                                        <?php echo strtoupper($t['provider']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($t['plan']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $t['status']; ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                                    <br>
                                    <small style="color: #999;"><?php echo timeAgo($t['created_at']); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view" onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($t)); ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($t['status'] === 'completed'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Refund this transaction?')">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" name="refund_transaction" class="action-btn btn-refund" title="Refund">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this transaction?')">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" name="delete_transaction" class="action-btn btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- View Transaction Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Transaction Details</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $months = array_column($monthly_data, 'month');
                    $labels = array_map(function($month) {
                        return date('M Y', strtotime($month . '-01'));
                    }, $months);
                    echo json_encode(array_reverse($labels));
                ?>,
                datasets: [{
                    label: 'Revenue (UGX)',
                    data: <?php echo json_encode(array_reverse(array_column($monthly_data, 'revenue'))); ?>,
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
                                return 'UGX ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        function viewTransaction(transaction) {
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Transaction ID:</div>
                    <div class="detail-value">${transaction.transaction_id}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">User:</div>
                    <div class="detail-value">${transaction.fullname} (${transaction.email})</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Amount:</div>
                    <div class="detail-value warning">UGX ${transaction.amount.toLocaleString()}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Provider:</div>
                    <div class="detail-value">${transaction.provider.toUpperCase()}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value">${transaction.phone || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Plan:</div>
                    <div class="detail-value">${transaction.plan}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge status-${transaction.status}">${transaction.status}</span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div class="detail-value">${new Date(transaction.created_at).toLocaleString()}</div>
                </div>
            `;
            document.getElementById('viewModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>