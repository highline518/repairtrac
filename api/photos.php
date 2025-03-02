<?php
require_once 'db.php';
header('Content-Type: application/json');

// Allow cross-origin requests from your domain
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/../uploads';
if (!file_exists($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create uploads directory']);
        exit;
    }
}

// Make sure the directory is writable
if (!is_writable($uploadsDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Uploads directory is not writable']);
    exit;
}

// Function to generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Upload photo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['taskId'])) {
            throw new Exception('Task ID is required');
        }
        
        $taskId = $_POST['taskId'];
        $photoId = generateUUID();
        
        if (!isset($_FILES['photo'])) {
            throw new Exception('No file uploaded');
        }
        
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }
        
        // Generate unique filename
        $filename = $photoId . '.' . $ext;
        $filepath = $uploadsDir . '/' . $filename;
        
        // Check if file was properly uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            throw new Exception('Upload error: ' . ($uploadErrors[$file['error']] ?? 'Unknown error'));
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file. Check directory permissions.');
        }
        
        // Create thumbnail
        $thumbPath = $uploadsDir . '/thumb_' . $filename;
        if (!createThumbnail($filepath, $thumbPath, 300)) {
            throw new Exception('Failed to create thumbnail');
        }
        
        // Save to database
        $stmt = $pdo->prepare('
            INSERT INTO photos (id, task_id, file_path, thumbnail_path)
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $photoId,
            $taskId,
            '/tasks/uploads/' . $filename,
            '/tasks/uploads/thumb_' . $filename
        ]);
        
        echo json_encode([
            'success' => true,
            'photo' => [
                'id' => $photoId,
                'url' => '/tasks/uploads/' . $filename,
                'thumbnail' => '/tasks/uploads/thumb_' . $filename
            ]
        ]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Delete photo
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $photoId = $_GET['id'];
        
        // Get file paths
        $stmt = $pdo->prepare('SELECT file_path, thumbnail_path FROM photos WHERE id = ?');
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();
        
        if ($photo) {
            // Delete files
            $fullPath = __DIR__ . '/..' . $photo['file_path'];
            $thumbPath = __DIR__ . '/..' . $photo['thumbnail_path'];
            
            if (file_exists($fullPath)) unlink($fullPath);
            if (file_exists($thumbPath)) unlink($thumbPath);
            
            // Delete from database
            $stmt = $pdo->prepare('DELETE FROM photos WHERE id = ?');
            $stmt->execute([$photoId]);
        }
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete photo']);
    }
}

function createThumbnail($source, $destination, $maxSize) {
    list($width, $height) = getimagesize($source);
    
    $ratio = $width / $height;
    if ($ratio > 1) {
        $newWidth = $maxSize;
        $newHeight = $maxSize / $ratio;
    } else {
        $newHeight = $maxSize;
        $newWidth = $maxSize * $ratio;
    }
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
    switch($ext) {
        case 'jpg':
        case 'jpeg':
            $source_image = imagecreatefromjpeg($source);
            break;
        case 'png':
            $source_image = imagecreatefrompng($source);
            break;
        case 'gif':
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    imagecopyresampled($thumb, $source_image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    switch($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($thumb, $destination, 80);
            break;
        case 'png':
            imagepng($thumb, $destination, 8);
            break;
        case 'gif':
            imagegif($thumb, $destination);
            break;
    }
    
    imagedestroy($thumb);
    imagedestroy($source_image);
    return true;
}