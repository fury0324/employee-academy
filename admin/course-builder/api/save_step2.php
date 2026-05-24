<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit();
}

// Get the raw input
$raw_input = file_get_contents('php://input');
error_log("save_step2.php received: " . $raw_input);

$input = json_decode($raw_input, true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit();
}

try {
    $courseId = isset($input['course_id']) ? intval($input['course_id']) : 0;
    
    if (!$courseId) {
        echo json_encode(['success' => false, 'error' => 'Course ID is required']);
        exit();
    }
    
    error_log("Saving for course ID: " . $courseId);
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete existing related data
    $delete_videos = $conn->prepare("DELETE FROM videos WHERE course_id = ?");
    $delete_videos->bind_param("i", $courseId);
    $delete_videos->execute();
    error_log("Deleted existing videos");
    
    $delete_settings = $conn->prepare("DELETE FROM quiz_settings WHERE course_id = ?");
    $delete_settings->bind_param("i", $courseId);
    $delete_settings->execute();
    error_log("Deleted existing quiz settings");
    
    $delete_quizzes = $conn->prepare("DELETE FROM quizzes WHERE course_id = ?");
    $delete_quizzes->bind_param("i", $courseId);
    $delete_quizzes->execute();
    error_log("Deleted existing quizzes");
    
    // Insert video if exists
    if (isset($input['video']) && !empty($input['video']['video_url'])) {
        $stmt = $conn->prepare("
            INSERT INTO videos (course_id, title, description, video_url, video_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $title = $input['video']['title'] ?? 'Course Video';
        $description = $input['video']['description'] ?? '';
        $video_url = $input['video']['video_url'];
        $video_type = $input['video']['video_type'] ?? 'external';
        
        $stmt->bind_param("issss", $courseId, $title, $description, $video_url, $video_type);
        $stmt->execute();
        error_log("Video inserted: " . $video_url);
    } else {
        error_log("No video to insert");
    }
    
    // Insert quiz settings with security options
    if (isset($input['quiz_settings'])) {
        $stmt = $conn->prepare("
            INSERT INTO quiz_settings (course_id, global_timer_minutes, global_timer_seconds, passing_threshold, 
            randomize_questions, randomize_options, hide_correct_answers, disable_copy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Get values from input with proper conversion
        $minutes = isset($input['quiz_settings']['global_timer_minutes']) ? intval($input['quiz_settings']['global_timer_minutes']) : 10;
        $seconds = isset($input['quiz_settings']['global_timer_seconds']) ? intval($input['quiz_settings']['global_timer_seconds']) : 0;
        $threshold = isset($input['quiz_settings']['passing_threshold']) ? intval($input['quiz_settings']['passing_threshold']) : 70;
        
        // Convert boolean/checkbox values properly
        $randomize_questions = 0;
        if (isset($input['quiz_settings']['randomize_questions'])) {
            $val = $input['quiz_settings']['randomize_questions'];
            $randomize_questions = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
        }
        
        $randomize_options = 0;
        if (isset($input['quiz_settings']['randomize_options'])) {
            $val = $input['quiz_settings']['randomize_options'];
            $randomize_options = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
        }
        
        $hide_correct_answers = 0;
        if (isset($input['quiz_settings']['hide_correct_answers'])) {
            $val = $input['quiz_settings']['hide_correct_answers'];
            $hide_correct_answers = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
        }
        
        $disable_copy = 0;
        if (isset($input['quiz_settings']['disable_copy'])) {
            $val = $input['quiz_settings']['disable_copy'];
            $disable_copy = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
        } elseif (isset($input['quiz_settings']['prevent_copy_paste'])) {
            $val = $input['quiz_settings']['prevent_copy_paste'];
            $disable_copy = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
        }
        
        error_log("=== SAVING QUIZ SETTINGS ===");
        error_log("Minutes: $minutes");
        error_log("Seconds: $seconds");
        error_log("Passing Threshold: $threshold");
        error_log("Randomize Questions: $randomize_questions");
        error_log("Randomize Options: $randomize_options");
        error_log("Hide Correct Answers: $hide_correct_answers");
        error_log("Disable Copy: $disable_copy");
        error_log("===============================");
        
        $stmt->bind_param("iiiiiiii", $courseId, $minutes, $seconds, $threshold, 
            $randomize_questions, $randomize_options, $hide_correct_answers, $disable_copy);
        $stmt->execute();
        error_log("Quiz settings inserted successfully");
    }
    
    // Insert quizzes if exists
    if (isset($input['quizzes']) && is_array($input['quizzes']) && count($input['quizzes']) > 0) {
        error_log("Processing " . count($input['quizzes']) . " quizzes");
        
        foreach ($input['quizzes'] as $quizIndex => $quiz) {
            $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, order_index) VALUES (?, ?, ?)");
            $quizTitle = $quiz['title'] ?? 'Untitled Quiz';
            $stmt->bind_param("isi", $courseId, $quizTitle, $quizIndex);
            $stmt->execute();
            $quizId = $conn->insert_id;
            error_log("Quiz inserted: " . $quizTitle . " (ID: " . $quizId . ")");
            
            if (isset($quiz['questions']) && is_array($quiz['questions'])) {
                error_log("  Processing " . count($quiz['questions']) . " questions");
                
                foreach ($quiz['questions'] as $questionIndex => $question) {
                    $stmt = $conn->prepare("
                        INSERT INTO questions (quiz_id, type, question_text, order_index)
                        VALUES (?, ?, ?, ?)
                    ");
                    $questionType = $question['type'] ?? 'mc';
                    $questionText = $question['text'] ?? '';
                    $stmt->bind_param("issi", $quizId, $questionType, $questionText, $questionIndex);
                    $stmt->execute();
                    $questionId = $conn->insert_id;
                    error_log("    Question inserted (ID: " . $questionId . ")");
                    
                    if (isset($question['options']) && is_array($question['options'])) {
                        error_log("      Processing " . count($question['options']) . " options");
                        
                        foreach ($question['options'] as $optionIndex => $option) {
                            $stmt = $conn->prepare("
                                INSERT INTO options (question_id, option_text, is_correct, order_index)
                                VALUES (?, ?, ?, ?)
                            ");
                            $optionText = $option['text'] ?? '';
                            $isCorrect = isset($option['correct']) && ($option['correct'] === true || $option['correct'] === 1 || $option['correct'] === '1') ? 1 : 0;
                            $stmt->bind_param("isii", $questionId, $optionText, $isCorrect, $optionIndex);
                            $stmt->execute();
                            error_log("        Option inserted: " . $optionText . " (correct: " . $isCorrect . ")");
                        }
                    }
                }
            }
        }
    } else {
        error_log("No quizzes to insert");
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'course_id' => $courseId,
        'message' => 'Video, quizzes, and security settings saved successfully'
    ]);
    
} catch(Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("ERROR in save_step2.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Failed to save: ' . $e->getMessage()]);
}
?>