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

    // Get filter parameters
    $location = $_GET['location'] ?? '';
    $category = $_GET['category'] ?? '';
    $experience = $_GET['experience'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Get database connection
    $db = DB::getInstance()->getConnection();

    // Build WHERE clause for filters
    $whereConditions = [
        "ma.status = 'approved'",
        "tp.privacy_level IN ('public', 'partial')",
        "tm.is_active = 1"
    ];
    $params = [];

    if (!empty($location)) {
        $whereConditions[] = "tp.location LIKE :location";
        $params[':location'] = '%' . $location . '%';
    }

    if (!empty($category)) {
        $whereConditions[] = "tp.category = :category";
        $params[':category'] = $category;
    }

    if (!empty($experience)) {
        switch ($experience) {
            case 'beginner':
                $whereConditions[] = "tp.years_experience <= 2";
                break;
            case 'intermediate':
                $whereConditions[] = "tp.years_experience BETWEEN 3 AND 5";
                break;
            case 'experienced':
                $whereConditions[] = "tp.years_experience BETWEEN 6 AND 10";
                break;
            case 'expert':
                $whereConditions[] = "tp.years_experience > 10";
                break;
        }
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Query for client talent feed
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
            tp.category,
            tp.years_experience,
            tp.height,
            tp.hair_color,
            tp.eye_color,
            u.name as talent_name
        FROM talent_media tm
        JOIN talent_profiles tp ON tm.talent_id = tp.id
        JOIN users u ON tp.user_id = u.id
        JOIN media_approvals ma ON tm.id = ma.media_id
        WHERE {$whereClause}
        ORDER BY tm.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get client's shortlist
    $shortlistSql = "
        SELECT 
            tp.id,
            tp.stage_name as name,
            tp.location,
            u.avatar_url as avatar
        FROM client_shortlist cs
        JOIN talent_profiles tp ON cs.talent_id = tp.id
        JOIN users u ON tp.user_id = u.id
        WHERE cs.client_id = :client_id
        AND cs.is_active = 1
        ORDER BY cs.created_at DESC
    ";

    $shortlistStmt = $db->prepare($shortlistSql);
    $shortlistStmt->bindValue(':client_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $shortlistStmt->execute();
    $shortlist = $shortlistStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countSql = "
        SELECT COUNT(*) as total
        FROM talent_media tm
        JOIN talent_profiles tp ON tm.talent_id = tp.id
        JOIN media_approvals ma ON tm.id = ma.media_id
        WHERE {$whereClause}
    ";

    $countStmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // If no data found, return demo data for development
    if (empty($reels)) {
        $reels = [
            [
                'id' => 1,
                'title' => 'Elite Fashion Model',
                'description' => 'International runway experience, available for high-end fashion shoots and events',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-1.svg',
                'talent_id' => 1,
                'stage_name' => 'Sarah Chen',
                'location' => 'New York',
                'category' => 'model',
                'years_experience' => 8,
                'height' => '5\'9"',
                'hair_color' => 'Black',
                'eye_color' => 'Brown',
                'talent_name' => 'Sarah Chen'
            ],
            [
                'id' => 2,
                'title' => 'Professional Dancer',
                'description' => 'Contemporary, jazz, and commercial dance specialist with Broadway experience',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-2.svg',
                'talent_id' => 2,
                'stage_name' => 'Marcus Johnson',
                'location' => 'Los Angeles',
                'category' => 'dancer',
                'years_experience' => 12,
                'height' => '6\'0"',
                'hair_color' => 'Black',
                'eye_color' => 'Brown',
                'talent_name' => 'Marcus Johnson'
            ],
            [
                'id' => 3,
                'title' => 'Versatile Actor',
                'description' => 'Film, TV, and theater actor with extensive range and professional training',
                'type' => 'image',
                'url' => '/assets/img/demo-talent-3.svg',
                'talent_id' => 3,
                'stage_name' => 'Emma Rodriguez',
                'location' => 'Chicago',
                'category' => 'actor',
                'years_experience' => 6,
                'height' => '5\'6"',
                'hair_color' => 'Brown',
                'eye_color' => 'Green',
                'talent_name' => 'Emma Rodriguez'
            ]
        ];
        $totalCount = count($reels);
    }

    if (empty($shortlist)) {
        $shortlist = [
            [
                'id' => 1,
                'name' => 'Sarah Chen',
                'location' => 'New York',
                'avatar' => ''
            ],
            [
                'id' => 4,
                'name' => 'David Kim',
                'location' => 'Miami',
                'avatar' => ''
            ]
        ];
    }

    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;

    // Return response
    echo json_encode([
        'success' => true,
        'reels' => $reels,
        'shortlist' => $shortlist,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'limit' => $limit,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ],
        'filters' => [
            'location' => $location,
            'category' => $category,
            'experience' => $experience
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in client feed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in client feed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
