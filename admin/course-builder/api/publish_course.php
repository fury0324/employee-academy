<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$courseId = $input['course_id'] ?? 0;

if (!$courseId) {
    echo json_encode(['success' => false, 'error' => 'Course ID required']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE courses SET status = 'published', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Course published successfully']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>