<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if columns exist
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

$hasThumbnail = columnExists($conn, 'courses', 'thumbnail_url');
$hasType = columnExists($conn, 'courses', 'type');
$hasStatus = columnExists($conn, 'courses', 'status');

// Get all general courses with user progress
$sql = "SELECT c.id, c.title, c.description, c.category, c.difficulty" .
       ($hasThumbnail ? ", c.thumbnail_url as thumbnail" : ", '' as thumbnail") .
       ", COALESCE(uc.progress, 0) as progress, COALESCE(uc.status, 'not_started') as status, uc.completed_at
        FROM courses c
        LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?";

$conditions = [];
if ($hasType) $conditions[] = "c.type = 'general'";
if ($hasStatus) $conditions[] = "c.status = 'published'";
if (!empty($conditions)) $sql .= " WHERE " . implode(" AND ", $conditions);

$sql .= " ORDER BY FIELD(COALESCE(uc.status, 'not_started'), 'in_progress', 'not_started', 'completed'), c.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['thumbnail']) && !str_starts_with($row['thumbnail'], 'http') && !str_starts_with($row['thumbnail'], '/')) {
        $row['thumbnail'] = '/upstaff/' . ltrim($row['thumbnail'], '/');
    }
    $courses[] = $row;
}
$stmt->close();

// Fetch quiz results and retake status for each course
$course_results = [];
foreach ($courses as $course) {
    // Check for approved retake request
    $retake_stmt = $conn->prepare("SELECT id, status FROM retake_requests WHERE user_id = ? AND course_id = ? AND status = 'approved'");
    $retake_stmt->bind_param("ii", $user_id, $course['id']);
    $retake_stmt->execute();
    $has_approved_retake = $retake_stmt->get_result()->fetch_assoc();
    $retake_stmt->close();
    
    $quiz_stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM user_quiz_attempts uqa JOIN quizzes q ON uqa.quiz_id = q.id WHERE uqa.user_id = ? AND q.course_id = ?");
    $quiz_stmt->bind_param("ii", $user_id, $course['id']);
    $quiz_stmt->execute();
    $quiz_result = $quiz_stmt->get_result()->fetch_assoc();
    $quiz_stmt->close();
    $average_score = round($quiz_result['avg_score'] ?? 0);
    
    $settings_stmt = $conn->prepare("SELECT passing_threshold FROM quiz_settings WHERE course_id = ?");
    $settings_stmt->bind_param("i", $course['id']);
    $settings_stmt->execute();
    $settings = $settings_stmt->get_result()->fetch_assoc();
    $passing_threshold = $settings['passing_threshold'] ?? 70;
    $settings_stmt->close();
    
    // If there's an approved retake, treat as not_started
    if ($has_approved_retake) {
        $course_status = 'not_started';
        $is_passed = false;
        $is_failed = false;
    } else {
        $is_actually_passed = ($course['status'] === 'completed' && $average_score >= $passing_threshold);
        $course_status = $course['status'];
        $is_passed = $is_actually_passed;
        $is_failed = ($course['status'] === 'completed' && $average_score < $passing_threshold);
    }
    
    $course_results[$course['id']] = [
        'average_score' => $average_score,
        'passing_threshold' => $passing_threshold,
        'status' => $is_passed ? 'passed' : ($is_failed ? 'failed' : $course_status),
        'has_approved_retake' => !empty($has_approved_retake),
        'course_status' => $course_status
    ];
}

$total_courses = count($courses);
$passed_courses = 0;
foreach ($course_results as $result) {
    if ($result['status'] === 'passed') $passed_courses++;
}

function cleanText($text, $limit = 100) {
    $clean = strip_tags($text);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);
    if (empty($clean)) return 'No description available.';
    if (strlen($clean) > $limit) $clean = substr($clean, 0, $limit) . '...';
    return $clean;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Courses - Upstaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .course-card { transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column; }
        .course-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .btn-start-course { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .btn-start-course:hover, .btn-continue-course:hover, .btn-download-cert:hover, .btn-request-retake:hover { transform: translateY(-2px); }
        .btn-continue-course { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .btn-download-cert { background: linear-gradient(135deg, #10b981, #059669); }
        .btn-request-retake { background: #dc2626; }
        .btn-retake-approved { background: linear-gradient(135deg, #10b981, #059669); }
        .filter-btn.active { background-color: #3b82f6; color: white; }
        .filter-btn:not(.active) { background-color: #f3f4f6; color: #4b5563; }
        .filter-btn:not(.active):hover { background-color: #e5e7eb; }
        .line-clamp-2 { display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; }
        .main-content { margin-left: 16rem; transition: margin-left 0.3s; min-height: 100vh; }
        .main-content.sidebar-collapsed { margin-left: 5rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0 !important; } }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .modal-animate-in {
            animation: modalFadeIn 0.3s ease-out forwards;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include_once 'sidebar.php'; ?>
    <?php include_once 'header.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="p-6" style="padding-top: 110px;">
            
            <!-- Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium active" data-type="all">All Courses</button>
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium" data-type="not_started">Not Started</button>
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium" data-type="passed">Passed</button>
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium" data-type="failed">Failed</button>
                    </div>
                    <input type="text" id="searchCourses" placeholder="Search courses..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64">
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="coursesGrid">
                <?php if (empty($courses)): ?>
                    <div class="col-span-full text-center py-12 bg-white rounded-xl">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-3"></i>
                        <p>No general courses available.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): 
                        $result = $course_results[$course['id']];
                        $isPassed = ($result['status'] === 'passed');
                        $isFailed = ($result['status'] === 'failed');
                        $isInProgress = ($result['course_status'] === 'in_progress');
                        $hasApprovedRetake = $result['has_approved_retake'];
                        
                        $thumb = $course['thumbnail'] ?? '';
                        if (!empty($thumb)) {
                            if (str_starts_with($thumb, 'http')) $thumbUrl = $thumb;
                            else $thumbUrl = '/upstaff/' . ltrim($thumb, '/');
                        } else {
                            $thumbUrl = 'https://placehold.co/600x300/3b82f6/white?text=' . urlencode($course['title']);
                        }
                    ?>
                    <div class="course-card bg-white rounded-xl shadow-sm overflow-hidden border" 
                         data-status="<?php echo $result['status']; ?>"
                         data-title="<?php echo strtolower(htmlspecialchars($course['title'])); ?>"
                         data-description="<?php echo strtolower(cleanText($course['description'] ?? '', 100)); ?>">
                        
                        <div class="relative h-44">
                            <img src="<?php echo $thumbUrl; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                            
                            <div class="absolute top-3 left-3 z-10">
                                <?php if ($hasApprovedRetake): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white shadow-lg"><i class="fas fa-check-circle text-xs mr-1"></i> RETAKE APPROVED</span>
                                <?php elseif ($isPassed): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white shadow-lg"><i class="fas fa-check-circle text-xs mr-1"></i> PASSED</span>
                                <?php elseif ($isFailed): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500 text-white shadow-lg"><i class="fas fa-times-circle text-xs mr-1"></i> FAILED</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-700 text-white shadow-lg"><i class="fas fa-hourglass-half text-xs mr-1"></i> NOT STARTED</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ((($isPassed || $isFailed) && $result['average_score'] > 0) && !$hasApprovedRetake): ?>
                            <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-lg px-2 py-1 text-xs font-bold shadow-md <?php echo $isPassed ? 'text-green-600' : 'text-red-600'; ?>">
                                <i class="fas fa-star mr-1"></i> Score: <?php echo $result['average_score']; ?>%
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($isInProgress && !$hasApprovedRetake): ?>
                            <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm rounded-lg px-2 py-1 text-xs font-bold text-blue-600 shadow-md">
                                <i class="fas fa-chart-line mr-1"></i> <?php echo $course['progress']; ?>% Complete
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4 flex-1 flex flex-col">
                            <div class="flex gap-2 mb-2 flex-wrap">
                                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full"><?php echo htmlspecialchars($course['category'] ?? 'General'); ?></span>
                                <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full"><?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?></span>
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo cleanText($course['description'] ?? '', 100); ?></p>
                            
                            <?php if ($hasApprovedRetake): ?>
                                <div class="flex items-center gap-2 text-green-700 mb-3 bg-green-50 rounded-lg px-3 py-2 text-xs">
                                    <i class="fas fa-check-circle"></i> Your retake request has been approved! You can now retake this course.
                                </div>
                                <button onclick="startCourse(<?php echo $course['id']; ?>)" class="btn-retake-approved w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-play"></i> Start Retake Course
                                </button>
                            <?php elseif ($isPassed): ?>
                                <div class="flex items-center gap-2 text-green-700 mb-3 bg-green-50 rounded-lg px-3 py-2 text-xs">
                                    <i class="fas fa-award"></i> Certificate earned — <?php echo $result['average_score']; ?>% score
                                </div>
                                <button onclick="downloadCertificate(<?php echo $course['id']; ?>)" class="btn-download-cert w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-download"></i> Download Certificate
                                </button>
                            <?php elseif ($isFailed): ?>
                                <div class="flex items-center gap-2 text-red-600 mb-3 bg-red-50 rounded-lg px-3 py-2 text-xs">
                                    <i class="fas fa-exclamation-triangle"></i> Failed - <?php echo $result['average_score']; ?>% (Need <?php echo $result['passing_threshold']; ?>%)
                                </div>
                                <button onclick="showRetakeModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')" class="btn-request-retake w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-redo-alt"></i> Request Retake
                                </button>
                            <?php elseif ($isInProgress): ?>
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progress</span>
                                        <span><?php echo $course['progress']; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                </div>
                                <button onclick="continueCourse(<?php echo $course['id']; ?>)" class="btn-continue-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-play"></i> Continue Course
                                </button>
                            <?php else: ?>
                                <button onclick="startCourse(<?php echo $course['id']; ?>)" class="btn-start-course w-full py-2.5 rounded-xl text-white text-sm font-medium flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-play"></i> Start Course
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Progress Banner -->
            <div class="mt-8 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-5 border border-indigo-100">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-rocket text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">🎯 Unlock Upskilling Tracks</p>
                            <p class="text-sm text-gray-600">Pass ALL general courses with a score ≥70% to access specialized career tracks.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm">
                        <i class="fas fa-chart-line text-indigo-500"></i>
                        <span class="text-xs font-medium text-gray-700">Progress: <span class="font-bold text-indigo-600"><?php echo $passed_courses; ?>/<?php echo $total_courses; ?></span> courses passed</span>
                    </div>
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
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        function updateMargin() {
            if (sidebar?.classList.contains('w-20')) {
                mainContent?.classList.add('sidebar-collapsed');
            } else {
                mainContent?.classList.remove('sidebar-collapsed');
            }
        }
        
        window.addEventListener('sidebarToggle', updateMargin);
        document.addEventListener('DOMContentLoaded', updateMargin);
        
        let currentType = 'all';
        let searchTerm = '';
        
        function filterCourses() {
            document.querySelectorAll('.course-card').forEach(card => {
                const matchType = currentType === 'all' || card.dataset.status === currentType;
                const matchSearch = searchTerm === '' || 
                    card.dataset.title.includes(searchTerm) || 
                    card.dataset.description.includes(searchTerm);
                card.style.display = (matchType && matchSearch) ? '' : 'none';
            });
        }
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentType = this.dataset.type;
                filterCourses();
            });
        });
        
        document.getElementById('searchCourses')?.addEventListener('input', function(e) {
            searchTerm = e.target.value.toLowerCase();
            filterCourses();
        });
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-lg transition-all duration-300 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
        }
        
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
                    },
                    body: JSON.stringify({
                        course_id: currentCourseId,
                        reason: reason
                    })
                });
                
                const data = await response.json();
                
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
                console.error('Error:', error);
                showToast('Failed to submit request. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Submit Request';
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
        }
        
        function downloadCertificate(courseId) {
            window.open(`generate_certificate.php?course_id=${courseId}`, '_blank');
            showToast('Opening certificate...', 'success');
        }
        
        function startCourse(courseId) {
            window.location.href = `course_rules.php?id=${courseId}`;
        }
        
        function continueCourse(courseId) {
            window.location.href = `course_rules.php?id=${courseId}`;
        }
        
        document.getElementById('closeRetakeModalBtn').addEventListener('click', closeRetakeModal);
        document.getElementById('cancelRetakeBtn').addEventListener('click', closeRetakeModal);
        document.getElementById('submitRetakeBtn').addEventListener('click', submitRetakeRequest);
        
        document.getElementById('retakeModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('retakeModal')) {
                closeRetakeModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('retakeModal').classList.contains('flex')) {
                closeRetakeModal();
            }
        });
    </script>
</body>
</html>