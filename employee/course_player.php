<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    header("Location: dashboard.php?error=Invalid course ID");
    exit();
}

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: dashboard.php?error=Course not found");
    exit();
}

// Fetch video for this course
$stmt = $conn->prepare("SELECT * FROM videos WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

// Fix video URL if needed
$video_url_corrected = null;
$video_id = null;
if ($video && $video['video_url']) {
    $video_id = $video['id'];
    $video_url_corrected = $video['video_url'];
    if (!preg_match('/^https?:\/\//', $video_url_corrected) && !str_starts_with($video_url_corrected, '/upstaff')) {
        $video_url_corrected = ltrim($video_url_corrected, './');
        if (str_starts_with($video_url_corrected, 'uploads/')) {
            $video_url_corrected = '/upstaff/' . $video_url_corrected;
        } elseif (str_starts_with($video_url_corrected, '/uploads/')) {
            $video_url_corrected = '/upstaff' . $video_url_corrected;
        } elseif (!str_starts_with($video_url_corrected, '/')) {
            $video_url_corrected = '/upstaff/uploads/' . $video_url_corrected;
        }
    }
}

// Fetch quiz settings
$stmt = $conn->prepare("SELECT * FROM quiz_settings WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quiz_settings = $stmt->get_result()->fetch_assoc();

if (!$quiz_settings) {
    $quiz_settings = [
        'global_timer_minutes' => 10,
        'global_timer_seconds' => 0,
        'passing_threshold' => 70,
        'randomize_questions' => 0,
        'randomize_options' => 0,
        'hide_correct_answers' => 0,
        'disable_copy' => 1
    ];
}

// Fetch all quizzes for this course
$quizzes = [];
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY order_index ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quizzes_result = $stmt->get_result();

while ($quiz = $quizzes_result->fetch_assoc()) {
    // Fetch questions for this quiz
    $stmt2 = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY order_index ASC");
    $stmt2->bind_param("i", $quiz['id']);
    $stmt2->execute();
    $questions_result = $stmt2->get_result();
    $questions = [];
    
    while ($question = $questions_result->fetch_assoc()) {
        // Fetch options for this question
        $stmt3 = $conn->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY order_index ASC");
        $stmt3->bind_param("i", $question['id']);
        $stmt3->execute();
        $options_result = $stmt3->get_result();
        $options = [];
        
        while ($option = $options_result->fetch_assoc()) {
            $options[] = [
                'id' => $option['id'],
                'text' => $option['option_text'],
                'correct' => (bool)$option['is_correct']
            ];
        }
        $question['options'] = $options;
        $questions[] = $question;
    }
    $quiz['questions'] = $questions;
    $quizzes[] = $quiz;
}

// Fetch user progress
$stmt = $conn->prepare("SELECT * FROM user_courses WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$user_course = $stmt->get_result()->fetch_assoc();

$user_progress = $user_course ? $user_course['progress'] : 0;
$course_status = $user_course ? $user_course['status'] : 'not_started';

// ========== CHECK FOR APPROVED RETAKE REQUESTS (FIXED COLUMN NAMES) ==========
// Check for approved retake using 'requested_at' instead of 'created_at'
$stmt = $conn->prepare("
    SELECT * FROM retake_requests 
    WHERE user_id = ? AND course_id = ? AND status = 'approved'
    ORDER BY requested_at DESC LIMIT 1
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$approved_retake = $stmt->get_result()->fetch_assoc();

// Auto-reset the course if there's an approved retake and course is completed/in_progress
$retake_just_processed = false;
if ($approved_retake && ($course_status === 'completed' || $course_status === 'in_progress')) {
    // Reset user_courses progress
    $stmt = $conn->prepare("
        UPDATE user_courses 
        SET progress = 0, status = 'not_started', completed_at = NULL 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    
    // Delete quiz attempts for this course
    $stmt = $conn->prepare("
        DELETE uqa FROM user_quiz_attempts uqa
        INNER JOIN quizzes q ON q.id = uqa.quiz_id
        WHERE uqa.user_id = ? AND q.course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    
    // Delete video watched records
    $stmt = $conn->prepare("
        DELETE FROM user_video_watched 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    
    // Mark retake request as completed using 'processed_at'
    $stmt = $conn->prepare("
        UPDATE retake_requests 
        SET status = 'completed', processed_at = NOW()
        WHERE user_id = ? AND course_id = ? AND status = 'approved'
    ");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    
    $retake_just_processed = true;
    
    // Refresh course status after reset
    $course_status = 'not_started';
    $user_progress = 0;
}

// Fetch completed quizzes with details (after potential retake reset)
$completed_quizzes = [];
$quiz_attempts_details = [];
$stmt = $conn->prepare("SELECT quiz_id, score, status, completed_at FROM user_quiz_attempts WHERE user_id = ? AND quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?)");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$quiz_attempts = $stmt->get_result();

while ($attempt = $quiz_attempts->fetch_assoc()) {
    $completed_quizzes[] = $attempt['quiz_id'];
    $quiz_attempts_details[$attempt['quiz_id']] = [
        'score' => $attempt['score'],
        'status' => $attempt['status'],
        'completed_at' => $attempt['completed_at']
    ];
}

// Check if video is watched from database
$video_watched = false;
if ($video_id) {
    $stmt = $conn->prepare("SELECT id FROM user_video_watched WHERE user_id = ? AND course_id = ? AND video_id = ?");
    $stmt->bind_param("iii", $user_id, $course_id, $video_id);
    $stmt->execute();
    $video_watched_record = $stmt->get_result()->fetch_assoc();
    if ($video_watched_record) {
        $video_watched = true;
    }
}

// Check for pending retake request (using requested_at)
$stmt = $conn->prepare("
    SELECT * FROM retake_requests 
    WHERE user_id = ? AND course_id = ? AND status = 'pending'
    ORDER BY requested_at DESC LIMIT 1
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$pending_retake = $stmt->get_result()->fetch_assoc();

// Check for rejected retake request (using requested_at)
$stmt = $conn->prepare("
    SELECT * FROM retake_requests 
    WHERE user_id = ? AND course_id = ? AND status = 'rejected'
    ORDER BY requested_at DESC LIMIT 1
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$rejected_retake = $stmt->get_result()->fetch_assoc();

// Prepare data for JavaScript
$course_data = [
    'id' => $course['id'],
    'title' => $course['title'],
    'course_type' => $course['type'] ?? 'general',  // ← ADD THIS LINE
    'category' => $course['category'] ?? 'General',
    'difficulty' => $course['difficulty'] ?? 'Beginner',
    'description' => strip_tags($course['description'] ?? 'No description available.'),
    'thumbnail_url' => $course['thumbnail_url'] ?? null,
    'status' => $course['status'],
    'video' => $video ? [
        'title' => $video['title'] ?? 'Course Video',
        'description' => strip_tags($video['description'] ?? 'Watch this video to learn the core concepts.'),
        'video_url' => $video_url_corrected ?? null,
        'video_type' => $video['video_type'] ?? 'upload',
        'id' => $video['id']
    ] : null,
    'quiz_settings' => $quiz_settings,
    'quizzes' => $quizzes,
    'total_quizzes' => count($quizzes),
    'completed_quizzes' => $completed_quizzes,
    'quiz_attempts_details' => $quiz_attempts_details,
    'video_watched' => $video_watched,
    'course_progress' => $user_progress,
    'course_status' => $course_status,
    'retake_just_processed' => $retake_just_processed,
    'pending_retake' => $pending_retake ? true : false,
    'rejected_retake' => $rejected_retake ? [
        'reason' => $rejected_retake['admin_notes'] ?? 'No reason provided'
    ] : null
];

$user_name = $_SESSION['firstname'] ?? 'Employee';
$user_email = $_SESSION['email'] ?? '';
$user_initials = strtoupper(substr($user_name, 0, 2));

$page_title = htmlspecialchars($course['title']) . " - Course Player";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> · UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes countdownPulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .fade-slide-up { animation: fadeSlideUp 0.4s ease-out; }
        .pulse-animation { animation: pulse 1s infinite; }
        
        .dot {
            animation: bounce 1s ease infinite;
        }
        
        .dot-1 { animation-delay: 0s; }
        .dot-2 { animation-delay: 0.2s; }
        .dot-3 { animation-delay: 0.4s; }
        
        .countdown-number {
            animation: countdownPulse 0.5s ease-out;
        }
        
        .quiz-option-card {
            transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            cursor: pointer;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .quiz-option-card:hover:not(.disabled) {
            transform: translateX(6px);
            border-color: #818cf8;
            box-shadow: 0 8px 18px -8px rgba(79, 70, 229, 0.25);
            background: #faf9ff;
        }
        
        .quiz-option-card.selected {
            background: linear-gradient(105deg, #4f46e5 0%, #6366f1 100%);
            border-color: #4f46e5;
            color: white;
        }
        
        .quiz-option-card.selected .option-letter {
            background: white;
            color: #4f46e5;
        }
        
        .quiz-option-card.correct {
            background: linear-gradient(105deg, #10b981 0%, #059669 100%);
            border-color: #10b981;
            color: white;
        }
        
        .quiz-option-card.correct .option-letter {
            background: white;
            color: #10b981;
        }
        
        .quiz-option-card.wrong {
            background: linear-gradient(105deg, #ef4444 0%, #dc2626 100%);
            border-color: #ef4444;
            color: white;
        }
        
        .quiz-option-card.wrong .option-letter {
            background: white;
            color: #ef4444;
        }
        
        .option-letter {
            width: 2.2rem;
            height: 2.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 800;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            background: #0f172a;
            border-radius: 1rem;
        }
        
        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .toast-success { background: linear-gradient(135deg, #10b981, #059669); }
        .toast-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .toast-info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        #quizQuestionsContainer::-webkit-scrollbar {
            width: 6px;
        }
        
        #quizQuestionsContainer::-webkit-scrollbar-track {
            background: #f1f1f1; border-radius: 10px;
        }
        
        #quizQuestionsContainer::-webkit-scrollbar-thumb {
            background: #cbd5e1; border-radius: 10px;
        }
        
        .quiz-disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .notification-banner {
            animation: fadeSlideUp 0.4s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <!-- Retake Notification Banner -->
        <?php if ($retake_just_processed): ?>
        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl notification-banner">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-green-600 text-2xl">refresh</span>
                <div class="flex-1">
                    <h4 class="font-bold text-green-800">Course Reset Successfully!</h4>
                    <p class="text-sm text-green-600">Your retake request has been approved. You can now restart the course from the beginning.</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <?php elseif ($pending_retake): ?>
        <div class="mb-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl notification-banner">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-yellow-600 text-2xl">hourglass_empty</span>
                <div class="flex-1">
                    <h4 class="font-bold text-yellow-800">Retake Request Pending</h4>
                    <p class="text-sm text-yellow-600">Your request to retake this course is awaiting admin approval. You'll be notified once it's processed.</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-yellow-600 hover:text-yellow-800">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <?php elseif ($rejected_retake): ?>
        <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl notification-banner">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600 text-2xl">cancel</span>
                <div class="flex-1">
                    <h4 class="font-bold text-red-800">Retake Request Denied</h4>
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($rejected_retake['admin_notes'] ?? 'No reason provided'); ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Course Header -->
        <div class="mb-8 fade-slide-up">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-800" id="courseTitle"><?php echo htmlspecialchars($course['title']); ?></h2>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs font-medium"><?php echo htmlspecialchars($course['category'] ?? 'General'); ?></span>
                        <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-xs font-medium"><?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?></span>
                        <span class="px-3 py-1 rounded-lg bg-green-50 text-green-600 text-xs font-medium">Published</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="dashboard.php" class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Dashboard
                    </a>
                    <?php if ($course_status === 'completed' && !$pending_retake && !$rejected_retake): ?>
                    <button id="requestRetakeBtn" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-md">
                        <span class="material-symbols-outlined text-base">refresh</span>
                        Request Retake
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- LEFT COLUMN: Video Player -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                    <div class="video-container bg-gradient-to-br from-slate-800 to-slate-900 relative" id="videoContainer">
                        <video id="videoPlayer" class="w-full h-full" controls style="display: none;">
                            <source id="videoSource" src="" type="video/mp4">
                            Your browser does not support video.
                        </video>
                        <iframe id="youtubeFrame" class="w-full h-full hidden" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        <div id="noVideoMessage" class="absolute inset-0 flex items-center justify-center text-white flex-col gap-3 hidden">
                            <span class="material-symbols-outlined text-5xl">videocam_off</span>
                            <p class="text-sm">No video available for this course</p>
                        </div>
                        <div id="videoLockedOverlay" class="absolute inset-0 bg-black/70 flex items-center justify-center flex-col hidden">
                            <span class="material-symbols-outlined text-5xl text-white mb-3">lock</span>
                            <p class="text-white text-lg font-semibold">Video Completed</p>
                            <p class="text-white/70 text-sm">You've already watched this video</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 id="videoTitle" class="text-xl font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($video['title'] ?? 'Course Video'); ?></h3>
                                <p id="videoDescription" class="text-slate-500 text-sm"><?php echo htmlspecialchars(strip_tags($video['description'] ?? 'Watch this video to learn the core concepts.')); ?></p>
                            </div>
                            <div id="videoStatusBadge" class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">⚠ Not Watched</div>
                        </div>
                        <button id="markWatchedBtn" class="mt-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-md">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Mark as Watched
                        </button>
                    </div>
                </div>
                
                <!-- About This Course -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h4 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500 text-xl">info</span>
                        About This Course
                    </h4>
                    <p class="text-slate-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars(strip_tags($course['description'] ?? 'No description available.'))); ?></p>
                </div>
            </div>

            <!-- RIGHT COLUMN: Progress & Quizzes -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Progress Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium opacity-90">Overall Progress</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold" id="progressPercentDisplay">0</span>
                            <span class="text-sm opacity-90">%</span>
                        </div>
                    </div>
                    <div class="w-full bg-white/30 rounded-full h-2 mb-4">
                        <div id="progressBar" class="bg-white rounded-full h-2 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">play_circle</span> Video:</span>
                            <span id="videoStatusText" class="font-medium">❌ No</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">quiz</span> Quizzes:</span>
                            <span><span id="quizzesCompletedCount">0</span>/<span id="totalQuizzesCount"><?php echo count($quizzes); ?></span></span>
                        </div>
                    </div>
                </div>

                <!-- Start Quiz Button -->
                <div id="startQuizSection" class="hidden">
                    <button id="startQuizBtn" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-lg">
                        <span class="material-symbols-outlined text-xl">play_arrow</span>
                        Start Quizzes
                    </button>
                </div>

                <!-- Quiz List -->
                <div id="quizListSection" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hidden">
                    <div class="border-b border-slate-200 px-5 py-4 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-purple-500">quiz</span>
                            Quiz Modules
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Complete all quizzes to pass the course</p>
                    </div>
                    <div id="quizList" class="divide-y divide-slate-100"></div>
                </div>

                <!-- Final Results Card -->
                <div id="finalResultCard" class="rounded-2xl p-6 shadow-lg hidden"></div>
            </div>
        </div>
    </main>

    <!-- Quiz Modal -->
    <div id="quizModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-white/95 backdrop-blur-sm"></div>
        
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all duration-500 fade-slide-up border border-gray-100">
                
                <div class="relative bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border-b border-gray-100 px-6 py-5">
                    <div class="text-center mb-4">
                        <h3 id="quizModalTitle" class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Quiz</h3>
                        <p class="text-xs text-gray-400 mt-1">Test your knowledge</p>
                    </div>
                    
                    <div class="flex justify-center">
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-4 shadow-lg text-center min-w-[180px]">
                            <div class="flex items-center justify-center gap-2 text-white/80 text-sm mb-1">
                                <span class="material-symbols-outlined text-base animate-pulse">timer</span>
                                <span>Time Remaining</span>
                            </div>
                            <div class="text-white">
                                <span id="timerDisplay" class="font-mono text-4xl font-black tracking-wider">00:00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="quizQuestionsContainer" class="p-6 max-h-[60vh] overflow-y-auto bg-white"></div>
                
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-3 flex justify-between items-center text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">help</span>
                        <span id="questionProgressText">Question 1 of 1</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">check_circle</span>
                        <span>Select answer to continue</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Countdown Overlay -->
    <div id="countdownOverlay" class="hidden fixed inset-0 z-[100] flex items-center justify-center">
        <div class="absolute inset-0 bg-white/95 backdrop-blur-sm"></div>
        
        <div class="relative z-10 bg-white rounded-3xl shadow-2xl p-12 text-center max-w-md w-full mx-4 transform-gpu fade-slide-up border border-gray-100">
            <div class="relative mb-8">
                <svg class="w-40 h-40 mx-auto transform -rotate-90">
                    <defs>
                        <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8b5cf6"/>
                            <stop offset="50%" style="stop-color:#ec4899"/>
                            <stop offset="100%" style="stop-color:#f59e0b"/>
                        </linearGradient>
                    </defs>
                    <circle cx="80" cy="80" r="72" stroke="#f0f0f0" stroke-width="4" fill="none"/>
                    <circle id="countdownRing" cx="80" cy="80" r="72" stroke="url(#ringGradient)" stroke-width="6" fill="none" 
                            stroke-dasharray="452" stroke-dashoffset="0" 
                            style="filter: drop-shadow(0 0 10px rgba(139,92,246,0.2)); transition: stroke-dashoffset 1s linear;"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div id="countdownNumber" class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 countdown-number">3</div>
                </div>
            </div>
            
            <div id="nextQuizName" class="text-xl font-bold text-gray-700 mb-2">Loading next quiz...</div>
            
            <div class="flex justify-center gap-3 mt-6">
                <div class="dot dot-1 w-2 h-2 bg-indigo-300 rounded-full"></div>
                <div class="dot dot-2 w-2 h-2 bg-indigo-400 rounded-full"></div>
                <div class="dot dot-3 w-2 h-2 bg-indigo-500 rounded-full"></div>
            </div>
            
            <p class="text-gray-400 text-sm mt-6 animate-pulse">Preparing your quiz...</p>
        </div>
    </div>

    <!-- Retake Request Modal -->
    <div id="retakeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 fade-slide-up">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-orange-600 text-3xl">refresh</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Request Course Retake</h3>
                    <p class="text-sm text-slate-500 mt-2">Please provide a reason for requesting to retake this course.</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason for retake request</label>
                    <textarea id="retakeReason" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="I would like to retake this course because..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button id="cancelRetakeBtn" class="flex-1 px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                    <button id="submitRetakeBtn" class="flex-1 px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-lg font-medium transition">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 pointer-events-none"></div>

    <script>
        // Course data from PHP
        const courseData = <?php echo json_encode($course_data, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const userId = <?php echo (int)$user_id; ?>;
        const courseId = <?php echo (int)$course_id; ?>;
        
        console.log('Course Data loaded:', courseData);
        
        // State variables
        let completedQuizzes = [...courseData.completed_quizzes];
        let quizAttemptsDetails = {...courseData.quiz_attempts_details};
        let quizResults = [];
        let videoWatched = courseData.video_watched || false;
        let courseCompleted = courseData.course_status === 'completed' && completedQuizzes.length === courseData.total_quizzes;
        let currentQuizIndex = 0;
        let currentQuizData = null;
        let currentQuestionIndex = 0;
        let currentAnswers = {};
        let quizTimerInterval = null;
        let timeRemaining = 0;
        let isQuizActive = false;
        
        // Quiz settings
        let quizSettings = {
            global_timer_minutes: courseData.quiz_settings?.global_timer_minutes ?? 0,
            global_timer_seconds: courseData.quiz_settings?.global_timer_seconds ?? 10,
            passing_threshold: courseData.quiz_settings?.passing_threshold ?? 70,
            randomize_questions: courseData.quiz_settings?.randomize_questions == 1,
            randomize_options: courseData.quiz_settings?.randomize_options == 1,
            hide_correct_answers: courseData.quiz_settings?.hide_correct_answers == 1,
            disable_copy: courseData.quiz_settings?.disable_copy == 1
        };
        
        // Anti-cheat protection
        if (quizSettings.disable_copy) {
            document.body.style.userSelect = 'none';
            document.body.style.webkitUserSelect = 'none';
            
            document.addEventListener('copy', (e) => { e.preventDefault(); showToast('🔒 Copying is disabled', 'warning'); });
            document.addEventListener('cut', (e) => { e.preventDefault(); showToast('🔒 Cutting is disabled', 'warning'); });
            document.addEventListener('paste', (e) => { e.preventDefault(); showToast('🔒 Pasting is disabled', 'warning'); });
            document.addEventListener('contextmenu', (e) => { e.preventDefault(); showToast('🔒 Right-click is disabled', 'warning'); });
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && (e.key === 'c' || e.key === 'C' || e.key === 'v' || e.key === 'V' || e.key === 'x' || e.key === 'X')) {
                    e.preventDefault();
                    showToast('🔒 Keyboard shortcuts are disabled', 'warning');
                }
                if (e.key === 'F12') {
                    e.preventDefault();
                    showToast('🔒 Developer tools are restricted', 'warning');
                }
            });
        }
        
        // Utility Functions
        function showToast(msg, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            let bgClass = '', icon = '';
            
            if (type === 'success') { bgClass = 'toast-success'; icon = 'check_circle'; }
            else if (type === 'warning') { bgClass = 'toast-warning'; icon = 'warning'; }
            else if (type === 'info') { bgClass = 'toast-info'; icon = 'info'; }
            else { bgClass = 'toast-error'; icon = 'error'; }
            
            toast.className = `pointer-events-auto flex items-center gap-2 px-5 py-3 ${bgClass} text-white text-sm font-medium rounded-xl shadow-lg opacity-0 translate-y-2 transition-all duration-300 z-50`;
            toast.innerHTML = `<span class="material-symbols-outlined text-base">${icon}</span>${msg}`;
            container.appendChild(toast);
            
            setTimeout(() => toast.classList.add('opacity-100', 'translate-y-0'), 10);
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
        }
        
        function fixMediaUrl(url) {
            if (!url || url === '') return null;
            let cleanUrl = url.trim();
            if (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://')) return cleanUrl;
            cleanUrl = cleanUrl.replace(/^(\.\.\/|\.\/)+/g, '');
            if (cleanUrl.startsWith('/upstaff/')) return cleanUrl;
            if (cleanUrl.startsWith('/uploads/')) return '/upstaff' + cleanUrl;
            if (cleanUrl.startsWith('uploads/')) return '/upstaff/' + cleanUrl;
            return '/upstaff/uploads/' + cleanUrl;
        }
        
        function requestRetake() {
            const reason = document.getElementById('retakeReason').value.trim();
            if (!reason) {
                showToast('Please provide a reason for your retake request', 'warning');
                return;
            }
            
            fetch('request_retake.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    course_id: courseId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Retake request submitted successfully!', 'success');
                    document.getElementById('retakeModal').classList.add('hidden');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to submit request', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
            });
        }
        
        function saveProgressToServer() {
            fetch('save_course_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    course_id: courseId,
                    progress: Math.round((completedQuizzes.length / courseData.total_quizzes) * 100),
                    status: completedQuizzes.length === courseData.total_quizzes ? 'completed' : 'in_progress',
                    video_watched: videoWatched,
                    video_id: courseData.video?.id || null
                })
            }).catch(err => console.error('Error saving progress:', err));
        }
        
        function saveQuizAttempt(quizId, score, status, detailedAnswers, timeTaken) {
            return fetch('save_quiz_attempt.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    quiz_id: quizId,
                    course_id: courseId,
                    score: score,
                    status: status,
                    answers: detailedAnswers,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) return data;
                showToast(data.message || 'Failed to save quiz results', 'error');
                throw new Error(data.error);
            })
            .catch(err => {
                console.error('Error saving quiz attempt:', err);
                showToast('Network error saving quiz results', 'error');
                throw err;
            });
        }
        
        function markVideoWatched() {
            if (videoWatched) {
                showToast('Video already marked as watched!', 'info');
                return;
            }
            if (confirm('⚠️ Once you mark this video as watched, it will be locked and cannot be replayed. Are you sure?')) {
                videoWatched = true;
                fetch('save_course_progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        course_id: courseId,
                        progress: Math.round((completedQuizzes.length / courseData.total_quizzes) * 100),
                        status: completedQuizzes.length === courseData.total_quizzes ? 'completed' : 'in_progress',
                        video_watched: true,
                        video_id: courseData.video?.id || null
                    })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          updateProgress();
                          showToast('✅ Video marked as watched! Quizzes are now unlocked.', 'success');
                          setTimeout(() => location.reload(), 1500);
                      }
                  })
                  .catch(err => {
                      showToast('Error saving video status', 'error');
                      videoWatched = false;
                  });
            }
        }
        
        function updateProgress() {
            const totalQuizzes = courseData.total_quizzes || 0;
            const completed = completedQuizzes.length;
            let percent = totalQuizzes > 0 ? Math.round((completed / totalQuizzes) * 100) : 0;
            if (videoWatched && totalQuizzes === 0) percent = 100;
            
            document.getElementById('progressPercentDisplay').textContent = percent;
            document.getElementById('progressBar').style.width = `${percent}%`;
            document.getElementById('quizzesCompletedCount').textContent = completed;
            
            const badge = document.getElementById('videoStatusBadge');
            const markBtn = document.getElementById('markWatchedBtn');
            const overlay = document.getElementById('videoLockedOverlay');
            const videoPlayer = document.getElementById('videoPlayer');
            
            if (videoWatched) {
                if (badge) {
                    badge.className = 'px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600';
                    badge.textContent = '✓ Watched';
                }
                if (markBtn) {
                    markBtn.disabled = true;
                    markBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                if (overlay) overlay.classList.remove('hidden');
                if (videoPlayer) {
                    videoPlayer.controls = false;
                    videoPlayer.style.pointerEvents = 'none';
                    videoPlayer.style.opacity = '0.5';
                }
                document.getElementById('startQuizSection')?.classList.remove('hidden');
                document.getElementById('quizListSection')?.classList.remove('hidden');
                displayQuizList();
            } else {
                if (badge) {
                    badge.className = 'px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600';
                    badge.textContent = '⚠ Not Watched';
                }
                if (markBtn) {
                    markBtn.disabled = false;
                    markBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                if (overlay) overlay.classList.add('hidden');
                if (videoPlayer) {
                    videoPlayer.controls = true;
                    videoPlayer.style.pointerEvents = 'auto';
                    videoPlayer.style.opacity = '1';
                }
                document.getElementById('startQuizSection')?.classList.add('hidden');
                document.getElementById('quizListSection')?.classList.add('hidden');
            }
            
            document.getElementById('videoStatusText').innerHTML = videoWatched ? '✅ Yes' : '❌ No';
            if (courseCompleted) showFinalResults();
        }
        
        function displayQuizList() {
            const container = document.getElementById('quizList');
            const quizzes = courseData?.quizzes || [];
            if (quizzes.length === 0) {
                container.innerHTML = '<div class="p-6 text-center text-slate-400">No quizzes available</div>';
                return;
            }
            
            container.innerHTML = quizzes.map((quiz, index) => {
                const isCompleted = completedQuizzes.includes(quiz.id);
                const attemptDetails = quizAttemptsDetails[quiz.id];
                const score = attemptDetails ? attemptDetails.score : null;
                const status = attemptDetails ? attemptDetails.status : null;
                
                return `
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition group ${isCompleted ? 'quiz-disabled' : ''}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl ${isCompleted ? 'bg-green-100' : 'bg-purple-100'} flex items-center justify-center group-hover:scale-110 transition">
                                <span class="material-symbols-outlined ${isCompleted ? 'text-green-600' : 'text-purple-600'}">
                                    ${isCompleted ? 'check_circle' : 'quiz'}
                                </span>
                            </div>
                            <div>
                                <h4 class="font-medium text-slate-800">Quiz ${index + 1}: ${escapeHtml(quiz.title)}</h4>
                                <p class="text-xs text-slate-400">${quiz.questions?.length || 0} questions</p>
                                ${score !== null ? `<p class="text-xs font-medium ${status === 'passed' ? 'text-green-600' : 'text-red-600'} mt-1">Score: ${score}% (${status === 'passed' ? 'PASSED' : 'FAILED'})</p>` : ''}
                            </div>
                        </div>
                        <div>${isCompleted ? '<span class="text-xs text-green-600">✓ Completed</span>' : '<span class="text-xs text-purple-600">⬤ Ready</span>'}</div>
                    </div>
                `;
            }).join('');
        }
        
        function setupVideoPlayer(videoUrl) {
            const videoPlayer = document.getElementById('videoPlayer');
            const youtubeFrame = document.getElementById('youtubeFrame');
            const noVideoMessage = document.getElementById('noVideoMessage');
            const videoSource = document.getElementById('videoSource');
            
            videoPlayer.style.display = 'none';
            youtubeFrame.classList.add('hidden');
            noVideoMessage.classList.add('hidden');
            
            if (!videoUrl) {
                noVideoMessage.classList.remove('hidden');
                return false;
            }
            
            let finalUrl = fixMediaUrl(videoUrl);
            
            if (finalUrl.includes('youtube.com') || finalUrl.includes('youtu.be')) {
                let embedUrl = finalUrl;
                if (finalUrl.includes('watch?v=')) {
                    const videoId = finalUrl.split('v=')[1]?.split('&')[0];
                    embedUrl = `https://www.youtube.com/embed/${videoId}?modestbranding=1&rel=0&autoplay=0`;
                } else if (finalUrl.includes('youtu.be/')) {
                    const videoId = finalUrl.split('youtu.be/')[1]?.split('?')[0];
                    embedUrl = `https://www.youtube.com/embed/${videoId}?modestbranding=1&rel=0&autoplay=0`;
                }
                youtubeFrame.src = embedUrl;
                youtubeFrame.classList.remove('hidden');
                return true;
            }
            
            videoSource.src = finalUrl;
            videoPlayer.load();
            videoPlayer.style.display = 'block';
            
            videoPlayer.onerror = function(e) {
                noVideoMessage.classList.remove('hidden');
                videoPlayer.style.display = 'none';
                showToast('Failed to load video file', 'error');
            };
            
            return true;
        }
        
        function startFirstQuiz() {
            if (courseCompleted) {
                showToast('Course already completed!', 'warning');
                return;
            }
            if (!videoWatched) {
                showToast('Please mark video as watched first!', 'warning');
                return;
            }
            const quizzes = courseData?.quizzes || [];
            if (quizzes.length > 0) {
                const firstUncompleted = quizzes.findIndex(q => !completedQuizzes.includes(q.id));
                if (firstUncompleted !== -1) {
                    startQuiz(firstUncompleted);
                } else {
                    showToast('All quizzes completed!', 'success');
                    checkCourseCompletion();
                }
            }
        }
        
        function startQuiz(quizIndex) {
            if (isQuizActive) return;
            const quiz = courseData.quizzes[quizIndex];
            if (completedQuizzes.includes(quiz.id)) {
                showToast('You have already completed this quiz! Retakes are not allowed.', 'warning');
                return;
            }
            
            isQuizActive = true;
            currentQuizIndex = quizIndex;
            currentQuizData = JSON.parse(JSON.stringify(quiz));
            
            if (quizSettings.randomize_questions) {
                currentQuizData.questions = shuffleArray(currentQuizData.questions);
            }
            if (quizSettings.randomize_options) {
                currentQuizData.questions.forEach(question => {
                    if (question.type === 'mc' && question.options) {
                        question.options = shuffleArray(question.options);
                    }
                });
            }
            
            currentQuestionIndex = 0;
            currentAnswers = {};
            
            let totalSeconds = (quizSettings.global_timer_minutes * 60) + quizSettings.global_timer_seconds;
            if (totalSeconds <= 0) totalSeconds = 60;
            timeRemaining = totalSeconds;
            
            const modal = document.getElementById('quizModal');
            document.getElementById('quizModalTitle').textContent = `${currentQuizData.title} (${quizIndex + 1}/${courseData.quizzes.length})`;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                if (modal.requestFullscreen) modal.requestFullscreen();
            }, 100);
            
            startTimer();
            renderCurrentQuestion();
        }
        
        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }
        
        function startTimer() {
            if (quizTimerInterval) clearInterval(quizTimerInterval);
            const timerDisplay = document.getElementById('timerDisplay');
            
            function updateTimerDisplay() {
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                if (timeRemaining <= 0) {
                    clearInterval(quizTimerInterval);
                    submitQuiz();
                }
                if (timeRemaining <= 60) {
                    timerDisplay.classList.add('text-red-300');
                } else {
                    timerDisplay.classList.remove('text-red-300');
                }
            }
            updateTimerDisplay();
            quizTimerInterval = setInterval(() => {
                if (timeRemaining > 0 && isQuizActive) {
                    timeRemaining--;
                    updateTimerDisplay();
                }
            }, 1000);
        }
        
        function renderCurrentQuestion() {
            const container = document.getElementById('quizQuestionsContainer');
            const q = currentQuizData.questions[currentQuestionIndex];
            const totalQuestions = currentQuizData.questions.length;
            
            if (!q || !q.options) return;
            
            const hideAnswers = quizSettings.hide_correct_answers;
            let correctOptionIndex = q.options.findIndex(opt => opt.correct === true);
            
            let optionsHtml = `<div class="space-y-3">
                ${q.options.map((opt, idx) => {
                    const letter = String.fromCharCode(65 + idx);
                    const isCorrect = correctOptionIndex === idx;
                    const showCorrectBadge = (!hideAnswers && isCorrect);
                    return `<div class="quiz-option-card" data-opt="${idx}" data-correct="${isCorrect}">
                        <div class="option-letter">${letter}</div>
                        <span class="flex-1">${escapeHtml(opt.text)}</span>
                        ${showCorrectBadge ? '<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full ml-2">✓ Correct</span>' : ''}
                    </div>`;
                }).join('')}
            </div>`;
            
            container.innerHTML = `
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-slate-500">Question ${currentQuestionIndex + 1} of ${totalQuestions}</span>
                        <span class="text-sm font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-600">Quiz ${currentQuizIndex + 1}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-600 rounded-full h-2 transition-all" style="width: ${((currentQuestionIndex) / totalQuestions) * 100}%"></div>
                    </div>
                </div>
                <h4 class="font-semibold text-slate-800 mb-6 text-xl">${escapeHtml(q.question_text)}</h4>
                ${optionsHtml}
                <div id="feedback" class="mt-4 text-sm hidden"></div>
            `;
            
            document.getElementById('questionProgressText').textContent = `Question ${currentQuestionIndex + 1} of ${totalQuestions}`;
            
            document.querySelectorAll('.quiz-option-card').forEach(opt => {
                opt.addEventListener('click', () => {
                    if (opt.classList.contains('disabled')) return;
                    
                    const isCorrect = opt.dataset.correct === 'true';
                    const optIdx = parseInt(opt.dataset.opt);
                    const feedbackDiv = document.getElementById('feedback');
                    
                    document.querySelectorAll('.quiz-option-card').forEach(el => {
                        el.style.pointerEvents = 'none';
                        el.classList.add('disabled');
                    });
                    
                    opt.classList.add('selected');
                    
                    if (isCorrect) {
                        opt.classList.add('correct');
                        feedbackDiv.innerHTML = `<span class="text-green-600 flex items-center gap-2">✓ Correct! Great job!</span>`;
                        feedbackDiv.classList.add('bg-green-50', 'p-3', 'rounded-lg');
                    } else {
                        opt.classList.add('wrong');
                        if (!quizSettings.hide_correct_answers) {
                            const correctOpt = Array.from(document.querySelectorAll('.quiz-option-card')).find(el => el.dataset.correct === 'true');
                            if (correctOpt) correctOpt.classList.add('correct');
                            feedbackDiv.innerHTML = `<span class="text-red-600 flex items-center gap-2">✗ Incorrect. Correct answer highlighted.</span>`;
                        } else {
                            feedbackDiv.innerHTML = `<span class="text-red-600 flex items-center gap-2">✗ Incorrect. No details available.</span>`;
                        }
                        feedbackDiv.classList.add('bg-red-50', 'p-3', 'rounded-lg');
                    }
                    
                    feedbackDiv.classList.remove('hidden');
                    currentAnswers[currentQuestionIndex] = { selected: optIdx, correct: isCorrect };
                    
                    setTimeout(() => {
                        if (currentQuestionIndex + 1 < totalQuestions) {
                            currentQuestionIndex++;
                            renderCurrentQuestion();
                        } else {
                            submitQuiz();
                        }
                    }, 1500);
                });
            });
        }
        
        function submitQuiz() {
            if (quizTimerInterval) clearInterval(quizTimerInterval);
            
            let correct = 0;
            let detailedAnswers = [];
            
            currentQuizData.questions.forEach((question, idx) => {
                const userAnswer = currentAnswers[idx];
                const isCorrect = userAnswer && userAnswer.correct;
                if (isCorrect) correct++;
                
                detailedAnswers.push({
                    question_id: question.id,
                    question_text: question.question_text,
                    user_answer: userAnswer ? userAnswer.selected : null,
                    is_correct: isCorrect,
                    correct_answer: question.options.find(opt => opt.correct === true)?.text
                });
            });
            
            const total = currentQuizData.questions.length;
            const score = Math.round((correct / total) * 100);
            const passed = score >= quizSettings.passing_threshold;
            const status = passed ? 'passed' : 'failed';
            
            const totalTime = (quizSettings.global_timer_minutes * 60) + quizSettings.global_timer_seconds;
            const timeTaken = totalTime - timeRemaining;
            
            if (!completedQuizzes.includes(currentQuizData.id)) {
                saveQuizAttempt(currentQuizData.id, score, status, detailedAnswers, timeTaken)
                    .then(data => {
                        if (data.success) {
                            completedQuizzes.push(currentQuizData.id);
                            quizResults.push({
                                quizId: currentQuizData.id,
                                quizTitle: currentQuizData.title,
                                score: score,
                                passed: passed,
                                totalQuestions: total,
                                correctAnswers: correct
                            });
                            quizAttemptsDetails[currentQuizData.id] = {
                                score: score,
                                status: status,
                                completed_at: new Date().toISOString()
                            };
                            saveProgressToServer();
                            
                            showToast(passed ? `✅ Quiz passed! Score: ${score}%` : `❌ Score: ${score}%`, passed ? 'success' : 'warning');
                            updateProgress();
                            
                            const nextQuizIndex = currentQuizIndex + 1;
                            if (nextQuizIndex < courseData.quizzes.length && !courseCompleted) {
                                showEnhancedCountdown(nextQuizIndex);
                            } else {
                                checkCourseCompletion();
                            }
                        }
                    })
                    .catch(err => console.error('Error saving quiz:', err));
            }
            
            isQuizActive = false;
            if (document.fullscreenElement) document.exitFullscreen();
            document.getElementById('quizModal').classList.add('hidden');
            document.body.style.overflow = '';
        }
        
        function showEnhancedCountdown(nextQuizIndex) {
            const overlay = document.getElementById('countdownOverlay');
            const countdownNumber = document.getElementById('countdownNumber');
            const nextQuizName = document.getElementById('nextQuizName');
            const countdownRing = document.getElementById('countdownRing');
            const nextQuiz = courseData.quizzes[nextQuizIndex];
            
            nextQuizName.textContent = `Quiz ${nextQuizIndex + 1}: ${nextQuiz.title}`;
            overlay.classList.remove('hidden');
            
            let count = 3;
            const circumference = 452;
            
            function updateCountdown() {
                countdownNumber.textContent = count;
                countdownNumber.style.animation = 'none';
                countdownNumber.offsetHeight;
                countdownNumber.style.animation = 'countdownPulse 0.5s ease-out';
                
                const progress = (count - 1) / 3;
                const offset = circumference * progress;
                countdownRing.style.strokeDashoffset = offset;
                
                if (count > 1) {
                    count--;
                    setTimeout(updateCountdown, 1000);
                } else {
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                        startQuiz(nextQuizIndex);
                    }, 1000);
                }
            }
            
            countdownRing.style.strokeDashoffset = circumference;
            count = 3;
            updateCountdown();
        }
        
        function checkCourseCompletion() {
            const totalQuizzes = courseData?.quizzes?.length || 0;
            if (completedQuizzes.length === totalQuizzes && totalQuizzes > 0 && !courseCompleted) {
                courseCompleted = true;
                saveProgressToServer();
                showFinalResults();
            }
        }
        
        function showFinalResults() {
            const totalQuizzes = quizResults.length;
            const passedQuizzes = quizResults.filter(r => r.passed).length;
            const failedQuizzes = totalQuizzes - passedQuizzes;
            
            let totalScore = 0;
            quizResults.forEach(r => { totalScore += r.score; });
            const overallPercentage = totalQuizzes > 0 ? Math.round(totalScore / totalQuizzes) : 0;
            const courseStatus = overallPercentage >= quizSettings.passing_threshold ? 'PASSED' : 'FAILED';
            
            const resultCard = document.getElementById('finalResultCard');
            
            if (courseStatus === 'PASSED') {
                resultCard.className = 'rounded-2xl p-6 shadow-lg bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 fade-slide-up';
                resultCard.innerHTML = `
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                            <span class="material-symbols-outlined text-green-600 text-4xl">celebration</span>
                        </div>
                        <h4 class="font-bold text-green-800 text-2xl mb-2">🎉 Course Completed! 🎉</h4>
                        <p class="text-green-600 text-sm mb-4">Congratulations! You have successfully completed this course.</p>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 rounded-full mb-6">
                            <span class="material-symbols-outlined text-green-600 text-sm">check_circle</span>
                            <span class="text-green-700 font-semibold">${courseStatus}</span>
                        </div>
                        <div class="bg-white rounded-xl p-5 mb-5 shadow-sm">
                            <h5 class="font-semibold text-gray-700 mb-3">📊 Course Summary</h5>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center"><p class="text-3xl font-bold text-green-600">${overallPercentage}%</p><p class="text-xs text-gray-500">Overall Score</p></div>
                                <div class="text-center"><p class="text-3xl font-bold text-green-600">${passedQuizzes}/${totalQuizzes}</p><p class="text-xs text-gray-500">Quizzes Passed</p></div>
                                <div class="text-center"><p class="text-xl font-bold text-green-600">✅ ${passedQuizzes}</p><p class="text-xs text-gray-500">Passed</p></div>
                                <div class="text-center"><p class="text-xl font-bold text-red-500">❌ ${failedQuizzes}</p><p class="text-xs text-gray-500">Failed</p></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mb-5">Passing threshold: ${quizSettings.passing_threshold}%</div>
                        <button id="downloadCertificateBtn" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-bold transition shadow-md flex items-center justify-center gap-2 mx-auto w-full sm:w-auto">
                            <span class="material-symbols-outlined text-xl">download</span>
                            Download Certificate
                        </button>
                    </div>
                `;
                document.getElementById('downloadCertificateBtn')?.addEventListener('click', () => downloadCertificate());
            } else {
                resultCard.className = 'rounded-2xl p-6 shadow-lg bg-gradient-to-br from-red-50 to-rose-50 border border-red-200 fade-slide-up';
                resultCard.innerHTML = `
                    <div class="text-center">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-red-600 text-4xl">sentiment_dissatisfied</span>
                        </div>
                        <h4 class="font-bold text-red-800 text-2xl mb-2">⚠️ Course Not Passed</h4>
                        <p class="text-red-600 text-sm mb-4">You need to achieve a higher score to pass this course.</p>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 rounded-full mb-6">
                            <span class="material-symbols-outlined text-red-600 text-sm">error</span>
                            <span class="text-red-700 font-semibold">${courseStatus}</span>
                        </div>
                        <div class="bg-white rounded-xl p-5 mb-5 shadow-sm">
                            <h5 class="font-semibold text-gray-700 mb-3">📊 Course Summary</h5>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center"><p class="text-3xl font-bold text-red-600">${overallPercentage}%</p><p class="text-xs text-gray-500">Overall Score</p></div>
                                <div class="text-center"><p class="text-3xl font-bold text-red-600">${passedQuizzes}/${totalQuizzes}</p><p class="text-xs text-gray-500">Quizzes Passed</p></div>
                                <div class="text-center"><p class="text-xl font-bold text-green-600">✅ ${passedQuizzes}</p><p class="text-xs text-gray-500">Passed</p></div>
                                <div class="text-center"><p class="text-xl font-bold text-red-500">❌ ${failedQuizzes}</p><p class="text-xs text-gray-500">Failed</p></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mb-5">Passing threshold: ${quizSettings.passing_threshold}%<br>You need at least ${Math.ceil(quizSettings.passing_threshold)}% to pass.</div>
                        <button id="retakeBtn" class="px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl font-bold transition shadow-md flex items-center justify-center gap-2 mx-auto w-full sm:w-auto">
                            <span class="material-symbols-outlined text-xl">refresh</span>
                            Request Retake
                        </button>
                    </div>
                `;
                document.getElementById('retakeBtn')?.addEventListener('click', () => {
                    document.getElementById('retakeModal').classList.remove('hidden');
                });
            }
            resultCard.classList.remove('hidden');
        }
        
function downloadCertificate() {
    // Check if this is an upskilling course
    const isUpskilling = courseData.course_type === 'upskilling';
    const certFile = isUpskilling ? 'upskilling_certificate.php' : 'generate_certificate.php';
    window.open(`${certFile}?course_id=${courseId}`, '_blank');
    showToast('🎓 Opening certificate...', 'success');
}
        
        function init() {
            console.log('Initializing course player...');
            if (courseData.video && courseData.video.video_url) {
                setupVideoPlayer(courseData.video.video_url);
            } else {
                document.getElementById('noVideoMessage').classList.remove('hidden');
                document.getElementById('videoTitle').textContent = 'No Video Available';
                document.getElementById('videoDescription').textContent = 'This course does not have a video lesson.';
            }
            
            document.getElementById('markWatchedBtn')?.addEventListener('click', markVideoWatched);
            document.getElementById('startQuizBtn')?.addEventListener('click', startFirstQuiz);
            document.getElementById('requestRetakeBtn')?.addEventListener('click', () => {
                document.getElementById('retakeModal').classList.remove('hidden');
            });
            document.getElementById('cancelRetakeBtn')?.addEventListener('click', () => {
                document.getElementById('retakeModal').classList.add('hidden');
            });
            document.getElementById('submitRetakeBtn')?.addEventListener('click', requestRetake);
            
            updateProgress();
            if (courseCompleted && (courseData.completed_quizzes.length === courseData.total_quizzes)) {
                showFinalResults();
            }
        }
        
        init();
    </script>
</body>
</html>