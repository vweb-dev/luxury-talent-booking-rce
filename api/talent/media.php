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
    // Check if user is logged in and has talent role
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'talent') {
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
    require_once __DIR__ . '/../../controllers/MediaService.php';

    $db = DB::getInstance();
    $userId = $_SESSION['user_id'];

    // Get talent profile ID
    $talentProfile = $db->queryOne(
        "SELECT id FROM talent_profiles WHERE user_id = :user_id",
        [':user_id' => $userId]
    );

    if (!$talentProfile) {
        throw new Exception('Talent profile not found');
    }

    $talentId = $talentProfile['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle media upload
        if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }

        $file = $_FILES['media'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $mediaType = $_POST['type'] ?? 'photo';

        // Validate media file
        $validation = MediaService::validateMedia($file);
        if (!$validation['valid']) {
            throw new Exception('Media validation failed: ' . implode(', ', $validation['errors']));
        }

        // Create upload directory structure
        $uploadDir = __DIR__ . '/../../uploads';
        $subDir = $mediaType === 'video' ? 'videos' : 'photos';
        $targetDir = $uploadDir . '/' . $subDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate unique filename
        $filename = MediaService::generateUniqueFilename($file['name'], $targetDir);
        $targetPath = $targetDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Get media information
        $mediaInfo = $validation['info'];
        $mediaUrl = '/uploads/' . $subDir . '/' . $filename;

        // Normalize media if needed (optional)
        $normalizedUrl = null;
        if (!MediaService::validateAspectRatio($mediaInfo['width'] ?? 0, $mediaInfo['height'] ?? 0)) {
            $normDir = $uploadDir . '/norm';
            if (!is_dir($normDir)) {
                mkdir($normDir, 0755, true);
            }
            
            $normFilename = 'norm_' . $filename;
            $normPath = $normDir . '/' . $normFilename;
            
            if (MediaService::normalizeMedia($targetPath, $normPath, MediaService::getMediaType($targetPath))) {
                $normalizedUrl = '/uploads/norm/' . $normFilename;
            }
        }

        // Insert media record
        $db->beginTransaction();

        try {
            $insertSql = "
                INSERT INTO talent_media (
                    talent_id, title, description, media_type, media_url, 
                    normalized_url, file_size, width, height, aspect_ratio,
                    is_active, created_at
                ) VALUES (
                    :talent_id, :title, :description, :media_type, :media_url,
                    :normalized_url, :file_size, :width, :height, :aspect_ratio,
                    1, NOW()
                )
            ";

            $aspectRatio = ($mediaInfo['width'] && $mediaInfo['height']) 
                ? round($mediaInfo['width'] / $mediaInfo['height'], 3) 
                : null;

            $db->execute($insertSql, [
                ':talent_id' => $talentId,
                ':title' => $title,
                ':description' => $description,
                ':media_type' => $mediaType === 'video' ? 'video' : 'image',
                ':media_url' => $mediaUrl,
                ':normalized_url' => $normalizedUrl,
                ':file_size' => $file['size'],
                ':width' => $mediaInfo['width'] ?? null,
                ':height' => $mediaInfo['height'] ?? null,
                ':aspect_ratio' => $aspectRatio
            ]);

            $mediaId = $db->lastInsertId();

            // Create approval record
            $db->execute(
                "INSERT INTO media_approvals (media_id, status, created_at) VALUES (:media_id, 'pending', NOW())",
                [':media_id' => $mediaId]
            );

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Media uploaded successfully and submitted for approval',
                'media_id' => $mediaId,
                'media_url' => $mediaUrl,
                'normalized_url' => $normalizedUrl
            ]);

        } catch (Exception $e) {
            $db->rollback();
            // Clean up uploaded file on database error
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            if ($normalizedUrl && file_exists($normPath)) {
                unlink($normPath);
            }
            throw $e;
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get talent's media
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                tm.id,
                tm.title,
                tm.description,
                tm.media_type,
                tm.media_url,
                tm.normalized_url,
                tm.width,
                tm.height,
                tm.aspect_ratio,
                tm.is_primary,
                tm.created_at,
                ma.status as approval_status,
                ma.review_notes
            FROM talent_media tm
            LEFT JOIN media_approvals ma ON tm.id = ma.media_id
            WHERE tm.talent_id = :talent_id
            AND tm.is_active = 1
            ORDER BY tm.is_primary DESC, tm.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':talent_id', $talentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM talent_media WHERE talent_id = :talent_id AND is_active = 1";
        $totalCount = $db->queryOne($countSql, [':talent_id' => $talentId])['total'];

        echo json_encode([
            'success' => true,
            'media' => $media,
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
    error_log("Talent media API error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
