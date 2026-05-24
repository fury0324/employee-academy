<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['employee', 'user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if this is a FormData request (with file upload)
$is_form_data = $_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

if ($is_form_data) {
    // Get form data
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $position  = trim($_POST['position'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    
    if (empty($firstname) || empty($lastname) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
        exit();
    }
    
    // Check email uniqueness
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $user_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
        exit();
    }
    
    // Handle profile picture upload
    $profile_picture = null;
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $max_size = 5 * 1024 * 1024;
        
        // Validate file
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, or WEBP.']);
            exit();
        }
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
            exit();
        }
        
        // Create uploads directory if not exists
        $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            echo json_encode(['debug' => 'Created directory: ' . $upload_dir]);
        }
        
        // Get current profile picture to delete later
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old profile picture if exists
            if (!empty($current['profile_picture']) && file_exists($upload_dir . $current['profile_picture'])) {
                unlink($upload_dir . $current['profile_picture']);
            }
            $profile_picture = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file. Check folder permissions.']);
            exit();
        }
    }
    
    // Update database
    if ($profile_picture) {
        $update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, position = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?");
        $update->bind_param("sssssssi", $firstname, $lastname, $email, $position, $phone, $address, $profile_picture, $user_id);
    } else {
        $update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, position = ?, phone = ?, address = ? WHERE id = ?");
        $update->bind_param("ssssssi", $firstname, $lastname, $email, $position, $phone, $address, $user_id);
    }
    
    if ($update->execute()) {
        $_SESSION['firstname'] = $firstname;
        $profile_picture_url = $profile_picture ? '../uploads/profile_pictures/' . $profile_picture : null;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'profile_picture_url' => $profile_picture_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $update->error]);
    }
    $update->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request format']);
}
?>