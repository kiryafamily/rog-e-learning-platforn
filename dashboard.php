<?php
// dashboard.php - FULLY RESPONSIVE VERSION
// This page serves as the main dashboard for logged-in users

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$subscription = getUserSubscription($pdo, $user['id']);
$hasAccess = hasAccess($pdo, $user['id']);

// Get user's children if parent account
$children = [];
if ($user['role'] === 'parent') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE family_id = ? AND role = 'student'");
    $stmt->execute([$user['family_id']]);
    $children = $stmt->fetchAll();
}

// Get in-progress lessons
$stmt = $pdo->prepare("
    SELECT l.*, p.status, p.last_accessed, p.progress 
    FROM progress p
    JOIN lessons l ON p.lesson_id = l.id
    WHERE p.user_id = ? AND p.status = 'in_progress'
    ORDER BY p.last_accessed DESC
    LIMIT 3
");
$stmt->execute([$user['id']]);
$inProgress = $stmt->fetchAll();

// Get completed lessons count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM progress 
    WHERE user_id = ? AND status = 'completed'
");
$stmt->execute([$user['id']]);
$completedCount = $stmt->fetch()['count'];

// Get quiz scores
$stmt = $pdo->prepare("
    SELECT AVG(percentage) as avg_score 
    FROM quiz_results 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$avgScore = round($stmt->fetch()['avg_score'] ?? 0);

// Get recent activity
$stmt = $pdo->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user['id']]);
$activities = $stmt->fetchAll();

// Get total lessons count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM lessons 
    WHERE class = ? OR class IS NULL
");
$stmt->execute([$user['class'] ?? 'P1']);
$totalLessons = $stmt->fetch()['total'];

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
    <title>Dashboard | ROGELE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --purple: #4B1C3C;
            --purple-dark: #2F1224;
            --purple-light: #6A2B52;
            --gold: #FFB800;
            --gold-dark: #D99B00;
            --gold-light: #FFD74D;
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
            background: #f8f4f8;
            min-height: 100vh;
        }

        /* ===== TOP NAVIGATION - FULLY RESPONSIVE ===== */
        .dashboard-nav {
            background: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 10px;
        }

        .logo-area {
            display: flex;
            align-items: center;
        }

        .logo-area a {
            display: block;
            line-height: 0;
        }

        .logo-area img {
            height: 40px;
            width: auto;
            border-radius: 8px;
            transition: var(--transition);
        }

        .logo-area img:hover {
            transform: scale(1.05);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .user-greeting {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8f4f8;
            padding: 8px 16px;
            border-radius: 50px;
            color: var(--purple);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .user-greeting i {
            color: var(--gold);
            font-size: 1rem;
        }

        .dropbtn {
            background: var(--purple);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            white-space: nowrap;
        }

        .dropbtn:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .dropbtn i {
            font-size: 0.9rem;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 180px;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            margin-top: 10px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: var(--gray-dark);
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            background: #f8f4f8;
            color: var(--purple);
        }

        .dropdown-content a i {
            width: 18px;
            color: var(--gold);
            font-size: 0.9rem;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* ===== MAIN CONTAINER - FLEXIBLE LAYOUT ===== */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        /* ===== SIDEBAR - RESPONSIVE ===== */
        .dashboard-sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            transition: var(--transition);
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px 20px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .sidebar-header h3 {
            color: var(--purple);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .sidebar-header p {
            color: var(--gold);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }

        .sidebar-menu li {
            margin: 2px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
            font-size: 0.95rem;
        }

        .sidebar-menu a:hover {
            background: #f8f4f8;
            color: var(--purple);
            border-left-color: var(--gold);
        }

        .sidebar-menu li.active a {
            background: linear-gradient(90deg, rgba(75,28,60,0.1) 0%, rgba(255,184,0,0.05) 100%);
            color: var(--purple);
            border-left-color: var(--gold);
            font-weight: 500;
        }

        .sidebar-menu i {
            width: 20px;
            color: var(--gold);
            font-size: 1.1rem;
        }

        /* ===== MAIN CONTENT ===== */
        .dashboard-main {
            flex: 1;
            padding: 25px;
            overflow-x: hidden;
        }

        /* ===== SUBSCRIPTION BANNER - RESPONSIVE ===== */
        .subscription-banner {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-lg);
            flex-wrap: wrap;
        }

        .banner-content {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 250px;
        }

        .banner-content i {
            font-size: 2.5rem;
            color: var(--gold);
        }

        .banner-text h3 {
            color: white;
            margin-bottom: 5px;
            font-size: 1.2rem;
        }

        .banner-text p {
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
        }

        .btn-banner {
            background: var(--gold);
            color: var(--purple);
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            white-space: nowrap;
            font-size: 0.95rem;
        }

        .btn-banner:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ===== WELCOME SECTION ===== */
        .welcome-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text h1 {
            color: var(--purple);
            font-size: 1.8rem;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .welcome-text p {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .welcome-text i {
            color: var(--gold);
        }

        .streak-badge {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--purple);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            box-shadow: var(--shadow-sm);
        }

        .streak-badge i {
            font-size: 1.3rem;
        }

        /* ===== STATS GRID - FULLY RESPONSIVE ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,184,0,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: var(--gold);
        }

        .stat-details {
            flex: 1;
            min-width: 0;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--purple);
            line-height: 1.2;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-progress {
            margin-top: 8px;
            height: 4px;
            background: #f0f0f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .stat-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--purple), var(--gold));
            border-radius: 2px;
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-title h2 {
            color: var(--purple);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title h2 i {
            color: var(--gold);
        }

        .view-all {
            color: var(--purple);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .view-all:hover {
            color: var(--gold);
            gap: 8px;
        }

        /* ===== LESSONS GRID - RESPONSIVE ===== */
        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .lesson-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .lesson-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .lesson-thumb {
            height: 120px;
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lesson-thumb i {
            font-size: 2.5rem;
            color: var(--gold);
            opacity: 0.8;
        }

        .class-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gold);
            color: var(--purple);
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .lesson-info {
            padding: 15px;
        }

        .lesson-info h3 {
            color: var(--purple);
            margin-bottom: 5px;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lesson-info p {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .progress-container {
            margin-bottom: 12px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--gray);
            margin-bottom: 4px;
        }

        .progress-bar {
            height: 4px;
            background: #f0f0f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--purple), var(--gold));
            border-radius: 2px;
        }

        .continue-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--purple);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .continue-btn:hover {
            color: var(--gold);
        }

        .continue-btn i {
            transition: transform 0.3s ease;
        }

        .continue-btn:hover i {
            transform: translateX(3px);
        }

        /* ===== CLASS TILES - RESPONSIVE ===== */
        .class-tiles {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            margin-bottom: 30px;
        }

        .class-tile {
            background: white;
            padding: 15px 5px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--purple);
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .class-tile:hover {
            background: var(--purple);
            color: white;
            transform: translateY(-2px);
            border-color: var(--gold);
            box-shadow: var(--shadow-md);
        }

        .class-tile i {
            font-size: 1.3rem;
            color: var(--gold);
        }

        .class-tile:hover i {
            color: white;
        }

        /* ===== ACTIVITY CARD ===== */
        .activity-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }

        .activity-timeline {
            margin-top: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,184,0,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .activity-details {
            flex: 1;
            min-width: 0;
        }

        .activity-action {
            color: var(--purple);
            font-weight: 500;
            margin-bottom: 3px;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-time {
            color: #999;
            font-size: 0.8rem;
        }

        .no-activity {
            text-align: center;
            color: #999;
            padding: 30px 15px;
        }

        .no-activity i {
            font-size: 2.5rem;
            color: var(--gold);
            margin-bottom: 10px;
            opacity: 0.5;
        }

        /* ===== FAMILY SECTION ===== */
        .family-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
        }

        .family-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .family-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f4f8;
            border-radius: 10px;
            transition: var(--transition);
        }

        .family-card:hover {
            transform: translateX(3px);
            box-shadow: var(--shadow-sm);
        }

        .family-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .family-info {
            flex: 1;
            min-width: 0;
        }

        .family-info h4 {
            color: var(--purple);
            margin-bottom: 3px;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .family-info p {
            color: var(--gray);
            font-size: 0.8rem;
            margin-bottom: 3px;
        }

        .family-progress {
            font-size: 0.75rem;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */

        /* Large Tablets (1024px and below) */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .lessons-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .class-tiles {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Tablets (768px and below) */
        @media (max-width: 768px) {
            .dashboard-nav {
                padding: 10px 15px;
            }
            
            .user-greeting span {
                display: none;
            }
            
            .user-greeting i {
                font-size: 1.2rem;
            }
            
            .dashboard-container {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                width: 100%;
                position: static;
                height: auto;
                margin-bottom: 20px;
            }
            
            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 5px;
                padding: 10px;
            }
            
            .sidebar-menu a {
                padding: 10px;
                justify-content: center;
                text-align: center;
                border-left: none;
                border-bottom: 2px solid transparent;
                font-size: 0.8rem;
            }
            
            .sidebar-menu a:hover {
                border-left-color: transparent;
                border-bottom-color: var(--gold);
            }
            
            .sidebar-menu i {
                margin-right: 0;
            }
            
            .sidebar-header {
                display: none;
            }
            
            .stats-grid {
                gap: 15px;
            }
            
            .class-tiles {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .family-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile Phones (480px and below) */
        @media (max-width: 480px) {
        /* Make sidebar full width and vertical */
        .dashboard-sidebar {
            width: 100%;
            position: static;
            margin-bottom: 20px;
        }
        
        /* KEEP MENU VERTICAL - like screenshot */
        /* .sidebar-menu {
            display: block; 
            padding: 10px 0;
        } */
        
        .sidebar-menu li {
            width: 100%;
        }
        
        .sidebar-menu a {
            padding: 15px 20px;
            font-size: 1rem;
            border-left: 4px solid transparent;
        }
        
        /* Top navigation adjustments */
        /* .dashboard-nav {
            flex-direction: column;
            align-items: stretch;
            padding: 10px;
        } */
        
        /* Stats grid - single column */
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        /* Class tiles - 2 columns is fine */
        .class-tiles {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        /* Welcome section */
        .welcome-section {
            flex-direction: column;
            text-align: center;
            padding: 20px 15px;
        }
        
        .streak-badge {
            margin-top: 10px;
        }
        
        /* Subscription banner */
        .subscription-banner {
            flex-direction: column;
            text-align: center;
            padding: 20px 15px;
        }
        
        .banner-content {
            flex-direction: column;
        }
        
        .btn-banner {
            width: 100%;
        }
        
        /* Activity items */
        .activity-item {
            padding: 12px 0;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
        }
        
        /* Section titles */
        .section-title {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }

        /* Small Mobile (360px and below) */
        @media (max-width: 360px) {
            .user-menu {
                flex-direction: column;
            }
            
            .user-greeting {
                width: 100%;
                justify-content: center;
            }
            
            .dropbtn {
                justify-content: center;
            }
            
            .class-tiles {
                grid-template-columns: 1fr;
            }
            
            .sidebar-menu {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="dashboard-nav">
        <div class="logo-area">
            <a href="index.php">
                <img src="images/logo-3.png" alt="RAYS OF GRACE Junior School">
            </a>
        </div>
        
        <div class="user-menu">
            <div class="user-greeting">
                <i class="fas fa-user-circle"></i>
                <span><?php 
                    // Get initials from fullname
                    $nameParts = explode(' ', trim($user['fullname']));
                    $initials = '';
                    foreach ($nameParts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    }
                    // Show first 2 initials max
                    echo substr($initials, 0, 2);
                ?></span>
            </div>
            
            <div class="dropdown">
                <button class="dropbtn">
                    <i class="fas fa-cog"></i> <span>Menu</span>
                </button>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <!-- <h3>Menu</h3> -->
                <p>Dashboard</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="lessons.php">
                        <i class="fas fa-book-open"></i>
                        <span>My Lessons</span>
                    </a>
                </li>
                <li>
                    <a href="quizzes.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Quizzes</span>
                    </a>
                </li>
                <li>
                    <a href="progress.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress</span>
                    </a>
                </li>
                <li>
                    <a href="downloads.php">
                        <i class="fas fa-download"></i>
                        <span>Downloads</span>
                    </a>
                </li>
                <li>
                    <a href="help.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Subscription Banner -->
            <?php if (!$hasAccess): ?>
            <div class="subscription-banner">
                <div class="banner-content">
                    <i class="fas fa-crown"></i>
                    <div class="banner-text">
                        <h3>No Active Subscription</h3>
                        <p>Subscribe now to access all <?php echo $totalLessons; ?> lessons</p>
                    </div>
                </div>
                <a href="pricing.php" class="btn-banner">
                    <i class="fas fa-rocket"></i> View Plans
                </a>
            </div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo explode(' ', $user['fullname'])[0]; ?>!</h1>
                    <p><i class="fas fa-calendar-check"></i> Continue your learning journey</p>
                </div>
                <div class="streak-badge">
                    <i class="fas fa-fire"></i>
                    <span>5 Day Streak!</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $completedCount; ?></div>
                        <div class="stat-label">Completed</div>
                        <div class="stat-progress">
                            <div class="stat-progress-bar" style="width: <?php echo $totalLessons > 0 ? ($completedCount/$totalLessons)*100 : 0; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo count($inProgress); ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $avgScore; ?>%</div>
                        <div class="stat-label">Quiz Score</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $subscription ? round((strtotime($subscription['end_date'])-time())/86400) : 0; ?></div>
                        <div class="stat-label">Days Left</div>
                    </div>
                </div>
            </div>

            <!-- Continue Learning -->
            <?php if (!empty($inProgress)): ?>
            <div class="section-title">
                <h2><i class="fas fa-play-circle"></i> Continue Learning</h2>
                <a href="lessons.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="lessons-grid">
                <?php foreach ($inProgress as $lesson): ?>
                <div class="lesson-card">
                    <div class="lesson-thumb">
                        <i class="fas fa-play-circle"></i>
                        <span class="class-badge"><?php echo $lesson['class']; ?></span>
                    </div>
                    <div class="lesson-info">
                        <h3><?php echo htmlspecialchars($lesson['topic']); ?></h3>
                        <p><?php echo $lesson['subject']; ?></p>
                        
                        <div class="progress-container">
                            <div class="progress-header">
                                <span>Progress</span>
                                <span><?php echo $lesson['progress'] ?? 0; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $lesson['progress'] ?? 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="continue-btn">
                            <span>Continue</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Quick Access by Class -->
            <div class="section-title">
                <h2><i class="fas fa-graduation-cap"></i> Quick Access</h2>
            </div>

            <div class="class-tiles">
                <?php 
                $classes = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];
                foreach ($classes as $class): 
                ?>
                <a href="lessons.php?class=<?php echo $class; ?>" class="class-tile">
                    <i class="fas fa-book"></i>
                    <span><?php echo $class; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Recent Activity -->
            <div class="activity-card">
                <div class="section-title">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                
                <div class="activity-timeline">
                    <?php if (empty($activities)): ?>
                        <div class="no-activity">
                            <i class="fas fa-clock"></i>
                            <p>No recent activity. Start learning!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?php 
                                    echo strpos($activity['action'], 'lesson') !== false ? 'fa-book' : 
                                        (strpos($activity['action'], 'quiz') !== false ? 'fa-question' : 
                                        (strpos($activity['action'], 'login') !== false ? 'fa-sign-in-alt' : 'fa-circle')); 
                                ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-action"><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></div>
                                <div class="activity-time"><?php echo timeAgo($activity['created_at']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Family Members (if parent) -->
            <?php if (!empty($children)): ?>
            <div class="family-section">
                <div class="section-title">
                    <h2><i class="fas fa-users"></i> Family Members</h2>
                </div>
                
                <div class="family-grid">
                    <?php foreach ($children as $child): ?>
                    <div class="family-card">
                        <div class="family-avatar">
                            <?php echo strtoupper(substr($child['fullname'], 0, 1)); ?>
                        </div>
                        <div class="family-info">
                            <h4><?php echo htmlspecialchars($child['fullname']); ?></h4>
                            <p><?php echo $child['class'] ?? 'No class'; ?></p>
                            <div class="family-progress">
                                <i class="fas fa-chart-line"></i>
                                <span>In progress</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="js/navbar.js"></script>
</body>
</html>