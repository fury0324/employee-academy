<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

// Get unread count
$count_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$unread_count = $count_result->fetch_assoc()['unread_count'];

// Get recent notifications (last 10)
$notif_query = "SELECT id, type, title, message, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'type' => $row['type'],
        'title' => $row['title'],
        'message' => $row['message'],
        'link' => $row['link'],
        'is_read' => (bool)$row['is_read'],
        'time_ago' => timeAgo($row['created_at'])
    ];
}

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>