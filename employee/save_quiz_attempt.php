<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Log the incoming request for debugging
$input = file_get_contents('php://input');
error_log("Save Quiz Attempt - Raw input: " . $input);

$data = json_decode($input, true);

if (!$data) {
    error_log("Save Quiz Attempt - Failed to decode JSON");
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit();
}

if (!isset($data['user_id']) || !isset($data['quiz_id'])) {
    error_log("Save Quiz Attempt - Missing required fields");
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$user_id = $data['user_id'];
$quiz_id = $data['quiz_id'];
$score = $data['score'] ?? 0;
$status = $data['status'] ?? 'pending';
$course_id = $data['course_id'] ?? null;
$answers = $data['answers'] ?? null;
$time_taken = $data['time_taken'] ?? null;

error_log("Save Quiz Attempt - User: $user_id, Quiz: $quiz_id, Score: $score, Status: $status, Course: $course_id");

// ========== CHECK IF THIS IS A RETAKE SCENARIO ==========
$is_retake = false;
$retake_request = null;

if ($course_id) {
    // Check if there's an approved or in_progress retake for this course
    $retake_stmt = $conn->prepare("
        SELECT id, status FROM retake_requests 
        WHERE user_id = ? AND course_id = ? 
        AND status IN ('approved', 'in_progress')
        ORDER BY id DESC LIMIT 1
    ");
    $retake_stmt->bind_param("ii", $user_id, $course_id);
    $retake_stmt->execute();
    $retake_request = $retake_stmt->get_result()->fetch_assoc();
    $retake_stmt->close();
    
    $is_retake = !empty($retake_request);
    error_log("Save Quiz Attempt - Is retake: " . ($is_retake ? 'Yes (ID: ' . $retake_request['id'] . ')' : 'No'));
}

// ========== CHECK IF QUIZ ALREADY TAKEN ==========
// For retakes, delete old attempts first to allow new ones
if ($is_retake) {
    // Delete old attempts for this quiz (retake scenario)
    $delete_stmt = $conn->prepare("DELETE FROM user_quiz_attempts WHERE user_id = ? AND quiz_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $quiz_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    error_log("Save Quiz Attempt - Deleted old attempts for retake");
} else {
    // Normal flow - check if already attempted
    $check_stmt = $conn->prepare("SELECT id, status FROM user_quiz_attempts WHERE user_id = ? AND quiz_id = ?");
    if (!$check_stmt) {
        error_log("Save Quiz Attempt - Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
        exit();
    }

    $check_stmt->bind_param("ii", $user_id, $quiz_id);
    $check_stmt->execute();
    $existing_attempt = $check_stmt->get_result()->fetch_assoc();

    if ($existing_attempt) {
        error_log("Save Quiz Attempt - Quiz already attempted: " . $existing_attempt['id']);
        echo json_encode([
            'success' => false, 
            'error' => 'Quiz already attempted',
            'message' => 'You have already taken this quiz. Retakes are not allowed.',
            'existing_status' => $existing_attempt['status']
        ]);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
}

// ========== SAVE NEW QUIZ ATTEMPT ==========
$stmt = $conn->prepare("INSERT INTO user_quiz_attempts (user_id, quiz_id, score, status, attempted_at, completed_at, answers_data, time_taken) 
                        VALUES (?, ?, ?, ?, NOW(), NOW(), ?, ?)");
if (!$stmt) {
    error_log("Save Quiz Attempt - Insert prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Database insert prepare failed']);
    exit();
}

$answers_json = $answers ? json_encode($answers) : null;
$stmt->bind_param("iiisss", $user_id, $quiz_id, $score, $status, $answers_json, $time_taken);

if (!$stmt->execute()) {
    error_log("Save Quiz Attempt - Insert failed: " . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'Failed to save quiz attempt: ' . $stmt->error]);
    $stmt->close();
    exit();
}

$attempt_id = $stmt->insert_id;
$stmt->close();
error_log("Save Quiz Attempt - Saved with attempt_id: $attempt_id");

// ========== SAVE DETAILED ANSWERS ==========
if ($answers && is_array($answers) && count($answers) > 0) {
    $answer_stmt = $conn->prepare("INSERT INTO user_quiz_answers (attempt_id, user_id, quiz_id, question_id, selected_option, is_correct, answered_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$answer_stmt) {
        error_log("Save Quiz Attempt - Answers prepare failed: " . $conn->error);
    } else {
        foreach ($answers as $answer) {
            $question_id = $answer['question_id'] ?? null;
            $selected_option = isset($answer['user_answer']) ? (int)$answer['user_answer'] : null;
            $is_correct = isset($answer['is_correct']) && $answer['is_correct'] ? 1 : 0;
            
            if ($question_id) {
                $answer_stmt->bind_param("iiiiii", $attempt_id, $user_id, $quiz_id, $question_id, $selected_option, $is_correct);
                if (!$answer_stmt->execute()) {
                    error_log("Save Quiz Attempt - Answer insert failed for question $question_id: " . $answer_stmt->error);
                }
            }
        }
        $answer_stmt->close();
    }
}

// ========== UPDATE COURSE PROGRESS ==========
if ($course_id) {
    // Get total quizzes for this course
    $quiz_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM quizzes WHERE course_id = ?");
    $quiz_count_stmt->bind_param("i", $course_id);
    $quiz_count_stmt->execute();
    $total_quizzes = $quiz_count_stmt->get_result()->fetch_assoc()['total'];
    $quiz_count_stmt->close();
    
    // Get completed/passed quizzes count (only passed attempts)
    $completed_stmt = $conn->prepare("
        SELECT COUNT(*) as completed 
        FROM user_quiz_attempts 
        WHERE user_id = ? 
        AND quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) 
        AND status = 'passed'
    ");
    $completed_stmt->bind_param("ii", $user_id, $course_id);
    $completed_stmt->execute();
    $completed = $completed_stmt->get_result()->fetch_assoc()['completed'];
    $completed_stmt->close();
    
    // Calculate progress
    $progress = $total_quizzes > 0 ? round(($completed / $total_quizzes) * 100) : 0;
    $course_status = ($completed == $total_quizzes && $total_quizzes > 0) ? 'completed' : 'in_progress';
    
    error_log("Save Quiz Attempt - Progress: $progress%, Completed: $completed/$total_quizzes, Status: $course_status");
    
    // Update or insert user_courses
    $check_course = $conn->prepare("SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?");
    $check_course->bind_param("ii", $user_id, $course_id);
    $check_course->execute();
    $existing_course = $check_course->get_result()->fetch_assoc();
    $check_course->close();
    
    if ($existing_course) {
        $update_course = $conn->prepare("UPDATE user_courses SET progress = ?, status = ?, last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
        $update_course->bind_param("isii", $progress, $course_status, $user_id, $course_id);
    } else {
        $update_course = $conn->prepare("INSERT INTO user_courses (user_id, course_id, progress, status, started_at, last_accessed) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $update_course->bind_param("iiis", $user_id, $course_id, $progress, $course_status);
    }
    
    if (!$update_course->execute()) {
        error_log("Save Quiz Attempt - Course progress update failed: " . $update_course->error);
    }
    $update_course->close();
    
    // ========== CHECK IF ALL QUIZZES PASSED ==========
    $all_quizzes_passed = ($completed == $total_quizzes && $total_quizzes > 0);
    
    if ($all_quizzes_passed) {
        error_log("Save Quiz Attempt - ALL QUIZZES PASSED for course $course_id!");
        
        // Generate certificate
        generateCertificate($conn, $user_id, $course_id, $progress);
        
        // If this was a retake, update retake request status to completed
        if ($is_retake && $retake_request) {
            $update_retake = $conn->prepare("
                UPDATE retake_requests 
                SET status = 'completed', processed_at = NOW() 
                WHERE id = ?
            ");
            $update_retake->bind_param("i", $retake_request['id']);
            $update_retake->execute();
            $update_retake->close();
            error_log("Save Quiz Attempt - Updated retake request to completed for ID: " . $retake_request['id']);
        }
    }
}

echo json_encode(['success' => true, 'attempt_id' => $attempt_id]);

// ========== FUNCTION TO GENERATE CERTIFICATE ==========
function generateCertificate($conn, $user_id, $course_id, $final_score) {
    // Check if certificate already exists
    $check_stmt = $conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $user_id, $course_id);
    $check_stmt->execute();
    
    if (!$check_stmt->get_result()->fetch_assoc()) {
        // Generate unique certificate number
        $certificate_number = 'UPSKILL-' . strtoupper(uniqid()) . '-' . date('Ymd');
        
        // Get total quizzes info
        $quiz_stmt = $conn->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed 
            FROM user_quiz_attempts 
            WHERE user_id = ? AND quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?)
        ");
        $quiz_stmt->bind_param("ii", $user_id, $course_id);
        $quiz_stmt->execute();
        $quiz_data = $quiz_stmt->get_result()->fetch_assoc();
        $quiz_stmt->close();
        
        // Set expiry date (2 years from now for upskilling)
        $expiry_date = date('Y-m-d', strtotime('+2 years'));
        
        $insert_stmt = $conn->prepare("INSERT INTO certificates (user_id, course_id, certificate_number, final_score, total_quizzes_passed, total_quizzes, issued_at, expiry_date) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $insert_stmt->bind_param("iisiiis", $user_id, $course_id, $certificate_number, $final_score, $quiz_data['passed'], $quiz_data['total'], $expiry_date);
        
        if (!$insert_stmt->execute()) {
            error_log("Generate Certificate - Failed: " . $insert_stmt->error);
        } else {
            error_log("Generate Certificate - Created certificate for user $user_id, course $course_id");
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
?>