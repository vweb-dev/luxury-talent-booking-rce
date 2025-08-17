<?php

/**
 * Media Service Controller
 * Handles media validation, processing, and 9:16 aspect ratio enforcement
 */
class MediaService {
    
    const ASPECT_RATIO_WIDTH = 9;
    const ASPECT_RATIO_HEIGHT = 16;
    const TARGET_WIDTH = 1080;
    const TARGET_HEIGHT = 1920;
    
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    
    const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB for images
    const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB for videos
    
    /**
     * Validate uploaded media file
     * @param array $file $_FILES array element
     * @return array Validation result
     */
    public static function validateMedia($file) {
        $result = [
            'valid' => false,
            'errors' => [],
            'info' => []
        ];
        
        try {
            // Check if file was uploaded
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                $result['errors'][] = 'No file uploaded or upload failed';
                return $result;
            }
            
            // Check file size
            if ($file['size'] > self::MAX_FILE_SIZE) {
                $result['errors'][] = 'File size exceeds maximum allowed size';
                return $result;
            }
            
            // Get file info
            $fileInfo = self::getFileInfo($file['tmp_name']);
            $result['info'] = $fileInfo;
            
            // Validate file type
            if (!in_array($fileInfo['mime_type'], array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_VIDEO_TYPES))) {
                $result['errors'][] = 'File type not allowed. Supported: JPEG, PNG, WebP, MP4, WebM, MOV';
                return $result;
            }
            
            // Type-specific validation
            if (self::isImage($fileInfo['mime_type'])) {
                $validation = self::validateImage($file, $fileInfo);
            } else {
                $validation = self::validateVideo($file, $fileInfo);
            }
            
            $result['errors'] = array_merge($result['errors'], $validation['errors']);
            $result['info'] = array_merge($result['info'], $validation['info']);
            
            // Check aspect ratio
            if (!empty($fileInfo['width']) && !empty($fileInfo['height'])) {
                $aspectRatioValid = self::validateAspectRatio($fileInfo['width'], $fileInfo['height']);
                if (!$aspectRatioValid) {
                    $result['errors'][] = 'Media must have 9:16 aspect ratio (portrait orientation)';
                }
            }
            
            $result['valid'] = empty($result['errors']);
            
        } catch (Exception $e) {
            $result['errors'][] = 'Media validation failed: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Get file information
     * @param string $filePath
     * @return array
     */
    private static function getFileInfo($filePath) {
        $info = [
            'mime_type' => mime_content_type($filePath),
            'size' => filesize($filePath),
            'width' => null,
            'height' => null,
            'duration' => null
        ];
        
        // Get image dimensions
        if (self::isImage($info['mime_type'])) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $info['width'] = $imageInfo[0];
                $info['height'] = $imageInfo[1];
            }
        }
        
        // Get video dimensions (requires ffmpeg or similar)
        if (self::isVideo($info['mime_type'])) {
            $videoInfo = self::getVideoInfo($filePath);
            $info = array_merge($info, $videoInfo);
        }
        
        return $info;
    }
    
    /**
     * Validate image file
     * @param array $file
     * @param array $fileInfo
     * @return array
     */
    private static function validateImage($file, $fileInfo) {
        $result = ['errors' => [], 'info' => []];
        
        // Check image-specific file size
        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            $result['errors'][] = 'Image size exceeds maximum allowed size (10MB)';
        }
        
        // Validate image can be processed
        $imageResource = null;
        switch ($fileInfo['mime_type']) {
            case 'image/jpeg':
                $imageResource = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imageResource = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $imageResource = @imagecreatefromwebp($file['tmp_name']);
                break;
        }
        
        if (!$imageResource) {
            $result['errors'][] = 'Invalid or corrupted image file';
        } else {
            imagedestroy($imageResource);
        }
        
        return $result;
    }
    
    /**
     * Validate video file
     * @param array $file
     * @param array $fileInfo
     * @return array
     */
    private static function validateVideo($file, $fileInfo) {
        $result = ['errors' => [], 'info' => []];
        
        // Check video-specific file size
        if ($file['size'] > self::MAX_VIDEO_SIZE) {
            $result['errors'][] = 'Video size exceeds maximum allowed size (100MB)';
        }
        
        // Basic video validation (more comprehensive validation would require ffmpeg)
        if (empty($fileInfo['width']) || empty($fileInfo['height'])) {
            $result['errors'][] = 'Could not determine video dimensions';
        }
        
        return $result;
    }
    
    /**
     * Get video information using ffmpeg (if available)
     * @param string $filePath
     * @return array
     */
    private static function getVideoInfo($filePath) {
        $info = ['width' => null, 'height' => null, 'duration' => null];
        
        // Check if ffprobe is available
        $ffprobePath = self::findFFProbe();
        if (!$ffprobePath) {
            return $info;
        }
        
        try {
            // Get video dimensions and duration
            $command = escapeshellcmd($ffprobePath) . ' -v quiet -print_format json -show_format -show_streams ' . escapeshellarg($filePath);
            $output = shell_exec($command);
            
            if ($output) {
                $data = json_decode($output, true);
                
                if (isset($data['streams'])) {
                    foreach ($data['streams'] as $stream) {
                        if ($stream['codec_type'] === 'video') {
                            $info['width'] = $stream['width'] ?? null;
                            $info['height'] = $stream['height'] ?? null;
                            $info['duration'] = $stream['duration'] ?? null;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("FFProbe error: " . $e->getMessage());
        }
        
        return $info;
    }
    
    /**
     * Normalize media to 9:16 aspect ratio
     * @param string $inputPath
     * @param string $outputPath
     * @param string $type 'image' or 'video'
     * @return bool Success status
     */
    public static function normalizeMedia($inputPath, $outputPath, $type = 'image') {
        try {
            if ($type === 'image') {
                return self::normalizeImage($inputPath, $outputPath);
            } else {
                return self::normalizeVideo($inputPath, $outputPath);
            }
        } catch (Exception $e) {
            error_log("Media normalization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Normalize image to 9:16 aspect ratio
     * @param string $inputPath
     * @param string $outputPath
     * @return bool
     */
    private static function normalizeImage($inputPath, $outputPath) {
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $sourceMime = $imageInfo['mime'];
        
        // Create source image resource
        switch ($sourceMime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($inputPath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($inputPath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($inputPath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Calculate dimensions for 9:16 aspect ratio
        $targetWidth = self::TARGET_WIDTH;
        $targetHeight = self::TARGET_HEIGHT;
        
        // Calculate scaling to fit within target dimensions
        $scaleX = $targetWidth / $sourceWidth;
        $scaleY = $targetHeight / $sourceHeight;
        $scale = min($scaleX, $scaleY);
        
        $scaledWidth = intval($sourceWidth * $scale);
        $scaledHeight = intval($sourceHeight * $scale);
        
        // Calculate padding
        $padX = intval(($targetWidth - $scaledWidth) / 2);
        $padY = intval(($targetHeight - $scaledHeight) / 2);
        
        // Create target image
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Set background color (black)
        $backgroundColor = imagecolorallocate($targetImage, 0, 0, 0);
        imagefill($targetImage, 0, 0, $backgroundColor);
        
        // Copy and resize source image to target
        imagecopyresampled(
            $targetImage, $sourceImage,
            $padX, $padY, 0, 0,
            $scaledWidth, $scaledHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Save normalized image
        $success = false;
        switch ($sourceMime) {
            case 'image/jpeg':
                $success = imagejpeg($targetImage, $outputPath, 90);
                break;
            case 'image/png':
                $success = imagepng($targetImage, $outputPath, 8);
                break;
            case 'image/webp':
                $success = imagewebp($targetImage, $outputPath, 90);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        return $success;
    }
    
    /**
     * Normalize video to 9:16 aspect ratio using ffmpeg
     * @param string $inputPath
     * @param string $outputPath
     * @return bool
     */
    private static function normalizeVideo($inputPath, $outputPath) {
        $ffmpegPath = self::findFFMpeg();
        if (!$ffmpegPath) {
            return false;
        }
        
        try {
            $command = sprintf(
                '%s -i %s -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:black" -c:a copy %s',
                escapeshellcmd($ffmpegPath),
                escapeshellarg($inputPath),
                self::TARGET_WIDTH,
                self::TARGET_HEIGHT,
                self::TARGET_WIDTH,
                self::TARGET_HEIGHT,
                escapeshellarg($outputPath)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            return $returnCode === 0 && file_exists($outputPath);
            
        } catch (Exception $e) {
            error_log("Video normalization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate aspect ratio
     * @param int $width
     * @param int $height
     * @return bool
     */
    private static function validateAspectRatio($width, $height) {
        if ($width <= 0 || $height <= 0) {
            return false;
        }
        
        $ratio = $width / $height;
        $expectedRatio = self::ASPECT_RATIO_WIDTH / self::ASPECT_RATIO_HEIGHT;
        
        // Allow small tolerance for aspect ratio (±5%)
        $tolerance = 0.05;
        return abs($ratio - $expectedRatio) <= ($expectedRatio * $tolerance);
    }
    
    /**
     * Check if mime type is image
     * @param string $mimeType
     * @return bool
     */
    private static function isImage($mimeType) {
        return in_array($mimeType, self::ALLOWED_IMAGE_TYPES);
    }
    
    /**
     * Check if mime type is video
     * @param string $mimeType
     * @return bool
     */
    private static function isVideo($mimeType) {
        return in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
    }
    
    /**
     * Find ffmpeg executable
     * @return string|null
     */
    private static function findFFMpeg() {
        $paths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', 'ffmpeg'];
        
        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        
        // Try which command
        $which = shell_exec('which ffmpeg 2>/dev/null');
        if ($which && is_executable(trim($which))) {
            return trim($which);
        }
        
        return null;
    }
    
    /**
     * Find ffprobe executable
     * @return string|null
     */
    private static function findFFProbe() {
        $paths = ['/usr/bin/ffprobe', '/usr/local/bin/ffprobe', 'ffprobe'];
        
        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        
        // Try which command
        $which = shell_exec('which ffprobe 2>/dev/null');
        if ($which && is_executable(trim($which))) {
            return trim($which);
        }
        
        return null;
    }
    
    /**
     * Generate unique filename
     * @param string $originalName
     * @param string $directory
     * @return string
     */
    public static function generateUniqueFilename($originalName, $directory) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Generate unique filename
        $counter = 0;
        do {
            $filename = $basename . ($counter > 0 ? "_{$counter}" : '') . '.' . $extension;
            $fullPath = $directory . '/' . $filename;
            $counter++;
        } while (file_exists($fullPath));
        
        return $filename;
    }
    
    /**
     * Get media type from file
     * @param string $filePath
     * @return string 'image', 'video', or 'unknown'
     */
    public static function getMediaType($filePath) {
        $mimeType = mime_content_type($filePath);
        
        if (self::isImage($mimeType)) {
            return 'image';
        } elseif (self::isVideo($mimeType)) {
            return 'video';
        }
        
        return 'unknown';
    }
}
?>
