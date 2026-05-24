<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id'], $data['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$user_id = $data['user_id'];
$course_id = $data['course_id'];
$progress = $data['progress'] ?? 0;
$status = $data['status'] ?? 'in_progress';
$video_watched = $data['video_watched'] ?? false;
$video_id = $data['video_id'] ?? null;

// Update or insert user course
$check_stmt = $conn->prepare("SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?");
$check_stmt->bind_param("ii", $user_id, $course_id);
$check_stmt->execute();
$existing = $check_stmt->get_result()->fetch_assoc();

if ($existing) {
    $stmt = $conn->prepare("UPDATE user_courses SET progress = ?, status = ?, last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param("isii", $progress, $status, $user_id, $course_id);
} else {
    $stmt = $conn->prepare("INSERT INTO user_courses (user_id, course_id, progress, status, started_at, last_accessed) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("iiis", $user_id, $course_id, $progress, $status);
}
$stmt->execute();

// Save video watched status to database
if ($video_watched && $video_id) {
    // Check if already exists
    $check_video = $conn->prepare("SELECT id FROM user_video_watched WHERE user_id = ? AND course_id = ? AND video_id = ?");
    $check_video->bind_param("iii", $user_id, $course_id, $video_id);
    $check_video->execute();
    
    if (!$check_video->get_result()->fetch_assoc()) {
        $save_video = $conn->prepare("INSERT INTO user_video_watched (user_id, course_id, video_id, watched_at) VALUES (?, ?, ?, NOW())");
        $save_video->bind_param("iii", $user_id, $course_id, $video_id);
        $save_video->execute();
    }
}

echo json_encode(['success' => true]);
?>