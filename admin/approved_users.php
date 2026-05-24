<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['firstname'] ?? 'Admin';

// ============================================
// FETCH APPROVED USERS ONLY WITH PROFILE PICTURE
// ============================================
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status, created_at, profile_picture FROM users WHERE status = 'approved' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$approved_users = [];
while ($row = $result->fetch_assoc()) {
    // Add profile picture URL
    if (!empty($row['profile_picture'])) {
        $profile_picture_path = __DIR__ . '/../uploads/profile_pictures/' . $row['profile_picture'];
        if (file_exists($profile_picture_path)) {
            $row['profile_picture_url'] = '../uploads/profile_pictures/' . $row['profile_picture'];
        } else {
            $row['profile_picture_url'] = null;
        }
    } else {
        $row['profile_picture_url'] = null;
    }
    $approved_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approved Users - Upstaff</title>
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
    
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
        }
        .fixed-header {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
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
    
    /* Card View Styles */
    .user-card {
        transition: all 0.3s ease;
    }
    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
    }
    
    /* View Toggle Buttons */
    .view-toggle-btn {
        transition: all 0.2s ease;
    }
    .view-toggle-btn.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    /* Dropdown menu styling */
    .dropdown {
        position: relative;
        display: inline-block;
    }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #fff;
        min-width: 160px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px;
        z-index: 50;
        margin-top: 0.5rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .dropdown-content a,
    .dropdown-content button {
        color: #374151;
        padding: 8px 16px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        transition: background 0.2s;
    }
    .dropdown-content a:hover,
    .dropdown-content button:hover {
        background-color: #f3f4f6;
    }
    .dropdown-content button.delete-btn {
        color: #dc2626;
    }
    .dropdown-content button.delete-btn:hover {
        background-color: #fee2e2;
    }
    .dropdown.open .dropdown-content {
        display: block;
    }
    
    /* Loading spinner */
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        border-top-color: #3498db;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Hide scrollbar for card view */
    .cards-container::-webkit-scrollbar {
        width: 6px;
    }
    .cards-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .cards-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    
    /* Animation for view switching */
    .table-view, .cards-view {
        transition: opacity 0.3s ease;
    }
    .hidden-view {
        display: none;
    }
</style>
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        
        <!-- HEADER -->
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <!-- PAGE CONTENT -->
        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Approved Users</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage and view all approved users</p>
                </div>
                
                <!-- View Toggle Buttons -->
                <div class="flex gap-2 bg-white rounded-lg shadow-sm border border-gray-200 p-1">
                    <button id="tableViewBtn" class="view-toggle-btn active px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 bg-green-500 text-white">
                        <i class="fas fa-table"></i> Table View
                    </button>
                    <button id="cardViewBtn" class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-th-large"></i> Card View
                    </button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or position..." 
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                </div>
            </div>
            
            <!-- ========== TABLE VIEW ========== -->
            <div id="tableView" class="table-view">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Users List
                            </h2>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full" id="totalCount">
                                <?php echo count($approved_users); ?> total
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-700" id="usersTable">
                            <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">ID</th>
                                    <th class="px-6 py-4 font-semibold">User</th>
                                    <th class="px-6 py-4 font-semibold">Email</th>
                                    <th class="px-6 py-4 font-semibold">Position</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white" id="usersTableBody">
                                <?php foreach ($approved_users as $user): ?>
                                <tr class="hover:bg-green-50 transition-colors duration-150 user-row" data-name="<?php echo strtolower($user['firstname'] . ' ' . $user['lastname']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-position="<?php echo strtolower($user['position'] ?? ''); ?>">
                                    <td class="px-6 py-4 font-medium text-gray-900">#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm overflow-hidden">
                                                <?php if ($user['profile_picture_url']): ?>
                                                    <img src="<?php echo $user['profile_picture_url']; ?>?t=<?php echo time(); ?>" class="w-full h-full object-cover" alt="Profile">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['position'] ?? 'Not specified'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-green-100 text-green-800 border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Approved
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="dropdown relative inline-block">
                                            <button type="button" class="dropdown-toggle px-2 py-1 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                </svg>
                                            </button>
                                            <div class="dropdown-content hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                                <button type="button" class="edit-user-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit text-gray-500"></i> Edit Info
                                                </button>
                                                <button type="button" class="view-logs-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-history text-gray-500"></i> View Logs
                                                </button>
                                                <button type="button" class="delete-user-btn flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash-alt text-red-500"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- ========== CARD VIEW ========== -->
            <div id="cardView" class="cards-view hidden-view">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="cardsContainer">
                    <?php foreach ($approved_users as $user): ?>
                    <div class="user-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all border border-gray-100" data-name="<?php echo strtolower($user['firstname'] . ' ' . $user['lastname']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-position="<?php echo strtolower($user['position'] ?? ''); ?>">
                        <!-- Card Header with Gradient -->
                        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 relative">
                            <div class="absolute -bottom-8 left-6">
                                <div class="w-16 h-16 rounded-full bg-white shadow-md border-4 border-white overflow-hidden">
                                    <?php if ($user['profile_picture_url']): ?>
                                        <img src="<?php echo $user['profile_picture_url']; ?>?t=<?php echo time(); ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold text-2xl">
                                            <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <div class="dropdown relative inline-block">
                                    <button type="button" class="dropdown-toggle text-white/80 hover:text-white transition p-1">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    <div class="dropdown-content hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                        <button type="button" class="edit-user-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit text-gray-500"></i> Edit Info
                                        </button>
                                        <button type="button" class="view-logs-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-history text-gray-500"></i> View Logs
                                        </button>
                                        <button type="button" class="delete-user-btn flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash-alt text-red-500"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="pt-10 pb-5 px-6">
                            <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                            <p class="text-sm text-gray-500 mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                            
                            <div class="space-y-2 mt-4">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="fas fa-envelope text-gray-400 w-4"></i>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="fas fa-briefcase text-gray-400 w-4"></i>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($user['position'] ?? 'Not specified'); ?></span>
                                </div>
                                <?php if (!empty($user['phone'])): ?>
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="fas fa-phone text-gray-400 w-4"></i>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($user['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-gray-100">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Approved
                                </span>
                                <span class="text-xs text-gray-400 ml-2">ID: #<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <div class="flex flex-col items-center">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No users found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your search</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FOR VIEWING LOGS -->
    <div id="logsModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl modal-animate-in">
                    <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-history"></i> User Activity Logs
                        </h3>
                        <button onclick="closeLogsModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <div id="logsContent" class="space-y-2">
                            <p class="text-gray-500 text-center">Loading logs...</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 px-6 py-4 flex justify-end">
                        <button type="button" onclick="closeLogsModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FOR EDITING USER -->
    <div id="editModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-3xl modal-animate-in">
                    <div class="relative bg-gradient-to-r from-green-600 to-green-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-user-edit"></i> Edit User
                        </h3>
                        <button onclick="closeEditModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <form id="editUserForm">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="firstname" id="edit_firstname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="lastname" id="edit_lastname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" id="edit_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="dob" id="edit_dob" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                                    <input type="text" name="position" id="edit_position" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <select name="role" id="edit_role" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="employee">Employee</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="approved">Approved</option>
                                        <option value="pending">Pending</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea name="address" id="edit_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">Cancel</button>
                                <button type="submit" id="editSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Save Changes</button>
                            </div>
                            <div id="editMessage" class="mt-3 text-sm hidden"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ========== SIDEBAR TOGGLE SYNC ==========
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
        
        // ========== VIEW TOGGLE ==========
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        
        // Load saved preference from localStorage
        const savedView = localStorage.getItem('userViewPreference') || 'table';
        
        function setView(view) {
            if (view === 'table') {
                tableView.classList.remove('hidden-view');
                cardView.classList.add('hidden-view');
                tableViewBtn.classList.add('active', 'bg-green-500', 'text-white');
                tableViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                cardViewBtn.classList.remove('active', 'bg-green-500', 'text-white');
                cardViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                localStorage.setItem('userViewPreference', 'table');
            } else {
                tableView.classList.add('hidden-view');
                cardView.classList.remove('hidden-view');
                cardViewBtn.classList.add('active', 'bg-green-500', 'text-white');
                cardViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                tableViewBtn.classList.remove('active', 'bg-green-500', 'text-white');
                tableViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                localStorage.setItem('userViewPreference', 'card');
            }
        }
        
        tableViewBtn.addEventListener('click', () => setView('table'));
        cardViewBtn.addEventListener('click', () => setView('card'));
        
        // Apply saved preference
        setView(savedView);
        
        // ========== SEARCH FUNCTIONALITY ==========
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#usersTableBody .user-row');
        const cardItems = document.querySelectorAll('#cardsContainer .user-card');
        const emptyState = document.getElementById('emptyState');
        const totalCountSpan = document.getElementById('totalCount');
        
        function updateTotalCount(visibleCount) {
            if (totalCountSpan) {
                totalCountSpan.textContent = visibleCount + ' total';
            }
        }
        
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase();
            let visibleTableCount = 0;
            let visibleCardCount = 0;
            
            // Filter table rows
            tableRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const position = row.getAttribute('data-position') || '';
                const matches = name.includes(searchTerm) || email.includes(searchTerm) || position.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
                if (matches) visibleTableCount++;
            });
            
            // Filter card items
            cardItems.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const email = card.getAttribute('data-email') || '';
                const position = card.getAttribute('data-position') || '';
                const matches = name.includes(searchTerm) || email.includes(searchTerm) || position.includes(searchTerm);
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCardCount++;
            });
            
            // Show/hide empty state based on current view
            const isTableView = !tableView.classList.contains('hidden-view');
            const visibleCount = isTableView ? visibleTableCount : visibleCardCount;
            
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
                if (isTableView) {
                    tableView.classList.add('hidden');
                } else {
                    cardView.classList.add('hidden');
                }
            } else {
                emptyState.classList.add('hidden');
                if (isTableView) {
                    tableView.classList.remove('hidden');
                } else {
                    cardView.classList.remove('hidden');
                }
            }
            
            updateTotalCount(visibleCount);
        }
        
        searchInput.addEventListener('input', performSearch);
        
        // ========== DROPDOWN HANDLING ==========
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const toggleBtn = dropdown.querySelector('.dropdown-toggle');
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open');
            });
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
            }
        });
        
        // ========== EDIT MODAL ==========
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editUserForm');
        const editMessage = document.getElementById('editMessage');
        const editSubmitBtn = document.getElementById('editSubmitBtn');
        
        async function openEditModal(userId) {
            editForm.reset();
            editMessage.classList.add('hidden');
            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<span class="spinner"></span> Loading...';
            
            try {
                const response = await fetch(`get_user.php?id=${userId}`);
                const data = await response.json();
                if (data.success) {
                    const user = data.user;
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_firstname').value = user.firstname;
                    document.getElementById('edit_lastname').value = user.lastname;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_phone').value = user.phone || '';
                    document.getElementById('edit_dob').value = user.dob || '';
                    document.getElementById('edit_position').value = user.position || '';
                    document.getElementById('edit_role').value = user.role || 'employee';
                    document.getElementById('edit_status').value = user.status || 'approved';
                    document.getElementById('edit_address').value = user.address || '';
                    editModal.classList.remove('hidden');
                } else {
                    alert('Failed to load user data: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Error loading user data');
            } finally {
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = 'Save Changes';
            }
        }
        
        function closeEditModal() {
            editModal.classList.add('hidden');
        }
        
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData.entries());
            
            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<span class="spinner"></span> Saving...';
            editMessage.classList.add('hidden');
            
            try {
                const response = await fetch('update_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    editMessage.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">User updated successfully. Refreshing...</div>';
                    editMessage.classList.remove('hidden');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    editMessage.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">${result.message || 'Update failed'}</div>`;
                    editMessage.classList.remove('hidden');
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = 'Save Changes';
                }
            } catch (err) {
                editMessage.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Network error. Please try again.</div>';
                editMessage.classList.remove('hidden');
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = 'Save Changes';
            }
        });
        
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-user-btn');
            if (editBtn) {
                e.preventDefault();
                const userId = editBtn.getAttribute('data-user-id');
                openEditModal(userId);
                editBtn.closest('.dropdown')?.classList.remove('open');
            }
        });
        
        // ========== VIEW LOGS ==========
        const logsModal = document.getElementById('logsModal');
        const logsContent = document.getElementById('logsContent');
        
        function openLogsModal(userId) {
            logsContent.innerHTML = '<p class="text-gray-500 text-center">Loading logs...</p>';
            logsModal.classList.remove('hidden');
            
            fetch(`get_user_logs.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.logs.length > 0) {
                        let html = '<ul class="divide-y divide-gray-200">';
                        data.logs.forEach(log => {
                            html += `<li class="py-3">
                                        <div class="text-sm text-gray-900">${escapeHtml(log.action)}</div>
                                        <div class="text-xs text-gray-500">${escapeHtml(log.timestamp)}</div>
                                    </li>`;
                        });
                        html += '</ul>';
                        logsContent.innerHTML = html;
                    } else {
                        logsContent.innerHTML = '<p class="text-gray-500 text-center">No logs found for this user.</p>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    logsContent.innerHTML = '<p class="text-red-500 text-center">Failed to load logs. Please try again.</p>';
                });
        }
        
        function closeLogsModal() {
            logsModal.classList.add('hidden');
        }
        
        document.addEventListener('click', function(e) {
            const logsBtn = e.target.closest('.view-logs-btn');
            if (logsBtn) {
                e.preventDefault();
                const userId = logsBtn.getAttribute('data-user-id');
                openLogsModal(userId);
                logsBtn.closest('.dropdown')?.classList.remove('open');
            }
        });
        
        // ========== DELETE USER ==========
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-user-btn');
            if (deleteBtn) {
                e.preventDefault();
                const userId = deleteBtn.getAttribute('data-user-id');
                
                if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('User deleted successfully.');
                            location.reload();
                        } else {
                            alert('Error deleting user: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Failed to delete user.');
                    });
                }
                deleteBtn.closest('.dropdown')?.classList.remove('open');
            }
        });
        
        // ========== CLOSE MODALS ON ESCAPE ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogsModal();
                closeEditModal();
            }
        });
        
        logsModal.addEventListener('click', function(e) {
            if (e.target === logsModal) closeLogsModal();
        });
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) closeEditModal();
        });
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>