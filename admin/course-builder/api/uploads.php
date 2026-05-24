<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Increase limits for large file uploads
ini_set('upload_max_filesize', '2G');
ini_set('post_max_size', '2G');
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');
ini_set('memory_limit', '512M');
ini_set('max_file_uploads', '20');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method allowed']);
    exit();
}

// Upload directory
$uploadDir = '../../../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ==================== CHUNKED UPLOAD HANDLER ====================
if (isset($_POST['chunk_index']) && isset($_POST['total_chunks'])) {
    $chunkIndex = (int)$_POST['chunk_index'];
    $totalChunks = (int)$_POST['total_chunks'];
    $originalFileName = $_POST['file_name'] ?? '';
    $fileSize = (int)$_POST['file_size'] ?? 0;
    $isFinalize = isset($_POST['finalize']) && $_POST['finalize'] === 'true';
    
    // Validate file name
    if (empty($originalFileName)) {
        echo json_encode(['error' => 'Invalid file name']);
        exit();
    }
    
    // Create temp directory for this upload session
    $uploadSessionId = md5($originalFileName . $fileSize . $_SESSION['user_id']);
    $tempDir = $uploadDir . 'temp_' . $uploadSessionId . '/';
    
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // If this is a finalize request, combine chunks and return URL
    if ($isFinalize) {
        // Check if all chunks are present
        $allChunksPresent = true;
        $missingChunks = [];
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . 'chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
            if (!file_exists($chunkPath)) {
                $allChunksPresent = false;
                $missingChunks[] = $i;
            }
        }
        
        if (!$allChunksPresent) {
            echo json_encode([
                'error' => 'Missing chunks', 
                'missing' => $missingChunks,
                'total' => $totalChunks,
                'received' => ($totalChunks - count($missingChunks))
            ]);
            exit();
        }
        
        // Combine all chunks into final file
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $finalFileName = time() . '_' . uniqid() . '.' . $extension;
        $finalFilePath = $uploadDir . $finalFileName;
        
        $finalFile = fopen($finalFilePath, 'wb');
        if (!$finalFile) {
            echo json_encode(['error' => 'Cannot create final file']);
            exit();
        }
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . 'chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
            if (file_exists($chunkPath)) {
                $chunkData = file_get_contents($chunkPath);
                fwrite($finalFile, $chunkData);
                unlink($chunkPath);
            }
        }
        fclose($finalFile);
        
        // Clean up temp directory
        rmdir($tempDir);
        
        $fileUrl = '/uploads/' . $finalFileName;
        
        echo json_encode([
            'success' => true, 
            'url' => $fileUrl, 
            'filename' => $finalFileName,
            'chunked' => true
        ]);
        exit();
    }
    
    // Save the chunk
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $chunkFile = $tempDir . 'chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
        move_uploaded_file($_FILES['video_file']['tmp_name'], $chunkFile);
        
        echo json_encode([
            'success' => true, 
            'chunk' => $chunkIndex, 
            'total' => $totalChunks
        ]);
    } else {
        $error = $_FILES['video_file']['error'] ?? 'No file';
        echo json_encode(['error' => 'Failed to receive chunk', 'chunk_error' => $error]);
    }
    exit();
}

// ==================== REGULAR FILE UPLOAD ====================
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        echo json_encode(['error' => $uploadErrors[$file['error']] ?? 'Unknown upload error']);
        exit();
    }
    
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $targetPath = $uploadDir . $fileName;
    
    $fileType = mime_content_type($file['tmp_name']);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Allow images and videos
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo',
        'video/webm', 'video/x-matroska', 'video/x-flv'
    ];
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'webm', 'avi', 'mkv', 'flv', 'mpeg'];
    
    if (!in_array($fileType, $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['error' => 'Invalid file type. Allowed: Images (JPG, PNG, GIF, WEBP) and Videos (MP4, MOV, WEBM, AVI)']);
        exit();
    }
    
    // Max size 500MB for regular upload
    $maxSize = 500 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        echo json_encode(['error' => 'File too large. Max 500MB. Use chunked upload for larger files.']);
        exit();
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $fileUrl = '/uploads/' . $fileName;
        echo json_encode(['success' => true, 'url' => $fileUrl, 'filename' => $fileName]);
    } else {
        echo json_encode(['error' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded']);
}
?>