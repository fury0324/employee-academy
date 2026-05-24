<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Accept both 'employee' and 'user' roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['employee', 'user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if profile_picture column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

// Check if job_title column exists (instead of position)
$check_column2 = $conn->query("SHOW COLUMNS FROM users LIKE 'job_title'");
$has_job_title = $check_column2->num_rows > 0;

// Build query based on existing columns
if ($has_job_title) {
    $sql = "SELECT firstname, lastname, email, job_title as position, phone, address" . 
           ($has_profile_picture ? ", profile_picture" : "") . 
           " FROM users WHERE id = ?";
} else {
    $sql = "SELECT firstname, lastname, email, position, phone, address" . 
           ($has_profile_picture ? ", profile_picture" : "") . 
           " FROM users WHERE id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Add profile picture URL if exists
if ($has_profile_picture && !empty($user['profile_picture'])) {
    $profile_picture_path = '../uploads/profile_pictures/' . $user['profile_picture'];
    if (file_exists(__DIR__ . '/../uploads/profile_pictures/' . $user['profile_picture'])) {
        $user['profile_picture_url'] = $profile_picture_path;
    } else {
        $user['profile_picture_url'] = null;
    }
} else {
    $user['profile_picture_url'] = null;
}

echo json_encode(['success' => true, 'user' => $user]);
?>