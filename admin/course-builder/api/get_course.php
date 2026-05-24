<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method allowed']);
    exit();
}

$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($courseId > 0) {
        // Load specific course
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course) {
            echo json_encode(['error' => "Course with ID $courseId not found"]);
            exit();
        }
        
        // Load video
        $stmt = $pdo->prepare("SELECT * FROM videos WHERE course_id = ?");
        $stmt->execute([$courseId]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['video'] = $video ?: null;
        
        // Load quiz settings with security options
        $stmt = $pdo->prepare("SELECT * FROM quiz_settings WHERE course_id = ?");
        $stmt->execute([$courseId]);
        $quizSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure security settings have default values if not exist in database
        if ($quizSettings) {
            // Map database columns to frontend expected format
            $course['quiz_settings'] = [
                'global_timer_minutes' => $quizSettings['global_timer_minutes'] ?? 10,
                'global_timer_seconds' => $quizSettings['global_timer_seconds'] ?? 0,
                'passing_threshold' => $quizSettings['passing_threshold'] ?? 70,
                // Security settings
                'randomize_questions' => isset($quizSettings['randomize_questions']) ? (int)$quizSettings['randomize_questions'] : 0,
                'randomize_options' => isset($quizSettings['randomize_options']) ? (int)$quizSettings['randomize_options'] : 0,
                'hide_correct_answers' => isset($quizSettings['hide_correct_answers']) ? (int)$quizSettings['hide_correct_answers'] : 0,
                'disable_copy' => isset($quizSettings['disable_copy']) ? (int)$quizSettings['disable_copy'] : 1
            ];
        } else {
            // Default values if no settings found
            $course['quiz_settings'] = [
                'global_timer_minutes' => 10,
                'global_timer_seconds' => 0,
                'passing_threshold' => 70,
                'randomize_questions' => 0,
                'randomize_options' => 0,
                'hide_correct_answers' => 0,
                'disable_copy' => 1
            ];
        }
        
        // Load quizzes
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY order_index ASC, id ASC");
        $stmt->execute([$courseId]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($quizzes as &$quiz) {
            // Load questions
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY order_index ASC, id ASC");
            $stmt->execute([$quiz['id']]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($questions as &$question) {
                // Load options - using correct column name 'option_text'
                $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
                $stmt->execute([$question['id']]);
                $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format options
                $formattedOptions = [];
                foreach ($options as $option) {
                    $formattedOptions[] = [
                        'text' => $option['option_text'], // Fixed: use 'option_text' not 'text'
                        'correct' => (bool)$option['is_correct']
                    ];
                }
                $question['options'] = $formattedOptions;
                
                // IMPORTANT: Map question_text to 'text' for frontend compatibility
                $question['text'] = $question['question_text'];
            }
            $quiz['questions'] = $questions;
        }
        $course['quizzes'] = $quizzes;
        
        echo json_encode(['success' => true, 'course' => $course]);
        
    } else {
        // Load all courses
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'courses' => $courses]);
    }
    
} catch(Exception $e) {
    error_log("Error in get_course.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load course: ' . $e->getMessage()]);
}
?>