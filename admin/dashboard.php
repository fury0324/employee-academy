<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$user_name = $_SESSION['firstname'] ?? 'Admin';
$user_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [];

// Total employees (users with role 'employee')
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$stats['total_employees'] = $result->fetch_assoc()['total'];

// Total courses (check if table exists first)
$result = $conn->query("SHOW TABLES LIKE 'courses'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as total FROM courses WHERE status = 'published'");
    $stats['total_courses'] = $result->fetch_assoc()['total'];
} else {
    $stats['total_courses'] = 0;
}

// Completed today - FIXED: Use certificates table instead (has proper issued_at dates)
$today = date('Y-m-d');
$result = $conn->query("
    SELECT COUNT(*) as total 
    FROM certificates 
    WHERE DATE(issued_at) = '$today'
");
$stats['completed_today'] = $result->fetch_assoc()['total'];

// Pending approvals
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$stats['pending_approvals'] = $result->fetch_assoc()['total'];

// Get recent activities
$recent_users = $conn->query("SELECT firstname, lastname, created_at FROM users WHERE role = 'employee' ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UpStaff Academy</title>
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
            margin-left: 5rem; /* w-20 */
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
        
        .stat-card {
            transition: all 0.3s ease;
            background: white;
            border: 1px solid #e5e7eb;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #d1d5db;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
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

        /* Consistent icon circle styling */
        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .stat-card:hover .icon-circle {
            transform: scale(1.05);
        }

        /* Consistent badge styling */
        .stat-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        
        <!-- Header -->
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <!-- Page Content -->
        <div class="p-6">
            <!-- Welcome Section -->
            <div class="mb-8 fade-in">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="text-gray-500 mt-1">Here's what's happening with UpStaff Academy today</p>
            </div>

            <!-- Stats Grid - WHITE CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 fade-in">
                <!-- Total Employees Card -->
                <div class="stat-card rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="icon-circle bg-blue-50">
                            <i class="fas fa-users text-xl text-blue-600"></i>
                        </div>
                        <span class="stat-badge bg-blue-50 text-blue-700">total employees</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Employees</h3>
                    <div class="flex items-end justify-between mt-2">
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $stats['total_employees']; ?></h2>
                        <span class="text-sm text-gray-400">active accounts</span>
                    </div>
                </div>

                <!-- Total Courses Card -->
                <div class="stat-card rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="icon-circle bg-purple-50">
                            <i class="fas fa-book-open text-xl text-purple-600"></i>
                        </div>
                        <span class="stat-badge bg-purple-50 text-purple-700"><?php echo $stats['total_courses']; ?> active</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Courses</h3>
                    <div class="flex items-end justify-between mt-2">
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $stats['total_courses']; ?></h2>
                        <span class="text-sm text-gray-400">available</span>
                    </div>
                </div>

                <!-- Completed Today Card - FIXED -->
                <div class="stat-card rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="icon-circle bg-green-50">
                            <i class="fas fa-check-circle text-xl text-green-600"></i>
                        </div>
                        <span class="stat-badge bg-green-50 text-green-700">today</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Completed Today</h3>
                    <div class="flex items-end justify-between mt-2">
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $stats['completed_today']; ?></h2>
                        <span class="text-sm text-gray-400">courses</span>
                    </div>
                </div>

                <!-- Pending Approvals Card -->
                <div class="stat-card rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="icon-circle bg-amber-50">
                            <i class="fas fa-clock text-xl text-amber-600"></i>
                        </div>
                        <span class="stat-badge bg-amber-50 text-amber-700">action needed</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Approvals</h3>
                    <div class="flex items-end justify-between mt-2">
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $stats['pending_approvals']; ?></h2>
                        <a href="../login/admin_approval.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">review →</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 fade-in">
                <!-- Recent Registrations -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Registrations</h3>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">newest first</span>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                        <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-700 font-semibold text-sm">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">new</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">No recent registrations</p>
                        <?php endif; ?>
                    </div>
                    <a href="../login/admin_approval.php" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
                        View all registrations →
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 mb-5">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="../login/admin_approval.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-check text-amber-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Pending Approvals</span>
                            </div>
                            <span class="bg-amber-500 text-white text-xs px-2 py-1 rounded-full min-w-[24px] text-center"><?php echo $stats['pending_approvals']; ?></span>
                        </a>
                        
                        <a href="course_management.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-plus-circle text-purple-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Add New Course</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                        </a>
                        
                        <a href="../admin/Generate_report.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-bar text-green-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Generate Reports</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
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
        
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', function() {
            setTimeout(updateSidebarState, 10);
        });

        // Check initial state
        updateSidebarState();
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                if (fixedHeader) fixedHeader.classList.add('sidebar-collapsed');
                mainContent.classList.remove('sidebar-collapsed');
            } else {
                updateSidebarState();
            }
        });
    });
    </script>
</body>
</html>