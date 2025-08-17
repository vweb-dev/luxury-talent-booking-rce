<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

try {
    // Log the logout if it was a super admin
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        $username = $_SESSION['user_name'] ?? 'unknown';
        $log_entry = date('Y-m-d H:i:s') . " - Super Admin logout: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        
        if (!file_exists(__DIR__ . '/../../logs')) {
            mkdir(__DIR__ . '/../../logs', 0755, true);
        }
        file_put_contents(__DIR__ . '/../../logs/saportal_access.log', $log_entry, FILE_APPEND | LOCK_EX);
    }

    // Clear all session data
    $_SESSION = array();

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($is_ajax) {
        // Return JSON response for AJAX requests
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect_url' => '/'
        ]);
    } else {
        // Redirect for regular requests
        header('Location: /');
        exit;
    }

} catch (Exception $e) {
    if ($is_ajax ?? false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Logout failed'
        ]);
    } else {
        // Even if logout fails, redirect to home
        header('Location: /');
        exit;
    }
}
?>
