<?php
// request_retake.php

session_start();
header('Content-Type: application/json');

// Error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is valid
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$course_id = $data['course_id'] ?? 0;
$reason = $data['reason'] ?? '';

// Validate input
if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid course']);
    exit();
}

if (empty($reason) || strlen($reason) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid reason (minimum 10 characters)']);
    exit();
}

// Check if this is an ADMIN approving a retake (when admin_id, request_id, and action are present)
$is_admin_action = isset($data['admin_id']) && $data['admin_id'] > 0;
$admin_id = $data['admin_id'] ?? 0;
$request_id = $data['request_id'] ?? 0;
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if ($is_admin_action && $request_id > 0 && $action === 'approve') {
    // This is an admin approving a retake request
    $update_query = "UPDATE retake_requests SET status = 'approved', processed_at = NOW(), admin_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $admin_id, $request_id);
    
    if ($update_stmt->execute()) {
        // Get the user_id for this retake request
        $get_user_query = "SELECT user_id, course_id FROM retake_requests WHERE id = ?";
        $get_user_stmt = $conn->prepare($get_user_query);
        $get_user_stmt->bind_param("i", $request_id);
        $get_user_stmt->execute();
        $request_data = $get_user_stmt->get_result()->fetch_assoc();
        $target_user_id = $request_data['user_id'];
        $target_course_id = $request_data['course_id'];
        $get_user_stmt->close();
        
        // RESET THE USER_COURSES FOR THIS COURSE
        $reset_course = $conn->prepare("
            UPDATE user_courses 
            SET progress = 0, status = 'not_started', completed_at = NULL 
            WHERE user_id = ? AND course_id = ?
        ");
        $reset_course->bind_param("ii", $target_user_id, $target_course_id);
        $reset_course->execute();
        $reset_course->close();
        
        // Delete quiz attempts for this course
        $delete_attempts = $conn->prepare("
            DELETE uqa FROM user_quiz_attempts uqa
            INNER JOIN quizzes q ON q.id = uqa.quiz_id
            WHERE uqa.user_id = ? AND q.course_id = ?
        ");
        $delete_attempts->bind_param("ii", $target_user_id, $target_course_id);
        $delete_attempts->execute();
        $delete_attempts->close();
        
        // Delete video watched records for this course
        $delete_video = $conn->prepare("
            DELETE FROM user_video_watched 
            WHERE user_id = ? AND course_id = ?
        ");
        $delete_video->bind_param("ii", $target_user_id, $target_course_id);
        $delete_video->execute();
        $delete_video->close();
        
        echo json_encode(['success' => true, 'message' => 'Retake request approved and course reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve request: ' . $conn->error]);
    }
    $update_stmt->close();
    exit();
}

if ($is_admin_action && $request_id > 0 && $action === 'reject') {
    // This is an admin rejecting a retake request
    $admin_notes = $data['admin_notes'] ?? 'No reason provided';
    $update_query = "UPDATE retake_requests SET status = 'rejected', processed_at = NOW(), admin_id = ?, admin_notes = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("isi", $admin_id, $admin_notes, $request_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Retake request rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject request: ' . $conn->error]);
    }
    $update_stmt->close();
    exit();
}

// Check if user already has a pending retake request for this course
$check_query = "SELECT id, status FROM retake_requests WHERE user_id = ? AND course_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $course_id);
$check_stmt->execute();
$existing = $check_stmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending retake request for this course']);
    exit();
}

// Get user details for notification
$user_query = "SELECT firstname, lastname FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_name = $user['firstname'] . ' ' . $user['lastname'];

// Get course details for notification
$course_query = "SELECT title FROM courses WHERE id = ?";
$course_stmt = $conn->prepare($course_query);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();
$course_title = $course['title'];

// Insert retake request
$insert_query = "INSERT INTO retake_requests (user_id, course_id, reason, requested_at, status) 
                 VALUES (?, ?, ?, NOW(), 'pending')";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iis", $user_id, $course_id, $reason);

if ($insert_stmt->execute()) {
    // SEND NOTIFICATION TO ALL ADMINS
    $admin_query = "SELECT id FROM users WHERE role = 'admin'";
    $admin_result = $conn->query($admin_query);
    
    if ($admin_result && $admin_result->num_rows > 0) {
        while ($admin = $admin_result->fetch_assoc()) {
            $notif_title = "New Retake Request";
            $notif_message = "{$user_name} is requesting to retake: {$course_title}";
            $notif_link = "../admin/Request_retake.php";
            $notif_type = "Request_retake";
            
            $notif_query = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                           VALUES (?, ?, ?, ?, ?, 0, NOW())";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->bind_param("issss", $admin['id'], $notif_type, $notif_title, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Retake request submitted successfully! Admin has been notified.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to submit request: ' . $conn->error
    ]);
}

$insert_stmt->close();
$conn->close();
?>