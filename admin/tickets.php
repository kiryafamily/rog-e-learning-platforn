<?php
// admin/tickets.php - Admin Support Ticket Management
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// admin/tickets.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ADD THIS FUNCTION HERE
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Rest of your code continues...
// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Handle ticket response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $response = sanitize($_POST['response']);
    $status = $_POST['status'];
    
    if (empty($response)) {
        $error = 'Response cannot be empty';
    } else {
        $stmt = $pdo->prepare("
            UPDATE support_tickets 
            SET admin_response = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$response, $status, $ticket_id])) {
            $success = 'Response sent successfully';
            
            // Get ticket details to notify user
            $stmt = $pdo->prepare("
                SELECT t.*, u.email, u.fullname 
                FROM support_tickets t
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch();
            
            // Send email notification to user
            $subject = "Your Support Ticket #$ticket_id has been updated";
            $message = "
                <h2>Support Ticket Update</h2>
                <p>Dear {$ticket['fullname']},</p>
                <p>Your support ticket has been updated:</p>
                <p><strong>Subject:</strong> {$ticket['subject']}</p>
                <p><strong>Status:</strong> $status</p>
                <p><strong>Response:</strong></p>
                <p>$response</p>
                <p>You can view your ticket at: <a href='".SITE_URL."/help.php'>Help Center</a></p>
            ";
            // sendEmail($ticket['email'], $subject, $message);
            
            // Log activity
            logActivity($pdo, $user['id'], 'ticket_response', "Responded to ticket #$ticket_id");
        } else {
            $error = 'Failed to send response';
        }
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $ticket_ids = $_POST['ticket_ids'] ?? [];
    
    if (!empty($ticket_ids)) {
        $placeholders = implode(',', array_fill(0, count($ticket_ids), '?'));
        
        switch ($action) {
            case 'mark_open':
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'open' WHERE id IN ($placeholders)");
                $stmt->execute($ticket_ids);
                $success = count($ticket_ids) . ' tickets marked as open';
                break;
            case 'mark_in_progress':
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'in-progress' WHERE id IN ($placeholders)");
                $stmt->execute($ticket_ids);
                $success = count($ticket_ids) . ' tickets marked as in progress';
                break;
            case 'mark_resolved':
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'resolved' WHERE id IN ($placeholders)");
                $stmt->execute($ticket_ids);
                $success = count($ticket_ids) . ' tickets marked as resolved';
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id IN ($placeholders)");
                $stmt->execute($ticket_ids);
                $success = count($ticket_ids) . ' tickets deleted';
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT t.*, u.fullname, u.email 
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $sql .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($search)) {
    $sql .= " AND (t.subject LIKE ? OR t.message LIKE ? OR u.fullname LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY 
    CASE t.status 
        WHEN 'open' THEN 1 
        WHEN 'in-progress' THEN 2 
        WHEN 'resolved' THEN 3 
        ELSE 4 
    END,
    t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get statistics
$stats = [
    'open' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
    'in_progress' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'in-progress'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'resolved'")->fetchColumn(),
    'total' => $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin - RAYS OF GRACE</title>
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
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-area img {
            height: 40px;
            width: auto;
            background: white;
            border-radius: 5px;
            padding: 3px;
        }

        .logo-area span {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-badge {
            background: #FFB800;
            color: #4B1C3C;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
        }

        .nav-btn {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
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
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
        }

        .sidebar-menu {
            list-style: none;
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

        /* Stats Cards */
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
            color: #4B1C3C;
            font-size: 1.8rem;
            line-height: 1.2;
        }

        .stat-info p {
            color: #666;
        }

        /* Filters */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            min-width: 150px;
        }

        .search-box {
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
            min-width: 250px;
            padding: 8px 0;
        }

        .search-box button {
            background: none;
            border: none;
            color: #FFB800;
            cursor: pointer;
        }

        /* Bulk Actions */
        .bulk-actions {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .bulk-select {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-bulk {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-bulk.open {
            background: #FFEBEE;
            color: #f44336;
        }

        .btn-bulk.progress {
            background: #FFF3E0;
            color: #FF9800;
        }

        .btn-bulk.resolved {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .btn-bulk.delete {
            background: #f44336;
            color: white;
        }

        .btn-bulk:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }

        /* Tickets Table */
        .tickets-table {
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
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f4f8;
        }

        .priority-badge {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-low {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .priority-medium {
            background: #FFF3E0;
            color: #FF9800;
        }

        .priority-high {
            background: #FFEBEE;
            color: #f44336;
        }

        .priority-urgent {
            background: #f44336;
            color: white;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open {
            background: #FFEBEE;
            color: #f44336;
        }

        .status-in-progress {
            background: #FFF3E0;
            color: #FF9800;
        }

        .status-resolved {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
        }

        .action-btn.view {
            background: #2196F3;
        }

        .action-btn.reply {
            background: #4CAF50;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

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

        .ticket-detail {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f4f8;
            border-radius: 5px;
        }

        .ticket-detail label {
            font-weight: 600;
            color: #4B1C3C;
            display: block;
            margin-bottom: 5px;
        }

        .ticket-detail p {
            color: #666;
            line-height: 1.6;
        }

        .response-form textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            margin: 10px 0;
            font-family: inherit;
        }

        .response-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .filters-section {
                flex-direction: column;
            }
            
            .search-box input {
                min-width: auto;
            }
            
            table {
                display: block;
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
            <span>Support Ticket Management</span>
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
                <li>
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
                <li class="active">
                    <a href="tickets.php">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Support Tickets</span>
                    </a>
                </li>
                <li>
                    <a href="analytics.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div style="background: #E8F5E9; color: #2E7D32; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #FFEBEE; color: #C62828; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Tickets</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['open']; ?></h3>
                        <p>Open</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filters">
                    <select class="filter-select" onchange="filterStatus(this.value)">
                        <option value="all">All Status</option>
                        <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in-progress" <?php echo $status_filter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>

                    <select class="filter-select" onchange="filterPriority(this.value)">
                        <option value="all">All Priority</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>

                <form method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search tickets..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm">
                <div class="bulk-actions">
                    <div class="bulk-select">
                        <input type="checkbox" id="selectAll" onclick="toggleAll()">
                        <label for="selectAll">Select All</label>
                    </div>
                    
                    <select name="bulk_action" class="filter-select" required>
                        <option value="">Bulk Actions</option>
                        <option value="mark_open">Mark as Open</option>
                        <option value="mark_in_progress">Mark as In Progress</option>
                        <option value="mark_resolved">Mark as Resolved</option>
                        <option value="delete">Delete</option>
                    </select>
                    
                    <button type="submit" class="btn-bulk open">Apply</button>
                </div>

                <!-- Tickets Table -->
                <div class="tickets-table">
                    <table>
                        <thead>
                            <tr>
                                <th width="30"></th>
                                <th>ID</th>
                                <th>User</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-ticket-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 10px;"></i>
                                        <p>No tickets found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="ticket-checkbox">
                                    </td>
                                    <td>#<?php echo $ticket['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ticket['fullname']); ?></strong>
                                        <br>
                                        <small style="color: #999;"><?php echo $ticket['email']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><?php echo ucfirst($ticket['category']); ?></td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $ticket['status']); ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                        <br>
                                        <small style="color: #999;"><?php echo timeAgo($ticket['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view" onclick="viewTicket(<?php echo $ticket['id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn reply" onclick="replyToTicket(<?php echo $ticket['id']; ?>)" title="Reply">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </main>
    </div>

    <!-- View Ticket Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ticket Details</h3>
                <button class="close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Loaded via AJAX -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reply to Ticket</h3>
                <button class="close" onclick="closeModal('replyModal')">&times;</button>
            </div>
            <div class="modal-body" id="replyModalBody">
                <!-- Loaded via AJAX -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter functions
        function filterStatus(status) {
            window.location.href = '?status=' + status + '&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>';
        }

        function filterPriority(priority) {
            window.location.href = '?status=<?php echo $status_filter; ?>&priority=' + priority + '&search=<?php echo urlencode($search); ?>';
        }

        // Select all checkboxes
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // View ticket details
        function viewTicket(id) {
            const modal = document.getElementById('viewModal');
            const modalBody = document.getElementById('viewModalBody');
            
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            modal.classList.add('active');
            
            fetch('get-ticket.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = '<div style="color: #f44336; padding: 20px;">Error loading ticket details</div>';
                });
        }

        // Reply to ticket
        function replyToTicket(id) {
            const modal = document.getElementById('replyModal');
            const modalBody = document.getElementById('replyModalBody');
            
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            modal.classList.add('active');
            
            fetch('reply-ticket.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = '<div style="color: #f44336; padding: 20px;">Error loading reply form</div>';
                });
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>