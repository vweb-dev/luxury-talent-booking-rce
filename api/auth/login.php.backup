<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();

try {
    // Check if config exists
    if (!file_exists(__DIR__ . '/../../config/config.php')) {
        throw new Exception('System not configured. Please run setup first.');
    }

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../models/DB.php';
    require_once __DIR__ . '/../../models/User.php';

    // Get input data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $portal = $_POST['portal'] ?? 'default';
    $security_token = $_POST['security_token'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }

    // Special handling for super admin portal
    if ($portal === 'saportal') {
        if (empty($security_token)) {
            throw new Exception('Security token is required for super admin access');
        }
        
        // Log the attempt
        $log_entry = date('Y-m-d H:i:s') . " - Super Admin login attempt: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        if (!file_exists(__DIR__ . '/../../logs')) {
            mkdir(__DIR__ . '/../../logs', 0755, true);
        }
        file_put_contents(__DIR__ . '/../../logs/saportal_access.log', $log_entry, FILE_APPEND | LOCK_EX);
    }

    // Authenticate user
    $user = User::authenticate($username, $password, $portal, $security_token);
    
    if (!$user) {
        // Log failed attempt for super admin
        if ($portal === 'saportal') {
            $log_entry = date('Y-m-d H:i:s') . " - FAILED Super Admin login: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
            file_put_contents(__DIR__ . '/../../logs/saportal_access.log', $log_entry, FILE_APPEND | LOCK_EX);
        }
        throw new Exception('Invalid credentials');
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['company_id'] = $user['company_id'];
    $_SESSION['login_time'] = time();

    // Determine redirect URL based on role
    $redirect_urls = [
        'super_admin' => '/saportal/',
        'tenant_admin' => '/admin/',
        'talent' => '/talent/',
        'client' => '/client/feed'
    ];

    $redirect_url = $redirect_urls[$user['role']] ?? '/client/feed';

    // Log successful super admin login
    if ($portal === 'saportal') {
        $log_entry = date('Y-m-d H:i:s') . " - SUCCESS Super Admin login: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        file_put_contents(__DIR__ . '/../../logs/saportal_access.log', $log_entry, FILE_APPEND | LOCK_EX);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect_url' => $redirect_url,
        'role' => $user['role']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
