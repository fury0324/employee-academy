<?php
// Admin Sidebar – Employee Design Style (same content, same URLs)
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['firstname'] ?? 'Admin';
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch profile picture from database for sidebar
$profile_picture = null;

if ($user_id) {
    require_once __DIR__ . '/../config/db.php';
    
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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            <a href="../admin/dashboard.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group 
                      <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Dashboard</span>
            </a>

            <!-- User Management Dropdown -->
            <div class="relative">
                <?php 
                $isUserManagementActive = in_array($current_page, ['admin_approval.php', 'approved_users.php', 'rejected_user.php']);
                ?>
                <button id="userMenuBtn"
                        class="nav-link flex items-center justify-between w-full px-4 py-2.5 rounded-lg transition-all duration-200 group
                               <?php echo $isUserManagementActive ? 'active' : ''; ?>">
                    <div class="flex items-center">
                        <i class="fas fa-users w-5 text-lg"></i>
                        <span class="ml-3 sidebar-text text-sm font-medium">User Management</span>
                    </div>
                    <i id="userArrow" class="fas fa-chevron-down text-xs transition-transform duration-300 sidebar-text 
                       <?php echo $isUserManagementActive ? 'rotate-180' : ''; ?>"></i>
                </button>

                <div id="userDropdown" class="mt-2 ml-8 space-y-0 <?php echo $isUserManagementActive ? '' : 'hidden'; ?>">
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-700/50 ml-0"></div>
                        <a href="../login/admin_approval.php"
                        class="dropdown-link flex items-center px-5 py-3 rounded-lg text-xs transition-all duration-200 relative
                                <?php echo $current_page == 'admin_approval.php' ? 'active-progress' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                            <i class="fas fa-clock w-5 text-xs mr-3"></i>
                            <span>Pending Approval</span>
                        </a>
                    </div>
                    <div class="border-t border-gray-700/30 my-1"></div>
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-700/50 ml-0"></div>
                        <a href="../admin/approved_users.php"
                        class="dropdown-link flex items-center px-5 py-3 rounded-lg text-xs transition-all duration-200 relative
                                <?php echo $current_page == 'approved_users.php' ? 'active-progress' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                            <i class="fas fa-check-circle w-5 text-xs mr-3"></i>
                            <span>Approved Users</span>
                        </a>
                    </div>
                    <div class="border-t border-gray-700/30 my-1"></div>
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-700/50 ml-0"></div>
                        <a href="../admin/rejected_user.php"
                        class="dropdown-link flex items-center px-5 py-3 rounded-lg text-xs transition-all duration-200 relative
                                <?php echo $current_page == 'rejected_user.php' ? 'active-progress' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                            <i class="fas fa-ban w-5 text-xs mr-3"></i>
                            <span>Rejected Users</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Courses -->
            <a href="../admin/course_management.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'course_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-book w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Courses</span>
            </a>

            <!-- Retake Request -->
            <a href="../admin/Request_retake.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'Request_retake.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Retake Request</span>
            </a>

            <!-- Certificate Validation -->
            <a href="../admin/cert_validation.php" 
               class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group
                      <?php echo $current_page == 'cert_validation.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate w-5 text-lg"></i>
                <span class="ml-3 sidebar-text text-sm font-medium">Certificate Validation</span>
            </a>

            <!-- Settings Dropdown -->
            <div class="relative">
                <?php 
                $isSettingsActive = in_array($current_page, ['audit_logs.php', 'Generate_report.php', 'account_settings.php']);
                ?>
                <button id="settingsBtn"
                        class="nav-link flex items-center justify-between w-full px-4 py-2.5 rounded-lg transition-all duration-200 group
                               <?php echo $isSettingsActive ? 'active' : ''; ?>">
                    <div class="flex items-center">
                        <i class="fas fa-cog w-5 text-lg"></i>
                        <span class="ml-3 sidebar-text text-sm font-medium">Settings</span>
                    </div>
                    <i id="settingsArrow" class="fas fa-chevron-down text-xs transition-transform duration-300 sidebar-text
                       <?php echo $isSettingsActive ? 'rotate-180' : ''; ?>"></i>
                </button>

                <div id="settingsDropdown" class="mt-2 ml-8 space-y-0 <?php echo $isSettingsActive ? '' : 'hidden'; ?>">
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-700/50 ml-0"></div>
                        <a href="../admin/audit_logs.php" 
                        class="dropdown-link flex items-center px-5 py-3 rounded-lg text-xs transition-all duration-200 relative
                                <?php echo $current_page == 'audit_logs.php' ? 'active-progress' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                            <i class="fas fa-history w-5 text-xs mr-3"></i>
                            <span>Audit Logs</span>
                        </a>
                    </div>
                    <div class="border-t border-gray-700/30 my-1"></div>
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-700/50 ml-0"></div>
                        <a href="../admin/Generate_report.php" 
                        class="dropdown-link flex items-center px-5 py-3 rounded-lg text-xs transition-all duration-200 relative
                                <?php echo $current_page == 'Generate_report.php' ? 'active-progress' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                            <i class="fas fa-chart-line w-5 text-xs mr-3"></i>
                            <span>Generate Report</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-6 border-t border-gray-700/50"></div>

            <!-- Logout -->
            <div class="space-y-1">
                <a href="../logout.php" class="nav-link flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 group text-red-400 hover:text-red-300 hover:bg-red-500/10">
                    <i class="fas fa-sign-out-alt w-5 text-lg"></i>
                    <span class="ml-3 sidebar-text text-sm font-medium">Logout</span>
                </a>
            </div>
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
                <p class="text-xs text-gray-400 truncate">Administrator</p>
            </div>
        </div>
    </div>
</aside>

<style>
/* ===== PROFESSIONAL SIDEBAR STYLES (Employee Design) ===== */
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

#sidebar.w-20 .dropdown-link span {
    display: none;
}

#sidebar.w-20 .dropdown-link i {
    margin-right: 0;
}

#sidebar.w-20 #userDropdown,
#sidebar.w-20 #settingsDropdown {
    display: none !important;
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

/* Active state - Modern gradient (Employee style) */
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

/* Dropdown link enhancements */
.dropdown-link {
    transition: all 0.2s ease;
    margin: 0;
    border-radius: 0.5rem;
    position: relative;
    background: transparent;
}

.dropdown-link i {
    width: 1.25rem;
    text-align: center;
}

/* Active dropdown link with progress bar line */
.dropdown-link.active-progress {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.dropdown-link.active-progress::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    border-radius: 0 4px 4px 0;
    box-shadow: 0 0 8px rgba(59,130,246,0.5);
    transition: all 0.2s ease;
}

/* Separator lines */
#userDropdown .border-t,
#settingsDropdown .border-t {
    opacity: 0.5;
    margin: 0.25rem 0;
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

/* Logo styling */
.logo-area span {
    background: linear-gradient(135deg, #ffffff, #94a3b8);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
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

/* Dropdown line bar styling */
.dropdown-link {
    transition: all 0.2s ease;
    margin: 0;
    border-radius: 0.5rem;
    position: relative;
    background: transparent;
}

/* Active dropdown link with progress bar line (retain for active page) */
.dropdown-link.active-progress {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.dropdown-link.active-progress::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    border-radius: 0 4px 4px 0;
    box-shadow: 0 0 8px rgba(59,130,246,0.5);
    transition: all 0.2s ease;
}

/* Continuous line bar for all dropdown items */
#userDropdown .relative,
#settingsDropdown .relative {
    position: relative;
}

#userDropdown .relative .absolute,
#settingsDropdown .relative .absolute {
    pointer-events: none;
}

/* Hover effect for dropdown items */
.dropdown-link:hover:not(.active-progress) {
    background: rgba(255, 255, 255, 0.05);
    color: white;
    transform: translateX(2px);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const collapseBtn = document.getElementById("collapseBtn");
    const sidebar = document.getElementById("sidebar");

    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");
    const userArrow = document.getElementById("userArrow");

    const settingsBtn = document.getElementById("settingsBtn");
    const settingsDropdown = document.getElementById("settingsDropdown");
    const settingsArrow = document.getElementById("settingsArrow");

    // Function to close all dropdowns
    function closeAllDropdowns() {
        if (userDropdown && !userDropdown.classList.contains('hidden')) {
            userDropdown.classList.add('hidden');
            if (userArrow) userArrow.classList.remove('rotate-180');
        }
        if (settingsDropdown && !settingsDropdown.classList.contains('hidden')) {
            settingsDropdown.classList.add('hidden');
            if (settingsArrow) settingsArrow.classList.remove('rotate-180');
        }
    }

    // Collapse sidebar
    if (collapseBtn) {
        collapseBtn.addEventListener("click", () => {
            sidebar.classList.toggle("w-64");
            sidebar.classList.toggle("w-20");

            // Close dropdowns when collapsed
            closeAllDropdowns();

            window.dispatchEvent(new Event('sidebarToggle'));
        });
    }

    // User Management dropdown toggle
    if (userBtn && userDropdown && userArrow) {
        userBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains("w-64")) {
                // Close other dropdown first
                if (settingsDropdown && !settingsDropdown.classList.contains('hidden')) {
                    settingsDropdown.classList.add('hidden');
                    if (settingsArrow) settingsArrow.classList.remove('rotate-180');
                }
                userDropdown.classList.toggle('hidden');
                userArrow.classList.toggle('rotate-180');
            }
        });
    }

    // Settings dropdown toggle
    if (settingsBtn && settingsDropdown && settingsArrow) {
        settingsBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains("w-64")) {
                // Close other dropdown first
                if (userDropdown && !userDropdown.classList.contains('hidden')) {
                    userDropdown.classList.add('hidden');
                    if (userArrow) userArrow.classList.remove('rotate-180');
                }
                settingsDropdown.classList.toggle('hidden');
                settingsArrow.classList.toggle('rotate-180');
            }
        });
    }

    // Close dropdowns when clicking on sidebar links (except dropdown buttons)
    const sidebarLinks = document.querySelectorAll('.nav-link:not(#userMenuBtn):not(#settingsBtn)');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeAllDropdowns();
        });
    });

    // Keep dropdowns open if current page is inside them (on page load)
    const currentPage = window.location.pathname.split('/').pop();
    
    // User Management pages
    if (currentPage === 'admin_approval.php' || currentPage === 'approved_users.php' || currentPage === 'rejected_user.php') {
        if (userDropdown) {
            userDropdown.classList.remove('hidden');
            if (userArrow) userArrow.classList.add('rotate-180');
        }
        if (settingsDropdown) {
            settingsDropdown.classList.add('hidden');
            if (settingsArrow) settingsArrow.classList.remove('rotate-180');
        }
    }
    
    // Settings pages (including Generate_report.php)
    if (currentPage === 'audit_logs.php' || currentPage === 'Generate_report.php' || currentPage === 'account_settings.php') {
        if (settingsDropdown) {
            settingsDropdown.classList.remove('hidden');
            if (settingsArrow) settingsArrow.classList.add('rotate-180');
        }
        if (userDropdown) {
            userDropdown.classList.add('hidden');
            if (userArrow) userArrow.classList.remove('rotate-180');
        }
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

    // Close mobile menu when clicking outside
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