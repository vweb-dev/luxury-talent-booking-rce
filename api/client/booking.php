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
        // Create new booking
        $input = json_decode(file_get_contents('php://input'), true);
        
        $talentId = intval($input['talent_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $startDate = $input['start_date'] ?? '';
        $endDate = $input['end_date'] ?? '';
        $location = trim($input['location'] ?? '');
        $rateAmount = floatval($input['rate_amount'] ?? 0);
        $rateType = $input['rate_type'] ?? 'hourly';
        $requirements = $input['requirements'] ?? [];

        // Validation
        if ($talentId <= 0) throw new Exception('Invalid talent ID');
        if (empty($title)) throw new Exception('Title is required');
        if (empty($startDate)) throw new Exception('Start date is required');
        if ($rateAmount <= 0) throw new Exception('Rate amount must be greater than 0');

        // Validate dates
        $startDateTime = new DateTime($startDate);
        $endDateTime = $endDate ? new DateTime($endDate) : null;
        
        if ($startDateTime <= new DateTime()) {
            throw new Exception('Start date must be in the future');
        }
        
        if ($endDateTime && $endDateTime <= $startDateTime) {
            throw new Exception('End date must be after start date');
        }

        // Check if talent exists
        $talent = $db->queryOne(
            "SELECT tp.id, u.name FROM talent_profiles tp JOIN users u ON tp.user_id = u.id WHERE tp.id = :talent_id",
            [':talent_id' => $talentId]
        );

        if (!$talent) {
            throw new Exception('Talent not found');
        }

        // Calculate total amount
        $totalAmount = $rateAmount;
        if ($rateType === 'hourly' && $endDateTime) {
            $hours = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 3600;
            $totalAmount = $rateAmount * $hours;
        }

        // Get commission rate (default 15%)
        $commissionRate = 15.0;
        $commissionAmount = $totalAmount * ($commissionRate / 100);

        // Insert booking
        $insertSql = "
            INSERT INTO bookings (
                client_id, talent_id, title, description, booking_type, status,
                start_date, end_date, location, rate_amount, rate_type, 
                total_amount, commission_rate, commission_amount, requirements,
                created_at
            ) VALUES (
                :client_id, :talent_id, :title, :description, 'direct', 'pending',
                :start_date, :end_date, :location, :rate_amount, :rate_type,
                :total_amount, :commission_rate, :commission_amount, :requirements,
                NOW()
            )
        ";

        $bookingId = $db->execute($insertSql, [
            ':client_id' => $clientId,
            ':talent_id' => $talentId,
            ':title' => $title,
            ':description' => $description,
            ':start_date' => $startDateTime->format('Y-m-d H:i:s'),
            ':end_date' => $endDateTime ? $endDateTime->format('Y-m-d H:i:s') : null,
            ':location' => $location,
            ':rate_amount' => $rateAmount,
            ':rate_type' => $rateType,
            ':total_amount' => $totalAmount,
            ':commission_rate' => $commissionRate,
            ':commission_amount' => $commissionAmount,
            ':requirements' => json_encode($requirements)
        ]);

        $bookingId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $bookingId,
            'total_amount' => $totalAmount,
            'commission_amount' => $commissionAmount
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get client's bookings
        $status = $_GET['status'] ?? 'all';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $whereClause = "WHERE b.client_id = :client_id";
        $params = [':client_id' => $clientId];

        if ($status !== 'all') {
            $whereClause .= " AND b.status = :status";
            $params[':status'] = $status;
        }

        $sql = "
            SELECT 
                b.id,
                b.title,
                b.description,
                b.status,
                b.start_date,
                b.end_date,
                b.location,
                b.rate_amount,
                b.rate_type,
                b.total_amount,
                b.created_at,
                tp.stage_name as talent_name,
                u.name as talent_real_name
            FROM bookings b
            JOIN talent_profiles tp ON b.talent_id = tp.id
            JOIN users u ON tp.user_id = u.id
            {$whereClause}
            ORDER BY b.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM bookings b {$whereClause}";
        $countStmt = $db->getConnection()->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            'success' => true,
            'bookings' => $bookings,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_count' => $totalCount,
                'limit' => $limit
            ]
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Booking API error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
