<?php
// admin/backup.php - Database Backup and Restore
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

// Create backups directory if it doesn't exist
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Get database configuration
    $dbname = DB_NAME;
    $dbuser = DB_USER;
    $dbpass = DB_PASS;
    
    // Create backup using mysqldump
    $command = "mysqldump --user=$dbuser --password=$dbpass --host=localhost $dbname > $filepath";
    system($command, $output);
    
    if (file_exists($filepath) && filesize($filepath) > 0) {
        $success = "Backup created successfully: $filename";
        logActivity($pdo, $user['id'], 'admin_backup', "Created backup: $filename");
    } else {
        $error = "Failed to create backup. Check your database permissions.";
    }
}

// Handle file upload restore
if (isset($_POST['restore_backup']) && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $file['tmp_name'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if ($file_ext === 'sql') {
            // Read SQL file
            $sql = file_get_contents($tmp_name);
            
            try {
                // Disable foreign key checks temporarily
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Execute SQL
                $pdo->exec($sql);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                $success = "Database restored successfully from " . $file['name'];
                logActivity($pdo, $user['id'], 'admin_restore', "Restored from: " . $file['name']);
            } catch (PDOException $e) {
                $error = "Restore failed: " . $e->getMessage();
            }
        } else {
            $error = "Please upload a valid SQL file";
        }
    } else {
        $error = "File upload failed";
    }
}

// Handle delete backup
if (isset($_POST['delete_backup'])) {
    $backup_file = $_POST['backup_file'];
    $filepath = $backup_dir . $backup_file;
    
    if (file_exists($filepath) && unlink($filepath)) {
        $success = "Backup deleted: $backup_file";
        logActivity($pdo, $user['id'], 'admin_backup_delete', "Deleted backup: $backup_file");
    } else {
        $error = "Failed to delete backup";
    }
}

// Handle download backup
if (isset($_GET['download'])) {
    $backup_file = $_GET['download'];
    $filepath = $backup_dir . $backup_file;
    
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup_file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// Get list of backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath)
            ];
        }
    }
    // Sort by date descending (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Get database statistics
$tables = $pdo->query("SHOW TABLE STATUS")->fetchAll();
$total_rows = 0;
$total_size = 0;
$total_data = 0;
$total_index = 0;

foreach ($tables as $table) {
    $total_rows += $table['Rows'];
    $total_size += $table['Data_length'] + $table['Index_length'];
    $total_data += $table['Data_length'];
    $total_index += $table['Index_length'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - RAYS OF GRACE</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .action-card h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-card h2 i {
            color: #FFB800;
        }

        .action-card p {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        .btn-warning {
            background: #FF9800;
            color: white;
        }

        .btn-warning:hover {
            background: #f57c00;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            border: 2px dashed #e0e0e0;
            border-radius: 5px;
            padding: 30px;
            text-align: center;
            background: #f8f4f8;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .file-upload:hover {
            border-color: #FFB800;
        }

        .file-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload i {
            font-size: 2.5rem;
            color: #FFB800;
            margin-bottom: 10px;
        }

        .file-upload p {
            color: #666;
        }

        .file-upload small {
            color: #999;
        }

        /* Backups Table */
        .backups-table {
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

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            transition: transform 0.2s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .btn-download { background: #4CAF50; }
        .btn-restore { background: #FF9800; }
        .btn-delete { background: #f44336; }

        .no-backups {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-backups i {
            font-size: 3rem;
            color: #FFB800;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        /* Info Box */
        .info-box {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #FFB800;
        }

        .info-box i {
            color: #FFB800;
            margin-right: 8px;
        }

        .info-box p {
            color: #666;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .backups-table {
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
            <span>Backup & Restore</span>
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
                <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="active"><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-database"></i> Backup & Restore</h1>
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

            <!-- Database Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($tables); ?></h3>
                        <p>Total Tables</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rows"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_rows); ?></h3>
                        <p>Total Records</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo round($total_size / 1024, 2); ?> KB</h3>
                        <p>Database Size</p>
                    </div>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="action-grid">
                <!-- Create Backup -->
                <div class="action-card">
                    <h2><i class="fas fa-plus-circle"></i> Create Backup</h2>
                    <p>Create a new backup of your entire database. This will save all tables, data, and structures.</p>
                    <form method="POST">
                        <button type="submit" name="create_backup" class="btn-primary">
                            <i class="fas fa-database"></i> Create New Backup
                        </button>
                    </form>
                </div>

                <!-- Restore Backup -->
                <div class="action-card">
                    <h2><i class="fas fa-upload"></i> Restore Backup</h2>
                    <p>Upload a previously saved backup file to restore your database.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="file-upload">
                            <input type="file" name="backup_file" accept=".sql" required>
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to select backup file</p>
                            <small>SQL files only</small>
                        </div>
                        <button type="submit" name="restore_backup" class="btn-primary btn-warning">
                            <i class="fas fa-upload"></i> Restore Database
                        </button>
                    </form>
                </div>
            </div>

            <!-- Existing Backups -->
            <div class="backups-table">
                <h2 style="padding: 20px; color: #4B1C3C;">
                    <i class="fas fa-history" style="color: #FFB800;"></i> Available Backups
                </h2>

                <?php if (empty($backups)): ?>
                    <div class="no-backups">
                        <i class="fas fa-database"></i>
                        <p>No backups found. Create your first backup!</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Backup File</th>
                                <th>Size</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-database" style="color: #FFB800; margin-right: 8px;"></i>
                                    <?php echo $backup['name']; ?>
                                </td>
                                <td><?php echo round($backup['size'] / 1024, 2); ?> KB</td>
                                <td><?php echo date('M d, Y H:i:s', $backup['date']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?download=<?php echo urlencode($backup['name']); ?>" class="action-btn btn-download" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Restore this backup? Current data will be replaced.')">
                                            <input type="hidden" name="backup_file" value="<?php echo $backup['name']; ?>">
                                            <button type="submit" name="restore_backup" class="action-btn btn-restore" title="Restore">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this backup?')">
                                            <input type="hidden" name="backup_file" value="<?php echo $backup['name']; ?>">
                                            <button type="submit" name="delete_backup" class="action-btn btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Backup Tips:</strong>
                <p>• Create regular backups to prevent data loss<br>
                   • Store backups in a safe location outside the server<br>
                   • Test your backups periodically by restoring them<br>
                   • The backup includes all tables: users, lessons, progress, etc.<br>
                   • Backup files are stored in the /backups directory</p>
            </div>
        </main>
    </div>
</body>
</html>