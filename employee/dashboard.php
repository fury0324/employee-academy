<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/db.php';

// Helper function to strip HTML tags and limit text
function cleanText($text, $limit = 100) {
    $clean = strip_tags($text);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);
    if (empty($clean)) {
        return 'No description available.';
    }
    if (strlen($clean) > $limit) {
        $clean = substr($clean, 0, $limit) . '...';
    }
    return $clean;
}

// Authentication and role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Fetch current user data for profile - using session for basic info
$user_data = [
    'firstname' => $_SESSION['firstname'] ?? '',
    'lastname' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];
$profile_picture_path = null;

// Try to get additional user data safely
try {
    $stmt = $conn->prepare("SELECT lastname, email, phone, address FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data['lastname'] = $row['lastname'] ?? '';
            $user_data['email'] = $row['email'] ?? '';
            $user_data['phone'] = $row['phone'] ?? '';
            $user_data['address'] = $row['address'] ?? '';
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silently fail - use empty values
}

// Helper: check if table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

// Initialize stats
$stats = ['completed_courses' => 0, 'learning_hours' => 0, 'streak' => 0];
$courses = [];
$news = [];
$certifications = [];
$pending_quizzes = 0;

$hasUserCourses = tableExists($conn, 'user_courses');
$hasCourses = tableExists($conn, 'courses');
$hasAcademyNews = tableExists($conn, 'academy_news');
$hasUserCertifications = tableExists($conn, 'user_certifications');
$hasCertifications = tableExists($conn, 'certifications');
$hasUserQuizAttempts = tableExists($conn, 'user_quiz_attempts');
$hasUserLogs = tableExists($conn, 'user_logs');

// 1. Completed courses count
if ($hasUserCourses) {
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM user_courses WHERE user_id = ? AND status = 'completed'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['completed_courses'] = $result->fetch_assoc()['completed'] ?? 0;
        $stmt->close();
    }
}

// 2. Learning hours - check if column exists first
$hasDurationHours = columnExists($conn, 'courses', 'duration_hours');
if ($hasUserCourses && $hasCourses && $hasDurationHours) {
    $stmt = $conn->prepare("SELECT SUM(c.duration_hours) as total_hours FROM user_courses uc JOIN courses c ON uc.course_id = c.id WHERE uc.user_id = ? AND uc.status = 'completed'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['learning_hours'] = round($result->fetch_assoc()['total_hours'] ?? 0, 1);
        $stmt->close();
    }
}

// 3. Streak
if ($hasUserLogs) {
    $stmt = $conn->prepare("SELECT DATEDIFF(CURDATE(), MAX(login_date)) as streak FROM user_logs WHERE user_id = ? AND action = 'login'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['streak'] = $result->fetch_assoc()['streak'] ?? 0;
        $stmt->close();
    }
}

// 4. Pending quizzes count
if ($hasUserQuizAttempts) {
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM user_quiz_attempts WHERE user_id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pending_quizzes = $result->fetch_assoc()['pending'] ?? 0;
        $stmt->close();
    }
}

// 5. Get general courses
$all_general_courses = [];
$total_general_courses = 0;
$passed_general_courses = 0;
$course_retake_status = [];

if ($hasCourses) {
    $hasType = columnExists($conn, 'courses', 'type');
    $hasStatus = columnExists($conn, 'courses', 'status');
    $hasThumbnail = columnExists($conn, 'courses', 'thumbnail_url');
    
    // First, get all courses and their retake status
    $sql_all = "SELECT c.id, c.title, c.description, c.category, c.difficulty" .
               ($hasThumbnail ? ", c.thumbnail_url as thumbnail" : ", '' as thumbnail") .
               ", COALESCE(uc.status, 'not_started') as user_course_status
                FROM courses c
                LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?";
    
    $conditions = [];
    if ($hasType) $conditions[] = "c.type = 'general'";
    if ($hasStatus) $conditions[] = "c.status = 'published'";
    if (!empty($conditions)) $sql_all .= " WHERE " . implode(" AND ", $conditions);
    
    $stmt = $conn->prepare($sql_all);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check for approved retake
            $retake_check = $conn->prepare("SELECT status FROM retake_requests WHERE user_id = ? AND course_id = ? AND status = 'approved'");
            $retake_check->bind_param("ii", $user_id, $row['id']);
            $retake_check->execute();
            $has_approved_retake = $retake_check->get_result()->fetch_assoc();
            $retake_check->close();
            
            $course_retake_status[$row['id']] = !empty($has_approved_retake);
            
            // FIXED: Check if course is passed based on actual quiz performance
            $is_passed = false;
            
            // Get quiz stats for general course
            $quiz_stmt = $conn->prepare("
                SELECT 
                    AVG(uqa.score) as avg_score,
                    COUNT(DISTINCT q.id) as total_quizzes,
                    COUNT(CASE WHEN uqa.status = 'passed' THEN 1 END) as passed_quizzes
                FROM quizzes q
                LEFT JOIN user_quiz_attempts uqa ON q.id = uqa.quiz_id AND uqa.user_id = ?
                WHERE q.course_id = ?
            ");
            $quiz_stmt->bind_param("ii", $user_id, $row['id']);
            $quiz_stmt->execute();
            $quiz_result = $quiz_stmt->get_result()->fetch_assoc();
            $avg_score = round($quiz_result['avg_score'] ?? 0);
            $total_quizzes = $quiz_result['total_quizzes'] ?? 0;
            $passed_quizzes = $quiz_result['passed_quizzes'] ?? 0;
            $quiz_stmt->close();

            // Get passing threshold
            $settings_stmt = $conn->prepare("SELECT passing_threshold FROM quiz_settings WHERE course_id = ?");
            $settings_stmt->bind_param("i", $row['id']);
            $settings_stmt->execute();
            $settings = $settings_stmt->get_result()->fetch_assoc();
            $passing_threshold = $settings['passing_threshold'] ?? 70;
            $settings_stmt->close();

            // Check if passed based on actual quiz performance
            $has_quizzes = ($total_quizzes > 0);
            $score_meets_threshold = ($avg_score >= $passing_threshold);
            $all_quizzes_passed = ($total_quizzes > 0 && $passed_quizzes == $total_quizzes);
            
            $is_passed = ($has_quizzes && $score_meets_threshold && $all_quizzes_passed);
            
            // Also check if certificate exists (backup validation)
            $cert_check_stmt = $conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
            $cert_check_stmt->bind_param("ii", $user_id, $row['id']);
            $cert_check_stmt->execute();
            if ($cert_check_stmt->get_result()->num_rows > 0) {
                $is_passed = true;
            }
            $cert_check_stmt->close();
            
            $total_general_courses++;
            if ($is_passed) $passed_general_courses++;
        }
        $stmt->close();
    }
    
    $upskilling_unlocked = ($total_general_courses > 0 && $passed_general_courses == $total_general_courses);
    $upskilling_unlocked_message = $upskilling_unlocked ? "" : "Complete and PASS all " . ($total_general_courses - $passed_general_courses) . " more general course(s) with a passing score to unlock Upskilling Tracks";
    
    $sql = "SELECT c.id, c.title, c.description, c.category, c.difficulty" .
           ($hasThumbnail ? ", c.thumbnail_url as thumbnail" : ", '' as thumbnail") .
           ", COALESCE(uc.progress, 0) as progress, COALESCE(uc.status, 'not_started') as status, uc.completed_at
            FROM courses c
            LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?";
    
    $conditions = [];
    if ($hasType) $conditions[] = "c.type = 'general'";
    if ($hasStatus) $conditions[] = "c.status = 'published'";
    if (!empty($conditions)) $sql .= " WHERE " . implode(" AND ", $conditions);
    $sql .= " ORDER BY FIELD(COALESCE(uc.status, 'not_started'), 'in_progress', 'not_started', 'completed'), c.id DESC LIMIT 2";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['thumbnail']) && !str_starts_with($row['thumbnail'], 'http') && !str_starts_with($row['thumbnail'], '/')) {
                $row['thumbnail'] = '/upstaff/' . ltrim($row['thumbnail'], '/');
            }
            $courses[] = $row;
        }
        $stmt->close();
    }
}

// 6. Academy News
if ($hasAcademyNews) {
    $news_result = $conn->query("SELECT title, summary, created_at, category, image FROM academy_news ORDER BY created_at DESC LIMIT 2");
    if ($news_result) {
        while ($row = $news_result->fetch_assoc()) $news[] = $row;
        $news_result->free();
    }
}

// ==========================================
// FIXED: Fetch course results with proper pass/fail logic (SAME AS GENERAL COURSES)
// ==========================================
$course_results = [];
foreach ($courses as $course) {
    $has_approved_retake = $course_retake_status[$course['id']] ?? false;
    
    // Get average score (SAME AS GENERAL COURSES)
    $quiz_stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM user_quiz_attempts uqa JOIN quizzes q ON uqa.quiz_id = q.id WHERE uqa.user_id = ? AND q.course_id = ?");
    $quiz_stmt->bind_param("ii", $user_id, $course['id']);
    $quiz_stmt->execute();
    $quiz_result = $quiz_stmt->get_result()->fetch_assoc();
    $average_score = round($quiz_result['avg_score'] ?? 0);
    $quiz_stmt->close();
    
    // Get passing threshold
    $settings_stmt = $conn->prepare("SELECT passing_threshold FROM quiz_settings WHERE course_id = ?");
    $settings_stmt->bind_param("i", $course['id']);
    $settings_stmt->execute();
    $settings = $settings_stmt->get_result()->fetch_assoc();
    $passing_threshold = $settings['passing_threshold'] ?? 70;
    $settings_stmt->close();
    
    // Check if certificate exists (SAME AS GENERAL COURSES)
    $cert_check = $conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
    $cert_check->bind_param("ii", $user_id, $course['id']);
    $cert_check->execute();
    $has_certificate = $cert_check->get_result()->num_rows > 0;
    $cert_check->close();
    
    // SAME LOGIC AS GENERAL COURSES (WORKING!)
    if ($has_approved_retake) {
        $status = 'not_started';
    } elseif ($has_certificate) {
        $status = 'passed';
    } else {
        // Check if actually passed based on course completion and score
        $is_actually_passed = ($course['status'] === 'completed' && $average_score >= $passing_threshold);
        $is_failed = ($course['status'] === 'completed' && $average_score < $passing_threshold);
        $status = $is_actually_passed ? 'passed' : ($is_failed ? 'failed' : $course['status']);
    }
    
    $course_results[$course['id']] = [
        'average_score' => $average_score,
        'passing_threshold' => $passing_threshold,
        'status' => $status,
        'has_approved_retake' => $has_approved_retake
    ];
}

$total_courses_display = count($courses);
$completed_courses = $stats['completed_courses'];
$overall_progress = $total_courses_display > 0 ? round(($completed_courses / $total_courses_display) * 100) : 0;

// Fetch upskilling courses for dashboard (limit to 4)
$upskilling_courses = [];
if ($hasCourses) {
    $hasType = columnExists($conn, 'courses', 'type');
    $hasStatus = columnExists($conn, 'courses', 'status');
    $hasThumbnail = columnExists($conn, 'courses', 'thumbnail_url');
    
    $upskilling_sql = "SELECT c.id, c.title, c.description, c.category, c.difficulty" .
                      ($hasThumbnail ? ", c.thumbnail_url as thumbnail" : ", '' as thumbnail") .
                      ", COALESCE(uc.status, 'not_started') as user_status, COALESCE(uc.progress, 0) as progress
                       FROM courses c
                       LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?";
    
    $upskilling_conditions = [];
    if ($hasType) $upskilling_conditions[] = "c.type = 'upskilling'";
    if ($hasStatus) $upskilling_conditions[] = "c.status = 'published'";
    if (!empty($upskilling_conditions)) $upskilling_sql .= " WHERE " . implode(" AND ", $upskilling_conditions);
    $upskilling_sql .= " ORDER BY c.id ASC LIMIT 4";
    
    $upskilling_stmt = $conn->prepare($upskilling_sql);
    if ($upskilling_stmt) {
        $upskilling_stmt->bind_param("i", $user_id);
        $upskilling_stmt->execute();
        $upskilling_result = $upskilling_stmt->get_result();
        while ($row = $upskilling_result->fetch_assoc()) {
            if (!empty($row['thumbnail']) && !str_starts_with($row['thumbnail'], 'http') && !str_starts_with($row['thumbnail'], '/')) {
                $row['thumbnail'] = '/upstaff/' . ltrim($row['thumbnail'], '/');
            }
            $upskilling_courses[] = $row;
        }
        $upskilling_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .form-input:focus { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        #profileUploadInput { display: none; }
        
        /* Fixed Header Styles */
        .fixed-header {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 40;
            background: white;
            transition: all 0.3s ease;
            margin-left: 15rem;
            width: calc(100% - 15rem);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .fixed-header.sidebar-collapsed {
            margin-left: 5rem;
            width: calc(100% - 5rem);
        }
        .main-content {
            margin-left: 15rem;
            width: calc(100% - 15rem);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
            margin-top: 70px;
        }
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
            width: calc(100% - 5rem);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
        }
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.15);
            border-color: #d1d5db;
        }
        
        .course-card { transition: all 0.3s ease; }
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }
        
        .upskill-card {
            transition: all 0.3s ease;
            position: relative;
        }
        .upskill-card.locked {
            opacity: 0.7;
            filter: grayscale(0.3);
        }
        .upskill-card.locked:hover {
            transform: none;
            box-shadow: none;
        }
        .upskill-card:not(.locked):hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }
        
        .lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            z-index: 10;
        }
        
        .progress-bar { transition: width 0.6s ease-out; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .pulse { animation: pulse 2s infinite; }
        
        @keyframes unlockGlow {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .unlock-glow { animation: unlockGlow 1.5s ease-out; }
        
        @media (max-width: 768px) {
            .fixed-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 60px !important;
            }
        }
        
        /* Consistent Button Styles */
        .btn-start-course { background: linear-gradient(135deg, #3b82f6, #2563eb); transition: all 0.3s ease; }
        .btn-start-course:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); transform: translateY(-2px); }
        
        .btn-continue-course { background: linear-gradient(135deg, #8b5cf6, #7c3aed); transition: all 0.3s ease; }
        .btn-continue-course:hover { background: linear-gradient(135deg, #7c3aed, #6d28d9); transform: translateY(-2px); }
        
        .btn-download-cert { background: linear-gradient(135deg, #10b981, #059669); transition: all 0.3s ease; }
        .btn-download-cert:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-2px); }
        
        .btn-view-cert { background: linear-gradient(135deg, #10b981, #059669); transition: all 0.3s ease; }
        .btn-view-cert:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-2px); }
        
        .btn-request-retake { background: #dc2626; transition: all 0.3s ease; }
        .btn-request-retake:hover { background: #b91c1c; transform: translateY(-2px); }
        
        .btn-retake-approved { background: linear-gradient(135deg, #10b981, #059669); transition: all 0.3s ease; }
        .btn-retake-approved:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-2px); }
        
        .btn-view-all { background: #6b7280; transition: all 0.3s ease; }
        .btn-view-all:hover { background: #4b5563; transform: translateY(-2px); }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .avatar-container { transition: all 0.2s ease; }
        .avatar-container:hover { transform: scale(1.02); }
        
        /* Icon circle backgrounds */
        .icon-circle-blue { background: #eff6ff; }
        .icon-circle-green { background: #ecfdf5; }
        .icon-circle-purple { background: #f5f3ff; }
        .icon-circle-orange { background: #fff7ed; }
        
        /* Modal Animation */
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-animate-in { animation: modalFadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="px-8 md:px-9 py-8">
            <div class="max-w-8xl mx-auto">
                <!-- Welcome Section -->
                <div class="mb-12 fade-in">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight">
                                Welcome back, <span class="text-blue-500"><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
                            </h1>
                            <p class="mt-3 text-gray-600 text-lg max-w-2xl">Continue your learning journey. You're making great progress!</p>
                        </div>
                        <?php if ($pending_quizzes > 0): ?>
                        <div class="bg-orange-100 border border-orange-300 rounded-xl px-5 py-3 flex items-center gap-3 pulse">
                            <i class="fas fa-clock text-orange-500 text-xl"></i>
                            <div>
                                <p class="text-sm font-semibold text-orange-700"><?php echo $pending_quizzes; ?> Pending Quiz(zes)</p>
                                <p class="text-xs text-orange-600">Complete your quizzes to continue</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-12 fade-in">
                    <div class="stat-card p-5 md:p-6 rounded-2xl shadow-md w-full overflow-hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-circle-blue p-2 md:p-3 rounded-xl"><i class="fas fa-book-open text-blue-600 text-xl md:text-2xl"></i></div>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 md:px-3 py-1 rounded-full whitespace-nowrap">courses</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Completed Courses</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-800"><?php echo $stats['completed_courses']; ?></h2>
                            <span class="text-sm text-gray-400">completed</span>
                        </div>
                    </div>
                    <div class="stat-card p-5 md:p-6 rounded-2xl shadow-md w-full overflow-hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-circle-green p-2 md:p-3 rounded-xl"><i class="fas fa-clock text-green-600 text-xl md:text-2xl"></i></div>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 md:px-3 py-1 rounded-full whitespace-nowrap">total</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Learning Hours</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-800"><?php echo $stats['learning_hours']; ?></h2>
                            <span class="text-sm text-gray-400">hours</span>
                        </div>
                    </div>
                    <div class="stat-card p-5 md:p-6 rounded-2xl shadow-md w-full overflow-hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-circle-purple p-2 md:p-3 rounded-xl"><i class="fas fa-fire text-purple-600 text-xl md:text-2xl"></i></div>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 md:px-3 py-1 rounded-full whitespace-nowrap">streak</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Active Streak</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-800"><?php echo $stats['streak']; ?></h2>
                            <span class="text-sm text-gray-400">days</span>
                        </div>
                    </div>
                    <div class="stat-card p-5 md:p-6 rounded-2xl shadow-md w-full overflow-hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-circle-orange p-2 md:p-3 rounded-xl"><i class="fas fa-chart-line text-orange-600 text-xl md:text-2xl"></i></div>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 md:px-3 py-1 rounded-full whitespace-nowrap">progress</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Overall Progress</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-800"><?php echo $overall_progress; ?>%</h2>
                            <span class="text-sm text-gray-400">complete</span>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-12 gap-8">
                    <!-- Left Column: Courses -->
                    <div class="col-span-12 lg:col-span-8 space-y-10">
                        <!-- General Courses Section -->
                        <div class="fade-in">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center"><i class="fas fa-graduation-cap text-blue-600 text-sm"></i></div>
                                    <h2 class="text-2xl font-bold text-gray-800">General Courses</h2>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-check-circle text-green-500 mr-1"></i><?php echo $passed_general_courses; ?>/<?php echo $total_general_courses; ?> Passed</span>
                                    <a href="general_courses.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-1">View all <i class="fas fa-arrow-right text-xs"></i></a>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php if (empty($courses)): ?>
                                    <div class="col-span-2 text-center py-12 text-gray-500 bg-white/50 rounded-2xl"><i class="fas fa-book-open text-4xl mb-3 opacity-50"></i><p>No courses available yet.</p></div>
                                <?php else: ?>
                                    <?php foreach ($courses as $course): ?>
                                    <?php 
                                        $courseResult = $course_results[$course['id']] ?? ['status' => $course['status'], 'average_score' => 0, 'passing_threshold' => 70, 'has_approved_retake' => false];
                                        $isPassed = ($courseResult['status'] === 'passed');
                                        $isFailed = ($courseResult['status'] === 'failed');
                                        $hasApprovedRetake = $courseResult['has_approved_retake'];
                                        $isInProgress = ($course['status'] === 'in_progress' && !$hasApprovedRetake);
                                    ?>
                                    <div class="course-card bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all">
                                        <div class="relative h-40 overflow-hidden">
                                            <img class="w-full h-full object-cover transition-transform duration-500" src="<?php 
                                                $thumb = $course['thumbnail'] ?? '';
                                                if (!empty($thumb)) {
                                                    if (str_starts_with($thumb, 'http')) echo htmlspecialchars($thumb);
                                                    else echo '/upstaff/' . ltrim($thumb, '/');
                                                } else echo 'https://placehold.co/400x200/2563eb/white?text=' . urlencode($course['title']);
                                            ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                            <?php if ($hasApprovedRetake): ?>
                                                <div class="absolute top-3 left-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-check-circle text-xs"></i> RETAKE APPROVED</div>
                                            <?php elseif ($isPassed): ?>
                                                <div class="absolute top-3 left-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-check-circle text-xs"></i> PASSED</div>
                                            <?php elseif ($isFailed): ?>
                                                <div class="absolute top-3 left-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-times-circle text-xs"></i> FAILED</div>
                                            <?php elseif ($isInProgress): ?>
                                                <div class="absolute top-3 left-3 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-play text-xs"></i> In Progress</div>
                                            <?php else: ?>
                                                <div class="absolute top-3 left-3 bg-gray-600 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-hourglass-half text-xs"></i> Not Started</div>
                                            <?php endif; ?>
                                            <?php if ((($course['status'] === 'completed' || $isFailed) && $courseResult['average_score'] > 0) && !$hasApprovedRetake): ?>
                                                <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-gray-800 px-3 py-1 rounded-full text-xs font-semibold shadow-md"><i class="fas fa-star text-yellow-500 mr-1"></i> <?php echo $courseResult['average_score']; ?>%</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-5">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-600 rounded-full"><?php echo htmlspecialchars($course['category'] ?? 'General'); ?></span>
                                                <span class="text-xs px-2 py-1 bg-purple-100 text-purple-600 rounded-full"><?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?></span>
                                            </div>
                                            <h3 class="font-bold text-lg text-gray-800 mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                                            <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo cleanText($course['description'] ?? '', 100); ?></p>
                                            <?php if ($hasApprovedRetake): ?>
                                                <div class="flex items-center gap-2 text-green-700 mb-3 bg-green-50 rounded-lg px-3 py-2 text-xs">
                                                    <i class="fas fa-check-circle"></i> Your retake request has been approved! You can retake this course.
                                                </div>
                                                <a href="course_rules.php?id=<?php echo $course['id']; ?>" class="btn-retake-approved w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-play"></i> Start Retake Course
                                                </a>
                                            <?php elseif ($isPassed): ?>
                                                <div class="flex items-center gap-2 text-green-600 mb-3"><i class="fas fa-check-circle"></i><span class="text-sm font-medium">Passed - <?php echo $courseResult['average_score']; ?>%</span></div>
                                                <button onclick="viewCertificate(<?php echo $course['id']; ?>)" class="btn-view-cert w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-eye"></i> View Certificate
                                                </button>
                                            <?php elseif ($isFailed): ?>
                                                <div class="flex items-center gap-2 text-red-600 mb-3"><i class="fas fa-times-circle"></i><span class="text-sm font-medium">Failed - <?php echo $courseResult['average_score']; ?>% (Need <?php echo $courseResult['passing_threshold']; ?>% to pass all quizzes)</span></div>
                                                <button onclick="showRetakeModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')" class="btn-request-retake w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-redo-alt"></i> Request Retake
                                                </button>
                                            <?php elseif ($isInProgress): ?>
                                                <div class="space-y-2">
                                                    <div class="flex justify-between text-xs text-gray-600"><span>Progress</span><span><?php echo $course['progress']; ?>%</span></div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2"><div class="progress-bar bg-blue-600 h-2 rounded-full" style="width: <?php echo $course['progress']; ?>%"></div></div>
                                                    <a href="course_rules.php?id=<?php echo $course['id']; ?>" class="btn-continue-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">Continue Course <i class="fas fa-arrow-right text-xs"></i></a>
                                                </div>
                                            <?php else: ?>
                                                <a href="course_rules.php?id=<?php echo $course['id']; ?>" class="btn-start-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-play"></i> Start Course
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Upskilling Tracks Section -->
                        <div class="fade-in">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-chart-line text-white text-sm"></i>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-800">Upskilling Tracks</h2>
                                </div>
                                <?php if (!$upskilling_unlocked): ?>
                                    <span class="text-xs bg-amber-100 text-amber-700 px-3 py-1 rounded-full flex items-center gap-1"><i class="fas fa-lock text-xs"></i> Locked</span>
                                <?php else: ?>
                                    <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full flex items-center gap-1 unlock-glow"><i class="fas fa-unlock-alt text-xs"></i> Unlocked</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$upskilling_unlocked): ?>
                                <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                                    <i class="fas fa-lock text-amber-500 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800">Upskilling Tracks Locked</p>
                                        <p class="text-sm text-amber-700"><?php echo $upskilling_unlocked_message; ?></p>
                                        <div class="mt-3">
                                            <div class="flex justify-between text-xs text-amber-700 mb-1">
                                                <span>Progress to Unlock</span>
                                                <span><?php echo $passed_general_courses; ?>/<?php echo $total_general_courses; ?> Courses Passed</span>
                                            </div>
                                            <div class="w-full bg-amber-200 rounded-full h-2.5">
                                                <div class="bg-amber-500 h-2.5 rounded-full" style="width: <?php echo ($total_general_courses > 0) ? ($passed_general_courses / $total_general_courses) * 100 : 0; ?>%"></div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-amber-600 mt-2"><i class="fas fa-info-circle mr-1"></i> You need to achieve a <strong>PASSING SCORE</strong> on all <?php echo $total_general_courses; ?> general course(s) to unlock upskilling tracks.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
                                    <i class="fas fa-trophy text-green-500 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-green-800">🎉 Congratulations! Upskilling Tracks Unlocked! 🎉</p>
                                        <p class="text-sm text-green-700">You have successfully passed all general courses. You can now access specialized upskilling tracks to advance your career!</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- DYNAMIC UPSKILLING COURSES GRID -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php
                                if (!empty($upskilling_courses)):
                                    foreach ($upskilling_courses as $upskill_course):
                                        $isUpskillLocked = !$upskilling_unlocked;
                                        $upskill_thumb = $upskill_course['thumbnail'] ?? '';
                                        if (!empty($upskill_thumb)) {
                                            if (str_starts_with($upskill_thumb, 'http')) $upskillThumbUrl = $upskill_thumb;
                                            else $upskillThumbUrl = '/upstaff/' . ltrim($upskill_thumb, '/');
                                        } else {
                                            $upskillThumbUrl = 'https://placehold.co/600x300/7c3aed/white?text=' . urlencode($upskill_course['title']);
                                        }
                                        
                                        // Get upskilling course quiz stats for certificate check
                                        $upskill_quiz_stmt = $conn->prepare("
                                            SELECT AVG(uqa.score) as avg_score, 
                                                   COUNT(DISTINCT q.id) as total_quizzes, 
                                                   COUNT(CASE WHEN uqa.status = 'passed' THEN 1 END) as passed_quizzes
                                            FROM quizzes q
                                            LEFT JOIN user_quiz_attempts uqa ON q.id = uqa.quiz_id AND uqa.user_id = ?
                                            WHERE q.course_id = ?
                                        ");
                                        $upskill_quiz_stmt->bind_param("ii", $user_id, $upskill_course['id']);
                                        $upskill_quiz_stmt->execute();
                                        $upskill_quiz_stats = $upskill_quiz_stmt->get_result()->fetch_assoc();
                                        $upskill_quiz_stmt->close();
                                        
                                        $upskill_avg_score = round($upskill_quiz_stats['avg_score'] ?? 0);
                                        $upskill_total_quizzes = $upskill_quiz_stats['total_quizzes'] ?? 0;
                                        $upskill_passed_quizzes = $upskill_quiz_stats['passed_quizzes'] ?? 0;
                                        
                                        $upskill_settings_stmt = $conn->prepare("SELECT passing_threshold FROM quiz_settings WHERE course_id = ?");
                                        $upskill_settings_stmt->bind_param("i", $upskill_course['id']);
                                        $upskill_settings_stmt->execute();
                                        $upskill_settings = $upskill_settings_stmt->get_result()->fetch_assoc();
                                        $upskill_passing_threshold = $upskill_settings['passing_threshold'] ?? 70;
                                        $upskill_settings_stmt->close();
                                        
                                        $upskill_has_passed = ($upskill_course['user_status'] === 'completed' && $upskill_avg_score >= $upskill_passing_threshold && $upskill_passed_quizzes == $upskill_total_quizzes && $upskill_total_quizzes > 0);
                                ?>
                                <div class="upskill-card <?php echo $isUpskillLocked ? 'locked' : ''; ?> bg-white rounded-2xl overflow-hidden shadow-md border border-gray-200 relative">
                                    <?php if ($isUpskillLocked): ?>
                                        <div class="lock-overlay">
                                            <div class="text-center text-white">
                                                <i class="fas fa-lock text-4xl mb-2"></i>
                                                <p class="text-sm font-semibold">Pass all general courses first</p>
                                                <p class="text-xs opacity-75">with a passing score to unlock</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="relative h-40 overflow-hidden">
                                        <img src="<?php echo $upskillThumbUrl; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($upskill_course['title']); ?>">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                        <?php if ($upskilling_unlocked && $upskill_has_passed): ?>
                                            <div class="absolute top-3 left-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-check-circle text-xs"></i> PASSED</div>
                                            <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-lg px-2 py-1 text-xs font-bold text-green-600 shadow-md">
                                                <i class="fas fa-star mr-1"></i> <?php echo $upskill_avg_score; ?>%
                                            </div>
                                        <?php elseif ($upskilling_unlocked && $upskill_course['user_status'] === 'in_progress'): ?>
                                            <div class="absolute top-3 left-3 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-play text-xs"></i> IN PROGRESS</div>
                                            <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm rounded-lg px-2 py-1 text-xs font-bold text-blue-600 shadow-md">
                                                <i class="fas fa-chart-line mr-1"></i> <?php echo $upskill_course['progress']; ?>% Complete
                                            </div>
                                        <?php elseif ($upskilling_unlocked): ?>
                                            <div class="absolute top-3 left-3 bg-gray-700 text-white px-3 py-1 rounded-full text-xs font-semibold"><i class="fas fa-hourglass-half text-xs"></i> NOT STARTED</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-5">
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full"><?php echo htmlspecialchars($upskill_course['category'] ?? 'Upskilling'); ?></span>
                                            <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full"><?php echo htmlspecialchars($upskill_course['difficulty'] ?? 'Advanced'); ?></span>
                                        </div>
                                        <h3 class="font-bold text-lg text-gray-800 mb-2"><?php echo htmlspecialchars($upskill_course['title']); ?></h3>
                                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo cleanText($upskill_course['description'] ?? '', 80); ?></p>
                                        
                                        <?php if ($upskilling_unlocked): ?>
                                            <?php if ($upskill_has_passed): ?>
                                                <div class="flex items-center gap-2 text-green-700 mb-3 bg-green-50 rounded-lg px-3 py-2 text-xs">
                                                    <i class="fas fa-award text-sm"></i>
                                                    <span>Certificate earned — <?php echo $upskill_avg_score; ?>% score</span>
                                                </div>
                                                <button onclick="viewUpskillCertificate(<?php echo $upskill_course['id']; ?>)" class="btn-view-cert w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-eye"></i> View Certificate
                                                </button>
                                            <?php elseif ($upskill_course['user_status'] === 'in_progress'): ?>
                                                <div class="mb-4">
                                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                        <span>Quiz Progress</span>
                                                        <span><?php echo $upskill_passed_quizzes; ?>/<?php echo $upskill_total_quizzes; ?> Completed</span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?php echo $upskill_total_quizzes > 0 ? ($upskill_passed_quizzes / $upskill_total_quizzes) * 100 : 0; ?>%"></div>
                                                    </div>
                                                </div>
                                                <button onclick="continueUpskillCourse(<?php echo $upskill_course['id']; ?>)" class="btn-continue-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-play"></i> Continue Course
                                                </button>
                                            <?php else: ?>
                                                <button onclick="startUpskillCourse(<?php echo $upskill_course['id']; ?>)" class="btn-start-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-play"></i> Start Course
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="mt-auto">
                                                <div class="w-full py-2.5 rounded-xl bg-gray-200 text-gray-500 text-sm font-semibold flex items-center justify-center gap-2 cursor-not-allowed">
                                                    <i class="fas fa-lock"></i> Locked
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php 
                                    endforeach;
                                    // If less than 4 courses, show a "View All" card
                                    if (count($upskilling_courses) < 4 && $upskilling_unlocked):
                                ?>
                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden shadow-md border border-gray-200 flex items-center justify-center min-h-[280px]">
                                    <div class="text-center p-6">
                                        <i class="fas fa-arrow-right text-gray-400 text-4xl mb-3"></i>
                                        <h3 class="font-semibold text-gray-700 mb-2">More Courses Coming</h3>
                                        <p class="text-sm text-gray-500 mb-4">New upskilling courses added regularly</p>
                                        <a href="upskilling_courses.php" class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-700 font-medium text-sm">
                                            View All Courses <i class="fas fa-external-link-alt text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                else:
                                ?>
                                <div class="col-span-2 text-center py-8 bg-gray-50 rounded-2xl">
                                    <i class="fas fa-book-open text-gray-400 text-4xl mb-2"></i>
                                    <p class="text-gray-500">No upskilling courses available yet.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($upskilling_unlocked && count($upskilling_courses) > 0): ?>
                            <div class="mt-4 text-right">
                                <a href="upskilling_courses.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm inline-flex items-center gap-1">
                                    View all upskilling courses <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Widgets -->
                    <aside class="col-span-12 lg:col-span-4 space-y-8 fade-in">
                        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                            <div class="flex items-center justify-between mb-5"><h3 class="font-bold text-lg text-gray-800 flex items-center gap-2"><i class="fas fa-newspaper text-blue-500"></i> Academy News</h3></div>
                            <div class="space-y-5">
                                <?php if (empty($news)): ?><p class="text-gray-500 text-center py-8">No recent news.</p>
                                <?php else: foreach ($news as $item): ?>
                                    <div class="flex gap-4 group cursor-pointer"><div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100"><img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/80x80/2563eb/white?text=News'); ?>" alt="News"></div><div class="flex-1"><p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1"><?php echo htmlspecialchars($item['category'] ?? 'Update'); ?></p><h4 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($item['title']); ?></h4><p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($item['summary'] ?? '', 0, 80)); ?>...</p><p class="text-xs text-gray-400 mt-1"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></p></div></div>
                                <?php endforeach; endif; ?>
                            </div>
                            <button class="w-full mt-6 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">Read All Updates</button>
                        </div>

                        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-certificate text-purple-500"></i> Latest Certificate
                                </h3>
                                <a href="certificates.php" class="btn-view-all px-3 py-1.5 rounded-lg text-white text-xs font-medium flex items-center gap-1 shadow-md">
                                    <i class="fas fa-eye text-xs"></i> View All
                                </a>
                            </div>
                            <div class="space-y-4">
                                <?php 
                                // Fetch latest certificate from certificates table
                                $latest_cert_query = "
                                    SELECT c.*, cs.title as course_title, cs.type as course_type
                                    FROM certificates c
                                    LEFT JOIN courses cs ON c.course_id = cs.id
                                    WHERE c.user_id = ? 
                                    ORDER BY c.issued_at DESC 
                                    LIMIT 1
                                ";
                                $stmt = $conn->prepare($latest_cert_query);
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $latest_cert_result = $stmt->get_result();
                                $latest_cert = $latest_cert_result->fetch_assoc();
                                $stmt->close();
                                
                                // Check if certificate exists and has valid data
                                if ($latest_cert && !empty($latest_cert['course_id']) && $latest_cert['final_score'] >= 70): 
                                    // Get course title if not from join
                                    $course_title = $latest_cert['course_title'] ?? '';
                                    if (empty($course_title)) {
                                        $course_stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
                                        $course_stmt->bind_param("i", $latest_cert['course_id']);
                                        $course_stmt->execute();
                                        $course_result = $course_stmt->get_result();
                                        $course = $course_result->fetch_assoc();
                                        $course_title = $course['title'] ?? 'Unknown Course';
                                        $course_stmt->close();
                                    }
                                ?>
                                    <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-md">
                                                <i class="fas fa-certificate text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($course_title ?? 'Certificate'); ?></p>
                                                <p class="text-xs text-gray-500">Earned on <?php echo date('M d, Y', strtotime($latest_cert['issued_at'] ?? 'now')); ?></p>
                                                <p class="text-xs text-gray-400">Score: <?php echo number_format($latest_cert['final_score'] ?? 0, 2); ?>%</p>
                                            </div>
                                        </div>
                                        <a href="view_certificate.php?course_id=<?php echo $latest_cert['course_id']; ?>" target="_blank" class="text-green-600 hover:text-green-700 transition bg-white px-3 py-2 rounded-lg shadow-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-certificate text-5xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">No certificates earned yet.</p>
                                        <p class="text-xs text-gray-400 mt-1">Complete and pass courses to earn certificates.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg"><div class="flex items-center gap-3 mb-3"><i class="fas fa-lightbulb text-2xl"></i><h3 class="font-bold text-lg">Pro Tip</h3></div><p class="text-sm opacity-90 mb-4">Complete and PASS all general courses to unlock specialized upskilling tracks and advance your career!</p><div class="flex items-center gap-2 text-xs opacity-75"><i class="fas fa-trophy"></i><span>Passing score required to unlock tracks</span></div></div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <!-- Retake Request Modal -->
    <div id="retakeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-auto modal-animate-in">
            <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-redo-alt text-indigo-500"></i> Request Retake
                </h3>
                <button id="closeRetakeModalBtn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-blue-800">Course Information</p>
                            <p class="text-gray-700 text-sm mt-1" id="modalCourseName">Loading...</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Retake Request <span class="text-red-500">*</span>
                    </label>
                    <textarea id="retakeReason" rows="4" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                        placeholder="Please explain why you need to retake this course (e.g., technical issues, personal circumstances, need to improve score, etc.)"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimum 10 characters</p>
                </div>
                
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 flex items-center gap-2">
                        <i class="fas fa-clock"></i>
                        Your request will be reviewed by an admin within 2-3 business days.
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-100 px-6 py-4 flex flex-col sm:flex-row justify-end gap-3">
                <button id="cancelRetakeBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button id="submitRetakeBtn" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Request
                </button>
            </div>
        </div>
    </div>

    <script>
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-lg transition-all duration-300 ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-orange-600'}`;
        toast.innerHTML = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // View certificate for general courses
    function viewCertificate(courseId) { 
        window.open(`generate_certificate.php?course_id=${courseId}`, '_blank'); 
        showToast('🎓 Opening certificate...', 'success'); 
    }
    
    // View certificate for upskilling courses
    function viewUpskillCertificate(courseId) { 
        window.open(`upskilling_certificate.php?course_id=${courseId}`, '_blank'); 
        showToast('🎓 Opening upskilling certificate...', 'success'); 
    }
    
    // Retake Modal Functionality
    let currentCourseId = null;
    let currentCourseTitle = null;
    
    function showRetakeModal(courseId, courseTitle) {
        currentCourseId = courseId;
        currentCourseTitle = courseTitle;
        
        document.getElementById('modalCourseName').innerHTML = `<strong>${escapeHtml(courseTitle)}</strong><br><span class="text-xs">Course ID: #${courseId}</span>`;
        document.getElementById('retakeReason').value = '';
        
        const modal = document.getElementById('retakeModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    
    function closeRetakeModal() {
        const modal = document.getElementById('retakeModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        currentCourseId = null;
        currentCourseTitle = null;
    }
    
    async function submitRetakeRequest() {
        const reason = document.getElementById('retakeReason').value.trim();
        const submitBtn = document.getElementById('submitRetakeBtn');
        
        if (reason.length === 0) {
            showToast('Please provide a reason for your request', 'error');
            return;
        }
        
        if (reason.length < 10) {
            showToast('Please provide a more detailed reason (minimum 10 characters)', 'error');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
        
        try {
            const response = await fetch('request_retake.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    course_id: currentCourseId,
                    reason: reason
                })
            });
            
            if (!response.ok) {
                const text = await response.text();
                console.error('Server response:', text);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            let data;
            try {
                const text = await response.text();
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid server response');
            }
            
            if (data.success) {
                showToast(data.message, 'success');
                closeRetakeModal();
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error details:', error);
            showToast('Failed to submit request. Please check console for details.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Submit Request';
        }
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
    }

    function initSidebarToggle() {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        const fixedHeader = document.querySelector('.fixed-header');
        if (sidebar && mainContent && fixedHeader) {
            if (!sidebar.classList.contains("w-64")) { mainContent.classList.add("sidebar-collapsed"); fixedHeader.classList.add("sidebar-collapsed"); }
            window.addEventListener('sidebarToggle', function() {
                if (sidebar.classList.contains("w-64")) { mainContent.classList.remove("sidebar-collapsed"); fixedHeader.classList.remove("sidebar-collapsed"); }
                else { mainContent.classList.add("sidebar-collapsed"); fixedHeader.classList.add("sidebar-collapsed"); }
            });
        }
    }
    
    function startUpskillCourse(courseId) {
        window.location.href = `course_rules.php?id=${courseId}`;
    }
    
    function continueUpskillCourse(courseId) {
        window.location.href = `course_rules.php?id=${courseId}`;
    }
    
    document.addEventListener('DOMContentLoaded', function() { 
        initSidebarToggle(); 
        
        const closeBtn = document.getElementById('closeRetakeModalBtn');
        const cancelBtn = document.getElementById('cancelRetakeBtn');
        const submitBtn = document.getElementById('submitRetakeBtn');
        
        if (closeBtn) closeBtn.addEventListener('click', closeRetakeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeRetakeModal);
        if (submitBtn) submitBtn.addEventListener('click', submitRetakeRequest);
        
        const modal = document.getElementById('retakeModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeRetakeModal();
                }
            });
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && modal.classList.contains('flex')) {
                closeRetakeModal();
            }
        });
    });
    </script>
</body>
</html>