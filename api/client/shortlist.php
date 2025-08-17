<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

try {
    // Check if user is logged in and has client role
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Check if config exists
    if (!file_exists(__DIR__ . '/../../config/config.php')) {
        throw new Exception('System not configured. Please run setup first.');
    }

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../models/DB.php';

    $db = DB::getInstance();
    $clientId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add to shortlist
        $talentId = intval($_POST['talent_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($talentId <= 0) {
            throw new Exception('Invalid talent ID');
        }

        // Check if talent exists
        $talentCheck = $db->queryOne(
            "SELECT id FROM talent_profiles WHERE id = :talent_id",
            [':talent_id' => $talentId]
        );

        if (!$talentCheck) {
            throw new Exception('Talent not found');
        }

        // Check if already in shortlist
        $existing = $db->queryOne(
            "SELECT id FROM client_shortlist WHERE client_id = :client_id AND talent_id = :talent_id",
            [':client_id' => $clientId, ':talent_id' => $talentId]
        );

        if ($existing) {
            // Update existing entry
            $db->execute(
                "UPDATE client_shortlist SET notes = :notes, is_active = 1, updated_at = NOW() WHERE id = :id",
                [':notes' => $notes, ':id' => $existing['id']]
            );
        } else {
            // Insert new entry
            $db->execute(
                "INSERT INTO client_shortlist (client_id, talent_id, notes, is_active, created_at) VALUES (:client_id, :talent_id, :notes, 1, NOW())",
                [':client_id' => $clientId, ':talent_id' => $talentId, ':notes' => $notes]
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Added to shortlist successfully'
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Remove from shortlist
        $talentId = intval($_GET['talent_id'] ?? 0);

        if ($talentId <= 0) {
            throw new Exception('Invalid talent ID');
        }

        $result = $db->execute(
            "UPDATE client_shortlist SET is_active = 0, updated_at = NOW() WHERE client_id = :client_id AND talent_id = :talent_id",
            [':client_id' => $clientId, ':talent_id' => $talentId]
        );

        if ($result > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Removed from shortlist successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found in shortlist'
            ]);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Shortlist API error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
