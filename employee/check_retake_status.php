<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? $_SESSION['user_id'];
$course_id = $data['course_id'] ?? 0;

if (!$course_id) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

// Check for approved retake that hasn't been acted upon
$stmt = $conn->prepare("
    SELECT rr.*, uc.status as course_status 
    FROM retake_requests rr
    LEFT JOIN user_courses uc ON uc.user_id = rr.user_id AND uc.course_id = rr.course_id
    WHERE rr.user_id = ? AND rr.course_id = ? AND rr.status = 'approved'
    ORDER BY rr.created_at DESC LIMIT 1
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$retake = $result->fetch_assoc();

$already_reset = false;
if ($retake) {
    // Check if user has already reset (you might want to add a flag in user_courses)
    $already_reset = ($retake['course_status'] === 'not_started' || empty($retake['course_status']));
}

echo json_encode([
    'retake_approved' => !empty($retake),
    'already_reset' => $already_reset,
    'retake_id' => $retake['id'] ?? null
]);
?>