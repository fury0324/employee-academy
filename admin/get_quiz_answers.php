<?php
// get_quiz_answers.php - FINAL CORRECTED VERSION
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if (!$user_id || !$course_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

$questions = [];

// Direct query to get all questions and answers for this user and course
$sql = "SELECT 
            q.id as question_id,
            q.question_text,
            q.type as question_type,
            qz.title as quiz_title,
            qz.order_index as quiz_order,
            uqa.is_correct,
            uqa.selected_option,
            opt_selected.option_text as user_answer_text,
            opt_correct.option_text as correct_answer_text
        FROM user_courses uc
        JOIN quizzes qz ON qz.course_id = uc.course_id
        JOIN quiz_questions qq ON qq.quiz_id = qz.id
        JOIN questions q ON qq.question_id = q.id
        LEFT JOIN user_quiz_answers uqa ON uqa.question_id = q.id AND uqa.user_id = uc.user_id
        LEFT JOIN options opt_selected ON uqa.selected_option = opt_selected.id
        LEFT JOIN options opt_correct ON opt_correct.question_id = q.id AND opt_correct.is_correct = 1
        WHERE uc.user_id = $user_id AND uc.course_id = $course_id AND uc.status = 'completed'
        ORDER BY qz.order_index ASC, qq.order_index ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get the user answer text
        $userAnswer = $row['user_answer_text'];
        
        // If still empty, try to get from the selected_option ID directly
        if (empty($userAnswer) && $row['selected_option']) {
            // Get option text directly
            $optSql = "SELECT option_text FROM options WHERE id = " . intval($row['selected_option']);
            $optResult = $conn->query($optSql);
            if ($optResult && $optResult->num_rows > 0) {
                $optRow = $optResult->fetch_assoc();
                $userAnswer = $optRow['option_text'];
            } else {
                $userAnswer = 'Not answered';
            }
        } elseif (empty($userAnswer)) {
            $userAnswer = 'Not answered';
        }
        
        // For true/false questions, clean up the answer
        if ($row['question_type'] == 'tf') {
            if ($userAnswer == '1' || $userAnswer == 'True' || strtolower($userAnswer) == 'true') {
                $userAnswer = 'True';
            } elseif ($userAnswer == '0' || $userAnswer == 'False' || strtolower($userAnswer) == 'false') {
                $userAnswer = 'False';
            }
        }
        
        $correctAnswer = $row['correct_answer_text'] ?? 'N/A';
        
        $questions[] = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => (bool)$row['is_correct'],
            'quiz_title' => $row['quiz_title']
        ];
    }
}

$totalQuestions = count($questions);
$totalCorrect = 0;
foreach ($questions as $q) {
    if ($q['is_correct']) $totalCorrect++;
}
$overallScore = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100) : 0;

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'total_questions' => $totalQuestions,
    'total_correct' => $totalCorrect,
    'overall_score' => $overallScore
]);

$conn->close();
?>