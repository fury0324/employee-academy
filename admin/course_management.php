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

$user_name = $_SESSION['firstname'] ?? 'Admin';

// Handle filters from GET for persistence
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clauses[] = "title LIKE ?";
    $params[] = "%$search_term%";
    $types .= "s";
}
if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch courses from merged database
$query = "SELECT * FROM courses $where_sql ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Get unique categories and difficulties for filters
$categories = [];
$difficulties = [];
$cat_result = $conn->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL");
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
$diff_result = $conn->query("SELECT DISTINCT difficulty FROM courses WHERE difficulty IS NOT NULL");
while ($row = $diff_result->fetch_assoc()) {
    $difficulties[] = $row['difficulty'];
}

// Display success/error messages if any
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Course Manager | UpStaff Admin</title>
    <!-- Google Material Symbols + TailwindCSS -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        /* MAIN CONTENT OFFSET - matches sidebar width */
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            margin-top: 70px;
        }
        
        /* When sidebar is collapsed */
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
        }
        
        /* Fixed Header Styles - SYNC with main-content */
        .fixed-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 40;
            background: white;
            transition: margin-left 0.3s ease, width 0.3s ease;
            margin-left: 16rem;
            width: calc(100% - 16rem);
        }
        
        .fixed-header.sidebar-collapsed {
            margin-left: 5rem;
            width: calc(100% - 5rem);
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
            .fixed-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        
        /* Smooth transitions & custom details marker removal */
        .course-item summary::-webkit-details-marker {
            display: none;
        }

        .course-item summary {
            list-style: none;
        }

        .chevron-icon {
            transition: transform 0.2s ease;
        }

        .course-item[open] .chevron-icon {
            transform: rotate(180deg);
        }

        /* Filter dropdown animation */
        #filterDropdown {
            transition: opacity 0.2s ease, visibility 0.2s, transform 0.2s ease;
            opacity: 0;
            visibility: hidden;
            transform-origin: top right;
            transform: scale(0.95);
        }

        #filterDropdown.open {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .badge-count {
            background-color: #e11d48;
            color: white;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 9999px;
            padding: 0.125rem 0.45rem;
            margin-left: 0.25rem;
            line-height: 1;
        }

        .filter-checkbox:checked+span {
            font-weight: 500;
            color: #1f2937;
        }

        input.filter-checkbox {
            accent-color: #4f46e5;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        /* Safe truncation that works in all browsers */
        .course-title {
            overflow: hidden;
            word-wrap: break-word;
            word-break: break-word;
            line-height: 1.4;
            max-height: 2.8em;
            display: block;
            position: relative;
        }

        /* responsive adjustments */
        @media (max-width: 640px) {
            .course-stats-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .filter-actions {
                flex-direction: column-reverse;
            }
            .filter-actions button {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            #filterDropdown {
                width: 90vw;
                right: 0;
                left: auto;
                max-width: 320px;
            }
        }

        button, .course-item summary {
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        /* Animation for modals */
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

        /* Alert styles */
        .alert-success {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #166534;
        }
        .alert-error {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>

<body class="bg-gray-50 antialiased">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        
        <!-- HEADER -->
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <!-- PAGE CONTENT -->
        <div class="p-6 md:p-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Course Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and organize all training courses</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div class="alert-success rounded-lg p-4 mb-6 flex items-start gap-3">
                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium"><?php echo nl2br(htmlspecialchars($success_message)); ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert-error rounded-lg p-4 mb-6 flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Search and Filters Bar -->
            <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <!-- Search input -->
                <div class="relative w-full sm:max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-gray-400 text-xl">search</span>
                    </span>
                    <input id="searchInput"
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl bg-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-sm shadow-sm"
                        placeholder="Search courses by title..." type="text"
                        value="<?php echo htmlspecialchars($search_term); ?>" />
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <!-- Status dropdown -->
                    <select id="statusSelect"
                        class="flex-1 sm:flex-none px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
                        <option value="">All statuses</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>

                    <!-- FILTER BUTTON + DROPDOWN -->
                    <div class="relative">
                        <button id="filterBtn"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold bg-white hover:bg-gray-50 shadow-sm transition-all">
                            <span class="material-symbols-outlined text-gray-600 text-lg">filter_alt</span>
                            <span>Filters</span>
                            <span id="filterBadge" class="badge-count hidden">0</span>
                        </button>

                        <!-- Filter dropdown panel -->
                        <div id="filterDropdown"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-30 p-5"
                            style="max-width: calc(100vw - 2rem);">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-2 mb-3">
                                <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-indigo-500 text-base">tune</span>
                                    Refine courses
                                </h4>
                                <button id="closeDropdownBtn" class="text-gray-400 hover:text-gray-700 transition rounded-full p-1">
                                    <span class="material-symbols-outlined text-xl">close</span>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <!-- Category filter -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">category</span> Category
                                    </p>
                                    <div class="grid grid-cols-2 gap-y-2 gap-x-3">
                                        <?php foreach ($categories as $cat): ?>
                                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="filter-checkbox category-filter" value="<?php echo htmlspecialchars($cat); ?>">
                                            <span><?php echo htmlspecialchars($cat); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Difficulty filter -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">signal_cellular_alt</span>
                                        Difficulty
                                    </p>
                                    <div class="grid grid-cols-2 gap-y-2">
                                        <?php foreach ($difficulties as $diff): ?>
                                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="filter-checkbox difficulty-filter" value="<?php echo htmlspecialchars($diff); ?>">
                                            <span><?php echo htmlspecialchars($diff); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Status filter -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">flag</span> Status
                                    </p>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="filter-checkbox status-filter" value="published">
                                            <span>Published</span>
                                        </label>
                                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="filter-checkbox status-filter" value="draft">
                                            <span>Draft</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-actions flex flex-col sm:flex-row justify-between gap-3 mt-6 pt-2 border-t border-gray-100">
                                <button id="clearFiltersBtn" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                                    Clear all
                                </button>
                                <button id="applyFiltersBtn" class="px-5 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-sm transition">
                                    Apply filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- CREATE NEW COURSE BUTTON -->
                    <div>
                        <a href="course-builder/step1.html"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-indigo-600 text-white rounded-xl transition-all duration-200 whitespace-nowrap hover:bg-indigo-700 active:scale-95 shadow-md">
                            <span class="material-symbols-outlined text-lg">add</span>
                            <span>Create course</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Course list container -->
            <div class="space-y-4" id="courseListContainer">
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                    <details class="course-item group bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all"
                        data-category="<?php echo htmlspecialchars($course['category'] ?? ''); ?>"
                        data-status="<?php echo htmlspecialchars($course['status']); ?>"
                        data-difficulty="<?php echo htmlspecialchars($course['difficulty'] ?? ''); ?>"
                        data-course-type="<?php echo htmlspecialchars($course['type'] ?? ''); ?>">
                        <summary class="flex flex-wrap items-center justify-between px-4 sm:px-6 py-4 sm:py-5 cursor-pointer hover:bg-gray-50 transition list-none gap-2">
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="flex items-center gap-3">
                                    <?php if(!empty($course['thumbnail_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($course['thumbnail_url']); ?>" class="w-12 h-12 rounded-lg object-cover">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center">
                                            <i class="fas fa-book-open text-white text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">calendar_today</span>
                                                <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">category</span>
                                                <?php echo htmlspecialchars($course['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 ml-auto">
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?php echo $course['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                                <span class="material-symbols-outlined text-gray-400 chevron-icon">expand_more</span>
                            </div>
                        </summary>
                        <div class="px-4 sm:px-6 pb-5 pt-2 border-t border-gray-100">
                            <div class="course-stats-grid grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Course Type</p>
                                    <p class="text-sm text-gray-700"><?php echo ucfirst($course['type'] ?? 'General'); ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Difficulty</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Modules / Curriculum</p>
                                    <p class="text-sm text-gray-700">
                                        <?php 
                                        // Count modules/lessons from videos table
                                        $module_count = 0;
                                        $module_query = "SELECT COUNT(*) as count FROM videos WHERE course_id = ?";
                                        $module_stmt = $conn->prepare($module_query);
                                        $module_stmt->bind_param("i", $course['id']);
                                        $module_stmt->execute();
                                        $module_result = $module_stmt->get_result();
                                        if ($module_row = $module_result->fetch_assoc()) {
                                            $module_count = $module_row['count'];
                                        }
                                        echo $module_count . ' module(s)';
                                        ?>
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Last Updated</p>
                                    <p class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($course['updated_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2 pt-3 border-t border-gray-50">
                                <a href="preview_course.php?id=<?php echo $course['id']; ?>" class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                    <i class="fas fa-eye mr-1"></i> Preview
                                </a>
                                <button onclick="deleteCourse(<?php echo $course['id']; ?>)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </details>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                        <div class="flex flex-col items-center">
                            <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">school</span>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No courses yet</h3>
                            <p class="text-gray-500 mb-4">Get started by creating your first course</p>
                            <a href="course-builder/step1.html" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Create Course
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if (count($courses) > 0): ?>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-sm text-gray-500">Showing <span id="visibleCount" class="font-medium"><?php echo count($courses); ?></span> of <span id="totalCount" class="font-medium"><?php echo count($courses); ?></span> courses</div>
                <div class="flex gap-2">
                    <button class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 text-sm disabled:opacity-50 hover:bg-gray-50 transition" disabled>Previous</button>
                    <button class="px-3 py-1.5 border border-indigo-600 rounded-lg bg-indigo-50 text-indigo-600 text-sm font-medium">1</button>
                    <button class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 text-sm hover:bg-gray-50">2</button>
                    <button class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 text-sm hover:bg-gray-50">3</button>
                    <button class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 text-sm hover:bg-gray-50">Next</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sidebar toggle handler - sync main content and fixed header
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
        
        window.addEventListener('sidebarToggle', function() {
            setTimeout(updateSidebarState, 10);
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            updateSidebarState();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    if (fixedHeader) fixedHeader.classList.add('sidebar-collapsed');
                } else {
                    updateSidebarState();
                }
            });
        });

        // Filter functionality with URL persistence
        (function () {
            const searchInput = document.getElementById('searchInput');
            const statusSelect = document.getElementById('statusSelect');
            const filterBtn = document.getElementById('filterBtn');
            const filterDropdown = document.getElementById('filterDropdown');
            const closeDropdownBtn = document.getElementById('closeDropdownBtn');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const filterBadge = document.getElementById('filterBadge');
            const visibleCountSpan = document.getElementById('visibleCount');
            const totalCountSpan = document.getElementById('totalCount');
            
            const categoryCheckboxes = () => Array.from(document.querySelectorAll('.category-filter'));
            const difficultyCheckboxes = () => Array.from(document.querySelectorAll('.difficulty-filter'));
            const statusCheckboxes = () => Array.from(document.querySelectorAll('.status-filter'));
            
            let appliedFilters = { categories: [], difficulties: [], statuses: [] };
            
            function updateTotalCourses() {
                const allCourses = document.querySelectorAll('.course-item');
                if (totalCountSpan) totalCountSpan.textContent = allCourses.length;
            }
            
            function filterCoursesAndUpdateUI() {
                const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
                const mainStatusValue = statusSelect ? statusSelect.value : '';
                const courseItems = document.querySelectorAll('.course-item');
                let visibleCount = 0;
                
                courseItems.forEach(course => {
                    const titleElem = course.querySelector('h3');
                    const title = titleElem ? titleElem.textContent.toLowerCase() : '';
                    const category = course.dataset.category || '';
                    const difficulty = course.dataset.difficulty || '';
                    const status = course.dataset.status || '';
                    
                    const matchesSearch = title.includes(searchTerm);
                    const matchesMainStatus = !mainStatusValue || status === mainStatusValue;
                    const matchesCategory = appliedFilters.categories.length === 0 || appliedFilters.categories.includes(category);
                    const matchesDifficulty = appliedFilters.difficulties.length === 0 || appliedFilters.difficulties.includes(difficulty);
                    const matchesStatusFilter = appliedFilters.statuses.length === 0 || appliedFilters.statuses.includes(status);
                    
                    if (matchesSearch && matchesMainStatus && matchesCategory && matchesDifficulty && matchesStatusFilter) {
                        course.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        course.classList.add('hidden');
                    }
                });
                
                if (visibleCountSpan) visibleCountSpan.textContent = visibleCount;
                updateFilterBadge();
            }
            
            function updateFilterBadge() {
                const total = appliedFilters.categories.length + appliedFilters.difficulties.length + appliedFilters.statuses.length;
                if (filterBadge) {
                    if (total > 0) {
                        filterBadge.textContent = total;
                        filterBadge.classList.remove('hidden');
                    } else {
                        filterBadge.classList.add('hidden');
                    }
                }
            }
            
            function syncDropdownWithAppliedFilters() {
                categoryCheckboxes().forEach(cb => cb.checked = appliedFilters.categories.includes(cb.value));
                difficultyCheckboxes().forEach(cb => cb.checked = appliedFilters.difficulties.includes(cb.value));
                statusCheckboxes().forEach(cb => cb.checked = appliedFilters.statuses.includes(cb.value));
            }
            
            function getFiltersFromDropdown() {
                return {
                    categories: categoryCheckboxes().filter(cb => cb.checked).map(cb => cb.value),
                    difficulties: difficultyCheckboxes().filter(cb => cb.checked).map(cb => cb.value),
                    statuses: statusCheckboxes().filter(cb => cb.checked).map(cb => cb.value)
                };
            }
            
            function applyFiltersFromDropdown() {
                const newFilters = getFiltersFromDropdown();
                appliedFilters = { ...newFilters };
                filterCoursesAndUpdateUI();
                closeDropdown();
                
                // Update URL with filters
                updateURLWithFilters();
            }
            
            function updateURLWithFilters() {
                const params = new URLSearchParams();
                if (searchInput.value) params.set('search', searchInput.value);
                if (statusSelect.value) params.set('status', statusSelect.value);
                const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
                window.history.pushState({}, '', newUrl);
            }
            
            function clearAllFilters() {
                categoryCheckboxes().forEach(cb => cb.checked = false);
                difficultyCheckboxes().forEach(cb => cb.checked = false);
                statusCheckboxes().forEach(cb => cb.checked = false);
                appliedFilters = { categories: [], difficulties: [], statuses: [] };
                filterCoursesAndUpdateUI();
            }
            
            function openDropdown() {
                syncDropdownWithAppliedFilters();
                if (filterDropdown) filterDropdown.classList.add('open');
            }
            
            function closeDropdown() {
                if (filterDropdown) filterDropdown.classList.remove('open');
            }
            
            function toggleDropdown() {
                if (filterDropdown && filterDropdown.classList.contains('open')) closeDropdown();
                else openDropdown();
            }
            
            if (filterBtn) filterBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(); });
            if (closeDropdownBtn) closeDropdownBtn.addEventListener('click', () => closeDropdown());
            if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', (e) => { e.preventDefault(); applyFiltersFromDropdown(); });
            if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', (e) => { e.preventDefault(); clearAllFilters(); });
            
            let searchTimeout;
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        filterCoursesAndUpdateUI();
                        updateURLWithFilters();
                    }, 300);
                });
            }
            if (statusSelect) statusSelect.addEventListener('change', () => {
                filterCoursesAndUpdateUI();
                updateURLWithFilters();
            });
            
            document.addEventListener('click', (e) => {
                if (filterDropdown && filterBtn && !filterDropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                    if (filterDropdown.classList.contains('open')) closeDropdown();
                }
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && filterDropdown && filterDropdown.classList.contains('open')) closeDropdown(); });
            if (filterDropdown) filterDropdown.addEventListener('click', (e) => e.stopPropagation());
            
            updateTotalCourses();
            filterCoursesAndUpdateUI();
            syncDropdownWithAppliedFilters();
        })();
        
        function deleteCourse(courseId) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                window.location.href = 'delete_course_confirm.php?id=' + courseId;
            }
        }
    </script>
</body>

</html>