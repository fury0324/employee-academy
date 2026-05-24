<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$course_id = $data['course_id'] ?? 0;

if (!$course_id) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

$conn->begin_transaction();

try {
    // Reset user_courses progress
    $stmt = $conn->prepare("
        UPDATE user_courses 
        SET progress = 0, status = 'not_started', completed_at = NULL 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete quiz attempts for this course
    $stmt = $conn->prepare("
        DELETE uqa FROM user_quiz_attempts uqa
        INNER JOIN quizzes q ON q.id = uqa.quiz_id
        WHERE uqa.user_id = ? AND q.course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete video watched records
    $stmt = $conn->prepare("
        DELETE FROM user_video_watched 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $stmt->close();
    
    // Mark retake request as completed - FIXED: use 'processed_at' instead of 'completed_at'
    $stmt = $conn->prepare("
        UPDATE retake_requests 
        SET status = 'completed', processed_at = NOW()
        WHERE user_id = ? AND course_id = ? AND status = 'approved'
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Course progress reset successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>