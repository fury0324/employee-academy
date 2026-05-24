<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle certificate download request
if (isset($_GET['download']) && isset($_GET['id'])) {
    $cert_id = intval($_GET['download']);
    
    $verify_query = "SELECT id FROM certificates WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $cert_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE certificates SET download_count = download_count + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $cert_id);
        $update_stmt->execute();
        
        $_SESSION['success_message'] = "Certificate download started!";
        header("Location: certificates.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid certificate access.";
        header("Location: certificates.php");
        exit();
    }
}

// Fetch all certificates with course details
$certificates_query = "
    SELECT 
        c.*,
        cs.title as course_title,
        cs.type as course_type,
        cs.category as course_category,
        cs.difficulty,
        cs.description,
        cs.thumbnail_url,
        CASE 
            WHEN cs.type = 'upskilling' THEN 'Upskilling Course'
            WHEN cs.type = 'general' THEN 'General Course'
            ELSE 'Standard Course'
        END as course_type_label
    FROM certificates c
    LEFT JOIN courses cs ON c.course_id = cs.id
    WHERE c.user_id = ?
    ORDER BY c.issued_at DESC
";

$stmt = $conn->prepare($certificates_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certificates_result = $stmt->get_result();
$certificates = $certificates_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get success/error messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Function to get thumbnail path
function getThumbnailPath($thumbnail_url) {
    if (empty($thumbnail_url)) {
        return null;
    }
    
    if (filter_var($thumbnail_url, FILTER_VALIDATE_URL)) {
        return $thumbnail_url;
    }
    
    $thumbnail_url = ltrim($thumbnail_url, './');
    
    if (strpos($thumbnail_url, 'uploads/') === 0) {
        return '../' . $thumbnail_url;
    }
    
    return '../uploads/' . $thumbnail_url;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - Upstaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .certificate-card {
            transition: all 0.3s ease;
        }
        
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        
        .status-badge {
            transition: all 0.2s ease;
        }
        
        .view-btn:hover {
            transform: translateX(3px);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .certificate-card {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .filter-btn.active {
            background-color: #3b82f6;
            color: white;
        }
        
        .filter-btn:not(.active) {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        .filter-btn:not(.active):hover {
            background-color: #e5e7eb;
        }
        
        .course-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .certificate-card:hover .course-thumbnail {
            transform: scale(1.05);
        }
        
        .thumbnail-container {
            overflow: hidden;
            position: relative;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 16rem;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Include Sidebar -->
    <?php include_once 'sidebar.php'; ?>
    
    <!-- Include Header -->
    <?php include_once 'header.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="p-6" style="padding-top: 110px;">
            
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all active" data-type="all">
                            <i class="fas fa-list mr-2"></i>All
                        </button>
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all" data-type="general">
                            <i class="fas fa-book mr-2"></i>General Courses
                        </button>
                        <button class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-all" data-type="upskilling">
                            <i class="fas fa-chart-line mr-2"></i>Upskilling
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <input type="text" id="searchCertificates" placeholder="Search certificates..." 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
                    </div>
                </div>
            </div>
            
            <!-- All Certificates Section -->
            <div id="all-certificates">
                <h2 class="text-xl font-bold text-gray-800 mb-4">All Certificates</h2>
                
                <?php if (empty($certificates)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-certificate text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">No Certificates Yet</h3>
                        <p class="text-gray-500 mb-4">You haven't earned any certificates yet. Complete courses to earn certifications!</p>
                        <a href="general_courses.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Browse Courses <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Certificates Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="certificatesGrid">
                        <?php foreach ($certificates as $index => $cert): 
                            $thumbnail_path = getThumbnailPath($cert['thumbnail_url']);
                            // Determine which certificate file to use
                            $cert_file = ($cert['course_type'] ?? 'general') == 'upskilling' ? 'upskilling_certificate.php' : 'generate_certificate.php';
                        ?>
                        <div class="certificate-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100" 
                             data-score="<?php echo $cert['final_score']; ?>"
                             data-type="<?php echo $cert['course_type'] ?? 'general'; ?>"
                             data-title="<?php echo strtolower(htmlspecialchars($cert['course_title'])); ?>"
                             style="animation-delay: <?php echo $index * 0.05; ?>s">
                            
                            <!-- Certificate Header with Thumbnail -->
                            <div class="relative h-44 thumbnail-container">
                                <?php if ($thumbnail_path): ?>
                                    <img src="<?php echo $thumbnail_path; ?>" 
                                         alt="<?php echo htmlspecialchars($cert['course_title']); ?>"
                                         class="course-thumbnail"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <!-- Overlay gradient -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                                    <!-- Fallback div that shows if image fails to load -->
                                    <div style="display: none;" class="absolute inset-0">
                                        <?php 
                                        $fallback_color = ($cert['course_type'] ?? 'general') == 'upskilling' 
                                            ? 'from-purple-600 to-indigo-600' 
                                            : 'from-blue-600 to-purple-600';
                                        ?>
                                        <div class="w-full h-full bg-gradient-to-r <?php echo $fallback_color; ?> flex items-center justify-center">
                                            <i class="fas fa-certificate text-white text-6xl opacity-50"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $fallback_color = ($cert['course_type'] ?? 'general') == 'upskilling' 
                                        ? 'from-purple-600 to-indigo-600' 
                                        : 'from-blue-600 to-purple-600';
                                    ?>
                                    <div class="w-full h-full bg-gradient-to-r <?php echo $fallback_color; ?> flex items-center justify-center">
                                        <i class="fas fa-certificate text-white text-6xl opacity-50"></i>
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                <?php endif; ?>
                                
                                <!-- Status Badge -->
                                <div class="absolute top-3 right-3 z-10">
                                    <span class="status-badge px-3 py-1 rounded-full text-xs font-semibold <?php echo ($cert['final_score'] >= 70) ? 'bg-green-500' : 'bg-red-500'; ?> text-white shadow-lg">
                                        <?php echo ($cert['final_score'] >= 70) ? '✓ PASSED' : '✗ FAILED'; ?>
                                    </span>
                                </div>
                                
                                <!-- Course Type Badge on Thumbnail -->
                                <div class="absolute top-3 left-3 z-10">
                                    <?php if (($cert['course_type'] ?? 'general') == 'upskilling'): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white shadow-lg">
                                            <i class="fas fa-chart-line mr-1 text-xs"></i>
                                            Upskilling
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-blue-600 text-white shadow-lg">
                                            <i class="fas fa-book mr-1 text-xs"></i>
                                            General
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Score Badge on Thumbnail -->
                                <div class="absolute bottom-3 left-3 z-10">
                                    <div class="bg-white/95 backdrop-blur-sm rounded-lg px-3 py-1 shadow-lg">
                                        <span class="text-sm font-bold <?php echo ($cert['final_score'] >= 70) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <i class="fas fa-star mr-1"></i> Score: <?php echo $cert['final_score']; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Certificate Body -->
                            <div class="p-4">
                                <h3 class="font-bold text-gray-800 text-lg mb-2 line-clamp-1">
                                    <?php echo htmlspecialchars($cert['course_title']); ?>
                                </h3>
                                
                                <?php if ($cert['course_category']): ?>
                                <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                                    <span class="flex items-center">
                                        <i class="fas fa-folder mr-1 text-xs"></i>
                                        <?php echo htmlspecialchars($cert['course_category']); ?>
                                    </span>
                                    <?php if ($cert['difficulty']): ?>
                                    <span>•</span>
                                    <span class="flex items-center">
                                        <i class="fas fa-signal mr-1 text-xs"></i>
                                        <?php echo ucfirst(htmlspecialchars($cert['difficulty'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            
                                <!-- Certificate Details -->
                                <div class="border-t border-gray-100 pt-3 mt-2">
                                    <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                        <div>
                                            <p class="text-gray-500 text-xs">Quizzes Passed</p>
                                            <p class="font-semibold text-gray-700">
                                                <?php echo $cert['total_quizzes_passed']; ?>/<?php echo $cert['total_quizzes']; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs">Issued Date</p>
                                            <p class="font-semibold text-gray-700 text-sm">
                                                <?php echo date('M d, Y', strtotime($cert['issued_at'])); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs">Valid Until</p>
                                            <p class="font-semibold text-gray-700 text-sm">
                                                <?php echo $cert['expiry_date'] ? date('M d, Y', strtotime($cert['expiry_date'])) : 'N/A'; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs">Certificate No.</p>
                                            <p class="font-semibold text-gray-700 text-xs truncate" title="<?php echo htmlspecialchars($cert['certificate_number']); ?>">
                                                <?php echo htmlspecialchars(substr($cert['certificate_number'], 0, 15)) . '...'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons - FIXED: Now uses correct certificate file based on course type -->
                                    <div class="flex space-x-2">
                                        <a href="<?php echo $cert_file; ?>?course_id=<?php echo $cert['course_id']; ?>" 
                                           class="view-btn flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition text-center">
                                            <i class="fas fa-eye mr-1"></i> View Certificate
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
                            <div class="flex flex-col items-center space-y-4 md:w-48 flex-shrink-0">
                                <div class="relative group cursor-pointer avatar-container" id="avatarContainer">
                                    <div class="w-36 h-36 md:w-40 md:h-40 rounded-full bg-gradient-to-tr from-gray-100 to-gray-200 shadow-md border-4 border-white ring-1 ring-gray-200 overflow-hidden transition-all duration-200 group-hover:scale-[1.02] group-hover:shadow-lg">
                                        <img id="profileAvatar" class="w-full h-full object-cover" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23cbd5e1'/%3E%3Ccircle cx='50' cy='38' r='16' fill='%236b7280'/%3E%3Cpath d='M22 76 Q50 56 78 76' stroke='%234b5563' stroke-width='6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E" alt="Profile picture preview">
                                    </div>
                                    <div class="absolute bottom-1 right-1 bg-white/95 backdrop-blur-sm rounded-full p-2 shadow-md border border-gray-200 transition group-hover:bg-white">
                                        <i class="fas fa-camera text-gray-700 text-sm"></i>
                                    </div>
                                </div>
                                <button type="button" id="triggerUploadBtn" class="text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full transition flex items-center gap-1">
                                    <i class="fas fa-cloud-upload-alt"></i> Change photo
                                </button>
                                <p class="text-xs text-gray-400 text-center">Click image or button<br>JPG, PNG up to 5MB</p>
                                <input type="file" id="profileUploadInput" name="profile_picture" accept="image/jpeg, image/png, image/webp, image/jpg" style="display: none;">
                            </div>

                            <div class="flex-1">
                                <form id="profileForm">
                                    <div class="grid grid-cols-1 gap-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                                <input type="text" name="firstname" id="profile_firstname" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                                <input type="text" name="lastname" id="profile_lastname" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                            <input type="email" name="email" id="profile_email" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Position / Job Title</label>
                                            <input type="text" name="position" id="profile_position" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                            <input type="tel" name="phone" id="profile_phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                            <textarea name="address" id="profile_address" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
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

    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        function updateMainContentMargin() {
            if (!sidebar || !mainContent) return;
            
            if (sidebar.classList.contains('w-20')) {
                mainContent.classList.add('sidebar-collapsed');
            } else {
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
        
        window.addEventListener('sidebarToggle', function() {
            updateMainContentMargin();
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            updateMainContentMargin();
        });
        
        // Filter functionality
        let currentType = 'all';
        let searchTerm = '';
        
        const filterButtons = document.querySelectorAll('.filter-btn');
        const searchInput = document.getElementById('searchCertificates');
        const certificates = document.querySelectorAll('.certificate-card');
        
        function filterCertificates() {
            certificates.forEach(cert => {
                const certType = cert.dataset.type;
                const certScore = parseInt(cert.dataset.score);
                const certTitle = cert.dataset.title;
                
                let typeMatch = currentType === 'all' || certType === currentType;
                let searchMatch = searchTerm === '' || certTitle.includes(searchTerm);
                
                if (typeMatch && searchMatch) {
                    cert.style.display = '';
                } else {
                    cert.style.display = 'none';
                }
            });
        }
        
        // Type filter buttons
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentType = this.dataset.type;
                filterCertificates();
            });
        });
        
        // Search filter
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                searchTerm = e.target.value.toLowerCase();
                filterCertificates();
            });
        }
        
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-orange-600'
            }`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Profile Modal Functions
        function openProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
            // Fetch user data via AJAX
            fetch('get_user_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profile_firstname').value = data.data.firstname || '';
                        document.getElementById('profile_lastname').value = data.data.lastname || '';
                        document.getElementById('profile_email').value = data.data.email || '';
                        document.getElementById('profile_position').value = data.data.position || '';
                        document.getElementById('profile_phone').value = data.data.phone || '';
                        document.getElementById('profile_address').value = data.data.address || '';
                        if (data.data.profile_picture) {
                            document.getElementById('profileAvatar').src = data.data.profile_picture;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }
        
        function closeSecurityModal() {
            document.getElementById('securityModal').classList.add('hidden');
        }
        
        // Profile form submission
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('profileMessage');
                if (data.success) {
                    messageDiv.className = 'text-sm text-green-600 bg-green-50 p-3 rounded-lg';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    messageDiv.classList.remove('hidden');
                    setTimeout(() => closeProfileModal(), 1500);
                } else {
                    messageDiv.className = 'text-sm text-red-600 bg-red-50 p-3 rounded-lg';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                    messageDiv.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.getElementById('profileMessage');
                messageDiv.className = 'text-sm text-red-600 bg-red-50 p-3 rounded-lg';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
                messageDiv.classList.remove('hidden');
            });
        });
        
        // Security form submission
        document.getElementById('securityForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('securityMessage');
                if (data.success) {
                    messageDiv.className = 'text-sm text-green-600 bg-green-50 p-3 rounded-lg';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    messageDiv.classList.remove('hidden');
                    setTimeout(() => {
                        closeSecurityModal();
                        document.getElementById('securityForm').reset();
                    }, 1500);
                } else {
                    messageDiv.className = 'text-sm text-red-600 bg-red-50 p-3 rounded-lg';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                    messageDiv.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.getElementById('securityMessage');
                messageDiv.className = 'text-sm text-red-600 bg-red-50 p-3 rounded-lg';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
                messageDiv.classList.remove('hidden');
            });
        });
        
        // Avatar upload
        const triggerUploadBtn = document.getElementById('triggerUploadBtn');
        const avatarContainer = document.getElementById('avatarContainer');
        const profileUploadInput = document.getElementById('profileUploadInput');
        
        function triggerUpload() {
            if (profileUploadInput) profileUploadInput.click();
        }
        
        if (triggerUploadBtn) triggerUploadBtn.addEventListener('click', triggerUpload);
        if (avatarContainer) avatarContainer.addEventListener('click', triggerUpload);
        
        if (profileUploadInput) {
            profileUploadInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('profile_picture', file);
                
                fetch('upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileAvatar').src = data.file_path + '?t=' + new Date().getTime();
                        showToast('Profile picture updated successfully!', 'success');
                    } else {
                        showToast(data.message || 'Failed to upload image', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        // Make functions globally available
        window.openProfileModal = openProfileModal;
        window.closeProfileModal = closeProfileModal;
        window.closeSecurityModal = closeSecurityModal;
    </script>
</body>
</html>