<?php
session_start();

// Check if config exists
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/config/config.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'client';
    switch ($role) {
        case 'super_admin':
            header('Location: /saportal/');
            break;
        case 'tenant_admin':
            header('Location: /admin/');
            break;
        case 'talent':
            header('Location: /talent/');
            break;
        case 'client':
        default:
            header('Location: /client/feed');
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Luxury Talent Booking RCE</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <?php include __DIR__ . '/views/auth/login.php'; ?>
    </div>
    
    <script src="/assets/js/http.js"></script>
    <script src="/assets/js/ui.js"></script>
</body>
</html>
