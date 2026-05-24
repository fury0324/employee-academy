<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

$query = "SELECT r.*, u.firstname, u.lastname, u.username, u.email, 
          c.title as course_title, c.description as course_description
          FROM retake_requests r
          JOIN users u ON r.user_id = u.id
          JOIN courses c ON r.course_id = c.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'request' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
}
?>