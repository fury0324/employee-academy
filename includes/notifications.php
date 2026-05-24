<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

// ============================================
// DYNAMIC URL CONFIGURATION - CORRECTED PATHS
// ============================================

// Base paths
define('BASE_PATH', '../');
define('LOGIN_PATH', BASE_PATH . 'login/');     // ../login/
define('ADMIN_PATH', BASE_PATH . 'admin/');     // ../admin/

// Notification URL mapping - CORRECTED
$notification_urls = [
    // User management (nasa login folder)
    'user_registration' => LOGIN_PATH . 'admin_approval.php',  // ../login/admin_approval.php
    'user_approved' => LOGIN_PATH . 'login.php',               // ../login/login.php
    'user_rejected' => LOGIN_PATH . 'register.php',            // ../login/register.php
    
    // Retake requests (nasa admin folder)
    'retake_request' => ADMIN_PATH . 'Request_retake.php',     // ../admin/Request_retake.php
    'retake_approved' => ADMIN_PATH . 'Request_retake.php',    // ../admin/Request_retake.php
    'retake_rejected' => ADMIN_PATH . 'Request_retake.php',    // ../admin/Request_retake.php
    
    // Course management (nasa admin folder)
    'course_completed' => ADMIN_PATH . 'reports.php',          // ../admin/reports.php
    'course_status' => ADMIN_PATH . 'course_management.php',   // ../admin/course_management.php
    'course_created' => ADMIN_PATH . 'course_management.php',  // ../admin/course_management.php
    
    // Quiz management (nasa admin folder)
    'quiz_submitted' => ADMIN_PATH . 'quiz_management.php',    // ../admin/quiz_management.php
    'quiz_failed' => ADMIN_PATH . 'quiz_management.php',       // ../admin/quiz_management.php
    
    // Reports (nasa admin folder)
    'report_ready' => ADMIN_PATH . 'reports.php',              // ../admin/reports.php
    
    // Default
    'system' => ADMIN_PATH . 'dashboard.php',                  // ../admin/dashboard.php
    'warning' => ADMIN_PATH . 'dashboard.php',                 // ../admin/dashboard.php
    'admin_action' => ADMIN_PATH . 'audit_logs.php',           // ../admin/audit_logs.php
];

// ============================================
// PAGINATION
// ============================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$notifications = $stmt->get_result();

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get dynamic URL based on notification type
 */
function getNotificationUrl($type, $custom_link) {
    global $notification_urls;
    
    // If there's a custom link in database, use it dynamically
    if (!empty($custom_link)) {
        $clean_link = ltrim($custom_link, './');
        return BASE_PATH . $clean_link;
    }
    
    // Return mapped URL or default
    return isset($notification_urls[$type]) ? $notification_urls[$type] : ADMIN_PATH . 'dashboard.php';
}

/**
 * Get icon class based on notification type
 */
function getNotificationIcon($type) {
    $icons = [
        'user_registration' => 'fa-user-plus',
        'user_approved' => 'fa-check-circle',
        'user_rejected' => 'fa-times-circle',
        'retake_request' => 'fa-redo-alt',
        'retake_approved' => 'fa-check-circle',
        'retake_rejected' => 'fa-times-circle',
        'course_completed' => 'fa-graduation-cap',
        'course_status' => 'fa-chart-line',
        'course_created' => 'fa-book',
        'quiz_submitted' => 'fa-question-circle',
        'quiz_failed' => 'fa-exclamation-triangle',
        'report_ready' => 'fa-chart-bar',
        'system' => 'fa-cog',
        'warning' => 'fa-exclamation-triangle',
        'admin_action' => 'fa-shield-alt',
    ];
    return isset($icons[$type]) ? $icons[$type] : 'fa-bell';
}

/**
 * Get color class based on notification type
 */
function getNotificationColor($type) {
    $colors = [
        'user_registration' => 'text-yellow-600 bg-yellow-100',
        'user_approved' => 'text-green-600 bg-green-100',
        'user_rejected' => 'text-red-600 bg-red-100',
        'retake_request' => 'text-red-600 bg-red-100',
        'retake_approved' => 'text-green-600 bg-green-100',
        'retake_rejected' => 'text-red-600 bg-red-100',
        'course_completed' => 'text-green-600 bg-green-100',
        'course_status' => 'text-blue-600 bg-blue-100',
        'course_created' => 'text-blue-600 bg-blue-100',
        'quiz_submitted' => 'text-purple-600 bg-purple-100',
        'quiz_failed' => 'text-orange-600 bg-orange-100',
        'report_ready' => 'text-blue-600 bg-blue-100',
        'system' => 'text-gray-600 bg-gray-100',
        'warning' => 'text-orange-600 bg-orange-100',
        'admin_action' => 'text-purple-600 bg-purple-100',
    ];
    return isset($colors[$type]) ? $colors[$type] : 'text-gray-600 bg-gray-100';
}

/**
 * Get human-readable time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .notification-item {
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="lg:ml-64 mt-16 p-6">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg">
                <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-bell mr-2 text-blue-600"></i> All Notifications
                            </h1>
                            <p class="text-sm text-gray-500 mt-1">View and manage all your notifications</p>
                        </div>
                        <button onclick="markAllAsRead()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                            <i class="fas fa-check-double"></i> Mark all as read
                        </button>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php while($notif = $notifications->fetch_assoc()): 
                            $targetUrl = getNotificationUrl($notif['type'], $notif['link']);
                            $iconClass = getNotificationIcon($notif['type']);
                            $colorClass = getNotificationColor($notif['type']);
                            $timeAgo = timeAgo($notif['created_at']);
                        ?>
                            <div class="notification-item p-5 hover:bg-gray-50 transition <?php echo !$notif['is_read'] ? 'bg-blue-50/30 border-l-4 border-l-blue-500' : ''; ?>" id="notification-<?php echo $notif['id']; ?>">
                                <div class="flex items-start gap-4">
                                    <!-- Icon Container -->
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-full <?php echo $colorClass; ?> flex items-center justify-center text-xl">
                                            <i class="fas <?php echo $iconClass; ?>"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1">
                                        <div class="flex flex-wrap justify-between items-start gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($notif['title']); ?></h3>
                                                    <?php if (!$notif['is_read']): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-1"></span>
                                                            New
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-gray-600 mt-1 leading-relaxed"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                                                <div class="flex items-center gap-4 mt-2">
                                                    <span class="text-xs text-gray-400">
                                                        <i class="far fa-clock mr-1"></i> <?php echo $timeAgo; ?>
                                                    </span>
                                                    <?php if ($targetUrl && $targetUrl != '#'): ?>
                                                        <a href="<?php echo $targetUrl; ?>" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                                            View details <i class="fas fa-arrow-right text-xs"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="flex gap-1">
                                                <?php if (!$notif['is_read']): ?>
                                                    <button onclick="markSingleAsRead(<?php echo $notif['id']; ?>)" 
                                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                            title="Mark as read">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="deleteSingleNotification(<?php echo $notif['id']; ?>)" 
                                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                        title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-16">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-bell-slash text-gray-400 text-4xl"></i>
                            </div>
                            <p class="text-gray-500 text-lg font-medium">No notifications yet</p>
                            <p class="text-gray-400 text-sm mt-1">You're all caught up!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t bg-gray-50">
                        <div class="flex justify-center gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>" class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <p class="text-center text-xs text-gray-500 mt-3">
                            Showing <?php echo min($offset + 1, $total); ?> - <?php echo min($offset + $limit, $total); ?> of <?php echo $total; ?> notifications
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function markSingleAsRead(id) {
        fetch('../includes/mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.getElementById(`notification-${id}`);
                if (notification) {
                    notification.classList.remove('bg-blue-50/30', 'border-l-4', 'border-l-blue-500');
                    const newBadge = notification.querySelector('.inline-flex.items-center.px-2.py-0.5');
                    if (newBadge) newBadge.remove();
                    const markButton = notification.querySelector('button:first-of-type');
                    if (markButton) markButton.remove();
                }
                showToast('Notification marked as read', 'success');
            }
        })
        .catch(err => console.error('Error:', err));
    }
    
    function deleteSingleNotification(id) {
        if (!confirm('Delete this notification?')) return;
        
        fetch('../includes/delete_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`notification-${id}`).remove();
                showToast('Notification deleted', 'success');
                
                const remainingNotifications = document.querySelectorAll('.notification-item');
                if (remainingNotifications.length === 0) {
                    location.reload();
                }
            }
        })
        .catch(err => console.error('Error:', err));
    }
    
    function markAllAsRead() {
        fetch('../includes/mark_all_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(err => console.error('Error:', err));
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-0 flex items-center gap-2 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>