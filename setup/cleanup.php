<?php
/**
 * Setup Cleanup Script
 * Safely removes the setup directory after installation
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if setup is actually complete
if (!file_exists(__DIR__ . '/../config/config.php')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Setup not complete']);
    exit;
}

try {
    // Function to recursively delete directory
    function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    // Delete the setup directory
    $setupDir = __DIR__;
    $deleted = deleteDirectory($setupDir);
    
    if ($deleted) {
        echo json_encode([
            'success' => true,
            'message' => 'Setup directory deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete setup directory. Please delete manually for security.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error during cleanup: ' . $e->getMessage()
    ]);
}
?>
