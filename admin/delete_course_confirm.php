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
    header("Location: course_management.php");
    exit();
}

$course_id = intval($_GET['id']);

// Get course details with counts of related data
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM videos WHERE course_id = c.id) as video_count,
          (SELECT COUNT(*) FROM quizzes WHERE course_id = c.id) as quiz_count,
          (SELECT COUNT(*) FROM questions q INNER JOIN quizzes qz ON q.quiz_id = qz.id WHERE qz.course_id = c.id) as question_count,
          (SELECT COUNT(*) FROM options o 
           INNER JOIN questions q ON o.question_id = q.id 
           INNER JOIN quizzes qz ON q.quiz_id = qz.id 
           WHERE qz.course_id = c.id) as option_count
          FROM courses c WHERE c.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    header("Location: course_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Course - UpStaff Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-content {
            animation: slideIn 0.3s ease-out;
            max-width: 500px;
            width: 90%;
            margin: 20px;
        }
        
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="p-6 md:p-8">
            <!-- Normal page content would go here, but we show popup immediately -->
        </div>
    </div>

    <!-- Delete Confirmation Modal Popup -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-red-600 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                        <h1 class="text-xl font-bold text-white">Delete Course</h1>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Course Info -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            <?php if(!empty($course['thumbnail_url'])): ?>
                                <img src="<?php echo htmlspecialchars($course['thumbnail_url']); ?>" class="w-16 h-16 rounded-lg object-cover">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-book-open text-white text-2xl"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h2>
                                <p class="text-xs text-gray-500">Course ID: #<?php echo $course['id']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Warning Message -->
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                            <div>
                                <h3 class="text-sm font-semibold text-red-800">Warning: This action cannot be undone!</h3>
                                <p class="text-xs text-red-700 mt-1">
                                    Deleting this course will permanently remove all associated content.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items to be deleted -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">The following items will be deleted:</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center justify-between">
                                <span><i class="fas fa-video text-gray-500 mr-2"></i> Videos</span>
                                <span class="font-semibold text-red-600"><?php echo $course['video_count']; ?> video(s)</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span><i class="fas fa-question-circle text-gray-500 mr-2"></i> Quizzes</span>
                                <span class="font-semibold text-red-600"><?php echo $course['quiz_count']; ?> quiz(es)</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span><i class="fas fa-tasks text-gray-500 mr-2"></i> Questions</span>
                                <span class="font-semibold text-red-600"><?php echo $course['question_count']; ?> question(s)</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span><i class="fas fa-check-circle text-gray-500 mr-2"></i> Answers/Options</span>
                                <span class="font-semibold text-red-600"><?php echo $course['option_count']; ?> option(s)</span>
                            </li>
                            <li class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span><i class="fas fa-cog text-gray-500 mr-2"></i> Quiz Settings</span>
                                <span class="font-semibold text-red-600">1 setting(s)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button onclick="closeModalAndRedirect()" 
                                class="flex-1 px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition cursor-pointer">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <a href="delete_course.php?id=<?php echo $course['id']; ?>" 
                           class="flex-1 px-4 py-2 text-center text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-trash-alt mr-2"></i> Yes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const mainContent = document.getElementById("mainContent");
        
        function updateMainContentMargin() {
            const sidebar = document.getElementById("sidebar");
            if (sidebar) {
                if (sidebar.classList.contains("collapsed")) {
                    mainContent.classList.add("sidebar-collapsed");
                } else {
                    mainContent.classList.remove("sidebar-collapsed");
                }
            }
        }
        
        function closeModalAndRedirect() {
            window.location.href = 'course_management.php';
        }
        
        window.addEventListener('sidebarToggle', function() {
            setTimeout(updateMainContentMargin, 50);
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            updateMainContentMargin();
        });
        
        // Close modal when clicking outside (optional)
        document.querySelector('.modal-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModalAndRedirect();
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModalAndRedirect();
            }
        });
    </script>
</body>
</html>