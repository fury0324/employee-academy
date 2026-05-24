<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================
// CHECK AND ADD MISSING COLUMNS AUTOMATICALLY
// ============================================
function addMissingColumns($conn) {
    $columns_to_check = [
        'lastname' => "ALTER TABLE users ADD COLUMN lastname VARCHAR(100) NULL AFTER firstname",
        'position' => "ALTER TABLE users ADD COLUMN position VARCHAR(100) NULL AFTER dob",
        'employee_id' => "ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL AFTER position",
        'profile_picture' => "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL"
    ];
    
    $result = $conn->query("SHOW COLUMNS FROM users");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($columns_to_check as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $conn->query($sql);
            if ($column == 'lastname') {
                $conn->query("UPDATE users SET lastname = SUBSTRING_INDEX(firstname, ' ', -1), firstname = SUBSTRING_INDEX(firstname, ' ', 1) WHERE INSTR(firstname, ' ') > 0 AND lastname IS NULL");
            }
            if ($column == 'employee_id') {
                $conn->query("UPDATE users SET employee_id = CONCAT('EMP-', LPAD(id, 4, '0')) WHERE employee_id IS NULL");
            }
        }
    }
}

addMissingColumns($conn);

// ============================================
// FETCH PENDING USERS WITH PROFILE PICTURE
// ============================================
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status, profile_picture FROM users WHERE status = 'pending' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$users = [];
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
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Approvals - Upstaff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
<style>
    * {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    /* MAIN CONTENT OFFSET */
    .main-content {
        margin-left: 16rem;
        width: calc(100% - 16rem);
        min-height: 100vh;
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    .main-content.sidebar-collapsed {
        margin-left: 5rem;
        width: calc(100% - 5rem);
    }
    
    @media (max-width: 768px) {
        .main-content {
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
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
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
    .dropdown-content button:hover {
        background-color: #f3f4f6;
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
    
    /* Animation for view switching */
    .table-view, .cards-view {
        transition: opacity 0.3s ease;
    }
    .hidden-view {
        display: none;
    }
    
    /* Toast notification */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-size: 14px;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
    
    .toast-success { background: #10b981; }
    .toast-error { background: #ef4444; }
    .toast-info { background: #3b82f6; }
</style>
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="p-6 overflow-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Pending Approvals</h1>
                    <p class="text-sm text-gray-500 mt-1">Review and manage pending user registrations</p>
                </div>
                
                <!-- View Toggle Buttons -->
                <div class="flex gap-2 bg-white rounded-lg shadow-sm border border-gray-200 p-1">
                    <button id="tableViewBtn" class="view-toggle-btn active px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 bg-blue-500 text-white">
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
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
            </div>
            
            <!-- ========== TABLE VIEW ========== -->
            <div id="tableView" class="table-view">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Pending Users List
                            </h2>
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full" id="totalCount">
                                <?php echo count($users); ?> pending
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
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-yellow-50 transition-colors duration-150 user-row" data-name="<?php echo strtolower($user['firstname'] . ' ' . $user['lastname']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-position="<?php echo strtolower($user['position'] ?? ''); ?>">
                                    <td class="px-6 py-4 font-medium text-gray-900">#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm overflow-hidden">
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
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-yellow-100 text-yellow-800 border-yellow-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                            Pending
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
                                                <button type="button" class="view-user-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-eye text-blue-500"></i> View Details
                                                </button>
                                                <button type="button" class="approve-user-btn flex items-center gap-2 px-4 py-2 text-sm text-green-700 hover:bg-green-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-check-circle text-green-500"></i> Approve
                                                </button>
                                                <button type="button" class="reject-user-btn flex items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-times-circle text-red-500"></i> Reject
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
                    <?php foreach ($users as $user): ?>
                    <div class="user-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all border border-gray-100" data-name="<?php echo strtolower($user['firstname'] . ' ' . $user['lastname']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-position="<?php echo strtolower($user['position'] ?? ''); ?>">
                        <!-- Card Header with Gradient -->
                        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4 relative">
                            <div class="absolute -bottom-8 left-6">
                                <div class="w-16 h-16 rounded-full bg-white shadow-md border-4 border-white overflow-hidden">
                                    <?php if ($user['profile_picture_url']): ?>
                                        <img src="<?php echo $user['profile_picture_url']; ?>?t=<?php echo time(); ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-white font-bold text-2xl">
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
                                        <button type="button" class="view-user-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye text-blue-500"></i> View Details
                                        </button>
                                        <button type="button" class="approve-user-btn flex items-center gap-2 px-4 py-2 text-sm text-green-700 hover:bg-green-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-check-circle text-green-500"></i> Approve
                                        </button>
                                        <button type="button" class="reject-user-btn flex items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-times-circle text-red-500"></i> Reject
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
                                    <span class="text-gray-600 truncate"><?php echo htmlspecialchars($user['email']); ?></span>
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
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                    Pending
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No pending users</p>
                    <p class="text-gray-400 text-sm mt-1">All users have been processed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FOR VIEWING USER DETAILS -->
    <div id="viewModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl modal-animate-in">
                    <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-user-circle"></i> User Details
                        </h3>
                        <button onclick="closeViewModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</label>
                                <p id="view_firstname" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</label>
                                <p id="view_lastname" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Username</label>
                                <p id="view_username" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Position</label>
                                <p id="view_position" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</label>
                                <p id="view_email" class="text-gray-900 font-medium mt-1 break-all"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</label>
                                <p id="view_phone" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</label>
                                <p id="view_dob" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</label>
                                <p id="view_employee_id" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Role</label>
                                <p id="view_role" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</label>
                                <p id="view_address" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-100 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="rejectFromModal()" id="modalRejectBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Reject</button>
                        <button type="button" onclick="approveFromModal()" id="modalApproveBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ========== VIEW TOGGLE ==========
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        
        const savedView = localStorage.getItem('adminApprovalView') || 'table';
        
        function setView(view) {
            if (view === 'table') {
                tableView.classList.remove('hidden-view');
                cardView.classList.add('hidden-view');
                tableViewBtn.classList.add('active', 'bg-blue-500', 'text-white');
                tableViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                cardViewBtn.classList.remove('active', 'bg-blue-500', 'text-white');
                cardViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                localStorage.setItem('adminApprovalView', 'table');
            } else {
                tableView.classList.add('hidden-view');
                cardView.classList.remove('hidden-view');
                cardViewBtn.classList.add('active', 'bg-blue-500', 'text-white');
                cardViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                tableViewBtn.classList.remove('active', 'bg-blue-500', 'text-white');
                tableViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                localStorage.setItem('adminApprovalView', 'card');
            }
        }
        
        tableViewBtn.addEventListener('click', () => setView('table'));
        cardViewBtn.addEventListener('click', () => setView('card'));
        setView(savedView);
        
        // ========== SEARCH FUNCTIONALITY ==========
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#usersTableBody .user-row');
        const cardItems = document.querySelectorAll('#cardsContainer .user-card');
        const emptyState = document.getElementById('emptyState');
        const totalCountSpan = document.getElementById('totalCount');
        
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase();
            let visibleTableCount = 0;
            let visibleCardCount = 0;
            
            tableRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const position = row.getAttribute('data-position') || '';
                const matches = name.includes(searchTerm) || email.includes(searchTerm) || position.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
                if (matches) visibleTableCount++;
            });
            
            cardItems.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const email = card.getAttribute('data-email') || '';
                const position = card.getAttribute('data-position') || '';
                const matches = name.includes(searchTerm) || email.includes(searchTerm) || position.includes(searchTerm);
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCardCount++;
            });
            
            const isTableView = !tableView.classList.contains('hidden-view');
            const visibleCount = isTableView ? visibleTableCount : visibleCardCount;
            
            if (visibleCount === 0 && searchTerm !== '') {
                emptyState.classList.remove('hidden');
                if (isTableView) tableView.classList.add('hidden');
                else cardView.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                if (isTableView) tableView.classList.remove('hidden');
                else cardView.classList.remove('hidden');
            }
            
            if (totalCountSpan) {
                totalCountSpan.textContent = visibleCount + ' pending';
            }
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
        
        // ========== VIEW USER DETAILS ==========
        let currentUserId = null;
        const viewModal = document.getElementById('viewModal');
        
        function showToast(message, type = 'info') {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        async function viewUserDetails(userId) {
            currentUserId = userId;
            try {
                const response = await fetch(`get_user.php?id=${userId}`);
                const data = await response.json();
                if (data.success) {
                    const user = data.user;
                    document.getElementById('view_firstname').textContent = user.firstname || 'N/A';
                    document.getElementById('view_lastname').textContent = user.lastname || 'N/A';
                    document.getElementById('view_username').textContent = user.username ? '@' + user.username : 'N/A';
                    document.getElementById('view_position').textContent = user.position || 'Not specified';
                    document.getElementById('view_email').textContent = user.email || 'N/A';
                    document.getElementById('view_phone').textContent = user.phone || 'Not provided';
                    document.getElementById('view_dob').textContent = user.dob ? new Date(user.dob).toLocaleDateString() : 'Not provided';
                    document.getElementById('view_employee_id').textContent = user.employee_id || 'Not assigned';
                    document.getElementById('view_role').textContent = user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Employee';
                    document.getElementById('view_address').textContent = user.address || 'Not provided';
                    viewModal.classList.remove('hidden');
                } else {
                    showToast('Failed to load user details', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error', 'error');
            }
        }
        
        function closeViewModal() {
            viewModal.classList.add('hidden');
            currentUserId = null;
        }
        
        // ========== APPROVE USER ==========
        async function approveUser(userId) {
            if (!confirm('Are you sure you want to approve this user?')) return;
            
            try {
                const response = await fetch('../approve_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + userId
                });
                const result = await response.text();
                if (result.trim() === 'success') {
                    showToast('User approved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + result, 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
        }
        
        // ========== REJECT USER ==========
        async function rejectUser(userId) {
            if (!confirm('Are you sure you want to reject this user?')) return;
            
            try {
                const response = await fetch('../reject_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + userId
                });
                const result = await response.text();
                if (result.trim() === 'success') {
                    showToast('User rejected successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + result, 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
        }
        
        function approveFromModal() {
            if (currentUserId) {
                approveUser(currentUserId);
                closeViewModal();
            }
        }
        
        function rejectFromModal() {
            if (currentUserId) {
                rejectUser(currentUserId);
                closeViewModal();
            }
        }
        
        // Event listeners for buttons
        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.view-user-btn');
            if (viewBtn) {
                e.preventDefault();
                const userId = viewBtn.getAttribute('data-user-id');
                viewUserDetails(userId);
                viewBtn.closest('.dropdown')?.classList.remove('open');
            }
            
            const approveBtn = e.target.closest('.approve-user-btn');
            if (approveBtn) {
                e.preventDefault();
                const userId = approveBtn.getAttribute('data-user-id');
                approveUser(userId);
                approveBtn.closest('.dropdown')?.classList.remove('open');
            }
            
            const rejectBtn = e.target.closest('.reject-user-btn');
            if (rejectBtn) {
                e.preventDefault();
                const userId = rejectBtn.getAttribute('data-user-id');
                rejectUser(userId);
                rejectBtn.closest('.dropdown')?.classList.remove('open');
            }
        });
        
        // ========== SIDEBAR TOGGLE ==========
        const mainContent = document.getElementById("mainContent");
        window.addEventListener('sidebarToggle', function() {
            const sidebar = document.getElementById("sidebar");
            if (sidebar) {
                if (sidebar.classList.contains("w-64")) {
                    mainContent.classList.remove("sidebar-collapsed");
                } else {
                    mainContent.classList.add("sidebar-collapsed");
                }
            }
        });
        const sidebar = document.getElementById("sidebar");
        if (sidebar && !sidebar.classList.contains("w-64")) {
            mainContent.classList.add("sidebar-collapsed");
        }
        
        // ========== CLOSE MODALS ON ESCAPE ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
            }
        });
        
        viewModal.addEventListener('click', function(e) {
            if (e.target === viewModal) closeViewModal();
        });
    </script>
</body>
</html>