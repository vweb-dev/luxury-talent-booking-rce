#!/usr/bin/env php
<?php
/**
 * Cleanup Status Posts Cron Job
 * Removes expired status posts (older than 24 hours)
 * 
 * Usage: php cleanup_status.php
 * Cron: 0 * * * * /usr/bin/php /path/to/cleanup_status.php
 */

// Set script start time
$startTime = microtime(true);

// Change to script directory
chdir(__DIR__);

// Check if config exists
if (!file_exists(__DIR__ . '/../config/config.php')) {
    echo "Error: Configuration not found. Please run setup first.\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/DB.php';

// Log function
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Write to log file
    $logFile = __DIR__ . '/../logs/cron_cleanup_status.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if running interactively
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

try {
    logMessage("Starting status posts cleanup job");
    
    // Get database connection
    $db = DB::getInstance();
    $connection = $db->getConnection();
    
    // Find expired status posts
    $findExpiredSql = "
        SELECT id, user_id, content, created_at, expires_at
        FROM status_posts 
        WHERE expires_at <= NOW() 
        AND is_active = 1
    ";
    
    $expiredPosts = $db->query($findExpiredSql);
    $expiredCount = count($expiredPosts);
    
    if ($expiredCount === 0) {
        logMessage("No expired status posts found");
    } else {
        logMessage("Found {$expiredCount} expired status posts");
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Mark expired posts as inactive
            $updateSql = "
                UPDATE status_posts 
                SET is_active = 0, updated_at = NOW()
                WHERE expires_at <= NOW() 
                AND is_active = 1
            ";
            
            $updatedRows = $db->execute($updateSql);
            
            // Log each expired post for audit purposes
            foreach ($expiredPosts as $post) {
                logMessage("Expired status post ID {$post['id']} from user {$post['user_id']} (created: {$post['created_at']}, expired: {$post['expires_at']})");
            }
            
            // Optional: Delete media files associated with expired posts
            $mediaCleanupSql = "
                SELECT media_url 
                FROM status_posts 
                WHERE expires_at <= NOW() - INTERVAL 7 DAY
                AND is_active = 0
                AND media_url IS NOT NULL
            ";
            
            $mediaToDelete = $db->query($mediaCleanupSql);
            $mediaDeletedCount = 0;
            
            foreach ($mediaToDelete as $media) {
                $filePath = __DIR__ . '/../' . ltrim($media['media_url'], '/');
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $mediaDeletedCount++;
                        logMessage("Deleted media file: {$filePath}");
                    } else {
                        logMessage("Failed to delete media file: {$filePath}", 'WARNING');
                    }
                }
            }
            
            // Delete very old inactive status posts (older than 30 days)
            $deleteSql = "
                DELETE FROM status_posts 
                WHERE expires_at <= NOW() - INTERVAL 30 DAY
                AND is_active = 0
            ";
            
            $deletedRows = $db->execute($deleteSql);
            
            // Commit transaction
            $db->commit();
            
            logMessage("Successfully processed {$updatedRows} expired posts, deleted {$mediaDeletedCount} media files, and permanently removed {$deletedRows} old posts");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            throw $e;
        }
    }
    
    // Clean up old log entries (keep last 30 days)
    $logFile = __DIR__ . '/../logs/cron_cleanup_status.log';
    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
        $lines = file($logFile);
        $cutoffDate = date('Y-m-d', strtotime('-30 days'));
        $filteredLines = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                if ($matches[1] >= $cutoffDate) {
                    $filteredLines[] = $line;
                }
            }
        }
        
        file_put_contents($logFile, implode('', $filteredLines));
        logMessage("Cleaned up old log entries, kept " . count($filteredLines) . " recent entries");
    }
    
    // Calculate execution time
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    logMessage("Status cleanup job completed successfully in {$executionTime}ms");
    
    exit(0);
    
} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), 'ERROR');
    exit(1);
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
