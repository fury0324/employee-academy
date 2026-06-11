<?php
// generate_report.php - WITH PROPER DATE HANDLING
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// AJAX handler for quiz details (same as before)
if (isset($_GET['ajax']) && $_GET['ajax'] == 'quiz_details') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    
    $questions = array();
    
    if ($user_id && $course_id) {
        $query = "SELECT 
                    uqa.id,
                    uqa.question_id,
                    uqa.selected_option,
                    uqa.is_correct,
                    q.question_text,
                    q.type as question_type,
                    qz.title as quiz_title,
                    qz.order_index as quiz_order,
                    opt.option_text as user_answer_text,
                    correct_opt.option_text as correct_answer_text
                  FROM user_quiz_answers uqa
                  INNER JOIN questions q ON uqa.question_id = q.id
                  INNER JOIN quizzes qz ON uqa.quiz_id = qz.id
                  LEFT JOIN options opt ON uqa.selected_option = opt.id
                  LEFT JOIN options correct_opt ON correct_opt.question_id = q.id AND correct_opt.is_correct = 1
                  WHERE uqa.user_id = $user_id AND qz.course_id = $course_id
                  ORDER BY qz.order_index ASC, q.order_index ASC";
        
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $optionsQuery = "SELECT option_text FROM options WHERE question_id = " . $row['question_id'] . " ORDER BY id ASC";
                $optionsResult = mysqli_query($conn, $optionsQuery);
                $options = array();
                if ($optionsResult) {
                    while ($opt = mysqli_fetch_assoc($optionsResult)) {
                        $options[] = $opt['option_text'];
                    }
                }
                
                $userAnswer = $row['user_answer_text'];
                if (empty($userAnswer)) {
                    $userAnswer = 'Not answered';
                }
                
                $correctAnswer = $row['correct_answer_text'] ?: 'N/A';
                
                if ($row['question_type'] == 'tf') {
                    if ($userAnswer == '1' || $userAnswer == 'True' || strtolower($userAnswer) == 'true') {
                        $userAnswer = 'True';
                    } elseif ($userAnswer == '0' || $userAnswer == 'False' || strtolower($userAnswer) == 'false') {
                        $userAnswer = 'False';
                    }
                }
                
                $questions[] = array(
                    'question_id' => $row['question_id'],
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'user_answer' => $userAnswer,
                    'correct_answer' => $correctAnswer,
                    'is_correct' => (bool)$row['is_correct'],
                    'quiz_title' => $row['quiz_title'],
                    'options' => $options,
                    'selected_option_id' => $row['selected_option']
                );
            }
        }
    }
    
    $total = count($questions);
    $correct = 0;
    foreach ($questions as $q) {
        if ($q['is_correct']) $correct++;
    }
    $score = $total > 0 ? round(($correct / $total) * 100) : 0;
    
    echo json_encode(array(
        'success' => true,
        'questions' => $questions,
        'total_questions' => $total,
        'total_correct' => $correct,
        'overall_score' => $score
    ));
    exit();
}

$user_name = $_SESSION['firstname'] ?? 'Admin';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// FIXED: Use COALESCE to handle NULL completion dates
$sql = "SELECT 
            'certificate' as record_type,
            c.id as record_id,
            c.user_id,
            c.course_id,
            u.firstname,
            u.lastname,
            cr.title as course_name,
            c.issued_at as completion_timestamp,
            c.final_score as percentage,
            'Pass' as status
        FROM certificates c
        JOIN users u ON c.user_id = u.id
        JOIN courses cr ON c.course_id = cr.id
        WHERE u.role = 'employee' AND cr.status = 'published'
        
        UNION ALL
        
        SELECT 
            'user_course' as record_type,
            uc.id as record_id,
            uc.user_id,
            uc.course_id,
            u.firstname,
            u.lastname,
            cr.title as course_name,
            COALESCE(uc.completed_at, uc.last_accessed, uc.started_at, NOW()) as completion_timestamp,
            COALESCE(uc.final_score, 0) as percentage,
            CASE 
                WHEN uc.pass_status = 'failed' THEN 'Fail'
                WHEN uc.pass_status = 'passed' THEN 'Pass'
                ELSE 'Completed'
            END as status
        FROM user_courses uc
        JOIN users u ON uc.user_id = u.id
        JOIN courses cr ON uc.course_id = cr.id
        WHERE u.role = 'employee' 
            AND cr.status = 'published'
            AND uc.status = 'completed'
            AND uc.pass_status = 'failed'
            AND NOT EXISTS (
                SELECT 1 FROM certificates cert 
                WHERE cert.user_id = uc.user_id 
                AND cert.course_id = uc.course_id
            )
        ORDER BY completion_timestamp DESC, record_id DESC";

$result = mysqli_query($conn, $sql);
$allReportData = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($searchTerm) {
            $searchLower = strtolower($searchTerm);
            $name = strtolower($row['firstname'] . ' ' . $row['lastname']);
            $course = strtolower($row['course_name']);
            if (strpos($name, $searchLower) !== false || strpos($course, $searchLower) !== false) {
                $allReportData[] = $row;
            }
        } else {
            $allReportData[] = $row;
        }
    }
}

$totalRecords = count($allReportData);
$totalPages = ceil($totalRecords / $limit);
$reportData = array_slice($allReportData, $offset, $limit);
$lastUpdated = date('Y-m-d H:i:s');

while (ob_get_level()) {
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Completion Report | Upstaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 16rem; min-height: 100vh; margin-top: 70px; transition: margin-left 0.3s; }
        .main-content.sidebar-collapsed { margin-left: 5rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0 !important; } }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; max-width: 800px; width: 90%; max-height: 85vh; overflow-y: auto; }
        .answer-correct { background: #dcfce7; border-left: 4px solid #22c55e; }
        .answer-wrong { background: #fee2e2; border-left: 4px solid #ef4444; }
        .status-pass { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .status-fail { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .loading-spinner { display: inline-block; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pagination-btn { transition: all 0.2s; }
        .pagination-btn:hover:not(:disabled) { background: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="p-6">
            <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Training Completion Report</h1>
                    <p class="text-sm text-gray-500">Newest completions appear first</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">Last updated: <?php echo $lastUpdated; ?></span>
                    <button id="refreshBtn" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            
            <div class="mb-6 flex flex-col sm:flex-row justify-between gap-4">
                <input type="text" id="searchInput" placeholder="Search by name or course..." class="w-full sm:w-96 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <div class="flex gap-3">
                    <select id="limitSelect" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 entries</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20 entries</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 entries</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 entries</option>
                    </select>
                    <button id="exportBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><i class="fas fa-file-csv mr-1"></i> Export</button>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h2 class="font-semibold"><i class="fas fa-list-check text-blue-500 mr-2"></i> Course Completion Records</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left">Employee</th>
                                <th class="px-6 py-3 text-left">Course</th>
                                <th class="px-6 py-3 text-left">Completed</th>
                                <th class="px-6 py-3 text-left">Score</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-gray-200">
                            <?php foreach ($reportData as $record): ?>
                            <?php 
                            $fullName = htmlspecialchars($record['firstname'] . ' ' . $record['lastname']);
                            $courseName = htmlspecialchars($record['course_name']);
                            $scoreColor = $record['percentage'] >= 70 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                            $statusClass = $record['status'] == 'Pass' ? 'status-pass' : 'status-fail';
                            $completionDate = !empty($record['completion_timestamp']) && $record['completion_timestamp'] != '1970-01-01 00:00:00' ? date('m/d/Y g:i A', strtotime($record['completion_timestamp'])) : 'Date not recorded';
                            $initial = strtoupper(substr($record['firstname'], 0, 1));
                            ?>
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-3"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold"><?php echo $initial; ?></div><span class="font-medium"><?php echo $fullName; ?></span></div></td>
                                <td class="px-6 py-3"><?php echo $courseName; ?></td>
                                <td class="px-6 py-3 text-gray-600"><?php echo $completionDate; ?></td>
                                <td class="px-6 py-3 <?php echo $scoreColor; ?>"><?php echo round($record['percentage']); ?>%</td>
                                <td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium border <?php echo $statusClass; ?>"><?php echo $record['status']; ?></span></td>
                                <td class="px-6 py-3 text-center"><button onclick="viewDetails(<?php echo $record['user_id']; ?>, <?php echo $record['course_id']; ?>)" class="px-3 py-1 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white rounded-lg text-xs transition-colors"><i class="fas fa-eye text-xs"></i> View</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($reportData) == 0): ?>
                            <tr><td colspan="6" class="text-center py-12 text-gray-500"><i class="fas fa-inbox mr-2"></i> No records found<\/td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalRecords > 0): ?>
                <div class="px-6 py-3 border-t bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm">
                    <span class="text-gray-600">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> entries</span>
                    <div class="flex gap-2">
                        <button id="prevBtn" class="pagination-btn px-3 py-1 border rounded <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                        <span class="px-3 py-1">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        <button id="nextBtn" class="pagination-btn px-3 py-1 border rounded <?php echo $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'; ?>" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>Next</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quiz Modal -->
    <div id="quizModal" class="modal">
        <div class="modal-content">
            <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-lg font-bold"><i class="fas fa-clipboard-list text-blue-500 mr-2"></i> Quiz Answers Review</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                <div class="flex justify-between items-center">
                    <div><p class="text-sm text-gray-600">Final Score</p><p class="text-3xl font-bold text-blue-600" id="modalScore">0%</p></div>
                    <div class="text-right"><p class="text-sm text-gray-600">Status</p><p class="text-lg font-semibold" id="modalStatus"></p></div>
                </div>
            </div>
            <div id="questionsList" class="p-6 space-y-3"></div>
        </div>
    </div>

    <script>
        const allReportData = <?php echo json_encode($allReportData); ?>;
        let searchTerm = "<?php echo addslashes($searchTerm); ?>";
        let currentPage = <?php echo $page; ?>;
        let currentLimit = <?php echo $limit; ?>;
        
        const mainContent = document.getElementById("mainContent");
        const fixedHeader = document.querySelector('.fixed-header');
        
        function updateSidebarState() {
            const sidebar = document.getElementById("sidebar");
            if (!sidebar || !mainContent) return;
            if (sidebar.classList.contains("w-20")) {
                mainContent.classList.add("sidebar-collapsed");
                if (fixedHeader) fixedHeader.classList.add("sidebar-collapsed");
            } else {
                mainContent.classList.remove("sidebar-collapsed");
                if (fixedHeader) fixedHeader.classList.remove("sidebar-collapsed");
            }
        }
        
        window.addEventListener('sidebarToggle', function() { setTimeout(updateSidebarState, 10); });
        document.addEventListener('DOMContentLoaded', updateSidebarState);
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        
        function getFilteredData() {
            if (!searchTerm) return [...allReportData];
            const term = searchTerm.toLowerCase();
            return allReportData.filter(item => {
                const name = (item.firstname + ' ' + item.lastname).toLowerCase();
                const course = item.course_name.toLowerCase();
                return name.includes(term) || course.includes(term);
            });
        }
        
        function render() {
            const filtered = getFilteredData();
            const total = filtered.length;
            const pages = Math.ceil(total / currentLimit);
            if (currentPage > pages) currentPage = pages || 1;
            const start = (currentPage - 1) * currentLimit;
            const pageData = filtered.slice(start, start + currentLimit);
            
            const url = new URL(window.location.href);
            if (searchTerm) url.searchParams.set('search', searchTerm); else url.searchParams.delete('search');
            url.searchParams.set('page', currentPage);
            url.searchParams.set('limit', currentLimit);
            window.history.pushState({}, '', url);
            
            const showingText = document.querySelector('.px-6.py-3.border-t .text-gray-600');
            if (showingText && total > 0) {
                showingText.textContent = `Showing ${start + 1} to ${Math.min(start + currentLimit, total)} of ${total} entries`;
            }
            const pageSpan = document.querySelector('.px-6.py-3.border-t span:not(.text-gray-600)');
            if (pageSpan) pageSpan.innerHTML = `Page ${currentPage} of ${pages}`;
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= pages;
            
            if (pageData.length === 0) {
                document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-500"><i class="fas fa-inbox mr-2"></i> No matching records found<\/td></td>';
                return;
            }
            
            let html = '';
            for (let item of pageData) {
                const scoreColor = item.percentage >= 70 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                const statusClass = item.status === 'Pass' ? 'status-pass' : 'status-fail';
                let completionDate = item.completion_timestamp ? new Date(item.completion_timestamp).toLocaleString() : 'Date not recorded';
                if (completionDate.includes('1970')) completionDate = 'Date not recorded';
                const initial = (item.firstname || 'U').charAt(0).toUpperCase();
                html += `<tr class="hover:bg-blue-50 transition-colors">
                    <td class="px-6 py-3"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold">${initial}</div><span class="font-medium">${escapeHtml(item.firstname + ' ' + (item.lastname || ''))}</span></div></td>
                    <td class="px-6 py-3">${escapeHtml(item.course_name)}</td>
                    <td class="px-6 py-3 text-gray-600">${completionDate}</td>
                    <td class="px-6 py-3 ${scoreColor}">${Math.round(item.percentage)}%</td>
                    <td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium border ${statusClass}">${item.status}</span></td>
                    <td class="px-6 py-3 text-center"><button onclick="viewDetails(${item.user_id}, ${item.course_id})" class="px-3 py-1 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white rounded-lg text-xs transition-colors"><i class="fas fa-eye text-xs"></i> View</button></td>
                  </tr>`;
            }
            document.getElementById('tableBody').innerHTML = html;
        }
        
        async function viewDetails(userId, courseId) {
            const modal = document.getElementById('quizModal');
            modal.classList.add('active');
            document.getElementById('questionsList').innerHTML = '<div class="text-center py-8"><div class="loading-spinner mb-2"></div><p>Loading quiz details...</p></div>';
            document.getElementById('modalScore').innerHTML = '0%';
            document.getElementById('modalStatus').innerHTML = '';
            
            try {
                const response = await fetch(`?ajax=quiz_details&user_id=${userId}&course_id=${courseId}`);
                const data = await response.json();
                
                if (data.success && data.questions && data.questions.length > 0) {
                    displayQuestions(data.questions);
                    document.getElementById('modalScore').innerHTML = data.overall_score + '%';
                    const statusEl = document.getElementById('modalStatus');
                    if (data.overall_score >= 70) {
                        statusEl.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Passed</span>';
                    } else {
                        statusEl.innerHTML = '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i> Failed</span>';
                    }
                } else {
                    document.getElementById('questionsList').innerHTML = '<div class="text-center text-amber-600 py-8"><i class="fas fa-exclamation-triangle text-4xl mb-3 opacity-50"></i><p>No quiz answers found for this completion record.</p><p class="text-sm mt-2 text-gray-500">The employee may not have taken the quizzes yet.</p></div>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('questionsList').innerHTML = `<div class="text-center text-red-500 py-8"><i class="fas fa-exclamation-circle text-4xl mb-3"></i><p>Failed to load quiz details.</p><p class="text-sm mt-1">${error.message}</p></div>`;
            }
        }
        
        function displayQuestions(questions) {
            let html = '', correct = 0, currentQuiz = '';
            const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            
            for (let i = 0; i < questions.length; i++) {
                const q = questions[i];
                if (q.is_correct) correct++;
                const isCorrect = q.is_correct;
                const bgClass = isCorrect ? 'answer-correct' : 'answer-wrong';
                const statusIcon = isCorrect ? '<i class="fas fa-check-circle text-green-500"></i>' : '<i class="fas fa-times-circle text-red-500"></i>';
                
                let userAnswer = q.user_answer || 'Not answered';
                let correctAnswer = q.correct_answer || 'N/A';
                
                if (userAnswer === 'Not answered' && q.selected_option_id !== undefined && q.selected_option_id !== null) {
                    const optionIndex = parseInt(q.selected_option_id);
                    if (optionIndex >= 0 && optionIndex < letters.length && q.options && q.options[optionIndex]) {
                        userAnswer = `${letters[optionIndex]}. ${q.options[optionIndex]}`;
                    }
                } else if (userAnswer !== 'Not answered' && q.options && q.options.length > 0) {
                    const userAnswerIndex = q.options.findIndex(opt => opt === userAnswer);
                    if (userAnswerIndex !== -1) {
                        userAnswer = `${letters[userAnswerIndex]}. ${userAnswer}`;
                    }
                }
                
                if (correctAnswer !== 'N/A' && q.options && q.options.length > 0) {
                    const correctAnswerIndex = q.options.findIndex(opt => opt === correctAnswer);
                    if (correctAnswerIndex !== -1) {
                        correctAnswer = `${letters[correctAnswerIndex]}. ${correctAnswer}`;
                    }
                }
                
                if (q.quiz_title && q.quiz_title !== currentQuiz) {
                    currentQuiz = q.quiz_title;
                    html += `<div class="bg-blue-100 rounded-lg p-2 mb-2"><p class="font-bold text-blue-800 text-sm"><i class="fas fa-book mr-1"></i> Quiz: ${escapeHtml(currentQuiz)}</p></div>`;
                }
                
                let optionsHtml = '';
                if (q.options && q.options.length > 0) {
                    const optionsList = q.options.map((opt, idx) => {
                        let isUserAnswer = false;
                        if (q.user_answer === opt) {
                            isUserAnswer = true;
                        } else if (q.selected_option_id !== undefined && parseInt(q.selected_option_id) === idx) {
                            isUserAnswer = true;
                        }
                        const isCorrectAnswer = (opt === q.correct_answer);
                        let style = '';
                        if (isUserAnswer && isCorrectAnswer) {
                            style = 'text-green-600 font-bold';
                        } else if (isUserAnswer && !isCorrectAnswer) {
                            style = 'text-red-600 line-through';
                        } else if (isCorrectAnswer) {
                            style = 'text-green-600';
                        }
                        return `<span class="${style}">${letters[idx]}. ${escapeHtml(opt)}</span>`;
                    }).join(' | ');
                    optionsHtml = `<div class="mt-2 text-xs text-gray-500"><span class="font-medium">Options:</span> ${optionsList}</div>`;
                }
                
                html += `<div class="question-item ${bgClass} rounded-lg p-3 transition-all mb-2">
                    <div class="flex items-start gap-2">
                        ${statusIcon}
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800 text-sm">Question ${i+1}: ${escapeHtml(q.question_text)}</p>
                            <div class="mt-1 text-sm">
                                <p class="text-gray-700"><span class="font-medium">📝 Employee Answer:</span> 
                                    <span class="${isCorrect ? 'text-green-700' : 'text-red-700'} font-medium">
                                        ${escapeHtml(userAnswer)}
                                    </span>
                                </p>
                                ${!isCorrect ? `<p class="text-gray-700 mt-1"><span class="font-medium">✅ Correct Answer:</span> <span class="text-green-700">${escapeHtml(correctAnswer)}</span></p>` : ''}
                                ${optionsHtml}
                            </div>
                        </div>
                        <div><span class="text-xs font-semibold ${isCorrect ? 'text-green-600' : 'text-red-600'} px-2 py-1 rounded-full bg-white/60">${isCorrect ? 'Correct' : 'Wrong'}</span></div>
                    </div>
                </div>`;
            }
            
            const summary = `<div class="bg-gray-50 rounded-lg p-3 mb-3 border">
                <div class="flex justify-between flex-wrap gap-2 text-sm">
                    <span>📊 Total: <strong>${questions.length}</strong></span>
                    <span class="text-green-600">✓ Correct: <strong>${correct}</strong></span>
                    <span class="text-red-600">✗ Wrong: <strong>${questions.length - correct}</strong></span>
                    <span class="text-blue-600">🎯 Accuracy: <strong>${Math.round((correct/questions.length)*100)}%</strong></span>
                </div>
            </div>`;
            
            document.getElementById('questionsList').innerHTML = summary + html;
        }
        
        function closeModal() {
            document.getElementById('quizModal').classList.remove('active');
        }
        
        document.getElementById('searchInput').addEventListener('input', function() {
            searchTerm = this.value;
            currentPage = 1;
            render();
        });
        
        document.getElementById('limitSelect').addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            render();
        });
        
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        if (prevBtn) prevBtn.addEventListener('click', function() { if (currentPage > 1) { currentPage--; render(); } });
        if (nextBtn) nextBtn.addEventListener('click', function() { 
            const filtered = getFilteredData();
            const pages = Math.ceil(filtered.length / currentLimit);
            if (currentPage < pages) { currentPage++; render(); } 
        });
        
        document.getElementById('refreshBtn').addEventListener('click', function() { location.reload(); });
        
        document.getElementById('exportBtn').addEventListener('click', function() {
            const filtered = getFilteredData();
            let csv = "\uFEFFEmployee Name,Course Name,Completion Date & Time,Percentage (%),Status\n";
            for (let item of filtered) {
                let completionDate = item.completion_timestamp ? new Date(item.completion_timestamp).toLocaleString() : 'Date not recorded';
                if (completionDate.includes('1970')) completionDate = 'Date not recorded';
                csv += `"${item.firstname} ${item.lastname}","${item.course_name}","${completionDate}",${Math.round(item.percentage)},${item.status}\n`;
            }
            const blob = new Blob([csv], {type: "text/csv;charset=utf-8"});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `training_report_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        });
        
        document.getElementById('quizModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
        
        render();
    </script>
</body>
</html>
