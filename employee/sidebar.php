<?php
// Employee Sidebar – Professional Design
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['firstname'] ?? 'Employee';

// Fetch profile picture from database for sidebar
$profile_picture = null;
$profile_picture_path = null;

if ($user_id) {
    require_once __DIR__ . '/../config/db.php';
    
    // Check if profile_picture column exists
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($check_column && $check_column->num_rows > 0) {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if (!empty($user_data['profile_picture'])) {
            $profile_picture_path = __DIR__ . '/../uploads/profile_pictures/' . $user_data['profile_picture'];
            if (file_exists($profile_picture_path)) {
                $profile_picture = '../uploads/profile_pictures/' . $user_data['profile_picture'];
            }
        }
        $stmt->close();
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<aside id="sidebar" class="fixed top-0 left-0 w-64 h-screen bg-gradient-to-br from-gray-900 to-gray-800 text-white flex flex-col shadow-2xl transition-all duration-300 z-50">

    <div class="p-5 border-b border-gray-700/50 flex items-center justify-between">
<div class="flex items-start logo-area">
    <img src="../landingpage/assets/logo.png" class="w-10 h-10 rounded-lg shadow-md -mt-1" alt="Logo">
    <div class="flex flex-col leading-tight ml-3">
        <span class="text-2xl tracking-wide" style="font-family: 'Inter', sans-serif; font-weight: 900; letter-spacing: 2px; color: #ffffff;">upstaff</span>
        <span class="text-xs tracking-wide font-normal mt-0.5" style="font-family: 'Inter', sans-serif; font-weight: 400; letter-spacing: 0.5px; color: #44D7E9; margin-left: 20px;">ACADEMY</span>
    </div>
</div>
        <button id="collapseBtn" class="text-gray-400 hover:text-white transition-colors duration-200 focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <nav class="flex-1 py-6 px-4 overflow-y-auto">
        <div class="space-y-1">
            <!-- Dashboard -->
            <a href="dashboard.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group 
                      <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Dashboard</span>
            </a>

            <!-- Certifications -->
            <a href="certificates.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Certifications</span>
            </a>

            <!-- General Courses -->
            <a href="general_courses.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'general_courses.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">General Courses</span>
            </a>

            <!-- Upskilling -->
            <a href="upskilling.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'upskilling.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Upskilling</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="my-6 border-t border-gray-700/50"></div>

        <!-- Account Section -->
        <div class="space-y-1">
            <a href="../logout.php" class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group text-red-400 hover:text-red-300 hover:bg-red-500/10">
                <i class="fas fa-sign-out-alt w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Logout</span>
            </a>
        </div>
    </nav>

    <!-- User Profile Section (Bottom) -->
    <div class="border-t border-gray-700/50 p-4 bg-gray-800/50">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold overflow-hidden" id="sidebarAvatar">
                <?php if ($profile_picture): ?>
                    <img src="<?php echo $profile_picture; ?>?t=<?php echo time(); ?>" class="w-full h-full object-cover" alt="Profile">
                <?php else: ?>
                    <span id="sidebarInitials" class="text-sm"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="sidebar-text flex-1">
                <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-xs text-gray-400 truncate">Employee</p>
            </div>
        </div>
    </div>
</aside>

<style>
/* ===== PROFESSIONAL SIDEBAR STYLES ===== */
* {
    font-family: 'Inter', sans-serif;
}

#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 16rem;
    z-index: 50;
    overflow-y: auto;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    scrollbar-width: thin;
}

#sidebar.w-20 {
    width: 5rem;
}

#sidebar.w-20 .sidebar-text {
    display: none;
}

#sidebar.w-20 .logo-area {
    display: none;
}

#sidebar.w-20 .nav-link {
    justify-content: center;
    padding: 0.75rem;
}

#sidebar.w-20 .nav-link i {
    margin: 0;
    width: auto;
}

/* Nav link styles - Professional */
.nav-link {
    position: relative;
    color: #d1d5db;
    transition: all 0.2s ease;
}

.nav-link i {
    color: #9ca3af;
    transition: color 0.2s ease;
    width: 1.25rem;
    text-align: center;
}

/* Active state - Modern gradient */
.nav-link.active {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.1));
    color: white;
}

.nav-link.active i {
    color: #60a5fa;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 70%;
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    border-radius: 0 4px 4px 0;
}

/* Hover state */
.nav-link:hover:not(.active) {
    background: rgba(255, 255, 255, 0.06);
    color: white;
    transform: translateX(2px);
}

.nav-link:hover:not(.active) i {
    color: #93c5fd;
}

/* Logout special styling */
.nav-link.text-red-400 {
    color: #f87171;
}

.nav-link.text-red-400 i {
    color: #f87171;
}

.nav-link.text-red-400:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
}

.nav-link.text-red-400:hover i {
    color: #fca5a5;
}

/* Scrollbar */
#sidebar::-webkit-scrollbar {
    width: 4px;
}

#sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

#sidebar::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 4px;
}

#sidebar::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Logo styling - UpStaff with Academy below */
.logo-area {
    flex: 1;
}

.logo-area .flex-col {
    display: flex;
    flex-direction: column;
}

/* Mobile responsive */
@media (max-width: 768px) {
    #sidebar {
        transform: translateX(-100%);
        box-shadow: none;
    }
    #sidebar.mobile-open {
        transform: translateX(0);
        box-shadow: 5px 0 25px rgba(0, 0, 0, 0.3);
    }
}

/* User profile section */
#sidebarAvatar {
    flex-shrink: 0;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const collapseBtn = document.getElementById("collapseBtn");
    const sidebar = document.getElementById("sidebar");

    // Collapse sidebar (toggles width)
    if (collapseBtn) {
        collapseBtn.addEventListener("click", () => {
            sidebar.classList.toggle("w-64");
            sidebar.classList.toggle("w-20");

            // Dispatch event for main content to adjust margin
            window.dispatchEvent(new Event('sidebarToggle'));
        });
    }

    // Mobile menu toggle
    const createMobileToggle = () => {
        if (window.innerWidth <= 768 && !document.querySelector('.mobile-menu-toggle')) {
            const menuToggle = document.createElement('button');
            menuToggle.className = 'mobile-menu-toggle fixed top-4 left-4 z-50 text-gray-700 hover:text-gray-900 text-xl bg-white p-2 rounded-lg shadow-lg';
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(menuToggle);
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('mobile-open');
            });
        }
    };
    createMobileToggle();

    document.addEventListener('click', (event) => {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !event.target.closest('.mobile-menu-toggle')) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
        } else {
            createMobileToggle();
        }
    });
});

// Function to update sidebar avatar
function updateSidebarAvatar(profilePictureUrl) {
    const sidebarAvatar = document.getElementById('sidebarAvatar');
    const userInitial = '<?php echo strtoupper(substr($user_name, 0, 1)); ?>';
    
    if (sidebarAvatar) {
        if (profilePictureUrl) {
            const urlWithTimestamp = profilePictureUrl + '?t=' + new Date().getTime();
            sidebarAvatar.innerHTML = `<img src="${urlWithTimestamp}" class="w-full h-full object-cover" alt="Profile">`;
        } else {
            sidebarAvatar.innerHTML = `<span id="sidebarInitials" class="text-sm">${userInitial}</span>`;
        }
    }
}

// Listen for profile picture updates
window.addEventListener('profilePictureUpdated', function(event) {
    if (event.detail && event.detail.profile_picture) {
        updateSidebarAvatar(event.detail.profile_picture);
    }
});
</script>