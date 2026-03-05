<?php
// admin/manage-users.php
// User management interface for administrators
// This page allows admins to view all users, filter by role and status, search for specific users, and perform actions like editing user details, sending notifications, and deleting accounts. The design is modern and user-friendly, with a focus on usability and efficiency.

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];
        $status = $_POST['status'];
        $class = $_POST['class'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE users SET role = ?, status = ?, class = ? WHERE id = ?");
        if ($stmt->execute([$role, $status, $class, $user_id])) {
            $success = 'User updated successfully';
            logActivity($pdo, $user['id'], 'admin_update_user', "Updated user ID: $user_id");
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Don't allow deleting own account
        if ($user_id == $user['id']) {
            $error = 'You cannot delete your own account';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = 'User deleted successfully';
                logActivity($pdo, $user['id'], 'admin_delete_user', "Deleted user ID: $user_id");
            }
        }
    }
    
    if (isset($_POST['send_notification'])) {
        $user_id = $_POST['user_id'];
        $title = $_POST['notification_title'];
        $message = $_POST['notification_message'];
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, created_at)
            VALUES (?, 'system', ?, ?, NOW())
        ");
        if ($stmt->execute([$user_id, $title, $message])) {
            $success = 'Notification sent successfully';
        }
    }
}

// Filters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($role_filter) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'parent' THEN 1 ELSE 0 END) as parents,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM users
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - RAYS OF GRACE</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="../images/logo.png" alt="RAYS OF GRACE">
                    <span>Manage Users</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="index.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php">
                        <i class="fas fa-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="manage-users.php">
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
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="user-stats">
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['active']; ?></span>
                    <span class="stat-label">Active</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['students']; ?></span>
                    <span class="stat-label">Students</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['parents']; ?></span>
                    <span class="stat-label">Parents</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['teachers']; ?></span>
                    <span class="stat-label">Teachers</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats['admins']; ?></span>
                    <span class="stat-label">Admins</span>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-section">
                <div class="filters">
                    <select onchange="filterRole(this.value)">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        <option value="parent" <?php echo $role_filter === 'parent' ? 'selected' : ''; ?>>Parent</option>
                        <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                    
                    <select onchange="filterStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                    
                    <button class="btn btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                </div>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>"
                           onkeyup="if(event.key === 'Enter') searchUsers(this.value)">
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Family Code</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($u['fullname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['fullname']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <span><i class="fas fa-envelope"></i> <?php echo $u['email']; ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo $u['phone']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $u['class'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge <?php echo $u['status']; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['family_id']): ?>
                                <code><?php echo $u['family_id']; ?></code>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            <td><?php echo $u['last_login'] ? timeAgo($u['last_login']) : 'Never'; ?></td>
                            <td class="actions">
                                <button class="action-btn edit" onclick="editUser(<?php echo $u['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn notify" onclick="showNotifyModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['fullname']); ?>')">
                                    <i class="fas fa-bell"></i>
                                </button>
                                <button class="action-btn view" onclick="viewUser(<?php echo $u['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($u['id'] != $user['id']): ?>
                                <button class="action-btn delete" onclick="deleteUser(<?php echo $u['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <button class="page-btn"><i class="fas fa-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <button class="page-btn">5</button>
                <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editUserModal')">&times;</span>
            <h2>Edit User</h2>
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="student">Student</option>
                        <option value="parent">Parent</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Class (for students)</label>
                    <select name="class" id="edit_class">
                        <option value="">Not applicable</option>
                        <?php foreach (getClasses() as $class): ?>
                        <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="update_user" class="btn btn-primary btn-block">
                    Update User
                </button>
            </form>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addUserModal')">&times;</span>
            <h2>Add New User</h2>
            <form method="POST" action="add-user.php" class="modal-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class">
                            <option value="">Select</option>
                            <?php foreach (getClasses() as $class): ?>
                            <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="add_user" class="btn btn-primary btn-block">
                    Create User
                </button>
            </form>
        </div>
    </div>

    <!-- Notification Modal -->
    <div id="notifyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('notifyModal')">&times;</span>
            <h2>Send Notification</h2>
            <p>To: <span id="notify_user_name"></span></p>
            
            <form method="POST" action="" id="notifyForm">
                <input type="hidden" name="user_id" id="notify_user_id">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="notification_title" required 
                           placeholder="e.g., Welcome to RAYS OF GRACE">
                </div>
                
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="notification_message" rows="4" required 
                              placeholder="Enter your message..."></textarea>
                </div>
                
                <button type="submit" name="send_notification" class="btn btn-primary btn-block">
                    Send Notification
                </button>
            </form>
        </div>
    </div>

    <style>
    .user-stats {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .stat-value {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #4B1C3C;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.8rem;
    }
    
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
    
    .filters select {
        padding: 8px 15px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
        min-width: 150px;
    }
    
    .users-table-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .users-table th {
        background: #4B1C3C;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 500;
    }
    
    .users-table td {
        padding: 12px;
        border-bottom: 1px solid #F0F0F0;
    }
    
    .users-table tr:hover {
        background: #F9F9F9;
    }
    
    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: #4B1C3C;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    
    .contact-info span {
        font-size: 0.9rem;
        color: #666;
    }
    
    .contact-info i {
        width: 16px;
        color: #FFB800;
    }
    
    .role-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .role-badge.admin {
        background: #f44336;
        color: white;
    }
    
    .role-badge.teacher {
        background: #2196F3;
        color: white;
    }
    
    .role-badge.parent {
        background: #4CAF50;
        color: white;
    }
    
    .role-badge.student {
        background: #FFB800;
        color: #4B1C3C;
    }
    
    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .status-badge.active {
        background: #E8F5E9;
        color: #4CAF50;
    }
    
    .status-badge.inactive {
        background: #FFEBEE;
        color: #f44336;
    }
    
    .status-badge.suspended {
        background: #FFF3E0;
        color: #FF9800;
    }
    
    .actions {
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
        transition: all 0.3s ease;
    }
    
    .action-btn.edit {
        background: #2196F3;
    }
    
    .action-btn.notify {
        background: #FFB800;
    }
    
    .action-btn.view {
        background: #4CAF50;
    }
    
    .action-btn.delete {
        background: #f44336;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
    }
    
    .pagination {
        display: flex;
        gap: 5px;
        justify-content: center;
    }
    
    .page-btn {
        width: 40px;
        height: 40px;
        border: 1px solid #E0E0E0;
        background: white;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .page-btn:hover {
        background: #F5F5F5;
    }
    
    .page-btn.active {
        background: #4B1C3C;
        color: white;
        border-color: #4B1C3C;
    }
    
    @media (max-width: 1200px) {
        .user-stats {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .users-table {
            display: block;
            overflow-x: auto;
        }
        
        .filters-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filters {
            flex-direction: column;
        }
        
        .filters select {
            width: 100%;
        }
        
        .user-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>

    <script>
    function filterRole(role) {
        window.location.href = `?role=${role}&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>`;
    }
    
    function filterStatus(status) {
        window.location.href = `?role=<?php echo $role_filter; ?>&status=${status}&search=<?php echo urlencode($search); ?>`;
    }
    
    function searchUsers(search) {
        window.location.href = `?role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=${encodeURIComponent(search)}`;
    }
    
    function editUser(userId) {
        // Fetch user data and populate modal
        fetch(`get-user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_user_id').value = data.id;
                document.getElementById('edit_role').value = data.role;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('edit_class').value = data.class || '';
                
                showModal('editUserModal');
            });
    }
    
    function showAddUserModal() {
        showModal('addUserModal');
    }
    
    function showNotifyModal(userId, userName) {
        document.getElementById('notify_user_id').value = userId;
        document.getElementById('notify_user_name').textContent = userName;
        showModal('notifyModal');
    }
    
    function viewUser(userId) {
        window.location.href = `view-user.php?id=${userId}`;
    }
    
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="delete_user">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    function hideModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let modal of modals) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
    </script>
</body>
</html>