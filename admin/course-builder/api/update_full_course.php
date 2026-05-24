<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST allowed']);
    exit();
}

$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

if ($course_id <= 0) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

// Update course
$title = $_POST['title'] ?? '';
$type = $_POST['type'] ?? 'general';
$category = $_POST['category'] ?? '';
$difficulty = $_POST['difficulty'] ?? 'Beginner';
$description = $_POST['description'] ?? '';
$status = $_POST['status'] ?? 'draft';
$thumbnail_url = $_POST['thumbnail_url'] ?? '';

$stmt = $conn->prepare("UPDATE courses SET title = ?, type = ?, category = ?, difficulty = ?, description = ?, status = ?, thumbnail_url = ? WHERE id = ?");
$stmt->bind_param("sssssssi", $title, $type, $category, $difficulty, $description, $status, $thumbnail_url, $course_id);
$stmt->execute();
$stmt->close();

// Update video
$video_title = $_POST['video_title'] ?? '';
$video_url = $_POST['video_url'] ?? '';

$stmt = $conn->prepare("SELECT id FROM videos WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    $stmt = $conn->prepare("UPDATE videos SET title = ?, video_url = ? WHERE course_id = ?");
    $stmt->bind_param("ssi", $video_title, $video_url, $course_id);
} else {
    $stmt = $conn->prepare("INSERT INTO videos (course_id, title, video_url) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $course_id, $video_title, $video_url);
}
$stmt->execute();
$stmt->close();

// Update quiz settings
$timer_minutes = intval($_POST['timer_minutes'] ?? 10);
$timer_seconds = intval($_POST['timer_seconds'] ?? 0);
$passing = intval($_POST['passing_threshold'] ?? 70);
$randomize_q = isset($_POST['randomize_questions']) ? 1 : 0;
$disable_copy = isset($_POST['disable_copy']) ? 1 : 0;

$stmt = $conn->prepare("SELECT id FROM quiz_settings WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    $stmt = $conn->prepare("UPDATE quiz_settings SET global_timer_minutes = ?, global_timer_seconds = ?, passing_threshold = ?, randomize_questions = ?, disable_copy = ? WHERE course_id = ?");
    $stmt->bind_param("iiiiii", $timer_minutes, $timer_seconds, $passing, $randomize_q, $disable_copy, $course_id);
} else {
    $stmt = $conn->prepare("INSERT INTO quiz_settings (course_id, global_timer_minutes, global_timer_seconds, passing_threshold, randomize_questions, disable_copy) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiii", $course_id, $timer_minutes, $timer_seconds, $passing, $randomize_q, $disable_copy);
}
$stmt->execute();
$stmt->close();

// Delete existing quizzes
$stmt = $conn->prepare("DELETE FROM quizzes WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->close();

// Insert new quizzes
$quizzes_json = $_POST['quizzes'] ?? '[]';
$quizzes = json_decode($quizzes_json, true);

if (is_array($quizzes)) {
    $insert_quiz = $conn->prepare("INSERT INTO quizzes (course_id, title) VALUES (?, ?)");
    $insert_question = $conn->prepare("INSERT INTO questions (quiz_id, type, question_text) VALUES (?, ?, ?)");
    $insert_option = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
    
    foreach ($quizzes as $quiz) {
        $quiz_title = $quiz['title'] ?? 'Untitled Quiz';
        $insert_quiz->bind_param("is", $course_id, $quiz_title);
        $insert_quiz->execute();
        $quiz_id = $insert_quiz->insert_id;
        
        foreach ($quiz['questions'] as $question) {
            $question_text = $question['text'] ?? '';
            $question_type = $question['type'] ?? 'mc';
            $insert_question->bind_param("iss", $quiz_id, $question_type, $question_text);
            $insert_question->execute();
            $question_id = $insert_question->insert_id;
            
            foreach ($question['options'] as $option) {
                $option_text = $option['text'] ?? '';
                $is_correct = $option['correct'] ? 1 : 0;
                $insert_option->bind_param("isi", $question_id, $option_text, $is_correct);
                $insert_option->execute();
            }
        }
    }
    
    $insert_quiz->close();
    $insert_question->close();
    $insert_option->close();
}

echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
?>