<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if config exists
    if (!file_exists(__DIR__ . '/../../config/config.php')) {
        throw new Exception('System not configured. Please run setup first.');
    }

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../models/DB.php';

    // Get pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Get database connection
    $db = DB::getInstance()->getConnection();

    // Query for public talent media (approved only)
    $sql = "
        SELECT 
            tm.id,
            tm.title,
            tm.description,
            tm.media_type as type,
            tm.media_url as url,
            tp.id as talent_id,
            tp.stage_name,
            tp.location,
            u.name as talent_name
        FROM talent_media tm
        JOIN talent_profiles tp ON tm.talent_id = tp.id
        JOIN users u ON tp.user_id = u.id
        JOIN media_approvals ma ON tm.id = ma.media_id
        WHERE ma.status = 'approved' 
        AND tp.privacy_level IN ('public', 'partial')
        AND tm.is_active = 1
        ORDER BY tm.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countSql = "
        SELECT COUNT(*) as total
        FROM talent_media tm
        JOIN talent_profiles tp ON tm.talent_id = tp.id
        JOIN media_approvals ma ON tm.id = ma.media_id
        WHERE ma.status = 'approved' 
        AND tp.privacy_level IN ('public', 'partial')
        AND tm.is_active = 1
    ";

    $countStmt = $db->prepare($countSql);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // If no data found, return demo data for development
    if (empty($reels)) {
        $reels = [
            [
                'id' => 1,
                'title' => 'Professional Model',
                'description' => 'Experienced runway and commercial model available for bookings',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-1.svg',
                'talent_id' => 1,
                'stage_name' => 'Demo Model',
                'location' => 'New York',
                'talent_name' => 'Demo User'
            ],
            [
                'id' => 2,
                'title' => 'Dance Performer',
                'description' => 'Contemporary and commercial dance specialist with 10+ years experience',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-2.svg',
                'talent_id' => 2,
                'stage_name' => 'Demo Dancer',
                'location' => 'Los Angeles',
                'talent_name' => 'Demo User 2'
            ],
            [
                'id' => 3,
                'title' => 'Actor & Voice Artist',
                'description' => 'Versatile performer with experience in film, TV, and voice-over work',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-3.svg',
                'talent_id' => 3,
                'stage_name' => 'Demo Actor',
                'location' => 'Chicago',
                'talent_name' => 'Demo User 3'
            ]
        ];
        $totalCount = count($reels);
    }

    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;

    // Return response
    echo json_encode([
        'success' => true,
        'reels' => $reels,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'limit' => $limit,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in public feed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in public feed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
