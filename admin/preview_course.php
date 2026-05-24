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

// Get course ID
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($course_id <= 0) {
    header("Location: course_management.php?error=Invalid course ID");
    exit();
}

// Function to strip HTML tags from description
function stripHtmlTags($text) {
    if (empty($text)) return '';
    return strip_tags($text);
}

// Function to fix video URL
function fixVideoUrl($url) {
    if (empty($url)) return '';
    if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        return $url;
    }
    $clean = preg_replace('#^(/upstaff/|/uploads/)#', '', $url);
    return '/upstaff/uploads/' . $clean;
}

// Function to fix thumbnail URL
function fixThumbnailUrl($url) {
    if (empty($url)) return '';
    if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
    $clean = preg_replace('#^(/upstaff/|/uploads/)#', '', $url);
    return '/upstaff/uploads/' . $clean;
}

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course_result = $stmt->get_result();
$course = $course_result->fetch_assoc();

if (!$course) {
    header("Location: course_management.php?error=Course not found");
    exit();
}

// Fix thumbnail URL
$course['thumbnail_url_fixed'] = fixThumbnailUrl($course['thumbnail_url'] ?? '');
// Strip HTML tags from description
$course['description_plain'] = stripHtmlTags($course['description'] ?? 'No description provided.');

// Fetch video
$stmt = $conn->prepare("SELECT * FROM videos WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

// Fix video URL
if ($video && !empty($video['video_url'])) {
    $video['video_url_fixed'] = fixVideoUrl($video['video_url']);
}

// Fetch quiz settings
$stmt = $conn->prepare("SELECT * FROM quiz_settings WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quiz_settings = $stmt->get_result()->fetch_assoc();

if (!$quiz_settings) {
    $quiz_settings = [
        'global_timer_minutes' => 10, 'global_timer_seconds' => 0, 'passing_threshold' => 70,
        'randomize_questions' => 0, 'randomize_options' => 0, 'hide_correct_answers' => 0, 'disable_copy' => 1
    ];
}

// Fetch quizzes
$quizzes = [];
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY order_index ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quizzes_result = $stmt->get_result();

while ($quiz = $quizzes_result->fetch_assoc()) {
    $stmt2 = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY order_index ASC");
    $stmt2->bind_param("i", $quiz['id']);
    $stmt2->execute();
    $questions_result = $stmt2->get_result();
    $questions = [];
    
    while ($question = $questions_result->fetch_assoc()) {
        $stmt3 = $conn->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY order_index ASC");
        $stmt3->bind_param("i", $question['id']);
        $stmt3->execute();
        $options_result = $stmt3->get_result();
        $options = [];
        while ($option = $options_result->fetch_assoc()) $options[] = $option;
        $question['options'] = $options;
        $questions[] = $question;
    }
    $quiz['questions'] = $questions;
    $quizzes[] = $quiz;
}

$page_title = "Edit Course: " . htmlspecialchars($course['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> · Live Edit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; border-radius: 12px; }
        .video-container iframe, .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .edit-mode .editable { background-color: #fff9c4; border: 1px solid #f9a825; cursor: pointer; padding: 4px 8px; border-radius: 6px; display: inline-block; }
        .edit-mode .editable:hover { background-color: #fff176; }
        .edit-input { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        .edit-input:focus { outline: none; border-color: #3b82f6; }
        .edit-textarea { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        .edit-select { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; }
        .quiz-card { transition: all 0.2s; }
        .option-row.correct-highlight { background-color: #dcfce7 !important; border-color: #22c55e !important; }
        .thumbnail-preview { max-width: 200px; max-height: 120px; object-fit: cover; border-radius: 8px; margin-top: 12px; }
    </style>
</head>
<body class="bg-slate-50">

    <main class="max-w-5xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <a href="course_management.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition shadow-sm">
                <span class="material-symbols-outlined text-base">arrow_back</span> Back
            </a>
            <div class="flex gap-3">
                <button id="toggleEditMode" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm transition">
                    <span class="material-symbols-outlined text-base">edit</span> Edit Mode
                </button>
                <button id="saveChangesBtn" class="hidden inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                    <span class="material-symbols-outlined text-base">save</span> Save All Changes
                </button>
            </div>
        </div>

        <!-- Course Title -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-5">
            <div class="flex-1">
                <div id="titleDisplay" class="text-2xl font-bold text-slate-800 mb-2 editable"><?php echo htmlspecialchars($course['title']); ?></div>
                <div id="titleInput" class="hidden"><input type="text" id="courseTitle" class="edit-input text-2xl font-bold" value="<?php echo htmlspecialchars($course['title']); ?>"></div>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span id="typeDisplay" class="px-2 py-0.5 rounded-md text-xs font-medium editable cursor-pointer <?php echo $course['type'] === 'general' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>"><?php echo ucfirst($course['type']); ?></span>
                    <div id="typeInput" class="hidden"><select id="courseType" class="edit-select text-xs"><option value="general" <?php echo $course['type'] === 'general' ? 'selected' : ''; ?>>General</option><option value="upskilling" <?php echo $course['type'] === 'upskilling' ? 'selected' : ''; ?>>Upskilling</option></select></div>
                    
                    <span id="categoryDisplay" class="px-2 py-0.5 rounded-md bg-green-100 text-green-700 text-xs font-medium editable cursor-pointer"><?php echo htmlspecialchars($course['category'] ?? 'Uncategorized'); ?></span>
                    <div id="categoryInput" class="hidden"><select id="courseCategory" class="edit-select text-xs"><option>Design</option><option>Development</option><option>Marketing</option><option>Business</option><option>Security & IT</option><option>Data Science</option></select></div>
                    
                    <span id="difficultyDisplay" class="px-2 py-0.5 rounded-md bg-orange-100 text-orange-700 text-xs font-medium editable cursor-pointer"><?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?></span>
                    <div id="difficultyInput" class="hidden"><select id="courseDifficulty" class="edit-select text-xs"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select></div>
                    
                    <span id="statusDisplay" class="px-2 py-0.5 rounded-md <?php echo $course['status'] === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'; ?> text-xs font-medium editable cursor-pointer"><?php echo ucfirst($course['status']); ?></span>
                    <div id="statusInput" class="hidden"><select id="courseStatus" class="edit-select text-xs"><option value="draft" <?php echo $course['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option><option value="published" <?php echo $course['status'] === 'published' ? 'selected' : ''; ?>>Published</option></select></div>
                </div>
            </div>
        </div>

        <!-- Description - WITHOUT P TAGS -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50">
                <h3 class="font-semibold text-slate-800"><span class="material-symbols-outlined text-blue-500 align-middle">description</span> Description</h3>
            </div>
            <div class="p-4">
                <div id="descDisplay" class="text-sm text-slate-700 editable cursor-pointer"><?php echo nl2br(htmlspecialchars($course['description_plain'])); ?></div>
                <div id="descInput" class="hidden"><textarea id="courseDescription" rows="5" class="edit-textarea"><?php echo htmlspecialchars($course['description_plain']); ?></textarea></div>
            </div>
        </div>

        <!-- Thumbnail -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50">
                <h3 class="font-semibold text-slate-800"><span class="material-symbols-outlined text-blue-500 align-middle">image</span> Thumbnail</h3>
            </div>
            <div class="p-4">
                <div id="thumbDisplay" class="text-sm text-blue-600 break-all editable cursor-pointer"><?php echo $course['thumbnail_url_fixed'] ?: 'Click to set thumbnail URL'; ?></div>
                <div id="thumbInput" class="hidden">
                    <input type="text" id="thumbnailUrl" class="edit-input" value="<?php echo htmlspecialchars($course['thumbnail_url_fixed'] ?? ''); ?>" placeholder="/upstaff/uploads/thumbnail.jpg">
                    <p class="text-xs text-slate-400 mt-1">Example: /upstaff/uploads/your-image.jpg</p>
                </div>
                <?php if (!empty($course['thumbnail_url_fixed'])): ?>
                    <img id="thumbnailPreviewImg" src="<?php echo htmlspecialchars($course['thumbnail_url_fixed']); ?>" class="thumbnail-preview" onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
        </div>

        <!-- Video Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50">
                <h3 class="font-semibold text-slate-800"><span class="material-symbols-outlined text-blue-500 align-middle">smart_display</span> Video Lesson</h3>
            </div>
            <div class="p-4">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Video Title</label>
                    <div id="videoTitleDisplay" class="text-sm editable cursor-pointer"><?php echo htmlspecialchars($video['title'] ?? 'Untitled Video'); ?></div>
                    <div id="videoTitleInput" class="hidden"><input type="text" id="videoTitle" class="edit-input" value="<?php echo htmlspecialchars($video['title'] ?? ''); ?>"></div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Video URL</label>
                    <div id="videoUrlDisplay" class="text-sm text-blue-600 break-all editable cursor-pointer"><?php echo $video['video_url_fixed'] ?? 'Click to set video URL'; ?></div>
                    <div id="videoUrlInput" class="hidden">
                        <input type="text" id="videoUrl" class="edit-input" value="<?php echo htmlspecialchars($video['video_url_fixed'] ?? ''); ?>" placeholder="/upstaff/uploads/video.mp4 or https://youtube.com/watch?v=...">
                        <p class="text-xs text-slate-400 mt-1">Example: 1776962376_2026-04-0405-17-04.mp4</p>
                    </div>
                </div>
                
                <!-- Video Preview -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Preview</label>
                    <div id="videoPreviewContainer" class="video-container">
                        <?php 
                        $video_url = $video['video_url_fixed'] ?? '';
                        if (!empty($video_url)): 
                            $is_youtube = strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false;
                            if ($is_youtube && preg_match('/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/)([^&?]+)/', $video_url, $matches)): ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo $matches[1]; ?>" frameborder="0" allowfullscreen></iframe>
                            <?php else: ?>
                                <video controls>
                                    <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="flex items-center justify-center h-full text-white bg-slate-800">
                                <span class="material-symbols-outlined text-4xl">videocam_off</span>
                                <span class="ml-2">No video</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50">
                <h3 class="font-semibold text-slate-800"><span class="material-symbols-outlined text-blue-500 align-middle">settings</span> Quiz Settings</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-medium text-slate-500">Time Limit</label>
                        <div id="timerDisplay" class="text-sm font-semibold editable cursor-pointer"><?php 
                            $m = $quiz_settings['global_timer_minutes'] ?? 10; 
                            $s = $quiz_settings['global_timer_seconds'] ?? 0; 
                            echo $m > 0 ? "{$m}m" : '';
                            echo $s > 0 ? ($m > 0 ? " {$s}s" : "{$s}s") : ($m == 0 && $s == 0 ? 'No limit' : '');
                        ?></div>
                        <div id="timerInput" class="hidden flex gap-2"><input type="number" id="timerMinutes" class="edit-input w-20" value="<?php echo $m; ?>"> min <input type="number" id="timerSeconds" class="edit-input w-20" value="<?php echo $s; ?>"> sec</div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Passing Score</label>
                        <div id="passingDisplay" class="text-sm font-bold text-green-600 editable cursor-pointer"><?php echo $quiz_settings['passing_threshold'] ?? 70; ?>%</div>
                        <div id="passingInput" class="hidden"><input type="range" id="passingThreshold" min="0" max="100" value="<?php echo $quiz_settings['passing_threshold'] ?? 70; ?>" class="w-32"> <span id="thresholdValue"><?php echo $quiz_settings['passing_threshold'] ?? 70; ?>%</span></div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Randomize Questions</label>
                        <div id="randomizeQuestionsDisplay" class="text-sm editable cursor-pointer"><?php echo ($quiz_settings['randomize_questions'] ?? 0) ? '✅ Enabled' : '❌ Disabled'; ?></div>
                        <div id="randomizeQuestionsInput" class="hidden"><input type="checkbox" id="randomizeQuestions" <?php echo ($quiz_settings['randomize_questions'] ?? 0) ? 'checked' : ''; ?>> Enabled</div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Disable Copy</label>
                        <div id="disableCopyDisplay" class="text-sm editable cursor-pointer"><?php echo ($quiz_settings['disable_copy'] ?? 1) ? '✅ Enabled' : '❌ Disabled'; ?></div>
                        <div id="disableCopyInput" class="hidden"><input type="checkbox" id="disableCopy" <?php echo ($quiz_settings['disable_copy'] ?? 1) ? 'checked' : ''; ?>> Enabled</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quizzes Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800"><span class="material-symbols-outlined text-blue-500 align-middle">quiz</span> Quizzes (<?php echo count($quizzes); ?>)</h3>
                <button id="addQuizBtn" class="hidden px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs">+ Add Quiz</button>
            </div>
            <div id="quizzesContainer" class="p-4 space-y-4">
                <?php if (empty($quizzes)): ?>
                    <p class="text-center text-slate-400 py-4">No quizzes yet. Enable Edit Mode to add quizzes.</p>
                <?php else: ?>
                    <?php foreach ($quizzes as $quiz_idx => $quiz): ?>
                        <div class="quiz-card border border-slate-200 rounded-xl overflow-hidden" data-quiz-index="<?php echo $quiz_idx; ?>">
                            <div class="border-b border-slate-200 px-4 py-3 bg-slate-50 flex justify-between items-center">
                                <div class="flex-1">
                                    <div id="quizTitleDisplay_<?php echo $quiz_idx; ?>" class="font-semibold text-slate-800 editable cursor-pointer"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                    <div id="quizTitleInput_<?php echo $quiz_idx; ?>" class="hidden"><input type="text" class="edit-input quiz-title" data-quiz-index="<?php echo $quiz_idx; ?>" value="<?php echo htmlspecialchars($quiz['title']); ?>"></div>
                                </div>
                                <button class="delete-quiz-btn hidden text-red-500 hover:text-red-700" data-quiz-index="<?php echo $quiz_idx; ?>"><span class="material-symbols-outlined">delete</span></button>
                            </div>
                            <div class="p-4 space-y-4">
                                <div class="flex justify-end gap-2">
                                    <button class="add-mcq-btn hidden text-xs bg-blue-100 px-3 py-1.5 rounded-lg" data-quiz-index="<?php echo $quiz_idx; ?>">+ MC Question</button>
                                    <button class="add-tf-btn hidden text-xs bg-green-100 px-3 py-1.5 rounded-lg" data-quiz-index="<?php echo $quiz_idx; ?>">+ True/False</button>
                                </div>
                                <div class="questions-list space-y-4">
                                    <?php foreach ($quiz['questions'] as $q_idx => $question): ?>
                                        <div class="border border-slate-200 rounded-lg p-4" data-question-index="<?php echo $q_idx; ?>">
                                            <div class="flex justify-between items-start gap-3 mb-3">
                                                <div class="flex-1">
                                                    <div id="questionTextDisplay_<?php echo $quiz_idx . '_' . $q_idx; ?>" class="text-sm font-medium text-slate-700 editable cursor-pointer"><?php echo htmlspecialchars($question['question_text']); ?> <span class="text-xs text-slate-400 ml-2">(<?php echo strtoupper($question['type']); ?>)</span></div>
                                                    <div id="questionTextInput_<?php echo $quiz_idx . '_' . $q_idx; ?>" class="hidden"><input type="text" class="edit-input question-text" data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>" value="<?php echo htmlspecialchars($question['question_text']); ?>"></div>
                                                </div>
                                                <button class="delete-question-btn hidden text-red-400 hover:text-red-600" data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>"><span class="material-symbols-outlined">close</span></button>
                                            </div>
                                            <div class="options-list space-y-2 ml-4">
                                                <?php foreach ($question['options'] as $opt_idx => $option): ?>
                                                    <div class="option-row flex items-center gap-2 p-2 rounded-lg <?php echo $option['is_correct'] ? 'correct-highlight border border-green-400' : 'border border-slate-200'; ?>">
                                                        <input type="radio" name="correct_<?php echo $quiz_idx . '_' . $q_idx; ?>" class="correct-radio hidden" <?php echo $option['is_correct'] ? 'checked' : ''; ?> data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>" data-opt-index="<?php echo $opt_idx; ?>">
                                                        <span class="w-6 h-6 rounded-full <?php echo $option['is_correct'] ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-600'; ?> flex items-center justify-center text-xs font-bold"><?php echo chr(65 + $opt_idx); ?></span>
                                                        <div id="optionTextDisplay_<?php echo $quiz_idx . '_' . $q_idx . '_' . $opt_idx; ?>" class="flex-1 text-sm editable cursor-pointer"><?php echo htmlspecialchars($option['option_text']); ?></div>
                                                        <div id="optionTextInput_<?php echo $quiz_idx . '_' . $q_idx . '_' . $opt_idx; ?>" class="hidden flex-1"><input type="text" class="edit-input option-text" data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>" data-opt-index="<?php echo $opt_idx; ?>" value="<?php echo htmlspecialchars($option['option_text']); ?>"></div>
                                                        <?php if ($option['is_correct']): ?><span class="text-green-500 text-xs">✓ Correct</span><?php endif; ?>
                                                        <button class="delete-opt-btn hidden text-slate-400 hover:text-red-500" data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>" data-opt-index="<?php echo $opt_idx; ?>"><span class="material-symbols-outlined">close</span></button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="add-option-btn hidden text-xs text-blue-600 mt-2 hover:underline" data-quiz-index="<?php echo $quiz_idx; ?>" data-question-index="<?php echo $q_idx; ?>">+ Add Option</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 flex justify-end"><a href="course_management.php" class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50">Close</a></div>
    </main>

    <div id="toastMsg" class="fixed bottom-6 right-6 z-50 hidden"></div>

    <script>
        let editMode = false;
        let courseId = <?php echo $course_id; ?>;
        
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toastMsg');
            toast.className = `fixed bottom-6 right-6 z-50 px-4 py-2 ${type === 'error' ? 'bg-red-500' : 'bg-green-600'} text-white text-sm rounded-lg shadow-lg`;
            toast.innerHTML = msg;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }
        
        function fixUrl(url) {
            if (!url) return '';
            if (url.startsWith('http')) return url;
            let clean = url.replace(/^(\/upstaff\/uploads\/|\/upstaff\/|\/uploads\/)/, '');
            if (!clean.includes('/') && !clean.includes('http')) {
                return '/upstaff/uploads/' + clean;
            }
            return '/upstaff/uploads/' + clean;
        }
        
        function updateVideoPreview() {
            const container = document.getElementById('videoPreviewContainer');
            let url = document.getElementById('videoUrl').value;
            
            if (!url || url === '') {
                container.innerHTML = `<div class="flex items-center justify-center h-full text-white bg-slate-800"><span class="material-symbols-outlined text-4xl">videocam_off</span><span class="ml-2">No video</span></div>`;
                return;
            }
            
            let fixedUrl = fixUrl(url);
            const isYoutube = fixedUrl.includes('youtube.com') || fixedUrl.includes('youtu.be');
            
            if (isYoutube) {
                let videoId = '';
                const patterns = [
                    /(?:youtube\.com\/watch\?v=)([^&]+)/,
                    /(?:youtu\.be\/)([^?]+)/
                ];
                for (let pattern of patterns) {
                    const match = fixedUrl.match(pattern);
                    if (match) { videoId = match[1]; break; }
                }
                if (videoId) {
                    container.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
                    return;
                }
            }
            
            container.innerHTML = `<video controls><source src="${fixedUrl}" type="video/mp4">Your browser does not support the video tag.</video>`;
        }
        
        function updateThumbnailPreview() {
            let url = document.getElementById('thumbnailUrl').value;
            const img = document.getElementById('thumbnailPreviewImg');
            if (url && img) {
                let fixedUrl = fixUrl(url);
                img.src = fixedUrl;
                img.style.display = 'block';
                img.onerror = () => img.style.display = 'none';
            }
        }
        
        function toggleEditMode() {
            editMode = !editMode;
            const saveBtn = document.getElementById('saveChangesBtn');
            const addQuizBtn = document.getElementById('addQuizBtn');
            
            if (editMode) {
                document.body.classList.add('edit-mode');
                saveBtn.classList.remove('hidden');
                if (addQuizBtn) addQuizBtn.classList.remove('hidden');
                document.querySelectorAll('.delete-quiz-btn, .delete-question-btn, .delete-opt-btn, .add-mcq-btn, .add-tf-btn, .add-option-btn, .correct-radio').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('[id$=Display]').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('[id$=Input]').forEach(el => el.classList.remove('hidden'));
            } else {
                document.body.classList.remove('edit-mode');
                saveBtn.classList.add('hidden');
                if (addQuizBtn) addQuizBtn.classList.add('hidden');
                document.querySelectorAll('.delete-quiz-btn, .delete-question-btn, .delete-opt-btn, .add-mcq-btn, .add-tf-btn, .add-option-btn, .correct-radio').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('[id$=Display]').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('[id$=Input]').forEach(el => el.classList.add('hidden'));
                location.reload();
            }
        }
        
        async function saveChanges() {
            const quizzes = [];
            document.querySelectorAll('.quiz-card').forEach((quizCard) => {
                const quizTitle = quizCard.querySelector('.quiz-title')?.value || 'Untitled Quiz';
                const questions = [];
                quizCard.querySelectorAll('.questions-list > div').forEach((questionDiv) => {
                    const questionText = questionDiv.querySelector('.question-text')?.value || '';
                    const type = questionDiv.querySelector('.add-option-btn') ? 'mc' : 'tf';
                    const options = [];
                    questionDiv.querySelectorAll('.option-row').forEach((optRow) => {
                        const optionText = optRow.querySelector('.option-text')?.value || '';
                        const isCorrect = optRow.querySelector('.correct-radio')?.checked || false;
                        options.push({ text: optionText, correct: isCorrect });
                    });
                    if (questionText) {
                        questions.push({ text: questionText, type: type, options: options });
                    }
                });
                quizzes.push({ title: quizTitle, questions: questions });
            });
            
            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('title', document.getElementById('courseTitle').value);
            formData.append('type', document.getElementById('courseType').value);
            formData.append('category', document.getElementById('courseCategory').value);
            formData.append('difficulty', document.getElementById('courseDifficulty').value);
            formData.append('description', document.getElementById('courseDescription').value);
            formData.append('status', document.getElementById('courseStatus').value);
            formData.append('thumbnail_url', document.getElementById('thumbnailUrl').value);
            formData.append('video_title', document.getElementById('videoTitle').value);
            formData.append('video_url', document.getElementById('videoUrl').value);
            formData.append('timer_minutes', document.getElementById('timerMinutes').value);
            formData.append('timer_seconds', document.getElementById('timerSeconds').value);
            formData.append('passing_threshold', document.getElementById('passingThreshold').value);
            formData.append('randomize_questions', document.getElementById('randomizeQuestions').checked ? 1 : 0);
            formData.append('disable_copy', document.getElementById('disableCopy').checked ? 1 : 0);
            formData.append('quizzes', JSON.stringify(quizzes));
            
            try {
                const response = await fetch('course-builder/api/update_full_course.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Course updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.error || 'Failed to save', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Event listeners
        document.getElementById('toggleEditMode').addEventListener('click', toggleEditMode);
        document.getElementById('saveChangesBtn').addEventListener('click', saveChanges);
        
        const videoUrlInput = document.getElementById('videoUrl');
        if (videoUrlInput) {
            videoUrlInput.addEventListener('change', updateVideoPreview);
            videoUrlInput.addEventListener('input', updateVideoPreview);
        }
        
        const thumbUrlInput = document.getElementById('thumbnailUrl');
        if (thumbUrlInput) {
            thumbUrlInput.addEventListener('change', updateThumbnailPreview);
            thumbUrlInput.addEventListener('input', updateThumbnailPreview);
        }
        
        const thresholdSlider = document.getElementById('passingThreshold');
        const thresholdValue = document.getElementById('thresholdValue');
        if (thresholdSlider) {
            thresholdSlider.addEventListener('input', () => thresholdValue.textContent = thresholdSlider.value + '%');
        }
        
        document.getElementById('addQuizBtn')?.addEventListener('click', () => {
            const quizzesContainer = document.getElementById('quizzesContainer');
            const newQuizIndex = Date.now();
            const newQuizHtml = `
                <div class="quiz-card border border-slate-200 rounded-xl overflow-hidden" data-quiz-index="${newQuizIndex}">
                    <div class="border-b border-slate-200 px-4 py-3 bg-slate-50 flex justify-between items-center">
                        <div class="flex-1">
                            <div class="font-semibold text-slate-800 editable cursor-pointer">New Quiz</div>
                            <div class="hidden"><input type="text" class="edit-input quiz-title" data-quiz-index="${newQuizIndex}" value="New Quiz"></div>
                        </div>
                        <button class="delete-quiz-btn text-red-500 hover:text-red-700"><span class="material-symbols-outlined">delete</span></button>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="flex justify-end gap-2">
                            <button class="add-mcq-btn text-xs bg-blue-100 px-3 py-1.5 rounded-lg">+ MC Question</button>
                            <button class="add-tf-btn text-xs bg-green-100 px-3 py-1.5 rounded-lg">+ True/False</button>
                        </div>
                        <div class="questions-list space-y-4">
                            <p class="text-center text-slate-400 py-4">No questions yet. Add a question above.</p>
                        </div>
                    </div>
                </div>
            `;
            quizzesContainer.insertAdjacentHTML('beforeend', newQuizHtml);
            showToast('New quiz added', 'success');
        });
        
        // Initialize previews
        setTimeout(() => {
            if (videoUrlInput && videoUrlInput.value) updateVideoPreview();
            if (thumbUrlInput && thumbUrlInput.value) updateThumbnailPreview();
        }, 100);
    </script>
</body>
</html>