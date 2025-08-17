<?php
session_start();

// Check if config exists, if not redirect to setup
if (!file_exists(__DIR__ . '/config/config.php')) {
    if (!file_exists(__DIR__ . '/setup/index.php')) {
        die('Setup files missing. Please re-upload the application.');
    }
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Talent Booking — Red Carpet Edition</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#000000">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/views/public/home.php'; ?>
    </div>
    
    <script src="/assets/js/ui.js"></script>
</body>
</html>
