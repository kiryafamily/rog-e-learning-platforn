<?php
// admin/lessons.php - Complete Lesson Management for Admin
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

// Handle lesson actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_lesson'])) {
        $lesson_id = $_POST['lesson_id'];
        
        // Get file paths to delete
        $stmt = $pdo->prepare("SELECT video_path, pdf_path, thumbnail FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $lesson = $stmt->fetch();
        
        // Delete files if they exist
        if ($lesson['video_path'] && file_exists("../uploads/videos/" . $lesson['video_path'])) {
            unlink("../uploads/videos/" . $lesson['video_path']);
        }
        if ($lesson['pdf_path'] && file_exists("../uploads/pdfs/" . $lesson['pdf_path'])) {
            unlink("../uploads/pdfs/" . $lesson['pdf_path']);
        }
        if ($lesson['thumbnail'] && file_exists("../uploads/thumbnails/" . $lesson['thumbnail'])) {
            unlink("../uploads/thumbnails/" . $lesson['thumbnail']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        if ($stmt->execute([$lesson_id])) {
            $success = 'Lesson deleted successfully';
            logActivity($pdo, $user['id'], 'admin_delete_lesson', "Deleted lesson ID: $lesson_id");
        } else {
            $error = 'Failed to delete lesson';
        }
    }
    
    if (isset($_POST['update_status'])) {
        $lesson_id = $_POST['lesson_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE lessons SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $lesson_id])) {
            $success = 'Lesson status updated';
            logActivity($pdo, $user['id'], 'admin_update_lesson', "Updated lesson ID: $lesson_id to $status");
        }
    }
}

// Get filter parameters
$class_filter = $_GET['class'] ?? 'all';
$subject_filter = $_GET['subject'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT l.*, 
        (SELECT COUNT(*) FROM progress WHERE lesson_id = l.id) as views,
        (SELECT COUNT(*) FROM quiz_questions WHERE lesson_id = l.id) as quiz_count
        FROM lessons l
        WHERE 1=1";
$params = [];

if ($class_filter !== 'all') {
    $sql .= " AND l.class = ?";
    $params[] = $class_filter;
}

if ($subject_filter !== 'all') {
    $sql .= " AND l.subject = ?";
    $params[] = $subject_filter;
}

if ($status_filter !== 'all') {
    $sql .= " AND l.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (l.topic LIKE ? OR l.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

// Get statistics
$total_lessons = $pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn();
$published_lessons = $pdo->query("SELECT COUNT(*) FROM lessons WHERE status = 'published'")->fetchColumn();
$draft_lessons = $pdo->query("SELECT COUNT(*) FROM lessons WHERE status = 'draft'")->fetchColumn();
$total_views = $pdo->query("SELECT COUNT(*) FROM progress")->fetchColumn();

// Get unique classes and subjects for filters
$classes = $pdo->query("SELECT DISTINCT class FROM lessons ORDER BY class")->fetchAll();
$subjects = $pdo->query("SELECT DISTINCT subject FROM lessons ORDER BY subject")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Management - RAYS OF GRACE</title>
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
            text-decoration: none;
        }

        .btn-add:hover {
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

        /* Lessons Table */
        .lessons-table {
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

        .class-badge {
            background: #4B1C3C;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .subject-badge {
            background: #FFB800;
            color: #4B1C3C;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 5px;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-published {
            background: #E8F5E9;
            color: #4CAF50;
        }

        .status-draft {
            background: #FFEBEE;
            color: #f44336;
        }

        .views-count {
            display: flex;
            align-items: center;
            gap: 3px;
            color: #666;
        }

        .views-count i {
            color: #FFB800;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .btn-view { background: #2196F3; }
        .btn-edit { background: #4CAF50; }
        .btn-delete { background: #f44336; }
        .btn-stats { background: #FF9800; }

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
            max-width: 400px;
            border-radius: 10px;
            overflow: hidden;
        }

        .modal-header {
            background: #f44336;
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

        .modal-body p {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm {
            background: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            flex: 1;
        }

        .btn-cancel {
            background: #666;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            flex: 1;
        }

        /* Status Toggle */
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            transition: 0.3s;
        }

        .toggle-switch.active {
            background: #4CAF50;
        }

        .toggle-switch:after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: 0.3s;
        }

        .toggle-switch.active:after {
            left: 22px;
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
            
            .lessons-table {
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
            <span>Lesson Management</span>
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
                <li class="active"><a href="lessons.php"><i class="fas fa-book"></i> Lessons</a></li>
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
                <h1><i class="fas fa-book"></i> Lesson Management</h1>
                <a href="upload-lesson.php" class="btn-add">
                    <i class="fas fa-plus"></i> Upload New Lesson
                </a>
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
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_lessons; ?></h3>
                        <p>Total Lessons</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $published_lessons; ?></h3>
                        <p>Published</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $draft_lessons; ?></h3>
                        <p>Drafts</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_views; ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <label>Class:</label>
                    <select class="filter-select" onchange="filterByClass(this.value)">
                        <option value="all">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class']; ?>" <?php echo $class_filter == $c['class'] ? 'selected' : ''; ?>>
                            <?php echo $c['class']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Subject:</label>
                    <select class="filter-select" onchange="filterBySubject(this.value)">
                        <option value="all">All Subjects</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['subject']; ?>" <?php echo $subject_filter == $s['subject'] ? 'selected' : ''; ?>>
                            <?php echo $s['subject']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Status:</label>
                    <select class="filter-select" onchange="filterByStatus(this.value)">
                        <option value="all">All Status</option>
                        <option value="published" <?php echo $status_filter == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <form method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search lessons..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Lessons Table -->
            <div class="lessons-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lesson</th>
                            <th>Class/Subject</th>
                            <th>Week</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Quiz</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lessons)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-book-open" style="font-size: 3rem; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No lessons found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $lesson): ?>
                            <tr>
                                <td>#<?php echo $lesson['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($lesson['topic']); ?></strong>
                                    <?php if (!empty($lesson['description'])): ?>
                                    <br><small style="color: #999;"><?php echo substr(htmlspecialchars($lesson['description']), 0, 50); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="class-badge"><?php echo $lesson['class']; ?></span>
                                    <span class="subject-badge"><?php echo $lesson['subject']; ?></span>
                                </td>
                                <td>Week <?php echo $lesson['week']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $lesson['status']; ?>">
                                        <?php echo ucfirst($lesson['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="views-count">
                                        <i class="fas fa-eye"></i> <?php echo $lesson['views']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($lesson['quiz_count'] > 0): ?>
                                    <span style="color: #4CAF50;">
                                        <i class="fas fa-check-circle"></i> <?php echo $lesson['quiz_count']; ?> Q
                                    </span>
                                    <?php else: ?>
                                    <span style="color: #999;">
                                        <i class="fas fa-times-circle"></i> None
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($lesson['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../lesson-view.php?id=<?php echo $lesson['id']; ?>" target="_blank" class="action-btn btn-view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-lesson.php?id=<?php echo $lesson['id']; ?>" class="action-btn btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="action-btn btn-stats" title="Statistics" onclick="viewStats(<?php echo $lesson['id']; ?>)">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        <button class="action-btn btn-delete" title="Delete" onclick="deleteLesson(<?php echo $lesson['id']; ?>, '<?php echo addslashes($lesson['topic']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Lesson</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteLessonName"></strong>?</p>
                <p style="color: #f44336;">This will also delete all associated files and quiz questions!</p>
                <div class="modal-actions">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="lesson_id" id="deleteLessonId">
                        <button type="submit" name="delete_lesson" class="btn-confirm">Yes, Delete</button>
                    </form>
                    <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterByClass(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('class', value);
            window.location.href = url.toString();
        }

        function filterBySubject(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('subject', value);
            window.location.href = url.toString();
        }

        function filterByStatus(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('status', value);
            window.location.href = url.toString();
        }

        function deleteLesson(id, name) {
            document.getElementById('deleteLessonId').value = id;
            document.getElementById('deleteLessonName').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function viewStats(id) {
            alert('Lesson statistics feature coming soon!');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>