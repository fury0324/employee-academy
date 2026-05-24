<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

try {
    $courseId = isset($input['course_id']) ? intval($input['course_id']) : 0;
    
    if ($courseId > 0) {
        // Update existing course
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET title = ?, type = ?, category = ?, difficulty = ?, 
                description = ?, thumbnail_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $input['title'] ?? '',
            $input['type'] ?? 'general',
            $input['category'] ?? 'General',
            $input['difficulty'] ?? 'Beginner',
            $input['description'] ?? '',
            $input['thumbnail_url'] ?? '',
            $courseId
        ]);
        
        echo json_encode([
            'success' => true,
            'course_id' => $courseId,
            'message' => 'Course basic info saved successfully'
        ]);
    } else {
        // Insert new course
        $stmt = $pdo->prepare("
            INSERT INTO courses (title, type, category, difficulty, description, thumbnail_url, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW())
        ");
        $stmt->execute([
            $input['title'] ?? 'Untitled Course',
            $input['type'] ?? 'general',
            $input['category'] ?? 'General',
            $input['difficulty'] ?? 'Beginner',
            $input['description'] ?? '',
            $input['thumbnail_url'] ?? ''
        ]);
        $courseId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'course_id' => $courseId,
            'message' => 'Course created successfully'
        ]);
    }
    
} catch(Exception $e) {
    error_log("Error in save_step1.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to save: ' . $e->getMessage()]);
}
?>