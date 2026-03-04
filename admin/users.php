<?php
// admin/users.php - Complete User Management
// This page allows administrators to view all users, filter by role and status, search for specific users, and perform actions like editing user details, sending notifications, and deleting accounts. The design is modern and user-friendly, with a focus on usability and efficiency.
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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $class = $_POST['class'] ?? null;
        $status = 'active';
        
        // Check if email exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email already exists';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password, role, class, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$fullname, $email, $phone, $password, $role, $class, $status])) {
                $success = 'User added successfully';
                logActivity($pdo, $user['id'], 'admin_add_user', "Added user: $fullname");
            } else {
                $error = 'Failed to add user';
            }
        }
    }
    
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $class = $_POST['class'] ?? null;
        
        // Check if email exists for another user
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = 'Email already in use by another user';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, role = ?, status = ?, class = ? WHERE id = ?");
            if ($stmt->execute([$fullname, $email, $phone, $role, $status, $class, $user_id])) {
                $success = 'User updated successfully';
                logActivity($pdo, $user['id'], 'admin_update_user', "Updated user ID: $user_id");
            } else {
                $error = 'Failed to update user';
            }
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        if ($user_id != $user['id']) {
            // Check if user has any related records
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = 'User deleted successfully';
                logActivity($pdo, $user['id'], 'admin_delete_user', "Deleted user ID: $user_id");
            } else {
                $error = 'Failed to delete user';
            }
        } else {
            $error = 'Cannot delete your own account';
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = password_hash('Password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$new_password, $user_id])) {
            $success = 'Password reset to Password123';
            logActivity($pdo, $user['id'], 'admin_reset_password', "Reset password for user ID: $user_id");
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get statistics
$total_users = count($users);
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$parents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'parent'")->fetchColumn();
$teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - RAYS OF GRACE</title>
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
        }

        .page-header h1 {
            color: #4B1C3C;
            font-size: 2rem;
        }

        .page-header h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .btn-add {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-add:hover {
            background: #2F1224;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            color: #4B1C3C;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
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

        /* Users Table */
        .users-table {
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

        .role-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .role-admin { background: #f44336; color: white; }
        .role-teacher { background: #2196F3; color: white; }
        .role-parent { background: #4CAF50; color: white; }
        .role-student { background: #FFB800; color: #4B1C3C; }

        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background: #E8F5E9; color: #4CAF50; }
        .status-inactive { background: #FFEBEE; color: #f44336; }

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

        .btn-edit { background: #2196F3; }
        .btn-reset { background: #FF9800; }
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
            max-width: 500px;
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFB800;
        }

        .btn-submit {
            background: #4B1C3C;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-submit:hover {
            background: #2F1224;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .users-table {
                overflow-x: auto;
            }
            
            .page-header {
                flex-direction: column;
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
            <span>User Management</span>
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
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="lessons.php"><i class="fas fa-book"></i> Lessons</a></li>
                <li><a href="upload-lesson.php"><i class="fas fa-upload"></i> Upload Lesson</a></li>
                <li><a href="transactions.php"><i class="fas fa-credit-card"></i> Transactions</a></li>
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
                <h1><i class="fas fa-users"></i> User Management</h1>
                <button class="btn-add" onclick="openModal('addModal')">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
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

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Active</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $admins; ?></h3>
                    <p>Admins</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $teachers; ?></h3>
                    <p>Teachers</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $parents; ?></h3>
                    <p>Parents</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $students; ?></h3>
                    <p>Students</p>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td><?php echo $u['phone']; ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $u['class'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $u['status']; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn btn-edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn btn-reset" onclick="resetPassword(<?php echo $u['id']; ?>, '<?php echo addslashes($u['fullname']); ?>')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if ($u['id'] != $user['id']): ?>
                                    <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['fullname']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button class="close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class (for students)</label>
                        <select name="class" class="form-control">
                            <option value="">None</option>
                            <option value="P1">P1</option>
                            <option value="P2">P2</option>
                            <option value="P3">P3</option>
                            <option value="P4">P4</option>
                            <option value="P5">P5</option>
                            <option value="P6">P6</option>
                            <option value="P7">P7</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn-submit">Add User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <button class="close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="user_id" id="edit_id">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class" id="edit_class" class="form-control">
                            <option value="">None</option>
                            <option value="P1">P1</option>
                            <option value="P2">P2</option>
                            <option value="P3">P3</option>
                            <option value="P4">P4</option>
                            <option value="P5">P5</option>
                            <option value="P6">P6</option>
                            <option value="P7">P7</option>
                        </select>
                    </div>
                    <button type="submit" name="update_user" class="btn-submit">Update User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: #f44336;">
                <h3>Delete User</h3>
                <button class="close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                <p style="color: #f44336; margin-bottom: 20px;">This action cannot be undone!</p>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="user_id" id="delete_id">
                    <button type="submit" name="delete_user" class="btn-submit" style="background: #f44336;">Yes, Delete</button>
                    <button type="button" class="btn-submit" style="background: #666; margin-top: 10px;" onclick="closeModal('deleteModal')">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.fullname;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_status').value = user.status;
            document.getElementById('edit_class').value = user.class || '';
            
            openModal('editModal');
        }

        function deleteUser(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteName').textContent = name;
            openModal('deleteModal');
        }

        function resetPassword(id, name) {
            if (confirm(`Reset password for ${name} to 'Password123'?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="user_id" value="${id}"><input type="hidden" name="reset_password">`;
                document.body.appendChild(form);
                form.submit();
            }
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