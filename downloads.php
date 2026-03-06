<?php
// downloads.php - FULLY RESPONSIVE VERSION
// Offline downloads manager for students

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$action = $_GET['action'] ?? '';
$lessonId = $_GET['lesson'] ?? 0;

// Handle download requests
if ($action === 'download' && $lessonId) {
    $stmt = $pdo->prepare("
        SELECT * FROM lessons WHERE id = ? AND status = 'published'
    ");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();
    
    if ($lesson) {
        // Log download
        $stmt = $pdo->prepare("
            INSERT INTO downloads (user_id, lesson_id, file_type, file_path, downloaded_at, expires_at)
            VALUES (?, ?, 'video', ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([$user['id'], $lessonId, $lesson['video_path'] ?? '']);
        
        logActivity($pdo, $user['id'], 'download', "Downloaded lesson: {$lesson['topic']}");
        
        $_SESSION['success'] = 'Lesson added to offline access';
    }
    header('Location: downloads.php');
    exit;
}

// Handle remove from offline
if ($action === 'remove' && $lessonId) {
    $stmt = $pdo->prepare("DELETE FROM downloads WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user['id'], $lessonId]);
    
    $_SESSION['success'] = 'Lesson removed from offline access';
    header('Location: downloads.php');
    exit;
}

// Get user's downloads
$stmt = $pdo->prepare("
    SELECT d.*, l.topic, l.subject, l.class, l.thumbnail,
           l.video_path, l.pdf_path
    FROM downloads d
    JOIN lessons l ON d.lesson_id = l.id
    WHERE d.user_id = ?
    ORDER BY d.downloaded_at DESC
");
$stmt->execute([$user['id']]);
$downloads = $stmt->fetchAll();

// Calculate storage used
$totalSize = 0;
foreach ($downloads as $d) {
    $totalSize += 50;
}
$storageUsed = $totalSize;
$storageLimit = 1000;

// Get storage by class
$storageByClass = [];
foreach ($downloads as $d) {
    if (!isset($storageByClass[$d['class']])) {
        $storageByClass[$d['class']] = 0;
    }
    $storageByClass[$d['class']] += 50;
}

// Get recently accessed offline
$stmt = $pdo->prepare("
    SELECT d.*, l.topic 
    FROM downloads d
    JOIN lessons l ON d.lesson_id = l.id
    WHERE d.user_id = ? AND d.last_accessed IS NOT NULL
    ORDER BY d.last_accessed DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recentlyAccessed = $stmt->fetchAll();

// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    return date('M j', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Downloads | ROGELE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --purple: #4B1C3C;
            --purple-dark: #2F1224;
            --purple-light: #6A2B52;
            --gold: #FFB800;
            --gold-dark: #D99B00;
            --white: #FFFFFF;
            --gray-light: #F5F5F5;
            --gray: #666666;
            --gray-dark: #333333;
            --shadow-sm: 0 2px 8px rgba(75, 28, 60, 0.1);
            --shadow-md: 0 4px 12px rgba(75, 28, 60, 0.15);
            --shadow-lg: 0 8px 24px rgba(75, 28, 60, 0.2);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--gray-light);
            min-height: 100vh;
        }

        /* ===== RESPONSIVE NAVIGATION - DASHBOARD BUTTON ALWAYS TOP RIGHT ===== */
        .dashboard-nav {
            background: var(--white);
            padding: 12px 20px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .dashboard-nav .container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        /* Logo on left */
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .logo img {
            height: 45px;
            width: auto;
            transition: var(--transition);
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        /* Nav right - Dashboard button container (always on right) */
        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        /* Dashboard button styling */
        .btn-dashboard {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--purple);
            color: var(--purple);
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            cursor: pointer;
        }

        .btn-dashboard:hover {
            background: var(--purple);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-dashboard i {
            font-size: 1rem;
        }

        /* Other button styles */
        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-outline {
            background: transparent;
            border-color: var(--purple);
            color: var(--purple);
        }

        .btn-outline:hover {
            background: var(--purple);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary {
            background: var(--purple);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ===== MAIN CONTAINER ===== */
        .downloads-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* ===== STORAGE CARD - FULLY RESPONSIVE ===== */
        .storage-card {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            color: var(--white);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-lg);
        }

        .storage-info {
            flex: 1;
            min-width: 280px;
        }

        .storage-info h2 {
            color: var(--white);
            margin: 0 0 15px 0;
            font-size: 1.5rem;
        }

        .storage-meter {
            max-width: 400px;
        }

        .meter-bar {
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            margin-bottom: 8px;
        }

        .meter-fill {
            height: 100%;
            background: var(--gold);
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .meter-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }

        .storage-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .storage-actions .btn-outline {
            border-color: var(--white);
            color: var(--white);
            background: transparent;
        }

        .storage-actions .btn-outline:hover {
            background: var(--white);
            color: var(--purple);
        }

        /* ===== STATS GRID - FULLY RESPONSIVE ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--gold);
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--purple);
            line-height: 1.2;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
        }

        /* ===== STORAGE BY CLASS ===== */
        .storage-classes {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
        }

        .storage-classes h3 {
            color: var(--purple);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .class-bars {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .class-bar-item {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .class-name {
            width: 45px;
            font-weight: 600;
            color: var(--purple);
        }

        .class-bar {
            flex: 1;
            min-width: 150px;
            height: 8px;
            background: var(--gray-light);
            border-radius: 4px;
        }

        .bar-fill {
            height: 100%;
            background: var(--purple);
            border-radius: 4px;
        }

        .class-size {
            width: 70px;
            color: var(--gray);
            font-size: 0.9rem;
            text-align: right;
        }

        /* ===== DOWNLOADS HEADER ===== */
        .downloads-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .downloads-header h3 {
            color: var(--purple);
            font-size: 1.2rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select,
        .search-input {
            padding: 10px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--white);
        }

        .search-input {
            width: 250px;
        }

        .filter-select:focus,
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
        }

        /* ===== NO DOWNLOADS STATE ===== */
        .no-downloads {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
        }

        .no-downloads i {
            font-size: 4rem;
            color: var(--purple);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-downloads h3 {
            color: var(--purple);
            margin-bottom: 10px;
        }

        .no-downloads p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* ===== DOWNLOADS GRID ===== */
        .downloads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .download-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            display: flex;
            transition: var(--transition);
            position: relative;
        }

        .download-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .download-thumb {
            width: 120px;
            background: var(--purple);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .download-thumb i {
            font-size: 2.5rem;
            color: var(--gold);
        }

        .download-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .download-info {
            flex: 1;
            padding: 15px;
            min-width: 200px;
        }

        .class-badge {
            background: var(--gold);
            color: var(--purple);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }

        .download-info h4 {
            color: var(--gray-dark);
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .subject {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .download-meta {
            font-size: 0.75rem;
            color: #999;
            margin-bottom: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 3px 0;
        }

        .meta-item i {
            width: 14px;
            color: var(--gold);
        }

        .progress-indicator {
            height: 4px;
            background: #4CAF50;
            border-radius: 2px;
            margin-top: 8px;
        }

        .download-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 15px;
            min-width: 100px;
        }

        /* ===== RECENT SECTION ===== */
        .recent-section {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
        }

        .recent-section h3 {
            color: var(--purple);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: var(--gray-light);
            border-radius: 8px;
            transition: var(--transition);
            flex-wrap: wrap;
        }

        .recent-item:hover {
            background: #f0f0f0;
        }

        .recent-item i {
            color: var(--gold);
            width: 20px;
            font-size: 1rem;
        }

        .recent-item > div {
            flex: 1;
            min-width: 150px;
        }

        .recent-item h4 {
            color: var(--gray-dark);
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .recent-item span {
            color: #999;
            font-size: 0.8rem;
        }

        .btn-text {
            color: var(--purple);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .btn-text:hover {
            color: var(--gold);
        }

        /* ===== TIPS CARD ===== */
        .tips-card {
            background: #FFF3E0;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .tips-card i {
            font-size: 2.5rem;
            color: var(--gold);
        }

        .tips-card > div {
            flex: 1;
            min-width: 250px;
        }

        .tips-card h4 {
            color: var(--purple);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .tips-card ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .tips-card li {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .tips-card li i {
            font-size: 1rem;
            color: #4CAF50;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Large Tablets (1024px and below) */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-input {
                width: 200px;
            }
        }

        /* Tablets (768px and below) */
        @media (max-width: 768px) {
            .dashboard-nav {
                padding: 10px 15px;
            }
            
            .logo img {
                height: 38px;
            }
            
            .btn-dashboard {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .storage-card {
                flex-direction: column;
                text-align: center;
            }
            
            .storage-info {
                width: 100%;
            }
            
            .storage-meter {
                max-width: 100%;
            }
            
            .storage-actions {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                gap: 15px;
            }
            
            .downloads-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .header-actions {
                flex-direction: column;
            }
            
            .filter-select,
            .search-input {
                width: 100%;
            }
            
            .download-card {
                flex-direction: column;
            }
            
            .download-thumb {
                width: 100%;
                height: 140px;
            }
            
            .download-actions {
                flex-direction: row;
                justify-content: flex-end;
                padding: 10px 15px 15px;
            }
            
            .class-bar-item {
                flex-wrap: wrap;
            }
            
            .class-name {
                width: auto;
            }
            
            .class-bar {
                min-width: 100%;
                order: 3;
            }
            
            .class-size {
                width: auto;
                text-align: left;
            }
            
            .recent-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .recent-item i {
                display: none;
            }
            
            .recent-item .btn-text {
                margin-left: 0;
            }
            
            .tips-card {
                flex-direction: column;
            }
        }

        /* Mobile Phones (480px and below) */
        @media (max-width: 480px) {
            .dashboard-nav {
                padding: 10px;
            }
            
            .logo img {
                height: 32px;
            }
            
            .btn-dashboard {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            /* Hide text on very small screens, keep only icon */
            .btn-dashboard span {
                display: none;
            }
            
            .btn-dashboard i {
                font-size: 1.1rem;
                margin: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.3rem;
            }
            
            .downloads-grid {
                grid-template-columns: 1fr;
            }
            
            .download-actions {
                flex-direction: column;
            }
            
            .download-actions .btn {
                width: 100%;
            }
            
            .tips-card ul {
                grid-template-columns: 1fr;
            }
            
            .downloads-container {
                padding: 15px;
            }
        }

        /* Small Mobile (360px and below) */
        @media (max-width: 360px) {
            .logo img {
                height: 28px;
            }
            
            .btn-dashboard {
                padding: 6px 10px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            
            .recent-item > div {
                min-width: 100%;
            }
            
            .class-name {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .class-size {
                width: 100%;
                margin-top: 5px;
            }
        }

        /* Landscape Mode */
        @media (max-width: 768px) and (orientation: landscape) {
            .dashboard-nav .container {
                flex-direction: row;
            }
            
            .btn-dashboard span {
                display: inline; /* Show text in landscape */
            }
            
            .downloads-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation - Dashboard button always at top right -->
    <nav class="dashboard-nav">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="images/logo-3.png" alt="RAYS OF GRACE">
            </a>
            
            <div class="nav-right">
                <a href="dashboard.php" class="btn-dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="downloads-container">
        <!-- Storage Overview -->
        <div class="storage-card">
            <div class="storage-info">
                <h2>Offline Storage</h2>
                <div class="storage-meter">
                    <div class="meter-bar">
                        <div class="meter-fill" style="width: <?php echo ($storageUsed / $storageLimit) * 100; ?>%"></div>
                    </div>
                    <div class="meter-stats">
                        <span><strong><?php echo round($storageUsed); ?> MB</strong> used</span>
                        <span><?php echo $storageLimit; ?> MB total</span>
                    </div>
                </div>
            </div>
            <div class="storage-actions">
                <button class="btn btn-outline btn-small" onclick="syncAll()">
                    <i class="fas fa-sync-alt"></i> Sync All
                </button>
                <button class="btn btn-outline btn-small" onclick="clearAll()">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-download"></i>
                <div>
                    <span class="stat-value"><?php echo count($downloads); ?></span>
                    <span class="stat-label">Lessons Offline</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div>
                    <span class="stat-value"><?php echo count($recentlyAccessed); ?></span>
                    <span class="stat-label">Recently Accessed</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-hdd"></i>
                <div>
                    <span class="stat-value"><?php echo round((1 - $storageUsed/$storageLimit) * 100); ?>%</span>
                    <span class="stat-label">Free Space</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-wifi"></i>
                <div>
                    <span class="stat-value" id="online-status">Online</span>
                    <span class="stat-label">Connection</span>
                </div>
            </div>
        </div>

        <!-- Storage by Class -->
        <?php if (!empty($storageByClass)): ?>
        <div class="storage-classes">
            <h3>Storage by Class</h3>
            <div class="class-bars">
                <?php foreach ($storageByClass as $class => $size): ?>
                <div class="class-bar-item">
                    <span class="class-name"><?php echo $class; ?></span>
                    <div class="class-bar">
                        <div class="bar-fill" style="width: <?php echo ($size / $storageLimit) * 100; ?>%"></div>
                    </div>
                    <span class="class-size"><?php echo round($size); ?> MB</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Downloads List -->
        <div class="downloads-header">
            <h3>My Offline Lessons</h3>
            <div class="header-actions">
                <select class="filter-select" onchange="filterDownloads(this.value)">
                    <option value="all">All Lessons</option>
                    <option value="recent">Recently Added</option>
                    <option value="expiring">Expiring Soon</option>
                </select>
                <input type="search" placeholder="Search offline lessons..." class="search-input">
            </div>
        </div>

        <?php if (empty($downloads)): ?>
        <div class="no-downloads">
            <i class="fas fa-cloud-download-alt"></i>
            <h3>No Offline Lessons</h3>
            <p>Download lessons to access them without internet</p>
            <a href="lessons.php" class="btn btn-primary">Browse Lessons</a>
        </div>
        <?php else: ?>
        <div class="downloads-grid">
            <?php foreach ($downloads as $download): ?>
            <div class="download-card">
                <div class="download-thumb">
                    <?php if ($download['thumbnail']): ?>
                        <img src="<?php echo $download['thumbnail']; ?>" alt="<?php echo $download['topic']; ?>">
                    <?php else: ?>
                        <i class="fas fa-play-circle"></i>
                    <?php endif; ?>
                </div>
                
                <div class="download-info">
                    <span class="class-badge"><?php echo $download['class']; ?></span>
                    <h4><?php echo htmlspecialchars($download['topic']); ?></h4>
                    <p class="subject"><?php echo $download['subject']; ?></p>
                    
                    <div class="download-meta">
                        <span class="meta-item">
                            <i class="fas fa-calendar"></i>
                            Downloaded: <?php echo date('d M Y', strtotime($download['downloaded_at'])); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-hourglass-half"></i>
                            Expires: <?php echo date('d M Y', strtotime($download['expires_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="progress-indicator" style="width: <?php echo rand(0, 100); ?>%"></div>
                </div>
                
                <div class="download-actions">
                    <a href="lesson-view.php?id=<?php echo $download['lesson_id']; ?>&offline=1" class="btn btn-primary btn-small">
                        <i class="fas fa-play"></i> Play
                    </a>
                    <button class="btn btn-outline btn-small" onclick="removeOffline(<?php echo $download['lesson_id']; ?>)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recently Accessed -->
        <?php if (!empty($recentlyAccessed)): ?>
        <div class="recent-section">
            <h3>Recently Accessed Offline</h3>
            <div class="recent-list">
                <?php foreach ($recentlyAccessed as $recent): ?>
                <div class="recent-item">
                    <i class="fas fa-history"></i>
                    <div>
                        <h4><?php echo htmlspecialchars($recent['topic']); ?></h4>
                        <span><?php echo timeAgo($recent['last_accessed']); ?></span>
                    </div>
                    <a href="lesson-view.php?id=<?php echo $recent['lesson_id']; ?>&offline=1" class="btn-text">
                        Resume <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Offline Tips -->
        <div class="tips-card">
            <i class="fas fa-lightbulb"></i>
            <div>
                <h4>Offline Access Tips</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Downloaded lessons work without internet</li>
                    <li><i class="fas fa-check"></i> Videos optimized for mobile data saving</li>
                    <li><i class="fas fa-check"></i> Downloads expire after 30 days</li>
                    <li><i class="fas fa-check"></i> Connect to WiFi to download large files</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function updateOnlineStatus() {
        const statusEl = document.getElementById('online-status');
        if (navigator.onLine) {
            statusEl.textContent = 'Online';
            statusEl.style.color = '#4CAF50';
        } else {
            statusEl.textContent = 'Offline';
            statusEl.style.color = '#f44336';
        }
    }
    
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
    
    function removeOffline(lessonId) {
        if (confirm('Remove this lesson from offline storage?')) {
            window.location.href = `downloads.php?action=remove&lesson=${lessonId}`;
        }
    }
    
    function syncAll() {
        alert('Syncing all offline content...');
    }
    
    function clearAll() {
        if (confirm('Remove all offline lessons? This will free up storage space.')) {
            window.location.href = 'downloads.php?action=clear-all';
        }
    }
    
    function filterDownloads(value) {
        console.log('Filtering by:', value);
    }
    
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/offline-worker.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>