<?php
// Error reporting - turn off HTML errors for AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    error_reporting(0); // Suppress errors for AJAX requests
    ini_set('display_errors', 0);
}

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: ../login/index.html");
    exit();
}

$user_name = $_SESSION['firstname'] ?? 'Admin';

// Define profile picture upload directory
define('PROFILE_UPLOAD_PATH', '../uploads/profile_pictures/');

// Handle Ajax requests for actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $request_id = $_POST['request_id'] ?? 0;
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    if ($action === 'approve' && $request_id) {
        $query = "UPDATE retake_requests SET status = 'approved', admin_notes = ?, processed_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $admin_notes, $request_id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Request approved successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    } 
    elseif ($action === 'reject' && $request_id) {
        $query = "UPDATE retake_requests SET status = 'rejected', admin_notes = ?, processed_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $admin_notes, $request_id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Request rejected and archived'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    }
    elseif ($action === 'restore' && $request_id) {
        $query = "UPDATE retake_requests SET status = 'pending', admin_notes = NULL, processed_at = NULL WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $request_id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Request restored to pending'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    }
    
    echo json_encode($response);
    exit();
}

// Handle search and filtering
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get highlight ID from URL (for notification click)
$highlight_id = isset($_GET['highlight']) ? (int)$_GET['highlight'] : 0;
$auto_view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Fetch pending requests with user and course details - Include profile_picture column
$pending_query = "SELECT r.*, u.firstname, u.lastname, u.username, u.email, u.profile_picture,
                  c.title as course_title, c.thumbnail_url
                  FROM retake_requests r
                  JOIN users u ON r.user_id = u.id
                  JOIN courses c ON r.course_id = c.id
                  WHERE r.status = 'pending'";
if (!empty($search_term)) {
    $pending_query .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR c.title LIKE ?)";
}
$pending_query .= " ORDER BY r.requested_at DESC";

$pending_stmt = $conn->prepare($pending_query);
if (!empty($search_term)) {
    $search_param = "%$search_term%";
    $pending_stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$pending_stmt->execute();
$pending_requests = $pending_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch rejected/archived requests - Include profile_picture column
$archived_query = "SELECT r.*, u.firstname, u.lastname, u.username, u.email, u.profile_picture,
                   c.title as course_title, c.thumbnail_url
                   FROM retake_requests r
                   JOIN users u ON r.user_id = u.id
                   JOIN courses c ON r.course_id = c.id
                   WHERE r.status = 'rejected'";
if (!empty($search_term)) {
    $archived_query .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR c.title LIKE ?)";
}
$archived_query .= " ORDER BY r.processed_at DESC";

$archived_stmt = $conn->prepare($archived_query);
if (!empty($search_term)) {
    $archived_stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$archived_stmt->execute();
$archived_requests = $archived_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function to get profile picture URL
function getProfilePictureUrl($profile_picture) {
    if (empty($profile_picture)) {
        return null;
    }
    
    // Check if the file exists in the uploads directory
    $upload_path = '../uploads/profile_pictures/' . $profile_picture;
    if (file_exists($upload_path)) {
        return '../uploads/profile_pictures/' . $profile_picture;
    }
    
    // Check alternative upload paths
    $alt_paths = [
        '../uploads/' . $profile_picture,
        '../assets/images/profile/' . $profile_picture,
        './uploads/profile_pictures/' . $profile_picture,
        './uploads/' . $profile_picture
    ];
    
    foreach ($alt_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

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
    <title>Retake Requests | UpStaff Admin</title>
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
        
        /* Fixed Header Styles */
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
        
        /* Modal transitions */
        .modal-transition {
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        
        /* Tab styles */
        .tab-active {
            background-color: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        .tab-active i {
            color: white;
        }
        
        .tab-inactive {
            background-color: white;
            color: #4b5563;
            border-color: #e5e7eb;
        }
        
        .tab-inactive:hover {
            background-color: #f9fafb;
        }
        
        /* Table styles */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }
        
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
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
        
        /* Loading spinner */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toast notification */
        .toast-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Highlight animation for notification click */
        @keyframes highlightPulse {
            0% { 
                background-color: #fef3c7;
                transform: scale(1);
            }
            50% { 
                background-color: #fde68a;
                box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.5);
                transform: scale(1.01);
            }
            100% { 
                background-color: transparent;
                transform: scale(1);
            }
        }
        
        .highlight-row {
            animation: highlightPulse 2s ease-out;
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
                <h1 class="text-2xl font-bold text-gray-900">Retake Requests</h1>
                <p class="text-sm text-gray-500 mt-1">Review employee retake requests and manage archived rejections</p>
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
            
            <!-- Tabs: Pending Requests / Archives Request -->
            <div class="flex gap-3 mb-6 border-b border-gray-200 pb-2">
                <button id="pendingTabBtn"
                    class="tab-active inline-flex items-center gap-2 px-5 py-2.5 rounded-t-lg text-sm font-medium transition-all duration-150">
                    <i class="fas fa-clock text-sm"></i> Pending Requests
                    <span id="pendingTabBadge" class="ml-1 bg-white/20 px-1.5 py-0.5 rounded-full text-xs"><?php echo count($pending_requests); ?></span>
                </button>
                <button id="archivedTabBtn"
                    class="tab-inactive inline-flex items-center gap-2 px-5 py-2.5 rounded-t-lg text-sm font-medium transition-all duration-150">
                    <i class="fas fa-archive text-sm"></i> Archives Request
                    <span id="archivedTabBadge" class="ml-1 bg-gray-200 px-1.5 py-0.5 rounded-full text-xs"><?php echo count($archived_requests); ?></span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="mb-6">
                <form method="GET" action="" id="searchForm">
                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-gray-400 text-xl">search</span>
                        </div>
                        <input type="text" name="search" id="searchInput" 
                            placeholder="Search by employee name or course title..."
                            value="<?php echo htmlspecialchars($search_term); ?>"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-sm shadow-sm">
                    </div>
                </form>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="hidden fixed inset-0 bg-black/20 z-50 flex items-center justify-center">
                <div class="loading-spinner"></div>
            </div>

            <!-- Pending Requests Table Container -->
            <div id="pendingTableContainer">
                <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-hourglass-half text-amber-500"></i>
                                Pending Retake Requests
                            </h2>
                            <span id="pendingCountBadge"
                                class="bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($pending_requests); ?></span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[900px] w-full text-sm text-left text-gray-700">
                            <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Course Title</th>
                                    <th class="px-6 py-4">Request Date</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingTableBody">
                                <?php if (count($pending_requests) > 0): ?>
                                    <?php foreach ($pending_requests as $request): ?>
                                    <?php 
                                    $profile_pic_url = getProfilePictureUrl($request['profile_picture'] ?? '');
                                    $is_highlight = ($highlight_id == $request['id'] || $auto_view_id == $request['id']);
                                    ?>
                                    <tr class="hover:bg-indigo-50 transition-colors duration-150 <?php echo $is_highlight ? 'highlight-row' : ''; ?>" 
                                        data-request-id="<?php echo $request['id']; ?>">
                                        <td class="px-6 py-4 font-medium">#<?php echo $request['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden shadow-sm flex-shrink-0">
                                                    <?php if ($profile_pic_url): ?>
                                                        <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" class="w-full h-full object-cover" alt="Profile">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-600 text-white font-bold text-base">
                                                            <?php echo strtoupper(substr($request['firstname'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></div>
                                                    <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($request['username']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($request['course_title']); ?></div>
                                        <td class="px-6 py-4"><?php echo date('M d, Y h:i A', strtotime($request['requested_at'])); ?></div>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-amber-100 text-amber-800 border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                            </span>
                                        </div>
                                        <td class="px-6 py-4 text-center">
                                            <button class="view-request-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm px-4 py-2 rounded-lg flex items-center gap-2 mx-auto transition" 
                                                    data-id="<?php echo $request['id']; ?>" 
                                                    data-tab="pending">
                                                <i class="fas fa-eye"></i> View Request
                                            </button>
                                        </div>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-500">
                                            <i class="fas fa-inbox mr-2"></i> No pending requests
                                        </div>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Archived Requests Table Container -->
            <div id="archivedTableContainer" class="hidden">
                <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-archive text-gray-500"></i>
                                Archived Retake Requests (Rejected)
                            </h2>
                            <span id="archivedCountBadge"
                                class="bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($archived_requests); ?></span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[1000px] w-full text-sm text-left text-gray-700">
                            <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Course Title</th>
                                    <th class="px-6 py-4">Request Date</th>
                                    <th class="px-6 py-4">Processed Date</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="archivedTableBody">
                                <?php if (count($archived_requests) > 0): ?>
                                    <?php foreach ($archived_requests as $request): ?>
                                    <?php 
                                    $profile_pic_url = getProfilePictureUrl($request['profile_picture'] ?? '');
                                    ?>
                                    <tr class="archived-row-no-hover" data-request-id="<?php echo $request['id']; ?>">
                                        <td class="px-6 py-4 font-medium">#<?php echo $request['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden shadow-sm flex-shrink-0">
                                                    <?php if ($profile_pic_url): ?>
                                                        <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" class="w-full h-full object-cover" alt="Profile">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-500 to-gray-600 text-white font-bold text-base">
                                                            <?php echo strtoupper(substr($request['firstname'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></div>
                                                    <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($request['username']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($request['course_title']); ?></div>
                                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></div>
                                        <td class="px-6 py-4"><?php echo $request['processed_at'] ? date('M d, Y', strtotime($request['processed_at'])) : 'N/A'; ?></div>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-red-100 text-red-700 border-red-200">
                                                <i class="fas fa-times-circle text-xs"></i> Rejected
                                            </span>
                                        </div>
                                        <td class="px-6 py-4 text-center">
                                            <button class="view-request-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg flex items-center gap-2 mx-auto transition" 
                                                    data-id="<?php echo $request['id']; ?>" 
                                                    data-tab="archived">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </div>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-12 text-gray-500">
                                            <i class="fas fa-archive mr-2"></i> No archived requests
                                        </div>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: View Request Details -->
    <div id="requestModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-transition invisible opacity-0"
        style="background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-auto">
            <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-indigo-500"></i> Retake Request Details
                </h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div id="modalContent"></div>
                <div id="modalActionButtons" class="border-t border-gray-100 pt-4 flex flex-col sm:flex-row justify-end gap-3 mt-2"></div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle handler
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
            
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    if (fixedHeader) fixedHeader.classList.add('sidebar-collapsed');
                } else {
                    updateSidebarState();
                }
            });
            
            // AUTO-OPEN MODAL FROM NOTIFICATION CLICK
            const autoViewId = <?php echo $auto_view_id ?: 0; ?>;
            const highlightId = <?php echo $highlight_id ?: 0; ?>;
            const requestToView = autoViewId || highlightId;
            
            if (requestToView) {
                // Scroll to the highlighted row
                const targetRow = document.querySelector(`tr[data-request-id="${requestToView}"]`);
                if (targetRow) {
                    setTimeout(() => {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
                
                // Auto-open the modal after a short delay
                setTimeout(() => {
                    // Check if request is pending or archived
                    let tab = 'pending';
                    const archivedRow = document.querySelector(`#archivedTableBody tr[data-request-id="${requestToView}"]`);
                    if (archivedRow) {
                        tab = 'archived';
                        setActiveTab('archived');
                    } else {
                        setActiveTab('pending');
                    }
                    
                    viewRequestDetails(requestToView, tab);
                }, 500);
            }
        });

        // Tab switching
        const pendingContainer = document.getElementById("pendingTableContainer");
        const archivedContainer = document.getElementById("archivedTableContainer");
        const pendingTabBtn = document.getElementById("pendingTabBtn");
        const archivedTabBtn = document.getElementById("archivedTabBtn");
        
        function setActiveTab(tab) {
            if (tab === "pending") {
                pendingContainer.classList.remove("hidden");
                archivedContainer.classList.add("hidden");
                pendingTabBtn.classList.add("tab-active", "bg-indigo-600", "text-white");
                pendingTabBtn.classList.remove("tab-inactive", "bg-white", "text-gray-700");
                archivedTabBtn.classList.add("tab-inactive", "bg-white", "text-gray-700", "border", "border-gray-200");
                archivedTabBtn.classList.remove("tab-active", "bg-indigo-600", "text-white");
            } else {
                pendingContainer.classList.add("hidden");
                archivedContainer.classList.remove("hidden");
                archivedTabBtn.classList.add("tab-active", "bg-indigo-600", "text-white");
                archivedTabBtn.classList.remove("tab-inactive", "bg-white", "text-gray-700");
                pendingTabBtn.classList.add("tab-inactive", "bg-white", "text-gray-700", "border", "border-gray-200");
                pendingTabBtn.classList.remove("tab-active", "bg-indigo-600", "text-white");
            }
        }
        
        pendingTabBtn.addEventListener("click", () => setActiveTab("pending"));
        archivedTabBtn.addEventListener("click", () => setActiveTab("archived"));
        
        // Auto-submit search on input
        const searchInput = document.getElementById("searchInput");
        let searchTimeout;
        searchInput.addEventListener("input", function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById("searchForm").submit();
            }, 500);
        });
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification px-4 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white text-sm`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Show loading spinner
        function showLoading() {
            document.getElementById("loadingSpinner").classList.remove("hidden");
        }
        
        function hideLoading() {
            document.getElementById("loadingSpinner").classList.add("hidden");
        }
        
        // Modal handling
        const modal = document.getElementById("requestModal");
        const modalContentDiv = document.getElementById("modalContent");
        const modalActionDiv = document.getElementById("modalActionButtons");
        const closeModalBtn = document.getElementById("closeModalBtn");
        
        let currentRequestId = null;
        
        async function viewRequestDetails(requestId, tab) {
            showLoading();
            try {
                const response = await fetch(`get_request_details.php?id=${requestId}`);
                const data = await response.json();
                
                if (data.success) {
                    currentRequestId = requestId;
                    const request = data.request;
                    
                    const fullName = `${request.firstname} ${request.lastname}`;
                    const requestDate = new Date(request.requested_at).toLocaleDateString();
                    const requestTime = new Date(request.requested_at).toLocaleTimeString();
                    const reasonText = request.reason || "No specific reason provided.";
                    
                    modalContentDiv.innerHTML = `
                        <div class="space-y-4">
                            <div class="flex items-start gap-3 pb-2 border-b border-gray-100">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                                    ${request.firstname.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">${escapeHtml(fullName)}</p>
                                    <p class="text-xs text-gray-500">@${escapeHtml(request.username)} · ${escapeHtml(request.email)}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="bg-gray-50 p-2 rounded-lg">
                                    <span class="text-xs text-gray-500">Course Title</span><br>
                                    <span class="font-medium">${escapeHtml(request.course_title)}</span>
                                </div>
                                <div class="bg-gray-50 p-2 rounded-lg">
                                    <span class="text-xs text-gray-500">Request Date & Time</span><br>
                                    <span class="font-medium">${requestDate} at ${requestTime}</span>
                                </div>
                            </div>
                            <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-comment-dots text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-semibold text-amber-800">Reason for Retake</p>
                                        <p class="text-gray-700 text-sm mt-1">${escapeHtml(reasonText)}</p>
                                    </div>
                                </div>
                            </div>
                            ${request.admin_notes ? `
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-sticky-note text-gray-500 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-600">Admin Notes</p>
                                        <p class="text-gray-700 text-sm mt-1">${escapeHtml(request.admin_notes)}</p>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            ${tab === 'pending' ? `
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                                <textarea id="adminNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Add notes about this decision..."></textarea>
                            </div>
                            ` : ''}
                        </div>
                    `;
                    
                    if (tab === 'pending') {
                        modalActionDiv.innerHTML = `
                            <button id="modalApproveBtn" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                                <i class="fas fa-check-circle"></i> Approve Retake
                            </button>
                            <button id="modalRejectBtn" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                                <i class="fas fa-times-circle"></i> Reject Request
                            </button>
                        `;
                        document.getElementById("modalApproveBtn")?.addEventListener("click", () => processRequest('approve'));
                        document.getElementById("modalRejectBtn")?.addEventListener("click", () => processRequest('reject'));
                    } else {
                        modalActionDiv.innerHTML = `
                            <button id="modalRestoreBtn" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                <i class="fas fa-undo-alt"></i> Restore to Pending
                            </button>
                            <button id="modalCloseOnlyBtn" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition">
                                Close
                            </button>
                        `;
                        document.getElementById("modalRestoreBtn")?.addEventListener("click", () => restoreRequest());
                        document.getElementById("modalCloseOnlyBtn")?.addEventListener("click", closeModal);
                    }
                    
                    modal.classList.remove("invisible", "opacity-0");
                    modal.classList.add("visible", "opacity-100");
                    document.body.style.overflow = "hidden";
                } else {
                    showToast(data.message || 'Error loading request details', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error loading request details', 'error');
            } finally {
                hideLoading();
            }
        }
        
        async function processRequest(action) {
            const adminNotes = document.getElementById("adminNotes")?.value || '';
            
            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('request_id', currentRequestId);
                formData.append('admin_notes', adminNotes);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error processing request', 'error');
            } finally {
                hideLoading();
                closeModal();
            }
        }
        
        async function restoreRequest() {
            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('request_id', currentRequestId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error restoring request', 'error');
            } finally {
                hideLoading();
                closeModal();
            }
        }
        
        function closeModal() {
            modal.classList.add("invisible", "opacity-0");
            modal.classList.remove("visible", "opacity-100");
            document.body.style.overflow = "";
            currentRequestId = null;
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
        }
        
        // Attach view button listeners
        document.querySelectorAll('.view-request-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const requestId = btn.dataset.id;
                const tab = btn.dataset.tab;
                viewRequestDetails(requestId, tab);
            });
        });
        
        closeModalBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
        document.addEventListener("keydown", (e) => { if (e.key === "Escape" && modal.classList.contains("visible")) closeModal(); });
        
        // Store initial tab state from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab');
        if (initialTab === 'archived') {
            setActiveTab('archived');
        }
    </script>
</body>

</html>