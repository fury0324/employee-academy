<?php
$user_name = $_SESSION['firstname'] ?? 'Admin';
$user_id = $_SESSION['user_id'] ?? 0;

// Get current page title for mobile view
$current_page = basename($_SERVER['PHP_SELF'], ".php");
$page_title_display = ucwords(str_replace('_', ' ', $current_page));

// Define page hierarchy for breadcrumb
$breadcrumb_map = [
    'dashboard' => ['parent' => 'Home', 'icon' => 'tachometer-alt', 'title' => 'Dashboard'],
    'course_management' => ['parent' => 'Courses', 'icon' => 'book', 'title' => 'Course Management'],
    'quiz_management' => ['parent' => 'Courses', 'icon' => 'question-circle', 'title' => 'Quiz Management'],
    'reports' => ['parent' => 'Reports', 'icon' => 'chart-line', 'title' => 'Reports'],
    'admin_approval' => ['parent' => 'User Management', 'icon' => 'users', 'title' => 'Pending Approval'],
    'approved_users' => ['parent' => 'User Management', 'icon' => 'users', 'title' => 'Approved Users'],
    'rejected_user' => ['parent' => 'User Management', 'icon' => 'users', 'title' => 'Rejected Users'],
    'audit_logs' => ['parent' => 'Settings', 'icon' => 'history', 'title' => 'Audit Logs'],
    'account_settings' => ['parent' => 'Settings', 'icon' => 'cog', 'title' => 'Account Settings'],
    'profile' => ['parent' => 'Account', 'icon' => 'user', 'title' => 'My Profile'],
    'Request_retake' => ['parent' => 'Courses', 'icon' => 'repeat', 'title' => 'Retake Requests']
];

$breadcrumb = $breadcrumb_map[$current_page] ?? ['parent' => 'Admin', 'icon' => 'home', 'title' => ucfirst($current_page)];

// Fetch profile picture from database
$profile_picture_url = null;

if ($user_id) {
    require_once __DIR__ . '/../config/db.php';
    
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($check_column && $check_column->num_rows > 0) {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_pic_data = $result->fetch_assoc();
        
        if (!empty($user_pic_data['profile_picture'])) {
            $profile_picture_url = '../uploads/profile_pictures/' . $user_pic_data['profile_picture'];
        }
        $stmt->close();
    }
}

// Fetch user data for profile modal
$user_data = [
    'firstname' => $_SESSION['firstname'] ?? '',
    'lastname' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'position' => ''
];

try {
    $stmt = $conn->prepare("SELECT lastname, email, phone, address, position FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data['lastname'] = $row['lastname'] ?? '';
            $user_data['email'] = $row['email'] ?? '';
            $user_data['phone'] = $row['phone'] ?? '';
            $user_data['address'] = $row['address'] ?? '';
            $user_data['position'] = $row['position'] ?? 'Administrator';
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silently fail
}
?>

<header class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-40 fixed-header">
    <div class="flex items-center justify-between px-4 md:px-6 py-3">
        
        <!-- LEFT SIDE: Mobile Menu + Breadcrumb -->
        <div class="flex items-center space-x-3">
            <!-- Mobile Menu Button -->
            <button id="mobileMenuBtn" class="md:hidden text-gray-600 hover:text-blue-600 text-xl">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Breadcrumb / Directory (Desktop) -->
            <div class="hidden md:flex items-center space-x-2 text-sm">
                <a href="../admin/dashboard.php" class="text-gray-500 hover:text-blue-600 transition">
                    <i class="fas fa-home"></i>
                </a>
                <span class="text-gray-400">
                    <i class="fas fa-chevron-right text-xs"></i>
                </span>
                <span class="text-gray-500"><?php echo $breadcrumb['parent']; ?></span>
                <span class="text-gray-400">
                    <i class="fas fa-chevron-right text-xs"></i>
                </span>
                <span class="text-gray-800 font-semibold"><?php echo $breadcrumb['title']; ?></span>
            </div>
            
            <!-- Page Title for Mobile -->
            <div class="md:hidden font-semibold text-gray-800">
                <?php echo $breadcrumb['title']; ?>
            </div>
        </div>

        <!-- RIGHT SIDE: Notifications + Profile -->
        <div class="flex items-center space-x-4">

            <!-- Notification Dropdown -->
            <div class="relative" id="notificationDropdown">
                <button onclick="toggleNotifications()" class="text-gray-600 hover:text-blue-600 text-lg relative focus:outline-none">
                    <i class="fas fa-bell"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center notification-badge hidden">0</span>
                </button>

                <!-- Notification Panel -->
                <div id="notificationPanel" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">
                    <div class="p-3 border-b bg-gray-50 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-bell"></i> Notifications
                        </h3>
                        <button onclick="markAllAsRead()" class="text-xs text-blue-600 hover:text-blue-800">
                            Mark all as read
                        </button>
                    </div>
                    <div class="max-h-96 overflow-y-auto notifications-list">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="text-gray-500 text-sm mt-2">Loading notifications...</p>
                        </div>
                    </div>
                    <div class="p-2 border-t text-center">
                        <a href="../includes/notifications.php" class="text-sm text-blue-600 hover:text-blue-800">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" id="profileDropdown">
                <button onclick="toggleProfile()" class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                        <?php if ($profile_picture_url): ?>
                            <img src="<?php echo $profile_picture_url; ?>?t=<?php echo time(); ?>" class="w-full h-full object-cover" alt="Profile">
                        <?php else: ?>
                            <span><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="hidden md:inline font-medium text-gray-700"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>

                <!-- Profile Dropdown Menu -->
                <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">
                    <div class="p-3 border-b bg-gray-50">
                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?> <?php echo htmlspecialchars($user_data['lastname']); ?></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <button onclick="openProfileModal()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i> Profile Information
                    </button>
                    <button onclick="openSecurityModal()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-shield-alt mr-2"></i> Security
                    </button>
                    <div class="border-t my-1"></div>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>

        </div>
    </div>
</header>

<!-- Profile Modal -->
<div id="profileModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl">
                <div class="relative bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <h2 class="text-white text-lg font-semibold flex items-center gap-2">
                        <i class="fas fa-user-edit"></i> Profile Information
                    </h2>
                    <button onclick="closeProfileModal()" class="absolute right-4 top-4 text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6 md:p-8">
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- LEFT: Profile picture upload area -->
                        <div class="flex flex-col items-center space-y-4 md:w-48 flex-shrink-0">
                            <div class="relative group cursor-pointer avatar-container" id="avatarContainer">
                                <div class="w-36 h-36 md:w-40 md:h-40 rounded-full bg-gradient-to-tr from-gray-100 to-gray-200 shadow-md border-4 border-white ring-1 ring-gray-200 overflow-hidden transition-all duration-200 group-hover:scale-[1.02] group-hover:shadow-lg">
                                    <img id="profileAvatar" class="w-full h-full object-cover"
                                        src="<?php echo $profile_picture_url ? $profile_picture_url : 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%23cbd5e1\'/%3E%3Ccircle cx=\'50\' cy=\'38\' r=\'16\' fill=\'%236b7280\'/%3E%3Cpath d=\'M22 76 Q50 56 78 76\' stroke=\'%234b5563\' stroke-width=\'6\' fill=\'none\' stroke-linecap=\'round\'/%3E%3C/svg%3E'; ?>"
                                        alt="Profile picture preview">
                                </div>
                                <div class="absolute bottom-1 right-1 bg-white/95 backdrop-blur-sm rounded-full p-2 shadow-md border border-gray-200 transition group-hover:bg-white">
                                    <i class="fas fa-camera text-gray-700 text-sm"></i>
                                </div>
                            </div>
                            <button type="button" id="triggerUploadBtn" class="text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full transition flex items-center gap-1">
                                <i class="fas fa-cloud-upload-alt"></i> Change photo
                            </button>
                            <p class="text-xs text-gray-400 text-center">Click image or button<br>JPG, PNG up to 5MB</p>
                            <input type="file" id="profileUploadInput" name="profile_picture" accept="image/jpeg, image/png, image/webp, image/jpg" class="hidden">
                        </div>

                        <!-- RIGHT: Form fields -->
                        <div class="flex-1">
                            <form id="profileForm">
                                <div class="grid grid-cols-1 gap-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                            <input type="text" name="firstname" id="profile_firstname" value="<?php echo htmlspecialchars($user_data['firstname'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                            <input type="text" name="lastname" id="profile_lastname" value="<?php echo htmlspecialchars($user_data['lastname'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                        <input type="email" name="email" id="profile_email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Position / Job Title</label>
                                        <input type="text" name="position" id="profile_position" value="<?php echo htmlspecialchars($user_data['position'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                        <input type="tel" name="phone" id="profile_phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                        <textarea name="address" id="profile_address" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div id="profileMessage" class="hidden text-sm"></div>
                                    <button type="submit" name="update_profile" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg mt-2">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Security Modal -->
<div id="securityModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-md">
                <div class="relative bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-5">
                    <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-key"></i> Change Password
                    </h3>
                    <button onclick="closeSecurityModal()" class="absolute right-4 top-4 text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 bg-gray-50">
                    <form id="securityForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" id="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                            </div>
                            <div id="securityMessage" class="hidden text-sm"></div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" onclick="closeSecurityModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Update Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Fixed header styles */
    .fixed-header {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 40;
        background: white;
        transition: all 0.3s ease;
    }
    
    /* Adjust header position based on sidebar */
    .fixed-header {
        margin-left: 16rem;
        width: calc(100% - 16rem);
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    /* When sidebar is collapsed */
    .fixed-header.sidebar-collapsed {
        margin-left: 5rem;
        width: calc(100% - 5rem);
    }
    
    /* Responsive: on mobile, header takes full width */
    @media (max-width: 768px) {
        .fixed-header {
            margin-left: 0 !important;
            width: 100% !important;
        }
        #mobileMenuBtn {
            display: block !important;
        }
    }
    
    @media (max-width: 768px) {
        .sidebar.mobile-open {
            transform: translateX(0) !important;
        }
        .notification-badge {
            font-size: 0.6rem;
            width: 1.2rem;
            height: 1.2rem;
        }
    }
    
    /* Smooth transitions */
    #notificationPanel, #profileMenu {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    
    .form-input:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    #profileUploadInput {
        display: none;
    }
    
    .avatar-container {
        transition: all 0.2s ease;
    }
    .avatar-container:hover {
        transform: scale(1.02);
    }
    
    /* Notification item styles */
    .notification-item {
        transition: all 0.2s ease;
    }
    .notification-item:hover {
        background-color: #f9fafb;
    }
</style>

<script>
// Enhanced Notification System
let notificationCheckInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    // Load initial notifications
    loadNotifications();
    
    // Auto-refresh notifications every 30 seconds
    if (notificationCheckInterval) clearInterval(notificationCheckInterval);
    notificationCheckInterval = setInterval(loadNotifications, 30000);
    
    // Toggle notification panel
    window.toggleNotifications = function() {
        const panel = document.getElementById('notificationPanel');
        const profileMenu = document.getElementById('profileMenu');
        
        if (profileMenu && !profileMenu.classList.contains('hidden')) {
            profileMenu.classList.add('hidden');
        }
        
        if (panel) {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                loadNotifications(); // Refresh when opening
            }
        }
    }
    
    // Toggle profile menu
    window.toggleProfile = function() {
        const menu = document.getElementById('profileMenu');
        const notificationPanel = document.getElementById('notificationPanel');
        
        if (notificationPanel && !notificationPanel.classList.contains('hidden')) {
            notificationPanel.classList.add('hidden');
        }
        
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }
    
    // Mark single notification as read
    window.markAsRead = function(notificationId, element) {
        fetch('../includes/mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(); // Reload to update count
                showNotification('Notification marked as read', 'success');
            }
        })
        .catch(err => console.error('Error marking as read:', err));
    }
    
    // Delete notification
    window.deleteNotification = function(notificationId, element) {
        if (!confirm('Delete this notification?')) return;
        
        fetch('../includes/delete_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(); // Reload to update list
                showNotification('Notification deleted', 'success');
            }
        })
        .catch(err => console.error('Error deleting notification:', err));
    }
    
    // Mark all notifications as read
    window.markAllAsRead = function() {
        fetch('../includes/mark_all_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                showNotification('All notifications marked as read', 'success');
            }
        })
        .catch(err => console.error('Error marking all as read:', err));
    }
    
    // Load notifications from server
    function loadNotifications() {
        fetch('../includes/get_notifications.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateNotificationUI(data);
                } else {
                    console.error('Error from server:', data.message);
                }
            })
            .catch(err => console.error('Error loading notifications:', err));
    }
    
// Update the notification UI
function updateNotificationUI(data) {
    const badge = document.querySelector('.notification-badge');
    const notificationsList = document.querySelector('.notifications-list');
    
    // Update badge count
    if (badge) {
        if (data.unread_count > 0) {
            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    
    // Update notifications list
    if (notificationsList && data.notifications) {
        if (data.notifications.length === 0) {
            notificationsList.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-bell-slash text-gray-400 text-3xl mb-2"></i>
                    <p class="text-gray-500 text-sm">No notifications yet</p>
                </div>
            `;
        } else {
            notificationsList.innerHTML = data.notifications.map(notif => {
                // Determine the correct URL based on notification type using PHP logic
                let targetUrl = '';
                
                <?php
                // PHP logic to determine URL based on notification type
                // This will be evaluated on page load
                ?>
                
                if (notif.type === 'user_registration') {
                    targetUrl = '../login/admin_approval.php';
                } else if (notif.type === 'retake_request') {
                    targetUrl = '../admin/Request_retake.php';
                } else if (notif.type === 'course_completed') {
                    targetUrl = '../admin/reports.php';
                } else if (notif.link) {
                    targetUrl = '../' + notif.link;
                } else {
                    targetUrl = '#';
                }
                
                return `
                    <div class="notification-item ${!notif.is_read ? 'bg-blue-50' : ''} relative group border-b last:border-b-0">
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    ${getNotificationIcon(notif.type)}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-1">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-gray-800">${escapeHtml(notif.title)}</p>
                                            ${!notif.is_read ? '<div class="inline-block ml-2 w-2 h-2 bg-blue-500 rounded-full align-middle"></div>' : ''}
                                        </div>
                                        <div class="flex gap-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            ${!notif.is_read ? `
                                                <button onclick="markAsRead(${notif.id}, this)" 
                                                        class="text-xs text-blue-600 hover:text-blue-800 p-1" title="Mark as read">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            ` : ''}
                                            <button onclick="deleteNotification(${notif.id}, this)" 
                                                    class="text-xs text-red-600 hover:text-red-800 p-1" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">${escapeHtml(notif.message)}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs text-gray-400">
                                            <i class="far fa-clock mr-1"></i>${notif.time_ago}
                                        </span>
                                        <a href="${targetUrl}" class="text-xs text-blue-600 hover:text-blue-800">
                                            View details <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }
}
    
    // Get appropriate icon for notification type
    function getNotificationIcon(type) {
        const icons = {
            'user_registration': '<div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center"><i class="fas fa-user-plus text-yellow-600 text-lg"></i></div>',
            'user_approved': '<div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-check-circle text-green-600 text-lg"></i></div>',
            'user_rejected': '<div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-times-circle text-red-600 text-lg"></i></div>',
            'course_completed': '<div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-graduation-cap text-green-600 text-lg"></i></div>',
            'course_created': '<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-book text-blue-600 text-lg"></i></div>',
            'quiz_submitted': '<div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-question-circle text-purple-600 text-lg"></i></div>',
            'retake_request': '<div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-redo-alt text-red-600 text-lg"></i></div>',
            'retake_approved': '<div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-check-circle text-green-600 text-lg"></i></div>',
            'retake_rejected': '<div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-times-circle text-red-600 text-lg"></i></div>',
            'report_ready': '<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-chart-line text-blue-600 text-lg"></i></div>',
            'system': '<div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center"><i class="fas fa-cog text-gray-600 text-lg"></i></div>',
            'warning': '<div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-orange-600 text-lg"></i></div>'
        };
        return icons[type] || icons.system;
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Show notification function
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-0 flex items-center gap-2 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        }`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const profileDropdown = document.getElementById('profileDropdown');
        const notificationPanel = document.getElementById('notificationPanel');
        const profileMenu = document.getElementById('profileMenu');
        
        if (notificationDropdown && !notificationDropdown.contains(event.target)) {
            if (notificationPanel) notificationPanel.classList.add('hidden');
        }
        
        if (profileDropdown && !profileDropdown.contains(event.target)) {
            if (profileMenu) profileMenu.classList.add('hidden');
        }
    });
    
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        });
    }
    
    // Sync header with sidebar toggle
    window.addEventListener('sidebarToggle', function() {
        const fixedHeader = document.querySelector('.fixed-header');
        const sidebar = document.getElementById('sidebar');
        if (fixedHeader && sidebar) {
            if (sidebar.classList.contains('w-64')) {
                fixedHeader.classList.remove('sidebar-collapsed');
            } else {
                fixedHeader.classList.add('sidebar-collapsed');
            }
        }
    });
    
    // Initialize header position based on sidebar state on load
    const fixedHeader = document.querySelector('.fixed-header');
    const sidebar = document.getElementById('sidebar');
    if (fixedHeader && sidebar && sidebar.classList.contains('w-20')) {
        fixedHeader.classList.add('sidebar-collapsed');
    }
});

// Clean up interval on page unload
window.addEventListener('beforeunload', function() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
});

// Profile Modal Functions
function openProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) modal.classList.remove('hidden');
    
    fetch('../admin/get_user_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('profile_firstname').value = data.user.firstname || '';
                document.getElementById('profile_lastname').value = data.user.lastname || '';
                document.getElementById('profile_email').value = data.user.email || '';
                document.getElementById('profile_phone').value = data.user.phone || '';
                document.getElementById('profile_address').value = data.user.address || '';
                document.getElementById('profile_position').value = data.user.position || '';
                
                if (data.user.profile_picture_url) {
                    document.getElementById('profileAvatar').src = data.user.profile_picture_url;
                }
            }
        })
        .catch(err => console.error('Error:', err));
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) modal.classList.add('hidden');
}

function openSecurityModal() {
    const modal = document.getElementById('securityModal');
    if (modal) modal.classList.remove('hidden');
    document.getElementById('securityForm')?.reset();
}

function closeSecurityModal() {
    const modal = document.getElementById('securityModal');
    if (modal) modal.classList.add('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileModal();
        closeSecurityModal();
    }
});

// Close modals on backdrop click
const profileModal = document.getElementById('profileModal');
const securityModal = document.getElementById('securityModal');
if (profileModal) {
    profileModal.addEventListener('click', function(e) {
        if (e.target === this) closeProfileModal();
    });
}
if (securityModal) {
    securityModal.addEventListener('click', function(e) {
        if (e.target === this) closeSecurityModal();
    });
}

// Profile Picture Upload Functionality
(function() {
    const avatarImg = document.getElementById('profileAvatar');
    const fileInput = document.getElementById('profileUploadInput');
    const avatarContainer = document.getElementById('avatarContainer');
    const triggerBtn = document.getElementById('triggerUploadBtn');
    let selectedImageFile = null;

    function showFloatingMessage(message, isError = false) {
        let toast = document.getElementById('modalToastMsg');
        if (toast) toast.remove();
        toast = document.createElement('div');
        toast.id = 'modalToastMsg';
        toast.className = `fixed top-6 left-1/2 transform -translate-x-1/2 z-[60] px-5 py-2.5 rounded-full shadow-lg text-sm font-medium transition-all duration-300 ${isError ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white'} flex items-center gap-2`;
        toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${message}`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    function openFileSelector() { if (fileInput) fileInput.click(); }
    if (avatarContainer) avatarContainer.addEventListener('click', openFileSelector);
    if (triggerBtn) triggerBtn.addEventListener('click', (e) => { e.preventDefault(); openFileSelector(); });

    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                showFloatingMessage('Please select a valid image (JPEG, PNG, WEBP)', true);
                fileInput.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showFloatingMessage('Image too large (max 5MB)', true);
                fileInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                if (avatarImg) avatarImg.src = evt.target.result;
                selectedImageFile = file;
                showFloatingMessage('Profile picture selected! Click Save Changes to upload.', false);
            };
            reader.onerror = function() {
                showFloatingMessage('Error reading file', true);
                fileInput.value = '';
            };
            reader.readAsDataURL(file);
        });
    }

    // Profile Form Submit with picture upload
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('firstname', document.getElementById('profile_firstname').value);
            formData.append('lastname', document.getElementById('profile_lastname').value);
            formData.append('email', document.getElementById('profile_email').value);
            formData.append('position', document.getElementById('profile_position').value);
            formData.append('phone', document.getElementById('profile_phone').value);
            formData.append('address', document.getElementById('profile_address').value);
            if (selectedImageFile) formData.append('profile_picture', selectedImageFile);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('../admin/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                const messageDiv = document.getElementById('profileMessage');
                if (result.success) {
                    messageDiv.className = 'mt-3 p-3 bg-green-100 text-green-700 rounded-lg text-sm';
                    messageDiv.innerHTML = 'Profile updated successfully!';
                    messageDiv.classList.remove('hidden');
                    setTimeout(() => {
                        closeProfileModal();
                        location.reload();
                    }, 1500);
                } else {
                    messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                    messageDiv.innerHTML = result.message || 'Update failed';
                    messageDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                const messageDiv = document.getElementById('profileMessage');
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'Network error. Please try again.';
                messageDiv.classList.remove('hidden');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
})();

// Security Form Submit
const securityForm = document.getElementById('securityForm');
if (securityForm) {
    securityForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        if (data.new_password !== data.confirm_password) {
            const messageDiv = document.getElementById('securityMessage');
            messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
            messageDiv.innerHTML = 'New password and confirmation do not match.';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        if (data.new_password.length < 6) {
            const messageDiv = document.getElementById('securityMessage');
            messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
            messageDiv.innerHTML = 'Password must be at least 6 characters.';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return
        }
        
        fetch('../admin/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            const messageDiv = document.getElementById('securityMessage');
            if (result.success) {
                messageDiv.className = 'mt-3 p-3 bg-green-100 text-green-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'Password changed successfully!';
                messageDiv.classList.remove('hidden');
                setTimeout(() => closeSecurityModal(), 1500);
            } else {
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = result.message || 'Password change failed';
                messageDiv.classList.remove('hidden');
            }
        })
        .catch(err => {
            const messageDiv = document.getElementById('securityMessage');
            messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
            messageDiv.innerHTML = 'Network error. Please try again.';
            messageDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}
</script>