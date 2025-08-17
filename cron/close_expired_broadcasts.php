#!/usr/bin/env php
<?php
/**
 * Close Expired Broadcasts Cron Job
 * Closes event broadcasts that have exceeded their active window
 * 
 * Usage: php close_expired_broadcasts.php
 * Cron: 0,15,30,45 * * * * /usr/bin/php /path/to/close_expired_broadcasts.php
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
    $logFile = __DIR__ . '/../logs/cron_broadcasts.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if running interactively
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

try {
    logMessage("Starting expired broadcasts cleanup job");
    
    // Get database connection
    $db = DB::getInstance();
    $connection = $db->getConnection();
    
    // Find broadcasts that should be expired
    $findExpiredSql = "
        SELECT 
            id, 
            client_id, 
            title, 
            status,
            expires_at,
            response_deadline,
            event_date,
            created_at
        FROM event_broadcasts 
        WHERE status IN ('active', 'paused')
        AND (
            expires_at <= NOW() 
            OR response_deadline <= NOW()
            OR event_date <= NOW() - INTERVAL 1 DAY
        )
    ";
    
    $expiredBroadcasts = $db->query($findExpiredSql);
    $expiredCount = count($expiredBroadcasts);
    
    if ($expiredCount === 0) {
        logMessage("No expired broadcasts found");
    } else {
        logMessage("Found {$expiredCount} expired broadcasts");
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            $processedCount = 0;
            
            foreach ($expiredBroadcasts as $broadcast) {
                // Determine expiry reason
                $now = new DateTime();
                $expiresAt = $broadcast['expires_at'] ? new DateTime($broadcast['expires_at']) : null;
                $responseDeadline = $broadcast['response_deadline'] ? new DateTime($broadcast['response_deadline']) : null;
                $eventDate = new DateTime($broadcast['event_date']);
                
                $expiredReason = '';
                if ($expiresAt && $expiresAt <= $now) {
                    $expiredReason = 'expires_at reached';
                } elseif ($responseDeadline && $responseDeadline <= $now) {
                    $expiredReason = 'response_deadline reached';
                } elseif ($eventDate <= $now->sub(new DateInterval('P1D'))) {
                    $expiredReason = 'event_date passed';
                }
                
                // Update broadcast status to expired
                $updateSql = "
                    UPDATE event_broadcasts 
                    SET status = 'expired', updated_at = NOW()
                    WHERE id = :id
                ";
                
                $db->execute($updateSql, [':id' => $broadcast['id']]);
                
                // Get response statistics
                $responseStatsSql = "
                    SELECT 
                        response,
                        COUNT(*) as count
                    FROM event_responses 
                    WHERE broadcast_id = :broadcast_id
                    GROUP BY response
                ";
                
                $responseStats = $db->query($responseStatsSql, [':broadcast_id' => $broadcast['id']]);
                $statsText = [];
                foreach ($responseStats as $stat) {
                    $statsText[] = "{$stat['response']}: {$stat['count']}";
                }
                $statsString = implode(', ', $statsText);
                
                // Log the expiry
                logMessage("Expired broadcast ID {$broadcast['id']} '{$broadcast['title']}' (reason: {$expiredReason}, responses: {$statsString})");
                
                // Create audit log entry
                $auditSql = "
                    INSERT INTO audit_logs (
                        user_id, company_id, action, entity_type, entity_id, 
                        old_values, new_values, ip_address, created_at
                    ) VALUES (
                        NULL, NULL, 'broadcast_expired', 'event_broadcasts', :entity_id,
                        :old_values, :new_values, 'system-cron', NOW()
                    )
                ";
                
                $oldValues = json_encode(['status' => $broadcast['status']]);
                $newValues = json_encode([
                    'status' => 'expired',
                    'expired_reason' => $expiredReason,
                    'response_stats' => $statsString
                ]);
                
                $db->execute($auditSql, [
                    ':entity_id' => $broadcast['id'],
                    ':old_values' => $oldValues,
                    ':new_values' => $newValues
                ]);
                
                $processedCount++;
            }
            
            // Also check for broadcasts that should be automatically closed due to max talents reached
            $autoCloseSql = "
                SELECT 
                    eb.id,
                    eb.title,
                    eb.max_talents,
                    COUNT(er.id) as accepted_count
                FROM event_broadcasts eb
                LEFT JOIN event_responses er ON eb.id = er.broadcast_id AND er.response = 'accept'
                WHERE eb.status = 'active'
                AND eb.max_talents > 0
                GROUP BY eb.id, eb.title, eb.max_talents
                HAVING accepted_count >= eb.max_talents
            ";
            
            $autoCloseList = $db->query($autoCloseSql);
            
            foreach ($autoCloseList as $broadcast) {
                $updateSql = "
                    UPDATE event_broadcasts 
                    SET status = 'closed', updated_at = NOW()
                    WHERE id = :id
                ";
                
                $db->execute($updateSql, [':id' => $broadcast['id']]);
                
                logMessage("Auto-closed broadcast ID {$broadcast['id']} '{$broadcast['title']}' (max talents {$broadcast['max_talents']} reached)");
                
                // Create audit log entry
                $auditSql = "
                    INSERT INTO audit_logs (
                        user_id, company_id, action, entity_type, entity_id, 
                        old_values, new_values, ip_address, created_at
                    ) VALUES (
                        NULL, NULL, 'broadcast_auto_closed', 'event_broadcasts', :entity_id,
                        :old_values, :new_values, 'system-cron', NOW()
                    )
                ";
                
                $oldValues = json_encode(['status' => 'active']);
                $newValues = json_encode([
                    'status' => 'closed',
                    'reason' => 'max_talents_reached',
                    'accepted_count' => $broadcast['accepted_count']
                ]);
                
                $db->execute($auditSql, [
                    ':entity_id' => $broadcast['id'],
                    ':old_values' => $oldValues,
                    ':new_values' => $newValues
                ]);
                
                $processedCount++;
            }
            
            // Clean up old expired broadcasts (older than 90 days)
            $cleanupSql = "
                DELETE FROM event_broadcasts 
                WHERE status = 'expired' 
                AND updated_at <= NOW() - INTERVAL 90 DAY
            ";
            
            $cleanedCount = $db->execute($cleanupSql);
            
            if ($cleanedCount > 0) {
                logMessage("Cleaned up {$cleanedCount} old expired broadcasts (older than 90 days)");
            }
            
            // Commit transaction
            $db->commit();
            
            logMessage("Successfully processed {$processedCount} broadcasts");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            throw $e;
        }
    }
    
    // Check for broadcasts that need reminders (response deadline approaching)
    $reminderSql = "
        SELECT 
            eb.id,
            eb.title,
            eb.client_id,
            eb.response_deadline,
            COUNT(et.id) as targets_sent,
            COUNT(er.id) as responses_received
        FROM event_broadcasts eb
        LEFT JOIN event_targets et ON eb.id = et.broadcast_id
        LEFT JOIN event_responses er ON eb.id = er.broadcast_id
        WHERE eb.status = 'active'
        AND eb.response_deadline IS NOT NULL
        AND eb.response_deadline BETWEEN NOW() AND NOW() + INTERVAL 24 HOUR
        AND eb.response_deadline > NOW()
        GROUP BY eb.id, eb.title, eb.client_id, eb.response_deadline
        HAVING targets_sent > responses_received
    ";
    
    $reminderBroadcasts = $db->query($reminderSql);
    
    foreach ($reminderBroadcasts as $broadcast) {
        $hoursLeft = round((strtotime($broadcast['response_deadline']) - time()) / 3600, 1);
        logMessage("Reminder: Broadcast ID {$broadcast['id']} '{$broadcast['title']}' deadline in {$hoursLeft} hours ({$broadcast['responses_received']}/{$broadcast['targets_sent']} responses)", 'WARNING');
        
        // Here you could implement email notifications to clients
        // or push notifications to talent who haven't responded yet
    }
    
    // Clean up old log entries (keep last 30 days)
    $logFile = __DIR__ . '/../logs/cron_broadcasts.log';
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
    logMessage("Broadcast cleanup job completed successfully in {$executionTime}ms");
    
    exit(0);
    
} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), 'ERROR');
    exit(1);
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
