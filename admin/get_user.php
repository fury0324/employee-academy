<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Check if profile_picture column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column && $check_column->num_rows > 0;

// Build query with profile_picture if exists
if ($has_profile_picture) {
    $stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status FROM users WHERE id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Add profile picture URL if exists
if ($has_profile_picture && !empty($user['profile_picture'])) {
    $profile_picture_path = __DIR__ . '/../uploads/profile_pictures/' . $user['profile_picture'];
    if (file_exists($profile_picture_path)) {
        $user['profile_picture_url'] = '../uploads/profile_pictures/' . $user['profile_picture'];
    } else {
        $user['profile_picture_url'] = null;
    }
} else {
    $user['profile_picture_url'] = null;
}

echo json_encode(['success' => true, 'user' => $user]);
?>