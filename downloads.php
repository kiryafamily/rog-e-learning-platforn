<?php
// downloads.php
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
    // Approximate size (you'd need actual file size in production)
    $totalSize += 50; // 50MB per lesson approx
}
$storageUsed = $totalSize;
$storageLimit = 1000; // 1GB limit

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline Downloads - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <img src="images/logo.png" alt="RAYS OF GRACE">
                    <span>Offline Downloads</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="dashboard.php" class="btn btn-outline btn-small">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
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
                <button class="btn btn-outline" onclick="syncAll()">
                    <i class="fas fa-sync-alt"></i> Sync All
                </button>
                <button class="btn btn-outline" onclick="clearAll()">
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
                        <i class="fas fa-trash"></i>
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
                    <li><i class="fas fa-check"></i> Videos are optimized for mobile data saving</li>
                    <li><i class="fas fa-check"></i> Downloads expire after 30 days, renew to keep access</li>
                    <li><i class="fas fa-check"></i> Connect to WiFi to download large files</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .downloads-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .storage-card {
        background: linear-gradient(135deg, #4B1C3C 0%, #2F1224 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .storage-info h2 {
        color: white;
        margin: 0 0 15px 0;
    }
    
    .storage-info h2:after {
        display: none;
    }
    
    .meter-bar {
        width: 400px;
        height: 10px;
        background: rgba(255,255,255,0.2);
        border-radius: 5px;
        margin-bottom: 5px;
    }
    
    .meter-fill {
        height: 100%;
        background: #FFB800;
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
    }
    
    .storage-actions .btn-outline {
        border-color: white;
        color: white;
    }
    
    .storage-actions .btn-outline:hover {
        background: white;
        color: #4B1C3C;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .stat-card i {
        font-size: 2rem;
        color: #FFB800;
    }
    
    .stat-value {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #4B1C3C;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .storage-classes {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .storage-classes h3 {
        color: #4B1C3C;
        margin-bottom: 20px;
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
    }
    
    .class-name {
        width: 40px;
        font-weight: 600;
        color: #4B1C3C;
    }
    
    .class-bar {
        flex: 1;
        height: 8px;
        background: #F0F0F0;
        border-radius: 4px;
    }
    
    .bar-fill {
        height: 100%;
        background: #4B1C3C;
        border-radius: 4px;
    }
    
    .class-size {
        width: 60px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .downloads-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .downloads-header h3 {
        color: #4B1C3C;
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
    }
    
    .filter-select {
        padding: 8px 15px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
        color: #333;
    }
    
    .search-input {
        padding: 8px 15px;
        border: 1px solid #E0E0E0;
        border-radius: 5px;
        width: 200px;
    }
    
    .no-downloads {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
    }
    
    .no-downloads i {
        font-size: 3rem;
        color: #4B1C3C;
        margin-bottom: 15px;
    }
    
    .downloads-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .download-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        transition: all 0.3s ease;
    }
    
    .download-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(75, 28, 60, 0.1);
    }
    
    .download-thumb {
        width: 100px;
        background: #4B1C3C;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .download-thumb i {
        font-size: 2rem;
        color: #FFB800;
    }
    
    .download-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .download-info {
        flex: 1;
        padding: 15px;
    }
    
    .class-badge {
        background: #FFB800;
        color: #4B1C3C;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 5px;
    }
    
    .download-info h4 {
        color: #333;
        margin-bottom: 3px;
        font-size: 1rem;
    }
    
    .subject {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .download-meta {
        font-size: 0.8rem;
        color: #999;
        margin-bottom: 10px;
    }
    
    .meta-item {
        display: block;
        margin: 2px 0;
    }
    
    .meta-item i {
        width: 16px;
        color: #FFB800;
    }
    
    .progress-indicator {
        height: 3px;
        background: #4CAF50;
        border-radius: 2px;
    }
    
    .download-actions {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding: 15px;
    }
    
    .recent-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .recent-section h3 {
        color: #4B1C3C;
        margin-bottom: 15px;
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
        padding: 10px;
        background: #F9F9F9;
        border-radius: 5px;
    }
    
    .recent-item i {
        color: #FFB800;
        width: 20px;
    }
    
    .recent-item h4 {
        color: #333;
        font-size: 0.95rem;
        margin-bottom: 2px;
    }
    
    .recent-item span {
        color: #999;
        font-size: 0.8rem;
    }
    
    .recent-item .btn-text {
        margin-left: auto;
    }
    
    .tips-card {
        background: #FFF3E0;
        padding: 20px;
        border-radius: 10px;
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }
    
    .tips-card i {
        font-size: 2rem;
        color: #FFB800;
    }
    
    .tips-card h4 {
        color: #4B1C3C;
        margin-bottom: 10px;
    }
    
    .tips-card ul {
        list-style: none;
    }
    
    .tips-card li {
        color: #666;
        margin: 5px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tips-card li i {
        font-size: 1rem;
        color: #4CAF50;
    }
    
    @media (max-width: 768px) {
        .storage-card {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .meter-bar {
            width: 100%;
        }
        
        .downloads-header {
            flex-direction: column;
            gap: 10px;
        }
        
        .header-actions {
            width: 100%;
            flex-direction: column;
        }
        
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
        }
    }
    </style>

    <script>
    // Check online status
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
        // Implement sync logic
    }
    
    function clearAll() {
        if (confirm('Remove all offline lessons? This will free up storage space.')) {
            window.location.href = 'downloads.php?action=clear-all';
        }
    }
    
    function filterDownloads(value) {
        // Implement filtering
        console.log('Filtering by:', value);
    }
    
    // Register service worker for offline
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/offline-worker.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
        <script src="js/navbar.js"></script>
</body>
</html>