<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/index.html");
    exit();
}

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No course ID specified.";
    header("Location: course_management.php");
    exit();
}

$course_id = intval($_GET['id']);

// First, get course details for confirmation message and to delete thumbnail if exists
$course_query = "SELECT title, thumbnail_url FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    $_SESSION['error_message'] = "Course not found.";
    header("Location: course_management.php");
    exit();
}

$course_title = $course['title'];
$thumbnail_url = $course['thumbnail_url'];

// Delete thumbnail file if it exists and is a local file
if (!empty($thumbnail_url) && strpos($thumbnail_url, 'uploads/') !== false) {
    $thumbnail_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $thumbnail_url;
    if (file_exists($thumbnail_path)) {
        unlink($thumbnail_path);
    }
}

// The database will handle cascading deletes if foreign keys are set up correctly
// But let's do it manually to be safe and track what's being deleted

// Start transaction
$conn->begin_transaction();

try {
    // 1. Delete options (answers) - through questions
    // 2. Delete questions - through quizzes
    // 3. Delete quizzes - through course_id
    // 4. Delete videos - through course_id
    // 5. Delete quiz_settings - through course_id
    // 6. Delete the course itself
    
    // Delete options (answers for questions)
    $delete_options = "DELETE o FROM options o 
                       INNER JOIN questions q ON o.question_id = q.id 
                       INNER JOIN quizzes qz ON q.quiz_id = qz.id 
                       WHERE qz.course_id = ?";
    $stmt = $conn->prepare($delete_options);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $options_deleted = $stmt->affected_rows;
    
    // Delete questions
    $delete_questions = "DELETE q FROM questions q 
                         INNER JOIN quizzes qz ON q.quiz_id = qz.id 
                         WHERE qz.course_id = ?";
    $stmt = $conn->prepare($delete_questions);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $questions_deleted = $stmt->affected_rows;
    
    // Delete quizzes
    $delete_quizzes = "DELETE FROM quizzes WHERE course_id = ?";
    $stmt = $conn->prepare($delete_quizzes);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $quizzes_deleted = $stmt->affected_rows;
    
    // Delete videos
    // First, get video files to delete from server
    $video_query = "SELECT video_url, video_type FROM videos WHERE course_id = ?";
    $stmt = $conn->prepare($video_query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $video_result = $stmt->get_result();
    $videos = [];
    while ($video = $video_result->fetch_assoc()) {
        $videos[] = $video;
    }
    
    // Delete video files from server
    foreach ($videos as $video) {
        if ($video['video_type'] === 'upload' && !empty($video['video_url'])) {
            $video_path = $_SERVER['DOCUMENT_ROOT'] . $video['video_url'];
            if (file_exists($video_path)) {
                unlink($video_path);
            }
        }
    }
    
    // Delete video records
    $delete_videos = "DELETE FROM videos WHERE course_id = ?";
    $stmt = $conn->prepare($delete_videos);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $videos_deleted = $stmt->affected_rows;
    
    // Delete quiz settings
    $delete_settings = "DELETE FROM quiz_settings WHERE course_id = ?";
    $stmt = $conn->prepare($delete_settings);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $settings_deleted = $stmt->affected_rows;
    
    // Finally, delete the course
    $delete_course = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($delete_course);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete course.");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set success message with deletion summary
    $_SESSION['success_message'] = "Course \"" . htmlspecialchars($course_title) . "\" has been deleted successfully.\n";
    $_SESSION['success_message'] .= "Deleted: " . $videos_deleted . " video(s), " . $quizzes_deleted . " quiz(es), " . $questions_deleted . " question(s), " . $options_deleted . " answer(s).";
    
    // Log the deletion action
    $log_query = "INSERT INTO employee_logs (user_id, action, details, ip_address) VALUES (?, 'Course Deleted', ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $user_id = $_SESSION['user_id'];
    $details = "Deleted course ID: $course_id - Title: " . $course_title . " - Deleted " . $videos_deleted . " videos, " . $quizzes_deleted . " quizzes, " . $questions_deleted . " questions, " . $options_deleted . " options";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $user_id, $details, $ip_address);
    $log_stmt->execute();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting course: " . $e->getMessage();
}

// Redirect back to course management page
header("Location: course_management.php");
exit();
?>