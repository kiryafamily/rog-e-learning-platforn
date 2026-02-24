<?php
// settings.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - RAYS OF GRACE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 800px; margin: 50px auto; padding: 30px; background: white; border-radius: 10px;">
        <h1 style="color: #4B1C3C;">Settings</h1>
        <p style="color: #666;">Settings page is under construction. Check back soon!</p>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
</html>