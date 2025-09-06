<?php
session_start();

// Check if config exists
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/../config/config.php';

// Log access attempt for security
$log_entry = date('Y-m-d H:i:s') . " - Super Admin login attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
file_put_contents(__DIR__ . '/../logs/saportal_access.log', $log_entry, FILE_APPEND | LOCK_EX);

// If already logged in as super admin, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'super_admin') {
    header('Location: /saportal/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Portal — RCE</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container saportal-auth">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Super Admin Portal</h1>
                <p class="auth-subtitle">Secure Access Required</p>
            </div>
            
            <form id="saLoginForm" class="auth-form">
                <div class="form-group">
                    <label for="sa_username">Username</label>
                    <input type="text" id="sa_username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="sa_password">Password</label>
                    <input type="password" id="sa_password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Secure Login</button>
                
                <div id="saLoginError" class="error-message" style="display: none;"></div>
            </form>
            
            <div class="auth-footer">
                <p class="security-notice">This is a secure area. All access attempts are logged.</p>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/http.js"></script>
    <script>
        document.getElementById('saLoginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('portal', 'saportal');
            
            try {
                const response = await httpRequest('/api/auth/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.success) {
                    window.location.href = '/saportal/';
                } else {
                    document.getElementById('saLoginError').textContent = response.message || 'Invalid credentials';
                    document.getElementById('saLoginError').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('saLoginError').textContent = 'Login failed. Please try again.';
                document.getElementById('saLoginError').style.display = 'block';
            }
        });
    </script>
</body>
</html>
